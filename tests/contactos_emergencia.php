<?php
/**
 * Pruebas del directorio de contactos de emergencia (contra un servidor en marcha):
 * sitio público (public/contactos.php), panel de empresa (public/empresa/contactos.php) y ABM del
 * Ministerio (public/ministerio/contactos-emergencia.php).
 *
 *   php -S localhost:8080 -t public               (en otra terminal)
 *   php tests/contactos_emergencia.php             (o: npm run test:contactos)
 *
 * Requiere haber aplicado database/023_contactos_emergencia.sql. Comprueba la visibilidad de cada
 * contacto (público / sólo empresas / inactivo), que una empresa o un anónimo no puedan escribir
 * (aunque envíen un token CSRF válido de su propia sesión), las validaciones del alta y el escape
 * de HTML. No asume que existan los contactos de ejemplo: crea sus propias filas temporales
 * (prefijo "zz_test") y las borra al terminar, junto con los usuarios temporales.
 * Solo usar contra una base de desarrollo. Sale con código 1 si algo falla.
 */

require_once __DIR__ . '/_lib.php';
require_once BASEPATH . '/includes/contactos_emergencia.php';

$ids = ['usuarios' => [], 'empresas' => []];

function limpiar_contactos(PDO $db, array $ids): void {
    try {
        $db->exec("DELETE FROM contactos_emergencia WHERE nombre LIKE 'zz_test%'");
        foreach ($ids['empresas'] as $e) {
            $db->prepare('DELETE FROM empresas WHERE id = ?')->execute([$e]);
        }
        foreach ($ids['usuarios'] as $u) {
            $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$u]);
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'AVISO: limpieza incompleta (' . $e->getMessage() . "). Buscar filas con prefijo zz_test / zz_test_.\n");
    }
}

if (!contactos_tabla_disponible($db)) {
    fwrite(STDERR, "Falta la tabla contactos_emergencia: aplicar database/023_contactos_emergencia.sql (con un usuario administrador de la base).\n");
    exit(2);
}

register_shutdown_function(function () use ($db, &$ids) {
    limpiar_contactos($db, $ids);
});

exigir_servidor($BASE);

$PANEL = '/ministerio/contactos-emergencia.php';
$M = crear_usuario($db, 'min', $PASS, $ids, 'ministerio');
$E = crear_usuario($db, 'emp', $PASS, $ids);

/** Contactos cuyo nombre coincide con el patrón LIKE. */
function contactos_de(PDO $db, string $like): array {
    $s = $db->prepare('SELECT * FROM contactos_emergencia WHERE nombre LIKE ? ORDER BY id');
    $s->execute([$like]);
    return $s->fetchAll();
}
/** Guarda un contacto desde el formulario del Ministerio (POST con token CSRF). */
function guardar(Cliente $c, array $campos, string $panel = '/ministerio/contactos-emergencia.php'): array {
    $tok = $c->csrf($panel);
    return $c->post($panel, array_merge([CSRF_TOKEN_NAME => $tok, 'accion' => 'guardar'], $campos));
}
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$ajenosAntes = (int) $db->query("SELECT COUNT(*) FROM contactos_emergencia WHERE nombre NOT LIKE 'zz_test%'")->fetchColumn();

echo "\n== Sitio público ==\n";
$anon = new Cliente($BASE);
prueba('contactos.php responde 200 sin sesión', function () use ($anon) {
    igual(200, $anon->get('/contactos.php')['code']);
});
prueba('lista todos los contactos públicos activos, con enlace tel:', function () use ($anon, $db) {
    $b = $anon->get('/contactos.php')['body'];
    $filas = $db->query("SELECT nombre, telefono FROM contactos_emergencia WHERE activo = 1 AND visibilidad = 'publico'")->fetchAll();
    foreach ($filas as $f) {
        verdadero(str_contains($b, esc($f['nombre'])), 'falta ' . $f['nombre']);
        verdadero(str_contains($b, 'href="' . contactos_tel_href($f['telefono']) . '"'), 'falta el enlace tel: de ' . $f['nombre']);
    }
});
prueba('el menú, el pie y la portada enlazan a Contactos', function () use ($anon) {
    $b = $anon->get('/contactos.php')['body'];
    verdadero(substr_count($b, 'contactos.php') >= 2, 'menos de 2 enlaces a contactos.php (menú + pie)');
    verdadero(str_contains($b, 'Contactos de emergencia'), 'falta el título o las migas de pan');
    verdadero(str_contains($anon->get('/index.php')['body'], 'contactos.php'), 'la portada no enlaza a contactos.php');
});

echo "\n== Permisos ==\n";
prueba('un anónimo no ve el panel del Ministerio', function () use ($anon, $PANEL) {
    $r = $anon->get($PANEL);
    verdadero($r['code'] === 302 || str_contains($r['url'], 'login'), "código {$r['code']}");
    verdadero(!str_contains($anon->get($PANEL, true)['body'], 'Nuevo contacto'), 'el anónimo ve el formulario');
});
prueba('un anónimo no ve el panel de empresa', function () use ($anon) {
    $r = $anon->get('/empresa/contactos.php', true);
    verdadero(str_contains($r['url'], 'login'), 'no redirigió a login: ' . $r['url']);
});

$emp = new Cliente($BASE);
$emp->login($E['email'], $PASS, '/empresa/dashboard.php');
$min = new Cliente($BASE);
$min->login($M['email'], $PASS, '/ministerio/dashboard.php');

prueba('una empresa no entra al panel del Ministerio', function () use ($emp, $PANEL) {
    verdadero(!str_contains($emp->get($PANEL, true)['body'], 'Nuevo contacto'), 'la empresa ve el ABM');
});
prueba('el Ministerio no entra al panel de empresa', function () use ($min) {
    $r = $min->get('/empresa/contactos.php', true);
    verdadero(!str_contains($r['url'], '/empresa/contactos.php'), 'el Ministerio ve el panel de empresa');
});
prueba('una empresa con su propio token CSRF no puede crear contactos', function () use ($emp, $db, $PANEL) {
    $tok = $emp->csrf('/empresa/cambiar-contrasena.php');
    $emp->post($PANEL, [CSRF_TOKEN_NAME => $tok, 'accion' => 'guardar', 'nombre' => 'zz_test_hack', 'telefono' => '1']);
    igual(0, count(contactos_de($db, 'zz_test_hack')), 'se creó un contacto desde una empresa');
});
prueba('un POST del Ministerio sin token CSRF se ignora', function () use ($min, $db, $PANEL) {
    $min->post($PANEL, ['accion' => 'guardar', 'nombre' => 'zz_test_sincsrf', 'telefono' => '1']);
    igual(0, count(contactos_de($db, 'zz_test_sincsrf')));
});

echo "\n== Panel del Ministerio ==\n";
prueba('el panel carga con el formulario', function () use ($min, $PANEL) {
    $r = $min->get($PANEL);
    igual(200, $r['code']);
    foreach (['Nuevo contacto', 'name="categoria"', 'name="visibilidad"'] as $t) {
        verdadero(str_contains($r['body'], $t), "falta {$t}");
    }
});
prueba('alta de un contacto público', function () use ($min, $db) {
    $r = guardar($min, ['categoria' => 'electricidad', 'nombre' => 'zz_test_pub', 'telefono' => '(0383) 443-1234',
        'telefono_alt' => '0800-555', 'descripcion' => 'desc', 'visibilidad' => 'publico', 'orden' => '5', 'activo' => '1']);
    igual(302, $r['code']);
    $f = contactos_de($db, 'zz_test_pub');
    igual(1, count($f), 'filas');
    igual('electricidad', $f[0]['categoria']);
    igual('publico', $f[0]['visibilidad']);
    igual('0800-555', $f[0]['telefono_alt']);
    igual(1, (int) $f[0]['activo']);
});
prueba('el contacto público aparece en el sitio con el teléfono normalizado en tel:', function () use ($anon) {
    $b = $anon->get('/contactos.php')['body'];
    verdadero(str_contains($b, 'zz_test_pub'), 'no aparece');
    verdadero(str_contains($b, 'href="tel:03834431234"'), 'tel: mal armado');
    verdadero(str_contains($b, 'Energía eléctrica'), 'falta el título de la categoría');
});
prueba('un contacto sólo-empresas no es público pero sí lo ve la empresa (con insignia)', function () use ($min, $anon, $emp) {
    guardar($min, ['categoria' => 'parque', 'nombre' => 'zz_test_priv', 'telefono' => '999', 'visibilidad' => 'empresas', 'activo' => '1']);
    verdadero(!str_contains($anon->get('/contactos.php')['body'], 'zz_test_priv'), 'se filtró al sitio público');
    $r = $emp->get('/empresa/contactos.php');
    igual(200, $r['code']);
    verdadero(str_contains($r['body'], 'zz_test_priv'), 'la empresa no lo ve');
    verdadero(str_contains($r['body'], 'zz_test_pub'), 'la empresa no ve los públicos');
    verdadero(str_contains($r['body'], 'bi-lock-fill'), 'falta la insignia "Empresas"');
});
prueba('editar actualiza la fila sin duplicarla y precarga el formulario', function () use ($min, $db, $PANEL) {
    $id = (int) contactos_de($db, 'zz_test_pub')[0]['id'];
    verdadero(str_contains($min->get("$PANEL?editar=$id")['body'], 'value="zz_test_pub"'), 'el formulario no se precarga al editar');
    guardar($min, ['id' => $id, 'categoria' => 'agua', 'nombre' => 'zz_test_pub', 'telefono' => '222', 'visibilidad' => 'publico', 'activo' => '1'], "$PANEL?editar=$id");
    $f = contactos_de($db, 'zz_test_pub');
    igual(1, count($f), 'duplicó la fila');
    igual('agua', $f[0]['categoria']);
    igual('222', $f[0]['telefono']);
    igual(null, $f[0]['telefono_alt'], 'un teléfono alternativo vacío debe guardarse como NULL');
});
prueba('desactivar (sin marcar "activo") lo oculta del sitio y del panel de empresa', function () use ($min, $anon, $emp, $db) {
    $id = (int) contactos_de($db, 'zz_test_pub')[0]['id'];
    guardar($min, ['id' => $id, 'categoria' => 'agua', 'nombre' => 'zz_test_pub', 'telefono' => '222', 'visibilidad' => 'publico']);
    igual(0, (int) contactos_de($db, 'zz_test_pub')[0]['activo']);
    verdadero(!str_contains($anon->get('/contactos.php')['body'], 'zz_test_pub'), 'sigue en el sitio');
    verdadero(!str_contains($emp->get('/empresa/contactos.php')['body'], 'zz_test_pub'), 'sigue en el panel de empresa');
});
prueba('validación: nombre o teléfono vacío no inserta', function () use ($min, $db) {
    guardar($min, ['nombre' => '', 'telefono' => '123']);
    guardar($min, ['nombre' => 'zz_test_sintel', 'telefono' => '   ']);
    igual(0, count(contactos_de($db, 'zz_test_sintel')));
});
prueba('una categoría inválida cae en "otros" y un orden negativo pasa a 0', function () use ($min, $db) {
    guardar($min, ['categoria' => 'inventada', 'nombre' => 'zz_test_cat', 'telefono' => '1', 'orden' => '-9', 'activo' => '1']);
    $f = contactos_de($db, 'zz_test_cat');
    igual('otros', $f[0]['categoria']);
    igual(0, (int) $f[0]['orden']);
});
prueba('se recortan los campos largos (nombre 150, teléfono 50, descripción 255)', function () use ($min, $db) {
    guardar($min, ['nombre' => 'zz_test_' . str_repeat('n', 300), 'telefono' => str_repeat('9', 90), 'descripcion' => str_repeat('d', 400), 'activo' => '1']);
    $f = contactos_de($db, 'zz_test_nnn%');
    igual(1, count($f));
    igual(150, mb_strlen($f[0]['nombre']));
    igual(50, mb_strlen($f[0]['telefono']));
    igual(255, mb_strlen($f[0]['descripcion']));
});
prueba('el HTML del nombre y la descripción se escapa en el sitio, en empresa y en el panel', function () use ($min, $anon, $emp, $PANEL) {
    guardar($min, ['nombre' => 'zz_test_<script>alert(1)</script>', 'telefono' => '1', 'descripcion' => '"><img src=x onerror=alert(2)>', 'activo' => '1']);
    foreach ([[$anon, '/contactos.php'], [$emp, '/empresa/contactos.php'], [$min, $PANEL]] as [$c, $ruta]) {
        $b = $c->get($ruta)['body'];
        verdadero(!str_contains($b, '<script>alert(1)'), "script sin escapar en $ruta");
        verdadero(!str_contains($b, '<img src=x onerror'), "img sin escapar en $ruta");
        verdadero(str_contains($b, '&lt;script&gt;alert(1)'), "no se muestra el texto escapado en $ruta");
    }
});
prueba('dentro de una categoría se respeta el orden ascendente', function () use ($min, $anon) {
    guardar($min, ['categoria' => 'gas', 'nombre' => 'zz_test_gas_B', 'telefono' => '2', 'orden' => '2', 'activo' => '1']);
    guardar($min, ['categoria' => 'gas', 'nombre' => 'zz_test_gas_A', 'telefono' => '1', 'orden' => '9', 'activo' => '1']);
    $b = $anon->get('/contactos.php')['body'];
    verdadero(strpos($b, 'zz_test_gas_B') < strpos($b, 'zz_test_gas_A'), 'orden incorrecto');
});
prueba('eliminar borra la fila y no rompe con un id inexistente', function () use ($min, $db, $PANEL) {
    $id = (int) contactos_de($db, 'zz_test_priv')[0]['id'];
    $min->post($PANEL, [CSRF_TOKEN_NAME => $min->csrf($PANEL), 'accion' => 'eliminar', 'id' => $id]);
    igual(0, count(contactos_de($db, 'zz_test_priv')));
    igual(302, $min->post($PANEL, [CSRF_TOKEN_NAME => $min->csrf($PANEL), 'accion' => 'eliminar', 'id' => 99999999])['code']);
});
prueba('las tres pantallas no muestran errores de PHP', function () use ($anon, $emp, $min, $PANEL) {
    foreach ([[$anon, '/contactos.php'], [$emp, '/empresa/contactos.php'], [$min, $PANEL]] as [$c, $ruta]) {
        $b = $c->get($ruta)['body'];
        verdadero(!preg_match('/(Warning|Notice|Fatal error|Deprecated|Parse error)<\/b>|<b>(Warning|Notice|Deprecated)/', $b), "error de PHP visible en $ruta");
    }
});
prueba('los contactos que no son de la prueba quedaron intactos', function () use ($db, $ajenosAntes) {
    igual($ajenosAntes, (int) $db->query("SELECT COUNT(*) FROM contactos_emergencia WHERE nombre NOT LIKE 'zz_test%'")->fetchColumn());
});

resumen();
