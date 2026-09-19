<?php
/**
 * Formato de fechas, números, monedas, textos, slugs y CUIT.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Formatear fecha
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $dt = new DateTime($date);
    return $dt->format($format);
}

function format_datetime($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    $dt = new DateTime($date);
    return $dt->format($format);
}

/**
 * Formatear números
 */
function format_number($number, $decimals = 0) {
    return number_format($number ?? 0, $decimals, ',', '.');
}

function format_currency($number) {
    return '$ ' . number_format($number ?? 0, 2, ',', '.');
}

/**
 * Generar slug desde texto
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', (string) $text);
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($converted !== false) {
        $text = $converted;
    }
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    $text = substr($text, 0, 240);
    return $text === '' ? 'n-a' : $text;
}

/**
 * Truncar texto
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Formato visual estándar XX-XXXXXXXX-X (11 dígitos válidos).
 */
function format_cuit_argentina(string $digits11): string {
    if (strlen($digits11) !== 11) {
        return $digits11;
    }
    return substr($digits11, 0, 2) . '-' . substr($digits11, 2, 8) . '-' . substr($digits11, 10, 1);
}

/**
 * Período actual
 */
function get_periodo_actual() {
    return date('Y') . '-Q' . ceil(date('n') / 3);
}
