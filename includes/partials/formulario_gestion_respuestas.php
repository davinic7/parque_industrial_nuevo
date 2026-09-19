<?php
/**
 * Pestaña "respuestas" de public/ministerio/formulario-gestion.php.
 * Se incluye desde esa página y usa sus variables ($formulario, $form_id, $respuestas, $lista_envios, ...).
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
?>

<div class="table-container mb-4">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Empresa</th>
                <th>CUIT</th>
                <th>Estado</th>
                <th>Enviado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($respuestas)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No hay respuestas para este formulario.</td></tr>
            <?php endif; ?>
            <?php foreach ($respuestas as $r): ?>
            <tr>
                <td><strong><?= e($r['empresa_nombre']) ?></strong></td>
                <td><?= e($r['cuit'] ?? '-') ?></td>
                <td>
                    <?php $bc = ['borrador' => 'bg-secondary', 'enviado' => 'bg-success']; ?>
                    <span class="badge <?= $bc[$r['estado']] ?? 'bg-secondary' ?>"><?= ucfirst($r['estado']) ?></span>
                </td>
                <td><?= $r['enviado_at'] ? format_datetime($r['enviado_at']) : '-' ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detalle<?= $r['id'] ?>">
                        <i class="bi bi-eye"></i> Ver
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php foreach ($respuestas as $r):
    $valores = json_decode($r['respuestas'] ?? '{}', true) ?: [];
?>
<div class="modal fade" id="detalle<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= e($r['empresa_nombre']) ?> <span class="text-muted small d-block">Respuesta #<?= $r['id'] ?></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <small class="text-muted d-block mb-3">
                    Estado: <?= ucfirst($r['estado']) ?> ·
                    Enviado: <?= $r['enviado_at'] ? format_datetime($r['enviado_at']) : '-' ?>
                </small>
                <div class="row g-3">
                    <?php foreach ($preguntas as $pid => $p):
                        $valor = $valores[$pid] ?? null;
                    ?>
                    <div class="col-12">
                        <div class="border rounded p-2">
                            <div class="small text-muted mb-1"><?= e($p['etiqueta']) ?></div>
                            <?php if ($p['tipo'] === 'archivo' && !empty($valor)): ?>
                                <a href="<?= e(uploads_resolve_url((string) $valor, 'formularios')) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-paperclip me-1"></i><?= e(basename((string) $valor)) ?>
                                </a>
                            <?php elseif (is_array($valor)): ?>
                                <strong><?= e(implode(', ', $valor)) ?: '-' ?></strong>
                            <?php else: ?>
                                <strong><?= ($valor !== null && $valor !== '') ? e((string)$valor) : '-' ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

