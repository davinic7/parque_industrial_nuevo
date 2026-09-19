<?php
/**
 * Formulario Dinámico Genérico - Empresa
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['empresa'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Formulario';
$db = getDB();
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$empresa_id) {
    set_flash('error', 'No se encontró la empresa asociada a su cuenta');
    redirect('dashboard.php');
}

$formulario_id = (int)($_GET['id'] ?? 0);
$mensaje = '';
$error = '';

if ($formulario_id <= 0) {
    set_flash('error', 'Formulario no especificado.');
    redirect('dashboard.php');
}

// Cargar formulario
$stmt = $db->prepare("SELECT * FROM formularios_dinamicos WHERE id = ? AND estado = 'publicado'");
$stmt->execute([$formulario_id]);
$formulario = $stmt->fetch();

if (!$formulario) {
    $error = 'El formulario solicitado no está disponible.';
} else {
    $page_title = $formulario['titulo'];

    $plazo_info = null;
    try {
        $stPl = $db->prepare("
            SELECT COALESCE(fd.plazo_hasta, fe.fecha_limite) AS limite
            FROM formulario_destinatarios fd
            INNER JOIN formulario_envios fe ON fe.id = fd.envio_id
            WHERE fd.empresa_id = ? AND fe.formulario_id = ? AND fd.respondido = 0
            ORDER BY fe.created_at DESC
            LIMIT 1
        ");
        $stPl->execute([$empresa_id, $formulario_id]);
        $plazo_info = $stPl->fetch();
    } catch (Exception $e) {
        $plazo_info = null;
    }

    // Cargar preguntas
    $stmt = $db->prepare("SELECT * FROM formulario_preguntas WHERE formulario_id = ? ORDER BY orden, id");
    $stmt->execute([$formulario_id]);
    $preguntas = $stmt->fetchAll();

    // Cargar respuesta existente
    $stmt = $db->prepare("
        SELECT * FROM formulario_respuestas
        WHERE formulario_id = ? AND empresa_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$formulario_id, $empresa_id]);
    $respuesta_existente = $stmt->fetch();

    $valores_actuales = [];
    if ($respuesta_existente) {
        $json = json_decode($respuesta_existente['respuestas'] ?? '{}', true);
        if (is_array($json)) {
            $valores_actuales = $json;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $error = 'Token de seguridad inválido. Recargue la página.';
        } else {
            try {
                $accion = $_POST['accion'] ?? 'guardar';
                $estado = $accion === 'enviar' ? 'enviado' : 'borrador';

                $respuestas = [];
                $errores_campos = [];

                foreach ($preguntas as $p) {
                    $campo_name = 'campo_' . $p['id'];
                    $tipo = $p['tipo'];
                    $requerido = (bool)$p['requerido'];

                    if ($tipo === 'archivo') {
                        $valor = $valores_actuales[$p['id']] ?? null;
                        if (!empty($_FILES[$campo_name]['name'])) {
                            $allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
                            $res = store_upload($_FILES[$campo_name], 'formularios', $allowed);
                            if ($res['success']) {
                                $valor = $res['filename'];
                            } else {
                                $errores_campos[$p['id']] = $res['error'];
                            }
                        }
                    } elseif ($tipo === 'tabla') {
                        $val_raw = $_POST[$campo_name] ?? null;
                        $valor = is_array($val_raw) ? json_encode($val_raw, JSON_UNESCAPED_UNICODE) : ($val_raw ?? '');
                    } else {
                        $valor = $_POST[$campo_name] ?? null;
                        if (is_array($valor)) {
                            $valor = array_values(array_filter($valor, static function ($v) {
                                return $v !== '' && $v !== null;
                            }));
                        } elseif ($tipo === 'checkbox') {
                            $valor = isset($_POST[$campo_name]) ? 1 : 0;
                        }
                    }

                    if ($estado === 'enviado' && $requerido) {
                        $vacio = ($valor === null || $valor === '' || (is_array($valor) && empty($valor)));
                        if ($vacio) {
                            $errores_campos[$p['id']] = 'Este campo es obligatorio.';
                        }
                    }

                    $respuestas[$p['id']] = $valor;
                }

                if (!empty($errores_campos)) {
                    $error = 'Revise los campos marcados en rojo.';
                    $valores_actuales = $respuestas;
                } else {
                    $json_respuestas = json_encode($respuestas, JSON_UNESCAPED_UNICODE);
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $usuario_id = $_SESSION['user_id'] ?? null;

                    if ($respuesta_existente) {
                        $stmt = $db->prepare("
                            UPDATE formulario_respuestas
                            SET estado = ?, respuestas = ?, ip = ?, usuario_id = ?,
                                enviado_at = CASE WHEN ? = 'enviado' THEN NOW() ELSE enviado_at END
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $estado,
                            $json_respuestas,
                            $ip,
                            $usuario_id,
                            $estado,
                            $respuesta_existente['id'],
                        ]);
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO formulario_respuestas
                            (formulario_id, empresa_id, usuario_id, estado, respuestas, ip, enviado_at)
                            VALUES (?, ?, ?, ?, ?, ?, CASE WHEN ? = 'enviado' THEN NOW() ELSE NULL END)
                        ");
                        $stmt->execute([
                            $formulario_id,
                            $empresa_id,
                            $usuario_id,
                            $estado,
                            $json_respuestas,
                            $ip,
                            $estado,
                        ]);
                    }

                    log_activity(
                        $estado === 'enviado' ? 'formulario_dinamico_enviado' : 'formulario_dinamico_borrador',
                        'formulario_respuestas',
                        $empresa_id
                    );

                    if ($estado === 'enviado') {
                        try {
                            $db->prepare("
                                UPDATE formulario_destinatarios fd
                                INNER JOIN formulario_envios fe ON fe.id = fd.envio_id
                                SET fd.respondido = 1, fd.fecha_respuesta = NOW()
                                WHERE fe.formulario_id = ? AND fd.empresa_id = ? AND fd.respondido = 0
                            ")->execute([$formulario_id, $empresa_id]);
                        } catch (Exception $e) {
                            // sin tablas de envío
                        }
                    }

                    $mensaje = $estado === 'enviado'
                        ? 'Formulario enviado correctamente.'
                        : 'Borrador guardado correctamente.';

                    $stmt = $db->prepare("
                        SELECT * FROM formulario_respuestas
                        WHERE formulario_id = ? AND empresa_id = ?
                        ORDER BY id DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$formulario_id, $empresa_id]);
                    $respuesta_existente = $stmt->fetch();
                    $valores_actuales = json_decode($respuesta_existente['respuestas'] ?? '{}', true) ?? [];
                }
            } catch (Exception $e) {
                error_log('Error formulario dinámico: ' . $e->getMessage());
                $error = 'Ocurrió un error al procesar el formulario. Intente nuevamente.';
            }
        }
    }
}

$empresa_nav = 'formularios';
$extra_head = '<link href="' . PUBLIC_URL . '/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="' . PUBLIC_URL . '/vendor/leaflet/leaflet.css">';
require_once BASEPATH . '/includes/empresa_layout_header.php';
?>
        <div class="mb-3">
            <a href="formularios.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Volver a mis formularios
            </a>
        </div>
        <h1 class="h3 mb-4"><?= e($page_title) ?></h1>

        <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= e($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php
        if (!empty($plazo_info['limite']) && empty($error)) {
            $ts = strtotime($plazo_info['limite'] . ' 23:59:59');
            $dias = (int)floor(($ts - time()) / 86400);
            if ($dias < 0) {
                echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>La fecha límite para este formulario ya venció (' . e($plazo_info['limite']) . '). Contacte al ministerio si necesita una prórroga.</div>';
            } elseif ($dias <= 3) {
                echo '<div class="alert alert-warning"><i class="bi bi-clock me-2"></i>Fecha límite: <strong>' . e($plazo_info['limite']) . '</strong> (quedan ' . $dias . ' día(s)).</div>';
            } else {
                echo '<div class="alert alert-info py-2 small mb-3">Fecha límite sugerida: <strong>' . e($plazo_info['limite']) . '</strong></div>';
            }
        }
        ?>

        <?php if (!empty($formulario) && !empty($preguntas)): ?>
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= e($formulario['titulo']) ?></h5>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted small"><?= e($formulario['descripcion'] ?? '') ?></p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($preguntas as $p):
                            $campo_name  = 'campo_' . $p['id'];
                            $tipo        = $p['tipo'];
                            $valor       = $valores_actuales[$p['id']] ?? '';
                            $es_obligatorio = (bool)$p['requerido'];

                            // Decodificar opciones
                            $opciones = null;
                            $tabla_cols = [];
                            $tabla_rows = [];
                            if (!empty($p['opciones'])) {
                                $dec = json_decode($p['opciones'], true);
                                if (isset($dec['items'])) {
                                    $opciones = $dec['items'];
                                } elseif (isset($dec['cols'])) {
                                    $tabla_cols = $dec['cols'] ?? [];
                                    $tabla_rows = $dec['rows'] ?? [];
                                }
                            }

                            // Valores guardados de tabla (JSON string → array 2D)
                            $tabla_vals = [];
                            if ($tipo === 'tabla' && $valor !== '') {
                                $tv = is_string($valor) ? json_decode($valor, true) : $valor;
                                $tabla_vals = is_array($tv) ? $tv : [];
                            }
                        ?>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <?= e($p['etiqueta']) ?>
                                <?php if ($es_obligatorio): ?><span class="text-danger ms-1">*</span><?php endif; ?>
                            </label>
                            <?php if (!empty($p['ayuda'])): ?>
                                <div class="form-text mb-1"><?= e($p['ayuda']) ?></div>
                            <?php endif; ?>

                            <?php if ($tipo === 'texto'): ?>
                                <?php $val_str = is_array($valor) ? '' : (string)$valor; ?>
                                <input type="text" name="<?= e($campo_name) ?>" class="form-control campo-con-contador"
                                    maxlength="50"
                                    value="<?= e($val_str) ?>"
                                    <?= $es_obligatorio ? 'required' : '' ?>>
                                <div class="form-text char-counter-wrap">
                                    <span class="chars-remaining">50</span> caracteres restantes
                                </div>

                            <?php elseif ($tipo === 'textarea'): ?>
                                <?php $val_str = is_array($valor) ? '' : (string)$valor; ?>
                                <textarea name="<?= e($campo_name) ?>" class="form-control campo-con-contador"
                                    rows="4" maxlength="200"
                                    <?= $es_obligatorio ? 'required' : '' ?>><?= e($val_str) ?></textarea>
                                <div class="form-text char-counter-wrap">
                                    <span class="chars-remaining">200</span> caracteres restantes
                                </div>

                            <?php elseif ($tipo === 'numero'): ?>
                                <?php
                                $min_v = $p['min_valor'] ?? null;
                                $max_v = $p['max_valor'] ?? null;
                                $val_str = is_array($valor) ? '' : (string)$valor;
                                ?>
                                <input type="text" inputmode="decimal" name="<?= e($campo_name) ?>"
                                    class="form-control campo-con-contador" style="max-width:280px;"
                                    maxlength="25"
                                    value="<?= e($val_str) ?>"
                                    placeholder="Ingrese un número"
                                    <?= $es_obligatorio ? 'required' : '' ?>>
                                <div class="form-text char-counter-wrap">
                                    <span class="chars-remaining">25</span> dígitos restantes
                                    <?php if ($min_v !== null || $max_v !== null): ?>
                                    &nbsp;·&nbsp;
                                    <?php if ($min_v !== null && $max_v !== null): ?>Entre <?= e($min_v) ?> y <?= e($max_v) ?>
                                    <?php elseif ($min_v !== null): ?>Mínimo: <?= e($min_v) ?>
                                    <?php else: ?>Máximo: <?= e($max_v) ?><?php endif; ?>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($tipo === 'fecha'): ?>
                                <input type="date" name="<?= e($campo_name) ?>" class="form-control" style="max-width:200px;"
                                    value="<?= e(is_array($valor) ? '' : (string)$valor) ?>"
                                    <?= $es_obligatorio ? 'required' : '' ?>>

                            <?php elseif ($tipo === 'email'): ?>
                                <input type="email" name="<?= e($campo_name) ?>" class="form-control"
                                    value="<?= e(is_array($valor) ? '' : (string)$valor) ?>"
                                    <?= $es_obligatorio ? 'required' : '' ?>>

                            <?php elseif ($tipo === 'archivo_adjunto'): ?>
                                <?php
                                $adj_data    = !empty($p['opciones']) ? (json_decode($p['opciones'], true) ?: []) : [];
                                $adj_archivo = $adj_data['archivo'] ?? null;
                                $adj_url     = $adj_archivo ? uploads_resolve_url($adj_archivo, 'formularios') : null;
                                $adj_ext     = $adj_archivo ? strtolower(pathinfo($adj_archivo, PATHINFO_EXTENSION)) : '';
                                $adj_img     = in_array($adj_ext, ['jpg','jpeg','png','webp','gif'], true);
                                $val_str     = is_array($valor) ? '' : (string)$valor;
                                ?>
                                <?php if ($adj_url): ?>
                                <div class="mb-3">
                                    <?php if ($adj_img): ?>
                                        <img src="<?= e($adj_url) ?>" class="img-fluid rounded shadow-sm"
                                             style="max-height:320px;max-width:100%;">
                                    <?php else: ?>
                                        <a href="<?= e($adj_url) ?>" target="_blank"
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-file-pdf me-1"></i>Ver archivo adjunto
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <textarea name="<?= e($campo_name) ?>" class="form-control campo-con-contador"
                                    rows="4" maxlength="200"
                                    placeholder="Escriba su respuesta aquí…"
                                    <?= $es_obligatorio ? 'required' : '' ?>><?= e($val_str) ?></textarea>
                                <div class="form-text char-counter-wrap">
                                    <span class="chars-remaining">200</span> caracteres restantes
                                </div>

                            <?php elseif ($tipo === 'archivo'): ?>
                                <input type="file" name="<?= e($campo_name) ?>" class="form-control"
                                    accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,
                                            application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,
                                            application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,
                                            video/mp4,video/mpeg,video/quicktime"
                                    <?= $es_obligatorio && empty($valor) ? 'required' : '' ?>>
                                <div class="form-text" style="font-size:.78rem;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Permitido: imágenes (JPG, PNG, WEBP), PDF, Excel, Word, videos (MP4, MOV)
                                    &nbsp;·&nbsp; Máx. 10 MB
                                </div>
                                <?php if (!empty($valor)): ?>
                                <div class="form-text mt-1">
                                    Archivo actual:
                                    <a href="<?= e(uploads_resolve_url((string) $valor, 'formularios')) ?>" target="_blank"><?= e(basename((string) $valor)) ?></a>
                                    — Subí uno nuevo para reemplazarlo.
                                </div>
                                <?php endif; ?>

                            <?php elseif ($tipo === 'select' && $opciones): ?>
                                <select name="<?= e($campo_name) ?>" class="form-select" <?= $es_obligatorio ? 'required' : '' ?>>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($opciones as $opt): ?>
                                    <option value="<?= e($opt) ?>" <?= (string)$valor === (string)$opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($tipo === 'radio' && $opciones): ?>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach ($opciones as $opt): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            name="<?= e($campo_name) ?>"
                                            id="<?= e($campo_name . '_' . md5($opt)) ?>"
                                            value="<?= e($opt) ?>"
                                            <?= (string)$valor === (string)$opt ? 'checked' : '' ?>
                                            <?= $es_obligatorio ? 'required' : '' ?>>
                                        <label class="form-check-label" for="<?= e($campo_name . '_' . md5($opt)) ?>"><?= e($opt) ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($tipo === 'checkbox' && $opciones): ?>
                                <?php $vals = is_array($valor) ? $valor : []; ?>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach ($opciones as $opt): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="<?= e($campo_name) ?>[]"
                                            id="<?= e($campo_name . '_' . md5($opt)) ?>"
                                            value="<?= e($opt) ?>"
                                            <?= in_array($opt, $vals, true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="<?= e($campo_name . '_' . md5($opt)) ?>"><?= e($opt) ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($tipo === 'checkbox' && !$opciones): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        name="<?= e($campo_name) ?>" id="<?= e($campo_name) ?>"
                                        value="1" <?= !empty($valor) ? 'checked' : '' ?>
                                        <?= $es_obligatorio ? 'required' : '' ?>>
                                    <label class="form-check-label" for="<?= e($campo_name) ?>">Sí</label>
                                </div>

                            <?php elseif ($tipo === 'tabla' && !empty($tabla_cols) && !empty($tabla_rows)): ?>
                                <div class="table-responsive mt-1">
                                    <table class="table table-bordered table-sm align-middle" style="min-width:400px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:140px;"></th>
                                                <?php foreach ($tabla_cols as $col): ?>
                                                <th class="text-center fw-semibold"><?= e($col) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tabla_rows as $ri => $row): ?>
                                            <tr>
                                                <td class="fw-semibold text-nowrap bg-light"><?= e($row) ?></td>
                                                <?php foreach ($tabla_cols as $ci => $col): ?>
                                                <td>
                                                    <input type="text"
                                                        name="<?= e($campo_name) ?>[<?= $ri ?>][<?= $ci ?>]"
                                                        class="form-control form-control-sm"
                                                        value="<?= e($tabla_vals[$ri][$ci] ?? '') ?>">
                                                </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php elseif ($tipo === 'direccion'): ?>
                                <?php
                                $dir_lat = ''; $dir_lng = '';
                                $dir_val = is_array($valor) ? '' : (string)$valor;
                                if (str_contains($dir_val, ',')) {
                                    [$dir_lat, $dir_lng] = explode(',', $dir_val, 2);
                                }
                                ?>
                                <div class="row g-2 mb-2">
                                    <div class="col-sm-4">
                                        <label class="form-label small text-muted mb-1">Latitud</label>
                                        <input type="number" id="dir_lat_<?= $p['id'] ?>" class="form-control form-control-sm"
                                            step="any" placeholder="-28.5337" value="<?= e(trim($dir_lat)) ?>">
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label small text-muted mb-1">Longitud</label>
                                        <input type="number" id="dir_lng_<?= $p['id'] ?>" class="form-control form-control-sm"
                                            step="any" placeholder="-65.8010" value="<?= e(trim($dir_lng)) ?>">
                                    </div>
                                    <div class="col-sm-4 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary w-100"
                                            onclick="dirActualizarMapa(<?= $p['id'] ?>)">
                                            <i class="bi bi-search me-1"></i>Ir a coordenadas
                                        </button>
                                    </div>
                                </div>
                                <div id="mapDir<?= $p['id'] ?>" style="height:240px;border-radius:8px;border:1px solid #dee2e6;"></div>
                                <input type="hidden" name="<?= e($campo_name) ?>" id="<?= e($campo_name) ?>"
                                    value="<?= e($dir_val) ?>" <?= $es_obligatorio ? 'required' : '' ?>>
                                <div class="form-text" style="font-size:.78rem;"><i class="bi bi-cursor me-1"></i>Clic en el mapa o ingresá las coordenadas manualmente</div>

                            <?php elseif ($tipo === 'ubicacion' || stripos($p['etiqueta'], 'ubicacion') !== false || stripos($p['etiqueta'], 'ubicación') !== false): ?>
                                <div id="mapUbicacion<?= $p['id'] ?>" style="height:250px;border-radius:8px;"></div>
                                <input type="hidden" name="<?= e($campo_name) ?>" id="<?= e($campo_name) ?>"
                                    value="<?= e(is_array($valor) ? '' : (string)$valor) ?>">
                                <div class="form-text"><i class="bi bi-cursor me-1"></i>Haga clic en el mapa para indicar la ubicación.</div>

                            <?php else: ?>
                                <input type="text" name="<?= e($campo_name) ?>" class="form-control"
                                    value="<?= e(is_array($valor) ? '' : (string)$valor) ?>"
                                    <?= $es_obligatorio ? 'required' : '' ?>>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php
            $terminos_form = get_config('terminos_legales_formularios', '');
            if ($terminos_form !== ''):
            ?>
            <div class="alert alert-secondary small mt-3 mb-3">
                <i class="bi bi-file-earmark-text me-1"></i>
                <strong>Términos y condiciones:</strong><br>
                <?= nl2br(e($terminos_form)) ?>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-3">
                <button type="submit" name="accion" value="guardar" class="btn btn-outline-secondary">
                    <i class="bi bi-save me-2"></i>Guardar borrador
                </button>
                <button type="submit" name="accion" value="enviar" class="btn btn-primary btn-lg">
                    <i class="bi bi-send me-2"></i>Enviar formulario
                </button>
            </div>
        </form>
        <?php endif; ?>

<?php
ob_start();
$puJs = htmlspecialchars(PUBLIC_URL, ENT_QUOTES, 'UTF-8');
?>
    <script src="<?= PUBLIC_URL ?>/vendor/leaflet/leaflet.js"></script>
    <script src="<?= $puJs ?>/js/parque-leaflet.js"></script>
<?php
// Preguntas que llevan mapa: tipo "direccion" o etiqueta con "ubicacion" (mismo criterio que el HTML de cada campo)
$fd_mapas = [];
foreach (($preguntas ?? []) as $p) {
    $tipo_p = strtolower(trim($p['tipo'] ?? ''));
    $es_ubicacion = (stripos($p['etiqueta'], 'ubicacion') !== false || stripos($p['etiqueta'], 'ubicación') !== false);
    $es_direccion = ($tipo_p === 'direccion');
    if ($es_direccion || $es_ubicacion) {
        $fd_mapas[] = ['pid' => (int) $p['id'], 'tipo' => $es_direccion ? 'direccion' : 'ubicacion'];
    }
}
?>
    <script>window.FD_CFG = <?= json_encode([
        'defLat' => (float) MAP_DEFAULT_LAT,
        'defLng' => (float) MAP_DEFAULT_LNG,
        'mapas'  => $fd_mapas,
    ], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
    <script src="<?= asset_url('js/empresa-formulario-dinamico.js') ?>"></script>
<?php
$extra_scripts = ob_get_clean();
require_once BASEPATH . '/includes/empresa_layout_footer.php';

