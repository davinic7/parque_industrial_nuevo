    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <?php if (empty($compact_footer)):
                $f_desc     = get_config('footer_descripcion', 'Impulsando el desarrollo industrial de la provincia.');
                $f_email    = get_config('sitio_email',     'contacto@parqueindustrial.gob.ar');
                $f_tel      = get_config('sitio_telefono',  '(0383) 4123456');
                $f_dir      = get_config('sitio_direccion', 'San Fernando del Valle de Catamarca');
                $f_fb       = get_config('redes_facebook',  '');
                $f_ig       = get_config('redes_instagram', '');
                $f_tw       = get_config('redes_twitter',   '');
            ?>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>Parque Industrial de Catamarca</h5>
                    <p class="mb-3"><?= e($f_desc) ?></p>
                    <div class="d-flex gap-3">
                        <?php if ($f_fb): ?><a href="<?= e($f_fb) ?>" target="_blank" rel="noopener" class="fs-5" aria-label="Facebook"><i class="bi bi-facebook"></i></a><?php else: ?><span class="fs-5 text-muted" title="Sin Facebook configurado"><i class="bi bi-facebook"></i></span><?php endif; ?>
                        <?php if ($f_ig): ?><a href="<?= e($f_ig) ?>" target="_blank" rel="noopener" class="fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a><?php else: ?><span class="fs-5 text-muted" title="Sin Instagram configurado"><i class="bi bi-instagram"></i></span><?php endif; ?>
                        <?php if ($f_tw): ?><a href="<?= e($f_tw) ?>" target="_blank" rel="noopener" class="fs-5" aria-label="Twitter/X"><i class="bi bi-twitter-x"></i></a><?php else: ?><span class="fs-5 text-muted" title="Sin Twitter configurado"><i class="bi bi-twitter-x"></i></span><?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5>Enlaces</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?= PUBLIC_URL ?>/">Inicio</a></li>
                        <li class="mb-2"><a href="<?= PUBLIC_URL ?>/empresas.php">Empresas</a></li>
                        <li class="mb-2"><a href="<?= PUBLIC_URL ?>/mapa.php">Mapa</a></li>
                        <li class="mb-2"><a href="<?= PUBLIC_URL ?>/estadisticas.php">Estadísticas</a></li>
                        <li class="mb-2"><a href="<?= PUBLIC_URL ?>/contactos.php">Contactos de emergencia</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5>Contacto</h5>
                    <ul class="list-unstyled">
                        <?php if ($f_dir): ?><li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= e($f_dir) ?></li><?php endif; ?>
                        <?php if ($f_tel): ?><li class="mb-2"><i class="bi bi-telephone me-2"></i><?= e($f_tel) ?></li><?php endif; ?>
                        <?php if ($f_email): ?><li class="mb-2"><i class="bi bi-envelope me-2"></i><?= e($f_email) ?></li><?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5>Gobierno de Catamarca</h5>
                    <p class="small">Ministerio de Producción e Industria</p>
                    <img src="<?= PUBLIC_URL ?>/img/logo-gobierno.png" alt="Gobierno de Catamarca" style="max-height: 50px; opacity: 0.8;" onerror="this.style.display='none'">
                </div>
            </div>
            <?php endif; ?>
            <div class="footer-bottom">
                <p class="mb-0">&copy; <?= date('Y') ?> Parque Industrial de Catamarca - Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="<?= PUBLIC_URL ?>/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="<?= PUBLIC_URL ?>/vendor/leaflet/leaflet.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/parque-leaflet.js"></script>
    
    <!-- Chart.js -->
    <script src="<?= PUBLIC_URL ?>/vendor/chartjs/chart.umd.js"></script>
    <script src="<?= PUBLIC_URL ?>/js/chart-percent-labels.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= PUBLIC_URL ?>/js/main.js"></script>
    
    <?php if (isset($extra_js)): ?>
    <?= $extra_js ?>
    <?php endif; ?>

    <?= recaptcha_script() ?>
</body>
</html>
