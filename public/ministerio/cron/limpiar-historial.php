<?php
/**
 * Purga el historial que crece sin límite: log_actividad y visitas_empresa (diario, p. ej. 03:30).
 *
 * CLI: php limpiar-historial.php [--dry-run]
 * Web: .../limpiar-historial.php?key=TU_CRON_SECRET[&dry-run=1]
 *
 * Retención (variables de .env; mínimo 30 días):
 *   LOG_RETENCION_DIAS      log_actividad     (por defecto 365)
 *   VISITAS_RETENCION_DIAS  visitas_empresa   (por defecto 180)
 * Con --dry-run solo informa cuántas filas se borrarían.
 */
require_once __DIR__ . '/../../../includes/cron_guard.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASEPATH . '/includes/mantenimiento.php';

$simular = in_array('--dry-run', $argv ?? [], true) || isset($_GET['dry-run']);
$log_dias = historial_dias('LOG_RETENCION_DIAS', 365);
$visitas_dias = historial_dias('VISITAS_RETENCION_DIAS', 180);

try {
    $r = historial_purgar(getDB(), $log_dias, $visitas_dias, $simular);
    echo ($simular ? 'SIMULACION (no se borró nada) ' : 'OK ')
        . "log_actividad(>{$log_dias}d): {$r['log_actividad']}"
        . " | visitas_empresa(>{$visitas_dias}d): {$r['visitas_empresa']}"
        . ($r['indice_creado'] ? ' | índice idx_fecha creado en visitas_empresa' : '') . "\n";
} catch (Throwable $e) {
    error_log('limpiar-historial: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
