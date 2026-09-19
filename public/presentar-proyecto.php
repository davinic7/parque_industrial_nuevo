<?php
/**
 * Formulario público: Presentar proyecto al ministerio
 * Requisitos según documento "Requisitos para la adjudicación de predios"
 */
require_once __DIR__ . '/../config/config.php';

$page_title = 'Presentar proyecto al ministerio';
$mensaje = '';
$error = '';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Token de seguridad inválido. Recargue la página.';
    } else {
        // Datos del solicitante
        $tipo_persona     = in_array($_POST['tipo_persona'] ?? '', ['fisica', 'juridica']) ? $_POST['tipo_persona'] : null;
        $contacto         = trim($_POST['contacto']         ?? '');
        $nombre_empresa   = trim($_POST['nombre_empresa']   ?? '');
        $dni_titulares    = trim($_POST['dni_titulares']    ?? '');
        $cuit_cuil        = trim($_POST['cuit_cuil']        ?? '');
        $email            = trim($_POST['email']            ?? '');
        $telefono         = trim($_POST['telefono']         ?? '');

        // Actividad
        $rubro            = trim($_POST['rubro']            ?? '');
        $rubro_actividad  = trim($_POST['rubro_actividad']  ?? '');
        $resumen_proyecto = trim($_POST['resumen_proyecto'] ?? '');

        // Documentación (checkboxes declarativos)
        $tiene_contrato_constitutivo = isset($_POST['tiene_contrato_constitutivo']) ? 1 : 0;
        $tiene_acta_autoridades      = isset($_POST['tiene_acta_autoridades'])      ? 1 : 0;
        $tiene_inscripcion_registro  = isset($_POST['tiene_inscripcion_registro'])  ? 1 : 0;
        $tiene_inscripcion_arca      = isset($_POST['tiene_inscripcion_arca'])      ? 1 : 0;
        $tiene_inscripcion_arcat     = isset($_POST['tiene_inscripcion_arcat'])     ? 1 : 0;
        $otras_inscripciones         = trim($_POST['otras_inscripciones']           ?? '');
        $tiene_estudio_ambiental     = isset($_POST['tiene_estudio_ambiental'])     ? 1 : 0;
        $tiene_croquis_obra          = isset($_POST['tiene_croquis_obra'])          ? 1 : 0;
        $tiene_cronograma_obra       = isset($_POST['tiene_cronograma_obra'])       ? 1 : 0;

        $solicita_cita = isset($_POST['solicita_cita']) ? 1 : 0;

        // Validaciones
        if (!verify_recaptcha()) {
            $error = 'Debe completar la verificación de seguridad (reCAPTCHA).';
        } elseif (empty($contacto) || empty($email)) {
            $error = 'Complete los campos obligatorios: nombre del titular y email.';
        } elseif (!is_valid_email($email)) {
            $error = 'El email ingresado no es válido.';
        } elseif (empty($resumen_proyecto)) {
            $error = 'Debe describir brevemente su proyecto.';
        } else {
            // ── Adjuntos (máx 5) ────────────────────────────────────────────
            $archivos = [null, null, null, null, null];
            $doc_dir  = UPLOADS_PATH . '/documento-proyectos';
            if (!is_dir($doc_dir)) mkdir($doc_dir, 0755, true);

            $tipos_permitidos = [
                'application/pdf', 'image/jpeg', 'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            foreach (range(1, 5) as $n) {
                $key = "archivo_$n";
                if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$key];
                    if ($file['size'] > 10 * 1024 * 1024) {
                        $error = "El archivo $n supera el tamaño máximo de 10 MB.";
                        break;
                    }
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime  = $finfo->file($file['tmp_name']);
                    if (!in_array($mime, $tipos_permitidos)) {
                        $error = "El archivo $n no es un tipo permitido (PDF, JPG, PNG, DOC, DOCX).";
                        break;
                    }
                    // Extensión derivada del MIME verificado, nunca del nombre del cliente (evita subir "x.php")
                    $ext     = safe_extension_for_mime($mime);
                    if ($ext === null) {
                        $error = "El archivo $n no es un tipo permitido (PDF, JPG, PNG, DOC, DOCX).";
                        break;
                    }
                    $guardado = store_upload($file, 'documento-proyectos', $tipos_permitidos, $mime);
                    if ($guardado['success']) {
                        // URL absoluta (Cloudinary) o ruta relativa a uploads/ (local)
                        $archivos[$n - 1] = preg_match('#^https?://#i', $guardado['filename'])
                            ? $guardado['filename']
                            : 'documento-proyectos/' . $guardado['filename'];
                    } else {
                        $error = "No se pudo guardar el archivo $n. Intente nuevamente.";
                        break;
                    }
                }
            }

            if (!$error) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO solicitudes_proyecto
                            (contacto, email, nombre_empresa, tipo_persona, dni_titulares, cuit_cuil,
                             telefono, rubro, rubro_actividad, resumen_proyecto,
                             tiene_contrato_constitutivo, tiene_acta_autoridades,
                             tiene_inscripcion_registro, tiene_inscripcion_arca, tiene_inscripcion_arcat,
                             otras_inscripciones, tiene_estudio_ambiental, tiene_croquis_obra, tiene_cronograma_obra,
                             solicita_cita, archivo_1, archivo_2, archivo_3, archivo_4, archivo_5)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $contacto, $email, $nombre_empresa, $tipo_persona, $dni_titulares, $cuit_cuil,
                        $telefono, $rubro, $rubro_actividad, $resumen_proyecto,
                        $tiene_contrato_constitutivo, $tiene_acta_autoridades,
                        $tiene_inscripcion_registro, $tiene_inscripcion_arca, $tiene_inscripcion_arcat,
                        $otras_inscripciones, $tiene_estudio_ambiental, $tiene_croquis_obra, $tiene_cronograma_obra,
                        $solicita_cita, $archivos[0], $archivos[1], $archivos[2], $archivos[3], $archivos[4]
                    ]);
                    $mensaje = 'Su solicitud fue enviada correctamente. El Ministerio se pondrá en contacto a la brevedad.';
                    $_POST = [];
                } catch (Exception $e) {
                    error_log("presentar-proyecto: " . $e->getMessage());
                    $error = 'No se pudo enviar la solicitud. Intente nuevamente.';
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="text-center mb-2">Presentar proyecto al ministerio</h1>
                <p class="text-center text-muted mb-4">
                    Complete el formulario con los datos requeridos para solicitar la adjudicación de un predio
                    en el Parque Industrial El Pantanillo. Puede adjuntar hasta 5 archivos (PDF, imagen o documento Word, máx. 10 MB cada uno).
                </p>

                <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i><?= e($mensaje) ?>
                </div>
                <p class="text-center mt-3">
                    <a href="<?= PUBLIC_URL ?>/" class="btn btn-primary">Volver al inicio</a>
                </p>
                <?php else: ?>
                <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>

                            <!-- ═══ SECCIÓN 1: Datos del solicitante ═══ -->
                            <h6 class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.78rem;letter-spacing:.06em;">
                                <i class="bi bi-person-vcard me-1"></i>Datos del solicitante
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Tipo de persona</label>
                                    <select name="tipo_persona" class="form-select">
                                        <option value="">— Seleccionar —</option>
                                        <option value="fisica" <?= ($_POST['tipo_persona'] ?? '') === 'fisica' ? 'selected' : '' ?>>Persona física</option>
                                        <option value="juridica" <?= ($_POST['tipo_persona'] ?? '') === 'juridica' ? 'selected' : '' ?>>Persona jurídica (Sociedad)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre y apellido del titular *</label>
                                    <input type="text" name="contacto" class="form-control" required
                                           placeholder="Ej: Juan Pérez"
                                           value="<?= e($_POST['contacto'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DNI del/los titular/es</label>
                                    <input type="text" name="dni_titulares" class="form-control"
                                           placeholder="Ej: 30.123.456 (si hay varios, sepárelos con coma)"
                                           value="<?= e($_POST['dni_titulares'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre de la empresa / emprendimiento</label>
                                    <input type="text" name="nombre_empresa" class="form-control"
                                           placeholder="Ej: Metalúrgica del Norte S.R.L."
                                           value="<?= e($_POST['nombre_empresa'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CUIT / CUIL</label>
                                    <input type="text" name="cuit_cuil" class="form-control"
                                           placeholder="Ej: 20-30123456-7"
                                           value="<?= e($_POST['cuit_cuil'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Correo electrónico *</label>
                                    <input type="email" name="email" class="form-control" required
                                           placeholder="contacto@empresa.com"
                                           value="<?= e($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="form-control"
                                           placeholder="(0383) 4-XXXXXX"
                                           value="<?= e($_POST['telefono'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- ═══ SECCIÓN 2: Actividad / Rubro ═══ -->
                            <h6 class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.78rem;letter-spacing:.06em;">
                                <i class="bi bi-gear me-1"></i>Actividad y proyecto
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Rubro principal</label>
                                    <select name="rubro" class="form-select">
                                        <option value="">— Seleccionar —</option>
                                        <?php
                                        $rubros = ['Metalúrgica', 'Alimenticia', 'Textil', 'Química', 'Maderera',
                                                   'Plástica', 'Tecnología', 'Logística', 'Construcción',
                                                   'Farmacéutica', 'Agroindustria', 'Otro'];
                                        foreach ($rubros as $r):
                                        ?>
                                        <option value="<?= e($r) ?>" <?= ($_POST['rubro'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Descripción de la actividad a desarrollar</label>
                                    <input type="text" name="rubro_actividad" class="form-control"
                                           placeholder="Ej: Fabricación de estructuras metálicas"
                                           value="<?= e($_POST['rubro_actividad'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Resumen del proyecto *</label>
                                    <textarea name="resumen_proyecto" class="form-control" rows="5" required
                                              placeholder="Describa su proyecto: actividad principal, capacidad productiva estimada, cantidad de empleados, necesidades de infraestructura, superficie requerida, etc."><?= e($_POST['resumen_proyecto'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- ═══ SECCIÓN 3: Documentación requerida ═══ -->
                            <h6 class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.78rem;letter-spacing:.06em;">
                                <i class="bi bi-folder-check me-1"></i>Documentación requerida
                            </h6>
                            <div class="alert alert-info small py-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Marque la documentación que ya tiene disponible. La documentación original deberá presentarse
                                ante el organismo para su certificación. Puede adjuntar copias digitales más abajo.
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_contrato_constitutivo" class="form-check-input" id="chk_contrato"
                                               value="1" <?= isset($_POST['tiene_contrato_constitutivo']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_contrato">Contrato constitutivo de la sociedad</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_acta_autoridades" class="form-check-input" id="chk_acta"
                                               value="1" <?= isset($_POST['tiene_acta_autoridades']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_acta">Acta de designación de autoridades</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_inscripcion_registro" class="form-check-input" id="chk_registro"
                                               value="1" <?= isset($_POST['tiene_inscripcion_registro']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_registro">Inscripción en Registro Público de Comercio</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_inscripcion_arca" class="form-check-input" id="chk_arca"
                                               value="1" <?= isset($_POST['tiene_inscripcion_arca']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_arca">Inscripción en ARCA</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_inscripcion_arcat" class="form-check-input" id="chk_arcat"
                                               value="1" <?= isset($_POST['tiene_inscripcion_arcat']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_arcat">Inscripción en ARCAT</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_estudio_ambiental" class="form-check-input" id="chk_ambiental"
                                               value="1" <?= isset($_POST['tiene_estudio_ambiental']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_ambiental">Estudio de Impacto Ambiental</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_croquis_obra" class="form-check-input" id="chk_croquis"
                                               value="1" <?= isset($_POST['tiene_croquis_obra']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_croquis">Croquis de la obra (firmado por profesional)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="tiene_cronograma_obra" class="form-check-input" id="chk_cronograma"
                                               value="1" <?= isset($_POST['tiene_cronograma_obra']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="chk_cronograma">Cronograma de obra (firmado por profesional)</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label mt-2">Otras inscripciones según la actividad
                                        <span class="text-muted small">(Bromatología, Farmacéuticas, etc.)</span>
                                    </label>
                                    <input type="text" name="otras_inscripciones" class="form-control"
                                           placeholder="Ej: Habilitación bromatológica municipal, Registro ANMAT"
                                           value="<?= e($_POST['otras_inscripciones'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- ═══ SECCIÓN 4: Archivos adjuntos ═══ -->
                            <h6 class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.78rem;letter-spacing:.06em;">
                                <i class="bi bi-paperclip me-1"></i>Archivos adjuntos
                            </h6>
                            <p class="text-muted small mb-3">
                                Puede adjuntar copias digitales de la documentación (contrato constitutivo, DNI, croquis, cronograma, estudio ambiental, etc.).
                                Formatos: PDF, JPG, PNG, DOC, DOCX — Máx. 10 MB cada uno.
                            </p>
                            <div class="row g-3 mb-4">
                                <?php for ($n = 1; $n <= 5; $n++): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Archivo <?= $n ?>
                                        <?php if ($n > 1): ?><span class="text-muted small">(opcional)</span><?php endif; ?>
                                    </label>
                                    <input type="file" name="archivo_<?= $n ?>" class="form-control"
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </div>
                                <?php endfor; ?>
                            </div>

                            <!-- ═══ Solicitud de reunión ═══ -->
                            <div class="form-check mb-4">
                                <input type="checkbox" name="solicita_cita" class="form-check-input" id="solicita_cita"
                                       value="1" <?= isset($_POST['solicita_cita']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="solicita_cita">
                                    <i class="bi bi-calendar-check me-1 text-primary"></i>
                                    Solicitar cita presencial / reunión con el Ministerio
                                </label>
                            </div>

                            <?= recaptcha_field() ?>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send me-2"></i>Enviar solicitud
                                </button>
                                <a href="<?= PUBLIC_URL ?>/" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
