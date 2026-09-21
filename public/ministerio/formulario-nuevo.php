<?php
/**
 * Crear Formulario Dinámico — Ministerio
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$page_title = 'Nuevo Formulario';
$db  = getDB();
$error = '';

$tipos = [
    'texto'           => 'Texto corto',
    'textarea'        => 'Párrafo',
    'numero'          => 'Número',
    'fecha'           => 'Fecha',
    'select'          => 'Lista desplegable',
    'radio'           => 'Opción única',
    'checkbox'        => 'Opción múltiple',
    'archivo_adjunto' => 'Archivo adjunto + respuesta',
    'archivo'         => 'Carga de archivo',
    'direccion'       => 'Ubicación en mapa',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Token de seguridad inválido. Recargue la página.';
    } else {
        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado      = in_array($_POST['estado'] ?? '', ['borrador','publicado','archivado'], true) ? $_POST['estado'] : 'borrador';

        if ($titulo === '') {
            $error = 'Debe ingresar un título.';
        } else {
            try {
                $db->beginTransaction();
                $stmt = $db->prepare("INSERT INTO formularios_dinamicos (titulo, descripcion, estado, creado_por) VALUES (?, ?, ?, ?)");
                $stmt->execute([$titulo, $descripcion, $estado, $_SESSION['user_id']]);
                $formulario_id = (int)$db->lastInsertId();

                $tipos_validos = array_keys($tipos);
                $tipos_post  = $_POST['pregunta_tipo'] ?? [];
                $labels_post = $_POST['pregunta_label'] ?? [];
                $req_post    = $_POST['pregunta_requerido'] ?? [];
                $ayuda_post  = $_POST['pregunta_ayuda'] ?? [];
                $opc_post    = $_POST['pregunta_opciones'] ?? [];
                $min_post    = $_POST['pregunta_min'] ?? [];
                $max_post    = $_POST['pregunta_max'] ?? [];

                foreach ($tipos_post as $i => $tipo) {
                    $tipo  = trim($tipo);
                    $label = trim($labels_post[$i] ?? '');
                    if ($label === '' || !in_array($tipo, $tipos_validos, true)) continue;

                    $requerido = !empty($req_post[$i]) ? 1 : 0;
                    $ayuda     = trim($ayuda_post[$i] ?? '');
                    $opciones  = null;
                    $min_valor = ($tipo === 'numero' && ($min_post[$i] ?? '') !== '') ? (float)$min_post[$i] : null;
                    $max_valor = ($tipo === 'numero' && ($max_post[$i] ?? '') !== '') ? (float)$max_post[$i] : null;

                    if (in_array($tipo, ['select','radio','checkbox'], true)) {
                        $raw   = str_replace("\r\n", "\n", $opc_post[$i] ?? '');
                        $items = array_values(array_filter(array_map('trim', explode("\n", $raw))));
                        // Límite 10 opciones, 50 chars c/u
                        $items = array_slice($items, 0, 10);
                        $items = array_map(fn($it) => mb_substr($it, 0, 50), $items);
                        if (empty($items)) throw new Exception("Agregue al menos una opción para la pregunta \"$label\".");
                        $opciones = json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
                    } elseif ($tipo === 'archivo_adjunto') {
                        // Subir archivo adjunto del ministerio
                        $adj_data = [];
                        $adj_key  = 'pregunta_adj_file';
                        if (!empty($_FILES[$adj_key]['name'][$i])) {
                            $file_tmp  = $_FILES[$adj_key]['tmp_name'][$i];
                            $allowed   = ['image/jpeg','image/png','image/webp','image/gif','application/pdf'];
                            // MIME real del contenido (el 'type' del cliente es falsificable) y extensión derivada de él
                            $file_type = is_uploaded_file($file_tmp) ? (new finfo(FILEINFO_MIME_TYPE))->file($file_tmp) : '';
                            $ext       = safe_extension_for_mime((string) $file_type);
                            if (in_array($file_type, $allowed, true) && $ext !== null) {
                                $archivo_subido = [
                                    'name'     => $_FILES['pregunta_adj_file']['name'][$i],
                                    'tmp_name' => $file_tmp,
                                    'error'    => $_FILES['pregunta_adj_file']['error'][$i],
                                    'size'     => $_FILES['pregunta_adj_file']['size'][$i],
                                ];
                                // Cloudinary si esta configurado; si no, disco local. Se guarda la URL o el nombre de archivo.
                                $guardado = store_upload($archivo_subido, 'formularios', $allowed, $file_type);
                                if ($guardado['success']) {
                                    $adj_data['archivo'] = $guardado['filename'];
                                }
                            }
                        }
                        $opciones = !empty($adj_data) ? json_encode($adj_data, JSON_UNESCAPED_UNICODE) : null;
                    }

                    $stmt = $db->prepare("
                        INSERT INTO formulario_preguntas
                        (formulario_id, tipo, etiqueta, ayuda, requerido, opciones, min_valor, max_valor, orden)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$formulario_id, $tipo, $label, $ayuda, $requerido, $opciones, $min_valor, $max_valor, $i + 1]);
                }

                $db->commit();
                log_activity('formulario_dinamico_creado', 'formularios_dinamicos', $formulario_id);
                set_flash('success', 'Formulario creado correctamente.');
                redirect('formularios-dinamicos.php');
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = $e->getMessage() ?: 'Error al crear el formulario.';
            }
        }
    }
}

$ministerio_nav = 'formularios_dinamicos';
require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0 fw-semibold">Nuevo formulario</h2>
    <a href="formularios-dinamicos.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="formPrincipal">
    <?= csrf_field() ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control" value="<?= e($_POST['titulo'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select name="estado" class="form-select">
                        <?php foreach (['borrador'=>'Borrador','publicado'=>'Publicado'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= ($_POST['estado'] ?? 'borrador') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción / instrucciones</label>
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Texto introductorio que verán las empresas"><?= e($_POST['descripcion'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Preguntas</h5>
        <button type="button" id="btnAddPregunta" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Agregar pregunta
        </button>
    </div>

    <div id="preguntasContainer"></div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-success"><i class="bi bi-save me-2"></i>Guardar formulario</button>
        <a href="formularios-dinamicos.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/ministerio_builder_template.php'; ?>

<?php
$tipos_json = json_encode($tipos, JSON_UNESCAPED_UNICODE);
ob_start();
?>
<script>
(function() {
    var TIPOS = <?= $tipos_json ?>;

    var TIPO_INFO = {
        "texto":           "Máximo <strong>50 caracteres</strong>",
        "textarea":        "Máximo <strong>200 caracteres</strong>",
        "numero":          "Solo números · Máx <strong>25 dígitos</strong>",
        "fecha":           "La empresa elige desde un <strong>almanaque</strong>",
        "select":          "Lista desplegable · Hasta <strong>10 opciones</strong> de 50 car. c/u",
        "radio":           "Opción única · Hasta <strong>10 opciones</strong> de 50 car. c/u",
        "checkbox":        "Opción múltiple · Hasta <strong>10 opciones</strong> de 50 car. c/u",
        "archivo_adjunto": "Ministerio adjunta imagen/PDF · Empresa responde con <strong>texto</strong>",
        "archivo":         "Empresa sube: imágenes, PDF, Excel, Word, videos",
        "direccion":       "Empresa marca ubicación en un <strong>mapa</strong>"
    };

    var container = document.getElementById('preguntasContainer');
    var btnAdd    = document.getElementById('btnAddPregunta');
    var form      = document.getElementById('formPrincipal');
    var counter   = 0;

    function crearPreguntaItem(datos) {
        var idx   = counter++;
        var tpl   = document.getElementById('tplPregunta');
        var clone = tpl.content.firstElementChild.cloneNode(true);

        var sel = clone.querySelector('.pregunta-tipo');
        Object.keys(TIPOS).forEach(function(val) {
            var opt = document.createElement('option');
            opt.value = val; opt.textContent = TIPOS[val];
            if (datos && datos.tipo === val) opt.selected = true;
            sel.appendChild(opt);
        });

        if (datos) {
            clone.querySelector('.pregunta-label').value = datos.label || '';
            clone.querySelector('.pregunta-ayuda').value = datos.ayuda || '';
            clone.querySelector('.pregunta-req').checked = !!datos.req;
            clone.querySelector('.pregunta-min').value   = datos.min_valor || '';
            clone.querySelector('.pregunta-max').value   = datos.max_valor || '';
            if (datos.opciones && Array.isArray(datos.opciones)) {
                var lista = clone.querySelector('.opciones-lista');
                datos.opciones.forEach(function(v) { agregarOpcionALista(lista, v); });
            }
        } else {
            agregarOpcionALista(clone.querySelector('.opciones-lista'));
        }

        bindItem(clone, idx);
        return clone;
    }

    function agregarOpcionALista(lista, valor) {
        if (!lista) return;
        var count = lista.querySelectorAll('.opcion-row').length;
        if (count >= 10) { alert('Máximo 10 opciones por pregunta.'); return; }
        var n = count + 1;
        var row = document.createElement('div');
        row.className = 'input-group mb-1 opcion-row';
        var safeVal = (valor || '').toString().replace(/"/g, '&quot;');
        row.innerHTML =
            '<span class="input-group-text text-muted" style="width:34px;font-size:.82rem">' + n + '</span>' +
            '<input type="text" class="form-control opcion-input" maxlength="50" placeholder="Opción ' + n + '" value="' + safeVal + '">' +
            '<button type="button" class="btn btn-outline-danger quitar-opcion">&times;</button>';
        row.querySelector('.quitar-opcion').addEventListener('click', function() {
            row.remove(); reindexar(lista);
        });
        lista.appendChild(row);
    }

    function reindexar(lista) {
        var rows = lista.querySelectorAll('.opcion-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].querySelector('.input-group-text').textContent = i + 1;
            rows[i].querySelector('input').placeholder = 'Opción ' + (i + 1);
        }
    }

    function toggleCampos(item) {
        var tipo = item.querySelector('.pregunta-tipo').value;
        var esOpc = (tipo === 'select' || tipo === 'radio' || tipo === 'checkbox');
        item.querySelector('.options-container').classList.toggle('d-none', !esOpc);
        item.querySelector('.adj-container').classList.toggle('d-none', tipo !== 'archivo_adjunto');
        item.querySelector('.numero-container').classList.toggle('d-none', tipo !== 'numero');
        item.querySelector('.tipo-info').innerHTML = TIPO_INFO[tipo] || '';
    }

    function bindItem(item, idx) {
        item.querySelectorAll('[data-name]').forEach(function(el) {
            el.name = el.getAttribute('data-name') + '[' + idx + ']';
        });
        item.querySelector('.pregunta-tipo').addEventListener('change', function() { toggleCampos(item); });
        toggleCampos(item);
        item.querySelector('.btn-remove').addEventListener('click', function() { item.remove(); renombrar(); });
        item.querySelector('.agregar-opcion').addEventListener('click', function() {
            agregarOpcionALista(item.querySelector('.opciones-lista'));
        });
    }

    function renombrar() {
        var idx = 0;
        container.querySelectorAll('.pregunta-item').forEach(function(item) {
            item.querySelectorAll('[data-name]').forEach(function(el) {
                el.name = el.getAttribute('data-name') + '[' + idx + ']';
            });
            idx++;
        });
    }

    form.addEventListener('submit', function() {
        container.querySelectorAll('.pregunta-item').forEach(function(item) {
            var lista  = item.querySelector('.opciones-lista');
            var hidden = item.querySelector('textarea.opciones-hidden');
            if (!lista || !hidden) return;
            var vals = [];
            lista.querySelectorAll('.opcion-input').forEach(function(inp) {
                var v = inp.value.trim();
                if (v) vals.push(v);
            });
            hidden.value = vals.join('\n');
        });
    });

    btnAdd.addEventListener('click', function() {
        container.appendChild(crearPreguntaItem(null));
        renombrar();
    });

    btnAdd.click();
})();
</script>
<?php
$extra_scripts = ob_get_clean();
require_once BASEPATH . '/includes/ministerio_layout_footer.php';
?>
