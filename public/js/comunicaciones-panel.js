/**
 * Centro de Comunicaciones (includes/partials/comunicaciones_panel.php): lista de conversaciones, hilo, editor
 * con borradores, adjuntos, plantillas de respuesta, nuevas conversaciones y archivado.
 * Lee su configuración de los atributos data-* de #coms-shell (actor, api, csrf); no depende de PHP.
 */
document.addEventListener('DOMContentLoaded', function () {
    const shell = document.getElementById('coms-shell');
    const ACTOR    = shell.dataset.actor;
    const API_BASE = shell.dataset.api;
    const CSRF     = shell.dataset.csrf;

    const $list      = document.getElementById('coms-list-items');
    const $search    = document.getElementById('coms-search-input');
    const $threadEmpty = document.getElementById('coms-thread-empty');
    const $threadCont  = document.getElementById('coms-thread-content');
    const $threadMsgs  = document.getElementById('coms-thread-msgs');
    const $threadTitle = document.getElementById('coms-thread-title');
    const $threadMeta  = document.getElementById('coms-thread-meta');
    const $editorText  = document.getElementById('coms-editor-text');
    const $sendBtn     = document.getElementById('coms-send-btn');
    const $attachBtn   = document.getElementById('coms-attach-btn');
    const $fileInput   = document.getElementById('coms-file-input');
    const $attachPrev  = document.getElementById('coms-attachments-preview');
    const $draftStatus = document.getElementById('coms-draft-status');

    let state = {
        filter: { estado: 'abierta', categoria: '', buscar: '' },
        conversaciones: [],
        currentId: null,
        currentMeta: null,
        attachments: [],   // adjuntos pendientes (subidos al borrador)
        draftMsgId: null,
        draftTimer: null,
    };

    // ============== Helpers ==============
    function fmt(d) {
        if (!d) return '';
        const dt = new Date(d.replace(' ', 'T'));
        const now = new Date();
        const diff = (now - dt) / 1000;
        if (diff < 60)        return 'hace un momento';
        if (diff < 3600)      return 'hace ' + Math.floor(diff/60) + ' min';
        if (diff < 86400)     return 'hace ' + Math.floor(diff/3600) + ' h';
        if (diff < 86400*7)   return 'hace ' + Math.floor(diff/86400) + ' d';
        return dt.toLocaleDateString();
    }
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // ============== Sidebar ==============
    document.querySelectorAll('.coms-sidebar .nav-link[data-filter-estado]').forEach(el => {
        el.addEventListener('click', e => {
            document.querySelectorAll('.coms-sidebar .nav-link').forEach(n => n.classList.remove('active'));
            el.classList.add('active');
            state.filter.estado    = el.dataset.filterEstado;
            state.filter.categoria = el.dataset.filterCategoria;
            cargarLista();
        });
    });

    // ============== Lista de conversaciones ==============
    let searchDebounce = null;
    $search.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            state.filter.buscar = $search.value.trim();
            cargarLista();
        }, 250);
    });

    async function cargarLista() {
        $list.innerHTML = '<div class="text-center text-muted small p-4">Cargando...</div>';
        const params = new URLSearchParams({
            estado:    state.filter.estado,
            categoria: state.filter.categoria,
            buscar:    state.filter.buscar,
        });
        try {
            const r = await fetch(`${API_BASE}/listar.php?${params}`, { credentials: 'same-origin' });
            const data = await r.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            state.conversaciones = data.conversaciones;
            renderLista();
        } catch (e) {
            $list.innerHTML = `<div class="alert alert-danger m-3">${escapeHtml(e.message)}</div>`;
        }
    }

    function renderLista() {
        if (state.conversaciones.length === 0) {
            $list.innerHTML = '<div class="text-center text-muted small p-4">Sin conversaciones</div>';
            return;
        }
        $list.innerHTML = state.conversaciones.map(c => {
            const unread = c.no_leidos > 0;
            const cls = ['conv-item'];
            if (unread) cls.push('unread');
            if (c.id === state.currentId) cls.push('active');
            const empresa = c.empresa_nombre ? `<span>${escapeHtml(c.empresa_nombre)}</span>` :
                             c.es_comunicado_global ? '<span><i class="bi bi-megaphone"></i> Global</span>' : '';
            return `
                <div class="${cls.join(' ')}" data-id="${c.id}">
                    <div class="conv-dot"></div>
                    <div class="conv-body">
                        <div class="conv-title">${escapeHtml(c.titulo)}</div>
                        <div class="conv-meta">
                            <span class="conv-cat coms-cat-${c.categoria}">${c.categoria}</span>
                            ${empresa ? '<span class="text-muted">&middot;</span>' + empresa : ''}
                            <span class="text-muted ms-auto">${fmt(c.ultimo_mensaje_at || c.created_at)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $list.querySelectorAll('.conv-item').forEach(el => {
            el.addEventListener('click', () => abrirConversacion(parseInt(el.dataset.id, 10)));
        });

        // counter
        const total = state.conversaciones.reduce((s, c) => s + c.no_leidos, 0);
        document.querySelectorAll('[data-counter]').forEach(el => {
            el.textContent = total > 0 ? total : '';
        });
    }

    // ============== Hilo abierto ==============
    async function abrirConversacion(id) {
        state.currentId = id;
        state.attachments = [];
        state.draftMsgId = null;
        $attachPrev.innerHTML = '';
        $editorText.value = '';
        $threadEmpty.classList.add('d-none');
        $threadCont.classList.remove('d-none');
        $threadCont.classList.add('d-flex');
        document.getElementById('coms-thread').setAttribute('data-mobile-state', 'open');
        document.getElementById('coms-list').setAttribute('data-mobile-state', 'thread');

        $threadMsgs.innerHTML = '<div class="text-center text-muted small p-4">Cargando...</div>';

        try {
            const r = await fetch(`${API_BASE}/conversacion.php?id=${id}`, { credentials: 'same-origin' });
            const data = await r.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            state.currentMeta = data.conversacion;
            renderThread(data);
            cargarLista();   // recarga la lista para reflejar conteos actualizados
        } catch (e) {
            $threadMsgs.innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
        }
    }

    function renderThread(data) {
        $threadTitle.textContent = data.conversacion.titulo;
        const empresa = data.conversacion.empresa_nombre || (data.conversacion.es_comunicado_global ? 'Comunicado global' : '');
        $threadMeta.textContent = `${data.conversacion.categoria}${empresa ? ' &middot; ' + empresa : ''}`;
        $threadMeta.innerHTML = `<span class="conv-cat coms-cat-${data.conversacion.categoria}">${data.conversacion.categoria}</span>${empresa ? ' &middot; ' + escapeHtml(empresa) : ''}`;

        // Toggle archivar/desarchivar segun estado
        const archivada = data.conversacion.estado === 'archivada';
        document.querySelector('[data-act="archivar"]').classList.toggle('d-none', archivada);
        document.querySelector('[data-act="desarchivar"]').classList.toggle('d-none', !archivada);

        // Las empresas leen los comunicados globales pero no pueden responderlos (el servidor lo rechaza).
        const editor = document.getElementById('coms-editor');
        if (editor) editor.classList.toggle('d-none', data.conversacion.es_comunicado_global && shell.dataset.actor === 'empresa');

        const refCard = renderReferenciaCard(data.referencia);
        $threadMsgs.innerHTML = refCard + data.mensajes.map(m => renderMsg(m)).join('');
        $threadMsgs.scrollTop = $threadMsgs.scrollHeight;
    }

    function renderReferenciaCard(ref) {
        if (!ref || ref.tipo !== 'formulario_dinamico' || !ref.detalle) return '';
        const d = ref.detalle;
        const respondido = d.estado_respuesta === 'enviado';
        const limite = d.fecha_limite || null;
        const vencido = !respondido && limite && new Date(limite + 'T23:59:59') < new Date();

        let badgeClass = 'bg-warning text-dark', badgeText = 'Pendiente';
        if (respondido) { badgeClass = 'bg-success'; badgeText = 'Respondido'; }
        else if (vencido) { badgeClass = 'bg-danger'; badgeText = 'Vencido'; }

        const desc = d.descripcion ? `<p class="mb-2 small text-muted">${escapeHtml(d.descripcion)}</p>` : '';
        const limiteHtml = limite
            ? `<div class="small mb-3"><i class="bi bi-calendar-event me-1"></i>Fecha límite: <strong>${escapeHtml(limite)}</strong></div>`
            : '';

        let botonHtml = '';
        if (ACTOR === 'empresa') {
            const btnLabel = respondido ? 'Ver mi respuesta' : 'Completar formulario';
            const btnClass = respondido ? 'btn-outline-success' : 'btn-primary';
            const icon = respondido ? 'bi-check2-circle' : 'bi-pencil-square';
            botonHtml = `<a href="${escapeHtml(d.url_completar)}" class="btn ${btnClass}">
                <i class="bi ${icon} me-1"></i>${btnLabel}
            </a>`;
        } else {
            botonHtml = `<a href="../ministerio/formulario-gestion.php?id=${ref.id}&tab=respuestas" class="btn btn-outline-primary">
                <i class="bi bi-clipboard-data me-1"></i>Ver respuestas
            </a>`;
        }

        return `
            <div class="card border-primary mb-3 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-text me-1"></i>${escapeHtml(d.titulo)}
                        </h5>
                        <span class="badge ${badgeClass}">${badgeText}</span>
                    </div>
                    ${desc}
                    ${limiteHtml}
                    ${botonHtml}
                </div>
            </div>
        `;
    }

    function linkifyMensaje(textoEscapado) {
        // Reemplaza URLs http(s) por un botón. Trabaja sobre texto ya escapado.
        const urlRe = /(https?:\/\/[^\s<>"']+)/g;
        return textoEscapado.replace(urlRe, function (url) {
            const limpio = url.replace(/[).,;:!?]+$/, '');
            const trail = url.slice(limpio.length);
            return `<a href="${limpio}" target="_blank" rel="noopener" class="btn btn-sm btn-primary my-1 me-1">
                <i class="bi bi-box-arrow-up-right me-1"></i>Abrir enlace
            </a>${trail}`;
        });
    }

    function renderMsg(m) {
        const out = m.remitente_tipo === ACTOR;
        const adj = (m.adjuntos || []).map(a => `
            <a class="adj" href="${escapeHtml(a.url)}" target="_blank" rel="noopener">
                <i class="bi bi-paperclip"></i> ${escapeHtml(a.nombre)} (${(a.tamano/1024).toFixed(0)} KB)
            </a>
        `).join(' ');
        const contenidoHtml = linkifyMensaje(escapeHtml(m.contenido)).replace(/\n/g, '<br>');
        return `
            <div class="coms-msg ${out ? 'out' : 'in'}">
                <div>
                    <div class="bubble">${contenidoHtml}${adj ? '<div class="mt-2">' + adj + '</div>' : ''}</div>
                    <div class="meta">
                        ${out ? 'Enviado' : (m.remitente_email || m.remitente_tipo)} &middot; ${fmt(m.created_at)}
                    </div>
                </div>
            </div>
        `;
    }

    // ============== Editor ==============
    $editorText.addEventListener('input', programarBorrador);
    function programarBorrador() {
        if (!state.currentId) return;
        clearTimeout(state.draftTimer);
        $draftStatus.classList.add('d-none');
        state.draftTimer = setTimeout(guardarBorrador, 1500);
    }
    async function guardarBorrador() {
        if (!state.currentId) return;
        try {
            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            fd.append('conversacion_id', state.currentId);
            fd.append('contenido', $editorText.value);
            const r = await fetch(`${API_BASE}/borrador.php`, { method:'POST', body: fd, credentials: 'same-origin' });
            const data = await r.json();
            if (data.ok) {
                state.draftMsgId = data.mensaje_id;
                $draftStatus.classList.remove('d-none');
            }
        } catch (e) { /* silencioso */ }
    }

    $sendBtn.addEventListener('click', enviarMensaje);
    async function enviarMensaje() {
        if (!state.currentId) return;
        const contenido = $editorText.value.trim();
        if (!contenido) { $editorText.focus(); return; }
        $sendBtn.disabled = true;
        try {
            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            fd.append('conversacion_id', state.currentId);
            fd.append('contenido', contenido);
            const r = await fetch(`${API_BASE}/enviar.php`, { method:'POST', body: fd, credentials: 'same-origin' });
            const data = await r.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            $editorText.value = '';
            $attachPrev.innerHTML = '';
            state.attachments = [];
            state.draftMsgId = null;
            $draftStatus.classList.add('d-none');
            await abrirConversacion(state.currentId);
        } catch (e) {
            alert(e.message);
        } finally {
            $sendBtn.disabled = false;
        }
    }

    // ============== Adjuntos ==============
    $attachBtn.addEventListener('click', () => $fileInput.click());
    $fileInput.addEventListener('change', subirAdjuntos);
    async function subirAdjuntos() {
        if (!state.currentId) return;
        if (!state.draftMsgId) {
            // Forzar creacion de borrador para tener mensaje_id donde anclar adjuntos
            await guardarBorrador();
            if (!state.draftMsgId) {
                alert('No se pudo crear borrador para adjuntar.');
                return;
            }
        }
        const fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('mensaje_id', state.draftMsgId);
        for (const f of $fileInput.files) fd.append('archivos[]', f);
        try {
            const r = await fetch(`${API_BASE}/adjuntar.php`, { method:'POST', body: fd, credentials: 'same-origin' });
            const data = await r.json();
            if (!data.ok) throw new Error(data.error || 'Error al adjuntar');
            data.adjuntos.forEach(a => {
                state.attachments.push(a);
                const pill = document.createElement('span');
                pill.className = 'adj-pill';
                pill.innerHTML = `<i class="bi bi-paperclip"></i>${escapeHtml(a.nombre)} (${(a.tamano/1024).toFixed(0)}KB)`;
                $attachPrev.appendChild(pill);
            });
        } catch (e) {
            alert(e.message);
        } finally {
            $fileInput.value = '';
        }
    }

    // ============== Acciones de hilo ==============
    document.querySelectorAll('[data-act]').forEach(el => {
        el.addEventListener('click', async () => {
            if (!state.currentId) return;
            const accion = el.dataset.act;
            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            fd.append('conversacion_id', state.currentId);
            fd.append('accion', accion);
            try {
                const r = await fetch(`${API_BASE}/marcar.php`, { method:'POST', body: fd, credentials: 'same-origin' });
                const data = await r.json();
                if (!data.ok) throw new Error(data.error || 'Error');
                if (accion === 'archivar' || accion === 'desarchivar') {
                    state.currentId = null;
                    $threadEmpty.classList.remove('d-none');
                    $threadCont.classList.add('d-none');
                    $threadCont.classList.remove('d-flex');
                }
                cargarLista();
            } catch (e) { alert(e.message); }
        });
    });

    // ============== Volver a la lista (mobile) ==============
    document.getElementById('coms-back-list').addEventListener('click', () => {
        document.getElementById('coms-thread').setAttribute('data-mobile-state', '');
        document.getElementById('coms-list').setAttribute('data-mobile-state', '');
    });

    // ============== Nueva conversacion ==============
    const modalNueva = new bootstrap.Modal(document.getElementById('coms-modal-nueva'));
    document.getElementById('btn-nueva-conv').addEventListener('click', () => modalNueva.show());
    document.getElementById('coms-new-enviar').addEventListener('click', async () => {
        const titulo    = document.getElementById('coms-new-titulo').value.trim();
        const categoria = document.getElementById('coms-new-categoria').value;
        const contenido = document.getElementById('coms-new-contenido').value.trim();
        const destEl    = document.getElementById('coms-new-destinatario');
        if (!titulo || !contenido) { alert('Complete asunto y mensaje.'); return; }
        const fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('titulo', titulo);
        fd.append('categoria', categoria);
        fd.append('contenido', contenido);
        if (destEl) fd.append('destinatario', destEl.value);
        try {
            const r = await fetch(`${API_BASE}/enviar.php`, { method:'POST', body: fd, credentials: 'same-origin' });
            const data = await r.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            modalNueva.hide();
            document.getElementById('coms-new-titulo').value = '';
            document.getElementById('coms-new-contenido').value = '';
            await cargarLista();
            abrirConversacion(data.conversacion_id);
        } catch (e) { alert(e.message); }
    });

    // ============== Plantillas (solo ministerio) ==============
    const $plantillaBtn = document.getElementById('coms-plantilla-btn');
    if ($plantillaBtn) {
        const modalPlantilla = new bootstrap.Modal(document.getElementById('coms-modal-plantilla'));
        const $plantillaList = document.getElementById('coms-plantilla-list');
        const $plantillaSearch = document.getElementById('coms-plantilla-search');
        let plantillasData = [];

        async function cargarPlantillas() {
            $plantillaList.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-hourglass-split"></i> Cargando...</div>';
            try {
                const r = await fetch(`${API_BASE}/plantillas.php`, { credentials: 'same-origin' });
                const data = await r.json();
                plantillasData = data.ok ? data.plantillas : [];
            } catch (e) { plantillasData = []; }
            renderPlantillas('');
        }

        function renderPlantillas(buscar) {
            const filtradas = buscar
                ? plantillasData.filter(p => p.titulo.toLowerCase().includes(buscar) || p.contenido.toLowerCase().includes(buscar))
                : plantillasData;
            if (!filtradas.length) {
                $plantillaList.innerHTML = '<div class="text-center text-muted py-4">No hay plantillas disponibles.</div>';
                return;
            }
            $plantillaList.innerHTML = filtradas.map(p => {
                const preview = p.contenido.replace(/\\n/g, ' ').substring(0, 90);
                return `<div class="px-3 py-2 border-bottom coms-plantilla-item" role="button" data-idx="${p.id}" style="cursor:pointer;">
                    <div class="fw-semibold small">${esc(p.titulo)}</div>
                    <div class="text-muted" style="font-size:.75rem;">${esc(preview)}…</div>
                    <span class="badge bg-secondary" style="font-size:.65rem;">${esc(p.categoria)}</span>
                </div>`;
            }).join('');
            $plantillaList.querySelectorAll('.coms-plantilla-item').forEach(el => {
                el.addEventListener('click', () => {
                    const p = plantillasData.find(x => String(x.id) === el.dataset.idx);
                    if (p) {
                        const texto = p.contenido.replace(/\\n/g, '\n');
                        $editorText.value = $editorText.value ? $editorText.value + '\n\n' + texto : texto;
                        $editorText.focus();
                        modalPlantilla.hide();
                    }
                });
            });
        }

        function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

        $plantillaBtn.addEventListener('click', () => {
            cargarPlantillas();
            $plantillaSearch.value = '';
            modalPlantilla.show();
        });
        $plantillaSearch.addEventListener('input', () => renderPlantillas($plantillaSearch.value.toLowerCase()));
    }

    // ============== Drag & drop categorias (SortableJS) ==============
    function initSortable() {
        var container = document.getElementById('coms-cat-sortable');
        if (!container || typeof Sortable === 'undefined') return;
        Sortable.create(container, {
            animation: 150,
            ghostClass: 'bg-light',
            handle: '.nav-link',
            onEnd: function () {
                // Guardar orden en localStorage
                var orden = [];
                container.querySelectorAll('.nav-link[data-filter-categoria]').forEach(function (el) {
                    orden.push(el.dataset.filterCategoria);
                });
                try { localStorage.setItem('coms_cat_orden', JSON.stringify(orden)); } catch (e) {}
            }
        });
    }
    // Restaurar orden guardado
    (function () {
        try {
            var saved = JSON.parse(localStorage.getItem('coms_cat_orden') || '[]');
            if (!saved.length) return;
            var container = document.getElementById('coms-cat-sortable');
            if (!container) return;
            var items = {};
            container.querySelectorAll('.nav-link[data-filter-categoria]').forEach(function (el) {
                items[el.dataset.filterCategoria] = el;
            });
            saved.forEach(function (cat) {
                if (items[cat]) container.appendChild(items[cat]);
            });
        } catch (e) {}
    })();

    // ============== Init ==============
    cargarLista();
    initSortable();
});
