<?php
/**
 * Mis Datos — empresa ve sus métricas históricas declaradas
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['empresa'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Mis datos';
$db = getDB();
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$empresa_id) {
    set_flash('error', 'No se encontró la empresa asociada.');
    redirect('dashboard.php');
}

// ── Métricas configuradas por el ministerio ──────────────────────────────
$default_visible = '["empleados_evolucion","empleados_genero","consumo_energia","consumo_agua","consumo_gas","capacidad_uso","produccion","comercio_exterior","emisiones_co2"]';
$valor_config    = get_config('empresa_metricas_visibles', $default_visible);
$bloques_visibles = json_decode($valor_config, true);
if (!is_array($bloques_visibles)) {
    $bloques_visibles = json_decode($default_visible, true);
}

// ── Datos declarados (solo enviados/aprobados) ───────────────────────────
$periodos = [];
try {
    $stmt = $db->prepare('
        SELECT periodo, dotacion_total, empleados_masculinos, empleados_femeninos,
               consumo_energia, consumo_agua, consumo_gas, porcentaje_capacidad_uso,
               produccion_mensual, unidad_produccion,
               exporta, importa, productos_exporta, paises_exporta,
               productos_importa, paises_importa, emisiones_co2, estado
        FROM datos_empresa
        WHERE empresa_id = ? AND estado IN (\'enviado\', \'aprobado\')
        ORDER BY periodo ASC
        LIMIT 8
    ');
    $stmt->execute([$empresa_id]);
    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('mis-datos.php periodos: ' . $e->getMessage());
}

$ultimo = !empty($periodos) ? end($periodos) : null;
reset($periodos);

$tiene_datos = !empty($periodos);

// ── Helpers de datos para JS ─────────────────────────────────────────────
$labels_js      = json_encode(array_column($periodos, 'periodo'));
$dotacion_js    = json_encode(array_map(fn($r) => $r['dotacion_total'] !== null ? (int)$r['dotacion_total'] : null, $periodos));
$energia_js     = json_encode(array_map(fn($r) => $r['consumo_energia'] !== null ? (float)$r['consumo_energia'] : null, $periodos));
$agua_js        = json_encode(array_map(fn($r) => $r['consumo_agua'] !== null ? (float)$r['consumo_agua'] : null, $periodos));
$gas_js         = json_encode(array_map(fn($r) => $r['consumo_gas'] !== null ? (float)$r['consumo_gas'] : null, $periodos));
$capacidad_js   = json_encode(array_map(fn($r) => $r['porcentaje_capacidad_uso'] !== null ? (float)$r['porcentaje_capacidad_uso'] : null, $periodos));
$emisiones_js   = json_encode(array_map(fn($r) => $r['emisiones_co2'] !== null ? (float)$r['emisiones_co2'] : null, $periodos));

$masc_ult = $ultimo ? (int)($ultimo['empleados_masculinos'] ?? 0) : 0;
$fem_ult  = $ultimo ? (int)($ultimo['empleados_femeninos']  ?? 0) : 0;

function bloque_visible(array $bloques_visibles, string $key): bool {
    return in_array($key, $bloques_visibles, true);
}

$empresa_nav = 'mis-datos';
$extra_head  = '
<link href="' . PUBLIC_URL . '/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
<style>
.md-chart-card { background:#fff; border:1px solid rgba(0,0,0,.07); border-radius:16px; padding:20px; box-shadow:0 2px 12px rgba(0,0,0,.04); }
.md-chart-card h6 { font-size:.82rem; text-transform:uppercase; letter-spacing:.04em; color:#6c757d; margin-bottom:12px; font-weight:600; }
.md-empty { text-align:center; padding:56px 24px; }
.md-empty i { font-size:3rem; color:#dee2e6; display:block; margin-bottom:12px; }
.periodo-badge { font-size:.7rem; padding:3px 8px; border-radius:20px; }
.kpi-inline { display:inline-flex; align-items:center; gap:8px; background:#f8f9fa; border-radius:10px; padding:8px 14px; margin:4px; }
.kpi-inline .k { font-size:.7rem; text-transform:uppercase; color:#6c757d; }
.kpi-inline .v { font-size:1.05rem; font-weight:700; }
</style>';

require_once BASEPATH . '/includes/empresa_layout_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 mb-0">Mis datos declarados</h1>
    <a href="formularios.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-file-earmark-check me-1"></i>Ir a declarar
    </a>
</div>

<?php show_flash(); ?>

<?php if (!$tiene_datos): ?>
<div class="md-empty">
    <i class="bi bi-bar-chart-line"></i>
    <h5 class="fw-semibold">Aún no hay datos para mostrar</h5>
    <p class="text-muted">Una vez que envíes tu primera declaración jurada, aquí verás tus métricas con gráficos históricos.</p>
    <a href="formularios.php" class="btn btn-primary mt-2">Completar declaración →</a>
</div>

<?php else: ?>

<!-- Strip del último período -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-4 p-3 rounded-3 bg-light border">
    <i class="bi bi-calendar-check text-primary"></i>
    <span class="fw-semibold">Último período declarado:</span>
    <span class="badge bg-primary periodo-badge"><?= e($ultimo['periodo']) ?></span>
    <span class="badge <?= $ultimo['estado'] === 'aprobado' ? 'bg-success' : 'bg-warning text-dark' ?> periodo-badge">
        <?= ucfirst($ultimo['estado']) ?>
    </span>
    <span class="ms-auto small text-muted"><?= count($periodos) ?> período<?= count($periodos) !== 1 ? 's' : '' ?> con datos</span>
</div>

<div class="row g-4">

<?php if (bloque_visible($bloques_visibles, 'empleados_evolucion')): ?>
<!-- Evolución empleados -->
<div class="col-lg-<?= bloque_visible($bloques_visibles, 'empleados_genero') ? '8' : '12' ?>">
    <div class="md-chart-card h-100">
        <h6><i class="bi bi-people me-1"></i>Evolución de empleados</h6>
        <canvas id="chartEmpleados" height="90"></canvas>
    </div>
</div>
<?php endif; ?>

<?php if (bloque_visible($bloques_visibles, 'empleados_genero') && ($masc_ult + $fem_ult) > 0): ?>
<!-- Género último período -->
<div class="col-lg-<?= bloque_visible($bloques_visibles, 'empleados_evolucion') ? '4' : '6 mx-auto' ?>">
    <div class="md-chart-card h-100">
        <h6><i class="bi bi-gender-ambiguous me-1"></i>Género — <?= e($ultimo['periodo']) ?></h6>
        <canvas id="chartGenero" height="<?= bloque_visible($bloques_visibles, 'empleados_evolucion') ? '160' : '100' ?>"></canvas>
        <div class="text-center mt-2">
            <span class="kpi-inline">
                <span><span class="k">Masc.</span><br><span class="v text-primary"><?= $masc_ult ?></span></span>
            </span>
            <span class="kpi-inline">
                <span><span class="k">Fem.</span><br><span class="v" style="color:#e91e8c;"><?= $fem_ult ?></span></span>
            </span>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$consumos_activos = array_filter([
    bloque_visible($bloques_visibles, 'consumo_energia') ? 'Energía (kWh)' : null,
    bloque_visible($bloques_visibles, 'consumo_agua')    ? 'Agua (m³)'     : null,
    bloque_visible($bloques_visibles, 'consumo_gas')     ? 'Gas (m³)'      : null,
]);
if (!empty($consumos_activos)):
?>
<!-- Consumos agrupados -->
<div class="col-12">
    <div class="md-chart-card">
        <h6><i class="bi bi-lightning-charge me-1"></i>Consumos por período</h6>
        <canvas id="chartConsumos" height="70"></canvas>
    </div>
</div>
<?php endif; ?>

<?php if (bloque_visible($bloques_visibles, 'capacidad_uso')): ?>
<!-- Capacidad de uso -->
<div class="col-md-6">
    <div class="md-chart-card h-100">
        <h6><i class="bi bi-speedometer2 me-1"></i>Uso de capacidad instalada (%)</h6>
        <canvas id="chartCapacidad" height="110"></canvas>
    </div>
</div>
<?php endif; ?>

<?php if (bloque_visible($bloques_visibles, 'emisiones_co2')): ?>
<!-- Emisiones CO2 -->
<div class="col-md-<?= bloque_visible($bloques_visibles, 'capacidad_uso') ? '6' : '12' ?>">
    <div class="md-chart-card h-100">
        <h6><i class="bi bi-cloud me-1"></i>Emisiones CO₂e (toneladas)</h6>
        <canvas id="chartEmisiones" height="110"></canvas>
    </div>
</div>
<?php endif; ?>

<?php if (bloque_visible($bloques_visibles, 'produccion') && !empty($periodos)): ?>
<!-- Producción tabla -->
<div class="col-md-<?= bloque_visible($bloques_visibles, 'comercio_exterior') ? '6' : '12' ?>">
    <div class="md-chart-card h-100">
        <h6><i class="bi bi-boxes me-1"></i>Producción mensual declarada</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Período</th><th>Producción</th><th>Unidad</th></tr>
                </thead>
                <tbody>
                <?php foreach (array_reverse($periodos) as $p):
                    $prod = trim($p['produccion_mensual'] ?? '');
                    $unid = trim($p['unidad_produccion'] ?? '');
                    if ($prod === '') continue;
                ?>
                <tr>
                    <td><span class="badge bg-light text-dark border"><?= e($p['periodo']) ?></span></td>
                    <td class="fw-semibold"><?= e($prod) ?></td>
                    <td class="text-muted small"><?= e($unid) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (bloque_visible($bloques_visibles, 'comercio_exterior') && $ultimo): ?>
<!-- Comercio exterior -->
<div class="col-md-<?= bloque_visible($bloques_visibles, 'produccion') ? '6' : '12' ?>">
    <div class="md-chart-card h-100">
        <h6><i class="bi bi-globe2 me-1"></i>Comercio exterior — <?= e($ultimo['periodo']) ?></h6>
        <div class="row g-3 mt-1">
            <div class="col-6">
                <div class="p-3 rounded-3 text-center <?= $ultimo['exporta'] ? 'bg-success bg-opacity-10 border border-success border-opacity-25' : 'bg-light' ?>">
                    <i class="bi bi-arrow-up-right-circle fs-4 <?= $ultimo['exporta'] ? 'text-success' : 'text-muted' ?>"></i>
                    <div class="fw-semibold mt-1">Exporta</div>
                    <div class="small text-muted"><?= $ultimo['exporta'] ? 'Sí' : 'No declarado' ?></div>
                    <?php if ($ultimo['exporta'] && !empty($ultimo['paises_exporta'])): ?>
                    <div class="small mt-1 text-success"><?= e($ultimo['paises_exporta']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3 text-center <?= $ultimo['importa'] ? 'bg-info bg-opacity-10 border border-info border-opacity-25' : 'bg-light' ?>">
                    <i class="bi bi-arrow-down-left-circle fs-4 <?= $ultimo['importa'] ? 'text-info' : 'text-muted' ?>"></i>
                    <div class="fw-semibold mt-1">Importa</div>
                    <div class="small text-muted"><?= $ultimo['importa'] ? 'Sí' : 'No declarado' ?></div>
                    <?php if ($ultimo['importa'] && !empty($ultimo['paises_importa'])): ?>
                    <div class="small mt-1 text-info"><?= e($ultimo['paises_importa']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- /row -->

<p class="text-muted small mt-4">
    <i class="bi bi-info-circle me-1"></i>
    Solo se grafican los períodos con declaración enviada o aprobada por el Ministerio.
    <a href="formularios.php" class="ms-1">Ver mis declaraciones →</a>
</p>

<?php endif; // tiene_datos ?>

<?php
ob_start();
?>
<script src="<?= PUBLIC_URL ?>/vendor/chartjs/chart.umd.js"></script>
<script>
(function() {
    var labels     = <?= $labels_js ?>;
    var dotacion   = <?= $dotacion_js ?>;
    var energia    = <?= $energia_js ?>;
    var agua       = <?= $agua_js ?>;
    var gas        = <?= $gas_js ?>;
    var capacidad  = <?= $capacidad_js ?>;
    var emisiones  = <?= $emisiones_js ?>;
    var mascUlt    = <?= $masc_ult ?>;
    var femUlt     = <?= $fem_ult ?>;

    var gridColor  = 'rgba(0,0,0,.06)';
    var fontColor  = '#6c757d';

    var baseOpts = {
        responsive: true,
        plugins: { legend: { labels: { color: fontColor, boxWidth: 12 } } },
        scales: {
            x: { ticks: { color: fontColor }, grid: { color: gridColor } },
            y: { ticks: { color: fontColor }, grid: { color: gridColor }, beginAtZero: true }
        }
    };

    // Empleados evolución
    var elEmp = document.getElementById('chartEmpleados');
    if (elEmp) {
        new Chart(elEmp, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Empleados totales',
                    data: dotacion,
                    borderColor: '#1a5276',
                    backgroundColor: 'rgba(26,82,118,.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 5,
                    pointBackgroundColor: '#1a5276'
                }]
            },
            options: baseOpts
        });
    }

    // Género
    var elGen = document.getElementById('chartGenero');
    if (elGen && (mascUlt + femUlt) > 0) {
        new Chart(elGen, {
            type: 'doughnut',
            data: {
                labels: ['Masculino', 'Femenino'],
                datasets: [{
                    data: [mascUlt, femUlt],
                    backgroundColor: ['#1a5276', '#e91e8c'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: fontColor, boxWidth: 12 } }
                }
            }
        });
    }

    // Consumos
    var elCons = document.getElementById('chartConsumos');
    if (elCons) {
        var datasets = [];
        <?php if (bloque_visible($bloques_visibles, 'consumo_energia')): ?>
        datasets.push({ label: 'Energía (kWh)', data: energia, backgroundColor: 'rgba(255,152,0,.75)', borderColor: '#ff9800', borderWidth: 2, borderRadius: 4 });
        <?php endif; ?>
        <?php if (bloque_visible($bloques_visibles, 'consumo_agua')): ?>
        datasets.push({ label: 'Agua (m³)', data: agua, backgroundColor: 'rgba(33,150,243,.7)', borderColor: '#2196f3', borderWidth: 2, borderRadius: 4 });
        <?php endif; ?>
        <?php if (bloque_visible($bloques_visibles, 'consumo_gas')): ?>
        datasets.push({ label: 'Gas (m³)', data: gas, backgroundColor: 'rgba(76,175,80,.7)', borderColor: '#4caf50', borderWidth: 2, borderRadius: 4 });
        <?php endif; ?>
        if (datasets.length) {
            new Chart(elCons, {
                type: 'bar',
                data: { labels: labels, datasets: datasets },
                options: baseOpts
            });
        }
    }

    // Capacidad
    var elCap = document.getElementById('chartCapacidad');
    if (elCap) {
        new Chart(elCap, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Uso capacidad (%)',
                    data: capacidad,
                    backgroundColor: 'rgba(103,58,183,.7)',
                    borderColor: '#673ab7',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: fontColor, boxWidth: 12 } } },
                scales: {
                    x: { ticks: { color: fontColor }, grid: { color: gridColor } },
                    y: { ticks: { color: fontColor, callback: function(v) { return v + '%'; } }, grid: { color: gridColor }, beginAtZero: true, max: 100 }
                }
            }
        });
    }

    // Emisiones
    var elEm = document.getElementById('chartEmisiones');
    if (elEm) {
        new Chart(elEm, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'CO₂e (t)',
                    data: emisiones,
                    borderColor: '#78909c',
                    backgroundColor: 'rgba(120,144,156,.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 5,
                    pointBackgroundColor: '#78909c'
                }]
            },
            options: baseOpts
        });
    }
})();
</script>
<?php
$extra_scripts = ob_get_clean();
require_once BASEPATH . '/includes/empresa_layout_footer.php';
?>
