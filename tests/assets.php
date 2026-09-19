<?php
/**
 * Pruebas de recursos estáticos (sin navegador): que el sitio no dependa de un CDN.
 *
 *   php -S localhost:8080 -t public      (en otra terminal)
 *   php tests/assets.php                 (o: npm run test:assets)
 *
 * Recorre las páginas públicas y los paneles de empresa y Ministerio (con usuarios temporales
 * zz_test_*, que borra al terminar) y comprueba:
 *  - que ningún <script>/<link> cargue desde un host externo (salvo reCAPTCHA);
 *  - que cada recurso local responda 200 y no esté vacío;
 *  - que las fuentes e imágenes referenciadas con url() desde los CSS locales existan.
 * Los mapas (teselas de OpenStreetMap/ArcGIS) y reCAPTCHA son servicios externos por naturaleza.
 *
 * Sale con código 1 si algo falla.
 */

require_once __DIR__ . '/_lib.php';

$ids = ['usuarios' => [], 'empresas' => []];
register_shutdown_function(function () use ($db, &$ids) {
    try {
        foreach ($ids['empresas'] as $e) {
            foreach (['visitas_empresa', 'formulario_respuestas'] as $t) {
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
});

exigir_servidor($BASE);

$E = crear_usuario($db, 'assets_e', $PASS, $ids);
$M = crear_usuario($db, 'assets_m', $PASS, $ids, 'ministerio');
$anon = new Cliente($BASE);
$ce = new Cliente($BASE);
$cm = new Cliente($BASE);
$ce->login($E['email'], $PASS);
$cm->login($M['email'], $PASS, '/ministerio/dashboard.php');

/** Hosts externos aceptados (servicios que no se pueden alojar localmente). */
const EXTERNOS_OK = ['www.google.com', 'www.gstatic.com'];

$paginas = [
    'público'    => [$anon, ['/', '/login.php', '/empresas.php', '/mapa.php', '/noticias.php', '/estadisticas.php', '/nosotros.php',
                             '/parque.php', '/el-parque.php', '/presentar-proyecto.php', '/recuperar.php']],
    'empresa'    => [$ce, ['/empresa/dashboard.php', '/empresa/formularios.php', '/empresa/mis-datos.php', '/empresa/perfil.php',
                           '/empresa/publicaciones.php', '/empresa/comunicaciones.php']],
    'ministerio' => [$cm, ['/ministerio/dashboard.php', '/ministerio/graficos.php', '/ministerio/lotes.php', '/ministerio/sitio-publico.php',
                           '/ministerio/empresa-metricas.php', '/ministerio/formularios.php', '/ministerio/comunicaciones.php',
                           '/ministerio/publicaciones.php', '/ministerio/empresas.php', '/ministerio/plantillas.php']],
];

$locales = [];   // ruta absoluta (path) => páginas que la usan
$externos = [];  // "página → url"

echo "\nPáginas: recursos que cargan\n";
foreach ($paginas as $grupo => [$cliente, $rutas]) {
    foreach ($rutas as $ruta) {
        prueba("$ruta ($grupo) carga y no depende de hosts externos", function () use ($cliente, $ruta, $BASE, &$locales, &$externos) {
            $r = $cliente->get($ruta, true);
            igual(200, $r['code'], 'la página no carga');
            verdadero(strpos($r['url'], 'login.php') === false || $ruta === '/login.php', "redirigió al login ($r[url])");
            preg_match_all('/<(?:script|link)\b[^>]*?\b(?:src|href)\s*=\s*["\']([^"\']+)["\']/i', $r['body'], $m);
            $propios = [];
            foreach ($m[1] as $url) {
                $url = html_entity_decode($url);
                if (strpos($url, '<?') !== false) {
                    $propios[] = "etiqueta PHP sin interpretar: $url";
                    continue;
                }
                // Valores que no son URLs (fragmentos de plantillas JavaScript dentro de <script>)
                if (preg_match('#^(data:|mailto:|tel:|javascript:|\#)#i', $url) || strpbrk($url, '<>+{}$ ') !== false) {
                    continue;
                }
                if (preg_match('#^(https?:)?//([^/]+)#i', $url, $h)) {
                    $host = strtolower($h[2]);
                    if ($host === parse_url($BASE, PHP_URL_HOST) . ':' . parse_url($BASE, PHP_URL_PORT) || $host === parse_url($BASE, PHP_URL_HOST)) {
                        $locales[parse_url($url, PHP_URL_PATH)][] = $ruta;
                        continue;
                    }
                    if (!in_array($host, EXTERNOS_OK, true)) {
                        $propios[] = $url;
                    }
                    continue;
                }
                // relativa o absoluta de ruta: resolver contra la página
                $path = parse_url($url, PHP_URL_PATH);
                if ($path === null || $path === '') {
                    continue;
                }
                if ($path[0] !== '/') {
                    $path = rtrim(dirname(parse_url($ruta, PHP_URL_PATH)), '/') . '/' . $path;
                }
                $locales[$path][] = $ruta;
            }
            verdadero(!$propios, 'carga recursos externos: ' . implode(', ', $propios));
        });
    }
}

echo "\nRecursos locales\n";

prueba('todos los <script>/<link> locales responden 200 y no están vacíos', function () use (&$locales, $anon) {
    verdadero(count($locales) > 10, 'se detectaron muy pocos recursos locales (' . count($locales) . ')');
    $fallos = [];
    foreach (array_keys($locales) as $path) {
        $r = $anon->get($path);
        if ($r['code'] !== 200 || strlen($r['body']) === 0) {
            $fallos[] = "$path → HTTP $r[code]" . ($r['code'] === 200 ? ' (vacío)' : '') . ' (usado en ' . $locales[$path][0] . ')';
        }
    }
    verdadero(!$fallos, implode("\n       ", $fallos));
});

prueba('los CSS locales de librerías (vendor) y sus fuentes/imágenes con url() existen', function () use (&$locales, $anon) {
    $css = array_filter(array_keys($locales), fn($p) => preg_match('#\.css$#', $p));
    verdadero(count($css) > 3, 'pocos CSS detectados');
    $revisados = 0;
    $fallos = [];
    foreach ($css as $path) {
        $r = $anon->get($path);
        preg_match_all('/url\(\s*[\'"]?([^\'")]+?)[\'"]?\s*\)/i', $r['body'], $m);
        foreach (array_unique($m[1]) as $u) {
            $u = html_entity_decode($u);
            if (preg_match('#^(data:|\#|about:)#i', $u)) {
                continue;
            }
            if (preg_match('#^(https?:)?//#i', $u)) {
                $fallos[] = "$path referencia un recurso externo: $u";
                continue;
            }
            $u = preg_replace('/[?#].*$/', '', $u);
            $dest = $u[0] === '/' ? $u : rtrim(dirname($path), '/') . '/' . $u;
            // normalizar ../ y ./
            $partes = [];
            foreach (explode('/', $dest) as $p) {
                if ($p === '' || $p === '.') continue;
                if ($p === '..') { array_pop($partes); continue; }
                $partes[] = $p;
            }
            $dest = '/' . implode('/', $partes);
            $revisados++;
            $x = $anon->get($dest);
            if ($x['code'] !== 200 || strlen($x['body']) === 0) {
                $fallos[] = "$path → $dest: HTTP $x[code]";
            }
        }
    }
    verdadero($revisados > 10, "se revisaron muy pocas referencias url() ($revisados)");
    verdadero(!$fallos, implode("\n       ", array_slice($fallos, 0, 15)));
});

prueba('las tipografías locales declaradas en fonts.css existen', function () use ($anon) {
    $css = $anon->get('/vendor/fonts/fonts.css');
    igual(200, $css['code']);
    preg_match_all("/url\('([^']+)'\)/", $css['body'], $m);
    igual(9, count($m[1]), 'fonts.css debería declarar 9 archivos');
    foreach ($m[1] as $f) {
        $r = $anon->get('/vendor/fonts/' . $f);
        igual(200, $r['code'], $f);
        verdadero(strlen($r['body']) > 1000, "$f demasiado chico");
    }
});

prueba('ninguna etiqueta <?= PUBLIC_URL ?> quedó dentro de código PHP (donde saldría literal en la página)', function () {
    // Una URL de /vendor escrita con la etiqueta corta solo funciona en HTML; dentro de una cadena,
    // heredoc o nowdoc PHP se imprime tal cual y el recurso da 404. Se revisa con el tokenizador.
    $infractores = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASEPATH, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $ruta = str_replace(DIRECTORY_SEPARATOR, '/', $f->getPathname());
        if (substr($ruta, -4) !== '.php' || preg_match('#/(node_modules|vendor|tests|public/uploads)/#', $ruta)) {
            continue;
        }
        $tokens = token_get_all((string) file_get_contents($ruta));
        $linea = 1;
        foreach ($tokens as $t) {
            if (is_array($t)) {
                $linea = $t[2];
                if ($t[0] !== T_INLINE_HTML && preg_match('/<\?=\s*(?:e\()?PUBLIC_URL/', $t[1])) {
                    $infractores[] = substr($ruta, strlen(BASEPATH) + 1) . ':' . $linea;
                }
                $linea += substr_count($t[1], "\n");
            } else {
                $linea += substr_count($t, "\n");
            }
        }
    }
    verdadero(!$infractores, 'etiqueta <?= sin interpretar dentro de PHP en: ' . implode(', ', $infractores));
});
prueba('ningún archivo del proyecto (PHP/JS/CSS propios) vuelve a apuntar a un CDN', function () {
    $patron = '#cdn\.jsdelivr\.net|cdnjs\.cloudflare\.com|unpkg\.com|fonts\.googleapis\.com|fonts\.gstatic\.com|cdn\.quilljs\.com#';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASEPATH . '/public', FilesystemIterator::SKIP_DOTS));
    $infractores = [];
    foreach ($it as $f) {
        $ruta = str_replace('\\', '/', $f->getPathname());
        if (strpos($ruta, '/public/vendor/') !== false || strpos($ruta, '/public/uploads/') !== false || !preg_match('#\.(php|js|css)$#', $ruta)) {
            continue;
        }
        if (preg_match($patron, (string) file_get_contents($ruta))) {
            $infractores[] = substr($ruta, strlen(BASEPATH) + 1);
        }
    }
    foreach (glob(BASEPATH . '/includes/*.php') + glob(BASEPATH . '/includes/partials/*.php') as $f) {
        if (preg_match($patron, (string) file_get_contents($f))) {
            $infractores[] = 'includes/' . basename($f);
        }
    }
    verdadero(!$infractores, 'referencias a CDN en: ' . implode(', ', $infractores));
});

resumen();
