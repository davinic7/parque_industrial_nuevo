/**
 * Lotes del parque (public/ministerio/lotes.php): mapa, listado, alta/edición, dibujo de terrenos y exportación.
 * Requiere el objeto global LOTES_CFG que define lotes.php: { apiBase, csrf, lotes }.
 */
(function () {
    'use strict';

    var API_BASE   = LOTES_CFG.apiBase;
    var CSRF_TOKEN = LOTES_CFG.csrf;
    var LOTES_DATA = LOTES_CFG.lotes;
    var COLORES    = { disponible: '#198754', ocupado: '#dc3545', reservado: '#fd7e14' };

    // ── Utilidades ──────────────────────────────────────────────
    function csrfFetch(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(body)
        }).then(function (r) { return r.json(); });
    }

    function showToast(msg, tipo) {
        var el = document.createElement('div');
        el.className = 'position-fixed bottom-0 end-0 p-3';
        el.style.zIndex = 9999;
        var colorClass = tipo === 'ok' ? 'success' : 'danger';
        var wrapper = document.createElement('div');
        wrapper.className = 'toast show align-items-center text-white bg-' + colorClass + ' border-0';
        var inner = document.createElement('div');
        inner.className = 'd-flex';
        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = msg;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-close btn-close-white me-2 m-auto';
        btn.addEventListener('click', function () { if (el.parentNode) el.parentNode.removeChild(el); });
        inner.appendChild(body);
        inner.appendChild(btn);
        wrapper.appendChild(inner);
        el.appendChild(wrapper);
        document.body.appendChild(el);
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 3500);
    }

    function spinner(id, show) {
        var el = document.getElementById(id);
        if (el) el.classList.toggle('d-none', !show);
    }

    // GeoJSON [lng,lat] → Leaflet [lat,lng]
    function geoToLeaflet(coords) {
        return coords.map(function (c) { return [c[1], c[0]]; });
    }
    // Leaflet → GeoJSON Polygon (cierra el anillo)
    function leafletToGeo(latLngs) {
        var ring = latLngs.map(function (ll) { return [ll.lng, ll.lat]; });
        ring.push(ring[0]);
        return { type: 'Polygon', coordinates: [ring] };
    }
    // Área en m² (fórmula de Shoelace con proyección esférica)
    function calcAreaM2(latLngs) {
        var n = latLngs.length, area = 0, R = 6378137;
        for (var i = 0, j = n - 1; i < n; j = i++) {
            var pi = latLngs[i], pj = latLngs[j];
            var xi = pj.lng * Math.PI / 180 * R * Math.cos(pi.lat * Math.PI / 180);
            var yi = pi.lat * Math.PI / 180 * R;
            var xj = pi.lng * Math.PI / 180 * R * Math.cos(pj.lat * Math.PI / 180);
            var yj = pj.lat * Math.PI / 180 * R;
            area += (xj - xi) * (yj + yi);
        }
        return Math.abs(area / 2);
    }

    // ── Mapa general (lado derecho, siempre visible) ─────────────
    var mapaG = L.map('mapaGeneral').setView([-28.5337, -65.8010], 15);
    var capaSat = ParqueLeaflet.addSatelliteLayer(mapaG);
    // Liberar restricciones — permitir zoom y paneo por toda la provincia
    mapaG.setMinZoom(7);
    mapaG.setMaxBounds(null);
    var capaOsm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });
    L.control.layers({ 'Satélite': capaSat, 'Mapa': capaOsm }, {}, { position: 'topright', collapsed: false }).addTo(mapaG);
    ParqueLeaflet.addParquePolygon(mapaG, { fillOpacity: 0.05, weight: 2 });

    var poligonosPorId = {}; // id → L.Polygon

    function colorLote(estado) { return COLORES[estado] || '#6c757d'; }

    LOTES_DATA.forEach(function (lote) {
        if (!lote.geometria_terreno || !lote.geometria_terreno.coordinates) return;
        var coords  = lote.geometria_terreno.coordinates[0];
        var latLngs = geoToLeaflet(coords.slice(0, -1));
        var color   = colorLote(lote.estado);

        var poly = L.polygon(latLngs, {
            color: color, weight: 2, fillColor: color, fillOpacity: 0.28
        }).addTo(mapaG);

        poly.bindPopup(buildPopup(lote), { maxWidth: 230 });

        poly.on('mouseover', function () { this.setStyle({ weight: 4, fillOpacity: 0.45 }); resaltarFila(lote.id, true); });
        poly.on('mouseout',  function () { this.setStyle({ weight: 2, fillOpacity: 0.28 }); resaltarFila(lote.id, false); });
        poly.on('click',     function () { scrollToFila(lote.id); });

        poligonosPorId[lote.id] = poly;
    });

    // Zoom a todos los polígonos al iniciar
    var todosLatLngs = Object.values(poligonosPorId).map(function (p) { return p.getLatLngs()[0]; }).flat();
    if (todosLatLngs.length) mapaG.fitBounds(todosLatLngs, { padding: [20, 20], maxZoom: 18 });

    // Leyenda
    var legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
        var d = L.DomUtil.create('div');
        d.style.cssText = 'background:#fff;padding:7px 11px;border-radius:8px;font-size:.76rem;box-shadow:0 2px 8px rgba(0,0,0,.2);line-height:1.9;';
        d.innerHTML = ['disponible','ocupado','reservado'].map(function (e) {
            return '<div><span style="display:inline-block;width:11px;height:11px;border-radius:2px;background:' +
                COLORES[e] + ';margin-right:5px;vertical-align:middle;"></span>' +
                e.charAt(0).toUpperCase() + e.slice(1) + '</div>';
        }).join('');
        return d;
    };
    legend.addTo(mapaG);

    function buildPopup(lote) {
        var color = colorLote(lote.estado);
        return '<div style="min-width:160px;font-family:inherit;">' +
            '<div style="font-weight:700;font-size:.9rem;color:#1a5276;">Lote ' + lote.numero_lote + '</div>' +
            (lote.sector ? '<div style="font-size:.78rem;color:#64748b;">Sector: ' + lote.sector + '</div>' : '') +
            (lote.superficie_m2 ? '<div style="font-size:.78rem;">Sup: ' + Math.round(lote.superficie_m2).toLocaleString('es-AR') + ' m²</div>' : '') +
            '<span style="display:inline-block;margin:4px 0;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600;color:#fff;background:' + color + ';">' +
            lote.estado.charAt(0).toUpperCase() + lote.estado.slice(1) + '</span>' +
            (lote.empresa_nombre ? '<div style="font-size:.78rem;"><i class="fa-solid fa-building" style="margin-right:3px;"></i>' + lote.empresa_nombre + '</div>' : '') +
            '</div>';
    }

    // Sincronización tabla ↔ mapa
    function resaltarFila(id, activo) {
        var tr = document.querySelector('#tablaLotes tr[data-id="' + id + '"]');
        if (tr) tr.classList.toggle('lote-activa', activo);
    }

    function scrollToFila(id) {
        var tr = document.querySelector('#tablaLotes tr[data-id="' + id + '"]');
        if (!tr) return;
        document.querySelectorAll('#tablaLotes tr.lote-activa').forEach(function (r) { r.classList.remove('lote-activa'); });
        tr.classList.add('lote-activa');
        tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Hover de fila → resaltar polígono
    document.querySelectorAll('#tablaLotes tbody tr[data-id]').forEach(function (tr) {
        var id = parseInt(tr.dataset.id, 10);
        tr.addEventListener('mouseenter', function () {
            var p = poligonosPorId[id];
            if (p) p.setStyle({ weight: 4, fillOpacity: 0.45 });
        });
        tr.addEventListener('mouseleave', function () {
            var p = poligonosPorId[id];
            if (p) p.setStyle({ weight: 2, fillOpacity: 0.28 });
        });
        // Click en fila → zoom al polígono
        tr.addEventListener('click', function (e) {
            if (e.target.closest('button')) return; // no interferir con botones
            var p = poligonosPorId[id];
            if (p) {
                mapaG.fitBounds(p.getBounds(), { padding: [40, 40], maxZoom: 18 });
                p.openPopup();
            }
        });
    });

    // Función para actualizar el polígono en el mapa general sin recargar
    function actualizarPoligonoEnMapa(id, geojson, superficie) {
        // Eliminar anterior
        if (poligonosPorId[id]) { mapaG.removeLayer(poligonosPorId[id]); delete poligonosPorId[id]; }
        if (!geojson) return;

        // Actualizar datos en memoria
        var lote = LOTES_DATA.find(function (l) { return l.id === id; });
        if (lote) { lote.geometria_terreno = geojson; lote.superficie_m2 = superficie; }

        var coords  = geojson.coordinates[0];
        var latLngs = geoToLeaflet(coords.slice(0, -1));
        var color   = colorLote(lote ? lote.estado : 'disponible');

        var poly = L.polygon(latLngs, {
            color: color, weight: 2, fillColor: color, fillOpacity: 0.28
        }).addTo(mapaG);
        if (lote) poly.bindPopup(buildPopup(lote), { maxWidth: 230 });
        poly.on('mouseover', function () { this.setStyle({ weight: 4, fillOpacity: 0.45 }); resaltarFila(id, true); });
        poly.on('mouseout',  function () { this.setStyle({ weight: 2, fillOpacity: 0.28 }); resaltarFila(id, false); });
        poly.on('click',     function () { scrollToFila(id); });
        poligonosPorId[id] = poly;

        // Actualizar celda m² en la tabla sin recargar
        var tr = document.querySelector('#tablaLotes tr[data-id="' + id + '"]');
        if (tr && superficie !== null) {
            var cell = tr.querySelector('.cell-m2');
            if (cell) cell.textContent = Math.round(superficie).toLocaleString('es-AR');
            tr.classList.remove('lote-row-sin-geo');
        }
    }

    // ── CRUD: modales ────────────────────────────────────────────
    var modalLote     = new bootstrap.Modal(document.getElementById('modalLote'));
    var modalMapa     = new bootstrap.Modal(document.getElementById('modalMapa'));
    var modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));
    var eliminarId    = null;

    document.getElementById('btnNuevoLote').addEventListener('click', function () {
        document.getElementById('modalLoteLabel').textContent = 'Nuevo lote';
        document.getElementById('formLote').reset();
        document.getElementById('loteId').value = '';
        document.getElementById('superficieHint').textContent = '';
    });

    document.querySelectorAll('.btn-editar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tr = this.closest('tr');
            var id = parseInt(tr.dataset.id, 10);
            var lote = LOTES_DATA.find(function (l) { return l.id === id; });
            document.getElementById('modalLoteLabel').textContent = 'Editar lote';
            document.getElementById('loteId').value       = tr.dataset.id;
            document.getElementById('loteNumero').value   = tr.dataset.numero;
            document.getElementById('loteSector').value   = tr.dataset.sector;
            document.getElementById('loteSuperficie').value = tr.dataset.superficie;
            document.getElementById('loteEstado').value   = tr.dataset.estado;
            document.getElementById('loteEmpresa').value  = tr.dataset.empresa || '';
            // Mostrar área del polígono si existe
            if (lote && lote.geometria_terreno && lote.geometria_terreno.coordinates) {
                var lls = geoToLeaflet(lote.geometria_terreno.coordinates[0].slice(0, -1));
                var area = Math.round(calcAreaM2(lls));
                document.getElementById('superficieHint').textContent = 'Área del polígono: ' + area.toLocaleString('es-AR') + ' m²';
            } else {
                document.getElementById('superficieHint').textContent = '';
            }
            modalLote.show();
        });
    });

    document.getElementById('btnGuardarLote').addEventListener('click', function () {
        var numero = document.getElementById('loteNumero').value.trim();
        if (!numero) { document.getElementById('loteNumero').focus(); return; }
        var id = document.getElementById('loteId').value;
        spinner('spinnerGuardar', true); this.disabled = true; var me = this;
        var payload = {
            numero_lote:   numero,
            sector:        document.getElementById('loteSector').value.trim(),
            superficie_m2: document.getElementById('loteSuperficie').value || null,
            estado:        document.getElementById('loteEstado').value,
            empresa_id:    document.getElementById('loteEmpresa').value || null
        };
        if (id) payload.id = parseInt(id, 10);
        csrfFetch(API_BASE + 'guardar.php', payload).then(function (res) {
            spinner('spinnerGuardar', false); me.disabled = false;
            if (res.status === 'ok') { showToast(res.mensaje, 'ok'); setTimeout(function () { location.reload(); }, 700); }
            else showToast(res.mensaje || 'Error al guardar.', 'error');
        }).catch(function () { spinner('spinnerGuardar', false); me.disabled = false; showToast('Error de red.', 'error'); });
    });

    document.querySelectorAll('.btn-eliminar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            eliminarId = parseInt(this.dataset.id, 10);
            document.getElementById('eliminarNumero').textContent = this.dataset.numero;
            modalEliminar.show();
        });
    });

    document.getElementById('btnConfirmarEliminar').addEventListener('click', function () {
        if (!eliminarId) return;
        spinner('spinnerEliminar', true); this.disabled = true; var me = this;
        csrfFetch(API_BASE + 'eliminar.php', { id: eliminarId }).then(function (res) {
            spinner('spinnerEliminar', false); me.disabled = false;
            if (res.status === 'ok') { showToast('Lote eliminado.', 'ok'); setTimeout(function () { location.reload(); }, 700); }
            else { showToast(res.mensaje || 'Error.', 'error'); modalEliminar.hide(); }
        }).catch(function () { spinner('spinnerEliminar', false); me.disabled = false; showToast('Error de red.', 'error'); });
    });

    // ── Editor de polígono ───────────────────────────────────────
    var mapaEditor  = null;
    var drawnItems  = null;
    var mapaLoteId  = null;
    var areaActual  = 0;

    function abrirEditorPoligono(id, numero) {
        mapaLoteId = id;
        document.getElementById('mapaLoteNumero').textContent = numero;
        document.getElementById('mapaAreaInfo').textContent = '';
        modalMapa.show();
    }
    window.abrirEditorPoligono = abrirEditorPoligono;

    document.querySelectorAll('.btn-mapa').forEach(function (btn) {
        btn.addEventListener('click', function () {
            abrirEditorPoligono(parseInt(this.dataset.id, 10), this.dataset.numero);
        });
    });

    document.getElementById('modalMapa').addEventListener('shown.bs.modal', function () {
        if (mapaEditor) { mapaEditor.remove(); mapaEditor = null; }

        var map = L.map('mapaLoteModal').setView([-28.5337, -65.8010], 16);
        var capaSatModal = ParqueLeaflet.addSatelliteLayer(map);
        map.setMinZoom(7);
        map.setMaxBounds(null);
        var capaOsmModal = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });
        L.control.layers({ 'Satélite': capaSatModal, 'Mapa': capaOsmModal }, {}, { position: 'topright', collapsed: false }).addTo(map);
        ParqueLeaflet.addParquePolygon(map, { fillOpacity: 0.05, weight: 2 });
        drawnItems = new L.FeatureGroup().addTo(map);

        map.addControl(new L.Control.Draw({
            draw: {
                polygon: { allowIntersection: false, showArea: true,
                    shapeOptions: { color: '#0d6efd', weight: 3, fillOpacity: 0.25 } },
                polyline: false, rectangle: false, circle: false, circlemarker: false, marker: false
            },
            edit: { featureGroup: drawnItems }
        }));

        function mostrarArea(lls) {
            areaActual = calcAreaM2(lls);
            document.getElementById('mapaAreaInfo').textContent = 'Área: ' + Math.round(areaActual).toLocaleString('es-AR') + ' m²';
        }

        // Cargar todos los lotes como referencia + el propio como editable
        LOTES_DATA.forEach(function (lote) {
            if (!lote.geometria_terreno || !lote.geometria_terreno.coordinates) return;
            var coords  = lote.geometria_terreno.coordinates[0];
            var latLngs = geoToLeaflet(coords.slice(0, -1));

            if (lote.id === mapaLoteId) {
                var poly = L.polygon(latLngs, { color: '#0d6efd', weight: 3, fillOpacity: 0.25 }).addTo(drawnItems);
                map.fitBounds(poly.getBounds(), { padding: [40, 40] });
                mostrarArea(poly.getLatLngs()[0]);
            } else {
                L.polygon(latLngs, {
                    color: colorLote(lote.estado), weight: 1.5, fillOpacity: 0.15, interactive: false
                }).addTo(map).bindTooltip('Lote ' + lote.numero_lote, { permanent: false });
            }
        });

        map.on(L.Draw.Event.CREATED, function (e) {
            drawnItems.clearLayers();
            drawnItems.addLayer(e.layer);
            mostrarArea(e.layer.getLatLngs()[0]);
        });
        map.on(L.Draw.Event.EDITED, function (e) {
            e.layers.eachLayer(function (layer) { mostrarArea(layer.getLatLngs()[0]); });
        });

        mapaEditor = map;
    });

    document.getElementById('modalMapa').addEventListener('hidden.bs.modal', function () {
        if (mapaEditor) { mapaEditor.remove(); mapaEditor = null; }
        drawnItems = null; mapaLoteId = null; areaActual = 0;
        document.getElementById('mapaAreaInfo').textContent = '';
    });

    document.getElementById('btnBorrarPoligono').addEventListener('click', function () {
        if (!mapaLoteId || !confirm('¿Borrar el polígono de este lote?')) return;
        var me = this; me.disabled = true;
        csrfFetch(API_BASE + 'geometria.php', { id: mapaLoteId, geometria_terreno: null, superficie_m2: null }).then(function (res) {
            me.disabled = false;
            if (res.status === 'ok') {
                actualizarPoligonoEnMapa(mapaLoteId, null, null);
                showToast('Polígono borrado.', 'ok');
                if (drawnItems) drawnItems.clearLayers();
                document.getElementById('mapaAreaInfo').textContent = '';
            } else showToast(res.mensaje || 'Error.', 'error');
        }).catch(function () { me.disabled = false; showToast('Error de red.', 'error'); });
    });

    document.getElementById('btnGuardarPoligono').addEventListener('click', function () {
        if (!mapaLoteId) return;
        var layers = drawnItems ? drawnItems.getLayers() : [];
        if (!layers.length) { showToast('Dibujá un polígono primero.', 'error'); return; }
        var geojson = leafletToGeo(layers[0].getLatLngs()[0]);
        var m2 = Math.round(calcAreaM2(layers[0].getLatLngs()[0]));
        spinner('spinnerPoligono', true); this.disabled = true; var me = this;
        var capturedId = mapaLoteId;
        csrfFetch(API_BASE + 'geometria.php', {
            id:                capturedId,
            geometria_terreno: JSON.stringify(geojson),
            superficie_m2:     m2
        }).then(function (res) {
            spinner('spinnerPoligono', false); me.disabled = false;
            if (res.status === 'ok') {
                actualizarPoligonoEnMapa(capturedId, geojson, m2);
                showToast('Polígono guardado. Superficie: ' + m2.toLocaleString('es-AR') + ' m²', 'ok');
                modalMapa.hide();
            } else showToast(res.mensaje || 'Error al guardar.', 'error');
        }).catch(function () { spinner('spinnerPoligono', false); me.disabled = false; showToast('Error de red.', 'error'); });
    });

    // ── Solicitudes pendientes: asignación rápida ───────────────
    document.querySelectorAll('.btn-asignar-sol').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var empresaId     = this.dataset.empresaId;
            var empresaNombre = this.dataset.empresaNombre;
            var loteNumero    = this.dataset.loteNumero;
            var lat  = parseFloat(this.dataset.lat);
            var lng  = parseFloat(this.dataset.lng);

            document.getElementById('modalLoteLabel').textContent = 'Asignar lote — ' + empresaNombre;
            document.getElementById('formLote').reset();
            document.getElementById('loteId').value         = '';
            document.getElementById('loteNumero').value     = loteNumero;
            document.getElementById('loteEstado').value     = 'ocupado';
            document.getElementById('loteEmpresa').value    = empresaId;
            document.getElementById('superficieHint').textContent = '';

            // Zoom al punto declarado por la empresa
            if (!isNaN(lat) && !isNaN(lng)) {
                mapaG.setView([lat, lng], 17);
                L.marker([lat, lng], { opacity: 0.5 })
                    .addTo(mapaG)
                    .bindPopup('<strong>' + empresaNombre + '</strong><br>Lote declarado: ' + loteNumero)
                    .openPopup();
            }

            modalLote.show();
        });
    });

    // ── Exportar CSV ─────────────────────────────────────────────
    document.getElementById('btnExportCsv').addEventListener('click', function () {
        var rows = [['Lote', 'Sector', 'Superficie m2', 'Estado', 'Empresa', 'Polígono']];
        LOTES_DATA.forEach(function (l) {
            rows.push([
                l.numero_lote, l.sector || '', l.superficie_m2 !== null ? l.superficie_m2 : '',
                l.estado, l.empresa_nombre || '',
                l.geometria_terreno ? 'Sí' : 'No'
            ]);
        });
        var csv = rows.map(function (r) {
            return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' }));
        a.download = 'lotes.csv'; a.click();
    });

}());
