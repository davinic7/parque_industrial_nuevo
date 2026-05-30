<?php
/**
 * Nueva Empresa - Ministerio
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Nueva Empresa';
$mensaje = '';
$error = '';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        try {
            $nombre       = trim($_POST['nombre'] ?? '');
            $rubro        = trim($_POST['rubro'] ?? '');
            $email_usuario = trim($_POST['email_usuario'] ?? '');
            $estado       = $_POST['estado'] ?? 'pendiente';

            if (empty($nombre)) {
                $error = 'El nombre comercial es obligatorio.';
            } elseif (empty($rubro)) {
                $error = 'Debe seleccionar un rubro.';
            } elseif (empty($email_usuario) || !is_valid_email($email_usuario)) {
                $error = 'Debe ingresar un email de acceso válido.';
            } else {
                $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email_usuario]);
                if ($stmt->fetch()) {
                    $error = 'El email de acceso ya está registrado en el sistema.';
                } else {
                    $db->beginTransaction();

                    $modo_registro   = $_POST['modo_registro'] ?? 'activacion_email';
                    $usuario_id      = null;
                    $temp_password   = null;
                    $token_activacion = null;

                    if ($modo_registro === 'activacion_email') {
                        $token_activacion = bin2hex(random_bytes(32));
                        $token_exp = date('Y-m-d H:i:s', strtotime('+48 hours'));
                        $result = $auth->registerEmpresaPending($email_usuario, $token_activacion, $token_exp);
                        if (!$result['success']) throw new Exception($result['error']);
                        $usuario_id = $result['user_id'];
                    } else {
                        $temp_password = bin2hex(random_bytes(4));
                        $result = $auth->register($email_usuario, $temp_password, 'empresa');
                        if (!$result['success']) throw new Exception($result['error']);
                        $usuario_id = $result['user_id'];
                    }

                    $stmt = $db->prepare("
                        INSERT INTO empresas (usuario_id, nombre, rubro, estado)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$usuario_id, $nombre, $rubro, $estado]);
                    $empresa_id = $db->lastInsertId();

                    if (isset($_POST['solicitar_formulario'])) {
                        crear_notificacion(
                            $usuario_id,
                            'formulario_pendiente',
                            'Complete su formulario trimestral',
                            'El Ministerio solicita que complete su declaración jurada trimestral.',
                            EMPRESA_URL . '/formularios.php'
                        );
                    }

                    $db->commit();
                    log_activity('empresa_registrada', 'empresas', $empresa_id);

                    if ($modo_registro === 'activacion_email' && $token_activacion) {
                        $url_act = rtrim(PUBLIC_URL, '/') . '/activar-cuenta.php?token=' . urlencode($token_activacion);
                        $mensaje = 'Empresa registrada. El usuario debe activar la cuenta con el enlace enviado por email.';
                        if (!empty($_POST['enviar_credenciales_email'])) {
                            if (can_send_mail() && enviar_email_activacion_empresa($email_usuario, $nombre, $url_act)) {
                                $mensaje .= ' Email de activación enviado.';
                            } else {
                                $mensaje .= ' No se pudo enviar el email. Enlace de activación: ' . $url_act;
                            }
                        } else {
                            $mensaje .= ' Enlace de activación (guarde o envíe manualmente): ' . $url_act;
                        }
                    } else {
                        $mensaje = "Empresa registrada correctamente. Credenciales de acceso — Email: $email_usuario / Contraseña temporal: $temp_password";
                        if (!empty($_POST['enviar_credenciales_email'])) {
                            $url_login = PUBLIC_URL . '/login.php';
                            if (enviar_email_credenciales_empresa($email_usuario, $nombre, $temp_password, $url_login)) {
                                $mensaje .= ' Se envió un email con las credenciales al usuario.';
                            } else {
                                $mensaje .= ' No se pudo enviar el email; las credenciales se muestran aquí.';
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("Error al registrar empresa: " . $e->getMessage());
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate') !== false || stripos($msg, 'UNIQUE') !== false) {
                $error = 'El email de acceso ya está registrado. Use otro email.';
            } elseif (stripos($msg, 'Column') !== false || stripos($msg, 'Unknown column') !== false) {
                $error = 'Error de base de datos. Revise las migraciones.';
            } else {
                $error = 'Error al registrar la empresa: ' . (strlen($msg) > 120 ? substr($msg, 0, 120) . '…' : $msg);
            }
        }
    }
}

$rubros = $db->query("SELECT nombre FROM rubros WHERE activo = 1 ORDER BY orden, nombre")->fetchAll(PDO::FETCH_COLUMN);

// Prefill desde solicitud (GET params enviados por solicitudes-proyecto.php)
$pre_nombre   = trim($_GET['prefill_nombre']   ?? '');
$pre_email    = trim($_GET['prefill_email']    ?? '');
$pre_contacto = trim($_GET['prefill_contacto'] ?? '');
$pre_telefono = trim($_GET['prefill_telefono'] ?? '');

// Solicitudes en carpeta (para el modal)
try {
    $sol_carpeta = $db->query("SELECT * FROM solicitudes_proyecto WHERE estado = 'en_carpeta' ORDER BY created_at DESC")->fetchAll();
} catch (Exception $e) {
    $sol_carpeta = [];
}

$ministerio_nav = 'nueva_empresa';
require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0" style="font-size:.8rem;">
                        <li class="breadcrumb-item"><a href="empresas.php" class="text-decoration-none"><i class="fa-solid fa-buildings me-1"></i>Empresas</a></li>
                        <li class="breadcrumb-item active">Nueva empresa</li>
                    </ol>
                </nav>
                <h2 class="h4 mb-0 fw-semibold">Registrar nueva empresa</h2>
            </div>
        </div>

        <?php if ($pre_nombre || $pre_email): ?>
        <div class="alert border-0 d-flex align-items-start gap-3 mb-4" style="background:#e8f4fd;">
            <i class="bi bi-arrow-right-circle-fill fs-5 mt-1 text-primary flex-shrink-0"></i>
            <div>
                <strong>Datos cargados desde solicitud<?= $pre_nombre ? ': ' . e($pre_nombre) : '' ?></strong><br>
                <span class="small text-muted">Completá el rubro y elegí el modo de acceso. El resto del perfil lo carga la empresa desde su panel.</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= e($mensaje) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="row g-4 align-items-start">

                <!-- ── Formulario principal ── -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">

                            <!-- Sección 1: Empresa -->
                            <p class="text-uppercase fw-semibold mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#6c757d;">
                                <i class="fa-solid fa-buildings me-2 opacity-50"></i>Datos de la empresa
                            </p>
                            <div class="row g-3 mb-2">
                                <div class="col-sm-6">
                                    <label class="form-label fw-medium">Nombre comercial <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" required
                                           placeholder="Ej: Metalúrgica del Norte S.R.L."
                                           value="<?= e($_POST['nombre'] ?? $pre_nombre) ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-medium">Rubro <span class="text-danger">*</span></label>
                                    <select name="rubro" class="form-select" required>
                                        <option value="">Seleccione un rubro…</option>
                                        <?php foreach ($rubros as $r): ?>
                                        <option value="<?= e($r) ?>" <?= ($_POST['rubro'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <p class="text-muted small mb-0">El resto del perfil (dirección, CUIT, descripción, logo) lo completa la empresa desde su panel.</p>

                            <hr class="my-4">

                            <!-- Sección 2: Acceso -->
                            <p class="text-uppercase fw-semibold mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#6c757d;">
                                <i class="fa-solid fa-key me-2 opacity-50"></i>Acceso al sistema
                            </p>

                            <!-- Modo de alta -->
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <label class="d-flex align-items-start gap-3 p-3 rounded-3 border cursor-pointer modo-opcion <?= ($_POST['modo_registro'] ?? 'activacion_email') !== 'password_temporal' ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle' ?>"
                                           for="modoActivacion" style="cursor:pointer;">
                                        <input class="form-check-input mt-0 flex-shrink-0" type="radio" name="modo_registro"
                                               id="modoActivacion" value="activacion_email"
                                               <?= ($_POST['modo_registro'] ?? 'activacion_email') !== 'password_temporal' ? 'checked' : '' ?>>
                                        <div>
                                            <span class="fw-semibold d-block">Activación por email</span>
                                            <span class="text-muted small">La empresa define su contraseña con un enlace seguro.</span>
                                            <span class="badge bg-success ms-0 mt-1 d-inline-block">Recomendado</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="d-flex align-items-start gap-3 p-3 rounded-3 border cursor-pointer modo-opcion <?= ($_POST['modo_registro'] ?? '') === 'password_temporal' ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle' ?>"
                                           for="modoTemp" style="cursor:pointer;">
                                        <input class="form-check-input mt-0 flex-shrink-0" type="radio" name="modo_registro"
                                               id="modoTemp" value="password_temporal"
                                               <?= ($_POST['modo_registro'] ?? '') === 'password_temporal' ? 'checked' : '' ?>>
                                        <div>
                                            <span class="fw-semibold d-block">Contraseña temporal</span>
                                            <span class="text-muted small">Se genera automáticamente y se muestra o envía por email.</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-medium">Email de acceso <span class="text-danger">*</span></label>
                                    <input type="email" name="email_usuario" class="form-control" required
                                           placeholder="contacto@empresa.com"
                                           value="<?= e($_POST['email_usuario'] ?? $pre_email) ?>">
                                    <?php if ($pre_email): ?>
                                    <div class="form-text text-success"><i class="fa-solid fa-circle-check me-1"></i>Pre-cargado desde la solicitud.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-medium">Estado inicial</label>
                                    <select name="estado" class="form-select">
                                        <option value="pendiente" <?= ($_POST['estado'] ?? 'pendiente') === 'pendiente' ? 'selected' : '' ?>>Pendiente de verificación</option>
                                        <option value="activa" <?= ($_POST['estado'] ?? '') === 'activa' ? 'selected' : '' ?>>Activa directamente</option>
                                    </select>
                                    <div class="form-text">«Pendiente» es lo habitual para nuevas altas.</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ── Sidebar ── -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-4">
                            <p class="text-uppercase fw-semibold mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#6c757d;">
                                <i class="fa-solid fa-sliders me-2 opacity-50"></i>Opciones
                            </p>
                            <div class="d-flex flex-column gap-3">
                                <label class="d-flex align-items-start gap-3" style="cursor:pointer;">
                                    <input type="checkbox" name="solicitar_formulario" class="form-check-input mt-0 flex-shrink-0" id="solicitarForm" checked>
                                    <div>
                                        <span class="fw-medium d-block" style="font-size:.9rem;">Solicitar declaración trimestral</span>
                                        <span class="text-muted small">Se notificará a la empresa que debe completar su formulario.</span>
                                    </div>
                                </label>
                                <label class="d-flex align-items-start gap-3" style="cursor:pointer;">
                                    <input type="checkbox" name="enviar_credenciales_email" class="form-check-input mt-0 flex-shrink-0" id="enviarEmail" checked>
                                    <div>
                                        <span class="fw-medium d-block" style="font-size:.9rem;">Enviar email al usuario</span>
                                        <span class="text-muted small">Envía el enlace de activación o las credenciales temporales.</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                            <i class="fa-solid fa-plus me-2"></i>Registrar empresa
                        </button>
                        <a href="empresas.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </div>

            </div>
        </form>

<script>
// Highlight del modo seleccionado
document.querySelectorAll('input[name="modo_registro"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.modo-opcion').forEach(function(el) {
            el.classList.remove('border-primary','bg-primary','bg-opacity-10');
            el.classList.add('border-secondary-subtle');
        });
        var lbl = this.closest('.modo-opcion');
        if (lbl) {
            lbl.classList.remove('border-secondary-subtle');
            lbl.classList.add('border-primary','bg-primary','bg-opacity-10');
        }
    });
});
</script>

<!-- ── Modal: Solicitudes en carpeta ─────────────────────────────────────── -->
<div class="modal fade" id="modalCarpeta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-folder2-open me-2"></i>Solicitudes en carpeta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <?php if (empty($sol_carpeta)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-folder2 fs-1 opacity-25 d-block mb-3"></i>
                    <p class="mb-0">No hay solicitudes en carpeta en este momento.</p>
                    <p class="small mt-2">Las solicitudes pasan a "En carpeta" cuando el Ministerio las revisa desde el listado de solicitudes.</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($sol_carpeta as $s): ?>
                    <?php
                        $params = http_build_query([
                            'prefill_nombre'   => $s['nombre_empresa'],
                            'prefill_email'    => $s['email'],
                            'prefill_contacto' => $s['contacto'],
                            'prefill_telefono' => $s['telefono'] ?? '',
                        ]);
                    ?>
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= e($s['nombre_empresa']) ?></div>
                                <div class="small text-muted">
                                    <i class="bi bi-person me-1"></i><?= e($s['contacto']) ?>
                                    &nbsp;·&nbsp;<i class="bi bi-envelope me-1"></i><?= e($s['email']) ?>
                                    <?= $s['telefono'] ? '&nbsp;·&nbsp;<i class="bi bi-telephone me-1"></i>' . e($s['telefono']) : '' ?>
                                </div>
                                <?php if ($s['solicita_cita']): ?>
                                <span class="badge bg-warning text-dark small mt-1"><i class="bi bi-calendar-check me-1"></i>Solicita cita</span>
                                <?php endif; ?>
                                <?php if ($s['observaciones']): ?>
                                <p class="small text-muted fst-italic mb-0 mt-1"><?= e(truncate($s['observaciones'], 80)) ?></p>
                                <?php endif; ?>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= format_datetime($s['created_at']) ?></small>
                            </div>
                            <div class="d-flex flex-column gap-1" style="min-width:130px;">
                                <a href="nueva-empresa.php?<?= $params ?>" class="btn btn-sm btn-success" data-bs-dismiss="modal">
                                    <i class="bi bi-person-plus me-1"></i>Dar usuario
                                </a>
                                <form method="POST" action="solicitudes-proyecto.php" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                                            onclick="return confirm('¿Cancelar esta carpeta?')">
                                        <i class="bi bi-x-circle me-1"></i>Cancelar carpeta
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a href="solicitudes-proyecto.php?filtro=en_carpeta" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list me-1"></i>Ver todas las solicitudes
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
