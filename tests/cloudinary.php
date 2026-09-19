<?php
/**
 * Pruebas del almacenamiento de archivos con Cloudinary (contra un Cloudinary SIMULADO).
 *
 *   php tests/cloudinary.php             (o: npm run test:cloudinary)
 *
 * Levanta por sí mismo dos servidores locales: la aplicación (puerto 8090, con credenciales de
 * Cloudinary falsas) y un Cloudinary simulado (puerto 8091, tests/mock_cloudinary.php) que valida
 * la firma SHA-1 y registra cada subida. Así se comprueba, sin cuentas reales:
 *  - firma, tipo de recurso (image / raw), carpeta y nombre enviados a Cloudinary;
 *  - que la extensión NUNCA salga del nombre del cliente;
 *  - que los tipos no permitidos no lleguen a Cloudinary;
 *  - el respaldo a disco local cuando Cloudinary falla;
 *  - que las URLs guardadas se muestren bien (comunicaciones, formularios, presentar proyecto).
 * Crea datos zz_test_* y los borra al terminar. Solo usar contra una base de desarrollo.
 *
 * Sale con código 1 si algo falla, 2 si no puede preparar el entorno.
 */

const APP_PORT = 8090;
const MOCK_PORT = 8091;
const CLOUD = 'zz-test-cloud';
const API_KEY = 'zz-test-key';
const API_SECRET = 'zz-test-secret';

putenv('TEST_BASE_URL=http://localhost:' . APP_PORT);
require_once __DIR__ . '/_lib.php';
require_once BASEPATH . '/includes/funciones.php';

// Si el .env real tuviera credenciales de Cloudinary, pisarían las de la prueba (.env manda sobre el entorno).
foreach (@file(BASEPATH . '/.env', FILE_IGNORE_NEW_LINES) ?: [] as $linea) {
    if (preg_match('/^\s*CLOUDINARY_[A-Z_]+\s*=\s*\S+/', $linea)) {
        fwrite(STDERR, "El .env define credenciales de CLOUDINARY_*: la prueba las usaría y subiría archivos reales.\n"
            . "Vacíelas temporalmente para correr esta prueba.\n");
        exit(2);
    }
}

$LOG = tempnam(sys_get_temp_dir(), 'mockcld');
$procs = [];
function lanzar(string $cmd, array $env): mixed {
    $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', sys_get_temp_dir() . '/zz_srv.log', 'a'], 2 => ['file', sys_get_temp_dir() . '/zz_srv.log', 'a']],
        $pipes, BASEPATH, array_merge(getenv(), $env));
    if (!is_resource($p)) {
        fwrite(STDERR, "No se pudo lanzar: $cmd\n");
        exit(2);
    }
    return $p;
}
register_shutdown_function(function () use (&$procs) {
    foreach ($procs as $p) {
        if (is_resource($p)) {
            $st = proc_get_status($p);
            if (PHP_OS_FAMILY === 'Windows') {
                exec('taskkill /F /T /PID ' . (int) $st['pid'] . ' >NUL 2>&1');
            } else {
                proc_terminate($p);
            }
            proc_close($p);
        }
    }
});
$php = escapeshellarg(PHP_BINARY);
$procs[] = lanzar("$php -S localhost:" . MOCK_PORT . ' ' . escapeshellarg(__DIR__ . '/mock_cloudinary.php'),
    ['MOCK_LOG' => $LOG, 'MOCK_SECRET' => API_SECRET, 'MOCK_API_KEY' => API_KEY]);
$procs[] = lanzar("$php -S localhost:" . APP_PORT . ' -t ' . escapeshellarg(BASEPATH . '/public'), [
    'CLOUDINARY_CLOUD_NAME' => CLOUD, 'CLOUDINARY_API_KEY' => API_KEY, 'CLOUDINARY_API_SECRET' => API_SECRET,
    'CLOUDINARY_API_BASE' => 'http://localhost:' . MOCK_PORT,
]);
for ($i = 0; $i < 50; $i++) {
    $a = @fsockopen('localhost', APP_PORT, $e1, $e2, 0.2);
    $b = @fsockopen('localhost', MOCK_PORT, $e1, $e2, 0.2);
    if ($a && $b) { fclose($a); fclose($b); break; }
    usleep(200000);
}
exigir_servidor($BASE);

$ids = ['usuarios' => [], 'empresas' => []];
$archivosLocales = [];
$formId = 0;

function limpiar(PDO $db, array $ids, array $archivosLocales, int $formId): void {
    try {
        foreach ($archivosLocales as $f) {
            @unlink($f);
        }
        $db->exec("DELETE FROM solicitudes_proyecto WHERE email LIKE 'zz_test%'");
        if ($formId > 0) {
            $db->prepare('DELETE FROM formulario_respuestas WHERE formulario_id = ?')->execute([$formId]);
            $db->prepare('DELETE FROM formulario_preguntas WHERE formulario_id = ?')->execute([$formId]);
            $db->prepare('DELETE FROM formularios_dinamicos WHERE id = ?')->execute([$formId]);
        }
        foreach ($ids['empresas'] as $e) {
            $conv = $db->prepare('SELECT id FROM conversaciones WHERE empresa_id = ?');
            $conv->execute([$e]);
            foreach ($conv->fetchAll(PDO::FETCH_COLUMN) as $c) {
                $db->prepare('DELETE FROM adjuntos_mensajes WHERE mensaje_id IN (SELECT id FROM mensajes_v2 WHERE conversacion_id = ?)')->execute([$c]);
                $db->prepare('DELETE FROM mensajes_v2 WHERE conversacion_id = ?')->execute([$c]);
                $db->prepare('DELETE FROM conversaciones WHERE id = ?')->execute([$c]);
            }
            foreach (['formulario_respuestas', 'visitas_empresa'] as $t) {
                try { $db->prepare("DELETE FROM $t WHERE empresa_id = ?")->execute([$e]); } catch (Throwable $ex) { /* opcional */ }
            }
        }
        foreach ($ids['usuarios'] as $u) {
            foreach (['log_actividad', 'notificaciones'] as $t) {
                try { $db->prepare("DELETE FROM $t WHERE usuario_id = ?")->execute([$u]); } catch (Throwable $ex) { /* opcional */ }
            }
        }
        foreach ($ids['empresas'] as $e) {
            $db->prepare('DELETE FROM empresas WHERE id = ?')->execute([$e]);
        }
        foreach ($ids['usuarios'] as $u) {
            $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$u]);
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'AVISO: limpieza incompleta (' . $e->getMessage() . "). Buscar filas con prefijo zz_test_.\n");
    }
}
register_shutdown_function(function () use ($db, &$ids, &$archivosLocales, &$formId, $LOG) {
    limpiar($db, $ids, $archivosLocales, $formId);
    @unlink($LOG);
});

// ─────────────────────────────────────────────────────────────────────────────
// Archivos de prueba
// ─────────────────────────────────────────────────────────────────────────────
$tmpFiles = [];
function tmp(string $contenido): string {
    global $tmpFiles;
    $f = tempnam(sys_get_temp_dir(), 'zzt');
    file_put_contents($f, $contenido);
    $tmpFiles[] = $f;
    return $f;
}
register_shutdown_function(function () use (&$tmpFiles) {
    foreach ($tmpFiles as $f) {
        @unlink($f);
    }
});
$PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
$PNG = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$ZIP = "PK\x05\x06" . str_repeat("\0", 18);   // ZIP vacío válido (application/zip)
$pdfPath = tmp($PDF);
$pngPath = tmp($PNG);
$zipPath = tmp($ZIP);
$htmlPath = tmp('<html><script>alert(1)</script></html>');

/** Peticiones que llegaron al Cloudinary simulado, como arrays. */
function peticiones(string $log): array {
    $out = [];
    foreach (@file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
        $out[] = json_decode($l, true);
    }
    return $out;
}

/** Última petición recibida por el Cloudinary simulado. */
function ultima(string $log): array {
    $todas = peticiones($log);
    return $todas ? $todas[count($todas) - 1] : [];
}

$E = crear_usuario($db, 'cld', $PASS, $ids);
$ce = new Cliente($BASE);
$ce->login($E['email'], $PASS);
$tokE = $ce->csrf('/empresa/perfil.php');

$db->prepare("INSERT INTO conversaciones (titulo, empresa_id, iniciada_por, categoria, estado, ultimo_mensaje_at) VALUES ('zz_test adjuntos', ?, 'empresa', 'consulta', 'abierta', NOW())")
   ->execute([$E['eid']]);
$conv = (int) $db->lastInsertId();
$db->prepare("INSERT INTO mensajes_v2 (conversacion_id, remitente_id, remitente_tipo, contenido) VALUES (?, ?, 'empresa', 'mensaje con adjuntos')")
   ->execute([$conv, $E['uid']]);
$msg = (int) $db->lastInsertId();

function adjuntar(Cliente $c, string $tok, int $msg, string $path, string $mime, string $nombre): array {
    return $c->multipart('/api/comunicaciones/adjuntar.php', [
        CSRF_TOKEN_NAME => $tok, 'mensaje_id' => $msg, 'archivos[0]' => new CURLFile($path, $mime, $nombre),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\nFirma de Cloudinary\n";

prueba('coincide con el ejemplo oficial de la documentación de Cloudinary', function () {
    // https://cloudinary.com/documentation/authentication_signatures
    $firma = cloudinary_signature([
        'eager'     => 'w_400,h_300,c_pad|w_260,h_200,c_crop',
        'public_id' => 'sample_image',
        'timestamp' => 1315060510,
    ], 'abcd');
    igual('bfd09f95f331f558cbd1320e67aa8d488770583e', $firma);
});

prueba('la firma ignora file / api_key / resource_type / cloud_name y los valores vacíos', function () {
    $a = cloudinary_signature(['timestamp' => 1, 'folder' => 'x'], 's');
    $b = cloudinary_signature(['timestamp' => 1, 'folder' => 'x', 'file' => 'zzz', 'api_key' => 'k', 'resource_type' => 'raw', 'cloud_name' => 'c', 'vacio' => ''], 's');
    igual($a, $b);
});

prueba('el nombre enviado a Cloudinary lleva siempre la extensión del MIME, no la del cliente', function () {
    igual('shell.pdf', cloudinary_safe_upload_name('shell.php', 'pdf'));
    igual('a_b.docx', cloudinary_safe_upload_name('a b.exe', 'docx'));
    igual('archivo.pdf', cloudinary_safe_upload_name('....', 'pdf'));
    verdadero(strpos(cloudinary_safe_upload_name('../../etc/passwd', 'txt'), '/') === false, 'el nombre conserva barras');
});

echo "\nAdjuntos del Centro de Comunicaciones → Cloudinary\n";

prueba('un PDF se sube como "raw", con carpeta, firma válida y extensión .pdf', function () use ($ce, $tokE, $msg, $pdfPath, $LOG, $db) {
    $r = adjuntar($ce, $tokE, $msg, $pdfPath, 'application/pdf', 'Informe Final 2026.pdf');
    igual(200, $r['code'], $r['body']);
    $p = ultima($LOG);
    igual('raw', $p['resource_type']);
    igual(CLOUD, $p['cloud']);
    verdadero($p['firma_ok'] && $p['api_key_ok'], 'la firma o la api_key no son válidas');
    igual('parque_industrial/mensajes', $p['params']['folder']);
    igual('Informe_Final_2026.pdf', $p['nombre']);
    igual('1', $p['params']['use_filename']);
    $url = $r['json']['adjuntos'][0]['url'];
    verdadero(strpos($url, 'http://localhost:' . MOCK_PORT . '/files/parque_industrial/mensajes/') === 0, "URL inesperada: $url");
    igual($url, $db->query("SELECT archivo_url FROM adjuntos_mensajes WHERE mensaje_id = $msg ORDER BY id DESC LIMIT 1")->fetchColumn(), 'la base debe guardar la URL de Cloudinary');
});

prueba('un ZIP también va a Cloudinary como "raw" (antes iba a disco local)', function () use ($ce, $tokE, $msg, $zipPath, $LOG) {
    $r = adjuntar($ce, $tokE, $msg, $zipPath, 'application/zip', 'planos.zip');
    igual(200, $r['code'], $r['body']);
    $p = ultima($LOG);
    igual('raw', $p['resource_type']);
    igual('planos.zip', $p['nombre']);
    verdadero($p['firma_ok'], 'firma inválida');
});

prueba('una imagen se sube como "image"', function () use ($ce, $tokE, $msg, $pngPath, $LOG) {
    $r = adjuntar($ce, $tokE, $msg, $pngPath, 'image/png', 'logo.png');
    igual(200, $r['code'], $r['body']);
    $p = ultima($LOG);
    igual('image', $p['resource_type']);
    igual('logo.png', $p['nombre']);
    verdadero($p['firma_ok'], 'firma inválida');
});

prueba('un PHP disfrazado de PDF llega a Cloudinary como "shell.pdf", nunca "shell.php"', function () use ($ce, $tokE, $msg, $LOG) {
    $malo = tmp("%PDF-1.4\n<?php system(\$_GET['c']); ?>\n");
    $r = adjuntar($ce, $tokE, $msg, $malo, 'application/x-php', 'shell.php');
    igual(200, $r['code'], $r['body']);
    $p = ultima($LOG);
    igual('shell.pdf', $p['nombre']);
    verdadero(strpos($r['json']['adjuntos'][0]['url'], '.php') === false, 'la URL guardada contiene .php');
});

prueba('un tipo no permitido (HTML) se rechaza y no llega a Cloudinary', function () use ($ce, $tokE, $msg, $htmlPath, $LOG) {
    $antes = count(peticiones($LOG));
    $r = adjuntar($ce, $tokE, $msg, $htmlPath, 'text/html', 'pagina.html');
    igual(415, $r['code']);
    igual($antes, count(peticiones($LOG)), 'el archivo rechazado se envió a Cloudinary');
});

prueba('si Cloudinary falla (HTTP 500) se guarda en disco local y el adjunto sigue siendo accesible', function () use ($ce, $tokE, $msg, $pdfPath, $LOG, $db, &$archivosLocales, $BASE) {
    $r = adjuntar($ce, $tokE, $msg, $pdfPath, 'application/pdf', 'fallar_uno.pdf');
    igual(200, $r['code'], $r['body']);
    $url = $r['json']['adjuntos'][0]['url'];
    verdadero(strpos($url, '/uploads/mensajes/') !== false, "no cayó a disco local: $url");
    $archivo = $db->query("SELECT archivo_url FROM adjuntos_mensajes WHERE mensaje_id = $msg ORDER BY id DESC LIMIT 1")->fetchColumn();
    verdadero(strpos($archivo, 'http') !== 0 && substr($archivo, -4) === '.pdf', "en la base debe ir solo el nombre local, no una URL: $archivo");
    $archivosLocales[] = BASEPATH . '/public/uploads/mensajes/' . $archivo;
    verdadero(is_file(BASEPATH . '/public/uploads/mensajes/' . $archivo), 'el archivo no está en disco');
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true]);
    curl_exec($c);
    igual(200, curl_getinfo($c, CURLINFO_HTTP_CODE), 'el adjunto local no se puede descargar');
    curl_close($c);
});

prueba('la API de la conversación devuelve URLs utilizables (absolutas de Cloudinary y locales resueltas)', function () use ($ce, $conv) {
    $r = $ce->json('GET', "/api/comunicaciones/conversacion.php?id=$conv");
    igual(200, $r['code']);
    $urls = [];
    foreach ($r['json']['mensajes'] as $m) {
        foreach ($m['adjuntos'] as $a) {
            $urls[] = $a['url'];
        }
    }
    verdadero(count($urls) >= 5, 'faltan adjuntos en la respuesta (' . count($urls) . ')');
    foreach ($urls as $u) {
        verdadero(preg_match('#^https?://#', $u) === 1, "URL relativa o vacía: '$u'");
    }
    verdadero((bool) array_filter($urls, fn($u) => strpos($u, ':' . MOCK_PORT . '/files/') !== false), 'no hay URLs de Cloudinary');
    verdadero((bool) array_filter($urls, fn($u) => strpos($u, '/uploads/mensajes/') !== false), 'no hay URLs locales');
});

echo "\nFormularios y \"Presentar proyecto\" → Cloudinary\n";

prueba('la respuesta de un formulario con archivo guarda la URL de Cloudinary y se muestra bien', function () use ($ce, $tokE, $pdfPath, $LOG, $db, &$formId, $E, $BASE) {
    $db->exec("INSERT INTO formularios_dinamicos (titulo, estado) VALUES ('zz_test form archivo', 'publicado')");
    $formId = (int) $db->lastInsertId();
    $db->prepare("INSERT INTO formulario_preguntas (formulario_id, tipo, etiqueta, requerido, orden) VALUES (?, 'archivo', 'Constancia', 0, 1)")->execute([$formId]);
    $preg = (int) $db->lastInsertId();

    $r = $ce->multipart("/empresa/formulario_dinamico.php?id=$formId", [
        CSRF_TOKEN_NAME => $tokE, 'accion' => 'guardar', "campo_$preg" => new CURLFile($pdfPath, 'application/pdf', 'constancia.pdf'),
    ]);
    igual(200, $r['code']);
    $p = ultima($LOG);
    igual('raw', $p['resource_type']);
    igual('parque_industrial/formularios', $p['params']['folder']);
    verdadero($p['firma_ok'], 'firma inválida');
    $json = json_decode($db->query("SELECT respuestas FROM formulario_respuestas WHERE formulario_id = $formId AND empresa_id = {$E['eid']}")->fetchColumn(), true);
    $valor = $json[$preg] ?? '';
    verdadero(strpos($valor, 'http://localhost:' . MOCK_PORT . '/files/parque_industrial/formularios/') === 0, "valor guardado inesperado: $valor");
    $html = $ce->get("/empresa/formulario_dinamico.php?id=$formId")['body'];
    verdadero(strpos($html, 'href="' . $valor . '"') !== false || strpos($html, htmlspecialchars($valor, ENT_QUOTES)) !== false, 'la página no enlaza al archivo de Cloudinary');
    verdadero(strpos($html, '/uploads/formularios/http') === false, 'la página antepone la ruta local a una URL absoluta');
});

prueba('"Presentar proyecto" envía sus adjuntos a Cloudinary', function () use ($pdfPath, $pngPath, $LOG, $db, $BASE) {
    $anon = new Cliente($BASE);
    $tok = $anon->csrf('/presentar-proyecto.php');
    $antes = count(peticiones($LOG));
    $r = $anon->multipart('/presentar-proyecto.php', [
        CSRF_TOKEN_NAME => $tok, 'contacto' => 'zz_test Titular', 'email' => 'zz_test_proyecto@test.local',
        'resumen_proyecto' => 'Proyecto de prueba', 'nombre_empresa' => 'zz_test',
        'archivo_1' => new CURLFile($pdfPath, 'application/pdf', 'proyecto.pdf'),
        'archivo_2' => new CURLFile($pngPath, 'image/png', 'croquis.png'),
    ]);
    igual(200, $r['code']);
    $nuevas = array_slice(peticiones($LOG), $antes);
    igual(2, count($nuevas), 'debían subirse 2 archivos');
    igual(['raw', 'image'], array_column($nuevas, 'resource_type'));
    $fila = $db->query("SELECT archivo_1, archivo_2 FROM solicitudes_proyecto WHERE email = 'zz_test_proyecto@test.local'")->fetch();
    verdadero($fila !== false, 'no se guardó la solicitud: ' . substr(strip_tags($r['body']), 0, 200));
    foreach (['archivo_1', 'archivo_2'] as $c) {
        verdadero(strpos($fila[$c], 'http://localhost:' . MOCK_PORT . '/files/parque_industrial/documento-proyectos/') === 0, "$c inesperado: {$fila[$c]}");
    }
});

resumen();
