<?php
/**
 * Google reCAPTCHA v2.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * reCAPTCHA v2: indica si está configurado
 */
function recaptcha_enabled(): bool {
    return defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== ''
        && defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== '';
}

/**
 * reCAPTCHA v2: renderiza el widget + script
 */
function recaptcha_field(): string {
    if (!recaptcha_enabled()) return '';
    return '<div class="g-recaptcha mb-3" data-sitekey="' . e(RECAPTCHA_SITE_KEY) . '"></div>';
}

/**
 * reCAPTCHA v2: tag <script> para cargar la API (incluir una vez en la página)
 */
function recaptcha_script(): string {
    if (!recaptcha_enabled()) return '';
    return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

/**
 * reCAPTCHA v2: verificar respuesta server-side
 * Devuelve true si el captcha es válido o si reCAPTCHA no está configurado (graceful degradation)
 */
function verify_recaptcha(): bool {
    if (!recaptcha_enabled()) return true;

    $response = $_POST['g-recaptcha-response'] ?? '';
    if (empty($response)) return false;

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode((string) $result, true);
    return !empty($data['success']);
}
