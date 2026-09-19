<?php
/**
 * Prueba de navegador del JavaScript de public/empresa/formulario_dinamico.php (js/empresa-formulario-dinamico.js).
 *
 *   php -S localhost:8080 -t public      (en otra terminal)
 *   php tests/js_formulario_dinamico.php (o: npm run test:js-formulario)
 *
 * Crea una empresa y un formulario publicado temporales (zz_test_*) con preguntas de texto, número,
 * dirección y ubicación, lanza test_js_formulario_dinamico.js (Playwright) con esos datos y luego
 * lo borra todo. Solo usar contra una base de desarrollo.
 *
 * Sale con el código del navegador (1 si algo falla, 2 si no puede preparar el entorno).
 */

require_once __DIR__ . '/_lib.php';

$ids = ['usuarios' => [], 'empresas' => []];
$formId = 0;
register_shutdown_function(function () use ($db, &$ids, &$formId) {
    try {
        if ($formId > 0) {
            $db->prepare('DELETE FROM formulario_respuestas WHERE formulario_id = ?')->execute([$formId]);
            $db->prepare('DELETE FROM formulario_preguntas WHERE formulario_id = ?')->execute([$formId]);
            $db->prepare('DELETE FROM formularios_dinamicos WHERE id = ?')->execute([$formId]);
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
$E = crear_usuario($db, 'fdin', $PASS, $ids);

$db->exec("INSERT INTO formularios_dinamicos (titulo, estado) VALUES ('zz_test formulario con mapas', 'publicado')");
$formId = (int) $db->lastInsertId();
$preguntas = [
    'texto'     => ['texto', 'Nombre del responsable'],
    'numero'    => ['numero', 'Cantidad de empleados'],
    'direccion' => ['direccion', 'Dirección de la planta'],
    // Un "select" sin opciones no tiene dibujo propio y cae en la rama del mapa de ubicación (por la etiqueta)
    'ubicacion' => ['select', 'Ubicación del depósito'],
];
$pids = [];
$ins = $db->prepare('INSERT INTO formulario_preguntas (formulario_id, tipo, etiqueta, requerido, orden) VALUES (?, ?, ?, 0, ?)');
$orden = 1;
foreach ($preguntas as $clave => [$tipo, $etiqueta]) {
    $ins->execute([$formId, $tipo, $etiqueta, $orden++]);
    $pids[$clave] = (int) $db->lastInsertId();
}

$script = BASEPATH . '/test_js_formulario_dinamico.js';
$env = array_merge(getenv(), [
    'BASE_URL' => $BASE, 'FD_EMAIL' => $E['email'], 'FD_PASS' => $PASS, 'FD_FORM_ID' => (string) $formId,
    'FD_PIDS' => json_encode($pids),
]);
$p = proc_open(['node', $script], [1 => STDOUT, 2 => STDERR], $pipes, BASEPATH, $env);
if (!is_resource($p)) {
    fwrite(STDERR, "No se pudo lanzar node\n");
    exit(2);
}
exit(proc_close($p) === 0 ? 0 : 1);
