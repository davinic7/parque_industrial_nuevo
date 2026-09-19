/**
 * Formulario dinámico de la empresa (public/empresa/formulario_dinamico.php): contador de caracteres,
 * filtro numérico y mapas para las preguntas de tipo "dirección" y "ubicación".
 * Requiere el objeto global FD_CFG que define formulario_dinamico.php:
 *   { defLat, defLng, mapas: [ { pid, tipo: 'direccion' | 'ubicacion' } ] }
 * y, para los mapas, Leaflet y ParqueLeaflet (public/js/parque-leaflet.js).
 */

// ── Contador de caracteres ─────────────────────────────────
document.querySelectorAll('.campo-con-contador').forEach(function(el) {
    var max   = parseInt(el.getAttribute('maxlength')) || 0;
    var wrap  = el.parentElement.querySelector('.char-counter-wrap');
    var span  = wrap ? wrap.querySelector('.chars-remaining') : null;
    if (!span || !max) return;

    // Filtro numérico para campos número
    if (el.getAttribute('inputmode') === 'decimal') {
        el.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.,\-]/g, '');
        });
    }

    function actualizar() {
        var rem = max - el.value.length;
        span.textContent = rem;
        if (wrap) {
            wrap.classList.toggle('text-danger',  rem <= 5);
            wrap.classList.toggle('text-warning', rem > 5 && rem <= 20);
        }
    }
    el.addEventListener('input', actualizar);
    actualizar(); // Inicializar con valor actual
});

// Mapa de instancias para campos direccion (accedido por dirActualizarMapa, que se llama desde el HTML)
var _dirMaps = {};

function dirActualizarMapa(id) {
    var latInput = document.getElementById('dir_lat_' + id);
    var lngInput = document.getElementById('dir_lng_' + id);
    var mapInst = _dirMaps[id];
    if (!latInput || !lngInput || !mapInst) return;
    var lat = parseFloat(latInput.value);
    var lng = parseFloat(lngInput.value);
    if (isNaN(lat) || isNaN(lng)) return;
    mapInst.map.setView([lat, lng], 16);
    if (mapInst.marker) mapInst.map.removeLayer(mapInst.marker);
    mapInst.marker = L.marker([lat, lng]).addTo(mapInst.map);
    var hidden = document.getElementById('campo_' + id);
    if (hidden) hidden.value = lat.toFixed(8) + ',' + lng.toFixed(8);
}

// Pregunta de tipo "direccion": mapa con marcador arrastrable y campos de latitud / longitud
function initDireccion(pid) {
    var latInput = document.getElementById('dir_lat_' + pid);
    var lngInput = document.getElementById('dir_lng_' + pid);
    var mapEl   = document.getElementById('mapDir' + pid);
    var hidden  = document.getElementById('campo_' + pid);
    if (!latInput || !lngInput || !mapEl || !hidden) return;

    var defLat = FD_CFG.defLat;
    var defLng = FD_CFG.defLng;
    var initLat = defLat, initLng = defLng, hasVal = false;

    if (hidden.value && hidden.value.includes(',')) {
        var parts = hidden.value.split(',');
        var lp = parseFloat(parts[0]), lgp = parseFloat(parts[1]);
        if (!isNaN(lp) && !isNaN(lgp)) {
            initLat = lp; initLng = lgp;
            latInput.value = lp.toFixed(6);
            lngInput.value = lgp.toFixed(6);
            hasVal = true;
        }
    }

    var map = L.map(mapEl).setView([initLat, initLng], hasVal ? 16 : 14);
    ParqueLeaflet.addSatelliteLayer(map);
    ParqueLeaflet.addParquePolygon(map);

    var marker = null;
    if (hasVal) {
        marker = L.marker([initLat, initLng], {draggable: true}).addTo(map);
        marker.on('dragend', function(ev) {
            var pos = ev.target.getLatLng();
            latInput.value = pos.lat.toFixed(6);
            lngInput.value = pos.lng.toFixed(6);
            hidden.value = pos.lat.toFixed(8) + ',' + pos.lng.toFixed(8);
        });
    }

    _dirMaps[pid] = { map: map, marker: marker };

    map.on('click', function(ev) {
        if (_dirMaps[pid].marker) map.removeLayer(_dirMaps[pid].marker);
        var m = L.marker(ev.latlng, {draggable: true}).addTo(map);
        m.on('dragend', function(de) {
            var pos = de.target.getLatLng();
            latInput.value = pos.lat.toFixed(6);
            lngInput.value = pos.lng.toFixed(6);
            hidden.value = pos.lat.toFixed(8) + ',' + pos.lng.toFixed(8);
        });
        _dirMaps[pid].marker = m;
        latInput.value = ev.latlng.lat.toFixed(6);
        lngInput.value = ev.latlng.lng.toFixed(6);
        hidden.value = ev.latlng.lat.toFixed(8) + ',' + ev.latlng.lng.toFixed(8);
    });
}

// Pregunta cuya etiqueta contiene "ubicación": mapa donde un clic fija "lat,lng" en el campo
function initUbicacion(pid) {
    const input = document.getElementById('campo_' + pid);
    const mapEl = document.getElementById('mapUbicacion' + pid);
    if (!input || !mapEl) return;

    let lat = FD_CFG.defLat;
    let lng = FD_CFG.defLng;

    if (input.value && input.value.includes(',')) {
        const parts = input.value.split(',');
        const latParsed = parseFloat(parts[0]);
        const lngParsed = parseFloat(parts[1]);
        if (!isNaN(latParsed) && !isNaN(lngParsed)) {
            lat = latParsed;
            lng = lngParsed;
        }
    }

    const map = L.map(mapEl).setView([lat, lng], 14);
    ParqueLeaflet.addSatelliteLayer(map);
    ParqueLeaflet.addParquePolygon(map);

    let marker = null;
    if (input.value && input.value.includes(',')) {
        marker = L.marker([lat, lng]).addTo(map);
    }

    map.on('click', function(e) {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);
        const latStr = e.latlng.lat.toFixed(8);
        const lngStr = e.latlng.lng.toFixed(8);
        input.value = latStr + ',' + lngStr;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    (FD_CFG.mapas || []).forEach(function(m) {
        if (m.tipo === 'direccion') initDireccion(m.pid);
        else initUbicacion(m.pid);
    });
});
