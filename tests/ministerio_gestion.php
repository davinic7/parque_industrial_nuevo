<?php
/**
 * Pruebas de permisos y escritura del panel del Ministerio — alta de empresas,
 * formularios dinámicos (creación/edición) y banners del inicio (contra un servidor en marcha).
 *
 *   php -S localhost:8080 -t public               (en otra terminal)
 *   php tests/ministerio_gestion.php               (o: npm run test:ministerio-gestion)
 *
 * Cubre lo que tests/ministerio.php no cubre: nueva-empresa.php, el reset de contraseña de
 * empresa-detalle.php, formulario-nuevo.php / formulario-editar.php y banners.php. Comprueba que
 * una empresa o un visitante anónimo no puedan ejecutar estas acciones (aunque envíen un token
 * CSRF válido de su propia sesión) y que el Ministerio sí pueda, con sus validaciones.
 * Crea filas temporales (prefijo "zz_test_" / "zz_test") y las borra al terminar.
 * Solo usar contra una base de desarrollo. Sale con código 1 si algo falla.
 */

require_once __DIR__ . '/_lib.php';

$ids = ['usuarios' => [], 'empresas' => [], 'formularios' => [], 'banners' => []];

function limpiar_gestion(PDO $db, array $ids): void {
    try {
        foreach ($ids['formularios'] as $f) {
            $db->prepare('DELETE FROM formulario_preguntas WHERE formulario_id = ?')->execute([$f]);
            $db->prepare('DELETE FROM formularios_dinamicos WHERE id = ?')->execute([$f]);
        }
        $db->exec("DELETE FROM formularios_dinamicos WHERE titulo LIKE 'zz_test%'");
        foreach ($ids['banners'] as $b) {
            $db->prepare('DELETE FROM banners_home WHERE id = ?')->execute([$b]);
        }
        $db->exec("DELETE FROM banners_home WHERE titulo LIKE 'zz_test%'");
        foreach ($ids['empresas'] as $e) {
            $db->prepare('DELETE FROM empresas WHERE id = ?')->execute([$e]);
        }
        foreach ($ids['usuarios'] as $u) {
            $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$u]);
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'AVISO: limpieza incompleta (' . $e->getMessage() . "). Buscar filas con prefijo zz_test_ / zz_test.\n");
    }
}

register_shutdown_function(function () use ($db, &$ids) {
    limpiar_gestion($db, $ids);
});

exigir_servidor($BASE);

$M = crear_usuario($db, 'min', $PASS, $ids, 'ministerio');
$X = crear_usuario($db, 'X', $PASS, $ids);   // empresa "atacante"

function un($db, string $sql, array $p = []) {
    $s = $db->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
}
function fila($db, string $sql, array $p = []) {
    $s = $db->prepare($sql);
    $s->execute($p);
    return $s->fetch();
}

$cm = new Cliente($BASE);
$cx = new Cliente($BASE);
$anon = new Cliente($BASE);
$cm->login($M['email'], $PASS, '/ministerio/dashboard.php');
$cx->login($X['email'], $PASS);
$tokM = $cm->csrf('/ministerio/nueva-empresa.php');
$tokX = $cx->csrf('/empresa/perfil.php');
$tokAnon = $anon->csrf('/login.php');

// ─────────────────────────────────────────────────────────────────────────────
echo "\nAlta de empresas (nueva-empresa.php)\n";

prueba('una empresa no puede dar de alta otra empresa', function () use ($cx, $tokX, $db) {
    $email = 'zz_test_intrusa_' . bin2hex(random_bytes(3)) . '@test.local';
    $cx->post('/ministerio/nueva-empresa.php', [
        CSRF_TOKEN_NAME => $tokX, 'nombre' => 'zz_test intrusa', 'rubro' => 'Textil',
        'email_usuario' => $email, 'modo_registro' => 'password_temporal',
    ]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM usuarios WHERE email = ?', [$email]), 'la empresa creó un usuario');
    igual(0, (int) un($db, "SELECT COUNT(*) FROM empresas WHERE nombre = 'zz_test intrusa'"), 'la empresa creó una empresa');
});

prueba('un anónimo no puede dar de alta una empresa', function () use ($anon, $tokAnon, $db) {
    $email = 'zz_test_anon_' . bin2hex(random_bytes(3)) . '@test.local';
    $anon->post('/ministerio/nueva-empresa.php', [
        CSRF_TOKEN_NAME => $tokAnon, 'nombre' => 'zz_test anon', 'rubro' => 'Textil',
        'email_usuario' => $email, 'modo_registro' => 'password_temporal',
    ]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM usuarios WHERE email = ?', [$email]), 'un anónimo creó un usuario');
});

prueba('sin token CSRF no crea nada', function () use ($cm, $db) {
    $email = 'zz_test_sintok_' . bin2hex(random_bytes(3)) . '@test.local';
    $cm->post('/ministerio/nueva-empresa.php', [
        'nombre' => 'zz_test sintok', 'rubro' => 'Textil',
        'email_usuario' => $email, 'modo_registro' => 'password_temporal',
    ]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM usuarios WHERE email = ?', [$email]), 'creó sin token CSRF');
});

prueba('validaciones: nombre, rubro y email obligatorios', function () use ($cm, $tokM, $db) {
    $email = 'zz_test_val_' . bin2hex(random_bytes(3)) . '@test.local';
    $cm->post('/ministerio/nueva-empresa.php', [CSRF_TOKEN_NAME => $tokM, 'nombre' => '', 'rubro' => 'Textil', 'email_usuario' => $email]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM usuarios WHERE email = ?', [$email]), 'aceptó nombre vacío');
    $cm->post('/ministerio/nueva-empresa.php', [CSRF_TOKEN_NAME => $tokM, 'nombre' => 'zz_test x', 'rubro' => '', 'email_usuario' => $email]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM usuarios WHERE email = ?', [$email]), 'aceptó rubro vacío');
    $cm->post('/ministerio/nueva-empresa.php', [CSRF_TOKEN_NAME => $tokM, 'nombre' => 'zz_test x', 'rubro' => 'Textil', 'email_usuario' => 'no-es-un-email']);
    igual(0, (int) un($db, "SELECT COUNT(*) FROM empresas WHERE nombre = 'zz_test x'"), 'aceptó un email inválido');
});

prueba('no permite un email de acceso ya registrado', function () use ($cm, $tokM, $X, $db) {
    $r = $cm->post('/ministerio/nueva-empresa.php', [
        CSRF_TOKEN_NAME => $tokM, 'nombre' => 'zz_test duplicada', 'rubro' => 'Textil', 'email_usuario' => $X['email'],
    ]);
    igual(0, (int) un($db, "SELECT COUNT(*) FROM empresas WHERE nombre = 'zz_test duplicada'"), 'creó una empresa con email duplicado');
    verdadero(strpos($r['body'], 'ya está registrado') !== false, 'no mostró el error de email duplicado');
});

prueba('el Ministerio da de alta una empresa (contraseña temporal) y queda notificada', function () use ($cm, $tokM, $db, &$ids) {
    $email = 'zz_test_nueva_' . bin2hex(random_bytes(3)) . '@test.local';
    $cm->post('/ministerio/nueva-empresa.php', [
        CSRF_TOKEN_NAME => $tokM, 'nombre' => 'zz_test nueva empresa', 'rubro' => 'Metalúrgica',
        'email_usuario' => $email, 'estado' => 'activa', 'modo_registro' => 'password_temporal',
        'solicitar_formulario' => '1',
    ]);
    $u = fila($db, 'SELECT * FROM usuarios WHERE email = ?', [$email]);
    verdadero($u !== false, 'no creó el usuario');
    igual('empresa', $u['rol']);
    igual(1, (int) $u['activo'], 'el usuario no quedó activo (modo contraseña temporal)');
    $ids['usuarios'][] = (int) $u['id'];

    $e = fila($db, 'SELECT * FROM empresas WHERE usuario_id = ?', [$u['id']]);
    verdadero($e !== false, 'no creó la empresa');
    igual('zz_test nueva empresa', $e['nombre']);
    igual('Metalúrgica', $e['rubro']);
    igual('activa', $e['estado']);
    $ids['empresas'][] = (int) $e['id'];

    igual(1, (int) un($db, "SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND tipo = 'formulario_pendiente'", [$u['id']]), 'no notificó el formulario trimestral');
});

prueba('activación por email: usuario queda pendiente sin contraseña utilizable', function () use ($cm, $tokM, $db, &$ids) {
    $email = 'zz_test_activ_' . bin2hex(random_bytes(3)) . '@test.local';
    $cm->post('/ministerio/nueva-empresa.php', [
        CSRF_TOKEN_NAME => $tokM, 'nombre' => 'zz_test activacion', 'rubro' => 'Textil',
        'email_usuario' => $email, 'modo_registro' => 'activacion_email',
    ]);
    $u = fila($db, 'SELECT * FROM usuarios WHERE email = ?', [$email]);
    verdadero($u !== false, 'no creó el usuario');
    igual(0, (int) $u['activo'], 'el usuario quedó activo antes de activar la cuenta');
    verdadero(!empty($u['token_activacion']), 'no generó token de activación');
    $ids['usuarios'][] = (int) $u['id'];
    $e = fila($db, 'SELECT id FROM empresas WHERE usuario_id = ?', [$u['id']]);
    if ($e) $ids['empresas'][] = (int) $e['id'];
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nReset de contraseña desde el detalle de empresa (empresa-detalle.php)\n";

$eX = (int) un($db, 'SELECT id FROM empresas WHERE usuario_id = ?', [$X['uid']]);

prueba('una empresa no puede pedir un reset de contraseña ajeno', function () use ($cx, $tokX, $eX, $X, $db) {
    $antes = un($db, 'SELECT token_recuperacion FROM usuarios WHERE id = ?', [$X['uid']]);
    $cx->post('/ministerio/empresa-detalle.php?id=' . $eX, [CSRF_TOKEN_NAME => $tokX, 'accion' => 'resetear_password']);
    igual($antes, un($db, 'SELECT token_recuperacion FROM usuarios WHERE id = ?', [$X['uid']]), 'una empresa generó un token de recuperación');
});

prueba('un anónimo no puede pedir un reset de contraseña', function () use ($anon, $tokAnon, $eX, $X, $db) {
    $antes = un($db, 'SELECT token_recuperacion FROM usuarios WHERE id = ?', [$X['uid']]);
    $anon->post('/ministerio/empresa-detalle.php?id=' . $eX, [CSRF_TOKEN_NAME => $tokAnon, 'accion' => 'resetear_password']);
    igual($antes, un($db, 'SELECT token_recuperacion FROM usuarios WHERE id = ?', [$X['uid']]), 'un anónimo generó un token de recuperación');
});

prueba('el Ministerio sí puede pedir el reset de contraseña de una empresa', function () use ($cm, $tokM, $eX, $X, $db) {
    $cm->post('/ministerio/empresa-detalle.php?id=' . $eX, [CSRF_TOKEN_NAME => $tokM, 'accion' => 'resetear_password']);
    $u = fila($db, 'SELECT token_recuperacion, token_expira FROM usuarios WHERE id = ?', [$X['uid']]);
    verdadero(!empty($u['token_recuperacion']), 'no generó el token de recuperación');
    verdadero(!empty($u['token_expira']), 'no fijó la expiración del token');
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nFormularios dinámicos (formulario-nuevo.php / formulario-editar.php)\n";

$tokFormM = $cm->csrf('/ministerio/formulario-nuevo.php');

prueba('una empresa no puede crear un formulario dinámico', function () use ($cx, $tokX, $db) {
    $cx->post('/ministerio/formulario-nuevo.php', [
        CSRF_TOKEN_NAME => $tokX, 'titulo' => 'zz_test form intruso', 'estado' => 'publicado',
    ]);
    igual(0, (int) un($db, "SELECT COUNT(*) FROM formularios_dinamicos WHERE titulo = 'zz_test form intruso'"), 'la empresa creó un formulario');
});

prueba('un anónimo no puede crear un formulario dinámico', function () use ($anon, $tokAnon, $db) {
    $anon->post('/ministerio/formulario-nuevo.php', [
        CSRF_TOKEN_NAME => $tokAnon, 'titulo' => 'zz_test form anon', 'estado' => 'publicado',
    ]);
    igual(0, (int) un($db, "SELECT COUNT(*) FROM formularios_dinamicos WHERE titulo = 'zz_test form anon'"), 'un anónimo creó un formulario');
});

prueba('sin título no crea el formulario', function () use ($cm, $tokFormM, $db) {
    $r = $cm->post('/ministerio/formulario-nuevo.php', [CSRF_TOKEN_NAME => $tokFormM, 'titulo' => '', 'estado' => 'publicado']);
    verdadero(strpos($r['body'], 'título') !== false, 'no mostró el error de título obligatorio');
});

$formId = null;
prueba('el Ministerio crea un formulario con preguntas de distinto tipo', function () use ($cm, $tokFormM, $db, &$ids, &$formId) {
    $cm->post('/ministerio/formulario-nuevo.php', [
        CSRF_TOKEN_NAME => $tokFormM,
        'titulo' => 'zz_test formulario', 'descripcion' => 'zz_test descripcion', 'estado' => 'publicado',
        'pregunta_tipo'      => ['texto', 'select'],
        'pregunta_label'     => ['zz_test pregunta texto', 'zz_test pregunta select'],
        'pregunta_requerido' => ['1', ''],
        'pregunta_ayuda'     => ['', ''],
        'pregunta_opciones'  => ['', "Opción A\nOpción B\n\n"],
        'pregunta_min'       => ['', ''],
        'pregunta_max'       => ['', ''],
    ]);
    $f = fila($db, "SELECT * FROM formularios_dinamicos WHERE titulo = 'zz_test formulario'");
    verdadero($f !== false, 'no creó el formulario');
    igual('publicado', $f['estado']);
    $formId = (int) $f['id'];
    $ids['formularios'][] = $formId;

    $preguntas = $db->query("SELECT * FROM formulario_preguntas WHERE formulario_id = $formId ORDER BY orden")->fetchAll();
    igual(2, count($preguntas), 'no guardó las dos preguntas');
    igual('texto', $preguntas[0]['tipo']);
    igual(1, (int) $preguntas[0]['requerido']);
    igual('select', $preguntas[1]['tipo']);
    igual(0, (int) $preguntas[1]['requerido']);
    $opciones = json_decode($preguntas[1]['opciones'], true);
    igual(['Opción A', 'Opción B'], $opciones['items'], 'no filtró las líneas vacías de las opciones');
});

prueba('una pregunta de tipo lista sin opciones no crea el formulario', function () use ($cm, $tokFormM, $db) {
    $r = $cm->post('/ministerio/formulario-nuevo.php', [
        CSRF_TOKEN_NAME => $tokFormM, 'titulo' => 'zz_test sin opciones', 'estado' => 'borrador',
        'pregunta_tipo' => ['radio'], 'pregunta_label' => ['zz_test radio'], 'pregunta_opciones' => [''],
    ]);
    verdadero(strpos($r['body'], 'opción') !== false, 'no mostró el error de opciones vacías');
    igual(0, (int) un($db, "SELECT COUNT(*) FROM formularios_dinamicos WHERE titulo = 'zz_test sin opciones'"), 'creó el formulario igual');
});

prueba('una empresa no puede editar un formulario dinámico', function () use ($cx, $tokX, $formId, $db) {
    $tituloAntes = un($db, 'SELECT titulo FROM formularios_dinamicos WHERE id = ?', [$formId]);
    $cx->post('/ministerio/formulario-editar.php?id=' . $formId, [
        CSRF_TOKEN_NAME => $tokX, 'titulo' => 'zz_test hackeado', 'estado' => 'archivado',
        'pregunta_tipo' => ['texto'], 'pregunta_label' => ['x'],
    ]);
    igual($tituloAntes, un($db, 'SELECT titulo FROM formularios_dinamicos WHERE id = ?', [$formId]), 'una empresa editó el formulario');
});

prueba('un anónimo no puede editar un formulario dinámico', function () use ($anon, $tokAnon, $formId, $db) {
    $tituloAntes = un($db, 'SELECT titulo FROM formularios_dinamicos WHERE id = ?', [$formId]);
    $anon->post('/ministerio/formulario-editar.php?id=' . $formId, [
        CSRF_TOKEN_NAME => $tokAnon, 'titulo' => 'zz_test hackeado anon', 'estado' => 'archivado',
    ]);
    igual($tituloAntes, un($db, 'SELECT titulo FROM formularios_dinamicos WHERE id = ?', [$formId]), 'un anónimo editó el formulario');
});

prueba('el Ministerio edita el formulario: título, estado y reemplazo de preguntas', function () use ($cm, $formId, $db) {
    $tokEdit = $cm->csrf('/ministerio/formulario-editar.php?id=' . $formId);
    $cm->post('/ministerio/formulario-editar.php?id=' . $formId, [
        CSRF_TOKEN_NAME => $tokEdit, 'titulo' => 'zz_test formulario editado', 'estado' => 'archivado',
        'pregunta_tipo' => ['numero'], 'pregunta_label' => ['zz_test pregunta numero'],
        'pregunta_requerido' => ['1'], 'pregunta_min' => ['1'], 'pregunta_max' => ['10'],
    ]);
    $f = fila($db, 'SELECT * FROM formularios_dinamicos WHERE id = ?', [$formId]);
    igual('zz_test formulario editado', $f['titulo']);
    igual('archivado', $f['estado']);

    $preguntas = $db->query("SELECT * FROM formulario_preguntas WHERE formulario_id = $formId")->fetchAll();
    igual(1, count($preguntas), 'no reemplazó las preguntas anteriores');
    igual('numero', $preguntas[0]['tipo']);
    igual(10.0, (float) $preguntas[0]['max_valor'], 'no guardó el máximo de la pregunta numérica');
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nBanners del inicio (banners.php)\n";

$db->prepare("INSERT INTO banners_home (titulo, subtitulo, imagen, orden, activo) VALUES ('zz_test banner', 'zz_test sub', 'zz_test.jpg', 99, 1)")->execute();
$bannerId = (int) $db->lastInsertId();
$ids['banners'][] = $bannerId;

prueba('una empresa no puede editar ni eliminar un banner', function () use ($cx, $tokX, $bannerId, $db) {
    $cx->post('/ministerio/banners.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => 'guardar', 'id' => $bannerId, 'titulo' => 'zz_test hackeado', 'orden' => 1]);
    igual('zz_test banner', un($db, 'SELECT titulo FROM banners_home WHERE id = ?', [$bannerId]), 'una empresa editó un banner');
    $cx->post('/ministerio/banners.php', [CSRF_TOKEN_NAME => $tokX, 'accion' => 'eliminar', 'id' => $bannerId]);
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM banners_home WHERE id = ?', [$bannerId]), 'una empresa eliminó un banner');
});

prueba('un anónimo no puede editar ni eliminar un banner', function () use ($anon, $tokAnon, $bannerId, $db) {
    $anon->post('/ministerio/banners.php', [CSRF_TOKEN_NAME => $tokAnon, 'accion' => 'eliminar', 'id' => $bannerId]);
    igual(1, (int) un($db, 'SELECT COUNT(*) FROM banners_home WHERE id = ?', [$bannerId]), 'un anónimo eliminó un banner');
});

prueba('sin token CSRF no edita ni elimina', function () use ($cm, $bannerId, $db) {
    $cm->post('/ministerio/banners.php', ['accion' => 'guardar', 'id' => $bannerId, 'titulo' => 'zz_test sintoken']);
    igual('zz_test banner', un($db, 'SELECT titulo FROM banners_home WHERE id = ?', [$bannerId]), 'editó sin token CSRF');
});

prueba('el Ministerio edita un banner (sin reemplazar la imagen) y conserva la imagen', function () use ($cm, $bannerId, $db) {
    $tokB = $cm->csrf('/ministerio/banners.php');
    $cm->post('/ministerio/banners.php', [
        CSRF_TOKEN_NAME => $tokB, 'accion' => 'guardar', 'id' => $bannerId,
        'titulo' => 'zz_test banner editado', 'subtitulo' => 'zz_test sub editado', 'orden' => 5, 'activo' => '1',
    ]);
    $b = fila($db, 'SELECT * FROM banners_home WHERE id = ?', [$bannerId]);
    igual('zz_test banner editado', $b['titulo']);
    igual(5, (int) $b['orden']);
    igual('zz_test.jpg', $b['imagen'], 'perdió la imagen al editar sin subir una nueva');
});

prueba('el Ministerio elimina un banner', function () use ($cm, $bannerId, $db) {
    $tokB = $cm->csrf('/ministerio/banners.php');
    $cm->post('/ministerio/banners.php', [CSRF_TOKEN_NAME => $tokB, 'accion' => 'eliminar', 'id' => $bannerId]);
    igual(0, (int) un($db, 'SELECT COUNT(*) FROM banners_home WHERE id = ?', [$bannerId]));
});

resumen();
