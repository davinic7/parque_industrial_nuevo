<?php
/**
 * Centro de Comunicaciones - Panel Empresa (Fase 2)
 *
 * Bandeja unica de mensajes y notificaciones de la empresa.
 */
require_once __DIR__ . '/../../config/config.php';
require_once BASEPATH . '/includes/comunicaciones.php';

if (!$auth->requireRole(['empresa'], PUBLIC_URL . '/login.php')) exit;

if (!coms_schema_disponible()) {
    http_response_code(503);
    exit('El Centro de Comunicaciones no esta disponible: falta aplicar el esquema de base de datos.');
}

$page_title = 'Comunicaciones';
$empresa_nav = 'comunicaciones';

// Variables que consume el partial
$coms_actor                = 'empresa';
$coms_api_base             = '../api/comunicaciones';
$coms_puede_broadcast      = false;
$coms_puede_elegir_empresa = false;
$coms_empresas_destino     = [];

require_once BASEPATH . '/includes/empresa_layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-chat-square-dots me-2"></i>Centro de Comunicaciones</h1>
    <small class="text-muted">Mensajes, notificaciones y comunicados en un solo lugar.</small>
</div>

<?php require BASEPATH . '/includes/partials/comunicaciones_panel.php'; ?>

<?php require_once BASEPATH . '/includes/empresa_layout_footer.php'; ?>
