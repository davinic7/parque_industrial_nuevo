<?php
/**
 * Contactos de emergencia y servicios — utilidades compartidas
 * Usado por: public/contactos.php, public/empresa/contactos.php,
 * public/ministerio/contactos-emergencia.php
 */
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/** Categorías: clave => [etiqueta, ícono Bootstrap Icons, color] */
function contactos_categorias(): array
{
    return [
        'emergencias'  => ['Emergencias',                    'bi-exclamation-triangle-fill', '#b42318'],
        'electricidad' => ['Energía eléctrica',              'bi-lightning-charge-fill',     '#b7791f'],
        'agua'         => ['Agua y cloacas',                 'bi-droplet-fill',              '#1d6fa5'],
        'gas'          => ['Gas',                            'bi-fire',                      '#c8641e'],
        'parque'       => ['Administración y seguridad del parque', 'bi-building-fill',      '#1b3a5c'],
        'salud'        => ['Salud',                          'bi-heart-pulse-fill',          '#2e7d4f'],
        'ministerio'   => ['Ministerio',                     'bi-bank2',                     '#1b3a5c'],
        'otros'        => ['Otros servicios',                'bi-telephone-fill',            '#5f6b76'],
    ];
}

/** true si la tabla existe (instalaciones anteriores a la migración 023). */
function contactos_tabla_disponible(PDO $db): bool
{
    try {
        $db->query('SELECT 1 FROM contactos_emergencia LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Contactos activos agrupados por categoría (en el orden de contactos_categorias()).
 * $solo_publicos = true para el sitio público; false para empresas (ven todos).
 */
function contactos_agrupados(PDO $db, bool $solo_publicos): array
{
    if (!contactos_tabla_disponible($db)) {
        return [];
    }
    $sql = 'SELECT * FROM contactos_emergencia WHERE activo = 1'
         . ($solo_publicos ? " AND visibilidad = 'publico'" : '')
         . ' ORDER BY orden ASC, nombre ASC';
    $filas = $db->query($sql)->fetchAll();

    $grupos = [];
    foreach (array_keys(contactos_categorias()) as $cat) {
        $grupos[$cat] = [];
    }
    foreach ($filas as $f) {
        $cat = isset($grupos[$f['categoria']]) ? $f['categoria'] : 'otros';
        $grupos[$cat][] = $f;
    }
    return array_filter($grupos);
}

/** Enlace tel: a partir de un número escrito libremente ("(0383) 443-1234"). */
function contactos_tel_href(string $tel): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $tel);
}

/** Dibuja el directorio agrupado en tarjetas (sitio público y panel de empresa). */
function contactos_render(array $grupos, bool $mostrar_visibilidad = false): void
{
    $cats = contactos_categorias();
    if (empty($grupos)) {
        echo '<div class="text-center text-muted py-5"><i class="bi bi-telephone fs-1 d-block mb-2"></i>'
           . 'Todavía no hay contactos cargados.</div>';
        return;
    }
    foreach ($grupos as $cat => $items) {
        [$label, $icon, $color] = $cats[$cat];
        echo '<div class="mb-4">';
        echo '<h2 class="h5 mb-3" style="color:' . e($color) . '"><i class="bi ' . e($icon) . ' me-2"></i>' . e($label) . '</h2>';
        echo '<div class="row g-3">';
        foreach ($items as $c) {
            echo '<div class="col-md-6 col-lg-4"><div class="card h-100 border-0 shadow-sm" style="border-left:4px solid ' . e($color) . ' !important">';
            echo '<div class="card-body">';
            echo '<div class="fw-semibold mb-1">' . e($c['nombre']);
            if ($mostrar_visibilidad && $c['visibilidad'] === 'empresas') {
                echo ' <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" title="Sólo visible para empresas del parque"><i class="bi bi-lock-fill"></i> Empresas</span>';
            }
            echo '</div>';
            if (!empty($c['descripcion'])) {
                echo '<div class="small text-muted mb-2">' . e($c['descripcion']) . '</div>';
            }
            echo '<a href="' . e(contactos_tel_href($c['telefono'])) . '" class="btn btn-sm btn-outline-primary me-1 mb-1">'
               . '<i class="bi bi-telephone-fill me-1"></i>' . e($c['telefono']) . '</a>';
            if (!empty($c['telefono_alt'])) {
                echo '<a href="' . e(contactos_tel_href($c['telefono_alt'])) . '" class="btn btn-sm btn-outline-secondary mb-1">'
                   . '<i class="bi bi-telephone me-1"></i>' . e($c['telefono_alt']) . '</a>';
            }
            echo '</div></div></div>';
        }
        echo '</div></div>';
    }
}
