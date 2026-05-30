<?php
/**
 * Formularios Dinamicos - Ministerio
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Formularios Dinamicos';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $accion = $_POST['accion'] ?? '';
    $form_id = (int)($_POST['formulario_id'] ?? 0);

    if ($form_id > 0 && in_array($accion, ['publicar', 'archivar', 'borrador'], true)) {
        $estado = $accion === 'publicar' ? 'publicado' : ($accion === 'archivar' ? 'archivado' : 'borrador');
        $stmt = $db->prepare("UPDATE formularios_dinamicos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $form_id]);
        log_activity("formulario_dinamico_$estado", 'formularios_dinamicos', $form_id);
        set_flash('success', 'Estado actualizado correctamente.');
        redirect('formularios-dinamicos.php');
    }
}

try {
    $stmt = $db->query("
        SELECT
            f.*,
            (SELECT COUNT(*) FROM formulario_preguntas p WHERE p.formulario_id = f.id) AS total_preguntas,
            (SELECT COUNT(*) FROM formulario_respuestas r WHERE r.formulario_id = f.id AND r.estado = 'enviado') AS total_respuestas,
            (SELECT COUNT(*) FROM formulario_envios fe WHERE fe.formulario_id = f.id) AS total_envios
        FROM formularios_dinamicos f
        ORDER BY f.created_at DESC
    ");
} catch (Exception $e) {
    $stmt = $db->query("
        SELECT
            f.*,
            (SELECT COUNT(*) FROM formulario_preguntas p WHERE p.formulario_id = f.id) AS total_preguntas,
            (SELECT COUNT(*) FROM formulario_respuestas r WHERE r.formulario_id = f.id AND r.estado = 'enviado') AS total_respuestas
        FROM formularios_dinamicos f
        ORDER BY f.created_at DESC
    ");
}
$formularios = $stmt->fetchAll();
foreach ($formularios as &$f) {
    if (!isset($f['total_envios'])) {
        $f['total_envios'] = 0;
    }
}
unset($f);

$ministerio_nav = 'formularios_dinamicos';
require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Formularios dinámicos</h1>
            <a href="formulario-nuevo.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nuevo formulario</a>
        </div>

        <?php show_flash(); ?>

        <div class="table-container">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Formulario</th>
                        <th style="width:110px;">Estado</th>
                        <th style="width:130px;" class="text-muted small fw-normal">Creado</th>
                        <th style="width:130px;" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($formularios)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-5">
                        <i class="bi bi-file-earmark-text fs-2 d-block mb-2 opacity-50"></i>
                        No hay formularios creados aún
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($formularios as $f):
                        $badge_class = ['borrador' => 'bg-secondary', 'publicado' => 'bg-success', 'archivado' => 'bg-dark'];
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($f['titulo']) ?></div>
                            <?php if (!empty($f['descripcion'])): ?>
                                <div class="text-muted small"><?= e(truncate($f['descripcion'], 80)) ?></div>
                            <?php endif; ?>
                            <div class="mt-1">
                                <span class="text-muted small me-3">
                                    <i class="bi bi-question-circle me-1"></i><?= (int)$f['total_preguntas'] ?> preguntas
                                </span>
                                <span class="text-muted small me-3">
                                    <i class="bi bi-chat-left-text me-1"></i><?= (int)$f['total_respuestas'] ?> respuestas
                                </span>
                                <span class="text-muted small">
                                    <i class="bi bi-send me-1"></i><?= (int)$f['total_envios'] ?> envíos
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $badge_class[$f['estado']] ?? 'bg-secondary' ?>">
                                <?= ucfirst($f['estado']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= format_datetime($f['created_at']) ?></td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="formulario-gestion.php?id=<?= $f['id'] ?>&tab=respuestas"
                                   class="btn btn-sm btn-primary" title="Gestionar">
                                    <i class="bi bi-sliders"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="formulario-gestion.php?id=<?= $f['id'] ?>&tab=respuestas">
                                            <i class="bi bi-sliders me-2"></i>Gestionar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="formulario-preview.php?id=<?= $f['id'] ?>" target="_blank">
                                            <i class="bi bi-eye me-2"></i>Vista previa
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="formulario-imprimir.php?id=<?= $f['id'] ?>" target="_blank">
                                            <i class="bi bi-printer me-2"></i>Imprimir / PDF
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="formulario-editar.php?id=<?= $f['id'] ?>">
                                            <i class="bi bi-pencil me-2"></i>Editar estructura
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="formulario-gestion.php?id=<?= $f['id'] ?>&tab=enviar">
                                            <i class="bi bi-send me-2"></i>Enviar a empresas
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Cambiar estado</h6></li>
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="formulario_id" value="<?= $f['id'] ?>">
                                        <?php if ($f['estado'] !== 'publicado'): ?>
                                        <li>
                                            <button class="dropdown-item text-success" name="accion" value="publicar">
                                                <i class="bi bi-check-circle me-2"></i>Publicar
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($f['estado'] !== 'borrador'): ?>
                                        <li>
                                            <button class="dropdown-item" name="accion" value="borrador">
                                                <i class="bi bi-pencil-square me-2"></i>Pasar a borrador
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($f['estado'] !== 'archivado'): ?>
                                        <li>
                                            <button class="dropdown-item text-muted" name="accion" value="archivar">
                                                <i class="bi bi-archive me-2"></i>Archivar
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                    </form>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
