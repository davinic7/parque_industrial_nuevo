<?php
/**
 * POST /api/lotes/geometria.php — requiere sesión ministerio/admin.
 * Body JSON: { id, geometria_terreno: GeoJSON Polygon (objeto o string JSON) }
 * Guarda el polígono del lote. Enviar geometria_terreno = null para borrar.
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
$id   = isset($data['id']) ? (int) $data['id'] : 0;

if (!$id) {
    lotes_json_error(422, 'ID de lote requerido.');
}

$geometria   = $data['geometria_terreno'] ?? null;
$superficie  = array_key_exists('superficie_m2', $data)
               ? ($data['superficie_m2'] !== null ? (float) $data['superficie_m2'] : null)
               : false; // false = no vino en el request, no tocar

if ($geometria !== null) {
    if (is_array($geometria)) {
        $geometria = json_encode($geometria);
    }
    if (!is_string($geometria) || json_decode($geometria) === null) {
        lotes_json_error(422, 'La geometría no es un GeoJSON válido.');
    }
}

try {
    $db = getDB();

    if ($superficie !== false) {
        $stmt = $db->prepare('UPDATE lotes SET geometria_terreno = ?, superficie_m2 = ? WHERE id = ?');
        $stmt->execute([$geometria, $superficie, $id]);
    } else {
        $stmt = $db->prepare('UPDATE lotes SET geometria_terreno = ? WHERE id = ?');
        $stmt->execute([$geometria, $id]);
    }

    if ($stmt->rowCount() === 0) {
        $exists = $db->prepare('SELECT id FROM lotes WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            lotes_json_error(404, 'Lote no encontrado.');
        }
    }

    echo json_encode(['status' => 'ok', 'mensaje' => 'Geometría guardada.']);
} catch (Throwable $e) {
    error_log('api/lotes/geometria: ' . $e->getMessage());
    lotes_json_error(500, 'Error interno del servidor.');
}
