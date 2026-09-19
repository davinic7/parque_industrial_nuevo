<?php
/**
 * Gestión unificada de un formulario dinámico.
 * Tabs: Respuestas · Envíos y seguimiento · Enviar
 * Reemplaza el acceso fragmentado a formulario-respuestas.php,
 * formulario-seguimiento.php y formulario-enviar.php.
 */
require_once __DIR__ . '/../../config/config.php';
require_once BASEPATH . '/includes/comunicaciones.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) {
    exit;
}

$db = getDB();
$form_id = (int)($_GET['id'] ?? 0);
$tab_activo = $_GET['tab'] ?? 'respuestas';
if (!in_array($tab_activo, ['respuestas', 'envios', 'enviar'], true)) {
    $tab_activo = 'respuestas';
}

if ($form_id <= 0) {
    set_flash('error', 'Formulario no especificado.');
    redirect('formularios-dinamicos.php');
}

$stmt = $db->prepare('SELECT * FROM formularios_dinamicos WHERE id = ?');
$stmt->execute([$form_id]);
$formulario = $stmt->fetch();
if (!$formulario) {
    set_flash('error', 'Formulario no encontrado.');
    redirect('formularios-dinamicos.php');
}

// ── POST: seguimiento (recordatorio / extender) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $accion  = $_POST['accion'] ?? '';
    $fd_id   = (int)($_POST['destinatario_id'] ?? 0);
    $env_id  = (int)($_POST['envio_id'] ?? 0);

    if (in_array($accion, ['recordatorio', 'extender'], true) && $fd_id > 0 && $env_id > 0) {
        $stmt = $db->prepare("
            SELECT fd.*, fe.formulario_id, f.titulo, e.usuario_id, e.nombre AS empresa_nombre
            FROM formulario_destinatarios fd
            INNER JOIN formulario_envios fe ON fe.id = fd.envio_id
            INNER JOIN formularios_dinamicos f ON f.id = fe.formulario_id
            INNER JOIN empresas e ON e.id = fd.empresa_id
            WHERE fd.id = ? AND fd.envio_id = ?
        ");
        $stmt->execute([$fd_id, $env_id]);
        $row = $stmt->fetch();
        if ($row) {
            $url_form = rtrim(EMPRESA_URL, '/') . '/formulario_dinamico.php?id=' . (int)$row['formulario_id'];
            if ($accion === 'recordatorio') {
                crear_notificacion(
                    (int)$row['usuario_id'],
                    'formulario_recordatorio',
                    'Recordatorio: ' . $row['titulo'],
                    'Le recordamos completar el formulario pendiente.',
                    $url_form
                );
                set_flash('success', 'Recordatorio enviado a ' . $row['empresa_nombre']);
            }
            if ($accion === 'extender') {
                $db->prepare("
                    UPDATE formulario_destinatarios
                    SET plazo_hasta = DATE_ADD(COALESCE(plazo_hasta, CURDATE()), INTERVAL 7 DAY)
                    WHERE id = ?
                ")->execute([$fd_id]);
                set_flash('success', 'Plazo extendido 7 días para ' . $row['empresa_nombre']);
            }
        }
        redirect('formulario-gestion.php?id=' . $form_id . '&tab=envios&envio_id=' . $env_id);
    }

    // POST: enviar formulario a empresas
    if ($accion === '' || in_array($_POST['paso'] ?? '', ['vista_previa', 'confirmar'], true)) {
        $tab_activo = 'enviar';
    }
}

// ── Datos: respuestas ────────────────────────────────────────────────────────
$stmt = $db->prepare('SELECT * FROM formulario_preguntas WHERE formulario_id = ? ORDER BY orden, id');
$stmt->execute([$form_id]);
$preguntas = $stmt->fetchAll(PDO::FETCH_UNIQUE);

$stmt = $db->prepare("
    SELECT r.*, e.nombre AS empresa_nombre, e.cuit
    FROM formulario_respuestas r
    INNER JOIN empresas e ON r.empresa_id = e.id
    WHERE r.formulario_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$form_id]);
$respuestas = $stmt->fetchAll();

// ── Datos: historial de envíos ───────────────────────────────────────────────
try {
    $stmt = $db->prepare("
        SELECT fe.*, f.titulo
        FROM formulario_envios fe
        INNER JOIN formularios_dinamicos f ON f.id = fe.formulario_id
        WHERE fe.formulario_id = ?
        ORDER BY fe.created_at DESC
    ");
    $stmt->execute([$form_id]);
    $lista_envios = $stmt->fetchAll();
} catch (Throwable $e) {
    $lista_envios = [];
}

// Detalle de un envío específico
$envio_id = (int)($_GET['envio_id'] ?? 0);
$envio = null;
$filas_envio = [];
if ($envio_id > 0) {
    try {
        $stmt = $db->prepare("
            SELECT fe.*, f.titulo AS formulario_titulo
            FROM formulario_envios fe
            INNER JOIN formularios_dinamicos f ON f.id = fe.formulario_id
            WHERE fe.id = ?
        ");
        $stmt->execute([$envio_id]);
        $envio = $stmt->fetch();
        if ($envio) {
            $stmt = $db->prepare("
                SELECT
                    fd.id AS fd_id, fd.respondido, fd.fecha_respuesta, fd.fecha_notificacion, fd.plazo_hasta,
                    e.id AS empresa_id, e.nombre, e.rubro,
                    fr.estado AS resp_estado, fr.enviado_at,
                    COALESCE(fd.plazo_hasta, fe.fecha_limite) AS limite
                FROM formulario_destinatarios fd
                INNER JOIN formulario_envios fe ON fe.id = fd.envio_id
                INNER JOIN empresas e ON e.id = fd.empresa_id
                LEFT JOIN formulario_respuestas fr
                    ON fr.formulario_id = fe.formulario_id AND fr.empresa_id = fd.empresa_id AND fr.estado = 'enviado'
                WHERE fd.envio_id = ?
                ORDER BY e.nombre
            ");
            $stmt->execute([$envio_id]);
            $filas_envio = $stmt->fetchAll();
        }
    } catch (Throwable $e) {
        $envio = null;
    }
}

// ── Datos: formulario enviar ─────────────────────────────────────────────────
$lista_empresas_todas = $db->query("
    SELECT e.id, e.nombre, e.rubro, e.estado
    FROM empresas e
    INNER JOIN usuarios u ON u.id = e.usuario_id AND u.activo = 1
    ORDER BY e.nombre
")->fetchAll();

$rubros_opts = $db->query("SELECT DISTINCT rubro FROM empresas WHERE rubro IS NOT NULL AND rubro != '' ORDER BY rubro")->fetchAll(PDO::FETCH_COLUMN);
$ubic_opts   = $db->query("SELECT DISTINCT ubicacion FROM empresas WHERE ubicacion IS NOT NULL AND ubicacion != '' ORDER BY ubicacion")->fetchAll(PDO::FETCH_COLUMN);

$preview_envio = null;
$error_envio   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $paso = $_POST['paso'] ?? '';
    if (in_array($paso, ['vista_previa', 'confirmar'], true)) {
        $tab_activo = 'enviar';

        $tipo_filtro = $_POST['tipo_filtro'] ?? 'todos';
        if (!in_array($tipo_filtro, ['todos', 'rubro', 'ubicacion', 'estado', 'empresas_especificas'], true)) {
            $tipo_filtro = 'todos';
        }
        $filtros = [
            'rubros'      => array_values(array_filter((array)($_POST['rubros'] ?? []))),
            'ubicaciones' => array_values(array_filter((array)($_POST['ubicaciones'] ?? []))),
            'estados'     => array_values(array_filter((array)($_POST['estados'] ?? []))),
            'empresa_ids' => array_map('intval', (array)($_POST['empresas_ids'] ?? [])),
        ];
        $fecha_limite     = trim($_POST['fecha_limite'] ?? '');
        $fecha_limite_sql = $fecha_limite !== '' ? $fecha_limite : null;

        try {
            $empresas_sel = ministerio_empresas_envio($db, $tipo_filtro, $filtros);
        } catch (Exception $e) {
            error_log('formulario-gestion enviar: ' . $e->getMessage());
            $empresas_sel = [];
            $error_envio  = 'Error al aplicar filtros.';
        }

        if ($paso === 'vista_previa') {
            $preview_envio = $empresas_sel;
        }

        if ($paso === 'confirmar' && $error_envio === '') {
            if ($empresas_sel === []) {
                $error_envio = 'No hay empresas que coincidan con el criterio elegido.';
            } elseif ($formulario['estado'] !== 'publicado') {
                $error_envio = 'El formulario debe estar en estado Publicado para enviarlo.';
            } else {
                try {
                    $db->beginTransaction();
                    $uid          = $_SESSION['user_id'] ?? null;
                    $filtros_json = safe_json_encode($filtros);

                    $stmt = $db->prepare("
                        INSERT INTO formulario_envios (formulario_id, tipo_filtro, filtros_json, total_destinatarios, fecha_limite, enviado_por)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$form_id, $tipo_filtro, $filtros_json, count($empresas_sel), $fecha_limite_sql, $uid]);
                    $nuevo_envio_id = (int)$db->lastInsertId();

                    $insD     = $db->prepare("
                        INSERT INTO formulario_destinatarios (envio_id, empresa_id, notificado, fecha_notificacion, plazo_hasta)
                        VALUES (?, ?, 1, NOW(), ?)
                    ");
                    $url_form = rtrim(EMPRESA_URL, '/') . '/formulario_dinamico.php?id=' . $form_id;
                    $coms_activo = FEATURE_CENTRO_COMS && coms_schema_disponible();
                    $coms_remitente_id = (int)($_SESSION['user_id'] ?? 0);

                    foreach ($empresas_sel as $emp) {
                        $insD->execute([$nuevo_envio_id, (int)$emp['id'], $fecha_limite_sql]);
                        crear_notificacion(
                            (int)$emp['usuario_id'],
                            'formulario_nuevo',
                            'Nuevo formulario: ' . $formulario['titulo'],
                            'Debe completar el formulario asignado por el ministerio.',
                            $url_form
                        );

                        if ($coms_activo && $coms_remitente_id > 0) {
                            try {
                                $conv_id = coms_crear_conversacion([
                                    'titulo'           => 'Nuevo formulario: ' . $formulario['titulo'],
                                    'empresa_id'       => (int)$emp['id'],
                                    'iniciada_por'     => 'ministerio',
                                    'categoria'        => 'formulario',
                                    'referencia_tipo'  => 'formulario_dinamico',
                                    'referencia_id'    => $form_id,
                                ]);
                                $contenido = 'El Ministerio le asignó un nuevo formulario para completar. Use el botón "Completar formulario" para acceder.';
                                if (!empty($formulario['descripcion'])) {
                                    $contenido .= "\n\n" . $formulario['descripcion'];
                                }
                                coms_enviar_mensaje([
                                    'conversacion_id' => $conv_id,
                                    'remitente_id'    => $coms_remitente_id,
                                    'remitente_tipo'  => 'ministerio',
                                    'contenido'       => $contenido,
                                ]);
                            } catch (Throwable $eComs) {
                                error_log('formulario-gestion coms: ' . $eComs->getMessage());
                            }
                        }

                        $mail_to = !empty($emp['email_contacto']) && is_valid_email($emp['email_contacto'])
                            ? $emp['email_contacto']
                            : ($emp['email_acceso'] ?? '');
                        if ($mail_to !== '' && can_send_mail()) {
                            enviar_email_formulario_nuevo($mail_to, $formulario['titulo'], $url_form);
                        }
                    }

                    $db->commit();
                    log_activity('formulario_envio_masivo', 'formulario_envios', $nuevo_envio_id);
                    set_flash('success', 'Envío registrado a ' . count($empresas_sel) . ' empresa(s).');
                    redirect('formulario-gestion.php?id=' . $form_id . '&tab=envios&envio_id=' . $nuevo_envio_id);
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('formulario-gestion confirmar: ' . $e->getMessage());
                    $error_envio = 'No se pudo completar el envío.';
                }
            }
        }
    }
}

function ministerio_empresas_envio(PDO $db, string $tipo_filtro, array $filtros): array
{
    $where  = ['e.usuario_id IS NOT NULL'];
    $params = [];

    switch ($tipo_filtro) {
        case 'todos':
            $where[] = "e.estado = 'activa'";
            break;
        case 'rubro':
            $rubros = $filtros['rubros'] ?? [];
            if (!is_array($rubros) || $rubros === []) return [];
            $ph      = implode(',', array_fill(0, count($rubros), '?'));
            $where[] = "e.rubro IN ($ph)";
            $params  = array_values($rubros);
            break;
        case 'ubicacion':
            $ubs = $filtros['ubicaciones'] ?? [];
            if (!is_array($ubs) || $ubs === []) return [];
            $ph      = implode(',', array_fill(0, count($ubs), '?'));
            $where[] = "e.ubicacion IN ($ph)";
            $params  = array_values($ubs);
            break;
        case 'estado':
            $estados = $filtros['estados'] ?? [];
            if (!is_array($estados) || $estados === []) return [];
            $ph      = implode(',', array_fill(0, count($estados), '?'));
            $where[] = "e.estado IN ($ph)";
            $params  = array_values($estados);
            break;
        case 'empresas_especificas':
            $ids = array_values(array_filter(array_map('intval', $filtros['empresa_ids'] ?? []), static fn($x) => $x > 0));
            if ($ids === []) return [];
            $ph      = implode(',', array_fill(0, count($ids), '?'));
            $where[] = "e.id IN ($ph)";
            $params  = $ids;
            break;
        default:
            return [];
    }

    $sql = 'SELECT e.id, e.nombre, e.rubro, e.ubicacion, e.estado, e.usuario_id, e.email_contacto,
                   u.email AS email_acceso
            FROM empresas e
            INNER JOIN usuarios u ON u.id = e.usuario_id AND u.activo = 1
            WHERE ' . implode(' AND ', $where) . ' ORDER BY e.nombre';
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

$total_resp  = count($respuestas);
$total_envios = count($lista_envios);

$page_title      = e($formulario['titulo']);
$ministerio_nav  = 'formularios_dinamicos';

$extra_head = '<style>
.nav-tabs .nav-link { font-weight: 500; }
.tab-badge { font-size: .7rem; vertical-align: middle; }
</style>';

require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h2 class="h4 mb-1 fw-semibold"><?= e($formulario['titulo']) ?></h2>
        <?php if (!empty($formulario['descripcion'])): ?>
            <p class="text-muted mb-1 small"><?= e($formulario['descripcion']) ?></p>
        <?php endif; ?>
        <?php
        $badge_estados = ['borrador' => 'bg-secondary', 'publicado' => 'bg-success', 'archivado' => 'bg-dark'];
        ?>
        <span class="badge <?= $badge_estados[$formulario['estado']] ?? 'bg-secondary' ?>"><?= ucfirst($formulario['estado']) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="formulario-preview.php?id=<?= $form_id ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i>Vista previa
        </a>
        <a href="formulario-imprimir.php?id=<?= $form_id ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimir / PDF
        </a>
        <a href="formulario-editar.php?id=<?= $form_id ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <a href="formularios-dinamicos.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<?php show_flash(); ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link<?= $tab_activo === 'respuestas' ? ' active' : '' ?>"
           href="formulario-gestion.php?id=<?= $form_id ?>&tab=respuestas">
            <i class="bi bi-clipboard-data me-1"></i>Respuestas
            <?php if ($total_resp > 0): ?>
                <span class="badge bg-success rounded-pill tab-badge"><?= $total_resp ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $tab_activo === 'envios' ? ' active' : '' ?>"
           href="formulario-gestion.php?id=<?= $form_id ?>&tab=envios">
            <i class="bi bi-graph-up-arrow me-1"></i>Envíos y seguimiento
            <?php if ($total_envios > 0): ?>
                <span class="badge bg-secondary rounded-pill tab-badge"><?= $total_envios ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $tab_activo === 'enviar' ? ' active' : '' ?>"
           href="formulario-gestion.php?id=<?= $form_id ?>&tab=enviar">
            <i class="bi bi-send me-1"></i>Enviar a empresas
        </a>
    </li>
</ul>

<?php /* ═══════════════════ TAB: RESPUESTAS ═══════════════════ */ ?>
<?php if ($tab_activo === 'respuestas'): ?>
<?php require BASEPATH . '/includes/partials/formulario_gestion_respuestas.php'; ?>
<?php /* ═══════════════════ TAB: ENVÍOS Y SEGUIMIENTO ═══════════════════ */ ?>
<?php elseif ($tab_activo === 'envios'): ?>
<?php require BASEPATH . '/includes/partials/formulario_gestion_envios.php'; ?>
<?php /* ═══════════════════ TAB: ENVIAR ═══════════════════ */ ?>
<?php elseif ($tab_activo === 'enviar'): ?>
<?php require BASEPATH . '/includes/partials/formulario_gestion_enviar.php'; ?>
<?php endif; ?>

<?php
$extra_scripts = '<script src="' . asset_url('js/ministerio-formulario-gestion.js') . '"></script>';
require_once BASEPATH . '/includes/ministerio_layout_footer.php';
?>
