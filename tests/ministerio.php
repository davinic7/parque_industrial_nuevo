<?php
/**
 * Pruebas de escritura y permisos del panel del Ministerio (contra un servidor en marcha).
 *
 *   php -S localhost:8080 -t public      (en otra terminal)
 *   php tests/ministerio.php             (o: npm run test:ministerio)
 *
 * Crea un usuario del Ministerio y dos empresas temporales (prefijo "zz_test_"), y comprueba:
 *  - que empresas y visitantes anónimos NO puedan ejecutar acciones del Ministerio (aunque
 *    envíen un token CSRF válido de su propia sesión);
 *  - que el Ministerio sí pueda, y que las acciones respeten sus límites (p. ej. "eliminar
 *    publicación propia" no borra las de las empresas).
 * Al terminar borra todo lo que creó. Solo usar contra una base de desarrollo.
 *
 * Sale con código 1 si algo falla.
 */

require_once __DIR__ . '/_lib.php';

$ids = ['usuarios' => [], 'empresas' => []];

function limpiar(PDO $db, array $ids): void {
    try {
        $db->exec("DELETE FROM lotes WHERE numero_lote LIKE 'ZZTEST%'");
        $db->exec("DELETE FROM plantillas_respuesta WHERE titulo LIKE 'zz_test%'");
        $db->exec("DELETE FROM publicaciones WHERE titulo LIKE 'zz_test%'");
        foreach ($ids['empresas'] as $e) {
            $db->prepare('DELETE FROM lotes WHERE empresa_id = ?')->execute([$e]);
            $db->prepare('UPDATE lotes SET empresa_id = NULL WHERE empresa_id = ?')->execute([$e]);
            foreach (['publicaciones', 'log_actividad_empresa'] as $t) {
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
        fwrite(STDERR, 'AVISO: limpieza incompleta (' . $e->getMessage() . "). Buscar filas con prefijo zz_test_ / ZZTEST.\n");
    }
}

register_shutdown_function(function () use ($db, &$ids) {
    limpiar($db, $ids);
});

exigir_servidor($BASE);

$M = crear_usuario($db, 'min', $PASS, $ids, 'ministerio');
$E = crear_usuario($db, 'E', $PASS, $ids);   // empresa "víctima"
$X = crear_usuario($db, 'X', $PASS, $ids);   // empresa "atacante"

function un($db, string $sql, array $p = []) {
    $s = $db->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
}

function nueva_pub(PDO $db, ?int $empresa_id, int $uid, string $titulo, string $estado): int {
    $db->prepare("INSERT INTO publicaciones (empresa_id, usuario_id, titulo, slug, tipo, contenido, estado, publicado) VALUES (?, ?, ?, ?, 'noticia', 'x', ?, ?)")
       ->execute([$empresa_id, $uid, $titulo, 'zz-test-' . bin2hex(random_bytes(4)), $estado, $estado === 'aprobado' ? 1 : 0]);
    return (int) $db->lastInsertId();
}

$pubE1   = nueva_pub($db, $E['eid'], $E['uid'], 'zz_test pub E1', 'pendiente');
$pubE2   = nueva_pub($db, $E['eid'], $E['uid'], 'zz_test pub E2', 'pendiente');
$pubE3   = nueva_pub($db, $E['eid'], $E['uid'], 'zz_test pub E3', 'pendiente');
$pubMin  = nueva_pub($db, null, $M['uid'], 'zz_test pub Ministerio', 'aprobado');

$db->prepare("INSERT INTO plantillas_respuesta (titulo, contenido, categoria) VALUES ('zz_test plantilla base', 'texto', 'general')")->execute();
$plantId = (int) $db->lastInsertId();
$db->prepare("INSERT INTO lotes (numero_lote, estado) VALUES ('ZZTEST-1', 'disponible')")->execute();
$loteId = (int) $db->lastInsertId();

$cm = new Cliente($BASE);
$cx = new Cliente($BASE);
$anon = new Cliente($BASE);
$cm->login($M['email'], $PASS, '/ministerio/dashboard.php');
$cx->login($X['email'], $PASS);
$tokM = $cm->csrf('/ministerio/publicaciones.php');
$tokX = $cx->csrf('/empresa/perfil.php');
$tokAnon = $anon->csrf('/login.php');

$estadoPub = fn(int $id) => un($db, 'SELECT estado FROM publicaciones WHERE id = ?', [$id]);

// ─────────────────────────────────────────────────────────────────────────────
echo "\nUna empresa no puede ejecutar acciones del Ministerio\n";

prueba('aprobar / rechazar publicaciones', function () use ($cx, $tokX, $pubE1, $estadoPub) {
    $cx->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => 'aprobar', 'publicacion_id' => $pubE1]);
    igual('pendiente', $estadoPub($pubE1), 'la empresa aprobó su propia publicación');
    $cx->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => 'rechazar', 'publicacion_id' => $pubE1]);
    igual('pendiente', $estadoPub($pubE1), 'la empresa rechazó una publicación');
});

prueba('crear, editar y borrar plantillas de respuesta', function () use ($cx, $tokX, $plantId, $db) {
    $cx->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => 'guardar', 'titulo' => 'zz_test intrusa', 'contenido' => 'x']);
    igual(0, (int) un($db, "SELECT COUNT(*) FROM plantillas_respuesta WHERE titulo = 'zz_test intrusa'"), 'la empresa creó una plantilla');
    $cx->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => 'guardar', 'id' => $plantId, 'titulo' => 'zz_test pisada', 'contenido' => 'x']);
    igual('zz_test plantilla base', un($db, 'SELECT titulo FROM plantillas_respuesta WHERE id = ?', [$plantId]), 'la empresa editó una plantilla');
    $cx->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => 'eliminar', 'id' => $plantId]);
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM plantillas_respuesta WHERE id = ?', [$plantId]), 'la empresa borró una plantilla');
});

prueba('suspender o inactivar a otra empresa', function () use ($cx, $tokX, $E, $db) {
    foreach (['suspender', 'inactivar'] as $accion) {
        $cx->post('/ministerio/empresas.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => $accion, 'empresa_id' => $E['eid']]);
        igual('activa', un($db, 'SELECT estado FROM empresas WHERE id = ?', [$E['eid']]), "la empresa ejecutó '$accion' sobre otra");
    }
});

prueba('crear, modificar y borrar lotes (API)', function () use ($cx, $tokX, $loteId, $E, $db) {
    $r = $cx->postJson('/api/lotes/guardar.php', ['numero_lote' => 'ZZTEST-EMPRESA', 'estado' => 'ocupado'], $tokX);
    igual(403, $r['code'], 'guardar (crear)');
    $r = $cx->postJson('/api/lotes/guardar.php', ['id' => $loteId, 'numero_lote' => 'ZZTEST-1', 'estado' => 'ocupado', 'empresa_id' => $E['eid']], $tokX);
    igual(403, $r['code'], 'guardar (asignarse un lote)');
    $r = $cx->postJson('/api/lotes/eliminar.php', ['id' => $loteId], $tokX);
    igual(403, $r['code'], 'eliminar');
    igual('disponible', un($db, 'SELECT estado FROM lotes WHERE id = ?', [$loteId]), 'la empresa cambió el estado del lote');
    igual(0, (int) un($db, "SELECT COUNT(*) FROM lotes WHERE numero_lote = 'ZZTEST-EMPRESA'"), 'la empresa creó un lote');
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nUn visitante sin sesión no puede ejecutar acciones del Ministerio\n";

prueba('publicaciones, plantillas y empresas (con un token CSRF válido de su propia sesión)', function () use ($anon, $tokAnon, $pubE1, $plantId, $E, $estadoPub, $db) {
    $anon->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokAnon, 'accion' => 'aprobar', 'publicacion_id' => $pubE1]);
    igual('pendiente', $estadoPub($pubE1), 'un anónimo aprobó una publicación');
    $anon->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokAnon, 'accion' => 'eliminar', 'id' => $plantId]);
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM plantillas_respuesta WHERE id = ?', [$plantId]), 'un anónimo borró una plantilla');
    $anon->post('/ministerio/empresas.php', [CSRF_TOKEN_NAME => $tokAnon, 'accion' => 'suspender', 'empresa_id' => $E['eid']]);
    igual('activa', un($db, 'SELECT estado FROM empresas WHERE id = ?', [$E['eid']]), 'un anónimo suspendió una empresa');
});

prueba('API de lotes: 401 y el lote sigue intacto', function () use ($anon, $tokAnon, $loteId, $db) {
    igual(401, $anon->postJson('/api/lotes/eliminar.php', ['id' => $loteId], $tokAnon)['code']);
    igual(401, $anon->postJson('/api/lotes/guardar.php', ['numero_lote' => 'ZZTEST-ANON'], $tokAnon)['code']);
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM lotes WHERE id = ?', [$loteId]));
    igual(0, (int) un($db, "SELECT COUNT(*) FROM lotes WHERE numero_lote = 'ZZTEST-ANON'"));
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nProtección CSRF en el Ministerio\n";

prueba('sin token CSRF ninguna acción escribe', function () use ($cm, $pubE1, $plantId, $E, $loteId, $estadoPub, $db) {
    $cm->post('/ministerio/publicaciones.php', ['accion' => 'aprobar', 'publicacion_id' => $pubE1]);
    igual('pendiente', $estadoPub($pubE1), 'aprobó sin token');
    $cm->post('/ministerio/plantillas.php', ['accion' => 'eliminar', 'id' => $plantId]);
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM plantillas_respuesta WHERE id = ?', [$plantId]), 'borró plantilla sin token');
    $cm->post('/ministerio/empresas.php', ['accion' => 'suspender', 'empresa_id' => $E['eid']]);
    igual('activa', un($db, 'SELECT estado FROM empresas WHERE id = ?', [$E['eid']]), 'suspendió sin token');
    $r = $cm->postJson('/api/lotes/eliminar.php', ['id' => $loteId]);
    igual(403, $r['code'], 'lotes/eliminar sin token');
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM lotes WHERE id = ?', [$loteId]), 'borró un lote sin token');
});

prueba('con un token CSRF inválido tampoco escribe', function () use ($cm, $pubE1, $estadoPub) {
    $cm->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => 'token-falso', 'accion' => 'aprobar', 'publicacion_id' => $pubE1]);
    igual('pendiente', $estadoPub($pubE1));
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nEl Ministerio sí puede (control positivo)\n";

prueba('aprobar y rechazar publicaciones de empresas', function () use ($cm, $tokM, $pubE1, $pubE2, $estadoPub, $db) {
    $cm->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'aprobar', 'publicacion_id' => $pubE1]);
    igual('aprobado', $estadoPub($pubE1));
    $cm->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'rechazar', 'publicacion_id' => $pubE2, 'observaciones' => 'zz_test motivo']);
    igual('rechazado', $estadoPub($pubE2));
    igual('zz_test motivo', un($db, 'SELECT motivo_rechazo FROM publicaciones WHERE id = ?', [$pubE2]));
});

prueba('"eliminar" y "despublicar" solo afectan a publicaciones del Ministerio, no a las de empresas', function () use ($cm, $tokM, $pubE3, $pubMin, $estadoPub, $db) {
    $cm->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'eliminar', 'publicacion_id' => $pubE3]);
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM publicaciones WHERE id = ?', [$pubE3]), 'eliminó una publicación de empresa');
    $cm->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'despublicar', 'publicacion_id' => $pubE3]);
    igual('pendiente', $estadoPub($pubE3), 'despublicó una publicación de empresa');
    $cm->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'despublicar', 'publicacion_id' => $pubMin]);
    igual('borrador', $estadoPub($pubMin), 'no despublicó la propia');
    $cm->post('/ministerio/publicaciones.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'eliminar', 'publicacion_id' => $pubMin]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM publicaciones WHERE id = ?', [$pubMin]), 'no eliminó la propia');
});

prueba('plantillas: crear, editar y eliminar', function () use ($cm, $tokM, $plantId, $db) {
    $cm->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'guardar', 'titulo' => 'zz_test nueva', 'contenido' => 'hola', 'categoria' => 'categoria-inventada']);
    igual('general', un($db, "SELECT categoria FROM plantillas_respuesta WHERE titulo = 'zz_test nueva'"), 'una categoría inválida debe caer en general');
    $cm->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'guardar', 'id' => $plantId, 'titulo' => 'zz_test editada', 'contenido' => 'x']);
    igual('zz_test editada', un($db, 'SELECT titulo FROM plantillas_respuesta WHERE id = ?', [$plantId]));
    $cm->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'guardar', 'titulo' => '', 'contenido' => 'x']);
    igual(0, (int) un($db, "SELECT COUNT(*) FROM plantillas_respuesta WHERE titulo = '' AND contenido = 'x'"), 'aceptó una plantilla sin título');
    $cm->post('/ministerio/plantillas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'eliminar', 'id' => $plantId]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM plantillas_respuesta WHERE id = ?', [$plantId]));
});

prueba('empresas: activar, suspender e inactivar (y un estado inventado se ignora)', function () use ($cm, $tokM, $E, $db) {
    $estado = fn() => un($db, 'SELECT estado FROM empresas WHERE id = ?', [$E['eid']]);
    $cm->post('/ministerio/empresas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'suspender', 'empresa_id' => $E['eid']]);
    igual('suspendida', $estado());
    $cm->post('/ministerio/empresas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'inactivar', 'empresa_id' => $E['eid']]);
    igual('inactiva', $estado());
    $cm->post('/ministerio/empresas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'borrar_todo', 'empresa_id' => $E['eid']]);
    igual('inactiva', $estado(), 'una acción inventada modificó la empresa');
    $cm->post('/ministerio/empresas.php', [CSRF_TOKEN_NAME => $tokM, 'accion' => 'activar', 'empresa_id' => $E['eid']]);
    igual('activa', $estado());
});

prueba('lotes (API): crear, asignar a empresa, validar y eliminar', function () use ($cm, $tokM, $E, $db) {
    $r = $cm->postJson('/api/lotes/guardar.php', ['numero_lote' => 'ZZTEST-2', 'sector' => 'Z', 'superficie_m2' => 500, 'estado' => 'disponible'], $tokM);
    igual(200, $r['code'], 'crear: ' . $r['body']);
    $id = (int) $r['json']['id'];
    verdadero($id > 0, 'no devolvió el id del lote');

    $r = $cm->postJson('/api/lotes/guardar.php', ['id' => $id, 'numero_lote' => 'ZZTEST-2', 'estado' => 'ocupado', 'empresa_id' => $E['eid']], $tokM);
    igual(200, $r['code'], 'asignar: ' . $r['body']);
    igual($E['eid'], (int) un($db, 'SELECT empresa_id FROM lotes WHERE id = ?', [$id]));
    igual('asignado', un($db, 'SELECT lote_solicitud_estado FROM empresas WHERE id = ?', [$E['eid']]), 'la empresa no quedó con el lote asignado');

    igual(409, $cm->postJson('/api/lotes/guardar.php', ['numero_lote' => 'ZZTEST-2'], $tokM)['code'], 'número de lote duplicado');
    igual(422, $cm->postJson('/api/lotes/guardar.php', ['numero_lote' => 'ZZTEST-3', 'estado' => 'inventado'], $tokM)['code'], 'estado inválido');
    igual(422, $cm->postJson('/api/lotes/guardar.php', ['numero_lote' => '  '], $tokM)['code'], 'número vacío');
    igual(404, $cm->postJson('/api/lotes/guardar.php', ['id' => 99999999, 'numero_lote' => 'ZZTEST-4'], $tokM)['code'], 'lote inexistente');
    igual(0, (int) un($db, "SELECT COUNT(*) FROM lotes WHERE numero_lote IN ('ZZTEST-3', 'ZZTEST-4')"), 'guardó un lote inválido');

    $r = $cm->postJson('/api/lotes/eliminar.php', ['id' => $id], $tokM);
    igual(200, $r['code'], 'eliminar: ' . $r['body']);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM lotes WHERE id = ?', [$id]));
    igual(404, $cm->postJson('/api/lotes/eliminar.php', ['id' => $id], $tokM)['code'], 'eliminar dos veces');
});

resumen();
