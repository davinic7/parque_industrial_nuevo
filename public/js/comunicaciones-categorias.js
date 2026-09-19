/**
 * Categorías del Centro de Comunicaciones (includes/partials/comunicaciones_panel.php):
 * las categorías de la barra lateral se pueden reordenar y el orden se recuerda en localStorage.
 * Va después de la carga de SortableJS.
 */
// Re-init sortable after CDN loads (si cargó después del IIFE)
(function () {
    var container = document.getElementById('coms-cat-sortable');
    if (container && typeof Sortable !== 'undefined' && !container._sortable) {
        container._sortable = true;
        Sortable.create(container, {
            animation: 150,
            ghostClass: 'bg-light',
            handle: '.nav-link',
            onEnd: function () {
                var orden = [];
                container.querySelectorAll('.nav-link[data-filter-categoria]').forEach(function (el) {
                    orden.push(el.dataset.filterCategoria);
                });
                try { localStorage.setItem('coms_cat_orden', JSON.stringify(orden)); } catch (e) {}
            }
        });
    }
})();
