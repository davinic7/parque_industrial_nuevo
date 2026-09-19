<?php
/**
 * Pestaña "enviar" de public/ministerio/formulario-gestion.php.
 * Se incluye desde esa página y usa sus variables ($formulario, $form_id, $respuestas, $lista_envios, ...).
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
?>

<?php if ($error_envio): ?>
<div class="alert alert-danger"><?= e($error_envio) ?></div>
<?php endif; ?>

<?php if ($formulario['estado'] !== 'publicado'): ?>
<div class="alert alert-warning">
    Este formulario no está publicado. Publíquelo desde
    <a href="formularios-dinamicos.php">Plantillas</a> antes de enviarlo.
</div>
<?php endif; ?>

<?php if ($preview_envio !== null): ?>
<div class="card mb-4">
    <div class="card-header bg-white"><strong>Vista previa</strong> — <?= count($preview_envio) ?> empresa(s)</div>
    <div class="card-body" style="max-height:320px;overflow-y:auto;">
        <?php if ($preview_envio === []): ?>
        <p class="text-muted mb-0">Ninguna empresa coincide con los filtros.</p>
        <?php else: ?>
        <ul class="list-unstyled mb-0 small">
            <?php foreach ($preview_envio as $pe): ?>
            <li class="mb-1"><strong><?= e($pe['nombre']) ?></strong> — <?= e($pe['rubro'] ?? '-') ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<form method="POST" class="card">
    <div class="card-body">
        <?= csrf_field() ?>
        <input type="hidden" name="formulario_id" value="<?= $form_id ?>">

        <div class="mb-3">
            <label class="form-label">Destinatarios</label>
            <select name="tipo_filtro" class="form-select" id="tipoFiltro">
                <option value="todos" <?= ($_POST['tipo_filtro'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todas las empresas activas</option>
                <option value="rubro" <?= ($_POST['tipo_filtro'] ?? '') === 'rubro' ? 'selected' : '' ?>>Por rubro</option>
                <option value="ubicacion" <?= ($_POST['tipo_filtro'] ?? '') === 'ubicacion' ? 'selected' : '' ?>>Por ubicación</option>
                <option value="estado" <?= ($_POST['tipo_filtro'] ?? '') === 'estado' ? 'selected' : '' ?>>Por estado de empresa</option>
                <option value="empresas_especificas" <?= ($_POST['tipo_filtro'] ?? '') === 'empresas_especificas' ? 'selected' : '' ?>>Empresas específicas</option>
            </select>
        </div>

        <div class="mb-3 filtro-opt" id="boxRubros" style="display:none;">
            <label class="form-label">Rubros</label>
            <select name="rubros[]" class="form-select" multiple size="6">
                <?php foreach ($rubros_opts as $r): ?>
                <option value="<?= e($r) ?>"><?= e($r) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Ctrl+clic para varios</small>
        </div>

        <div class="mb-3 filtro-opt" id="boxUbic" style="display:none;">
            <label class="form-label">Ubicaciones</label>
            <select name="ubicaciones[]" class="form-select" multiple size="5">
                <?php foreach ($ubic_opts as $u): ?>
                <option value="<?= e($u) ?>"><?= e($u) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3 filtro-opt" id="boxEstado" style="display:none;">
            <label class="form-label">Estado empresa</label>
            <?php foreach (['pendiente', 'activa', 'suspendida', 'inactiva'] as $es): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="estados[]" value="<?= e($es) ?>" id="est_<?= e($es) ?>"
                    <?= $es === 'activa' ? 'checked' : '' ?>>
                <label class="form-check-label" for="est_<?= e($es) ?>"><?= ucfirst($es) ?></label>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-3 filtro-opt" id="boxEmp" style="display:none;">
            <label class="form-label">Empresas</label>
            <select name="empresas_ids[]" class="form-select" multiple size="10">
                <?php foreach ($lista_empresas_todas as $le): ?>
                <option value="<?= (int)$le['id'] ?>"><?= e($le['nombre']) ?> (<?= e($le['rubro'] ?? '') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Fecha límite (opcional)</label>
            <input type="date" name="fecha_limite" class="form-control" value="<?= e($_POST['fecha_limite'] ?? '') ?>">
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" name="paso" value="vista_previa" class="btn btn-outline-primary">
                <i class="bi bi-eye me-1"></i>Vista previa
            </button>
            <button type="submit" name="paso" value="confirmar" class="btn btn-success"
                <?= $formulario['estado'] !== 'publicado' ? 'disabled' : '' ?>>
                <i class="bi bi-send me-1"></i>Confirmar y enviar
            </button>
        </div>
    </div>
</form>

