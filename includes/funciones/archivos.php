<?php
/**
 * Archivos subidos: validación por MIME real, almacenamiento local o en Cloudinary y URLs de los archivos guardados.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Cloudinary configurado (.env)
 */
function cloudinary_configured(): bool {
    return defined('CLOUDINARY_CLOUD_NAME') && CLOUDINARY_CLOUD_NAME !== ''
        && defined('CLOUDINARY_API_KEY') && CLOUDINARY_API_KEY !== ''
        && defined('CLOUDINARY_API_SECRET') && CLOUDINARY_API_SECRET !== '';
}

/**
 * URL base de la API de Cloudinary (configurable solo para las pruebas automáticas).
 */
function cloudinary_api_base(): string {
    $base = defined('CLOUDINARY_API_BASE') ? trim((string) CLOUDINARY_API_BASE) : '';
    return rtrim($base !== '' ? $base : 'https://api.cloudinary.com', '/');
}

/**
 * Firma de una subida firmada: SHA-1 de los parámetros ordenados alfabéticamente
 * ("k=v&k=v", sin file / cloud_name / resource_type / api_key) más el API secret.
 */
function cloudinary_signature(array $params, string $api_secret): string {
    foreach (['file', 'cloud_name', 'resource_type', 'api_key'] as $excluido) {
        unset($params[$excluido]);
    }
    ksort($params);
    $pares = [];
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            continue;
        }
        $pares[] = $k . '=' . (is_array($v) ? implode(',', $v) : $v);
    }
    return sha1(implode('&', $pares) . $api_secret);
}

/**
 * Sube un archivo a Cloudinary; devuelve secure_url o null (y deja el motivo en error_log).
 *
 * @param string $resource_type 'image' (JPG/PNG/GIF/WebP) o 'raw' (PDF, Word, Excel, ZIP...)
 * @param string $upload_name   Nombre con extensión segura; en 'raw' la extensión forma parte de la URL
 */
function cloudinary_upload(string $file_path, string $resource_type, string $upload_name, string $mime, string $folder = ''): ?string {
    if (!cloudinary_configured() || !is_readable($file_path)) {
        return null;
    }
    if (!function_exists('curl_init')) {
        error_log('cloudinary_upload: extensión curl no disponible');
        return null;
    }
    $params = ['timestamp' => time()];
    if ($resource_type === 'raw') {
        $params['use_filename'] = '1';
        $params['unique_filename'] = '1';
    }
    if ($folder !== '') {
        $params['folder'] = $folder;
    }
    $campos = $params + [
        'api_key'   => CLOUDINARY_API_KEY,
        'signature' => cloudinary_signature($params, CLOUDINARY_API_SECRET),
        'file'      => new CURLFile($file_path, $mime, $upload_name),
    ];

    $ch = curl_init(cloudinary_api_base() . '/v1_1/' . rawurlencode(CLOUDINARY_CLOUD_NAME) . '/' . $resource_type . '/upload');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => $campos,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 90,
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $code < 200 || $code >= 300) {
        error_log('cloudinary_upload(' . $resource_type . '): HTTP ' . $code . ' ' . $curl_error . ' ' . substr((string) $response, 0, 300));
        return null;
    }
    $result = json_decode((string) $response, true);
    $url = is_array($result) ? ($result['secure_url'] ?? null) : null;
    return is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
}

/** Sube una imagen a Cloudinary; devuelve secure_url o null. */
function cloudinary_upload_image(string $file_path): ?string {
    return cloudinary_upload($file_path, 'image', basename($file_path), 'application/octet-stream');
}

/**
 * Nombre de archivo para Cloudinary: base del nombre original saneada + extensión segura del MIME.
 * Nunca conserva la extensión que mandó el cliente.
 */
function cloudinary_safe_upload_name(string $original_name, string $extension): string {
    $base = pathinfo($original_name, PATHINFO_FILENAME);
    $base = trim((string) preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $base), '_');
    return ($base !== '' ? mb_substr($base, 0, 60) : 'archivo') . '.' . $extension;
}

/**
 * URL para mostrar imagen guardada: URL absoluta (Cloudinary) o ruta bajo uploads.
 */
function uploads_resolve_url(?string $stored, string $subdir): string {
    if ($stored === null || $stored === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $stored)) {
        return $stored;
    }
    return rtrim(UPLOADS_URL, '/') . '/' . trim($subdir, '/') . '/' . ltrim($stored, '/');
}

/**
 * Extensión segura para un MIME ya validado. Nunca usar la extensión del
 * nombre de archivo del cliente: un atacante puede subir "shell.php" con
 * contenido cuyo encabezado engaña a finfo (p. ej. "%PDF-1.4\n<?php ...?>"
 * es detectado como application/pdf) y, si se conserva la extensión .php,
 * el servidor lo ejecuta como código en vez de servirlo como documento.
 * Devuelve null si el MIME no tiene una extensión segura mapeada: en ese
 * caso el caller debe rechazar la subida en vez de usar la del cliente.
 */
function safe_extension_for_mime(string $mime): ?string {
    static $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
    ];
    return $map[$mime] ?? null;
}

/**
 * MIME detectado y normalizado a uno de la lista permitida (JPG a veces llega como octet-stream).
 */
function resolve_upload_mime_to_allowed(array $file, array $allowed): ?string {
    $tmp = $file['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    if (in_array($mime, $allowed, true)) {
        return $mime;
    }
    static $aliases = [
        'image/jpg' => 'image/jpeg',
        'image/pjpeg' => 'image/jpeg',
        'image/x-png' => 'image/png',
    ];
    if (isset($aliases[$mime]) && in_array($aliases[$mime], $allowed, true)) {
        return $aliases[$mime];
    }
    // SVG (logos ministerio): finfo suele dar xml/octet-stream
    if (in_array('image/svg+xml', $allowed, true)) {
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)); // safe-ext-ok: solo decide si inspeccionar el contenido, no arma ningún nombre
        if ($ext === 'svg') {
            $head = @file_get_contents($tmp, false, null, 0, 500);
            if (is_string($head) && preg_match('/<\s*svg\b/i', $head)) {
                $svg_mimes = [
                    'image/svg+xml', 'text/xml', 'application/xml', 'text/plain',
                    'application/octet-stream', 'binary/octet-stream', 'text/svg+xml',
                ];
                if ($mime === '' || in_array($mime, $svg_mimes, true)) {
                    return 'image/svg+xml';
                }
            }
        }
    }
    if (in_array($mime, ['application/octet-stream', 'binary/octet-stream', 'application/x-empty'], true) || $mime === '') {
        $info = @getimagesize($tmp);
        if ($info === false) {
            return null;
        }
        $map = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_GIF => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
        ];
        $canon = $map[$info[2] ?? 0] ?? null;
        if ($canon && in_array($canon, $allowed, true)) {
            return $canon;
        }
    }
    return null;
}

/**
 * Valida un archivo subido: error de PHP, tamaño y MIME real (finfo). Devuelve
 * ['mime' => ..., 'ext' => ...] con la extensión derivada del MIME verificado (nunca del
 * nombre que manda el cliente) o ['error' => mensaje].
 *
 * @param string|null $verified_mime Si ya se validó el MIME (p. ej. resolve_upload_mime_to_allowed), reutilizarlo
 */
function validate_upload(array $file, ?array $allowed_types, ?string $verified_mime = null): array {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Error al subir el archivo'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'El archivo excede el tamaño máximo'];
    }
    if ($verified_mime !== null && $allowed_types && in_array($verified_mime, $allowed_types, true)) {
        $mime_type = $verified_mime;
    } else {
        $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    }
    if ($allowed_types && !in_array($mime_type, $allowed_types, true)) {
        return ['error' => 'Tipo de archivo no permitido'];
    }
    $extension = safe_extension_for_mime((string) $mime_type);
    if ($extension === null) {
        return ['error' => 'Tipo de archivo no permitido'];
    }
    return ['mime' => (string) $mime_type, 'ext' => $extension];
}

/**
 * Guarda un archivo subido en almacenamiento persistente si hay Cloudinary configurado
 * (imágenes como 'image', el resto de documentos como 'raw'); si no está configurado o
 * falla, cae al disco local (upload_file).
 *
 * 'filename' es la URL absoluta (Cloudinary) o el nombre del archivo local: en la base se guarda
 * ese valor y para mostrarlo se usa uploads_resolve_url().
 */
function store_upload(array $file, string $directory = '', ?array $allowed_types = null, ?string $verified_mime = null): array {
    $v = validate_upload($file, $allowed_types, $verified_mime);
    if (isset($v['error'])) {
        return ['success' => false, 'error' => $v['error']];
    }
    if (cloudinary_configured()) {
        $es_imagen = in_array($v['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
        $url = cloudinary_upload(
            $file['tmp_name'],
            $es_imagen ? 'image' : 'raw',
            cloudinary_safe_upload_name((string) ($file['name'] ?? ''), $v['ext']),
            $v['mime'],
            $directory !== '' ? 'parque_industrial/' . trim($directory, '/') : 'parque_industrial'
        );
        if ($url !== null) {
            return ['success' => true, 'filename' => $url, 'filepath' => null, 'url' => $url];
        }
        error_log('store_upload: Cloudinary falló, usando almacenamiento local');
    }
    return upload_file($file, $directory, $allowed_types, $v['mime']);
}

/**
 * Subida de imagen: Cloudinary si está configurado; si no, disco local.
 * En BD se guarda la URL completa o el nombre de archivo local.
 */
function upload_image_storage(array $file, string $directory, ?array $allowed_types = null): array {
    $allowed = $allowed_types ?? ALLOWED_IMAGE_TYPES;
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'El archivo excede el tamaño máximo permitido'];
    }
    $mime_type = resolve_upload_mime_to_allowed($file, $allowed);
    if ($mime_type === null) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido para esta carga.'];
    }
    return store_upload($file, $directory, $allowed, $mime_type);
}

/**
 * Subir archivo al disco local (uploads/$directory).
 * @param string|null $verified_mime Si ya se validó el MIME (p. ej. resolve_upload_mime_to_allowed), reutilizarlo
 */
function upload_file($file, $directory = '', $allowed_types = null, ?string $verified_mime = null) {
    $v = validate_upload($file, $allowed_types, $verified_mime);
    if (isset($v['error'])) {
        return ['success' => false, 'error' => $v['error']];
    }
    $filename = uniqid() . '_' . time() . '.' . $v['ext'];

    $upload_dir = UPLOADS_PATH . ($directory ? '/' . $directory : '');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filepath = $upload_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => UPLOADS_URL . ($directory ? '/' . $directory : '') . '/' . $filename
        ];
    }

    return ['success' => false, 'error' => 'Error al mover el archivo'];
}

/**
 * Convierte $_FILES['nombre'] (simple o múltiple) en lista de arrays para upload_file.
 */
function normalize_uploaded_files(string $key): array {
    if (empty($_FILES[$key])) {
        return [];
    }
    $f = $_FILES[$key];
    if (!is_array($f['name'])) {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }
        return [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ($name === '') {
            continue;
        }
        $err = $f['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = [
            'name' => $f['name'][$i],
            'type' => $f['type'][$i] ?? '',
            'tmp_name' => $f['tmp_name'][$i],
            'error' => $err,
            'size' => $f['size'][$i] ?? 0,
        ];
    }
    return $out;
}

/**
 * Sube hasta $max PDFs (Cloudinary si está configurado; si no, uploads/$directory).
 * Devuelve en 'saved' la URL absoluta (Cloudinary) o el nombre de archivo local.
 */
function upload_pdf_batch(array $fileStructs, string $directory = 'mensajes', int $max = 5): array {
    $saved = [];
    $slice = array_slice($fileStructs, 0, max(1, $max));
    foreach ($slice as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir el archivo', 'saved' => $saved];
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($mime !== 'application/pdf') {
            return ['success' => false, 'error' => 'Solo se permiten archivos PDF', 'saved' => $saved];
        }
        $r = store_upload($file, $directory, ['application/pdf'], 'application/pdf');
        if (!$r['success']) {
            return ['success' => false, 'error' => $r['error'], 'saved' => $saved];
        }
        $saved[] = $r['filename'];
    }

    return ['success' => true, 'saved' => $saved];
}
