<?php
/**
 * Contactos de emergencia y servicios - Ministerio
 * Alta, edición y baja del directorio de teléfonos útiles.
 */
require_once __DIR__ . '/../../config/config.php';
require_once BASEPATH . '/includes/contactos_emergencia.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Contactos de emergencia';
$ministerio_nav = 'contactos';
$db = getDB();
$cats = contactos_categorias();

if (!contactos_tabla_disponible($db)) {
    set_flash('warning', 'La tabla de contactos no está creada. Aplique database/023_contactos_emergencia.sql primero.');
    redirect('dashboard.php');
}

// ── POST: guardar / eliminar ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id          = (int) ($_POST['id'] ?? 0);
        $categoria   = $_POST['categoria'] ?? 'otros';
        $nombre      = trim($_POST['nombre'] ?? '');
        $telefono    = trim($_POST['telefono'] ?? '');
        $telefono2   = trim($_POST['telefono_alt'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $visibilidad = ($_POST['visibilidad'] ?? 'publico') === 'empresas' ? 'empresas' : 'publico';
        $orden       = max(0, (int) ($_POST['orden'] ?? 0));
        $activo      = isset($_POST['activo']) ? 1 : 0;

        if (!isset($cats[$categoria])) $categoria = 'otros';
        $nombre      = mb_substr($nombre, 0, 150);
        $telefono    = mb_substr($telefono, 0, 50);
        $telefono2   = mb_substr($telefono2, 0, 50);
        $descripcion = mb_substr($descripcion, 0, 255);

        if ($nombre === '' || $telefono === '') {
            set_flash('error', 'El nombre y el teléfono son obligatorios.');
            redirect('contactos-emergencia.php' . ($id > 0 ? '?editar=' . $id : ''));
        }

        $params = [$categoria, $nombre, $telefono, $telefono2 ?: null, $descripcion ?: null, $visibilidad, $orden, $activo];
        if ($id > 0) {
            $params[] = $id;
            $db->prepare('UPDATE contactos_emergencia SET categoria=?, nombre=?, telefono=?, telefono_alt=?, descripcion=?, visibilidad=?, orden=?, activo=? WHERE id=?')
               ->execute($params);
            set_flash('success', 'Contacto actualizado.');
        } else {
            $db->prepare('INSERT INTO contactos_emergencia (categoria, nombre, telefono, telefono_alt, descripcion, visibilidad, orden, activo) VALUES (?,?,?,?,?,?,?,?)')
               ->execute($params);
            set_flash('success', 'Contacto agregado.');
        }
        if (function_exists('log_activity')) {
            log_activity('contacto_emergencia_guardado', 'contactos_emergencia', $id ?: (int) $db->lastInsertId());
        }
        redirect('contactos-emergencia.php');
    }

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare('DELETE FROM contactos_emergencia WHERE id = ?')->execute([$id]);
            set_flash('success', 'Contacto eliminado.');
        }
        redirect('contactos-emergencia.php');
    }
}

$contactos = $db->query('SELECT * FROM contactos_emergencia ORDER BY FIELD(categoria,' .
    implode(',', array_map([$db, 'quote'], array_keys($cats))) . '), orden ASC, nombre ASC')->fetchAll();

$editando = null;
if (!empty($_GET['editar'])) {
    $eid = (int) $_GET['editar'];
    foreach ($contactos as $c) {
        if ((int) $c['id'] === $eid) { $editando = $c; break; }
    }
}

require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-telephone-plus me-2"></i>Contactos de emergencia</h1>
    <div class="d-flex gap-2">
        <a href="<?= PUBLIC_URL ?>/contactos.php" target="_blank" rel="noopener" class="btn btn-outline-secondary">
            <i class="bi bi-box-arrow-up-right me-1"></i>Ver página pública
        </a>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formContacto" aria-expanded="<?= $editando ? 'true' : 'false' ?>">
            <i class="bi bi-plus-lg me-1"></i><?= $editando ? 'Editando contacto' : 'Nuevo contacto' ?>
        </button>
    </div>
</div>
<p class="text-muted mb-4">Teléfonos útiles ante cortes de luz, agua o gas, emergencias y la administración del parque.
    Los contactos <strong>públicos</strong> aparecen en el sitio web y en el panel de las empresas;
    los de <strong>sólo empresas</strong>, únicamente en el panel de las empresas.</p>

<?php show_flash(); ?>

<div class="collapse <?= $editando ? 'show' : '' ?> mb-4" id="formContacto">
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= (int) ($editando['id'] ?? 0) ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select name="categoria" class="form-select">
                            <?php foreach ($cats as $k => [$label]): ?>
                            <option value="<?= e($k) ?>"<?= ($editando['categoria'] ?? 'electricidad') === $k ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required maxlength="150"
                               value="<?= e($editando['nombre'] ?? '') ?>" placeholder="Ej: Guardia de reclamos por cortes de luz">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Teléfono *</label>
                        <input type="text" name="telefono" class="form-control" required maxlength="50"
                               value="<?= e($editando['telefono'] ?? '') ?>" placeholder="Ej: 0800-555-1234">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Teléfono alternativo</label>
                        <input type="text" name="telefono_alt" class="form-control" maxlength="50"
                               value="<?= e($editando['telefono_alt'] ?? '') ?>" placeholder="Opcional (celular, WhatsApp)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Visible para</label>
                        <select name="visibilidad" class="form-select">
                            <option value="publico"<?= ($editando['visibilidad'] ?? 'publico') === 'publico' ? ' selected' : '' ?>>Público (sitio web y empresas)</option>
                            <option value="empresas"<?= ($editando['visibilidad'] ?? '') === 'empresas' ? ' selected' : '' ?>>Sólo empresas del parque</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" name="descripcion" class="form-control" maxlength="255"
                               value="<?= e($editando['descripcion'] ?? '') ?>" placeholder="Ej: Atención 24 horas. Indicar número de lote al llamar.">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Orden</label>
                        <input type="number" name="orden" class="form-control" min="0" value="<?= (int) ($editando['orden'] ?? 0) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="activo" class="form-check-input" id="chkActivo"
                                   <?= ($editando === null || !empty($editando['activo'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkActivo">Activo</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= $editando ? 'Actualizar' : 'Agregar' ?> contacto</button>
                        <?php if ($editando): ?><a href="contactos-emergencia.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Nombre</th>
                    <th>Teléfonos</th>
                    <th>Visible para</th>
                    <th>Estado</th>
                    <th class="text-end" style="width:110px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contactos)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay contactos cargados. Agregue el primero.</td></tr>
                <?php endif; ?>
                <?php foreach ($contactos as $c):
                    [$clabel, $cicon, $ccolor] = $cats[$c['categoria']] ?? $cats['otros']; ?>
                <tr class="<?= empty($c['activo']) ? 'table-secondary' : '' ?>">
                    <td class="small"><i class="bi <?= e($cicon) ?> me-1" style="color:<?= e($ccolor) ?>"></i><?= e($clabel) ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($c['nombre']) ?></div>
                        <?php if (!empty($c['descripcion'])): ?><div class="small text-muted"><?= e($c['descripcion']) ?></div><?php endif; ?>
                    </td>
                    <td class="small text-nowrap"><?= e($c['telefono']) ?><?= !empty($c['telefono_alt']) ? '<br>' . e($c['telefono_alt']) : '' ?></td>
                    <td><?= $c['visibilidad'] === 'publico'
                            ? '<span class="badge bg-success">Público</span>'
                            : '<span class="badge bg-secondary">Sólo empresas</span>' ?></td>
                    <td><?= $c['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="contactos-emergencia.php?editar=<?= (int) $c['id'] ?>" class="btn btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este contacto?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once BASEPATH . '/includes/ministerio_layout_footer.php'; ?>
