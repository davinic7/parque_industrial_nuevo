<?php
/**
 * Script de importación masiva de imágenes
 * Acceder: http://localhost:8000/importar-imagenes.php
 * Solo funciona en APP_ENV != production
 */
require_once __DIR__ . '/../config/config.php';

if (APP_ENV === 'production') {
    die('No disponible en producción.');
}

$db = getDB();
$log = [];
$errores = [];

// ── Extensiones permitidas ──────────────────────────────────────
$ext_ok = ['jpg','jpeg','png','webp','gif'];

function es_imagen($nombre) {
    global $ext_ok;
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    return in_array($ext, $ext_ok);
}

function copiar_imagen($origen, $destino_dir, $nombre_destino) {
    if (!is_dir($destino_dir)) mkdir($destino_dir, 0755, true);
    $dest = $destino_dir . DIRECTORY_SEPARATOR . $nombre_destino;
    return copy($origen, $dest) ? $nombre_destino : null;
}

// ══════════════════════════════════════════════════════
//  BLOQUE 1 — LOGOS
//  Convenio de nombre: empresa_<ID>.<ext>
//  Ej: empresa_102.jpg  empresa_103.png
// ══════════════════════════════════════════════════════
$dir_logos = __DIR__ . '/uploads/_importar/logos';
foreach (glob($dir_logos . '/*') as $archivo) {
    if (!es_imagen(basename($archivo))) continue;
    $base = pathinfo(basename($archivo), PATHINFO_FILENAME); // "empresa_102"
    if (!preg_match('/^empresa_(\d+)$/', $base, $m)) {
        $errores[] = "Logo ignorado (nombre incorrecto): " . basename($archivo);
        continue;
    }
    $empresa_id = (int)$m[1];
    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    $nombre_destino = 'empresa_' . $empresa_id . '_logo.' . $ext;
    $copiado = copiar_imagen($archivo, __DIR__ . '/uploads/logos', $nombre_destino);
    if ($copiado) {
        $db->prepare("UPDATE empresas SET logo = ? WHERE id = ?")->execute([$copiado, $empresa_id]);
        $log[] = "✅ Logo empresa $empresa_id → $copiado";
    } else {
        $errores[] = "❌ Error copiando logo empresa $empresa_id";
    }
}

// ══════════════════════════════════════════════════════
//  BLOQUE 2 — GALERÍA
//  Convenio: empresa_<ID>_<N>.<ext>
//  Ej: empresa_102_1.jpg  empresa_102_2.jpg  empresa_103_1.png
//  Reemplaza las imágenes de galería existentes de esa empresa
// ══════════════════════════════════════════════════════
$dir_galeria = __DIR__ . '/uploads/_importar/galeria';
$empresas_galeria = []; // empresa_id => [ [archivo, orden], ... ]
foreach (glob($dir_galeria . '/*') as $archivo) {
    if (!es_imagen(basename($archivo))) continue;
    $base = pathinfo(basename($archivo), PATHINFO_FILENAME);
    if (!preg_match('/^empresa_(\d+)_(\d+)$/', $base, $m)) {
        $errores[] = "Galería ignorada (nombre incorrecto): " . basename($archivo);
        continue;
    }
    $empresas_galeria[(int)$m[1]][] = ['archivo' => $archivo, 'orden' => (int)$m[2]];
}

foreach ($empresas_galeria as $empresa_id => $imagenes) {
    usort($imagenes, fn($a,$b) => $a['orden'] - $b['orden']);
    // Eliminar galería anterior de esta empresa
    $db->prepare("DELETE FROM empresa_imagenes WHERE empresa_id = ?")->execute([$empresa_id]);
    foreach ($imagenes as $img) {
        $ext = strtolower(pathinfo($img['archivo'], PATHINFO_EXTENSION));
        $nombre_destino = 'empresa_' . $empresa_id . '_gal_' . $img['orden'] . '.' . $ext;
        $copiado = copiar_imagen($img['archivo'], __DIR__ . '/uploads/galeria_empresa', $nombre_destino);
        if ($copiado) {
            $db->prepare("INSERT INTO empresa_imagenes (empresa_id, url, nombre, orden) VALUES (?,?,?,?)")
               ->execute([$empresa_id, $copiado, 'Imagen ' . $img['orden'], $img['orden']]);
            $log[] = "✅ Galería empresa $empresa_id imagen {$img['orden']} → $copiado";
        } else {
            $errores[] = "❌ Error copiando galería empresa $empresa_id imagen {$img['orden']}";
        }
    }
}

// ══════════════════════════════════════════════════════
//  BLOQUE 3 — NOTICIAS / PUBLICACIONES
//  Convenio: pub_<ID>.<ext>
//  Ej: pub_1.jpg  pub_3.png
// ══════════════════════════════════════════════════════
$dir_noticias = __DIR__ . '/uploads/_importar/noticias';
foreach (glob($dir_noticias . '/*') as $archivo) {
    if (!es_imagen(basename($archivo))) continue;
    $base = pathinfo(basename($archivo), PATHINFO_FILENAME);
    if (!preg_match('/^pub_(\d+)$/', $base, $m)) {
        $errores[] = "Noticia ignorada (nombre incorrecto): " . basename($archivo);
        continue;
    }
    $pub_id = (int)$m[1];
    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    $nombre_destino = 'pub_' . $pub_id . '.' . $ext;
    $copiado = copiar_imagen($archivo, __DIR__ . '/uploads/publicaciones', $nombre_destino);
    if ($copiado) {
        $db->prepare("UPDATE publicaciones SET imagen = ? WHERE id = ?")->execute([$copiado, $pub_id]);
        $log[] = "✅ Noticia $pub_id → $copiado";
    } else {
        $errores[] = "❌ Error copiando imagen noticia $pub_id";
    }
}

// ══════════════════════════════════════════════════════
//  BLOQUE 4 — IMAGEN DE PORTADA (portada_<ID>.<ext>)
// ══════════════════════════════════════════════════════
foreach (glob($dir_logos . '/*') as $archivo) {
    if (!es_imagen(basename($archivo))) continue;
    $base = pathinfo(basename($archivo), PATHINFO_FILENAME);
    if (!preg_match('/^portada_(\d+)$/', $base, $m)) continue;
    $empresa_id = (int)$m[1];
    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    $nombre_destino = 'empresa_' . $empresa_id . '_portada.' . $ext;
    $copiado = copiar_imagen($archivo, __DIR__ . '/uploads/logos', $nombre_destino);
    if ($copiado) {
        $db->prepare("UPDATE empresas SET imagen_portada = ? WHERE id = ?")->execute([$copiado, $empresa_id]);
        $log[] = "✅ Portada empresa $empresa_id → $copiado";
    }
}

// ── Mostrar resultado ────────────────────────────────
$total_ok     = count($log);
$total_errores = count($errores);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Imágenes</title>
    <link href="<?= PUBLIC_URL ?>/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width:780px">
    <h2 class="mb-1">📦 Importador de imágenes</h2>
    <p class="text-muted mb-4">Parque Industrial de Catamarca — solo entorno local</p>

    <?php if ($total_ok === 0 && $total_errores === 0): ?>
    <div class="alert alert-info">
        No se encontraron imágenes para importar. Colocá los archivos en las carpetas indicadas y recargá esta página.
    </div>
    <?php else: ?>
    <div class="alert alert-<?= $total_errores ? 'warning' : 'success' ?>">
        <strong><?= $total_ok ?> imágenes importadas</strong><?= $total_errores ? " · $total_errores errores" : '' ?>
    </div>
    <?php endif; ?>

    <?php foreach ($log as $l): ?>
    <div class="text-success small"><?= htmlspecialchars($l) ?></div>
    <?php endforeach; ?>
    <?php foreach ($errores as $e): ?>
    <div class="text-danger small"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <hr class="my-4">
    <h5>📁 Cómo usar</h5>
    <p>Colocá tus imágenes en las carpetas de abajo y recargá esta página. El script las copia y actualiza la base de datos automáticamente.</p>

    <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark"><tr><th>Carpeta</th><th>Nombre del archivo</th><th>Qué actualiza</th></tr></thead>
        <tbody>
        <tr>
            <td><code>uploads/_importar/logos/</code></td>
            <td><code>empresa_102.jpg</code><br><code>empresa_103.png</code></td>
            <td>Logo de la empresa (campo <code>logo</code>)</td>
        </tr>
        <tr>
            <td><code>uploads/_importar/logos/</code></td>
            <td><code>portada_102.jpg</code><br><code>portada_103.png</code></td>
            <td>Imagen de portada del perfil</td>
        </tr>
        <tr>
            <td><code>uploads/_importar/galeria/</code></td>
            <td><code>empresa_102_1.jpg</code><br><code>empresa_102_2.jpg</code><br><code>empresa_103_1.png</code></td>
            <td>Galería de imágenes del perfil público (el número final es el orden)</td>
        </tr>
        <tr>
            <td><code>uploads/_importar/noticias/</code></td>
            <td><code>pub_1.jpg</code><br><code>pub_3.png</code><br><code>pub_7.jpg</code></td>
            <td>Imagen de portada de la noticia (el número es el ID de la publicación)</td>
        </tr>
        </tbody>
    </table>

    <div class="alert alert-secondary mt-3 small">
        <strong>IDs de empresas:</strong> 1 · 100 · 101 · 102 · 103 · 104 · 105 · 106<br>
        <strong>IDs de noticias:</strong> 1 al 10
    </div>

    <a href="/" class="btn btn-primary mt-2">← Volver al sitio</a>
</div>
</body>
</html>
