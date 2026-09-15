<?php
/**
 * GET /api/lotes/listar.php — requiere sesión ministerio/admin.
 * Devuelve todos los lotes con nombre de empresa (sin filtro de estado).
 */

require __DIR__ . '/_base.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    lotes_json_error(405, 'Método no permitido.');
}

if (!$auth->isLoggedIn()) {
    lotes_json_error(401, 'No autenticado.');
}

lotes_require_admin();

try {
    $db = getDB();
    $stmt = $db->query(
        "SELECT l.id, l.numero_lote, l.sector, l.superficie_m2, l.estado,
                l.geometria_terreno, l.empresa_id,
                COALESCE(e.nombre, e.razon_social) AS propietario_nombre
         FROM lotes l
         LEFT JOIN empresas e ON l.empresa_id = e.id
         ORDER BY l.numero_lote ASC"
    );
    $lotes = $stmt->fetchAll();

    foreach ($lotes as &$lote) {
        $lote['geometria_terreno'] = $lote['geometria_terreno']
            ? json_decode($lote['geometria_terreno'], true)
            : null;
    }
    unset($lote);

    echo json_encode(['status' => 'ok', 'total' => count($lotes), 'data' => $lotes]);
} catch (Throwable $e) {
    error_log('api/lotes/listar: ' . $e->getMessage());
    lotes_json_error(500, 'Error interno del servidor.');
}
