<?php
/**
 * Configurar qué métricas/gráficos se muestran a las empresas en "Mis datos"
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Métricas visibles para empresas';
$db = getDB();

$bloques = [
    'empleados_evolucion' => [
        'label' => 'Evolución de empleados totales',
        'desc'  => 'Línea de tiempo con dotación total por período declarado.',
        'icon'  => 'fa-solid fa-people-group',
    ],
    'empleados_genero' => [
        'label' => 'Distribución por género',
        'desc'  => 'Gráfico de torta: masculinos vs. femeninos (último período).',
        'icon'  => 'fa-solid fa-venus-mars',
    ],
    'consumo_energia' => [
        'label' => 'Consumo de energía (kWh)',
        'desc'  => 'Barras por período: consumo eléctrico declarado.',
        'icon'  => 'fa-solid fa-bolt',
    ],
    'consumo_agua' => [
        'label' => 'Consumo de agua (m³)',
        'desc'  => 'Barras por período: consumo de agua declarado.',
        'icon'  => 'fa-solid fa-droplet',
    ],
    'consumo_gas' => [
        'label' => 'Consumo de gas (m³)',
        'desc'  => 'Barras por período: consumo de gas natural declarado.',
        'icon'  => 'fa-solid fa-fire-flame-simple',
    ],
    'capacidad_uso' => [
        'label' => 'Uso de capacidad instalada (%)',
        'desc'  => 'Barras por período: porcentaje de capacidad utilizada.',
        'icon'  => 'fa-solid fa-gauge-high',
    ],
    'produccion' => [
        'label' => 'Producción mensual declarada',
        'desc'  => 'Tabla con producción y unidad de medida por período.',
        'icon'  => 'fa-solid fa-boxes-stacked',
    ],
    'comercio_exterior' => [
        'label' => 'Comercio exterior',
        'desc'  => 'Indicadores de exportación e importación (último período).',
        'icon'  => 'fa-solid fa-globe',
    ],
    'emisiones_co2' => [
        'label' => 'Huella de carbono — CO₂e (toneladas)',
        'desc'  => 'Línea de tiempo: emisiones declaradas por período.',
        'icon'  => 'fa-solid fa-cloud',
    ],
];

$default_visible = json_encode(array_keys($bloques));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $visibles = $_POST['visibles'] ?? [];
    $visibles = array_values(array_intersect($visibles, array_keys($bloques)));
    $valor = json_encode($visibles);
    try {
        $db->prepare("
            INSERT INTO configuracion_sitio (clave, valor, tipo, grupo)
            VALUES ('empresa_metricas_visibles', ?, 'json', 'empresa')
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ")->execute([$valor]);
        set_flash('success', 'Configuración guardada. Las empresas verán los bloques seleccionados en su página "Mis datos".');
        redirect('empresa-metricas.php');
    } catch (Throwable $e) {
        set_flash('error', 'Error al guardar la configuración.');
    }
}

$valor_actual = get_config('empresa_metricas_visibles', $default_visible);
$visibles_actual = json_decode($valor_actual, true);
if (!is_array($visibles_actual)) {
    $visibles_actual = array_keys($bloques);
}

$ministerio_nav = 'empresa-metricas';
$extra_head = '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">';
require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<div class="d-flex align-items-center gap-2 mb-1">
    <i class="fa-solid fa-chart-bar text-primary"></i>
    <h2 class="h4 mb-0 fw-semibold">Métricas visibles para empresas</h2>
</div>
<p class="text-muted small mb-4">
    Seleccioná qué bloques de datos aparecen en la página <strong>"Mis datos"</strong> del panel de cada empresa.
    Solo se muestran datos de sus <strong>declaraciones juradas enviadas o aprobadas</strong>.
</p>

<?php show_flash(); ?>

<form method="POST">
    <?= csrf_field() ?>
    <div class="row g-3 mb-4">
        <?php foreach ($bloques as $id => $bloque): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 <?= in_array($id, $visibles_actual) ? 'border-primary' : 'border-light' ?> transition" style="cursor:pointer;"
                 onclick="document.getElementById('v_<?= e($id) ?>').click()">
                <div class="card-body d-flex gap-3 align-items-start">
                    <div class="flex-shrink-0 pt-1 text-primary" style="font-size:1.2rem;">
                        <i class="<?= e($bloque['icon']) ?>"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <input type="checkbox" name="visibles[]" value="<?= e($id) ?>"
                                   class="form-check-input flex-shrink-0"
                                   id="v_<?= e($id) ?>"
                                   <?= in_array($id, $visibles_actual) ? 'checked' : '' ?>
                                   onclick="event.stopPropagation()">
                            <label class="form-check-label fw-semibold mb-0" for="v_<?= e($id) ?>" style="cursor:pointer;">
                                <?= e($bloque['label']) ?>
                            </label>
                        </div>
                        <p class="small text-muted mb-0"><?= e($bloque['desc']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-2"></i>Guardar configuración
        </button>
        <button type="button" class="btn btn-outline-secondary" id="btnMarcarTodos">Marcar todos</button>
        <button type="button" class="btn btn-outline-secondary" id="btnDesmarcarTodos">Desmarcar todos</button>
    </div>
</form>

<script>
document.getElementById('btnMarcarTodos').onclick = function() {
    document.querySelectorAll('input[name="visibles[]"]').forEach(function(el) { el.checked = true; updateCard(el); });
};
document.getElementById('btnDesmarcarTodos').onclick = function() {
    document.querySelectorAll('input[name="visibles[]"]').forEach(function(el) { el.checked = false; updateCard(el); });
};
function updateCard(checkbox) {
    var card = checkbox.closest('.card');
    if (!card) return;
    card.classList.toggle('border-primary', checkbox.checked);
    card.classList.toggle('border-light', !checkbox.checked);
}
document.querySelectorAll('input[name="visibles[]"]').forEach(function(el) {
    el.addEventListener('change', function() { updateCard(this); });
});
</script>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
