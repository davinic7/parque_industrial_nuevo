<?php
/**
 * Centro de Comunicaciones - Panel Ministerio (Fase 2)
 *
 * Bandeja unica de mensajes y comunicados del Ministerio.
 */
require_once __DIR__ . '/../../config/config.php';
require_once BASEPATH . '/includes/comunicaciones.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

if (!coms_schema_disponible()) {
    http_response_code(503);
    exit('El Centro de Comunicaciones no esta disponible: falta aplicar el esquema de base de datos.');
}

$page_title = 'Comunicaciones';
$ministerio_nav = 'comunicaciones';
$db = getDB();

// Lista de empresas activas para el selector de destinatario en "Nueva conversacion".
$coms_empresas_destino = $db->query("
    SELECT id, nombre FROM empresas
    WHERE estado IN ('activa', 'pendiente')
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Variables que consume el partial
$coms_actor                = 'ministerio';
$coms_api_base             = '../api/comunicaciones';
$coms_puede_broadcast      = true;
$coms_puede_elegir_empresa = true;

require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-chat-square-dots me-2"></i>Centro de Comunicaciones</h1>
    <small class="text-muted">Mensajes con empresas + comunicados globales + notificaciones.</small>
</div>

<?php require BASEPATH . '/includes/partials/comunicaciones_panel.php'; ?>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
