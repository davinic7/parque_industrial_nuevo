<?php
/**
 * Cloudinary simulado para tests/cloudinary.php (router de `php -S`). No usar fuera de las pruebas.
 *
 * POST /v1_1/{cloud}/{image|raw}/upload  -> valida api_key y firma SHA-1, registra la petición en
 *                                           $MOCK_LOG (JSON por línea) y responde {secure_url}.
 *   - Si el nombre del archivo empieza por "fallar_" responde 500 (para probar el respaldo local).
 * GET  /files/...                        -> 200
 */

$log = getenv('MOCK_LOG') ?: sys_get_temp_dir() . '/mock_cloudinary.log';
$secret = getenv('MOCK_SECRET') ?: '';
$apiKey = getenv('MOCK_API_KEY') ?: '';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($path, '/files/') === 0) {
    header('Content-Type: text/plain');
    echo 'archivo simulado';
    return;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !preg_match('#^/v1_1/([^/]+)/(image|raw)/upload$#', $path, $m)) {
    http_response_code(404);
    echo json_encode(['error' => ['message' => 'ruta desconocida']]);
    return;
}

header('Content-Type: application/json');
[$cloud, $resource] = [$m[1], $m[2]];
$file = $_FILES['file'] ?? null;
$params = $_POST;
$firma = (string) ($params['signature'] ?? '');
$claveRecibida = (string) ($params['api_key'] ?? '');
unset($params['signature'], $params['api_key']);
ksort($params);
$pares = [];
foreach ($params as $k => $v) {
    $pares[] = "$k=$v";
}
$firmaEsperada = sha1(implode('&', $pares) . $secret);

$registro = [
    'cloud'         => $cloud,
    'resource_type' => $resource,
    'params'        => $params,
    'nombre'        => $file['name'] ?? null,
    'mime'          => $file['type'] ?? null,
    'tamano'        => $file['size'] ?? null,
    'firma_ok'      => hash_equals($firmaEsperada, $firma),
    'api_key_ok'    => $claveRecibida === $apiKey,
];
file_put_contents($log, json_encode($registro) . "\n", FILE_APPEND | LOCK_EX);

if (!$registro['firma_ok'] || !$registro['api_key_ok']) {
    http_response_code(401);
    echo json_encode(['error' => ['message' => 'Invalid Signature']]);
    return;
}
if (strpos((string) ($file['name'] ?? ''), 'fallar_') === 0) {
    http_response_code(500);
    echo json_encode(['error' => ['message' => 'fallo simulado']]);
    return;
}

$carpeta = trim((string) ($params['folder'] ?? ''), '/');
$nombre = (string) ($file['name'] ?? 'archivo');
$base = pathinfo($nombre, PATHINFO_FILENAME) . '_' . bin2hex(random_bytes(3));
$ext = pathinfo($nombre, PATHINFO_EXTENSION);
// En 'raw' la extensión forma parte de la URL; en 'image' Cloudinary la pone según el formato.
$publicId = $resource === 'raw' ? $base . ($ext !== '' ? '.' . $ext : '') : $base . '.' . ($ext ?: 'jpg');
$origen = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
echo json_encode([
    'public_id'     => $publicId,
    'resource_type' => $resource,
    'secure_url'    => $origen . '/files/' . ($carpeta !== '' ? $carpeta . '/' : '') . $publicId,
]);
