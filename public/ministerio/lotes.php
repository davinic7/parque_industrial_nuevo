<?php
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title     = 'Gestión de Lotes';
$ministerio_nav = 'lotes';

$db = getDB();

$empresas_lista = $db->query(
    "SELECT id, COALESCE(nombre, razon_social) AS nombre FROM empresas ORDER BY COALESCE(nombre, razon_social)"
)->fetchAll();

// Empresas con solicitud de lote pendiente
$solicitudes_pendientes = [];
try {
    $solicitudes_pendientes = $db->query(
        "SELECT id, COALESCE(nombre, razon_social) AS nombre, latitud, longitud, lote_declarado
         FROM empresas
         WHERE lote_solicitud_estado = 'pendiente' AND lote_declarado IS NOT NULL
         ORDER BY COALESCE(nombre, razon_social)"
    )->fetchAll();
} catch (Throwable $e) { /* columna aún no migrada */ }

$lotes = $db->query(
    "SELECT l.id, l.numero_lote, l.sector, l.superficie_m2, l.estado,
            l.empresa_id, l.geometria_terreno,
            l.geometria_terreno IS NOT NULL AS tiene_poligono,
            COALESCE(e.nombre, e.razon_social) AS empresa_nombre
     FROM lotes l
     LEFT JOIN empresas e ON l.empresa_id = e.id
     ORDER BY l.numero_lote ASC"
)->fetchAll();

$conteos = ['total' => count($lotes), 'disponible' => 0, 'ocupado' => 0, 'reservado' => 0];
foreach ($lotes as $l) {
    $conteos[$l['estado']] = ($conteos[$l['estado']] ?? 0) + 1;
}

// Pasar geometrías al JS
$lotes_js = array_map(function($l) {
    return [
        'id'                => (int) $l['id'],
        'numero_lote'       => $l['numero_lote'],
        'sector'            => $l['sector'],
        'superficie_m2'     => $l['superficie_m2'] !== null ? (float) $l['superficie_m2'] : null,
        'estado'            => $l['estado'],
        'empresa_id'        => $l['empresa_id'] ? (int) $l['empresa_id'] : null,
        'empresa_nombre'    => $l['empresa_nombre'],
        'geometria_terreno' => $l['geometria_terreno'] ? json_decode($l['geometria_terreno'], true) : null,
    ];
}, $lotes);

$csrf_token_value = $_SESSION[CSRF_TOKEN_NAME] ?? '';

$extra_head = '
<link rel="stylesheet" href="' . PUBLIC_URL . '/vendor/leaflet/leaflet.css"/>
<link rel="stylesheet" href="' . PUBLIC_URL . '/vendor/leaflet-draw/leaflet.draw.css"/>
<style>
/* Mapa full-width arriba */
#mapaGeneral   { width: 100%; height: clamp(380px, calc(100vh - 370px), 600px); }
#mapaLoteModal { height: 500px; }

/* Tabla scrollable debajo del mapa */
.lotes-table-wrap {
    overflow-y: auto;
    max-height: 300px;
    border-top: 2px solid #e2e8f0;
}
.lotes-table-wrap table { font-size: .83rem; }

/* Header de tabla — neutraliza el override oscuro de styles.css */
.lotes-table-wrap thead th {
    background: #f1f5f9 !important;
    color: #374151 !important;
    font-weight: 700;
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 2px solid #e2e8f0 !important;
    padding: 8px 12px;
    white-space: nowrap;
}
.badge-disponible { background: #198754; }
.badge-ocupado    { background: #dc3545; }
.badge-reservado  { background: #fd7e14; }
.lote-row-sin-geo td:first-child { opacity: .55; }
tr.lote-activa    { background: #e8f4fd !important; outline: 2px solid #0d6efd; outline-offset: -2px; }

/* Control de capas Leaflet — tamaño compacto */
.leaflet-control-layers { font-size: .78rem; }

@media (max-width: 768px) {
    #mapaGeneral { height: 320px; }
    .lotes-table-wrap { max-height: 260px; }
}
</style>
';

require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<!-- Cards resumen -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-2 fw-bold"><?= $conteos['total'] ?></div>
            <div class="small text-muted">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 border-start border-success border-3">
            <div class="fs-2 fw-bold text-success"><?= $conteos['disponible'] ?></div>
            <div class="small text-muted">Disponibles</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 border-start border-danger border-3">
            <div class="fs-2 fw-bold text-danger"><?= $conteos['ocupado'] ?></div>
            <div class="small text-muted">Ocupados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 border-start border-warning border-3">
            <div class="fs-2 fw-bold text-warning"><?= $conteos['reservado'] ?></div>
            <div class="small text-muted">Reservados</div>
        </div>
    </div>
</div>

<?php if (!empty($solicitudes_pendientes)): ?>
<!-- Solicitudes de lote pendientes -->
<div class="card border-0 shadow-sm border-start border-warning border-4 mb-3">
    <div class="card-header bg-white py-2 d-flex align-items-center gap-2">
        <i class="fa-solid fa-clock text-warning"></i>
        <span class="fw-semibold">Solicitudes de lote pendientes</span>
        <span class="badge bg-warning text-dark ms-1"><?= count($solicitudes_pendientes) ?></span>
        <small class="text-muted ms-2">Empresas que declararon su lote y esperan confirmación</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.84rem;">
            <thead style="background:#fefce8; border-bottom:2px solid #fde68a;">
                <tr>
                    <th class="px-3 py-2">Empresa</th>
                    <th class="py-2">Lote declarado</th>
                    <th class="py-2">Coordenadas</th>
                    <th class="text-end pe-3 py-2">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($solicitudes_pendientes as $sol): ?>
            <tr>
                <td class="px-3 fw-semibold"><?= e($sol['nombre']) ?></td>
                <td><code class="text-warning-emphasis bg-warning-subtle px-2 py-1 rounded"><?= e($sol['lote_declarado']) ?></code></td>
                <td class="text-muted small">
                    <?php if ($sol['latitud'] && $sol['longitud']): ?>
                    <?= number_format((float)$sol['latitud'], 5) ?>, <?= number_format((float)$sol['longitud'], 5) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-end pe-3">
                    <button class="btn btn-sm btn-warning btn-asignar-sol"
                            data-empresa-id="<?= (int)$sol['id'] ?>"
                            data-empresa-nombre="<?= e($sol['nombre']) ?>"
                            data-lote-numero="<?= e($sol['lote_declarado']) ?>"
                            data-lat="<?= e($sol['latitud'] ?? '') ?>"
                            data-lng="<?= e($sol['longitud'] ?? '') ?>">
                        <i class="fa-solid fa-link me-1"></i>Asignar lote
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Vista principal lado a lado -->
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
        <span class="fw-semibold"><i class="fa-solid fa-map me-2 text-primary"></i>Lotes del parque</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="btnExportCsv">
                <i class="fa-solid fa-download me-1"></i>CSV
            </button>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalLote" id="btnNuevoLote">
                <i class="fa-solid fa-plus me-1"></i>Nuevo lote
            </button>
        </div>
    </div>

    <!-- Mapa full-width -->
    <div id="mapaGeneral"></div>

    <!-- Tabla de lotes debajo -->
    <div class="lotes-table-wrap">
        <table class="table table-hover align-middle mb-0" id="tablaLotes">
            <thead class="sticky-top">
                <tr>
                    <th>Lote</th>
                    <th>Sector</th>
                    <th class="text-end">m²</th>
                    <th>Estado</th>
                    <th>Empresa</th>
                    <th class="text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lotes as $lote): ?>
            <tr class="<?= $lote['tiene_poligono'] ? '' : 'lote-row-sin-geo' ?>"
                data-id="<?= $lote['id'] ?>"
                data-numero="<?= e($lote['numero_lote']) ?>"
                data-sector="<?= e($lote['sector'] ?? '') ?>"
                data-superficie="<?= e($lote['superficie_m2'] ?? '') ?>"
                data-estado="<?= e($lote['estado']) ?>"
                data-empresa="<?= (int) $lote['empresa_id'] ?>">
                <td class="fw-semibold"><?= e($lote['numero_lote']) ?></td>
                <td class="text-muted" style="font-size:.82rem;"><?= $lote['sector'] ? e($lote['sector']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end cell-m2">
                    <?= $lote['superficie_m2'] !== null ? number_format((float)$lote['superficie_m2'], 0, ',', '.') : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <span class="badge badge-<?= e($lote['estado']) ?>"><?= ucfirst(e($lote['estado'])) ?></span>
                </td>
                <td style="font-size:.82rem;">
                    <?= $lote['empresa_nombre'] ? '<i class="fa-solid fa-building me-1 text-muted" style="font-size:.7rem;"></i>' . e($lote['empresa_nombre']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-editar" title="Editar datos">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-outline-success btn-mapa" title="Dibujar polígono"
                                data-id="<?= $lote['id'] ?>" data-numero="<?= e($lote['numero_lote']) ?>">
                            <i class="fa-solid fa-draw-polygon"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-eliminar" title="Eliminar"
                                data-id="<?= $lote['id'] ?>" data-numero="<?= e($lote['numero_lote']) ?>">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($lotes)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay lotes. Creá el primero.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal crear / editar lote -->
<div class="modal fade" id="modalLote" tabindex="-1" aria-labelledby="modalLoteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLoteLabel">Nuevo lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formLote" novalidate>
                    <input type="hidden" id="loteId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Número de lote <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="loteNumero" required maxlength="50" placeholder="Ej: L-01, A3, 042">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Sector</label>
                            <input type="text" class="form-control" id="loteSector" maxlength="100" placeholder="Ej: Norte, Zona A">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Superficie (m²)</label>
                            <input type="number" class="form-control" id="loteSuperficie" min="0" step="1" placeholder="—">
                            <div class="form-text" id="superficieHint"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select" id="loteEstado">
                            <option value="disponible">Disponible</option>
                            <option value="ocupado">Ocupado</option>
                            <option value="reservado">Reservado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Empresa asignada</label>
                        <select class="form-select" id="loteEmpresa">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($empresas_lista as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= e($emp['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarLote">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="spinnerGuardar"></span>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal editor de polígono -->
<div class="modal fade" id="modalMapa" tabindex="-1" aria-labelledby="modalMapaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMapaLabel">
                    <i class="fa-solid fa-draw-polygon me-2"></i>Polígono — Lote <strong id="mapaLoteNumero"></strong>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-info rounded-0 border-0 mb-0 py-2 px-3 small d-flex align-items-center gap-3">
                    <span><i class="fa-solid fa-circle-info me-1"></i>Dibujá o editá el polígono. El área se calculará automáticamente.</span>
                    <span id="mapaAreaInfo" class="fw-bold text-dark ms-auto"></span>
                </div>
                <div id="mapaLoteModal"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-sm me-auto" id="btnBorrarPoligono">
                    <i class="fa-solid fa-trash me-1"></i>Borrar polígono
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarPoligono">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="spinnerPoligono"></span>
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar polígono
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmar eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar lote</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                ¿Eliminar el lote <strong id="eliminarNumero"></strong>? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnConfirmarEliminar">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="spinnerEliminar"></span>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script src="<?= PUBLIC_URL ?>/vendor/leaflet/leaflet.js"></script>
<script src="<?= PUBLIC_URL ?>/vendor/leaflet-draw/leaflet.draw.js"></script>
<script src="<?= PUBLIC_URL ?>/js/parque-leaflet.js"></script>
<script>
(function () {
    'use strict';

    var API_BASE   = '<?= rtrim(PUBLIC_URL, '/') ?>/api/lotes/';
    var CSRF_TOKEN = '<?= e($csrf_token_value) ?>';
    var LOTES_DATA = <?= json_encode(array_values($lotes_js)) ?>;
    var COLORES    = { disponible: '#198754', ocupado: '#dc3545', reservado: '#fd7e14' };

    // ── Utilidades ──────────────────────────────────────────────
    function csrfFetch(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(body)
        }).then(function (r) { return r.json(); });
    }

    function showToast(msg, tipo) {
        var el = document.createElement('div');
        el.className = 'position-fixed bottom-0 end-0 p-3';
        el.style.zIndex = 9999;
        var colorClass = tipo === 'ok' ? 'success' : 'danger';
        var wrapper = document.createElement('div');
        wrapper.className = 'toast show align-items-center text-white bg-' + colorClass + ' border-0';
        var inner = document.createElement('div');
        inner.className = 'd-flex';
        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = msg;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-close btn-close-white me-2 m-auto';
        btn.addEventListener('click', function () { if (el.parentNode) el.parentNode.removeChild(el); });
        inner.appendChild(body);
        inner.appendChild(btn);
        wrapper.appendChild(inner);
        el.appendChild(wrapper);
        document.body.appendChild(el);
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 3500);
    }

    function spinner(id, show) {
        var el = document.getElementById(id);
        if (el) el.classList.toggle('d-none', !show);
    }

    // GeoJSON [lng,lat] → Leaflet [lat,lng]
    function geoToLeaflet(coords) {
        return coords.map(function (c) { return [c[1], c[0]]; });
    }
    // Leaflet → GeoJSON Polygon (cierra el anillo)
    function leafletToGeo(latLngs) {
        var ring = latLngs.map(function (ll) { return [ll.lng, ll.lat]; });
        ring.push(ring[0]);
        return { type: 'Polygon', coordinates: [ring] };
    }
    // Área en m² (fórmula de Shoelace con proyección esférica)
    function calcAreaM2(latLngs) {
        var n = latLngs.length, area = 0, R = 6378137;
        for (var i = 0, j = n - 1; i < n; j = i++) {
            var pi = latLngs[i], pj = latLngs[j];
            var xi = pj.lng * Math.PI / 180 * R * Math.cos(pi.lat * Math.PI / 180);
            var yi = pi.lat * Math.PI / 180 * R;
            var xj = pi.lng * Math.PI / 180 * R * Math.cos(pj.lat * Math.PI / 180);
            var yj = pj.lat * Math.PI / 180 * R;
            area += (xj - xi) * (yj + yi);
        }
        return Math.abs(area / 2);
    }

    // ── Mapa general (lado derecho, siempre visible) ─────────────
    var mapaG = L.map('mapaGeneral').setView([-28.5337, -65.8010], 15);
    var capaSat = ParqueLeaflet.addSatelliteLayer(mapaG);
    // Liberar restricciones — permitir zoom y paneo por toda la provincia
    mapaG.setMinZoom(7);
    mapaG.setMaxBounds(null);
    var capaOsm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });
    L.control.layers({ 'Satélite': capaSat, 'Mapa': capaOsm }, {}, { position: 'topright', collapsed: false }).addTo(mapaG);
    ParqueLeaflet.addParquePolygon(mapaG, { fillOpacity: 0.05, weight: 2 });

    var poligonosPorId = {}; // id → L.Polygon

    function colorLote(estado) { return COLORES[estado] || '#6c757d'; }

    LOTES_DATA.forEach(function (lote) {
        if (!lote.geometria_terreno || !lote.geometria_terreno.coordinates) return;
        var coords  = lote.geometria_terreno.coordinates[0];
        var latLngs = geoToLeaflet(coords.slice(0, -1));
        var color   = colorLote(lote.estado);

        var poly = L.polygon(latLngs, {
            color: color, weight: 2, fillColor: color, fillOpacity: 0.28
        }).addTo(mapaG);

        poly.bindPopup(buildPopup(lote), { maxWidth: 230 });

        poly.on('mouseover', function () { this.setStyle({ weight: 4, fillOpacity: 0.45 }); resaltarFila(lote.id, true); });
        poly.on('mouseout',  function () { this.setStyle({ weight: 2, fillOpacity: 0.28 }); resaltarFila(lote.id, false); });
        poly.on('click',     function () { scrollToFila(lote.id); });

        poligonosPorId[lote.id] = poly;
    });

    // Zoom a todos los polígonos al iniciar
    var todosLatLngs = Object.values(poligonosPorId).map(function (p) { return p.getLatLngs()[0]; }).flat();
    if (todosLatLngs.length) mapaG.fitBounds(todosLatLngs, { padding: [20, 20], maxZoom: 18 });

    // Leyenda
    var legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
        var d = L.DomUtil.create('div');
        d.style.cssText = 'background:#fff;padding:7px 11px;border-radius:8px;font-size:.76rem;box-shadow:0 2px 8px rgba(0,0,0,.2);line-height:1.9;';
        d.innerHTML = ['disponible','ocupado','reservado'].map(function (e) {
            return '<div><span style="display:inline-block;width:11px;height:11px;border-radius:2px;background:' +
                COLORES[e] + ';margin-right:5px;vertical-align:middle;"></span>' +
                e.charAt(0).toUpperCase() + e.slice(1) + '</div>';
        }).join('');
        return d;
    };
    legend.addTo(mapaG);

    function buildPopup(lote) {
        var color = colorLote(lote.estado);
        return '<div style="min-width:160px;font-family:inherit;">' +
            '<div style="font-weight:700;font-size:.9rem;color:#1a5276;">Lote ' + lote.numero_lote + '</div>' +
            (lote.sector ? '<div style="font-size:.78rem;color:#64748b;">Sector: ' + lote.sector + '</div>' : '') +
            (lote.superficie_m2 ? '<div style="font-size:.78rem;">Sup: ' + Math.round(lote.superficie_m2).toLocaleString('es-AR') + ' m²</div>' : '') +
            '<span style="display:inline-block;margin:4px 0;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600;color:#fff;background:' + color + ';">' +
            lote.estado.charAt(0).toUpperCase() + lote.estado.slice(1) + '</span>' +
            (lote.empresa_nombre ? '<div style="font-size:.78rem;"><i class="fa-solid fa-building" style="margin-right:3px;"></i>' + lote.empresa_nombre + '</div>' : '') +
            '</div>';
    }

    // Sincronización tabla ↔ mapa
    function resaltarFila(id, activo) {
        var tr = document.querySelector('#tablaLotes tr[data-id="' + id + '"]');
        if (tr) tr.classList.toggle('lote-activa', activo);
    }

    function scrollToFila(id) {
        var tr = document.querySelector('#tablaLotes tr[data-id="' + id + '"]');
        if (!tr) return;
        document.querySelectorAll('#tablaLotes tr.lote-activa').forEach(function (r) { r.classList.remove('lote-activa'); });
        tr.classList.add('lote-activa');
        tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Hover de fila → resaltar polígono
    document.querySelectorAll('#tablaLotes tbody tr[data-id]').forEach(function (tr) {
        var id = parseInt(tr.dataset.id, 10);
        tr.addEventListener('mouseenter', function () {
            var p = poligonosPorId[id];
            if (p) p.setStyle({ weight: 4, fillOpacity: 0.45 });
        });
        tr.addEventListener('mouseleave', function () {
            var p = poligonosPorId[id];
            if (p) p.setStyle({ weight: 2, fillOpacity: 0.28 });
        });
        // Click en fila → zoom al polígono
        tr.addEventListener('click', function (e) {
            if (e.target.closest('button')) return; // no interferir con botones
            var p = poligonosPorId[id];
            if (p) {
                mapaG.fitBounds(p.getBounds(), { padding: [40, 40], maxZoom: 18 });
                p.openPopup();
            }
        });
    });

    // Función para actualizar el polígono en el mapa general sin recargar
    function actualizarPoligonoEnMapa(id, geojson, superficie) {
        // Eliminar anterior
        if (poligonosPorId[id]) { mapaG.removeLayer(poligonosPorId[id]); delete poligonosPorId[id]; }
        if (!geojson) return;

        // Actualizar datos en memoria
        var lote = LOTES_DATA.find(function (l) { return l.id === id; });
        if (lote) { lote.geometria_terreno = geojson; lote.superficie_m2 = superficie; }

        var coords  = geojson.coordinates[0];
        var latLngs = geoToLeaflet(coords.slice(0, -1));
        var color   = colorLote(lote ? lote.estado : 'disponible');

        var poly = L.polygon(latLngs, {
            color: color, weight: 2, fillColor: color, fillOpacity: 0.28
        }).addTo(mapaG);
        if (lote) poly.bindPopup(buildPopup(lote), { maxWidth: 230 });
        poly.on('mouseover', function () { this.setStyle({ weight: 4, fillOpacity: 0.45 }); resaltarFila(id, true); });
        poly.on('mouseout',  function () { this.setStyle({ weight: 2, fillOpacity: 0.28 }); resaltarFila(id, false); });
        poly.on('click',     function () { scrollToFila(id); });
        poligonosPorId[id] = poly;

        // Actualizar celda m² en la tabla sin recargar
        var tr = document.querySelector('#tablaLotes tr[data-id="' + id + '"]');
        if (tr && superficie !== null) {
            var cell = tr.querySelector('.cell-m2');
            if (cell) cell.textContent = Math.round(superficie).toLocaleString('es-AR');
            tr.classList.remove('lote-row-sin-geo');
        }
    }

    // ── CRUD: modales ────────────────────────────────────────────
    var modalLote     = new bootstrap.Modal(document.getElementById('modalLote'));
    var modalMapa     = new bootstrap.Modal(document.getElementById('modalMapa'));
    var modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));
    var eliminarId    = null;

    document.getElementById('btnNuevoLote').addEventListener('click', function () {
        document.getElementById('modalLoteLabel').textContent = 'Nuevo lote';
        document.getElementById('formLote').reset();
        document.getElementById('loteId').value = '';
        document.getElementById('superficieHint').textContent = '';
    });

    document.querySelectorAll('.btn-editar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tr = this.closest('tr');
            var id = parseInt(tr.dataset.id, 10);
            var lote = LOTES_DATA.find(function (l) { return l.id === id; });
            document.getElementById('modalLoteLabel').textContent = 'Editar lote';
            document.getElementById('loteId').value       = tr.dataset.id;
            document.getElementById('loteNumero').value   = tr.dataset.numero;
            document.getElementById('loteSector').value   = tr.dataset.sector;
            document.getElementById('loteSuperficie').value = tr.dataset.superficie;
            document.getElementById('loteEstado').value   = tr.dataset.estado;
            document.getElementById('loteEmpresa').value  = tr.dataset.empresa || '';
            // Mostrar área del polígono si existe
            if (lote && lote.geometria_terreno && lote.geometria_terreno.coordinates) {
                var lls = geoToLeaflet(lote.geometria_terreno.coordinates[0].slice(0, -1));
                var area = Math.round(calcAreaM2(lls));
                document.getElementById('superficieHint').textContent = 'Área del polígono: ' + area.toLocaleString('es-AR') + ' m²';
            } else {
                document.getElementById('superficieHint').textContent = '';
            }
            modalLote.show();
        });
    });

    document.getElementById('btnGuardarLote').addEventListener('click', function () {
        var numero = document.getElementById('loteNumero').value.trim();
        if (!numero) { document.getElementById('loteNumero').focus(); return; }
        var id = document.getElementById('loteId').value;
        spinner('spinnerGuardar', true); this.disabled = true; var me = this;
        var payload = {
            numero_lote:   numero,
            sector:        document.getElementById('loteSector').value.trim(),
            superficie_m2: document.getElementById('loteSuperficie').value || null,
            estado:        document.getElementById('loteEstado').value,
            empresa_id:    document.getElementById('loteEmpresa').value || null
        };
        if (id) payload.id = parseInt(id, 10);
        csrfFetch(API_BASE + 'guardar.php', payload).then(function (res) {
            spinner('spinnerGuardar', false); me.disabled = false;
            if (res.status === 'ok') { showToast(res.mensaje, 'ok'); setTimeout(function () { location.reload(); }, 700); }
            else showToast(res.mensaje || 'Error al guardar.', 'error');
        }).catch(function () { spinner('spinnerGuardar', false); me.disabled = false; showToast('Error de red.', 'error'); });
    });

    document.querySelectorAll('.btn-eliminar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            eliminarId = parseInt(this.dataset.id, 10);
            document.getElementById('eliminarNumero').textContent = this.dataset.numero;
            modalEliminar.show();
        });
    });

    document.getElementById('btnConfirmarEliminar').addEventListener('click', function () {
        if (!eliminarId) return;
        spinner('spinnerEliminar', true); this.disabled = true; var me = this;
        csrfFetch(API_BASE + 'eliminar.php', { id: eliminarId }).then(function (res) {
            spinner('spinnerEliminar', false); me.disabled = false;
            if (res.status === 'ok') { showToast('Lote eliminado.', 'ok'); setTimeout(function () { location.reload(); }, 700); }
            else { showToast(res.mensaje || 'Error.', 'error'); modalEliminar.hide(); }
        }).catch(function () { spinner('spinnerEliminar', false); me.disabled = false; showToast('Error de red.', 'error'); });
    });

    // ── Editor de polígono ───────────────────────────────────────
    var mapaEditor  = null;
    var drawnItems  = null;
    var mapaLoteId  = null;
    var areaActual  = 0;

    function abrirEditorPoligono(id, numero) {
        mapaLoteId = id;
        document.getElementById('mapaLoteNumero').textContent = numero;
        document.getElementById('mapaAreaInfo').textContent = '';
        modalMapa.show();
    }
    window.abrirEditorPoligono = abrirEditorPoligono;

    document.querySelectorAll('.btn-mapa').forEach(function (btn) {
        btn.addEventListener('click', function () {
            abrirEditorPoligono(parseInt(this.dataset.id, 10), this.dataset.numero);
        });
    });

    document.getElementById('modalMapa').addEventListener('shown.bs.modal', function () {
        if (mapaEditor) { mapaEditor.remove(); mapaEditor = null; }

        var map = L.map('mapaLoteModal').setView([-28.5337, -65.8010], 16);
        var capaSatModal = ParqueLeaflet.addSatelliteLayer(map);
        map.setMinZoom(7);
        map.setMaxBounds(null);
        var capaOsmModal = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });
        L.control.layers({ 'Satélite': capaSatModal, 'Mapa': capaOsmModal }, {}, { position: 'topright', collapsed: false }).addTo(map);
        ParqueLeaflet.addParquePolygon(map, { fillOpacity: 0.05, weight: 2 });
        drawnItems = new L.FeatureGroup().addTo(map);

        map.addControl(new L.Control.Draw({
            draw: {
                polygon: { allowIntersection: false, showArea: true,
                    shapeOptions: { color: '#0d6efd', weight: 3, fillOpacity: 0.25 } },
                polyline: false, rectangle: false, circle: false, circlemarker: false, marker: false
            },
            edit: { featureGroup: drawnItems }
        }));

        function mostrarArea(lls) {
            areaActual = calcAreaM2(lls);
            document.getElementById('mapaAreaInfo').textContent = 'Área: ' + Math.round(areaActual).toLocaleString('es-AR') + ' m²';
        }

        // Cargar todos los lotes como referencia + el propio como editable
        LOTES_DATA.forEach(function (lote) {
            if (!lote.geometria_terreno || !lote.geometria_terreno.coordinates) return;
            var coords  = lote.geometria_terreno.coordinates[0];
            var latLngs = geoToLeaflet(coords.slice(0, -1));

            if (lote.id === mapaLoteId) {
                var poly = L.polygon(latLngs, { color: '#0d6efd', weight: 3, fillOpacity: 0.25 }).addTo(drawnItems);
                map.fitBounds(poly.getBounds(), { padding: [40, 40] });
                mostrarArea(poly.getLatLngs()[0]);
            } else {
                L.polygon(latLngs, {
                    color: colorLote(lote.estado), weight: 1.5, fillOpacity: 0.15, interactive: false
                }).addTo(map).bindTooltip('Lote ' + lote.numero_lote, { permanent: false });
            }
        });

        map.on(L.Draw.Event.CREATED, function (e) {
            drawnItems.clearLayers();
            drawnItems.addLayer(e.layer);
            mostrarArea(e.layer.getLatLngs()[0]);
        });
        map.on(L.Draw.Event.EDITED, function (e) {
            e.layers.eachLayer(function (layer) { mostrarArea(layer.getLatLngs()[0]); });
        });

        mapaEditor = map;
    });

    document.getElementById('modalMapa').addEventListener('hidden.bs.modal', function () {
        if (mapaEditor) { mapaEditor.remove(); mapaEditor = null; }
        drawnItems = null; mapaLoteId = null; areaActual = 0;
        document.getElementById('mapaAreaInfo').textContent = '';
    });

    document.getElementById('btnBorrarPoligono').addEventListener('click', function () {
        if (!mapaLoteId || !confirm('¿Borrar el polígono de este lote?')) return;
        var me = this; me.disabled = true;
        csrfFetch(API_BASE + 'geometria.php', { id: mapaLoteId, geometria_terreno: null, superficie_m2: null }).then(function (res) {
            me.disabled = false;
            if (res.status === 'ok') {
                actualizarPoligonoEnMapa(mapaLoteId, null, null);
                showToast('Polígono borrado.', 'ok');
                if (drawnItems) drawnItems.clearLayers();
                document.getElementById('mapaAreaInfo').textContent = '';
            } else showToast(res.mensaje || 'Error.', 'error');
        }).catch(function () { me.disabled = false; showToast('Error de red.', 'error'); });
    });

    document.getElementById('btnGuardarPoligono').addEventListener('click', function () {
        if (!mapaLoteId) return;
        var layers = drawnItems ? drawnItems.getLayers() : [];
        if (!layers.length) { showToast('Dibujá un polígono primero.', 'error'); return; }
        var geojson = leafletToGeo(layers[0].getLatLngs()[0]);
        var m2 = Math.round(calcAreaM2(layers[0].getLatLngs()[0]));
        spinner('spinnerPoligono', true); this.disabled = true; var me = this;
        var capturedId = mapaLoteId;
        csrfFetch(API_BASE + 'geometria.php', {
            id:                capturedId,
            geometria_terreno: JSON.stringify(geojson),
            superficie_m2:     m2
        }).then(function (res) {
            spinner('spinnerPoligono', false); me.disabled = false;
            if (res.status === 'ok') {
                actualizarPoligonoEnMapa(capturedId, geojson, m2);
                showToast('Polígono guardado. Superficie: ' + m2.toLocaleString('es-AR') + ' m²', 'ok');
                modalMapa.hide();
            } else showToast(res.mensaje || 'Error al guardar.', 'error');
        }).catch(function () { spinner('spinnerPoligono', false); me.disabled = false; showToast('Error de red.', 'error'); });
    });

    // ── Solicitudes pendientes: asignación rápida ───────────────
    document.querySelectorAll('.btn-asignar-sol').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var empresaId     = this.dataset.empresaId;
            var empresaNombre = this.dataset.empresaNombre;
            var loteNumero    = this.dataset.loteNumero;
            var lat  = parseFloat(this.dataset.lat);
            var lng  = parseFloat(this.dataset.lng);

            document.getElementById('modalLoteLabel').textContent = 'Asignar lote — ' + empresaNombre;
            document.getElementById('formLote').reset();
            document.getElementById('loteId').value         = '';
            document.getElementById('loteNumero').value     = loteNumero;
            document.getElementById('loteEstado').value     = 'ocupado';
            document.getElementById('loteEmpresa').value    = empresaId;
            document.getElementById('superficieHint').textContent = '';

            // Zoom al punto declarado por la empresa
            if (!isNaN(lat) && !isNaN(lng)) {
                mapaG.setView([lat, lng], 17);
                L.marker([lat, lng], { opacity: 0.5 })
                    .addTo(mapaG)
                    .bindPopup('<strong>' + empresaNombre + '</strong><br>Lote declarado: ' + loteNumero)
                    .openPopup();
            }

            modalLote.show();
        });
    });

    // ── Exportar CSV ─────────────────────────────────────────────
    document.getElementById('btnExportCsv').addEventListener('click', function () {
        var rows = [['Lote', 'Sector', 'Superficie m2', 'Estado', 'Empresa', 'Polígono']];
        LOTES_DATA.forEach(function (l) {
            rows.push([
                l.numero_lote, l.sector || '', l.superficie_m2 !== null ? l.superficie_m2 : '',
                l.estado, l.empresa_nombre || '',
                l.geometria_terreno ? 'Sí' : 'No'
            ]);
        });
        var csv = rows.map(function (r) {
            return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' }));
        a.download = 'lotes.csv'; a.click();
    });

}());
</script>
<?php $extra_scripts = ob_get_clean(); ?>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
