<?php
/**
 * POST /api/lotes/guardar.php — requiere sesión ministerio/admin.
 * Body JSON: { numero_lote, sector?, superficie_m2?, estado?, empresa_id?, id? }
 * Si id presente → UPDATE, si no → INSERT.
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

$id          = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
$numero_lote = trim($data['numero_lote'] ?? '');
$sector      = trim($data['sector'] ?? '') ?: null;
$superficie  = isset($data['superficie_m2']) && $data['superficie_m2'] !== ''
               ? (float) $data['superficie_m2'] : null;
$estado      = $data['estado'] ?? 'disponible';
$empresa_id  = isset($data['empresa_id']) && $data['empresa_id'] !== ''
               ? (int) $data['empresa_id'] : null;

if ($numero_lote === '') {
    lotes_json_error(422, 'El número de lote es obligatorio.');
}

$estados_validos = ['disponible', 'ocupado', 'reservado'];
if (!in_array($estado, $estados_validos, true)) {
    lotes_json_error(422, 'Estado inválido.');
}

try {
    $db = getDB();

    if ($id) {
        $stmt = $db->prepare(
            "UPDATE lotes SET numero_lote = ?, sector = ?, superficie_m2 = ?,
                              estado = ?, empresa_id = ?
             WHERE id = ?"
        );
        $stmt->execute([$numero_lote, $sector, $superficie, $estado, $empresa_id, $id]);

        if ($stmt->rowCount() === 0) {
            $exists = $db->prepare('SELECT id FROM lotes WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                lotes_json_error(404, 'Lote no encontrado.');
            }
        }

        if ($empresa_id) {
            $db->prepare(
                "UPDATE empresas SET lote_solicitud_estado = 'asignado', lote_declarado = ?
                 WHERE id = ? AND lote_solicitud_estado != 'asignado'"
            )->execute([$numero_lote, $empresa_id]);
        }

        echo json_encode(['status' => 'ok', 'mensaje' => 'Lote actualizado.', 'id' => $id]);
    } else {
        $stmt = $db->prepare(
            "INSERT INTO lotes (numero_lote, sector, superficie_m2, estado, empresa_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$numero_lote, $sector, $superficie, $estado, $empresa_id]);
        $nuevo_id = (int) $db->lastInsertId();

        if ($empresa_id) {
            $db->prepare(
                "UPDATE empresas SET lote_solicitud_estado = 'asignado', lote_declarado = ? WHERE id = ?"
            )->execute([$numero_lote, $empresa_id]);
        }

        echo json_encode(['status' => 'ok', 'mensaje' => 'Lote creado.', 'id' => $nuevo_id]);
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1062')) {
        lotes_json_error(409, 'El número de lote ya existe.');
    }
    error_log('api/lotes/guardar: ' . $e->getMessage());
    lotes_json_error(500, 'Error interno del servidor.');
}
