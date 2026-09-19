/**
 * Gestión del sitio público (public/ministerio/sitio-publico.php): editor Quill y servicios (pestaña "El parque")
 * y normalización de URLs de redes (pestaña "Contacto").
 * Requiere el objeto global SITIO_CFG que define sitio-publico.php: { tab, servicios }.
 */
(function () {
    'use strict';

    var cfg = window.SITIO_CFG || {};

    /* ── Quill – solo en tab el_parque ──────────────────────────────────────── */
    function initElParque() {
        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'clean']
                ]
            },
            placeholder: 'Escribí el texto descriptivo del parque aquí...'
        });

        // Cargar contenido existente
        var rawSource = document.getElementById('quill-raw-source').textContent;
        if (rawSource && rawSource.trim() !== '') {
            // Detectar si es HTML o texto plano
            if (/<[a-z][\s\S]*>/i.test(rawSource)) {
                quill.clipboard.dangerouslyPasteHTML(rawSource);
            } else {
                quill.setText(rawSource);
            }
        }

        // Copiar HTML al hidden field antes de enviar
        document.getElementById('form-el-parque').addEventListener('submit', function () {
            document.getElementById('nosotros_texto_hidden').value = quill.root.innerHTML;
        });

        /* ── Editor de servicios ─────────────────────────────────────────────────── */
        var servicios = cfg.servicios || [];

        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderServicios() {
            var container = document.getElementById('servicios-lista');
            if (!container) return;
            if (servicios.length === 0) {
                container.innerHTML = '<p class="text-muted small">No hay servicios cargados. Agregá el primero con el formulario de abajo.</p>';
            } else {
                container.innerHTML = servicios.map(function (s, i) {
                    return '<div class="servicio-item border rounded p-3 mb-2 d-flex align-items-center gap-3">'
                        + '<i class="bi ' + esc(s.icon) + ' fs-4 text-primary" style="min-width:2rem;text-align:center"></i>'
                        + '<div class="flex-grow-1">'
                        + '<div class="fw-semibold">' + esc(s.titulo) + '</div>'
                        + '<div class="small text-muted">' + esc(s.desc) + '</div>'
                        + '</div>'
                        + '<div class="d-flex gap-1 flex-shrink-0">'
                        + '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moverServicio(' + i + ',-1)" ' + (i === 0 ? 'disabled' : '') + ' title="Subir"><i class="bi bi-arrow-up"></i></button>'
                        + '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moverServicio(' + i + ',1)" ' + (i === servicios.length - 1 ? 'disabled' : '') + ' title="Bajar"><i class="bi bi-arrow-down"></i></button>'
                        + '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editarServicio(' + i + ')" title="Editar"><i class="bi bi-pencil"></i></button>'
                        + '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="eliminarServicio(' + i + ')" title="Eliminar"><i class="bi bi-trash3"></i></button>'
                        + '</div></div>';
                }).join('');
            }
            document.getElementById('nosotros_servicios_hidden').value = JSON.stringify(servicios);
        }

        window.agregarServicio = function () {
            var icon   = document.getElementById('new-svc-icon').value.trim();
            var titulo = document.getElementById('new-svc-titulo').value.trim();
            var desc   = document.getElementById('new-svc-desc').value.trim();
            if (!titulo) {
                document.getElementById('new-svc-titulo').focus();
                return;
            }
            servicios.push({ icon: icon || 'bi-gear', titulo: titulo, desc: desc });
            document.getElementById('new-svc-titulo').value = '';
            document.getElementById('new-svc-desc').value = '';
            renderServicios();
        };

        window.editarServicio = function (i) {
            var s = servicios[i];
            document.getElementById('new-svc-icon').value   = s.icon;
            document.getElementById('new-svc-titulo').value = s.titulo;
            document.getElementById('new-svc-desc').value   = s.desc;
            // Actualizar preview del ícono
            actualizarIconPreview(s.icon);
            servicios.splice(i, 1);
            renderServicios();
            document.getElementById('new-svc-titulo').focus();
        };

        window.eliminarServicio = function (i) {
            if (!confirm('¿Eliminar este servicio?')) return;
            servicios.splice(i, 1);
            renderServicios();
        };

        window.moverServicio = function (i, dir) {
            var j = i + dir;
            if (j < 0 || j >= servicios.length) return;
            var tmp = servicios[i]; servicios[i] = servicios[j]; servicios[j] = tmp;
            renderServicios();
        };

        function actualizarIconPreview(iconClass) {
            var el = document.getElementById('icon-preview-live');
            if (!el) return;
            el.className = 'icon-preview-live bi ' + iconClass;
        }

        var iconSel = document.getElementById('new-svc-icon');
        if (iconSel) {
            iconSel.addEventListener('change', function () { actualizarIconPreview(this.value); });
            // Inicializar preview
            actualizarIconPreview(iconSel.value);
        }

        // Enter en el campo desc agrega el servicio
        var descInput = document.getElementById('new-svc-desc');
        if (descInput) {
            descInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); agregarServicio(); }
            });
        }

        renderServicios();
    }

    /* ── Validar redes sociales – advertir si no es URL ─────────────────────── */
    function initContacto() {
        var urlInputs = document.querySelectorAll('input[type="url"]');
        urlInputs.forEach(function (inp) {
            inp.addEventListener('blur', function () {
                var val = this.value.trim();
                if (val && !val.startsWith('http')) {
                    this.value = 'https://' + val;
                }
            });
        });
    }

    if (cfg.tab === 'el_parque') initElParque();
    if (cfg.tab === 'contacto') initContacto();

}());
