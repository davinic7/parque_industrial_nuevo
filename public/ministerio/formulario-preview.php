<?php
/**
 * Vista previa del formulario — renderizado con SurveyJS (read-only)
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$db      = getDB();
$form_id = (int)($_GET['id'] ?? 0);

if ($form_id <= 0) { header('Location: formularios-dinamicos.php'); exit; }

$stmt = $db->prepare("SELECT * FROM formularios_dinamicos WHERE id = ?");
$stmt->execute([$form_id]);
$formulario = $stmt->fetch();
if (!$formulario) { header('Location: formularios-dinamicos.php'); exit; }

$survey_json = $formulario['survey_json'] ?? '{}';
if (empty($survey_json) || $survey_json === 'null') {
    $survey_json = '{"pages":[{"name":"page1","elements":[]}]}';
}

$json_for_js = json_encode($survey_json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$estados_badge = ['borrador' => 'secondary', 'publicado' => 'success', 'archivado' => 'dark'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa — <?= e($formulario['titulo']) ?></title>
    <link href="<?= PUBLIC_URL ?>/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="<?= PUBLIC_URL ?>/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <!-- sin dependencias externas -->
    <style>
        body { background: #f0f2f5; }
        .preview-shell { max-width: 820px; margin: 0 auto; padding: 1.5rem 1rem 4rem; }
        .toolbar { background: #1e293b; border-radius: .5rem; padding: .6rem 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .form-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
        .form-header { background: linear-gradient(135deg, #1a3d6b 0%, #2563a8 100%); color: #fff; padding: 1.6rem 2rem; }
        .form-header h1 { font-size: 1.3rem; font-weight: 700; margin: 0; }
        .form-header .desc { font-size: .88rem; opacity: .82; margin-top: .35rem; }
        .survey-wrap { padding: 1.5rem 2rem 2rem; }
        .readonly-notice { background: #fff3cd; border: 1px solid #ffc107; border-radius: .4rem; padding: .5rem .9rem; font-size: .8rem; margin-bottom: 1.2rem; }
        @media print {
            .toolbar { display: none !important; }
            body { background: #fff; }
            .form-card { box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="preview-shell">

    <div class="toolbar">
        <div class="d-flex align-items-center gap-2">
            <a href="formulario-gestion.php?id=<?= $form_id ?>&tab=respuestas"
               class="btn btn-sm btn-outline-light">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
            <span class="badge bg-<?= $estados_badge[$formulario['estado']] ?? 'secondary' ?> ms-1">
                <?= ucfirst($formulario['estado']) ?>
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="formulario-editar.php?id=<?= $form_id ?>" class="btn btn-sm btn-outline-light">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <a href="formulario-imprimir.php?id=<?= $form_id ?>&modo=vacio" target="_blank"
               class="btn btn-sm btn-outline-light">
                <i class="bi bi-printer me-1"></i>Imprimir en blanco
            </a>
        </div>
    </div>

    <div class="form-card">
        <div class="form-header">
            <div class="small text-white-50 mb-1">Parque Industrial de Catamarca — Vista previa</div>
            <h1><?= e($formulario['titulo']) ?></h1>
            <?php if (!empty($formulario['descripcion'])): ?>
                <p class="desc mb-0"><?= e($formulario['descripcion']) ?></p>
            <?php endif; ?>
        </div>

        <div class="survey-wrap">
            <div class="readonly-notice">
                <i class="bi bi-eye me-1"></i>
                <strong>Vista previa:</strong> este formulario no se puede enviar desde aquí.
                Las empresas lo completan desde su panel.
            </div>
            <div id="surveyContainer"></div>
        </div>
    </div>

</div>

<script>/* sin JS externo — renderizado server-side */</script>
</body>
</html>
