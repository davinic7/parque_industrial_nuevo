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
<script>window.LOTES_CFG = <?= json_encode([
    'apiBase' => rtrim(PUBLIC_URL, '/') . '/api/lotes/',
    'csrf'    => $csrf_token_value,
    'lotes'   => array_values($lotes_js),
], JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= asset_url('js/ministerio-lotes.js') ?>"></script>
<?php $extra_scripts = ob_get_clean(); ?>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
