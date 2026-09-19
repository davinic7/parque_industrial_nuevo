<?php
/**
 * Pruebas de regresión de seguridad (sin navegador ni base de datos).
 *
 *   php tests/seguridad.php        (o: npm run test:seguridad)
 *
 * Cubre los hallazgos de la auditoría:
 *  - Ejecución remota de código por subida de archivos (extensión tomada del cliente).
 *  - Escapado de HTML (XSS).
 *  - Configuración endurecida (APP_ENV, cookie de sesión, .htaccess).
 *
 * Sale con código 1 si algo falla.
 */

define('BASEPATH', dirname(__DIR__));
require_once BASEPATH . '/includes/funciones.php';

$pasan = 0;
$fallan = [];

function prueba(string $nombre, callable $fn): void {
    global $pasan, $fallan;
    try {
        $fn();
        $pasan++;
        echo "  OK   $nombre\n";
    } catch (Throwable $e) {
        $fallan[] = [$nombre, $e->getMessage()];
        echo "  FAIL $nombre\n       " . $e->getMessage() . "\n";
    }
}

function igual($esperado, $real, string $msg = ''): void {
    if ($esperado !== $real) {
        throw new RuntimeException(
            ($msg !== '' ? "$msg: " : '') . 'esperado ' . var_export($esperado, true) . ', obtenido ' . var_export($real, true)
        );
    }
}

function verdadero($cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

/** Contenido de un archivo del proyecto (sin fallar si no existe). */
function leer(string $rel): string {
    $ruta = BASEPATH . '/' . $rel;
    return is_file($ruta) ? (string) file_get_contents($ruta) : '';
}

echo "\nSubidas de archivos\n";

prueba('safe_extension_for_mime: tipos permitidos dan su extensión fija', function () {
    $esperado = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'application/pdf' => 'pdf', 'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/zip' => 'zip', 'text/plain' => 'txt', 'text/csv' => 'csv',
    ];
    foreach ($esperado as $mime => $ext) {
        igual($ext, safe_extension_for_mime($mime), $mime);
    }
});

prueba('safe_extension_for_mime: tipos ejecutables o desconocidos se rechazan (null)', function () {
    foreach (['application/x-httpd-php', 'text/x-php', 'application/x-php', 'text/html',
              'application/x-sh', 'application/octet-stream', 'application/javascript', ''] as $mime) {
        igual(null, safe_extension_for_mime($mime), "MIME '$mime'");
    }
});

prueba('un PHP con cabecera %PDF engaña a finfo, pero la extensión final es pdf y nunca php', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'seg');
    file_put_contents($tmp, "%PDF-1.4\n<?php echo 'x'; ?>\n");
    try {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        // Documenta el vector: finfo lo acepta como PDF, por eso la extensión no puede salir del nombre del cliente.
        igual('application/pdf', $mime, 'finfo debería creerlo un PDF (es el vector del ataque)');
        igual('pdf', safe_extension_for_mime($mime), 'extensión derivada del MIME');
    } finally {
        @unlink($tmp);
    }
});

prueba('ningún código arma el nombre de un archivo subido con la extensión que manda el cliente', function () {
    $infractores = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASEPATH, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $archivo) {
        $ruta = str_replace('\\', '/', $archivo->getPathname());
        if (substr($ruta, -4) !== '.php'
            || strpos($ruta, '/node_modules/') !== false
            || strpos($ruta, '/tests/') !== false
            || strpos($ruta, '/public/uploads/') !== false) {
            continue;
        }
        foreach (file($ruta) as $n => $linea) {
            $usaPathinfoExt = strpos($linea, 'PATHINFO_EXTENSION') !== false;
            $vieneDelCliente = preg_match('/\[[\'"]name[\'"]\]|\$file_name|\$_FILES/', $linea);
            if ($usaPathinfoExt && $vieneDelCliente && strpos($linea, 'safe-ext-ok') === false) {
                $infractores[] = substr($ruta, strlen(BASEPATH) + 1) . ':' . ($n + 1);
            }
        }
    }
    verdadero(!$infractores, 'Extensión tomada del nombre del cliente en: ' . implode(', ', $infractores)
        . ' (usar safe_extension_for_mime; si es un uso legítimo, marcar la línea con "safe-ext-ok")');
});

prueba('formulario-nuevo/editar no confían en el Content-Type del cliente ($_FILES[..][\'type\'])', function () {
    foreach (['public/ministerio/formulario-nuevo.php', 'public/ministerio/formulario-editar.php'] as $rel) {
        $src = leer($rel);
        verdadero($src !== '', "No se encontró $rel");
        verdadero(strpos($src, "['type']") === false, "$rel usa el 'type' del cliente");
        verdadero(strpos($src, 'FILEINFO_MIME_TYPE') !== false, "$rel no verifica el MIME real con finfo");
    }
});

echo "\nXSS\n";

prueba('e() escapa etiquetas y comillas', function () {
    $salida = e('<img src=x onerror="alert(1)">\'');
    verdadero(strpos($salida, '<') === false && strpos($salida, '>') === false, 'quedaron < o > sin escapar');
    verdadero(strpos($salida, '"') === false, 'quedaron comillas dobles sin escapar');
    igual('', e(null), 'e(null)');
});

echo "\nConfiguración\n";

prueba('APP_ENV por defecto es production (falla cerrado)', function () {
    verdadero(strpos(leer('config/config.php'), "env('APP_ENV', 'production')") !== false,
        "el valor por defecto de APP_ENV debe ser 'production'");
});

prueba('cookie de sesión con SameSite, HttpOnly y modo estricto', function () {
    $cfg = leer('config/config.php');
    foreach (['session.cookie_samesite', 'session.cookie_httponly', 'session.use_strict_mode'] as $clave) {
        verdadero(strpos($cfg, $clave) !== false, "falta $clave en config/config.php");
    }
});

prueba('.htaccess presentes: raíz, public/ y public/uploads/', function () {
    foreach (['.htaccess', 'public/.htaccess', 'public/uploads/.htaccess'] as $rel) {
        verdadero(is_file(BASEPATH . '/' . $rel), "falta $rel");
    }
});

prueba('public/uploads/.htaccess bloquea la ejecución de scripts', function () {
    $ht = leer('public/uploads/.htaccess');
    verdadero(strpos($ht, 'php_flag engine off') !== false, 'no desactiva el motor PHP');
    verdadero(strpos($ht, 'Require all denied') !== false, 'no niega el acceso a scripts');
});

prueba('el dockerfile habilita AllowOverride (si no, Apache ignora los .htaccess)', function () {
    verdadero(strpos(leer('dockerfile'), 'AllowOverride All') !== false, 'falta AllowOverride All');
});

echo "\n";
$total = $pasan + count($fallan);
echo "Resultado: $pasan de $total pruebas OK\n\n";
exit($fallan ? 1 : 0);
