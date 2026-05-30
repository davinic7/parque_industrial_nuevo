<?php
/**
 * Formulario público: Presentar proyecto al ministerio
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
        $nombre_empresa   = trim($_POST['nombre_empresa']   ?? '');
        $contacto         = trim($_POST['contacto']         ?? '');
        $email            = trim($_POST['email']            ?? '');
        $telefono         = trim($_POST['telefono']         ?? '');
        $resumen_proyecto = trim($_POST['resumen_proyecto'] ?? '');
        $solicita_cita    = isset($_POST['solicita_cita'])  ? 1 : 0;

        if (empty($nombre_empresa) || empty($contacto) || empty($email)) {
            $error = 'Complete los campos obligatorios: empresa, persona de contacto y email.';
        } elseif (!is_valid_email($email)) {
            $error = 'El email ingresado no es válido.';
        } elseif (empty($resumen_proyecto)) {
            $error = 'Debe describir brevemente su proyecto.';
        } else {
            // ── Adjuntos (máx 2) ────────────────────────────────────────────
            $archivo_1 = null;
            $archivo_2 = null;
            $doc_dir   = UPLOADS_PATH . '/documento-proyectos';
            if (!is_dir($doc_dir)) mkdir($doc_dir, 0755, true);

            $tipos_permitidos = ['application/pdf','image/jpeg','image/png',
                                 'application/msword',
                                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

            foreach ([1, 2] as $n) {
                $key = "archivo_$n";
                if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$key];
                    if ($file['size'] > 10 * 1024 * 1024) {
                        $error = "El archivo $n supera el tamaño máximo de 10 MB."; break;
                    }
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime  = $finfo->file($file['tmp_name']);
                    if (!in_array($mime, $tipos_permitidos)) {
                        $error = "El archivo $n no es un tipo permitido (PDF, JPG, PNG, DOC, DOCX)."; break;
                    }
                    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $nombre   = uniqid("doc{$n}_") . '_' . time() . '.' . $ext;
                    $destino  = $doc_dir . '/' . $nombre;
                    if (move_uploaded_file($file['tmp_name'], $destino)) {
                        $$key = 'documento-proyectos/' . $nombre;
                    } else {
                        $error = "No se pudo guardar el archivo $n. Intente nuevamente."; break;
                    }
                }
            }

            if (!$error) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO solicitudes_proyecto
                            (nombre_empresa, contacto, email, telefono, resumen_proyecto, solicita_cita, archivo_1, archivo_2)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$nombre_empresa, $contacto, $email, $telefono,
                                    $resumen_proyecto, $solicita_cita, $archivo_1, $archivo_2]);
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
            <div class="col-lg-8">
                <h1 class="text-center mb-2">Presentar proyecto al ministerio</h1>
                <p class="text-center text-muted mb-4">Complete el formulario con los datos de su proyecto. Puede adjuntar hasta 2 archivos (PDF, imagen o documento Word, máx. 10 MB cada uno).</p>

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

                            <h6 class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.78rem;letter-spacing:.06em;">Datos del solicitante</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre y apellido del titular *</label>
                                    <input type="text" name="contacto" class="form-control" required
                                           placeholder="Ej: Juan Pérez"
                                           value="<?= e($_POST['contacto'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre de la empresa / emprendimiento *</label>
                                    <input type="text" name="nombre_empresa" class="form-control" required
                                           placeholder="Ej: Metalúrgica del Norte S.R.L."
                                           value="<?= e($_POST['nombre_empresa'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo electrónico *</label>
                                    <input type="email" name="email" class="form-control" required
                                           placeholder="contacto@empresa.com"
                                           value="<?= e($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="form-control"
                                           placeholder="(0383) 4-XXXXXX"
                                           value="<?= e($_POST['telefono'] ?? '') ?>">
                                </div>
                            </div>

                            <h6 class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.78rem;letter-spacing:.06em;">Proyecto</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Resumen del proyecto *</label>
                                    <textarea name="resumen_proyecto" class="form-control" rows="6" required
                                              placeholder="Describa su proyecto: rubro, actividad, capacidad productiva, cantidad de empleados estimados, necesidades de infraestructura, etc."><?= e($_POST['resumen_proyecto'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Documentación adjunta — Archivo 1
                                        <span class="text-muted small">(PDF, JPG, PNG, DOC · máx. 10 MB)</span>
                                    </label>
                                    <input type="file" name="archivo_1" class="form-control"
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Documentación adjunta — Archivo 2
                                        <span class="text-muted small">(opcional)</span>
                                    </label>
                                    <input type="file" name="archivo_2" class="form-control"
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input type="checkbox" name="solicita_cita" class="form-check-input" id="solicita_cita"
                                       value="1" <?= isset($_POST['solicita_cita']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="solicita_cita">
                                    <i class="bi bi-calendar-check me-1 text-primary"></i>
                                    Solicitar cita presencial con el Ministerio
                                </label>
                            </div>

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
