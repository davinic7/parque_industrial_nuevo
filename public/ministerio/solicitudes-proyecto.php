<?php
/**
 * Solicitudes "Presentar proyecto" - Ministerio
 * Estados: nueva | en_carpeta | eliminada
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Solicitudes de proyecto';
$db = getDB();

// ── Acciones POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $id     = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($id > 0) {
        if ($accion === 'actualizar') {
            $estado = $_POST['estado'] ?? '';
            if (in_array($estado, ['nueva', 'en_carpeta', 'eliminada'])) {
                $db->prepare("UPDATE solicitudes_proyecto SET estado = ?, observaciones = ? WHERE id = ?")
                   ->execute([$estado, trim($_POST['observaciones'] ?? ''), $id]);
                set_flash('success', 'Solicitud actualizada.');
            }
        } elseif ($accion === 'marcar_carpeta') {
            $db->prepare("UPDATE solicitudes_proyecto SET estado = 'en_carpeta' WHERE id = ? AND estado = 'nueva'")
               ->execute([$id]);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true]);
                exit;
            }
        } elseif ($accion === 'eliminar') {
            $db->prepare("UPDATE solicitudes_proyecto SET estado = 'eliminada' WHERE id = ?")->execute([$id]);
            set_flash('success', 'Solicitud eliminada.');
        } elseif ($accion === 'enviar_mensaje') {
            $asunto    = trim($_POST['msg_asunto']    ?? '');
            $contenido = trim($_POST['msg_contenido'] ?? '');
            if ($asunto && $contenido) {
                $sol = $db->prepare("SELECT * FROM solicitudes_proyecto WHERE id = ?");
                $sol->execute([$id]);
                $solicitud = $sol->fetch();
                if ($solicitud) {
                    $dest = $db->prepare("SELECT u.id, e.id as emp_id FROM usuarios u LEFT JOIN empresas e ON e.usuario_id = u.id WHERE u.email = ?");
                    $dest->execute([$solicitud['email']]);
                    $dest_user = $dest->fetch();
                    require_once BASEPATH . '/includes/comunicaciones.php';
                    if ($dest_user && $dest_user['emp_id'] && coms_schema_disponible()) {
                        // Centro de Comunicaciones: reutiliza el hilo de la solicitud si ya existe
                        $hilo = $db->prepare("SELECT id FROM conversaciones WHERE referencia_tipo = 'solicitud_proyecto' AND referencia_id = ?");
                        $hilo->execute([$id]);
                        $conv_id = (int)$hilo->fetchColumn();
                        if (!$conv_id) {
                            $conv_id = coms_crear_conversacion([
                                'titulo'          => $asunto,
                                'empresa_id'      => $dest_user['emp_id'],
                                'iniciada_por'    => 'ministerio',
                                'categoria'       => 'tramite',
                                'referencia_tipo' => 'solicitud_proyecto',
                                'referencia_id'   => $id,
                            ]);
                        }
                        coms_enviar_mensaje([
                            'conversacion_id' => $conv_id,
                            'remitente_id'    => $_SESSION['user_id'],
                            'remitente_tipo'  => 'ministerio',
                            'contenido'       => $asunto . "\n\n" . $contenido,
                        ]);
                        set_flash('success', 'Mensaje enviado al panel de la empresa.');
                    } else {
                        $cuerpo = $contenido . "\n\n---\nParque Industrial de Catamarca — Ministerio de Producción";
                        if (function_exists('resend_send_email') && resend_send_email($solicitud['email'], $asunto, $cuerpo)) {
                            set_flash('success', 'Email enviado correctamente.');
                        } else {
                            set_flash('warning', 'No se pudo enviar el email. Verificá la configuración de correo.');
                        }
                    }
                }
            } else {
                set_flash('error', 'Completá el asunto y el mensaje.');
            }
        }
        redirect('solicitudes-proyecto.php?filtro=' . urlencode($_GET['filtro'] ?? 'activas'));
    }
}

// ── Datos ────────────────────────────────────────────────────────────────────
$filtro = $_GET['filtro'] ?? 'activas';
$filtro = in_array($filtro, ['activas', 'nueva', 'en_carpeta', 'eliminadas']) ? $filtro : 'activas';

try {
    $solicitudes = match($filtro) {
        'nueva'      => $db->query("SELECT * FROM solicitudes_proyecto WHERE estado = 'nueva'      ORDER BY created_at DESC")->fetchAll(),
        'en_carpeta' => $db->query("SELECT * FROM solicitudes_proyecto WHERE estado = 'en_carpeta' ORDER BY created_at DESC")->fetchAll(),
        'eliminadas' => $db->query("SELECT * FROM solicitudes_proyecto WHERE estado = 'eliminada'  ORDER BY created_at DESC")->fetchAll(),
        default      => $db->query("SELECT * FROM solicitudes_proyecto WHERE estado != 'eliminada' ORDER BY created_at DESC")->fetchAll(),
    };

    $counts_raw = $db->query("SELECT estado, COUNT(*) n FROM solicitudes_proyecto GROUP BY estado")->fetchAll();
    $counts = [];
    foreach ($counts_raw as $r) $counts[$r['estado']] = (int)$r['n'];
    $cnt_nuevas    = $counts['nueva']      ?? 0;
    $cnt_carpeta   = $counts['en_carpeta'] ?? 0;
    $cnt_activas   = $cnt_nuevas + $cnt_carpeta;
    $cnt_eliminadas = $counts['eliminada'] ?? 0;
} catch (Exception $e) {
    $solicitudes = [];
    $counts = [];
    $cnt_nuevas = $cnt_carpeta = $cnt_activas = $cnt_eliminadas = 0;
}

$ministerio_nav = 'solicitudes';
$ministerio_badge_solicitudes = $cnt_nuevas;
require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<style>
.sol-card { transition: box-shadow .15s; }
.sol-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1) !important; }
.sol-resumen { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.border-l-nueva    { border-left: 4px solid #ffc107 !important; }
.border-l-carpeta  { border-left: 4px solid #0d6efd !important; }
.doc-check { display:inline-flex; align-items:center; gap:.25rem; padding:.2rem .5rem; border-radius:.25rem; font-size:.8rem; }
.doc-check.si { background:#d1e7dd; color:#0a3622; }
.doc-check.no { background:#f8d7da; color:#58151c; }
</style>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h2 class="h4 mb-0 fw-semibold"><i class="bi bi-inbox me-2"></i>Solicitudes de proyecto</h2>
    <?php if ($cnt_nuevas > 0): ?>
    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
        <i class="bi bi-bell me-1"></i><?= $cnt_nuevas ?> nueva<?= $cnt_nuevas > 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
</div>

<?php show_flash(); ?>

<!-- Tabs filtro -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'activas' ? 'active fw-semibold' : '' ?>" href="?filtro=activas">
            Todas <span class="badge bg-secondary ms-1"><?= $cnt_activas ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'nueva' ? 'active fw-semibold' : '' ?>" href="?filtro=nueva">
            Nuevas <?php if ($cnt_nuevas > 0): ?><span class="badge bg-warning text-dark ms-1"><?= $cnt_nuevas ?></span><?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'en_carpeta' ? 'active fw-semibold' : '' ?>" href="?filtro=en_carpeta">
            En carpeta <span class="badge bg-primary ms-1"><?= $cnt_carpeta ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filtro === 'eliminadas' ? 'active fw-semibold' : '' ?>" href="?filtro=eliminadas">
            Eliminadas <span class="badge bg-secondary ms-1"><?= $cnt_eliminadas ?></span>
        </a>
    </li>
</ul>

<?php if (empty($solicitudes)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-inbox fs-1 opacity-25 d-block mb-3"></i>
    <p class="mb-0">No hay solicitudes <?= $filtro === 'eliminadas' ? 'eliminadas' : ($filtro === 'nueva' ? 'nuevas' : ($filtro === 'en_carpeta' ? 'en carpeta' : 'activas')) ?>.</p>
</div>
<?php else: ?>
<div class="d-flex flex-column gap-3">
<?php foreach ($solicitudes as $s):
    $estado = $s['estado'];
    [$badge_class, $border_class, $label] = match($estado) {
        'nueva'      => ['bg-warning text-dark', 'border-l-nueva',   'Nueva'],
        'en_carpeta' => ['bg-primary',           'border-l-carpeta', 'En carpeta'],
        'eliminada'  => ['bg-secondary',         '',                 'Eliminada'],
        default      => ['bg-secondary',         '',                 ucfirst($estado)],
    };
    $tiene_adjuntos = !empty($s['archivo_1']) || !empty($s['archivo_2']) || !empty($s['archivo_3']) || !empty($s['archivo_4']) || !empty($s['archivo_5']);
    $tipo_label = match($s['tipo_persona'] ?? '') {
        'fisica'   => 'P. Física',
        'juridica' => 'P. Jurídica',
        default    => '',
    };
?>
<div class="card shadow-sm sol-card <?= $border_class ?>" id="sol-<?= $s['id'] ?>">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge <?= $badge_class ?>" id="badge-<?= $s['id'] ?>"><?= $label ?></span>
                    <?php if ($tipo_label): ?>
                    <span class="badge bg-light text-dark border small"><?= $tipo_label ?></span>
                    <?php endif; ?>
                    <?php if ($s['solicita_cita']): ?>
                    <span class="badge bg-light text-dark border small"><i class="bi bi-calendar-check me-1"></i>Solicita cita</span>
                    <?php endif; ?>
                    <?php if ($tiene_adjuntos): ?>
                    <span class="badge bg-light text-dark border small"><i class="bi bi-paperclip me-1"></i>Con adjuntos</span>
                    <?php endif; ?>
                    <strong><?= e($s['nombre_empresa'] ?: $s['contacto']) ?></strong>
                </div>
                <p class="mb-1 small text-muted">
                    <i class="bi bi-person me-1"></i><?= e($s['contacto']) ?>
                    &nbsp;·&nbsp;<a href="mailto:<?= e($s['email']) ?>" class="text-muted"><?= e($s['email']) ?></a>
                    <?= $s['telefono'] ? ' &nbsp;·&nbsp;<i class="bi bi-telephone me-1"></i>' . e($s['telefono']) : '' ?>
                    <?= $s['cuit_cuil'] ? ' &nbsp;·&nbsp;CUIT: ' . e($s['cuit_cuil']) : '' ?>
                </p>
                <p class="mb-1 small text-secondary sol-resumen"><?= e($s['resumen_proyecto']) ?></p>
                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= format_datetime($s['created_at']) ?></small>
                <?php if ($s['observaciones']): ?>
                <p class="mb-0 mt-1 small text-muted fst-italic">
                    <i class="bi bi-sticky me-1"></i><?= e(truncate($s['observaciones'], 80)) ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="col-auto d-flex flex-column gap-2 align-items-stretch" style="min-width:140px;">
                <button type="button" class="btn btn-sm btn-primary btn-ver"
                    data-id="<?= $s['id'] ?>"
                    data-nombre="<?= e($s['nombre_empresa']) ?>"
                    data-contacto="<?= e($s['contacto']) ?>"
                    data-email="<?= e($s['email']) ?>"
                    data-telefono="<?= e($s['telefono'] ?? '') ?>"
                    data-tipo="<?= e($s['tipo_persona'] ?? '') ?>"
                    data-dni="<?= e($s['dni_titulares'] ?? '') ?>"
                    data-cuit="<?= e($s['cuit_cuil'] ?? '') ?>"
                    data-rubro="<?= e($s['rubro'] ?? '') ?>"
                    data-rubro-actividad="<?= e($s['rubro_actividad'] ?? '') ?>"
                    data-resumen="<?= htmlspecialchars($s['resumen_proyecto'], ENT_QUOTES) ?>"
                    data-cita="<?= $s['solicita_cita'] ? '1' : '0' ?>"
                    data-estado="<?= e($estado) ?>"
                    data-obs="<?= e($s['observaciones'] ?? '') ?>"
                    data-fecha="<?= e(format_datetime($s['created_at'])) ?>"
                    data-contrato="<?= $s['tiene_contrato_constitutivo'] ?>"
                    data-acta="<?= $s['tiene_acta_autoridades'] ?>"
                    data-registro="<?= $s['tiene_inscripcion_registro'] ?>"
                    data-arca="<?= $s['tiene_inscripcion_arca'] ?>"
                    data-arcat="<?= $s['tiene_inscripcion_arcat'] ?>"
                    data-otras="<?= e($s['otras_inscripciones'] ?? '') ?>"
                    data-ambiental="<?= $s['tiene_estudio_ambiental'] ?>"
                    data-croquis="<?= $s['tiene_croquis_obra'] ?>"
                    data-cronograma="<?= $s['tiene_cronograma_obra'] ?>"
                    data-archivo1="<?= e($s['archivo_1'] ?? '') ?>"
                    data-archivo2="<?= e($s['archivo_2'] ?? '') ?>"
                    data-archivo3="<?= e($s['archivo_3'] ?? '') ?>"
                    data-archivo4="<?= e($s['archivo_4'] ?? '') ?>"
                    data-archivo5="<?= e($s['archivo_5'] ?? '') ?>">
                    <i class="bi bi-eye me-1"></i>Ver detalle
                </button>
                <?php if ($estado !== 'eliminada'): ?>
                <form method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                            onclick="return confirm('¿Eliminar esta solicitud?')">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Modal detalle ──────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="mNombre"></h5>
                    <small class="text-muted" id="mFecha"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formModal">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="mId">
                <input type="hidden" name="accion" value="actualizar">
                <div class="modal-body">
                    <!-- Datos del solicitante -->
                    <p class="text-uppercase text-muted small fw-semibold mb-2"><i class="bi bi-person-vcard me-1"></i>Datos del solicitante</p>
                    <div class="row g-2 mb-3 small">
                        <div class="col-sm-4 d-flex align-items-center gap-2">
                            <i class="bi bi-person text-primary"></i><span id="mContacto"></span>
                        </div>
                        <div class="col-sm-4 d-flex align-items-center gap-2">
                            <i class="bi bi-envelope text-primary"></i><a id="mEmail" href="#"></a>
                        </div>
                        <div class="col-sm-4 d-flex align-items-center gap-2" id="mTelWrap">
                            <i class="bi bi-telephone text-primary"></i><span id="mTel"></span>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 small">
                        <div class="col-sm-3" id="mTipoWrap">
                            <span class="text-muted">Tipo:</span> <strong id="mTipo"></strong>
                        </div>
                        <div class="col-sm-3" id="mDniWrap">
                            <span class="text-muted">DNI:</span> <strong id="mDni"></strong>
                        </div>
                        <div class="col-sm-3" id="mCuitWrap">
                            <span class="text-muted">CUIT/CUIL:</span> <strong id="mCuit"></strong>
                        </div>
                        <div class="col-sm-3" id="mRubroWrap">
                            <span class="text-muted">Rubro:</span> <strong id="mRubro"></strong>
                        </div>
                    </div>
                    <div id="mRubroActWrap" class="mb-3 small d-none">
                        <span class="text-muted">Actividad:</span> <span id="mRubroAct"></span>
                    </div>

                    <div id="mCitaBadge" class="mb-3 d-none">
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-calendar-check me-1"></i>Solicita cita presencial
                        </span>
                    </div>

                    <!-- Documentación -->
                    <p class="text-uppercase text-muted small fw-semibold mb-2"><i class="bi bi-folder-check me-1"></i>Documentación declarada</p>
                    <div class="d-flex flex-wrap gap-2 mb-3" id="mDocChecks"></div>
                    <div id="mOtrasWrap" class="mb-3 small d-none">
                        <span class="text-muted">Otras inscripciones:</span> <span id="mOtras"></span>
                    </div>

                    <!-- Adjuntos -->
                    <div id="mAdjuntos" class="mb-3 d-none">
                        <p class="small fw-semibold text-uppercase text-muted mb-2"><i class="bi bi-paperclip me-1"></i>Archivos adjuntos</p>
                        <div class="d-flex gap-2 flex-wrap" id="mAdjuntosLinks"></div>
                    </div>

                    <!-- Resumen -->
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body py-3">
                            <p class="text-uppercase text-muted small fw-semibold mb-2">Resumen del proyecto</p>
                            <p class="mb-0" id="mResumen" style="white-space:pre-wrap;"></p>
                        </div>
                    </div>

                    <!-- Estado + Obs -->
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Estado</label>
                            <select name="estado" id="mEstado" class="form-select form-select-sm">
                                <option value="nueva">Nueva</option>
                                <option value="en_carpeta">En carpeta</option>
                                <option value="eliminada">Eliminada</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Observaciones internas</label>
                            <textarea name="observaciones" id="mObs" class="form-control form-control-sm" rows="2"
                                      placeholder="Notas del ministerio..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" id="mBtnEmail" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-envelope me-1"></i>Enviar mensaje
                    </button>
                    <a id="mBtnEmpresa" href="#" class="btn btn-success btn-sm">
                        <i class="bi bi-building-check me-1"></i>Registrar empresa
                    </a>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-floppy me-1"></i>Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal mensaje ──────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalMensaje" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope me-2"></i>Enviar mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formMensaje">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="enviar_mensaje">
                <input type="hidden" name="id" id="msgSolId">
                <div class="modal-body">
                    <p class="text-muted small mb-3">Destino: <strong id="msgDestino"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Asunto</label>
                        <input type="text" name="msg_asunto" id="msgAsunto" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mensaje</label>
                        <textarea name="msg_contenido" id="msgContenido" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send me-1"></i>Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$uploads_url_js    = json_encode(rtrim(PUBLIC_URL, '/') . '/uploads/');
$csrf_name_json    = json_encode(CSRF_TOKEN_NAME);
$csrf_val_json     = json_encode($_SESSION[CSRF_TOKEN_NAME] ?? '');
$extra_scripts = <<<HTML
<script>
(function () {
    const UPLOADS_URL = {$uploads_url_js};
    const CSRF_NAME   = {$csrf_name_json};
    const CSRF_VAL    = {$csrf_val_json};

    const DOC_LABELS = [
        ['contrato',   'Contrato constitutivo'],
        ['acta',       'Acta de autoridades'],
        ['registro',   'Inscripción Reg. Público'],
        ['arca',       'Inscripción ARCA'],
        ['arcat',      'Inscripción ARCAT'],
        ['ambiental',  'Estudio Impacto Ambiental'],
        ['croquis',    'Croquis de obra'],
        ['cronograma', 'Cronograma de obra'],
    ];

    document.querySelectorAll('.btn-ver').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = this.dataset;

            document.getElementById('mId').value             = d.id;
            document.getElementById('mNombre').textContent   = d.nombre || d.contacto;
            document.getElementById('mFecha').textContent    = d.fecha;
            document.getElementById('mContacto').textContent = d.contacto;

            const emailEl = document.getElementById('mEmail');
            emailEl.textContent = d.email;
            emailEl.href        = 'mailto:' + d.email;

            const telWrap = document.getElementById('mTelWrap');
            if (d.telefono) {
                document.getElementById('mTel').textContent = d.telefono;
                telWrap.classList.remove('d-none');
            } else {
                telWrap.classList.add('d-none');
            }

            // Campos nuevos
            const show = (id, val) => {
                const el = document.getElementById(id);
                if (val) { el.classList.remove('d-none'); } else { el.classList.add('d-none'); }
            };
            const tipoMap = { fisica: 'Persona física', juridica: 'Persona jurídica' };
            document.getElementById('mTipo').textContent = tipoMap[d.tipo] || '—';
            show('mTipoWrap', d.tipo);
            document.getElementById('mDni').textContent = d.dni || '—';
            show('mDniWrap', d.dni);
            document.getElementById('mCuit').textContent = d.cuit || '—';
            show('mCuitWrap', d.cuit);
            const rubroText = [d.rubro, d.rubroActividad].filter(Boolean).join(' — ');
            document.getElementById('mRubro').textContent = d.rubro || '—';
            show('mRubroWrap', d.rubro);
            document.getElementById('mRubroAct').textContent = d.rubroActividad || '';
            show('mRubroActWrap', d.rubroActividad);

            // Documentación checks
            const checksDiv = document.getElementById('mDocChecks');
            checksDiv.innerHTML = '';
            DOC_LABELS.forEach(([key, label]) => {
                const tiene = d[key] === '1';
                checksDiv.innerHTML += '<span class="doc-check ' + (tiene ? 'si' : 'no') + '">'
                    + '<i class="bi bi-' + (tiene ? 'check-circle-fill' : 'x-circle') + '"></i> '
                    + label + '</span>';
            });
            document.getElementById('mOtras').textContent = d.otras || '';
            show('mOtrasWrap', d.otras);

            document.getElementById('mCitaBadge').classList.toggle('d-none', d.cita !== '1');
            document.getElementById('mResumen').textContent = d.resumen;
            document.getElementById('mEstado').value        = d.estado;
            document.getElementById('mObs').value           = d.obs;

            // Adjuntos (hasta 5)
            const adjDiv   = document.getElementById('mAdjuntos');
            const adjLinks = document.getElementById('mAdjuntosLinks');
            adjLinks.innerHTML = '';
            const archivos = [d.archivo1, d.archivo2, d.archivo3, d.archivo4, d.archivo5].filter(Boolean);
            if (archivos.length > 0) {
                archivos.forEach((a, i) => {
                    const url  = UPLOADS_URL + a;
                    adjLinks.innerHTML += '<a href="' + url + '" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-arrow-down me-1"></i>Archivo ' + (i+1) + '</a>';
                });
                adjDiv.classList.remove('d-none');
            } else {
                adjDiv.classList.add('d-none');
            }

            // Botón "Registrar empresa"
            const params = new URLSearchParams({
                prefill_nombre:   d.nombre,
                prefill_email:    d.email,
                prefill_contacto: d.contacto,
                prefill_telefono: d.telefono || '',
            });
            document.getElementById('mBtnEmpresa').href = 'nueva-empresa.php?' + params.toString();

            // Botón mensaje
            document.getElementById('mBtnEmail').onclick = function () {
                bootstrap.Modal.getInstance(document.getElementById('modalDetalle'))?.hide();
                document.getElementById('msgSolId').value          = d.id;
                document.getElementById('msgDestino').textContent  = d.email;
                document.getElementById('msgAsunto').value         = 'Re: Solicitud de proyecto - ' + (d.nombre || d.contacto);
                document.getElementById('msgContenido').value      = '';
                new bootstrap.Modal(document.getElementById('modalMensaje')).show();
            };

            // Marcar como en_carpeta silenciosamente si es nueva
            if (d.estado === 'nueva') {
                const fd = new FormData();
                fd.append(CSRF_NAME, CSRF_VAL);
                fd.append('id', d.id);
                fd.append('accion', 'marcar_carpeta');
                fetch('solicitudes-proyecto.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                }).then(() => {
                    const badge = document.getElementById('badge-' + d.id);
                    if (badge) { badge.className = 'badge bg-primary'; badge.textContent = 'En carpeta'; }
                    const card = document.getElementById('sol-' + d.id);
                    if (card) { card.classList.remove('border-l-nueva'); card.classList.add('border-l-carpeta'); }
                }).catch(() => {});
            }

            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        });
    });
})();
</script>
HTML;
require_once BASEPATH . '/includes/ministerio_layout_footer.php';
