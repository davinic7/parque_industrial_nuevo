<?php
/**
 * Perfil de Empresa - Parque Industrial de Catamarca
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['empresa'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Mi Perfil';
$mensaje = '';
$error = '';
$field_errors = [];
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$empresa_id) {
    set_flash('error', 'No se encontró la empresa asociada a su cuenta');
    redirect('dashboard.php');
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Token de seguridad inválido. Recargue la página.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM empresas WHERE id = ?");
            $stmt->execute([$empresa_id]);
            $datos_anteriores = $stmt->fetch();

            if (!$datos_anteriores) {
                set_flash('error', 'Empresa no encontrada');
                redirect('dashboard.php');
            }

            if (array_key_exists('nombre', $_POST)) {
                $nombre        = trim($_POST['nombre'] ?? '');
                $razon_social  = trim($_POST['razon_social'] ?? '');
                $cuit          = trim($_POST['cuit'] ?? '');
                $cuit_digits   = cuit_digits_only($cuit);
                if ($cuit !== '' && $cuit_digits === '') $cuit = '';
                $rubro         = trim($_POST['rubro'] ?? '') ?: null;
                $descripcion   = trim($_POST['descripcion'] ?? '');
                $direccion     = trim($_POST['direccion'] ?? '');
                $latitud       = !empty($_POST['latitud'])  ? (float)$_POST['latitud']  : null;
                $longitud      = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : null;
                $dentro_parque = !empty($_POST['dentro_parque']) && $_POST['dentro_parque'] === '1';
                $lote_declarado_input = trim($_POST['lote_declarado'] ?? '');
                $current_lote_estado = $datos_anteriores['lote_solicitud_estado'] ?? 'sin_solicitud';
                if ($current_lote_estado === 'asignado') {
                    $lote_guardar       = $datos_anteriores['lote_declarado'];
                    $lote_estado_guardar = 'asignado';
                } elseif ($dentro_parque && $lote_declarado_input !== '') {
                    $lote_guardar       = $lote_declarado_input;
                    $lote_estado_guardar = 'pendiente';
                } else {
                    $lote_guardar       = null;
                    $lote_estado_guardar = 'sin_solicitud';
                }
                $telefono      = trim($_POST['telefono'] ?? '');
                $email_contacto = trim($_POST['email_contacto'] ?? '');
                $contacto_nombre = trim($_POST['contacto_nombre'] ?? '');
                $sitio_web     = trim($_POST['sitio_web'] ?? '');
                $facebook      = trim($_POST['facebook'] ?? '');
                $instagram     = trim($_POST['instagram'] ?? '');

                if ($nombre === '') $field_errors['nombre'] = 'El nombre comercial es obligatorio';
                if ($rubro === null || $rubro === '') $field_errors['rubro'] = 'Seleccioná un rubro';
                if ($cuit_digits !== '' && !is_valid_cuit($cuit_digits)) {
                    $field_errors['cuit'] = 'CUIT no válido: deben ser los 11 dígitos de AFIP (el último es verificador).';
                }
                if ($email_contacto !== '' && !is_valid_email($email_contacto)) {
                    $field_errors['email_contacto'] = 'El email de contacto no es válido';
                }

                // Procesar logo si no hay errores de validación previos
                if (empty($field_errors)) {
                    $logo_filename = $datos_anteriores['logo'];
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                        $upload = upload_image_storage($_FILES['logo'], 'logos', ALLOWED_IMAGE_TYPES);
                        if ($upload['success']) {
                            $logo_filename = $upload['filename'];
                        } else {
                            $field_errors['logo'] = $upload['error'];
                        }
                    }
                } else {
                    // Hay errores de validación: el logo NO se guarda.
                    // Avisamos para que el usuario sepa que debe seleccionarlo de nuevo.
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                        $field_errors['logo_warning'] = 'El logo seleccionado no se guardó porque había errores en el formulario. Su logo actual permanece intacto. Vuelva a seleccionar el archivo cuando corrija los datos.';
                    }
                }

                // Guardar solo si no hay ningún error (ni validación ni logo)
                if (empty($field_errors)) {
                    $cuit_guardar = ($cuit_digits !== '') ? format_cuit_argentina($cuit_digits) : '';
                    $db->prepare("
                        UPDATE empresas SET
                            nombre = ?, razon_social = ?, cuit = ?, rubro = ?,
                            descripcion = ?, direccion = ?,
                            latitud = ?, longitud = ?,
                            telefono = ?, email_contacto = ?, contacto_nombre = ?,
                            sitio_web = ?, facebook = ?, instagram = ?, logo = ?,
                            lote_declarado = ?, lote_solicitud_estado = ?
                        WHERE id = ?
                    ")->execute([
                        $nombre, $razon_social, $cuit_guardar, $rubro,
                        $descripcion, $direccion,
                        $latitud, $longitud,
                        $telefono, $email_contacto, $contacto_nombre,
                        $sitio_web, $facebook, $instagram, $logo_filename,
                        $lote_guardar, $lote_estado_guardar,
                        $empresa_id
                    ]);

                    $_SESSION['empresa_nombre'] = $nombre;
                    log_activity('perfil_actualizado', 'empresas', $empresa_id, $datos_anteriores);
                    $mensaje = 'Perfil actualizado correctamente';
                }
            }
        } catch (Exception $e) {
            error_log("Error al actualizar perfil empresa_id=$empresa_id: " . $e->getMessage());
            $error = 'Error al guardar los cambios. Intente nuevamente.';
        }
    }
}

$stmt = $db->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch();

if (!$empresa) {
    set_flash('error', 'Empresa no encontrada');
    redirect('dashboard.php');
}

// Info del lote ya asignado por el ministerio (si existe)
$lote_asignado_info = null;
if (($empresa['lote_solicitud_estado'] ?? '') === 'asignado') {
    try {
        $st = $db->prepare("SELECT numero_lote, sector FROM lotes WHERE empresa_id = ? LIMIT 1");
        $st->execute([$empresa_id]);
        $lote_asignado_info = $st->fetch() ?: null;
    } catch (Exception $e) {}
}

$csrf_msg = 'Token de seguridad inválido. Recargue la página.';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($field_errors) || ($error !== '' && $error !== $csrf_msg))) {
    foreach (['nombre', 'razon_social', 'cuit', 'rubro', 'descripcion', 'direccion',
              'telefono', 'email_contacto', 'contacto_nombre', 'sitio_web', 'facebook', 'instagram'] as $c) {
        if (array_key_exists($c, $_POST)) {
            $empresa[$c] = is_string($_POST[$c]) ? trim($_POST[$c]) : $_POST[$c];
        }
    }
    if (array_key_exists('latitud', $_POST))  $empresa['latitud']  = $_POST['latitud']  !== '' ? $_POST['latitud']  : null;
    if (array_key_exists('longitud', $_POST)) $empresa['longitud'] = $_POST['longitud'] !== '' ? $_POST['longitud'] : null;
}

$stmt = $db->query("SELECT nombre FROM rubros WHERE activo = 1 ORDER BY orden, nombre");
$rubros = $stmt->fetchAll(PDO::FETCH_COLUMN);

$galeria_imagenes = [];
try {
    $db->query("SELECT 1 FROM empresa_imagenes LIMIT 1");
    $stmt = $db->prepare("SELECT id, url AS imagen FROM empresa_imagenes WHERE empresa_id = ? ORDER BY orden ASC, id ASC");
    $stmt->execute([$empresa_id]);
    $galeria_imagenes = $stmt->fetchAll();
} catch (Exception $e) {
    $galeria_imagenes = [];
}

$empresa_nav = '';
$extra_head = '<link rel="stylesheet" href="' . PUBLIC_URL . '/vendor/leaflet/leaflet.css">';
require_once BASEPATH . '/includes/empresa_layout_header.php';
?>

<style>
.gallery-thumb {
    width: 72px; height: 72px; object-fit: cover;
    border-radius: 6px; border: 2px solid #dee2e6;
    transition: border-color .15s;
}
.gallery-thumb:hover { border-color: #0d6efd; }
.gallery-item { position: relative; display: inline-block; }
.gallery-item .btn-del-img {
    position: absolute; top: -6px; right: -6px;
    width: 20px; height: 20px; padding: 0;
    line-height: 1; font-size: .65rem;
    border-radius: 50%;
}
.map-preview-box { height: 300px; border-radius: 8px; overflow: hidden; }
.upload-drop-zone {
    border: 2px dashed #dee2e6; border-radius: 8px;
    padding: 2rem 1rem; text-align: center; cursor: pointer;
    transition: border-color .2s, background .2s;
}
.upload-drop-zone:hover, .upload-drop-zone.dragover {
    border-color: #0d6efd; background: #f0f4ff;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">Editar Perfil de Empresa</h1>
    <a href="<?= PUBLIC_URL ?>/empresa.php?id=<?= $empresa['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-eye me-1"></i>Ver perfil público
    </a>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= e($mensaje) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate id="formPerfil">
    <?= csrf_field() ?>
    <div class="row g-4">
        <!-- Columna principal -->
        <div class="col-lg-8">

            <!-- Datos básicos -->
            <div class="card mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Datos de la Empresa</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre comercial *</label>
                            <input type="text" name="nombre" class="form-control<?= isset($field_errors['nombre']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($empresa['nombre']) ?>" required>
                            <?php if (isset($field_errors['nombre'])): ?>
                            <div class="invalid-feedback d-block"><?= e($field_errors['nombre']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Razón social</label>
                            <input type="text" name="razon_social" class="form-control" value="<?= e($empresa['razon_social'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CUIT</label>
                            <input type="text" name="cuit" id="inputCuit"
                                   class="form-control<?= isset($field_errors['cuit']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($empresa['cuit'] ?? '') ?>"
                                   placeholder="Ej. 20-12345678-6" inputmode="numeric" autocomplete="off" maxlength="13">
                            <?php if (isset($field_errors['cuit'])): ?>
                            <div class="invalid-feedback d-block"><?= e($field_errors['cuit']) ?></div>
                            <?php else: ?>
                            <small class="text-muted">11 dígitos AFIP. Guiones opcionales. Vacío si no aplica.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rubro *</label>
                            <select name="rubro" class="form-select<?= isset($field_errors['rubro']) ? ' is-invalid' : '' ?>" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($rubros as $r): ?>
                                <option value="<?= e($r) ?>" <?= ($empresa['rubro'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($field_errors['rubro'])): ?>
                            <div class="invalid-feedback d-block"><?= e($field_errors['rubro']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="4"
                                      placeholder="Describe tu empresa..."><?= e($empresa['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <div class="card mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Ubicación en el Parque</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Dirección exacta</label>
                        <input type="text" name="direccion" class="form-control"
                               value="<?= e($empresa['direccion'] ?? '') ?>"
                               placeholder="Ej. Calle Industrial 123, Parque Industrial">
                    </div>

                    <!-- Mapa picker -->
                    <label class="form-label">Ubicación en el mapa</label>
                    <p class="text-muted small mb-2">Hacé clic en el mapa para marcar tu posición, o ingresá las coordenadas manualmente. Podés arrastrar el marcador para ajustar.</p>
                    <div id="mapPicker" class="map-preview-box mb-3"></div>

                    <!-- Coordenadas visibles y editables (sincronizan con el mapa) -->
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Latitud</label>
                            <input type="number" step="any" name="latitud" id="latitud"
                                   class="form-control form-control-sm"
                                   placeholder="-28.533..."
                                   value="<?= e($empresa['latitud'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Longitud</label>
                            <input type="number" step="any" name="longitud" id="longitud"
                                   class="form-control form-control-sm"
                                   placeholder="-65.801..."
                                   value="<?= e($empresa['longitud'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Botones de compartir (visibles cuando hay coords) -->
                    <div id="coordsDisplay" class="<?= ($empresa['latitud'] && $empresa['longitud']) ? '' : 'd-none' ?> mb-1">
                        <div class="d-flex gap-2 flex-wrap">
                            <a id="btnGoogleMaps" href="#" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-map me-1"></i>Google Maps
                            </a>
                            <a id="btnWhatsApp" href="#" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-success">
                                <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp
                            </a>
                            <button type="button" id="btnCopiarCoords" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-regular fa-copy me-1"></i>Copiar enlace
                            </button>
                        </div>
                    </div>

                    <!-- Sección de lote: visible solo si coords están dentro del parque (JS la muestra) -->
                    <div id="sectionLote" class="mt-3 border-top pt-3 d-none">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-industry text-success fa-sm"></i>
                            <strong class="text-success">Tu ubicación está dentro del Parque Industrial</strong>
                        </div>

                        <!-- Estado: asignado por ministerio -->
                        <div id="loteAsignadoAlert" class="alert alert-success py-2 mb-0 d-none">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Tu lote fue asignado: <strong><?= e($lote_asignado_info['numero_lote'] ?? '') ?></strong>
                            <?php if (!empty($lote_asignado_info['sector'])): ?>
                            — Sector <?= e($lote_asignado_info['sector']) ?>
                            <?php endif; ?>
                            <span class="badge bg-success ms-1">Confirmado</span>
                        </div>

                        <!-- Estado: pendiente o sin_solicitud → mostrar input -->
                        <div id="loteInputWrap" class="d-none">
                            <?php if (($empresa['lote_solicitud_estado'] ?? 'sin_solicitud') === 'pendiente'): ?>
                            <div class="alert alert-warning py-2 px-3 small mb-2">
                                <i class="fa-solid fa-clock me-1"></i>
                                Solicitud "<strong><?= e($empresa['lote_declarado'] ?? '') ?></strong>" pendiente de confirmación por el ministerio. Podés actualizar el número abajo.
                            </div>
                            <?php else: ?>
                            <p class="text-muted small mb-1">Si conocés tu número de lote, ingresalo. El ministerio lo confirmará y asignará el polígono en el mapa.</p>
                            <?php endif; ?>
                            <input type="text" id="inputLoteDeclarado" name="lote_declarado"
                                   class="form-control form-control-sm"
                                   value="<?= e($empresa['lote_declarado'] ?? '') ?>"
                                   placeholder="Ej: A-3, L-12, Lote 7..." maxlength="50">
                            <small class="text-muted">Dejá vacío si no sabés tu número de lote aún.</small>
                        </div>
                    </div>
                    <input type="hidden" name="dentro_parque" id="dentroPque" value="0">
                </div>
            </div>

            <!-- Contacto -->
            <div class="card mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Información de Contacto</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" value="<?= e($empresa['telefono'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email de contacto</label>
                            <input type="email" name="email_contacto"
                                   class="form-control<?= isset($field_errors['email_contacto']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($empresa['email_contacto'] ?? '') ?>">
                            <?php if (isset($field_errors['email_contacto'])): ?>
                            <div class="invalid-feedback d-block"><?= e($field_errors['email_contacto']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Persona de contacto</label>
                            <input type="text" name="contacto_nombre" class="form-control" value="<?= e($empresa['contacto_nombre'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sitio web</label>
                            <input type="url" name="sitio_web" class="form-control"
                                   value="<?= e($empresa['sitio_web'] ?? '') ?>" placeholder="https://">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">

            <!-- Logo -->
            <div class="card mb-3">
                <div class="card-header bg-white"><h5 class="mb-0">Logo</h5></div>
                <div class="card-body text-center">
                    <?php
                    $logo_src = !empty($empresa['logo'])
                        ? uploads_resolve_url($empresa['logo'], 'logos')
                        : 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect fill="#e9ecef" width="120" height="120"/><text x="60" y="68" font-size="48" fill="#6c757d" text-anchor="middle">🏢</text></svg>');
                    ?>
                    <img id="logoPreview" src="<?= $logo_src ?>" alt="Logo"
                         class="img-fluid rounded d-block mx-auto mb-3" style="max-height: 130px; background: #f8f9fa;">
                    <?php if (!empty($empresa['logo'])): ?>
                    <small class="d-block text-muted mb-2"><i class="fa-solid fa-circle-check text-success me-1"></i>Logo actual guardado</small>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-1" onclick="document.getElementById('logoFileInput').click()">
                        <i class="bi bi-upload me-1"></i> Seleccionar logo
                    </button>
                    <input type="file" name="logo" id="logoFileInput" class="d-none" accept="image/*">
                    <span id="logoFileName" class="d-block small text-muted mb-1">Sin archivo seleccionado</span>
                    <?php if (isset($field_errors['logo'])): ?>
                    <div class="text-danger small text-start"><?= e($field_errors['logo']) ?></div>
                    <?php elseif (isset($field_errors['logo_warning'])): ?>
                    <div class="alert alert-warning small text-start mt-2 mb-0 py-2 px-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <?= e($field_errors['logo_warning']) ?>
                    </div>
                    <?php else: ?>
                    <?php $logo_max_mb = max(1, (int) ceil(MAX_FILE_SIZE / 1048576)); ?>
                    <small class="text-muted">JPG, PNG, GIF, WebP. Máx. <?= $logo_max_mb ?> MB.</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Galería -->
            <div class="card mb-3">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Galería</h5>
                    <span class="badge bg-secondary" id="galleryCount"><?= count($galeria_imagenes) ?></span>
                </div>
                <div class="card-body">
                    <!-- Miniaturas inline -->
                    <div class="d-flex flex-wrap gap-2 mb-3" id="galleryThumbsInline">
                        <?php foreach ($galeria_imagenes as $img): ?>
                        <div class="gallery-item" id="gitem-<?= $img['id'] ?>">
                            <img src="<?= e(uploads_resolve_url($img['imagen'], 'galeria_empresa')) ?>"
                                 class="gallery-thumb" alt="Foto">
                            <button type="button" class="btn btn-danger btn-del-img"
                                    data-id="<?= $img['id'] ?>" title="Eliminar">×</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalGaleria">
                        <i class="fa-solid fa-images me-2"></i>Agregar fotos
                    </button>
                </div>
            </div>

            <!-- Redes sociales -->
            <div class="card mb-3">
                <div class="card-header bg-white"><h5 class="mb-0">Redes Sociales</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="fa-brands fa-facebook text-primary me-1"></i>Facebook</label>
                        <input type="url" name="facebook" class="form-control" value="<?= e($empresa['facebook'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label"><i class="fa-brands fa-instagram text-danger me-1"></i>Instagram</label>
                        <input type="url" name="instagram" class="form-control" value="<?= e($empresa['instagram'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Guardar -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>

        </div>
    </div>
</form>

<!-- Modal advertencia: pin fuera del parque con lote pendiente -->
<div class="modal fade" id="modalLotePerdido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Solicitud de lote</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1 small">
                Tu ubicación está <strong>fuera del Parque Industrial</strong>. Si guardás, tu solicitud de lote
                "<strong id="lotePerdidoNumero"></strong>" se cancelará y tendrás que volver a declararla.
                <br><br>¿Querés guardar igual?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnConfirmarGuardar">Guardar igualmente</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Galería (AJAX) -->
<div class="modal fade" id="modalGaleria" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-images me-2"></i>Galería de imágenes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Estas imágenes se muestran en el carrusel de tu perfil público. Seleccioná un archivo para subirlo automáticamente.</p>

                <!-- Drop zone / file input -->
                <div class="upload-drop-zone mb-4" id="dropZone">
                    <i class="fa-solid fa-cloud-arrow-up fa-2x text-muted mb-2 d-block"></i>
                    <p class="mb-1 fw-semibold">Arrastrá imágenes aquí o hacé clic para seleccionar</p>
                    <small class="text-muted">JPG, PNG, GIF, WebP — se suben al instante</small>
                    <input type="file" id="galeriaFileInput" accept="image/*" multiple class="d-none">
                </div>

                <!-- Progress -->
                <div id="uploadProgress" class="d-none mb-3">
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
                    </div>
                    <small class="text-muted mt-1 d-block">Subiendo...</small>
                </div>

                <!-- Grid de imágenes en modal -->
                <div class="d-flex flex-wrap gap-3" id="galleryGrid">
                    <?php foreach ($galeria_imagenes as $img): ?>
                    <div class="gallery-item" id="mgitem-<?= $img['id'] ?>">
                        <img src="<?= e(uploads_resolve_url($img['imagen'], 'galeria_empresa')) ?>"
                             class="gallery-thumb" style="width:90px;height:90px;" alt="Foto">
                        <button type="button" class="btn btn-danger btn-del-img-modal"
                                data-id="<?= $img['id'] ?>" title="Eliminar">×</button>
                    </div>
                    <?php endforeach; ?>
                    <div id="galleryEmpty" class="<?= empty($galeria_imagenes) ? '' : 'd-none' ?> text-muted small py-2">
                        Sin fotos aún. Subí la primera arriba.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Listo</button>
            </div>
        </div>
    </div>
</div>

<?php
$pu      = htmlspecialchars(PUBLIC_URL, ENT_QUOTES, 'UTF-8');
$js_cfg  = json_encode([
    'csrfName'   => CSRF_TOKEN_NAME,
    'csrfVal'    => $_SESSION[CSRF_TOKEN_NAME] ?? '',
    'defLat'     => (float) MAP_DEFAULT_LAT,
    'defLng'     => (float) MAP_DEFAULT_LNG,
    'hasCoords'  => !empty($empresa['latitud']) && !empty($empresa['longitud']),
    'initLat'    => (float)($empresa['latitud'] ?? 0),
    'initLng'    => (float)($empresa['longitud'] ?? 0),
    'loteEstado' => $empresa['lote_solicitud_estado'] ?? 'sin_solicitud',
    'loteDeclarado' => $empresa['lote_declarado'] ?? '',
]);
$extra_scripts = '<script src="' . PUBLIC_URL . '/vendor/leaflet/leaflet.js"></script>'
    . '<script src="' . $pu . '/js/parque-leaflet.js"></script>'
    . '<script>const __CFG=' . $js_cfg . ';</script>'
    . '<script src="' . asset_url('js/empresa-perfil.js') . '"></script>';
require_once BASEPATH . '/includes/empresa_layout_footer.php';
