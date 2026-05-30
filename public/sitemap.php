<?php
/**
 * Sitemap XML (URLs públicas + empresas activas + publicaciones aprobadas).
 * Registrar en Search Console: {SITE_URL}/sitemap.php
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = rtrim(PUBLIC_URL, '/');

$static = [
    ['loc' => $base . '/', 'changefreq' => 'daily', 'priority' => '1.0'],
    ['loc' => $base . '/empresas.php', 'changefreq' => 'weekly', 'priority' => '0.9'],
    ['loc' => $base . '/mapa.php', 'changefreq' => 'weekly', 'priority' => '0.85'],
    ['loc' => $base . '/parque.php', 'changefreq' => 'monthly', 'priority' => '0.85'],
    ['loc' => $base . '/estadisticas.php', 'changefreq' => 'weekly', 'priority' => '0.8'],
    ['loc' => $base . '/noticias.php', 'changefreq' => 'daily', 'priority' => '0.85'],
    ['loc' => $base . '/nosotros.php', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => $base . '/presentar-proyecto.php', 'changefreq' => 'monthly', 'priority' => '0.6'],
];

$xmlEsc = static function (string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($static as $u) {
    echo '  <url>';
    echo '<loc>' . $xmlEsc($u['loc']) . '</loc>';
    echo '<changefreq>' . $xmlEsc($u['changefreq']) . '</changefreq>';
    echo '<priority>' . $xmlEsc($u['priority']) . '</priority>';
    echo "</url>\n";
}

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, updated_at FROM empresas WHERE estado = 'activa' ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = $base . '/empresa.php?id=' . (int) $row['id'];
        $ts = !empty($row['updated_at']) ? strtotime($row['updated_at']) : false;
        $lastmod = $ts ? date('Y-m-d', $ts) : null;
        echo '  <url>';
        echo '<loc>' . $xmlEsc($loc) . '</loc>';
        if ($lastmod) {
            echo '<lastmod>' . $xmlEsc($lastmod) . '</lastmod>';
        }
        echo '<changefreq>monthly</changefreq>';
        echo '<priority>0.75</priority>';
        echo "</url>\n";
    }

    $stmt = $db->query("
        SELECT slug, updated_at, created_at
        FROM publicaciones
        WHERE estado = 'aprobado' AND slug IS NOT NULL AND slug != ''
        ORDER BY COALESCE(updated_at, created_at) DESC
        LIMIT 500
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = $base . '/publicacion.php?slug=' . rawurlencode($row['slug']);
        $raw = $row['updated_at'] ?? $row['created_at'] ?? null;
        $ts = $raw ? strtotime($raw) : false;
        $lastmod = $ts ? date('Y-m-d', $ts) : null;
        echo '  <url>';
        echo '<loc>' . $xmlEsc($loc) . '</loc>';
        if ($lastmod) {
            echo '<lastmod>' . $xmlEsc($lastmod) . '</lastmod>';
        }
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>0.7</priority>';
        echo "</url>\n";
    }
} catch (Exception $e) {
    // Sitemap sigue siendo válido con al menos las URLs estáticas
}

echo '</urlset>';
