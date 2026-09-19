<?php
/**
 * Herramienta de verificación de refactors (no es una prueba): guarda en una carpeta el JavaScript
 * EFECTIVO que sirven las páginas grandes (inline y externo, en el orden en que se cargan), para
 * comparar "antes" y "después" de mover JavaScript a public/js/.
 *
 *   php tests/capturar_js.php <carpeta_salida>
 *
 * Con un servidor en marcha (php -S localhost:8080 -t public). Crea usuarios zz_test_* y los borra.
 * Cada página produce <carpeta>/<nombre>.js con todos los <script> concatenados: los inline tal cual y
 * los de /js/ propios sustituidos por su contenido, separados por una marca "//=== ... ===".
 */

require_once __DIR__ . '/_lib.php';

$salida = $argv[1] ?? '';
if ($salida === '') {
    fwrite(STDERR, "Uso: php tests/capturar_js.php <carpeta_salida>\n");
    exit(2);
}
@mkdir($salida, 0777, true);

$ids = ['usuarios' => [], 'empresas' => []];
register_shutdown_function(function () use ($db, &$ids) {
    try {
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
        fwrite(STDERR, 'AVISO: limpieza incompleta: ' . $e->getMessage() . "\n");
    }
});

exigir_servidor($BASE);
$E = crear_usuario($db, 'cap_e', $PASS, $ids);
$M = crear_usuario($db, 'cap_m', $PASS, $ids, 'ministerio');
$ce = new Cliente($BASE);
$cm = new Cliente($BASE);
$ce->login($E['email'], $PASS);
$cm->login($M['email'], $PASS, '/ministerio/dashboard.php');

$formId = (int) $db->query('SELECT id FROM formularios_dinamicos ORDER BY id LIMIT 1')->fetchColumn();
$paginas = [
    'empresa_perfil'         => [$ce, '/empresa/perfil.php'],
    'ministerio_lotes'       => [$cm, '/ministerio/lotes.php'],
    'sitio_inicio'           => [$cm, '/ministerio/sitio-publico.php?tab=inicio'],
    'sitio_el_parque'        => [$cm, '/ministerio/sitio-publico.php?tab=el_parque'],
    'sitio_contacto'         => [$cm, '/ministerio/sitio-publico.php?tab=contacto'],
    'sitio_legal'            => [$cm, '/ministerio/sitio-publico.php?tab=legal'],
    'form_gestion_respuestas' => [$cm, "/ministerio/formulario-gestion.php?id=$formId&tab=respuestas"],
    'form_gestion_envios'    => [$cm, "/ministerio/formulario-gestion.php?id=$formId&tab=envios"],
    'form_gestion_enviar'    => [$cm, "/ministerio/formulario-gestion.php?id=$formId&tab=enviar"],
];

foreach ($paginas as $nombre => [$cli, $ruta]) {
    $r = $cli->get($ruta, true);
    if ($r['code'] !== 200) {
        fwrite(STDERR, "$nombre: HTTP {$r['code']}\n");
        exit(1);
    }
    $html = $r['body'];
    $js = '';
    // <script ...>cuerpo</script>, en orden de aparición
    preg_match_all('#<script\b([^>]*)>(.*?)</script>#is', $html, $m, PREG_SET_ORDER);
    foreach ($m as $s) {
        if (preg_match('#\bsrc\s*=\s*["\']([^"\']+)["\']#i', $s[1], $src)) {
            $url = html_entity_decode($src[1]);
            $path = parse_url($url, PHP_URL_PATH);
            // solo los JS propios de public/js/ (las librerías de vendor no cambian)
            if ($path !== null && preg_match('#/js/[^/]+\.js$#', $path)) {
                $js .= "//=== externo " . basename($path) . " ===\n" . $cli->get($path)['body'] . "\n";
            }
            continue;
        }
        $js .= "//=== inline ===\n" . $s[2] . "\n";
    }
    file_put_contents("$salida/$nombre.js", $js);
    file_put_contents("$salida/$nombre.html", $html);
    echo sprintf("%-26s %6d bytes de JS, %6d de HTML\n", $nombre, strlen($js), strlen($html));
}
