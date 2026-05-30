<?php
/**
 * Partial: Hero Carousel
 * Reutilizable desde index.php, el-parque.php, o cualquier otra página.
 * Si $banners_home ya está cargado por la página padre, lo usa tal cual.
 * Si no, lo consulta aquí.
 */
if (!isset($banners_home)) {
    $banners_home = [];
    try {
        $_hc_db = getDB();
        $_hc_db->query("SELECT 1 FROM banners_home LIMIT 1");
        $stmt = $_hc_db->query("SELECT * FROM banners_home WHERE activo = 1 ORDER BY orden ASC, id ASC");
        $banners_home = $stmt->fetchAll();
    } catch (Exception $e) {
        $banners_home = [];
    }
}

$_hero_titulo_default    = 'Portal Estratégico de Parques Industriales';
$_hero_subtitulo_default = 'Información del desarrollo industrial de la provincia de Catamarca';
$_hero_imagen_fallback   = (defined('PUBLIC_URL') ? PUBLIC_URL : '') . '/img/hero-parque.jpg';
?>

<?php if (!empty($banners_home)): ?>
<section class="hero-section hero-carousel-wrap">
    <div id="heroCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="5000">
        <div class="carousel-inner h-100">
            <?php foreach ($banners_home as $i => $b): ?>
            <?php
            $slide_imagen = $_hero_imagen_fallback;
            if (($b['tipo'] ?? '') !== 'video' && !empty($b['imagen'])) {
                $img = trim($b['imagen']);
                if (preg_match('#^https?://#i', $img)) {
                    $slide_imagen = $img;  // URL absoluta (Cloudinary, etc.)
                } else {
                    $slide_imagen = rtrim(PUBLIC_URL, '/') . '/' . ltrim($img, '/');
                }
            }
            $slide_titulo    = trim($b['titulo']    ?? '') ?: $_hero_titulo_default;
            $slide_subtitulo = trim($b['subtitulo'] ?? '') ?: $_hero_subtitulo_default;
            ?>
            <div class="carousel-item h-100 <?= $i === 0 ? 'active' : '' ?>">
                <?php if (($b['tipo'] ?? '') === 'video' && !empty($b['url_video'])): ?>
                <div class="hero-slide hero-slide-video">
                    <iframe src="<?= e($b['url_video']) ?>" title="Video banner" allowfullscreen class="hero-video-iframe"></iframe>
                </div>
                <?php else: ?>
                <div class="hero-slide" style="background-image: url('<?= e($slide_imagen) ?>');"></div>
                <?php endif; ?>
                <div class="hero-content">
                    <h1><?= e($slide_titulo) ?></h1>
                    <p class="hero-subtitle"><?= e($slide_subtitulo) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($banners_home) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
        <div class="carousel-indicators">
            <?php foreach ($banners_home as $i => $b): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>"
                    class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php else: ?>

<section class="hero-section" style="background-image: url('<?= e($_hero_imagen_fallback) ?>');">
    <div class="hero-content">
        <h1><?= e($_hero_titulo_default) ?></h1>
        <p class="hero-subtitle"><?= e($_hero_subtitulo_default) ?></p>
    </div>
</section>

<?php endif; ?>
