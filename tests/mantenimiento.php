<?php
/**
 * Pruebas de rendimiento/mantenimiento: conteo de visitas y purga del historial.
 *
 *   php -S localhost:8080 -t public      (en otra terminal; solo lo usa la parte HTTP)
 *   php tests/mantenimiento.php          (o: npm run test:mantenimiento)
 *
 * Comprueba que:
 *  - los robots y los refrescos (misma IP, 30 min) no cuentan como visitas;
 *  - la purga borra solo lo viejo, respeta el piso de 30 días, funciona por lotes y en simulación;
 *  - el cron limpiar-historial.php funciona por CLI y no se puede ejecutar por HTTP sin clave.
 * La parte de borrado real se ejecuta dentro de una transacción que se revierte (ROLLBACK), así que
 * no toca datos reales de la base. Crea empresas/usuarios zz_test_* y los borra al terminar.
 *
 * Sale con código 1 si algo falla.
 */

require_once __DIR__ . '/_lib.php';
require_once BASEPATH . '/includes/funciones.php';
require_once BASEPATH . '/includes/mantenimiento.php';

$ids = ['usuarios' => [], 'empresas' => []];
register_shutdown_function(function () use ($db, &$ids) {
    try {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        foreach ($ids['usuarios'] as $u) {
            foreach (['log_actividad', 'notificaciones'] as $t) {
                try { $db->prepare("DELETE FROM $t WHERE usuario_id = ?")->execute([$u]); } catch (Throwable $ex) { /* opcional */ }
            }
        }
        foreach ($ids['empresas'] as $e) {
            $db->prepare('DELETE FROM empresas WHERE id = ?')->execute([$e]);   // visitas_empresa cae por ON DELETE CASCADE
        }
        foreach ($ids['usuarios'] as $u) {
            $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$u]);
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'AVISO: limpieza incompleta (' . $e->getMessage() . "). Buscar filas con prefijo zz_test_.\n");
    }
});

$E = crear_usuario($db, 'mant', $PASS, $ids);
$FIREFOX = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0';

function visitas(PDO $db, int $eid): int {
    $s = $db->prepare('SELECT visitas FROM empresas WHERE id = ?');
    $s->execute([$eid]);
    return (int) $s->fetchColumn();
}
function filas_visitas(PDO $db, int $eid): int {
    $s = $db->prepare('SELECT COUNT(*) FROM visitas_empresa WHERE empresa_id = ?');
    $s->execute([$eid]);
    return (int) $s->fetchColumn();
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\nRobots\n";

prueba('es_bot_ua: robots, herramientas y User-Agent vacío se detectan', function () {
    foreach ([
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
        'Mozilla/5.0 (compatible; YandexBot/3.0)', 'Mozilla/5.0 (compatible; Baiduspider/2.0)',
        'Mozilla/5.0 (compatible; SemrushBot/7~bl)', 'facebookexternalhit/1.1', 'Slackbot-LinkExpanding 1.0',
        'curl/8.4.0', 'Wget/1.21', 'python-requests/2.31.0', 'Go-http-client/1.1', 'Java/17.0.2',
        'Mozilla/5.0 (compatible; UptimeRobot/2.0)', 'Site24x7 monitor', '', '   ', null,
    ] as $ua) {
        verdadero(es_bot_ua($ua), 'no detectó como robot: ' . var_export($ua, true));
    }
});

prueba('es_bot_ua: navegadores reales NO son robots (incluye el Chrome sin ventana de las pruebas)', function () {
    foreach ([
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0',
    ] as $ua) {
        verdadero(!es_bot_ua($ua), 'trató a un navegador como robot: ' . $ua);
    }
});

echo "\nConteo de visitas\n";

prueba('un robot no suma visita ni deja fila', function () use ($db, $E) {
    $antes = [visitas($db, $E['eid']), filas_visitas($db, $E['eid'])];
    igual(false, registrar_visita_empresa($db, $E['eid'], '198.51.100.1', 'Googlebot/2.1'));
    igual(false, registrar_visita_empresa($db, $E['eid'], '198.51.100.1', null));
    igual($antes, [visitas($db, $E['eid']), filas_visitas($db, $E['eid'])]);
});

prueba('una visita real suma 1 al contador y deja 1 fila', function () use ($db, $E, $FIREFOX) {
    igual(true, registrar_visita_empresa($db, $E['eid'], '198.51.100.10', $FIREFOX));
    igual(1, visitas($db, $E['eid']));
    igual(1, filas_visitas($db, $E['eid']));
});

prueba('un refresco de la misma IP dentro de 30 min no cuenta', function () use ($db, $E, $FIREFOX) {
    igual(false, registrar_visita_empresa($db, $E['eid'], '198.51.100.10', $FIREFOX));
    igual(false, registrar_visita_empresa($db, $E['eid'], '198.51.100.10', $FIREFOX));
    igual(1, visitas($db, $E['eid']));
    igual(1, filas_visitas($db, $E['eid']));
});

prueba('otra IP sí cuenta, y una IP nula también se deduplica', function () use ($db, $E, $FIREFOX) {
    igual(true, registrar_visita_empresa($db, $E['eid'], '198.51.100.11', $FIREFOX));
    igual(true, registrar_visita_empresa($db, $E['eid'], null, $FIREFOX));
    igual(false, registrar_visita_empresa($db, $E['eid'], null, $FIREFOX));
    igual(3, visitas($db, $E['eid']));
    igual(3, filas_visitas($db, $E['eid']));
});

prueba('pasada la ventana, la misma IP vuelve a contar', function () use ($db, $E, $FIREFOX) {
    $db->prepare("UPDATE visitas_empresa SET created_at = DATE_SUB(NOW(), INTERVAL 31 MINUTE) WHERE empresa_id = ? AND ip = '198.51.100.10'")->execute([$E['eid']]);
    igual(true, registrar_visita_empresa($db, $E['eid'], '198.51.100.10', $FIREFOX));
    igual(4, visitas($db, $E['eid']));
});

prueba('la visita a otra empresa no se ve afectada por la ventana de la primera', function () use ($db, $E, $FIREFOX, &$ids) {
    $O = crear_usuario($db, 'mant2', 'x', $ids);
    igual(true, registrar_visita_empresa($db, $O['eid'], '198.51.100.10', $FIREFOX));
    igual(1, visitas($db, $O['eid']));
});

prueba('el perfil público cuenta una visita por navegador, no por refresco ni por robot (HTTP)', function () use ($db, $E, $BASE, $FIREFOX) {
    exigir_servidor($BASE);
    $pedir = function (?string $ua) use ($E, $BASE) {
        $ch = curl_init($BASE . '/empresa.php?id=' . $E['eid']);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20] + ($ua !== null ? [CURLOPT_USERAGENT => $ua] : []));
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    };
    $antes = visitas($db, $E['eid']);
    igual(200, $pedir('curl/8.4.0'));
    igual(200, $pedir(null));
    igual($antes, visitas($db, $E['eid']), 'un robot sumó una visita');
    igual(200, $pedir($FIREFOX));
    igual(200, $pedir($FIREFOX));
    igual(200, $pedir($FIREFOX));
    igual($antes + 1, visitas($db, $E['eid']), 'debía sumar exactamente una visita');
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nRetención (variables de entorno)\n";

prueba('historial_dias: valores válidos, piso de 30 días y valores erróneos', function () {
    $prueba = function (?string $valor) {
        $valor === null ? putenv('ZZ_RET') : putenv("ZZ_RET=$valor");
        return historial_dias('ZZ_RET', 365);
    };
    igual(365, $prueba(null), 'sin variable → por defecto');
    igual(90, $prueba('90'));
    igual(30, $prueba('0'), 'un 0 no debe vaciar la tabla');
    igual(30, $prueba('7'), 'piso de 30 días');
    igual(365, $prueba('abc'), 'texto inválido → por defecto');
    igual(365, $prueba('-5'), 'negativo → por defecto');
    igual(365, $prueba(''), 'vacío → por defecto');
    putenv('ZZ_RET');
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nPurga del historial\n";

prueba('historial_purgar_tabla borra lo viejo y respeta lo reciente (en transacción revertida)', function () use ($db, $E) {
    $totalLog = (int) $db->query('SELECT COUNT(*) FROM log_actividad')->fetchColumn();
    $totalVis = (int) $db->query('SELECT COUNT(*) FROM visitas_empresa')->fetchColumn();
    $db->beginTransaction();
    try {
        $ins = $db->prepare("INSERT INTO log_actividad (usuario_id, empresa_id, accion, created_at) VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))");
        foreach ([['zz_vieja_400', 400], ['zz_vieja_366', 366], ['zz_ok_300', 300], ['zz_ok_10', 10]] as [$acc, $d]) {
            $ins->execute([$E['uid'], $E['eid'], $acc, $d]);
        }
        $insV = $db->prepare("INSERT INTO visitas_empresa (empresa_id, ip, created_at) VALUES (?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))");
        foreach ([['203.0.113.1', 400], ['203.0.113.2', 181], ['203.0.113.3', 100], ['203.0.113.4', 1]] as [$ip, $d]) {
            $insV->execute([$E['eid'], $ip, $d]);
        }
        $accs = fn() => $db->query("SELECT accion FROM log_actividad WHERE accion LIKE 'zz\\_%' ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);
        $ips = fn() => $db->query("SELECT ip FROM visitas_empresa WHERE ip LIKE '203.0.113.%' ORDER BY ip")->fetchAll(PDO::FETCH_COLUMN);

        $n = historial_purgar_tabla($db, 'log_actividad', 365);
        verdadero($n >= 2, "debía borrar al menos 2 filas de log (borró $n)");
        igual(['zz_ok_10', 'zz_ok_300'], $accs(), 'log_actividad: solo debían sobrevivir las de menos de 365 días');

        $n = historial_purgar_tabla($db, 'visitas_empresa', 180);
        verdadero($n >= 2, "debía borrar al menos 2 visitas (borró $n)");
        igual(['203.0.113.3', '203.0.113.4'], $ips(), 'visitas_empresa: solo debían sobrevivir las de menos de 180 días');
    } finally {
        $db->rollBack();
    }
    igual($totalLog, (int) $db->query('SELECT COUNT(*) FROM log_actividad')->fetchColumn(), 'la transacción no se revirtió (log_actividad)');
    igual($totalVis, (int) $db->query('SELECT COUNT(*) FROM visitas_empresa')->fetchColumn(), 'la transacción no se revirtió (visitas_empresa)');
});

prueba('un valor de retención menor a 30 días se eleva a 30 (no borra lo de 10 días)', function () use ($db, $E) {
    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO log_actividad (usuario_id, empresa_id, accion, created_at) VALUES (?, ?, 'zz_piso_10', DATE_SUB(NOW(), INTERVAL 10 DAY))")->execute([$E['uid'], $E['eid']]);
        $db->prepare("INSERT INTO log_actividad (usuario_id, empresa_id, accion, created_at) VALUES (?, ?, 'zz_piso_45', DATE_SUB(NOW(), INTERVAL 45 DAY))")->execute([$E['uid'], $E['eid']]);
        historial_purgar_tabla($db, 'log_actividad', 1);
        historial_purgar_tabla($db, 'log_actividad', 0);
        $quedan = $db->query("SELECT accion FROM log_actividad WHERE accion LIKE 'zz\\_piso%' ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);
        igual(['zz_piso_10'], $quedan, 'con retención 1 debía respetar el piso de 30 días (borrar solo lo de 45)');
    } finally {
        $db->rollBack();
    }
});

prueba('borra por lotes (lote de 2 con 7 filas viejas) y devuelve el total', function () use ($db, $E) {
    $db->beginTransaction();
    try {
        $ins = $db->prepare("INSERT INTO visitas_empresa (empresa_id, ip, created_at) VALUES (?, ?, DATE_SUB(NOW(), INTERVAL 500 DAY))");
        for ($i = 1; $i <= 7; $i++) {
            $ins->execute([$E['eid'], "203.0.113.$i"]);
        }
        $antes = (int) $db->query('SELECT COUNT(*) FROM visitas_empresa WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY)')->fetchColumn();
        $n = historial_purgar_tabla($db, 'visitas_empresa', 180, false, 2);
        igual($antes, $n, 'el total devuelto debe ser igual a lo que había');
        igual(0, (int) $db->query('SELECT COUNT(*) FROM visitas_empresa WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY)')->fetchColumn(), 'quedaron filas viejas');
    } finally {
        $db->rollBack();
    }
});

prueba('la simulación cuenta pero no borra', function () use ($db, $E) {
    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO visitas_empresa (empresa_id, ip, created_at) VALUES (?, '203.0.113.9', DATE_SUB(NOW(), INTERVAL 500 DAY))")->execute([$E['eid']]);
        $antes = (int) $db->query('SELECT COUNT(*) FROM visitas_empresa')->fetchColumn();
        $n = historial_purgar_tabla($db, 'visitas_empresa', 180, true);
        verdadero($n >= 1, 'la simulación debía contar la fila vieja');
        igual($antes, (int) $db->query('SELECT COUNT(*) FROM visitas_empresa')->fetchColumn(), 'la simulación borró filas');
    } finally {
        $db->rollBack();
    }
});

prueba('solo se pueden purgar las tablas permitidas (no es un DELETE arbitrario)', function () use ($db) {
    foreach (['usuarios', 'empresas', 'log_actividad; DROP TABLE usuarios', 'conversaciones'] as $t) {
        try {
            historial_purgar_tabla($db, $t, 365);
            throw new RuntimeException("aceptó purgar '$t'");
        } catch (InvalidArgumentException $e) {
            // esperado
        }
    }
});

prueba('historial_asegurar_indice crea idx_fecha en visitas_empresa si falta y es idempotente', function () use ($db) {
    $existe = fn() => (int) $db->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'visitas_empresa' AND index_name = 'idx_fecha'")->fetchColumn();
    if ($existe()) {
        try {
            $db->exec('ALTER TABLE visitas_empresa DROP INDEX idx_fecha');
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), '1142') !== false) {
                echo "       (omitida: el usuario de BD no tiene ALTER, que es lo correcto con permisos mínimos)\n";
                return;
            }
            throw $e;
        }
    }
    igual(0, $existe());
    igual(true, historial_asegurar_indice($db), 'debía crearlo');
    igual(1, $existe());
    igual(false, historial_asegurar_indice($db), 'la segunda vez no debe hacer nada');
});

// ─────────────────────────────────────────────────────────────────────────────
echo "\nCron limpiar-historial.php\n";

prueba('por CLI, en simulación, informa las filas que borraría y no borra nada', function () use ($db, $E) {
    $db->prepare("INSERT INTO log_actividad (usuario_id, empresa_id, accion, created_at) VALUES (?, ?, 'zz_cron_vieja', DATE_SUB(NOW(), INTERVAL 800 DAY))")->execute([$E['uid'], $E['eid']]);
    $db->prepare("INSERT INTO visitas_empresa (empresa_id, ip, created_at) VALUES (?, '203.0.113.50', DATE_SUB(NOW(), INTERVAL 800 DAY))")->execute([$E['eid']]);
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASEPATH . '/public/ministerio/cron/limpiar-historial.php') . ' --dry-run 2>&1';
    $salida = (string) shell_exec($cmd);
    verdadero(preg_match('/^SIMULACION .*log_actividad\(>(\d+)d\): (\d+) \| visitas_empresa\(>(\d+)d\): (\d+)/', $salida, $m) === 1, "salida inesperada: $salida");
    verdadero((int) $m[2] >= 1 && (int) $m[4] >= 1, 'no contó las filas viejas: ' . $salida);
    igual(1, (int) $db->query("SELECT COUNT(*) FROM log_actividad WHERE accion = 'zz_cron_vieja'")->fetchColumn(), 'la simulación borró la fila de log');
    igual(1, (int) $db->query("SELECT COUNT(*) FROM visitas_empresa WHERE ip = '203.0.113.50'")->fetchColumn(), 'la simulación borró la visita');
});

prueba('por CLI respeta LOG_RETENCION_DIAS y VISITAS_RETENCION_DIAS (y su piso de 30)', function () {
    // Se pasa por el entorno del proceso (en Windows no existe la sintaxis "VAR=x comando")
    $env = array_merge(getenv(), ['LOG_RETENCION_DIAS' => '90', 'VISITAS_RETENCION_DIAS' => '1']);
    $p = proc_open([PHP_BINARY, BASEPATH . '/public/ministerio/cron/limpiar-historial.php', '--dry-run'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
    $salida = stream_get_contents($pipes[1]);
    proc_close($p);
    verdadero(strpos($salida, 'log_actividad(>90d)') !== false, "no usó 90 días: $salida");
    verdadero(strpos($salida, 'visitas_empresa(>30d)') !== false, "no aplicó el piso de 30 días: $salida");
});

prueba('por HTTP el cron responde 403 sin clave o con clave incorrecta', function () use ($BASE) {
    foreach (['', '?key=incorrecta', '?key='] as $q) {
        $ch = curl_init($BASE . '/ministerio/cron/limpiar-historial.php' . $q);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
        $cuerpo = (string) curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        igual(403, $code, "GET limpiar-historial.php$q");
        verdadero(stripos($cuerpo, 'Forbidden') !== false, 'debía decir Forbidden');
    }
});

resumen();
