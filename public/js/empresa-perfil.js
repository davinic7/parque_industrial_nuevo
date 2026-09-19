/**
 * Perfil de la empresa (public/empresa/perfil.php): formato de CUIT, mapa, lote, galería y guardado.
 * Requiere el objeto global __CFG que define perfil.php (token CSRF, coordenadas y estado del lote).
 */
(function () {
    /* ---- CUIT formatter ---- */
    const cuitEl = document.getElementById('inputCuit');
    if (cuitEl) {
        function fmtCuit(raw) {
            let d = String(raw).replace(/\D/g, '').slice(0, 11);
            if (d.length <= 2)  return d;
            if (d.length <= 10) return d.slice(0, 2) + '-' + d.slice(2);
            return d.slice(0, 2) + '-' + d.slice(2, 10) + '-' + d.slice(10);
        }
        cuitEl.addEventListener('input', function () {
            const cur = this.selectionStart, before = this.value.length;
            this.value = fmtCuit(this.value);
            const after = this.value.length;
            try { this.setSelectionRange(cur + (after - before), cur + (after - before)); } catch(e){}
        });
    }

    /* ---- Logo preview ---- */
    document.getElementById('logoFileInput').addEventListener('change', function () {
        if (this.files[0]) {
            const r = new FileReader();
            r.onload = ev => document.getElementById('logoPreview').src = ev.target.result;
            r.readAsDataURL(this.files[0]);
            document.getElementById('logoFileName').textContent = this.files[0].name;
        }
    });

    /* ---- Map ---- */
    const C        = __CFG;
    const initLat  = C.hasCoords ? C.initLat : C.defLat;
    const initLng  = C.hasCoords ? C.initLng : C.defLng;
    const initZoom = C.hasCoords ? 16 : 14;

    const map = L.map('mapPicker').setView([initLat, initLng], initZoom);
    var capaSat = ParqueLeaflet.addSatelliteLayer(map);
    map.setMinZoom(7);
    map.setMaxBounds(null);
    var capaOsm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });
    L.control.layers({ 'Satélite': capaSat, 'Mapa': capaOsm }, {}, { position: 'topright', collapsed: false }).addTo(map);
    ParqueLeaflet.addParquePolygon(map);

    let marker = null;

    /* Detección dentro/fuera del polígono del parque (ray-casting) */
    function pointInPark(lat, lng) {
        var poly = ParqueLeaflet.PANTANILLO_POLYGON;
        var inside = false;
        for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
            var xi = poly[i][0], yi = poly[i][1];
            var xj = poly[j][0], yj = poly[j][1];
            if (((yi > lng) !== (yj > lng)) &&
                (lat < (xj - xi) * (lng - yi) / (yj - yi) + xi)) {
                inside = !inside;
            }
        }
        return inside;
    }

    /* Muestra/oculta la sección de lote según si las coords están dentro del parque */
    function updateLoteSection(lat, lng) {
        var inside = pointInPark(lat, lng);
        document.getElementById('sectionLote').classList.toggle('d-none', !inside);
        document.getElementById('dentroPque').value = inside ? '1' : '0';
        if (!inside) return;
        var estado = C.loteEstado;
        document.getElementById('loteAsignadoAlert').classList.toggle('d-none', estado !== 'asignado');
        document.getElementById('loteInputWrap').classList.toggle('d-none', estado === 'asignado');
    }

    function updateShareLinks(lat, lng) {
        var mapsUrl = 'https://www.google.com/maps?q=' + lat.toFixed(6) + ',' + lng.toFixed(6);
        var waText  = encodeURIComponent('📍 Ubicación Parque Industrial: ' + mapsUrl);
        document.getElementById('btnGoogleMaps').href = mapsUrl;
        document.getElementById('btnWhatsApp').href   = 'https://wa.me/?text=' + waText;
        document.getElementById('btnCopiarCoords').onclick = function() {
            navigator.clipboard.writeText(mapsUrl).then(function() {
                var btn = document.getElementById('btnCopiarCoords');
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Copiado';
                setTimeout(function() { btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i>Copiar enlace'; }, 2000);
            });
        };
    }

    /* Actualiza todo al cambiar coords (desde click o drag) */
    function applyCoords(lat, lng) {
        document.getElementById('latitud').value  = lat.toFixed(8);
        document.getElementById('longitud').value = lng.toFixed(8);
        updateShareLinks(lat, lng);
        updateLoteSection(lat, lng);
        document.getElementById('coordsDisplay').classList.remove('d-none');
    }

    /* Actualiza todo al escribir manualmente (no toca los inputs) */
    function applyCoordsFromInput(lat, lng) {
        updateShareLinks(lat, lng);
        updateLoteSection(lat, lng);
        document.getElementById('coordsDisplay').classList.remove('d-none');
    }

    function attachDragEnd(m) {
        m.on('dragend', function(ev) {
            var p = ev.target.getLatLng();
            applyCoords(p.lat, p.lng);
        });
    }

    if (C.hasCoords) {
        marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
        attachDragEnd(marker);
        updateShareLinks(initLat, initLng);
        updateLoteSection(initLat, initLng);
    }

    map.on('click', function (e) {
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { draggable: true }).addTo(map);
            attachDragEnd(marker);
        }
        applyCoords(e.latlng.lat, e.latlng.lng);
    });

    /* Sincronización al escribir lat/lng manualmente */
    function onCoordInput() {
        var lat = parseFloat(document.getElementById('latitud').value);
        var lng = parseFloat(document.getElementById('longitud').value);
        if (isNaN(lat) || isNaN(lng)) return;
        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            attachDragEnd(marker);
        }
        map.panTo([lat, lng]);
        applyCoordsFromInput(lat, lng);
    }
    document.getElementById('latitud').addEventListener('change', onCoordInput);
    document.getElementById('longitud').addEventListener('change', onCoordInput);

    /* Advertencia: si hay lote pendiente y se guarda fuera del parque, se pierde */
    var _lotePendienteNumero = __CFG.loteDeclarado;
    var _formConfirmado = false;
    document.getElementById('formPerfil').addEventListener('submit', function(e) {
        if (_formConfirmado) return;
        if (C.loteEstado === 'pendiente' && document.getElementById('dentroPque').value === '0') {
            e.preventDefault();
            document.getElementById('lotePerdidoNumero').textContent = _lotePendienteNumero;
            var modal = new bootstrap.Modal(document.getElementById('modalLotePerdido'));
            modal.show();
            document.getElementById('btnConfirmarGuardar').onclick = function() {
                _formConfirmado = true;
                modal.hide();
                document.getElementById('formPerfil').submit();
            };
        }
    });

    /* ---- Gallery AJAX ---- */
    const CSRF_NAME = C.csrfName;
    const CSRF_VAL  = C.csrfVal;
    const API       = 'galeria_api.php';

    function galleryUpdateCount() {
        const n = document.querySelectorAll('[id^="gitem-"]').length;
        document.getElementById('galleryCount').textContent = n;
        document.getElementById('galleryEmpty').classList.toggle('d-none', n > 0);
    }

    function makeThumbEl(id, url, modal) {
        const wrap = document.createElement('div');
        wrap.className = 'gallery-item';
        wrap.id = (modal ? 'mgitem-' : 'gitem-') + id;
        const img = document.createElement('img');
        img.src = url; img.alt = 'Foto'; img.className = 'gallery-thumb';
        if (modal) { img.style.width = '90px'; img.style.height = '90px'; }
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-danger ' + (modal ? 'btn-del-img-modal' : 'btn-del-img');
        btn.dataset.id = id; btn.title = 'Eliminar'; btn.textContent = '\u00D7';
        wrap.appendChild(img); wrap.appendChild(btn);
        return wrap;
    }

    function addThumbToAll(id, url) {
        const inlineEl = makeThumbEl(id, url, false);
        const modalEl  = makeThumbEl(id, url, true);
        document.getElementById('galleryThumbsInline').appendChild(inlineEl);
        document.getElementById('galleryGrid').appendChild(modalEl);
        galleryUpdateCount();
        inlineEl.querySelector('.btn-del-img').addEventListener('click', function() {
            if (confirm('\u00BFEliminar esta imagen?')) deleteImage(this.dataset.id);
        });
        modalEl.querySelector('.btn-del-img-modal').addEventListener('click', function() {
            if (confirm('\u00BFEliminar esta imagen?')) deleteImage(this.dataset.id);
        });
    }

    function removeThumbFromAll(id) {
        ['gitem-' + id, 'mgitem-' + id].forEach(function(sid) {
            const el = document.getElementById(sid);
            if (el) el.remove();
        });
        galleryUpdateCount();
    }

    async function uploadFile(file) {
        const prog = document.getElementById('uploadProgress');
        prog.classList.remove('d-none');
        const fd = new FormData();
        fd.append(CSRF_NAME, CSRF_VAL);
        fd.append('accion', 'subir');
        fd.append('imagen', file);
        try {
            const res  = await fetch(API, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) addThumbToAll(data.id, data.url);
            else alert('Error al subir: ' + data.error);
        } catch(e) {
            alert('Error de red al subir imagen');
        } finally {
            prog.classList.add('d-none');
        }
    }

    async function deleteImage(id) {
        const fd = new FormData();
        fd.append(CSRF_NAME, CSRF_VAL);
        fd.append('accion', 'eliminar');
        fd.append('imagen_id', id);
        try {
            const res  = await fetch(API, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) removeThumbFromAll(id);
            else alert('Error al eliminar: ' + data.error);
        } catch(e) {
            alert('Error de red');
        }
    }

    document.querySelectorAll('.btn-del-img').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (confirm('\u00BFEliminar esta imagen?')) deleteImage(this.dataset.id);
        });
    });
    document.querySelectorAll('.btn-del-img-modal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (confirm('\u00BFEliminar esta imagen?')) deleteImage(this.dataset.id);
        });
    });

    const fileInput = document.getElementById('galeriaFileInput');
    const dropZone  = document.getElementById('dropZone');

    dropZone.addEventListener('click', function() { fileInput.click(); });
    dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', function() { dropZone.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault(); dropZone.classList.remove('dragover');
        Array.from(e.dataTransfer.files).forEach(uploadFile);
    });
    fileInput.addEventListener('change', function() {
        Array.from(this.files).forEach(uploadFile);
        this.value = '';
    });
})();
