<?php
/**
 * Hub CMS – Gestión del sitio público
 * Permite al ministerio editar todos los textos, servicios, contacto y redes del sitio.
 */
require_once __DIR__ . '/../../config/config.php';
if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Gestión del sitio público';
$db = getDB();

$tabs_validos = ['inicio', 'el_parque', 'contacto'];
$tab = in_array($_GET['tab'] ?? '', $tabs_validos) ? $_GET['tab'] : 'inicio';

// ── POST handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $section = $_POST['tab_section'] ?? '';
    $kvs = [];

    switch ($section) {
        case 'inicio':
            $kvs = [
                'dato_impacto_texto'            => ['textarea', trim($_POST['dato_impacto_texto'] ?? '')],
                'seccion_sectores_titulo'       => ['text',     trim($_POST['seccion_sectores_titulo'] ?? '')],
                'seccion_sectores_descripcion'  => ['textarea', trim($_POST['seccion_sectores_descripcion'] ?? '')],
                'seccion_mapa_titulo'           => ['text',     trim($_POST['seccion_mapa_titulo'] ?? '')],
                'seccion_mapa_descripcion'      => ['textarea', trim($_POST['seccion_mapa_descripcion'] ?? '')],
            ];
            break;

        case 'el_parque':
            $svc_raw = trim($_POST['nosotros_servicios'] ?? '[]');
            $svc = json_decode($svc_raw, true);
            if (!is_array($svc)) $svc = [];
            $svc = array_values(array_map(fn($s) => [
                'icon'   => preg_replace('/[^a-z0-9\-]/', '', $s['icon'] ?? 'bi-gear'),
                'titulo' => mb_substr(strip_tags($s['titulo'] ?? ''), 0, 100),
                'desc'   => mb_substr(strip_tags($s['desc']   ?? ''), 0, 300),
            ], $svc));
            $kvs = [
                'nosotros_titulo'              => ['text',     trim($_POST['nosotros_titulo'] ?? '')],
                'nosotros_subtitulo'           => ['text',     trim($_POST['nosotros_subtitulo'] ?? '')],
                'nosotros_texto'               => ['richtext', trim($_POST['nosotros_texto'] ?? '')],
                'nosotros_ubicacion_direccion' => ['text',     trim($_POST['nosotros_ubicacion_direccion'] ?? '')],
                'nosotros_ubicacion_localidad' => ['text',     trim($_POST['nosotros_ubicacion_localidad'] ?? '')],
                'nosotros_ubicacion_provincia' => ['text',     trim($_POST['nosotros_ubicacion_provincia'] ?? '')],
                'nosotros_contacto_direccion'  => ['textarea', trim($_POST['nosotros_contacto_direccion'] ?? '')],
                'nosotros_contacto_email'      => ['text',     trim($_POST['nosotros_contacto_email'] ?? '')],
                'nosotros_contacto_telefono'   => ['text',     trim($_POST['nosotros_contacto_telefono'] ?? '')],
                'nosotros_servicios'           => ['json',     json_encode($svc)],
            ];
            break;

        case 'contacto':
            $kvs = [
                'sitio_email'        => ['text', trim($_POST['sitio_email'] ?? '')],
                'sitio_telefono'     => ['text', trim($_POST['sitio_telefono'] ?? '')],
                'sitio_direccion'    => ['text', trim($_POST['sitio_direccion'] ?? '')],
                'footer_descripcion' => ['text', trim($_POST['footer_descripcion'] ?? '')],
                'redes_facebook'     => ['text', trim($_POST['redes_facebook'] ?? '')],
                'redes_instagram'    => ['text', trim($_POST['redes_instagram'] ?? '')],
                'redes_twitter'      => ['text', trim($_POST['redes_twitter'] ?? '')],
            ];
            break;
    }

    if ($kvs) {
        try {
            $stmt = $db->prepare(
                "INSERT INTO configuracion_sitio (clave, valor, tipo, grupo)
                 VALUES (?, ?, ?, 'sitio')
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo)"
            );
            foreach ($kvs as $clave => [$tipo, $valor]) {
                $stmt->execute([$clave, $valor, $tipo]);
            }
            set_flash('success', 'Cambios guardados correctamente.');
        } catch (Exception $e) {
            error_log('sitio-publico: ' . $e->getMessage());
            set_flash('error', 'Error al guardar. Intente nuevamente.');
        }
    }
    redirect('sitio-publico.php?tab=' . $section);
}

// ── Leer valores actuales ─────────────────────────────────────────────────────

// Tab Inicio
$dato_impacto_texto           = get_config('dato_impacto_texto', '');
$seccion_sectores_titulo      = get_config('seccion_sectores_titulo',      'Industrias por Sector');
$seccion_sectores_descripcion = get_config('seccion_sectores_descripcion', 'El Parque Industrial de Catamarca cuenta con empresas de diversos sectores productivos, destacándose la industria textil, construcción y metalúrgica.');
$seccion_mapa_titulo          = get_config('seccion_mapa_titulo',          'Zona del Parque Industrial');
$seccion_mapa_descripcion     = get_config('seccion_mapa_descripcion',     'Polígono del Parque Industrial El Pantanillo. Para ver las empresas en el mapa, ingresá al mapa interactivo.');

// Tab El Parque
$nosotros_titulo              = get_config('nosotros_titulo',    'Parque Industrial de Catamarca');
$nosotros_subtitulo           = get_config('nosotros_subtitulo', 'Impulsando el desarrollo productivo de la provincia');
$nosotros_texto               = get_config('nosotros_texto', '');
$nosotros_ubicacion_dir       = get_config('nosotros_ubicacion_direccion', 'RN 38, El Pantanillo');
$nosotros_ubicacion_localidad = get_config('nosotros_ubicacion_localidad', 'San Fernando del Valle de Catamarca');
$nosotros_ubicacion_provincia = get_config('nosotros_ubicacion_provincia', 'Catamarca, Argentina');
$contacto_dir                 = get_config('nosotros_contacto_direccion',  '');
$contacto_email_p             = get_config('nosotros_contacto_email',      '');
$contacto_tel                 = get_config('nosotros_contacto_telefono',   '');

$servicios_default = [
    ['icon' => 'bi-lightning-charge', 'titulo' => 'Energía Eléctrica',  'desc' => 'Red de media y baja tensión con capacidad para la demanda industrial.'],
    ['icon' => 'bi-droplet',          'titulo' => 'Agua Potable',        'desc' => 'Red de agua potable y sistema de pozos para abastecimiento continuo.'],
    ['icon' => 'bi-fire',             'titulo' => 'Gas Natural',         'desc' => 'Red de gas natural disponible para procesos industriales.'],
    ['icon' => 'bi-signpost-split',   'titulo' => 'Accesos Viales',      'desc' => 'Rutas de acceso pavimentadas y señalizadas para transporte de cargas.'],
    ['icon' => 'bi-shield-check',     'titulo' => 'Seguridad',           'desc' => 'Sistema de vigilancia y control de acceso las 24 horas.'],
    ['icon' => 'bi-wifi',             'titulo' => 'Conectividad',        'desc' => 'Acceso a servicios de telecomunicaciones y fibra óptica.'],
];
$svc_json = get_config('nosotros_servicios', '');
$servicios = ($svc_json !== '' && is_array(json_decode($svc_json, true)) && count(json_decode($svc_json, true)) > 0)
    ? json_decode($svc_json, true) : $servicios_default;

// Tab Contacto y Redes
$sitio_email     = get_config('sitio_email',        '');
$sitio_telefono  = get_config('sitio_telefono',     '');
$sitio_direccion = get_config('sitio_direccion',    '');
$footer_desc     = get_config('footer_descripcion', 'Impulsando el desarrollo industrial de la provincia.');
$redes_facebook  = get_config('redes_facebook',     '');
$redes_instagram = get_config('redes_instagram',    '');
$redes_twitter   = get_config('redes_twitter',      '');

$ministerio_nav = 'sitio_publico';
$extra_head = '<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">';
require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<style>
.cms-tab-nav .nav-link { font-weight: 500; color: #555; }
.cms-tab-nav .nav-link.active { color: var(--bs-primary); border-bottom: 3px solid var(--bs-primary); background: transparent; }
.cms-tab-nav { border-bottom: 1px solid #dee2e6; margin-bottom: 1.5rem; }
.cms-section-label {
    font-size: .72rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #6c757d;
    border-bottom: 1px solid #e9ecef; padding-bottom: 6px; margin-bottom: 12px;
}
.servicio-item { background: #f8f9fa; transition: background .15s; }
.servicio-item:hover { background: #e9ecef; }
.preview-btn { font-size: .8rem; }
#quill-editor { min-height: 180px; font-size: 1rem; }
.ql-container.ql-snow { border-radius: 0 0 .375rem .375rem; }
.ql-toolbar.ql-snow { border-radius: .375rem .375rem 0 0; }
.icon-preview-live { font-size: 1.5rem; color: var(--bs-primary); min-width: 2rem; text-align: center; }
.social-row { display: flex; align-items: center; gap: .75rem; }
.social-row .si { font-size: 1.4rem; min-width: 2rem; text-align: center; }
</style>

<!-- Encabezado -->
<div class="d-flex align-items-center justify-content-between mb-1">
    <h2 class="h4 fw-semibold mb-0"><i class="bi bi-globe2 me-2"></i>Gestión del sitio público</h2>
    <a href="<?= PUBLIC_URL ?>/" target="_blank" class="btn btn-outline-secondary btn-sm preview-btn">
        <i class="bi bi-box-arrow-up-right me-1"></i>Ver sitio
    </a>
</div>
<p class="text-muted small mb-3">Editá los textos, servicios, contacto y redes que se muestran en el sitio público.</p>

<?php show_flash(); ?>

<!-- Tabs de navegación -->
<ul class="nav cms-tab-nav mb-0 flex-nowrap overflow-auto">
    <li class="nav-item">
        <a class="nav-link px-3 py-2<?= $tab === 'inicio'    ? ' active' : '' ?>" href="?tab=inicio">
            <i class="bi bi-house me-1"></i>Inicio
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link px-3 py-2<?= $tab === 'el_parque' ? ' active' : '' ?>" href="?tab=el_parque">
            <i class="bi bi-building me-1"></i>El Parque
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link px-3 py-2<?= $tab === 'contacto'  ? ' active' : '' ?>" href="?tab=contacto">
            <i class="bi bi-person-lines-fill me-1"></i>Contacto y Redes
        </a>
    </li>
</ul>

<?php /* ══════════════════ TAB: INICIO ══════════════════ */ if ($tab === 'inicio'): ?>

<form method="POST" id="form-inicio">
    <?= csrf_field() ?>
    <input type="hidden" name="tab_section" value="inicio">

    <!-- Franja "¿Sabías que?" -->
    <div class="card mb-3">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-lightbulb me-2 text-warning"></i>Franja de dato de impacto</span>
            <a href="<?= PUBLIC_URL ?>/" target="_blank" class="btn btn-outline-secondary btn-sm preview-btn">
                <i class="bi bi-eye me-1"></i>Ver en inicio
            </a>
        </div>
        <div class="card-body">
            <label class="form-label">Texto personalizado <span class="text-muted small">(opcional)</span></label>
            <textarea name="dato_impacto_texto" class="form-control" rows="3"
                      placeholder="Ej: El Parque Industrial El Pantanillo concentra más de 78 empresas y genera miles de empleos en Catamarca."><?= e($dato_impacto_texto) ?></textarea>
            <div class="form-text">Si lo dejás vacío, el texto se genera automáticamente con las estadísticas en tiempo real.</div>
        </div>
    </div>

    <!-- Sección Industrias por Sector -->
    <div class="card mb-3">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-pie-chart me-2 text-info"></i>Sección "Industrias por Sector"</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Aparece en la página de inicio</div>
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input type="text" name="seccion_sectores_titulo" class="form-control"
                       value="<?= e($seccion_sectores_titulo) ?>" maxlength="100">
            </div>
            <div>
                <label class="form-label">Descripción / Párrafo</label>
                <textarea name="seccion_sectores_descripcion" class="form-control" rows="3"><?= e($seccion_sectores_descripcion) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Sección Zona del Parque (mapa) -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-map me-2 text-success"></i>Sección "Zona del Parque"</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Aparece en la página de inicio, encima del mapa</div>
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input type="text" name="seccion_mapa_titulo" class="form-control"
                       value="<?= e($seccion_mapa_titulo) ?>" maxlength="100">
            </div>
            <div>
                <label class="form-label">Descripción / Párrafo</label>
                <textarea name="seccion_mapa_descripcion" class="form-control" rows="3"><?= e($seccion_mapa_descripcion) ?></textarea>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
        <a href="<?= PUBLIC_URL ?>/" target="_blank" class="btn btn-outline-secondary">Ver página de inicio</a>
    </div>
</form>

<?php /* ══════════════════ TAB: EL PARQUE ══════════════════ */ elseif ($tab === 'el_parque'): ?>

<form method="POST" id="form-el-parque">
    <?= csrf_field() ?>
    <input type="hidden" name="tab_section" value="el_parque">
    <input type="hidden" name="nosotros_texto" id="nosotros_texto_hidden">
    <input type="hidden" name="nosotros_servicios" id="nosotros_servicios_hidden">

    <!-- Hero del parque -->
    <div class="card mb-3">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-card-heading me-2 text-primary"></i>Encabezado hero</span>
            <a href="<?= PUBLIC_URL ?>/el-parque.php" target="_blank" class="btn btn-outline-secondary btn-sm preview-btn">
                <i class="bi bi-eye me-1"></i>Ver página
            </a>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Aparece en el hero de la página "El Parque"</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Título principal</label>
                    <input type="text" name="nosotros_titulo" class="form-control"
                           value="<?= e($nosotros_titulo) ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subtítulo</label>
                    <input type="text" name="nosotros_subtitulo" class="form-control"
                           value="<?= e($nosotros_subtitulo) ?>" maxlength="200">
                </div>
            </div>
        </div>
    </div>

    <!-- Texto principal con Quill -->
    <div class="card mb-3">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-text-paragraph me-2 text-secondary"></i>Texto "Sobre el Parque Industrial"</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Aparece en la sección de descripción de la página El Parque</div>
            <div id="quill-editor"></div>
            <!-- El contenido del editor se copia aquí antes de enviar -->
            <div id="quill-raw-source" class="d-none"><?= e($nosotros_texto) ?></div>
        </div>
    </div>

    <!-- Ubicación -->
    <div class="card mb-3">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-geo-alt me-2 text-danger"></i>Ubicación</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Datos de ubicación que aparecen en el mapa lateral y en el contacto</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Dirección / Ruta</label>
                    <input type="text" name="nosotros_ubicacion_direccion" class="form-control"
                           value="<?= e($nosotros_ubicacion_dir) ?>" placeholder="RN 38, El Pantanillo">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Localidad</label>
                    <input type="text" name="nosotros_ubicacion_localidad" class="form-control"
                           value="<?= e($nosotros_ubicacion_localidad) ?>" placeholder="San Fernando del Valle de Catamarca">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Provincia / País</label>
                    <input type="text" name="nosotros_ubicacion_provincia" class="form-control"
                           value="<?= e($nosotros_ubicacion_provincia) ?>" placeholder="Catamarca, Argentina">
                </div>
            </div>
        </div>
    </div>

    <!-- Contacto institucional -->
    <div class="card mb-3">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-telephone me-2 text-success"></i>Contacto institucional</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Aparece en la sección de contacto de la página El Parque</div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Dirección postal</label>
                    <textarea name="nosotros_contacto_direccion" class="form-control" rows="2"><?= e($contacto_dir) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="nosotros_contacto_email" class="form-control"
                           value="<?= e($contacto_email_p) ?>" placeholder="contacto@parqueindustrial.gob.ar">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="nosotros_contacto_telefono" class="form-control"
                           value="<?= e($contacto_tel) ?>" placeholder="(0383) 4-XXXXXX">
                </div>
            </div>
        </div>
    </div>

    <!-- Servicios e infraestructura -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-gear me-2 text-warning"></i>Servicios e infraestructura</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Tarjetas que aparecen en la sección "Servicios e Infraestructura" de la página El Parque</div>

            <!-- Lista de servicios actual -->
            <div id="servicios-lista" class="mb-3"></div>

            <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Usá las flechas para reordenar. Hacé clic en <i class="bi bi-pencil"></i> para editar un servicio.</p>

            <!-- Formulario agregar/editar servicio -->
            <div class="border rounded p-3 bg-light">
                <div class="cms-section-label mb-2">Agregar / editar servicio</div>
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small mb-1">Ícono</label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="icon-preview-live bi bi-gear" id="icon-preview-live"></span>
                            <select id="new-svc-icon" class="form-select form-select-sm" style="max-width:220px">
                                <option value="bi-lightning-charge">⚡ Energía eléctrica</option>
                                <option value="bi-droplet">💧 Agua potable</option>
                                <option value="bi-fire">🔥 Gas natural</option>
                                <option value="bi-signpost-split">🛣 Accesos viales</option>
                                <option value="bi-shield-check">🔒 Seguridad</option>
                                <option value="bi-wifi">📶 Conectividad</option>
                                <option value="bi-truck">🚛 Transporte / logística</option>
                                <option value="bi-buildings">🏭 Instalaciones</option>
                                <option value="bi-trash3">♻ Tratamiento de residuos</option>
                                <option value="bi-tools">🔧 Talleres / mantenimiento</option>
                                <option value="bi-camera-video">📷 Videovigilancia</option>
                                <option value="bi-thermometer-half">🌡 Temperatura / clima</option>
                                <option value="bi-hospital">🏥 Salud y seguridad</option>
                                <option value="bi-bank">🏦 Servicios financieros</option>
                                <option value="bi-gear">⚙ General</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Título del servicio</label>
                        <input type="text" id="new-svc-titulo" class="form-control form-control-sm"
                               placeholder="Ej: Energía Eléctrica" maxlength="100">
                    </div>
                    <div class="col">
                        <label class="form-label small mb-1">Descripción breve</label>
                        <input type="text" id="new-svc-desc" class="form-control form-control-sm"
                               placeholder="Ej: Red de baja y media tensión..." maxlength="300">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary btn-sm" onclick="agregarServicio()">
                            <i class="bi bi-plus-lg me-1"></i>Agregar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
        <a href="<?= PUBLIC_URL ?>/el-parque.php" target="_blank" class="btn btn-outline-secondary">Ver página El Parque</a>
    </div>
</form>

<?php /* ══════════════════ TAB: CONTACTO Y REDES ══════════════════ */ elseif ($tab === 'contacto'): ?>

<form method="POST" id="form-contacto">
    <?= csrf_field() ?>
    <input type="hidden" name="tab_section" value="contacto">

    <!-- Info del footer -->
    <div class="card mb-3">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-layout-sidebar-reverse me-2 text-secondary"></i>Información del pie de página (footer)</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">Aparece en el footer de todas las páginas del sitio</div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Descripción breve del parque</label>
                    <input type="text" name="footer_descripcion" class="form-control"
                           value="<?= e($footer_desc) ?>" maxlength="200"
                           placeholder="Impulsando el desarrollo industrial de la provincia.">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email institucional</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="sitio_email" class="form-control"
                               value="<?= e($sitio_email) ?>" placeholder="contacto@parqueindustrial.gob.ar">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="sitio_telefono" class="form-control"
                               value="<?= e($sitio_telefono) ?>" placeholder="(0383) 4123456">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dirección</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" name="sitio_direccion" class="form-control"
                               value="<?= e($sitio_direccion) ?>" placeholder="San Fernando del Valle de Catamarca">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Redes sociales -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <span class="fw-semibold"><i class="bi bi-share me-2 text-info"></i>Redes sociales</span>
        </div>
        <div class="card-body">
            <div class="cms-section-label">URLs completas de los perfiles institucionales</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Facebook</label>
                    <div class="social-row">
                        <i class="bi bi-facebook si text-primary"></i>
                        <input type="url" name="redes_facebook" class="form-control"
                               value="<?= e($redes_facebook) ?>"
                               placeholder="https://facebook.com/parquecatamarca">
                    </div>
                    <?php if ($redes_facebook): ?>
                    <div class="mt-1"><a href="<?= e($redes_facebook) ?>" target="_blank" class="small text-muted">Ver perfil <i class="bi bi-box-arrow-up-right"></i></a></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Instagram</label>
                    <div class="social-row">
                        <i class="bi bi-instagram si" style="color:#E1306C"></i>
                        <input type="url" name="redes_instagram" class="form-control"
                               value="<?= e($redes_instagram) ?>"
                               placeholder="https://instagram.com/parquecatamarca">
                    </div>
                    <?php if ($redes_instagram): ?>
                    <div class="mt-1"><a href="<?= e($redes_instagram) ?>" target="_blank" class="small text-muted">Ver perfil <i class="bi bi-box-arrow-up-right"></i></a></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Twitter / X</label>
                    <div class="social-row">
                        <i class="bi bi-twitter-x si text-dark"></i>
                        <input type="url" name="redes_twitter" class="form-control"
                               value="<?= e($redes_twitter) ?>"
                               placeholder="https://x.com/parquecatamarca">
                    </div>
                    <?php if ($redes_twitter): ?>
                    <div class="mt-1"><a href="<?= e($redes_twitter) ?>" target="_blank" class="small text-muted">Ver perfil <i class="bi bi-box-arrow-up-right"></i></a></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$redes_facebook && !$redes_instagram && !$redes_twitter): ?>
            <div class="alert alert-warning mt-3 mb-0 py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Las redes sociales del footer actualmente muestran <strong>enlaces sin destino (#)</strong>. Completá las URLs para activarlas.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
        <a href="<?= PUBLIC_URL ?>/" target="_blank" class="btn btn-outline-secondary">Ver sitio</a>
    </div>
</form>

<?php endif; ?>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function () {
    'use strict';

    /* ── Quill – solo en tab el_parque ──────────────────────────────────────── */
    <?php if ($tab === 'el_parque'): ?>
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'clean']
            ]
        },
        placeholder: 'Escribí el texto descriptivo del parque aquí...'
    });

    // Cargar contenido existente
    var rawSource = document.getElementById('quill-raw-source').textContent;
    if (rawSource && rawSource.trim() !== '') {
        // Detectar si es HTML o texto plano
        if (/<[a-z][\s\S]*>/i.test(rawSource)) {
            quill.clipboard.dangerouslyPasteHTML(rawSource);
        } else {
            quill.setText(rawSource);
        }
    }

    // Copiar HTML al hidden field antes de enviar
    document.getElementById('form-el-parque').addEventListener('submit', function () {
        document.getElementById('nosotros_texto_hidden').value = quill.root.innerHTML;
    });

    /* ── Editor de servicios ─────────────────────────────────────────────────── */
    var servicios = <?= json_encode($servicios, JSON_UNESCAPED_UNICODE) ?>;

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderServicios() {
        var container = document.getElementById('servicios-lista');
        if (!container) return;
        if (servicios.length === 0) {
            container.innerHTML = '<p class="text-muted small">No hay servicios cargados. Agregá el primero con el formulario de abajo.</p>';
        } else {
            container.innerHTML = servicios.map(function (s, i) {
                return '<div class="servicio-item border rounded p-3 mb-2 d-flex align-items-center gap-3">'
                    + '<i class="bi ' + esc(s.icon) + ' fs-4 text-primary" style="min-width:2rem;text-align:center"></i>'
                    + '<div class="flex-grow-1">'
                    + '<div class="fw-semibold">' + esc(s.titulo) + '</div>'
                    + '<div class="small text-muted">' + esc(s.desc) + '</div>'
                    + '</div>'
                    + '<div class="d-flex gap-1 flex-shrink-0">'
                    + '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moverServicio(' + i + ',-1)" ' + (i === 0 ? 'disabled' : '') + ' title="Subir"><i class="bi bi-arrow-up"></i></button>'
                    + '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moverServicio(' + i + ',1)" ' + (i === servicios.length - 1 ? 'disabled' : '') + ' title="Bajar"><i class="bi bi-arrow-down"></i></button>'
                    + '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editarServicio(' + i + ')" title="Editar"><i class="bi bi-pencil"></i></button>'
                    + '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="eliminarServicio(' + i + ')" title="Eliminar"><i class="bi bi-trash3"></i></button>'
                    + '</div></div>';
            }).join('');
        }
        document.getElementById('nosotros_servicios_hidden').value = JSON.stringify(servicios);
    }

    window.agregarServicio = function () {
        var icon   = document.getElementById('new-svc-icon').value.trim();
        var titulo = document.getElementById('new-svc-titulo').value.trim();
        var desc   = document.getElementById('new-svc-desc').value.trim();
        if (!titulo) {
            document.getElementById('new-svc-titulo').focus();
            return;
        }
        servicios.push({ icon: icon || 'bi-gear', titulo: titulo, desc: desc });
        document.getElementById('new-svc-titulo').value = '';
        document.getElementById('new-svc-desc').value = '';
        renderServicios();
    };

    window.editarServicio = function (i) {
        var s = servicios[i];
        document.getElementById('new-svc-icon').value   = s.icon;
        document.getElementById('new-svc-titulo').value = s.titulo;
        document.getElementById('new-svc-desc').value   = s.desc;
        // Actualizar preview del ícono
        actualizarIconPreview(s.icon);
        servicios.splice(i, 1);
        renderServicios();
        document.getElementById('new-svc-titulo').focus();
    };

    window.eliminarServicio = function (i) {
        if (!confirm('¿Eliminar este servicio?')) return;
        servicios.splice(i, 1);
        renderServicios();
    };

    window.moverServicio = function (i, dir) {
        var j = i + dir;
        if (j < 0 || j >= servicios.length) return;
        var tmp = servicios[i]; servicios[i] = servicios[j]; servicios[j] = tmp;
        renderServicios();
    };

    function actualizarIconPreview(iconClass) {
        var el = document.getElementById('icon-preview-live');
        if (!el) return;
        el.className = 'icon-preview-live bi ' + iconClass;
    }

    var iconSel = document.getElementById('new-svc-icon');
    if (iconSel) {
        iconSel.addEventListener('change', function () { actualizarIconPreview(this.value); });
        // Inicializar preview
        actualizarIconPreview(iconSel.value);
    }

    // Enter en el campo desc agrega el servicio
    var descInput = document.getElementById('new-svc-desc');
    if (descInput) {
        descInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); agregarServicio(); }
        });
    }

    renderServicios();
    <?php endif; ?>

    /* ── Validar redes sociales – advertir si no es URL ─────────────────────── */
    <?php if ($tab === 'contacto'): ?>
    var urlInputs = document.querySelectorAll('input[type="url"]');
    urlInputs.forEach(function (inp) {
        inp.addEventListener('blur', function () {
            var val = this.value.trim();
            if (val && !val.startsWith('http')) {
                this.value = 'https://' + val;
            }
        });
    });
    <?php endif; ?>

}());
</script>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
