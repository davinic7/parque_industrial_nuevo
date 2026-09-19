<?php
/**
 * Mantenimiento de tablas que crecen sin límite: log_actividad y visitas_empresa.
 * Lo usa el cron public/ministerio/cron/limpiar-historial.php.
 */

/** Retención mínima permitida (días): los paneles muestran hasta 30 días de visitas. */
const HISTORIAL_MIN_DIAS = 30;

/** Días de retención desde el entorno, con piso HISTORIAL_MIN_DIAS (un valor 0/erróneo no debe vaciar la tabla). */
function historial_dias(string $variable, int $por_defecto): int {
    $v = getenv($variable);
    $n = ($v !== false && ctype_digit(trim((string) $v))) ? (int) $v : $por_defecto;
    return max(HISTORIAL_MIN_DIAS, $n);
}

/** Crea el índice por fecha de visitas_empresa si falta (conteo mensual global y purga; portable MySQL/MariaDB). */
function historial_asegurar_indice(PDO $db): bool {
    $st = $db->query("SELECT COUNT(*) FROM information_schema.statistics
                      WHERE table_schema = DATABASE() AND table_name = 'visitas_empresa' AND index_name = 'idx_fecha'");
    if ((int) $st->fetchColumn() > 0) {
        return false;
    }
    $db->exec('ALTER TABLE visitas_empresa ADD INDEX idx_fecha (created_at)');
    return true;
}

/**
 * Borra en lotes (sin bloquear la tabla mucho tiempo) las filas más viejas que $dias.
 * Con $simular no borra: solo cuenta.
 */
function historial_purgar_tabla(PDO $db, string $tabla, int $dias, bool $simular = false, int $lote = 5000): int {
    if (!in_array($tabla, ['log_actividad', 'visitas_empresa'], true)) {
        throw new InvalidArgumentException("Tabla no permitida: $tabla");
    }
    $dias = max(HISTORIAL_MIN_DIAS, $dias);
    if ($simular) {
        $st = $db->prepare("SELECT COUNT(*) FROM $tabla WHERE created_at < DATE_SUB(NOW(), INTERVAL $dias DAY)");
        $st->execute();
        return (int) $st->fetchColumn();
    }
    $lote = max(1, $lote);
    $total = 0;
    do {
        $n = (int) $db->exec("DELETE FROM $tabla WHERE created_at < DATE_SUB(NOW(), INTERVAL $dias DAY) LIMIT $lote");
        $total += $n;
    } while ($n >= $lote);
    return $total;
}

/** @return array{log_actividad:int, visitas_empresa:int, indice_creado:bool} */
function historial_purgar(PDO $db, int $log_dias, int $visitas_dias, bool $simular = false, int $lote = 5000): array {
    $indice = $simular ? false : historial_asegurar_indice($db);
    return [
        'log_actividad'   => historial_purgar_tabla($db, 'log_actividad', $log_dias, $simular, $lote),
        'visitas_empresa' => historial_purgar_tabla($db, 'visitas_empresa', $visitas_dias, $simular, $lote),
        'indice_creado'   => $indice,
    ];
}
