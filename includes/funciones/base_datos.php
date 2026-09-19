<?php
/**
 * Ayudas de base de datos y configuración del sitio.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Comprueba si existe una columna en una tabla (MySQL).
 */
function db_table_has_column(PDO $db, string $table, string $column): bool {
    $t = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($t === '' || $c === '') {
        return false;
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");

        return $stmt && (bool) $stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Obtener configuración del sitio
 */
function get_config($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT clave, valor FROM configuracion_sitio");
            $config = [];
            while ($row = $stmt->fetch()) {
                $config[$row['clave']] = $row['valor'];
            }
        } catch (Exception $e) {
            $config = [];
        }
    }
    
    return $config[$key] ?? $default;
}

/**
 * Indica si una columna tiene AUTO_INCREMENT (SHOW COLUMNS).
 * Tabla y columna: solo letras, números y guión bajo.
 */
function db_column_is_auto_increment(PDO $db, string $table, string $column = 'id'): bool {
    $t = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($t === '' || $c === '') {
        return true;
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `{$t}` WHERE Field = '{$c}'");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$row) {
            return true;
        }
        return stripos((string) ($row['Extra'] ?? ''), 'auto_increment') !== false;
    } catch (Throwable $e) {
        return true;
    }
}
