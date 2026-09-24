<?php
/**
 * Contactos de emergencia y servicios - Panel Empresa
 * Muestra todos los contactos activos (públicos y exclusivos para empresas).
 */
require_once __DIR__ . '/../../config/config.php';
require_once BASEPATH . '/includes/contactos_emergencia.php';

if (!$auth->requireRole(['empresa'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Contactos de emergencia';
$empresa_nav = 'contactos';
$grupos = contactos_agrupados(getDB(), false);

require_once BASEPATH . '/includes/empresa_layout_header.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1"><i class="bi bi-telephone-fill me-2"></i>Contactos de emergencia</h1>
    <p class="text-muted mb-0">Teléfonos útiles ante cortes de servicios, emergencias y consultas a la administración del parque.
        En el celular, tocá un número para llamar.</p>
</div>

<div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-octagon-fill fs-4"></i>
    <div>Ante una emergencia con riesgo para las personas, llamá primero a <strong>Bomberos (100)</strong>,
        <strong>Policía (101)</strong> o <strong>SAME (107)</strong>.</div>
</div>

<?php contactos_render($grupos, true); ?>

<?php require_once BASEPATH . '/includes/empresa_layout_footer.php'; ?>
