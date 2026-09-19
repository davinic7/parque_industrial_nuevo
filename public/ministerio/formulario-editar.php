<?php
/**
 * Editar Formulario Dinámico — Ministerio
 */
require_once __DIR__ . '/../../config/config.php';

if (!$auth->requireRole(['ministerio', 'admin'], PUBLIC_URL . '/login.php')) exit;

$db      = getDB();
$form_id = (int)($_GET['id'] ?? 0);

if ($form_id <= 0) { set_flash('error', 'Formulario no especificado.'); redirect('formularios-dinamicos.php'); }

$stmt = $db->prepare("SELECT * FROM formularios_dinamicos WHERE id = ?");
$stmt->execute([$form_id]);
$formulario = $stmt->fetch();
if (!$formulario) { set_flash('error', 'Formulario no encontrado.'); redirect('formularios-dinamicos.php'); }

$page_title = 'Editar: ' . $formulario['titulo'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado      = in_array($_POST['estado'] ?? '', ['borrador','publicado','archivado'], true)
                   ? $_POST['estado'] : $formulario['estado'];

    if ($titulo === '') {
        $error = 'Debe ingresar un título.';
    } else {
        try {
            $db->beginTransaction();
            $db->prepare("UPDATE formularios_dinamicos SET titulo=?, descripcion=?, estado=? WHERE id=?")
               ->execute([$titulo, $descripcion, $estado, $form_id]);
            $db->prepare("DELETE FROM formulario_preguntas WHERE formulario_id=?")->execute([$form_id]);

            $tipos_validos = array_keys($tipos);
            $tipos_post  = $_POST['pregunta_tipo'] ?? [];
            $labels_post = $_POST['pregunta_label'] ?? [];
            $req_post    = $_POST['pregunta_requerido'] ?? [];
            $ayuda_post  = $_POST['pregunta_ayuda'] ?? [];
            $opc_post    = $_POST['pregunta_opciones'] ?? [];
            $min_post    = $_POST['pregunta_min'] ?? [];
            $max_post    = $_POST['pregunta_max'] ?? [];
            $adj_prev    = $_POST['pregunta_adj_prev'] ?? []; // archivos existentes

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
                    $items = array_slice($items, 0, 10);
                    $items = array_map(fn($it) => mb_substr($it, 0, 50), $items);
                    if (empty($items)) throw new Exception("Agregue al menos una opción para \"$label\".");
                    $opciones = json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
                } elseif ($tipo === 'archivo_adjunto') {
                    $adj_data = [];
                    // Conservar archivo previo si no se subió uno nuevo
                    if (!empty($adj_prev[$i])) {
                        $adj_data['archivo'] = $adj_prev[$i];
                    }
                    if (!empty($_FILES['pregunta_adj_file']['name'][$i])) {
                        $file_tmp  = $_FILES['pregunta_adj_file']['tmp_name'][$i];
                        $allowed   = ['image/jpeg','image/png','image/webp','image/gif','application/pdf'];
                        // MIME real del contenido (el 'type' del cliente es falsificable) y extensión derivada de él
                        $file_type = is_uploaded_file($file_tmp) ? (new finfo(FILEINFO_MIME_TYPE))->file($file_tmp) : '';
                        $ext       = safe_extension_for_mime((string) $file_type);
                        if (in_array($file_type, $allowed, true) && $ext !== null) {
                            $new_name = 'adj_' . uniqid() . '.' . $ext;
                            $dest     = UPLOADS_PATH . '/formularios/' . $new_name;
                            if (!is_dir(UPLOADS_PATH . '/formularios')) mkdir(UPLOADS_PATH . '/formularios', 0775, true);
                            if (move_uploaded_file($file_tmp, $dest)) {
                                $adj_data['archivo'] = $new_name;
                            }
                        }
                    }
                    $opciones = !empty($adj_data) ? json_encode($adj_data, JSON_UNESCAPED_UNICODE) : null;
                }

                $db->prepare("
                    INSERT INTO formulario_preguntas
                    (formulario_id, tipo, etiqueta, ayuda, requerido, opciones, min_valor, max_valor, orden)
                    VALUES (?,?,?,?,?,?,?,?,?)
                ")->execute([$form_id, $tipo, $label, $ayuda, $requerido, $opciones, $min_valor, $max_valor, $i + 1]);
            }

            $db->commit();
            log_activity('formulario_dinamico_editado', 'formularios_dinamicos', $form_id);
            set_flash('success', 'Formulario actualizado correctamente.');
            redirect('formulario-gestion.php?id=' . $form_id);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = $e->getMessage() ?: 'Error al actualizar el formulario.';
        }
    }
}

// Cargar preguntas actuales
$stmt = $db->prepare("SELECT * FROM formulario_preguntas WHERE formulario_id = ? ORDER BY orden, id");
$stmt->execute([$form_id]);
$preguntas_actuales = $stmt->fetchAll();

// Preparar datos para JS
$preguntas_js = [];
foreach ($preguntas_actuales as $p) {
    $opc_items = [];
    $adj_archivo = null;
    if (!empty($p['opciones'])) {
        $dec = json_decode($p['opciones'], true) ?: [];
        if (isset($dec['items'])) $opc_items = $dec['items'];
        if (isset($dec['archivo'])) $adj_archivo = $dec['archivo'];
    }
    $preguntas_js[] = [
        'tipo'      => $p['tipo'],
        'label'     => $p['etiqueta'],
        'ayuda'     => $p['ayuda'] ?? '',
        'req'       => (bool)$p['requerido'],
        'min_valor' => $p['min_valor'],
        'max_valor' => $p['max_valor'],
        'opciones'  => $opc_items,
        'adj_archivo' => $adj_archivo,
    ];
}
$preguntas_js_encoded = json_encode($preguntas_js, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

$ministerio_nav = 'formularios_dinamicos';
require_once BASEPATH . '/includes/ministerio_layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0 fw-semibold">Editar formulario</h2>
        <small class="text-muted"><?= e($formulario['titulo']) ?></small>
    </div>
    <a href="formulario-gestion.php?id=<?= $form_id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a gestión
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="formEditar">
    <?= csrf_field() ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control" value="<?= e($formulario['titulo']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select name="estado" class="form-select">
                        <?php foreach (['borrador'=>'Borrador','publicado'=>'Publicado','archivado'=>'Archivado'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $formulario['estado'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción / instrucciones</label>
                    <textarea name="descripcion" class="form-control" rows="2"><?= e($formulario['descripcion'] ?? '') ?></textarea>
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
        <button type="submit" class="btn btn-success"><i class="bi bi-save me-2"></i>Guardar cambios</button>
        <a href="formulario-gestion.php?id=<?= $form_id ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php include BASEPATH . '/includes/ministerio_builder_template.php'; ?>

<?php
$tipos_json           = json_encode($tipos, JSON_UNESCAPED_UNICODE);
$uploads_url_js       = json_encode(UPLOADS_URL . '/formularios/', JSON_UNESCAPED_UNICODE);
ob_start();
?>
<script>
(function() {
    var TIPOS       = <?= $tipos_json ?>;
    var UPLOADS_URL = <?= $uploads_url_js ?>;
    var existentes  = <?= $preguntas_js_encoded ?>;

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
    var form      = document.getElementById('formEditar');
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
            clone.querySelector('.pregunta-label').value = datos.label  || '';
            clone.querySelector('.pregunta-ayuda').value = datos.ayuda  || '';
            clone.querySelector('.pregunta-req').checked = !!datos.req;
            clone.querySelector('.pregunta-min').value   = datos.min_valor != null ? datos.min_valor : '';
            clone.querySelector('.pregunta-max').value   = datos.max_valor != null ? datos.max_valor : '';

            if (datos.opciones && datos.opciones.length) {
                var lista = clone.querySelector('.opciones-lista');
                datos.opciones.forEach(function(v) { agregarOpcionALista(lista, v); });
            }

            if (datos.adj_archivo) {
                var adjCont  = clone.querySelector('.adj-container');
                var prevInput = document.createElement('input');
                prevInput.type = 'hidden';
                prevInput.setAttribute('data-name', 'pregunta_adj_prev');
                prevInput.value = datos.adj_archivo;
                adjCont.appendChild(prevInput);

                var ext   = datos.adj_archivo.split('.').pop().toLowerCase();
                var isImg = ['jpg','jpeg','png','webp','gif'].indexOf(ext) >= 0;
                var prev  = document.createElement('div');
                prev.className = 'mt-2';
                if (isImg) {
                    prev.innerHTML = '<img src="' + UPLOADS_URL + datos.adj_archivo + '" class="img-thumbnail" style="max-height:120px"><div class="form-text">Archivo actual — subí uno nuevo para reemplazarlo</div>';
                } else {
                    prev.innerHTML = '<a href="' + UPLOADS_URL + datos.adj_archivo + '" target="_blank" class="btn btn-sm btn-outline-secondary">Ver archivo actual</a><div class="form-text">Subí uno nuevo para reemplazarlo</div>';
                }
                adjCont.appendChild(prev);
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
        if (count >= 10) { alert('Máximo 10 opciones.'); return; }
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
        lista.querySelectorAll('.opcion-row').forEach(function(row, i) {
            row.querySelector('.input-group-text').textContent = i + 1;
            row.querySelector('input').placeholder = 'Opción ' + (i + 1);
        });
    }

    function toggleCampos(item) {
        var tipo = item.querySelector('.pregunta-tipo').value;
        item.querySelector('.options-container').classList.toggle('d-none', ['select','radio','checkbox'].indexOf(tipo) < 0);
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
            var vals = Array.from(lista.querySelectorAll('.opcion-input'))
                .map(function(i) { return i.value.trim(); }).filter(function(v) { return v !== ''; });
            hidden.value = vals.join('\n');
        });
    });

    btnAdd.addEventListener('click', function() {
        container.appendChild(crearPreguntaItem(null));
        renombrar();
    });

    existentes.forEach(function(datos) { container.appendChild(crearPreguntaItem(datos)); });
    renombrar();
})();
</script>
<?php
$extra_scripts = ob_get_clean();
require_once BASEPATH . '/includes/ministerio_layout_footer.php';
?>
