<?php
/**
 * Pruebas de aislamiento entre empresas y de escritura (contra un servidor en marcha).
 *
 *   php -S localhost:8080 -t public      (en otra terminal)
 *   php tests/aislamiento.php            (o: npm run test:aislamiento)
 *
 * Variable opcional: TEST_BASE_URL (por defecto http://localhost:8080).
 *
 * Crea DOS empresas temporales (prefijo "zz_aislamiento_") con sus usuarios, inicia sesión como
 * cada una y comprueba que la empresa B no pueda leer ni modificar datos de la empresa A.
 * Al terminar borra todo lo que creó. Solo usar contra una base de desarrollo.
 *
 * Sale con código 1 si algo falla.
 */

require_once dirname(__DIR__) . '/config/config.php';

$BASE = rtrim(getenv('TEST_BASE_URL') ?: 'http://localhost:8080', '/');
$PASS = 'Aislamiento-' . bin2hex(random_bytes(4));
$db = getDB();

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

function verdadero($cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function igual($esperado, $real, string $msg = ''): void {
    if ($esperado !== $real) {
        throw new RuntimeException(
            ($msg !== '' ? "$msg: " : '') . 'esperado ' . var_export($esperado, true) . ', obtenido ' . var_export($real, true)
        );
    }
}

/** Cliente HTTP con su propio jar de cookies (una "sesión de navegador"). */
class Cliente {
    public string $jar;
    public function __construct(public string $base) {
        $this->jar = tempnam(sys_get_temp_dir(), 'jar');
    }
    public function __destruct() {
        @unlink($this->jar);
    }
    /** @return array{code:int, body:string, url:string} */
    public function pedir(string $metodo, string $ruta, array $datos = [], bool $seguir = false): array {
        $ch = curl_init($this->base . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_FOLLOWLOCATION => $seguir,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body = (string) curl_exec($ch);
        $r = ['code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => $body, 'url' => (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)];
        curl_close($ch);
        return $r;
    }
    public function get(string $ruta, bool $seguir = false): array {
        return $this->pedir('GET', $ruta, [], $seguir);
    }
    public function post(string $ruta, array $datos): array {
        return $this->pedir('POST', $ruta, $datos);
    }
    public function json(string $metodo, string $ruta, array $datos = []): array {
        $r = $this->pedir($metodo, $ruta, $datos);
        $r['json'] = json_decode($r['body'], true);
        return $r;
    }
    /** Token CSRF de una página (campo oculto). */
    public function csrf(string $ruta): string {
        $html = $this->get($ruta, true)['body'];
        if (!preg_match('/name="' . preg_quote(CSRF_TOKEN_NAME, '/') . '"\s+value="([^"]+)"/', $html, $m)
            && !preg_match('/value="([^"]+)"\s+name="' . preg_quote(CSRF_TOKEN_NAME, '/') . '"/', $html, $m)) {
            throw new RuntimeException("No se encontró el token CSRF en $ruta");
        }
        return html_entity_decode($m[1]);
    }
    public function login(string $email, string $pass): void {
        $token = $this->csrf('/login.php');
        $this->post('/login.php', [CSRF_TOKEN_NAME => $token, 'email' => $email, 'password' => $pass]);
        $r = $this->get('/empresa/dashboard.php');
        if ($r['code'] !== 200 || strpos($r['url'], 'login.php') !== false) {
            throw new RuntimeException("No se pudo iniciar sesión como $email (¿reCAPTCHA activo o servidor caído?)");
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Datos de prueba
// ─────────────────────────────────────────────────────────────────────────────
$ids = ['usuarios' => [], 'empresas' => []];
$convs = [];

function crear_empresa(PDO $db, string $tag, string $pass, array &$ids): array {
    $email = "zz_aislamiento_{$tag}_" . bin2hex(random_bytes(3)) . '@test.local';
    $db->prepare("INSERT INTO usuarios (email, password, rol, activo, email_verificado) VALUES (?, ?, 'empresa', 1, 1)")
       ->execute([$email, password_hash($pass, PASSWORD_DEFAULT)]);
    $uid = (int) $db->lastInsertId();
    $db->prepare("INSERT INTO empresas (usuario_id, nombre, estado) VALUES (?, ?, 'activa')")
       ->execute([$uid, "zz_aislamiento_$tag"]);
    $eid = (int) $db->lastInsertId();
    $ids['usuarios'][] = $uid;
    $ids['empresas'][] = $eid;
    return ['uid' => $uid, 'eid' => $eid, 'email' => $email];
}

function limpiar(PDO $db, array $ids, array $convs): void {
    try {
        foreach ($convs as $c) {
            $db->prepare('DELETE FROM adjuntos_mensajes WHERE mensaje_id IN (SELECT id FROM mensajes_v2 WHERE conversacion_id = ?)')->execute([$c]);
            $db->prepare('DELETE FROM mensajes_v2 WHERE conversacion_id = ?')->execute([$c]);
            $db->prepare('DELETE FROM comunicado_visto WHERE conversacion_id = ?')->execute([$c]);
            $db->prepare('DELETE FROM conversaciones WHERE id = ?')->execute([$c]);
        }
        foreach ($ids['empresas'] as $e) {
            foreach (['formulario_respuestas', 'publicaciones', 'empresa_imagenes', 'comunicado_visto', 'visitas_empresa', 'formulario_destinatarios'] as $t) {
                try { $db->prepare("DELETE FROM $t WHERE empresa_id = ?")->execute([$e]); } catch (Throwable $ex) { /* tabla opcional */ }
            }
            $db->prepare('DELETE FROM conversaciones WHERE empresa_id = ?')->execute([$e]);
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
        fwrite(STDERR, 'AVISO: limpieza incompleta (' . $e->getMessage() . "). Buscar filas con prefijo zz_aislamiento_.\n");
    }
}

register_shutdown_function(function () use ($db, &$ids, &$convs) {
    limpiar($db, $ids, $convs);
});

try {
    $r = (new Cliente($BASE))->get('/login.php');
    if ($r['code'] !== 200) {
        throw new RuntimeException('');
    }
} catch (Throwable $e) {
    fwrite(STDERR, "No hay servidor en $BASE. Inícielo con: php -S localhost:8080 -t public\n");
    exit(2);
}

$A = crear_empresa($db, 'A', $PASS, $ids);
$B = crear_empresa($db, 'B', $PASS, $ids);

// Conversación 1-a-1 de A con un mensaje del ministerio
$db->prepare("INSERT INTO conversaciones (titulo, empresa_id, iniciada_por, categoria, estado, ultimo_mensaje_at) VALUES ('zz_aislamiento privada A', ?, 'ministerio', 'consulta', 'abierta', NOW())")
   ->execute([$A['eid']]);
$convA = (int) $db->lastInsertId();
$convs[] = $convA;
$db->prepare("INSERT INTO mensajes_v2 (conversacion_id, remitente_id, remitente_tipo, contenido) VALUES (?, ?, 'ministerio', 'SECRETO-DE-A')")
   ->execute([$convA, $A['uid']]);
$msgA = (int) $db->lastInsertId();

// Comunicado global (empresa_id NULL) publicado por el ministerio
$db->prepare("INSERT INTO conversaciones (titulo, empresa_id, iniciada_por, categoria, estado, ultimo_mensaje_at) VALUES ('zz_aislamiento comunicado global', NULL, 'ministerio', 'comunicado', 'abierta', NOW())")
   ->execute();
$convGlobal = (int) $db->lastInsertId();
$convs[] = $convGlobal;
$db->prepare("INSERT INTO mensajes_v2 (conversacion_id, remitente_id, remitente_tipo, contenido) VALUES (?, ?, 'ministerio', 'COMUNICADO-OFICIAL')")
   ->execute([$convGlobal, $A['uid']]);

// Publicación e imagen de galería de A
$db->prepare("INSERT INTO publicaciones (empresa_id, usuario_id, titulo, slug, tipo, contenido, estado) VALUES (?, ?, 'zz_aislamiento pub A', ?, 'noticia', 'x', 'borrador')")
   ->execute([$A['eid'], $A['uid'], 'zz-aislamiento-' . bin2hex(random_bytes(3))]);
$pubA = (int) $db->lastInsertId();
$db->prepare("INSERT INTO empresa_imagenes (empresa_id, url, orden) VALUES (?, 'zz_aislamiento.jpg', 1)")->execute([$A['eid']]);
$imgA = (int) $db->lastInsertId();

$formId = (int) $db->query("SELECT id FROM formularios_dinamicos WHERE estado = 'publicado' ORDER BY id LIMIT 1")->fetchColumn();

$ca = new Cliente($BASE);
$cb = new Cliente($BASE);
$anon = new Cliente($BASE);
$ca->login($A['email'], $PASS);
$cb->login($B['email'], $PASS);
$tokB = $cb->csrf('/empresa/perfil.php');
$tokA = $ca->csrf('/empresa/perfil.php');

// ─────────────────────────────────────────────────────────────────────────────
echo "\nCentro de Comunicaciones: lectura entre empresas\n";

prueba('A puede leer su propia conversación (control)', function () use ($ca, $convA) {
    $r = $ca->json('GET', "/api/comunicaciones/conversacion.php?id=$convA");
    igual(200, $r['code'], 'A no puede leer su propio hilo');
    verdadero(strpos($r['body'], 'SECRETO-DE-A') !== false, 'A no ve su mensaje');
});

prueba('B no puede leer la conversación de A', function () use ($cb, $convA) {
    $r = $cb->json('GET', "/api/comunicaciones/conversacion.php?id=$convA");
    igual(403, $r['code']);
    verdadero(strpos($r['body'], 'SECRETO-DE-A') === false, 'el contenido de A se filtró a B');
});

prueba('la bandeja de B no lista conversaciones de A', function () use ($cb, $convA) {
    foreach (['abierta', 'archivada', 'cerrada'] as $estado) {
        $r = $cb->json('GET', "/api/comunicaciones/listar.php?estado=$estado&limit=200");
        verdadero(strpos($r['body'], 'zz_aislamiento privada A') === false, "la bandeja '$estado' de B lista la conversación de A");
    }
});

prueba('sin sesión, la API responde 401', function () use ($anon, $convA) {
    igual(401, $anon->json('GET', "/api/comunicaciones/conversacion.php?id=$convA")['code']);
    igual(401, $anon->json('GET', '/api/comunicaciones/listar.php')['code']);
    igual(401, $anon->json('GET', '/api/comunicaciones/badge.php')['code']);
});

echo "\nCentro de Comunicaciones: escritura entre empresas\n";

prueba('B no puede enviar mensajes en la conversación de A', function () use ($cb, $tokB, $convA, $db) {
    $r = $cb->json('POST', '/api/comunicaciones/enviar.php', [CSRF_TOKEN_NAME => $tokB, 'conversacion_id' => $convA, 'contenido' => 'INTRUSO-B']);
    igual(403, $r['code']);
    $n = $db->prepare("SELECT COUNT(*) FROM mensajes_v2 WHERE contenido = 'INTRUSO-B'");
    $n->execute();
    igual(0, (int) $n->fetchColumn(), 'el mensaje de B quedó guardado en el hilo de A');
});

prueba('B no puede marcar, archivar ni dejar borradores en el hilo de A', function () use ($cb, $tokB, $convA, $db) {
    foreach (['leida', 'no_leida', 'archivar', 'desarchivar'] as $accion) {
        $r = $cb->json('POST', '/api/comunicaciones/marcar.php', [CSRF_TOKEN_NAME => $tokB, 'conversacion_id' => $convA, 'accion' => $accion]);
        igual(403, $r['code'], "marcar.php accion=$accion");
    }
    $r = $cb->json('POST', '/api/comunicaciones/borrador.php', [CSRF_TOKEN_NAME => $tokB, 'conversacion_id' => $convA, 'contenido' => 'BORRADOR-B']);
    igual(403, $r['code'], 'borrador.php');
    $e = $db->prepare('SELECT estado FROM conversaciones WHERE id = ?');
    $e->execute([$convA]);
    igual('abierta', $e->fetchColumn(), 'el estado de la conversación de A cambió');
});

prueba('B no puede adjuntar archivos a un mensaje de A', function () use ($cb, $tokB, $msgA) {
    $r = $cb->json('POST', '/api/comunicaciones/adjuntar.php', [CSRF_TOKEN_NAME => $tokB, 'mensaje_id' => $msgA]);
    igual(403, $r['code']);
});

prueba('POST sin token CSRF es rechazado', function () use ($ca, $convA) {
    $r = $ca->json('POST', '/api/comunicaciones/enviar.php', ['conversacion_id' => $convA, 'contenido' => 'SIN-TOKEN']);
    igual(403, $r['code']);
});

prueba('una empresa no puede escribir en un comunicado global (solo leerlo)', function () use ($cb, $tokB, $convGlobal, $db) {
    $lee = $cb->json('GET', "/api/comunicaciones/conversacion.php?id=$convGlobal");
    igual(200, $lee['code'], 'las empresas deben poder leer los comunicados');
    $r = $cb->json('POST', '/api/comunicaciones/enviar.php', [CSRF_TOKEN_NAME => $tokB, 'conversacion_id' => $convGlobal, 'contenido' => 'SPAM-GLOBAL-B']);
    $n = $db->prepare("SELECT COUNT(*) FROM mensajes_v2 WHERE contenido = 'SPAM-GLOBAL-B'");
    $n->execute();
    igual(0, (int) $n->fetchColumn(), 'B publicó un mensaje en un comunicado visible para todas las empresas (HTTP ' . $r['code'] . ')');
    igual(403, $r['code']);
});

prueba('una empresa no puede crear un comunicado global ni escribir a otra empresa', function () use ($cb, $tokB, $A, $db) {
    $r = $cb->json('POST', '/api/comunicaciones/enviar.php', [
        CSRF_TOKEN_NAME => $tokB, 'titulo' => 'zz_aislamiento intento', 'categoria' => 'consulta',
        'destinatario' => 'empresa:' . $A['eid'], 'contenido' => 'INTENTO-B',
    ]);
    $c = $db->prepare("SELECT empresa_id FROM conversaciones WHERE titulo = 'zz_aislamiento intento'");
    $c->execute();
    $filas = $c->fetchAll(PDO::FETCH_COLUMN);
    // La conversación (si se crea) debe quedar siempre a nombre de B, nunca de A ni global.
    foreach ($filas as $emp) {
        igual($GLOBALS['B']['eid'], (int) $emp, 'la conversación se creó a nombre de otra empresa o como global');
    }
    $db->prepare("DELETE m FROM mensajes_v2 m JOIN conversaciones c ON c.id = m.conversacion_id WHERE c.titulo = 'zz_aislamiento intento'")->execute();
    $db->prepare("DELETE FROM conversaciones WHERE titulo = 'zz_aislamiento intento'")->execute();
});

echo "\nDatos de la empresa: publicaciones, galería y perfil\n";

prueba('B no puede ver ni editar la publicación de A', function () use ($cb, $pubA) {
    $r = $cb->get("/empresa/publicaciones.php?editar=$pubA", true);
    verdadero(strpos($r['body'], 'zz_aislamiento pub A') === false, 'B ve el título de la publicación de A');
});

prueba('B no puede modificar ni borrar la publicación de A', function () use ($cb, $tokB, $pubA, $db) {
    $cb->post('/empresa/publicaciones.php', [
        CSRF_TOKEN_NAME => $tokB, 'accion' => 'guardar', 'publicacion_id' => $pubA,
        'titulo' => 'zz_aislamiento HACKEADA', 'tipo' => 'noticia', 'contenido' => 'x',
    ]);
    $cb->post('/empresa/publicaciones.php', [CSRF_TOKEN_NAME => $tokB, 'accion' => 'eliminar', 'publicacion_id' => $pubA]);
    $s = $db->prepare('SELECT titulo, empresa_id FROM publicaciones WHERE id = ?');
    $s->execute([$pubA]);
    $fila = $s->fetch();
    verdadero($fila !== false, 'la publicación de A fue borrada por B');
    igual('zz_aislamiento pub A', $fila['titulo'], 'B cambió el título');
    igual($GLOBALS['A']['eid'], (int) $fila['empresa_id'], 'B se apropió de la publicación');
});

prueba('B no puede borrar una imagen de la galería de A', function () use ($cb, $tokB, $imgA, $db) {
    $cb->post('/empresa/galeria_api.php', [CSRF_TOKEN_NAME => $tokB, 'accion' => 'eliminar', 'imagen_id' => $imgA]);
    $s = $db->prepare('SELECT COUNT(*) FROM empresa_imagenes WHERE id = ?');
    $s->execute([$imgA]);
    igual(1, (int) $s->fetchColumn(), 'B borró la imagen de A');
});

prueba('el perfil de B ignora empresa_id/usuario_id inyectados y no toca a A', function () use ($cb, $tokB, $A, $B, $db) {
    $cb->post('/empresa/perfil.php', [
        CSRF_TOKEN_NAME => $tokB, 'nombre' => 'zz_aislamiento_B_editada', 'descripcion' => 'x',
        'empresa_id' => $A['eid'], 'id' => $A['eid'], 'usuario_id' => $A['uid'],
    ]);
    $s = $db->prepare('SELECT nombre, usuario_id FROM empresas WHERE id = ?');
    $s->execute([$A['eid']]);
    $a = $s->fetch();
    igual('zz_aislamiento_A', $a['nombre'], 'B cambió el nombre de la empresa A');
    igual($A['uid'], (int) $a['usuario_id'], 'B cambió el usuario dueño de A');
    $s->execute([$B['eid']]);
    igual($B['uid'], (int) $s->fetch()['usuario_id'], 'B perdió su propio usuario');
});

echo "\nFormularios (escritura)\n";

prueba('la respuesta a un formulario se guarda a nombre de la empresa de la sesión', function () use ($ca, $cb, $tokA, $tokB, $formId, $A, $B, $db) {
    verdadero($formId > 0, 'no hay formularios publicados para probar');
    $ca->post("/empresa/formulario_dinamico.php?id=$formId", [CSRF_TOKEN_NAME => $tokA, 'accion' => 'guardar', 'empresa_id' => $B['eid']]);
    $cb->post("/empresa/formulario_dinamico.php?id=$formId", [CSRF_TOKEN_NAME => $tokB, 'accion' => 'guardar', 'empresa_id' => $A['eid']]);
    $s = $db->prepare('SELECT empresa_id, usuario_id FROM formulario_respuestas WHERE formulario_id = ? AND empresa_id IN (?, ?) ORDER BY empresa_id');
    $s->execute([$formId, $A['eid'], $B['eid']]);
    $filas = $s->fetchAll();
    igual(2, count($filas), 'debe haber exactamente una respuesta por empresa');
    igual($A['uid'], (int) $filas[0]['usuario_id'], 'la respuesta de A quedó a nombre de otro usuario');
    igual($B['uid'], (int) $filas[1]['usuario_id'], 'la respuesta de B quedó a nombre de otro usuario');
});

prueba('B no puede pisar la respuesta de A (cada una edita solo la suya)', function () use ($ca, $cb, $tokA, $tokB, $formId, $A, $B, $db) {
    $ca->post("/empresa/formulario_dinamico.php?id=$formId", [CSRF_TOKEN_NAME => $tokA, 'accion' => 'guardar']);
    $antes = $db->prepare('SELECT id, respuestas FROM formulario_respuestas WHERE formulario_id = ? AND empresa_id = ?');
    $antes->execute([$formId, $A['eid']]);
    $a0 = $antes->fetch();
    $cb->post("/empresa/formulario_dinamico.php?id=$formId", [CSRF_TOKEN_NAME => $tokB, 'accion' => 'guardar', 'id' => $a0['id'], 'respuesta_id' => $a0['id']]);
    $antes->execute([$formId, $A['eid']]);
    $a1 = $antes->fetch();
    igual($a0['respuestas'], $a1['respuestas'], 'B alteró el contenido de la respuesta de A');
});

prueba('un POST a un formulario sin token CSRF no escribe nada', function () use ($ca, $formId, $A, $db) {
    $n = $db->prepare('SELECT COUNT(*) FROM formulario_respuestas WHERE formulario_id = ? AND empresa_id = ?');
    $n->execute([$formId, $A['eid']]);
    $antes = (int) $n->fetchColumn();
    $db->prepare('DELETE FROM formulario_respuestas WHERE formulario_id = ? AND empresa_id = ?')->execute([$formId, $A['eid']]);
    $ca->post("/empresa/formulario_dinamico.php?id=$formId", ['accion' => 'enviar']);
    $n->execute([$formId, $A['eid']]);
    igual(0, (int) $n->fetchColumn(), 'se guardó una respuesta sin token CSRF');
    verdadero($antes >= 1, 'la prueba anterior debió dejar una respuesta de A');
});

echo "\nSeparación de roles\n";

prueba('una empresa no puede abrir las páginas del Ministerio', function () use ($cb) {
    foreach (['dashboard', 'empresas', 'comunicaciones', 'solicitudes-proyecto', 'formularios', 'lotes', 'banners', 'exportar'] as $p) {
        $r = $cb->get("/ministerio/$p.php", true);
        // Aceptable: 403, o redirección fuera de /ministerio/ (login o su propio panel).
        verdadero($r['code'] === 403 || strpos($r['url'], '/ministerio/') === false,
            "la empresa accede a /ministerio/$p.php (HTTP {$r['code']}, {$r['url']})");
    }
});

prueba('sin sesión, el panel de empresa y el del Ministerio redirigen al login', function () use ($anon) {
    foreach (['/empresa/dashboard.php', '/empresa/perfil.php', '/ministerio/dashboard.php', '/ministerio/empresas.php'] as $p) {
        $r = $anon->get($p, true);
        verdadero(strpos($r['url'], 'login.php') !== false, "$p es accesible sin sesión (HTTP {$r['code']})");
    }
});

prueba('las APIs de lotes que escriben rechazan a una empresa', function () use ($cb, $tokB) {
    foreach (['guardar', 'eliminar', 'geometria'] as $p) {
        $r = $cb->json('POST', "/api/lotes/$p.php", [CSRF_TOKEN_NAME => $tokB, 'id' => 1]);
        verdadero(in_array($r['code'], [401, 403], true), "/api/lotes/$p.php respondió HTTP {$r['code']} a una empresa");
    }
});

echo "\n";
$total = $pasan + count($fallan);
echo "Resultado: $pasan de $total pruebas OK\n\n";
exit($fallan ? 1 : 0);
