<?php
/**
 * Pestaña "envios" de public/ministerio/formulario-gestion.php.
 * Se incluye desde esa página y usa sus variables ($formulario, $form_id, $respuestas, $lista_envios, ...).
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
?>

<?php if ($envio_id > 0 && $envio): ?>

    <!-- Detalle de un envío -->
    <?php
    $total_f = count($filas_envio);
    $hechas_f = count(array_filter($filas_envio, static fn($r) => !empty($r['enviado_at']) || (int)$r['respondido'] === 1));
    $pend_f = $total_f - $hechas_f;
    ?>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <p class="text-muted mb-0">Envío del <?= format_datetime($envio['created_at']) ?>
                <?php if (!empty($envio['fecha_limite'])): ?> · Límite: <?= e($envio['fecha_limite']) ?><?php endif; ?>
            </p>
        </div>
        <a href="formulario-gestion.php?id=<?= $form_id ?>&tab=envios" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Todos los envíos
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light"><div class="card-body text-center">
                <div class="fs-2 fw-bold text-primary"><?= $total_f ?></div>
                <div class="small text-muted">Destinatarios</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light"><div class="card-body text-center">
                <div class="fs-2 fw-bold text-success"><?= $hechas_f ?></div>
                <div class="small text-muted">Respondieron</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light"><div class="card-body text-center">
                <div class="fs-2 fw-bold text-warning"><?= $pend_f ?></div>
                <div class="small text-muted">Pendientes</div>
            </div></div>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-hover table-sm align-middle">
            <thead>
                <tr><th>Empresa</th><th>Rubro</th><th>Estado</th><th>Límite</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($filas_envio as $f):
                    $ok   = !empty($f['enviado_at']) || (int)$f['respondido'] === 1;
                    $lim  = $f['limite'] ?? null;
                    $venc = $lim && !$ok && strtotime($lim . ' 23:59:59') < time();
                    $txt  = $ok ? 'Respondido' : ($venc ? 'Vencido' : 'Pendiente');
                    $bg   = $ok ? 'success' : ($venc ? 'danger' : 'warning');
                ?>
                <tr>
                    <td><?= e($f['nombre']) ?></td>
                    <td><?= e($f['rubro'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $bg ?>"><?= $txt ?></span></td>
                    <td><?= $lim ? e($lim) : '—' ?></td>
                    <td>
                        <?php if (!$ok): ?>
                        <form method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="envio_id" value="<?= $envio_id ?>">
                            <input type="hidden" name="destinatario_id" value="<?= (int)$f['fd_id'] ?>">
                            <button type="submit" name="accion" value="recordatorio" class="btn btn-sm btn-outline-primary">Recordatorio</button>
                            <button type="submit" name="accion" value="extender" class="btn btn-sm btn-outline-secondary">+7 días</button>
                        </form>
                        <?php else: ?>
                        <a href="formulario-gestion.php?id=<?= $form_id ?>&tab=respuestas" class="btn btn-sm btn-outline-success">Ver</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>

    <!-- Lista de todos los envíos -->
    <div class="table-container">
        <table class="table table-hover">
            <thead>
                <tr><th>Fecha</th><th>Filtro</th><th>Destinatarios</th><th>Límite</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($lista_envios)): ?>
                <tr><td colspan="5" class="text-muted text-center py-4">Sin envíos registrados. Use la pestaña «Enviar a empresas».</td></tr>
                <?php endif; ?>
                <?php foreach ($lista_envios as $le): ?>
                <tr>
                    <td><?= format_datetime($le['created_at']) ?></td>
                    <td><?= e($le['tipo_filtro']) ?></td>
                    <td><?= (int)$le['total_destinatarios'] ?></td>
                    <td><?= $le['fecha_limite'] ? e($le['fecha_limite']) : '—' ?></td>
                    <td>
                        <a class="btn btn-sm btn-primary"
                           href="formulario-gestion.php?id=<?= $form_id ?>&tab=envios&envio_id=<?= (int)$le['id'] ?>">
                            Seguimiento
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

