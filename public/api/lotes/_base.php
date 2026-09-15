<?php
/**
 * Base común a todos los endpoints de lotes.
 * Carga config, establece headers JSON, valida sesión.
 *
 * Expone: $lotes_user_id, $lotes_rol
 * Requiere rol ministerio/admin para mutaciones (llamar lotes_require_admin()).
 */

require_once __DIR__ . '/../../../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function lotes_json_error(int $code, string $mensaje): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'mensaje' => $mensaje]);
    exit;
}

function lotes_require_admin(): void {
    global $lotes_rol;
    if ($lotes_rol !== 'ministerio' && $lotes_rol !== 'admin') {
        lotes_json_error(403, 'Acceso no autorizado.');
    }
}

function lotes_require_csrf(): void {
    $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verify_csrf($token)) {
        lotes_json_error(403, 'Token CSRF inválido. Recargue la página.');
    }
}

function lotes_json_input(): array {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : [];
    return is_array($data) ? $data : [];
}

$lotes_user_id = (int) ($_SESSION['user_id'] ?? 0);
$lotes_rol     = $_SESSION['user_rol'] ?? '';
