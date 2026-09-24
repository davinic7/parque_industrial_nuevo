<?php
/**
 * Contactos de emergencia y servicios - Sitio público
 * Sólo muestra los contactos marcados como públicos.
 */
require_once __DIR__ . '/../config/config.php';
require_once BASEPATH . '/includes/contactos_emergencia.php';

$page_title = 'Contactos de emergencia';
$grupos = contactos_agrupados(getDB(), true);

include __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <h1 class="text-center mb-2">Contactos de emergencia y servicios</h1>
        <p class="text-center text-muted mb-4">Teléfonos útiles para el Parque Industrial. En el celular, tocá un número para llamar.</p>

        <div class="alert alert-danger d-flex align-items-center gap-2 mb-5">
            <i class="bi bi-exclamation-octagon-fill fs-4"></i>
            <div>Ante una emergencia con riesgo para las personas, llamá primero a <strong>Bomberos (100)</strong>,
                <strong>Policía (101)</strong> o <strong>SAME (107)</strong>.</div>
        </div>

        <?php contactos_render($grupos); ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
