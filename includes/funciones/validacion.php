<?php
/**
 * Validación de correos y CUIT.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Validar email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Solo dígitos del CUIT / CUIL (11 caracteres).
 */
function cuit_digits_only($cuit): string {
    return preg_replace('/\D/', '', (string) $cuit);
}

/**
 * Validar CUIT (acepta con o sin guiones; se normaliza a 11 dígitos).
 */
function is_valid_cuit($cuit) {
    $cuit = cuit_digits_only($cuit);
    if (strlen($cuit) !== 11) {
        return false;
    }

    $mult = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += (int) $cuit[$i] * $mult[$i];
    }
    $checksum = 11 - ($sum % 11);
    if ($checksum == 11) {
        $checksum = 0;
    }
    if ($checksum == 10) {
        $checksum = 9;
    }

    return (int) $cuit[10] === $checksum;
}
