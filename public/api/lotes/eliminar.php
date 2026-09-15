<?php
/**
 * POST /api/lotes/eliminar.php — requiere sesión ministerio/admin.
 * Body JSON: { id }
 */

require __DIR__ . '/_base.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lotes_json_error(405, 'Método no permitido.');
}

if (!$auth->isLoggedIn()) {
    lotes_json_error(401, 'No autenticado.');
}

lotes_require_admin();
lotes_require_csrf();

$data = lotes_json_input();
$id = isset($data['id']) ? (int) $data['id'] : 0;

if (!$id) {
    lotes_json_error(422, 'ID de lote requerido.');
}

try {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM lotes WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        lotes_json_error(404, 'Lote no encontrado.');
    }

    echo json_encode(['status' => 'ok', 'mensaje' => 'Lote eliminado.']);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        lotes_json_error(409, 'No se puede eliminar: el lote tiene registros asociados.');
    }
    error_log('api/lotes/eliminar: ' . $e->getMessage());
    lotes_json_error(500, 'Error interno del servidor.');
}
