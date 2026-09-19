<?php
/**
 * Imprimir / Exportar PDF — Respuestas de Formulario Dinámico
 * Formato profesional con logo del Ministerio
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$db         = getDB();
$form_id    = (int)($_GET['id'] ?? 0);
$empresa_id = isset($_GET['empresa']) ? (int)$_GET['empresa'] : null;
$modo       = $_GET['modo'] ?? 'respuestas'; // 'respuestas' | 'vacio'

if ($form_id <= 0) { header('Location: formularios-dinamicos.php'); exit; }

$stmt = $db->prepare("SELECT * FROM formularios_dinamicos WHERE id = ?");
$stmt->execute([$form_id]);
$formulario = $stmt->fetch();
if (!$formulario) { header('Location: formularios-dinamicos.php'); exit; }

$stmt = $db->prepare("SELECT * FROM formulario_preguntas WHERE formulario_id = ? ORDER BY orden, id");
$stmt->execute([$form_id]);
$preguntas = $stmt->fetchAll(PDO::FETCH_UNIQUE);

$respuestas = [];
if ($modo !== 'vacio') {
    $where_r  = "r.formulario_id = ?";
    $params_r = [$form_id];
    if ($empresa_id) {
        $where_r  .= " AND r.empresa_id = ?";
        $params_r[] = $empresa_id;
    }
    $stmt = $db->prepare("
        SELECT r.*, e.nombre AS empresa_nombre, e.cuit, e.rubro, e.ubicacion
        FROM formulario_respuestas r
        INNER JOIN empresas e ON r.empresa_id = e.id
        WHERE $where_r AND r.estado = 'enviado'
        ORDER BY e.nombre ASC
    ");
    $stmt->execute($params_r);
    $respuestas = $stmt->fetchAll();
}

$logo_path  = PUBLIC_URL . '/img/logo-ministerio.png';
$logo2_path = PUBLIC_URL . '/img/logo-gobierno.png';
$fecha_exp  = date('d/m/Y \a \l\a\s H:i');
$total_resp = count($respuestas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($formulario['titulo']) ?> — Exportar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ── Base ── */
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; background: #e5e7eb; color: #1e293b; }
        .page { max-width: 820px; margin: 0 auto; background: #fff; }

        /* ── Toolbar (solo pantalla) ── */
        .toolbar { background: #1e293b; color: #fff; padding: .75rem 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
        .toolbar .btn { font-size: .8rem; }

        /* ── Header institucional ── */
        .inst-header { padding: 1.8rem 2rem 1.4rem; border-bottom: 3px solid #1a3d6b; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
        .inst-logos { display: flex; gap: .8rem; align-items: center; }
        .inst-logos img { height: 58px; object-fit: contain; }
        .inst-logos .logo-sep { width: 1px; background: #cbd5e1; height: 50px; }
        .inst-info { text-align: right; }
        .inst-info .org-name { font-size: .95rem; font-weight: 700; color: #1a3d6b; line-height: 1.3; }
        .inst-info .sub-name { font-size: .78rem; color: #64748b; }
        .inst-info .doc-date { font-size: .72rem; color: #94a3b8; margin-top: .4rem; }

        /* ── Título del formulario ── */
        .form-title-bar { background: #1a3d6b; color: #fff; padding: 1.2rem 2rem; }
        .form-title-bar h1 { font-size: 1.25rem; font-weight: 700; margin: 0; }
        .form-title-bar .desc { font-size: .85rem; opacity: .8; margin-top: .3rem; }
        .form-title-bar .meta { display: flex; gap: 1.5rem; margin-top: .8rem; }
        .form-title-bar .meta-item { font-size: .78rem; opacity: .75; }
        .form-title-bar .meta-item strong { opacity: 1; }

        /* ── Cuerpo de respuestas ── */
        .pdf-body { padding: 1.5rem 2rem; }

        .empresa-block { border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1.8rem; page-break-inside: avoid; overflow: hidden; }
        .empresa-head { background: #f1f5f9; border-bottom: 2px solid #1a3d6b; padding: .75rem 1rem; display: flex; justify-content: space-between; align-items: center; }
        .empresa-head .emp-name { font-weight: 700; font-size: 1rem; color: #1a3d6b; }
        .empresa-head .emp-meta { font-size: .75rem; color: #64748b; }
        .empresa-head .emp-date { font-size: .72rem; color: #94a3b8; }

        .campos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .campo-item { padding: .65rem 1rem; border-bottom: 1px solid #f1f5f9; }
        .campo-item:nth-child(odd) { border-right: 1px solid #f1f5f9; }
        .campo-item.col-full { grid-column: 1 / -1; }
        .campo-item .c-label { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: .2rem; }
        .campo-item .c-value { font-weight: 600; font-size: .9rem; color: #1e293b; word-break: break-word; }
        .campo-item .c-value.empty { color: #94a3b8; font-weight: 400; font-style: italic; }

        /* ── Formulario vacío ── */
        .blank-campo { margin-bottom: 1.2rem; page-break-inside: avoid; }
        .blank-campo .bl-label { font-weight: 600; font-size: .9rem; color: #1e293b; margin-bottom: .4rem; }
        .blank-campo .bl-required { color: #dc3545; }
        .blank-campo .bl-input { border-bottom: 1.5px solid #94a3b8; min-height: 28px; margin-top: .2rem; }
        .blank-campo .bl-input.large { min-height: 60px; }
        .blank-campo .bl-ayuda { font-size: .75rem; color: #64748b; margin-top: .25rem; }
        .blank-campo .bl-opts { margin-top: .4rem; }
        .blank-campo .bl-opt-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .3rem; }
        .blank-campo .bl-circle { width: 14px; height: 14px; border: 1.5px solid #64748b; border-radius: 50%; flex-shrink: 0; }
        .blank-campo .bl-square { width: 14px; height: 14px; border: 1.5px solid #64748b; flex-shrink: 0; }

        /* ── Footer de página ── */
        .pdf-footer { border-top: 1px solid #e2e8f0; padding: .75rem 2rem; display: flex; justify-content: space-between; font-size: .72rem; color: #94a3b8; }
        .pdf-footer .confidencial { color: #dc3545; font-weight: 600; }

        /* ── Sin respuestas ── */
        .empty-state { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
        .empty-state i { font-size: 2.5rem; display: block; margin-bottom: .75rem; }

        /* ── Print ── */
        @media print {
            @page { size: A4; margin: 1.5cm 1.5cm 1.8cm 1.5cm; }
            body { background: #fff; font-size: 11px; color: #000; }
            .toolbar { display: none !important; }
            .page { max-width: 100%; margin: 0; box-shadow: none; }
            .empresa-block { break-inside: avoid; border: 1px solid #999; margin-bottom: 1rem; }
            .campos-grid { grid-template-columns: 1fr 1fr; }
            .campo-item { padding: .4rem .6rem; }
            .c-value { font-size: .85rem; }
            a { color: #000; text-decoration: none; }
            .inst-header { padding: 1rem; }
            .form-title-bar { padding: .8rem 1rem; }
            .pdf-body { padding: .8rem 1rem; }
            .empresa-block { page-break-inside: avoid; }
            .blank-campo { page-break-inside: avoid; }
            .pdf-footer { position: fixed; bottom: 0; width: 100%; background: #fff; padding: .4rem 1rem; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Toolbar solo pantalla -->
    <div class="toolbar no-print">
        <div class="d-flex gap-2 align-items-center">
            <a href="formulario-gestion.php?id=<?= $form_id ?>&tab=respuestas" class="btn btn-sm btn-outline-light">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
            <span class="text-white-50" style="font-size:.8rem;">
                <?= $modo === 'vacio' ? 'Formulario en blanco' : "Respuestas — $total_resp empresa(s)" ?>
            </span>
        </div>
        <div class="d-flex gap-2">
            <?php if ($modo === 'vacio'): ?>
            <a href="formulario-imprimir.php?id=<?= $form_id ?>&modo=respuestas" class="btn btn-sm btn-outline-light">
                <i class="bi bi-clipboard-data me-1"></i>Ver respuestas
            </a>
            <?php else: ?>
            <a href="formulario-imprimir.php?id=<?= $form_id ?>&modo=vacio" class="btn btn-sm btn-outline-light">
                <i class="bi bi-file-earmark me-1"></i>Formulario en blanco
            </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-sm btn-primary">
                <i class="bi bi-file-earmark-pdf me-1"></i>Guardar / Imprimir PDF
            </button>
        </div>
    </div>

    <!-- ── Header institucional ── -->
    <div class="inst-header">
        <div class="inst-logos">
            <img src="<?= $logo2_path ?>" alt="Gobierno de Catamarca" onerror="this.style.display='none'">
            <div class="logo-sep"></div>
            <img src="<?= $logo_path ?>" alt="Ministerio" onerror="this.style.display='none'">
        </div>
        <div class="inst-info">
            <div class="org-name">Ministerio de Producción<br>y Desarrollo Económico</div>
            <div class="sub-name">Parque Industrial de Catamarca</div>
            <div class="doc-date">Exportado el <?= $fecha_exp ?></div>
        </div>
    </div>

    <!-- ── Barra título del formulario ── -->
    <div class="form-title-bar">
        <h1><?= e($formulario['titulo']) ?></h1>
        <?php if (!empty($formulario['descripcion'])): ?>
            <div class="desc"><?= e($formulario['descripcion']) ?></div>
        <?php endif; ?>
        <div class="meta">
            <div class="meta-item">Estado: <strong><?= ucfirst($formulario['estado']) ?></strong></div>
            <?php if ($modo !== 'vacio'): ?>
            <div class="meta-item">Respuestas: <strong><?= $total_resp ?></strong></div>
            <?php endif; ?>
            <div class="meta-item">Preguntas: <strong><?= count($preguntas) ?></strong></div>
        </div>
    </div>

    <!-- ── CUERPO ── -->
    <div class="pdf-body">

    <?php if ($modo === 'vacio'): ?>
        <!-- Formulario en blanco -->
        <?php if (empty($preguntas)): ?>
        <div class="empty-state">
            <i class="bi bi-question-circle"></i>
            Este formulario no tiene preguntas.
        </div>
        <?php else: ?>
        <p style="font-size:.8rem;color:#64748b;margin-bottom:1.5rem;">
            Los campos con <span style="color:#dc3545">*</span> son obligatorios.
            Complete con letra clara y legible.
        </p>
        <?php foreach ($preguntas as $pid => $p):
            $opciones = [];
            if (!empty($p['opciones'])) {
                $dec = json_decode($p['opciones'], true);
                if (is_array($dec)) $opciones = $dec;
            }
        ?>
        <div class="blank-campo">
            <div class="bl-label">
                <?= e($p['etiqueta']) ?>
                <?php if ($p['requerido']): ?><span class="bl-required"> *</span><?php endif; ?>
            </div>
            <?php if ($p['tipo'] === 'textarea' || $p['tipo'] === 'tabla'): ?>
                <div class="bl-input large"></div>
            <?php elseif ($p['tipo'] === 'radio'): ?>
                <div class="bl-opts">
                    <?php foreach ($opciones as $op): ?>
                    <div class="bl-opt-row">
                        <div class="bl-circle"></div>
                        <span style="font-size:.85rem"><?= e(is_array($op) ? ($op['valor'] ?? $op['label'] ?? '') : $op) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($p['tipo'] === 'checkbox'): ?>
                <div class="bl-opts">
                    <?php foreach ($opciones as $op): ?>
                    <div class="bl-opt-row">
                        <div class="bl-square"></div>
                        <span style="font-size:.85rem"><?= e(is_array($op) ? ($op['valor'] ?? $op['label'] ?? '') : $op) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($p['tipo'] === 'select'): ?>
                <div class="bl-input" style="padding:.2rem .4rem;font-size:.8rem;color:#94a3b8;">
                    Opciones: <?= e(implode(' / ', array_map(fn($o) => is_array($o) ? ($o['valor'] ?? '') : $o, $opciones))) ?>
                </div>
            <?php else: ?>
                <div class="bl-input"></div>
            <?php endif; ?>
            <?php if (!empty($p['ayuda'])): ?>
                <div class="bl-ayuda"><?= e($p['ayuda']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    <?php else: ?>
        <!-- Respuestas completadas -->
        <?php if (empty($respuestas)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            No hay respuestas enviadas para este formulario.
        </div>
        <?php endif; ?>

        <?php foreach ($respuestas as $r):
            $valores = json_decode($r['respuestas'] ?? '{}', true) ?: [];
        ?>
        <div class="empresa-block">
            <div class="empresa-head">
                <div>
                    <div class="emp-name"><?= e($r['empresa_nombre']) ?></div>
                    <div class="emp-meta">
                        <?php if (!empty($r['cuit'])): ?>CUIT: <?= e($r['cuit']) ?><?php endif; ?>
                        <?php if (!empty($r['rubro'])): ?> &mdash; <?= e($r['rubro']) ?><?php endif; ?>
                        <?php if (!empty($r['ubicacion'])): ?> &mdash; <?= e($r['ubicacion']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="emp-date">
                    Enviado: <?= $r['enviado_at'] ? date('d/m/Y H:i', strtotime($r['enviado_at'])) : '—' ?>
                </div>
            </div>
            <div class="campos-grid">
                <?php foreach ($preguntas as $pid => $p):
                    $valor     = $valores[$pid] ?? null;
                    $es_largo  = in_array($p['tipo'], ['textarea', 'tabla', 'direccion'], true);
                    $vacio     = ($valor === null || $valor === '' || $valor === []);
                ?>
                <div class="campo-item <?= $es_largo ? 'col-full' : '' ?>">
                    <div class="c-label"><?= e($p['etiqueta']) ?></div>
                    <div class="c-value <?= $vacio ? 'empty' : '' ?>">
                        <?php if ($p['tipo'] === 'archivo' && !empty($valor)): ?>
                            <a href="<?= e(uploads_resolve_url((string) $valor, 'formularios')) ?>" target="_blank">[Ver archivo adjunto]</a>
                        <?php elseif ($p['tipo'] === 'archivo_adjunto'): ?>
                            <?= $vacio ? '—' : e((string)$valor) ?>
                        <?php elseif ($p['tipo'] === 'direccion' && !empty($valor)): ?>
                            <span style="font-family:monospace">📍 <?= e((string)$valor) ?></span>
                        <?php elseif (is_array($valor)): ?>
                            <?= $vacio ? '—' : e(implode(', ', $valor)) ?>
                        <?php else: ?>
                            <?= $vacio ? '—' : e((string)$valor) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    $terminos_export = get_config('terminos_legales_exportaciones', '');
    if ($terminos_export !== ''):
    ?>
    <div style="margin:0 2rem 1rem; padding:10px 14px; border:1px solid #e2e8f0; border-radius:6px; font-size:.72rem; color:#64748b; background:#f8fafc;">
        <strong style="color:#1e293b;">Términos legales:</strong><br>
        <?= nl2br(e($terminos_export)) ?>
    </div>
    <?php endif; ?>

    </div><!-- /pdf-body -->

    <!-- ── Footer ── -->
    <div class="pdf-footer">
        <div>Parque Industrial de Catamarca — Sistema de Gestión</div>
        <div class="confidencial">DOCUMENTO OFICIAL — USO INTERNO</div>
        <div><?= $fecha_exp ?></div>
    </div>

</div><!-- /page -->
</body>
</html>
