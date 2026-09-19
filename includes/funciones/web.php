<?php
/**
 * Base web: escape de HTML, CSRF, redirecciones, mensajes flash, URLs de estáticos, paginación.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Escapar HTML para prevenir XSS
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generar token CSRF
 */
function csrf_token() {
    return $_SESSION[CSRF_TOKEN_NAME] ?? '';
}

/**
 * Campo hidden con token CSRF
 */
function csrf_field() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

/**
 * Verificar token CSRF
 */
function verify_csrf($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Redireccionar
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Mostrar mensaje flash
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function show_flash() {
    $flash = get_flash();
    if ($flash) {
        $type_class = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        $class = $type_class[$flash['type']] ?? 'alert-info';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo e($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * URL de un archivo estático propio de public/ (p. ej. 'js/empresa-perfil.js') con la fecha de
 * modificación como versión (?v=...), para que el navegador lo recargue cuando cambia.
 */
function asset_url(string $ruta): string {
    $ruta = ltrim($ruta, '/');
    $archivo = BASEPATH . '/public/' . $ruta;
    $v = is_file($archivo) ? filemtime($archivo) : 0;
    return rtrim(PUBLIC_URL, '/') . '/' . $ruta . ($v ? '?v=' . $v : '');
}

/**
 * IP del cliente (evitar confiar en X-Forwarded-For salvo proxy conocido).
 */
function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * JSON seguro
 */
function safe_json_encode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * Sanitizar entrada
 */
function sanitize_input($data) {
    if (is_array($data)) return array_map('sanitize_input', $data);
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Paginación
 */
function paginate($total, $per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    
    $pages = [];
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $pages[] = $i;
    }
    
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'pages' => $pages,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_url' => str_replace('{page}', $current_page - 1, $url_pattern),
        'next_url' => str_replace('{page}', $current_page + 1, $url_pattern),
        'url_pattern' => $url_pattern
    ];
}

/**
 * Renderizar paginación
 */
function render_pagination($p) {
    if ($p['total_pages'] <= 1) return '';

    $html = '<nav><ul class="pagination justify-content-center">';
    $html .= $p['has_prev']
        ? '<li class="page-item"><a class="page-link" href="'.e($p['prev_url']).'">«</a></li>'
        : '<li class="page-item disabled"><span class="page-link">«</span></li>';

    foreach ($p['pages'] as $page) {
        $html .= $page == $p['current_page']
            ? '<li class="page-item active"><span class="page-link">'.$page.'</span></li>'
            : '<li class="page-item"><a class="page-link" href="'.str_replace('{page}', $page, $p['url_pattern']).'">'.$page.'</a></li>';
    }

    $html .= $p['has_next']
        ? '<li class="page-item"><a class="page-link" href="'.e($p['next_url']).'">»</a></li>'
        : '<li class="page-item disabled"><span class="page-link">»</span></li>';

    return $html . '</ul></nav>';
}
