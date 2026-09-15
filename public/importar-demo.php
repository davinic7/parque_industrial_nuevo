<?php
/**
 * Importa la carpeta "imagenes demo" directamente a las empresas correctas.
 * Acceder: http://localhost:8000/importar-demo.php
 */
require_once __DIR__ . '/../config/config.php';
if (APP_ENV === 'production') die('Solo local.');

$db  = getDB();
$log = [];
$err = [];

$BASE   = dirname(__DIR__) . '/imagenes demo';
$LOGOS  = __DIR__ . '/uploads/logos';
$GAL    = __DIR__ . '/uploads/galeria_empresa';
$PUBS   = __DIR__ . '/uploads/publicaciones';

foreach ([$LOGOS, $GAL, $PUBS] as $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
}

// ── Mapeo carpeta → empresa_id ────────────────────────────────
$mapa = [
    'catamarca cementos' => 102,
    'dulces del norte'   => 104,
    'frigorifico andino' => 105,
    'norand metaurgica'  => 103,
    'reciclacat'         => 106,
];

// ── Qué archivo es el logo en cada carpeta ────────────────────
// Se detecta automáticamente: el que tenga "logo" en el nombre,
// o el único PNG/WEBP/AVIF si no hay otro criterio.
function detectar_logo(array $archivos): ?string {
    // 1. nombre contiene "logo" o "logotipo"
    foreach ($archivos as $a) {
        if (stripos(basename($a), 'logo') !== false) return $a;
    }
    // 2. único PNG
    $pngs = array_filter($archivos, fn($a) => strtolower(pathinfo($a,PATHINFO_EXTENSION)) === 'png');
    if (count($pngs) === 1) return reset($pngs);
    // 3. único WEBP
    $webp = array_filter($archivos, fn($a) => strtolower(pathinfo($a,PATHINFO_EXTENSION)) === 'webp');
    if (count($webp) === 1) return reset($webp);
    // 4. único AVIF
    $avif = array_filter($archivos, fn($a) => strtolower(pathinfo($a,PATHINFO_EXTENSION)) === 'avif');
    if (count($avif) === 1) return reset($avif);
    return null;
}

// ── Asignaciones manuales de fotos a noticias ─────────────────
// pub_id => nombre exacto del archivo dentro de la carpeta de la empresa
$foto_noticia = [
    2  => ['empresa' => 'catamarca cementos', 'archivo' => 'visita.jpg'],
    3  => ['empresa' => 'norand metaurgica',  'archivo' => 'images.jpg'],
];

$ext_ok = ['jpg','jpeg','png','webp','gif','avif'];

function copiar(string $origen, string $destino): bool {
    return copy($origen, $destino);
}

// ═══════════════════════════════════════════════════════════════
//  PROCESAR CADA EMPRESA
// ═══════════════════════════════════════════════════════════════
foreach ($mapa as $carpeta => $empresa_id) {
    $dir = $BASE . '/' . $carpeta;
    if (!is_dir($dir)) {
        $err[] = "Carpeta no encontrada: $carpeta";
        continue;
    }

    // Listar archivos imagen
    $archivos = [];
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext, $ext_ok)) $archivos[] = $dir . '/' . $f;
    }
    if (empty($archivos)) continue;

    // ── Logo ──────────────────────────────────────────────────
    $logo_src = detectar_logo($archivos);
    if ($logo_src) {
        $ext  = strtolower(pathinfo($logo_src, PATHINFO_EXTENSION));
        $dest = $LOGOS . '/empresa_' . $empresa_id . '_logo.' . $ext;
        if (copiar($logo_src, $dest)) {
            $nombre_logo = 'empresa_' . $empresa_id . '_logo.' . $ext;
            $db->prepare("UPDATE empresas SET logo = ? WHERE id = ?")->execute([$nombre_logo, $empresa_id]);
            $log[] = "✅ Logo empresa $empresa_id ← " . basename($logo_src);
        } else {
            $err[] = "❌ No se pudo copiar logo $empresa_id";
        }
    }

    // ── Galería (todos los que NO son el logo) ─────────────────
    // Excluir también archivos asignados a noticias de esta carpeta
    $excluir_noticias = [];
    foreach ($foto_noticia as $pid => $info) {
        if ($info['empresa'] === $carpeta) $excluir_noticias[] = $info['archivo'];
    }

    $galeria = array_filter($archivos, function($a) use ($logo_src, $excluir_noticias) {
        if ($a === $logo_src) return false;
        foreach ($excluir_noticias as $ex) {
            if (basename($a) === $ex) return false;
        }
        return true;
    });

    // Limpiar galería anterior (solo las de picsum/placeholder)
    $db->prepare("DELETE FROM empresa_imagenes WHERE empresa_id = ? AND url LIKE 'https://picsum%'")->execute([$empresa_id]);

    $orden = 1;
    foreach (array_values($galeria) as $gal_src) {
        $ext  = strtolower(pathinfo($gal_src, PATHINFO_EXTENSION));
        $nombre_gal = 'empresa_' . $empresa_id . '_gal_' . $orden . '.' . $ext;
        $dest = $GAL . '/' . $nombre_gal;
        if (copiar($gal_src, $dest)) {
            $db->prepare("INSERT INTO empresa_imagenes (empresa_id, url, nombre, orden) VALUES (?,?,?,?)")
               ->execute([$empresa_id, $nombre_gal, 'Foto ' . $orden, $orden]);
            $log[] = "✅ Galería empresa $empresa_id img $orden ← " . basename($gal_src);
            $orden++;
        }
    }
}

// ═══════════════════════════════════════════════════════════════
//  PROCESAR FOTOS DE NOTICIAS
// ═══════════════════════════════════════════════════════════════
foreach ($foto_noticia as $pub_id => $info) {
    $src  = $BASE . '/' . $info['empresa'] . '/' . $info['archivo'];
    if (!file_exists($src)) {
        $err[] = "Foto noticia $pub_id no encontrada: " . $info['archivo'];
        continue;
    }
    $ext  = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    $nombre = 'pub_' . $pub_id . '.' . $ext;
    $dest = $PUBS . '/' . $nombre;
    if (copiar($src, $dest)) {
        $db->prepare("UPDATE publicaciones SET imagen = ? WHERE id = ?")->execute([$nombre, $pub_id]);
        $log[] = "✅ Noticia pub_$pub_id ← " . $info['archivo'];
    }
}

$total_ok  = count($log);
$total_err = count($err);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Importar imágenes demo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width:720px">
  <h2 class="mb-1">📦 Importar "imagenes demo"</h2>
  <p class="text-muted">Parque Industrial de Catamarca</p>

  <div class="alert alert-<?= $total_err ? ($total_ok ? 'warning' : 'danger') : 'success' ?> mt-3">
    <strong><?= $total_ok ?> imágenes importadas</strong>
    <?= $total_err ? " · <strong>$total_err errores</strong>" : ' · Sin errores 🎉' ?>
  </div>

  <?php foreach ($log as $l): ?>
  <div class="text-success small py-1 border-bottom"><?= htmlspecialchars($l) ?></div>
  <?php endforeach; ?>
  <?php foreach ($err as $e): ?>
  <div class="text-danger small py-1 border-bottom"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="mt-4 d-flex gap-2">
    <a href="/" class="btn btn-primary">← Ir al sitio</a>
    <a href="/empresas.php" class="btn btn-outline-secondary">Ver empresas</a>
  </div>
</div>
</body>
</html>
