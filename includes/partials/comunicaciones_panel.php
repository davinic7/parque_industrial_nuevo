<?php
/**
 * Partial compartido del Centro de Comunicaciones (Fase 2).
 *
 * Renderiza el panel completo (3 columnas: categorias | lista | hilo).
 * Se incluye desde /empresa/comunicaciones.php y /ministerio/comunicaciones.php.
 *
 * Variables esperadas (definidas por el include padre):
 *   $coms_actor             string  'empresa' | 'ministerio'
 *   $coms_api_base          string  URL base de los endpoints, ej: '../api/comunicaciones'
 *   $coms_puede_broadcast   bool    si puede crear comunicado global
 *   $coms_puede_elegir_empresa bool si puede elegir empresa destinataria (solo ministerio)
 *   $coms_empresas_destino  array<{id,nombre}>  lista para el select (solo si puede_elegir_empresa)
 *
 * El layout asume Bootstrap 5 + Bootstrap Icons ya cargados por el header.
 */

if (!isset($coms_actor) || !isset($coms_api_base)) {
    throw new RuntimeException('comunicaciones_panel.php: faltan variables requeridas.');
}
$coms_puede_broadcast       = $coms_puede_broadcast       ?? false;
$coms_puede_elegir_empresa  = $coms_puede_elegir_empresa  ?? false;
$coms_empresas_destino      = $coms_empresas_destino      ?? [];
?>
<link rel="stylesheet" href="<?= asset_url('css/comunicaciones-panel.css') ?>">

<div class="coms-shell" id="coms-shell" data-actor="<?= e($coms_actor) ?>" data-api="<?= e($coms_api_base) ?>" data-csrf="<?= e($_SESSION[CSRF_TOKEN_NAME] ?? '') ?>">
    <!-- Sidebar de filtros -->
    <aside class="coms-sidebar">
        <h6>Bandeja</h6>
        <a class="nav-link active" data-filter-estado="abierta" data-filter-categoria="">
            <span><i class="bi bi-inbox me-2"></i>Activas</span>
            <span class="badge-count" data-counter></span>
        </a>
        <a class="nav-link" data-filter-estado="archivada" data-filter-categoria="">
            <span><i class="bi bi-archive me-2"></i>Archivadas</span>
        </a>

        <h6>Categorias <i class="bi bi-grip-vertical float-end opacity-50" title="Arrastra para reordenar" style="cursor:grab;"></i></h6>
        <div id="coms-cat-sortable">
            <a class="nav-link" data-filter-estado="abierta" data-filter-categoria="tramite">
                <span><i class="bi bi-file-earmark-text me-2"></i>Tramites</span>
            </a>
            <a class="nav-link" data-filter-estado="abierta" data-filter-categoria="consulta">
                <span><i class="bi bi-question-circle me-2"></i>Consultas</span>
            </a>
            <a class="nav-link" data-filter-estado="abierta" data-filter-categoria="reclamo">
                <span><i class="bi bi-exclamation-triangle me-2"></i>Reclamos</span>
            </a>
            <a class="nav-link" data-filter-estado="abierta" data-filter-categoria="comunicado">
                <span><i class="bi bi-megaphone me-2"></i>Comunicados</span>
            </a>
            <a class="nav-link" data-filter-estado="abierta" data-filter-categoria="formulario">
                <span><i class="bi bi-clipboard-check me-2"></i>Formularios</span>
            </a>
        </div>

        <hr>
        <button class="btn btn-primary btn-sm w-100" id="btn-nueva-conv">
            <i class="bi bi-pencil-square me-1"></i>Nueva conversación
        </button>
    </aside>

    <!-- Lista + hilo -->
    <div class="coms-main">
        <div class="coms-list" id="coms-list">
            <div class="coms-search">
                <input type="search" id="coms-search-input" class="form-control form-control-sm" placeholder="Buscar por titulo... (filtra al escribir)">
            </div>
            <div id="coms-list-items">
                <div class="text-center text-muted small p-4">Cargando...</div>
            </div>
        </div>

        <div class="coms-thread" id="coms-thread">
            <div class="coms-empty" id="coms-thread-empty">
                <div>
                    <i class="bi bi-chat-square-text d-block" style="font-size: 3rem; opacity: .3;"></i>
                    <div class="mt-2">Seleccione una conversación para ver los mensajes.</div>
                </div>
            </div>

            <div id="coms-thread-content" class="d-none flex-column h-100">
                <div class="coms-thread-header d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <button class="btn btn-link btn-sm d-lg-none p-0 mb-1" id="coms-back-list"><i class="bi bi-arrow-left"></i> Volver</button>
                        <h5 class="mb-1" id="coms-thread-title"></h5>
                        <div class="small text-muted" id="coms-thread-meta"></div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item" data-act="no_leida"><i class="bi bi-bell-slash me-2"></i>Marcar como no leida</button></li>
                            <li><button class="dropdown-item" data-act="archivar"><i class="bi bi-archive me-2"></i>Archivar</button></li>
                            <li><button class="dropdown-item d-none" data-act="desarchivar"><i class="bi bi-arrow-counterclockwise me-2"></i>Mover a activas</button></li>
                        </ul>
                    </div>
                </div>

                <div class="coms-thread-body" id="coms-thread-msgs"></div>

                <div class="coms-editor" id="coms-editor">
                    <div id="coms-attachments-preview" class="mb-2 d-flex flex-wrap gap-2"></div>
                    <textarea id="coms-editor-text" class="form-control" placeholder="Escriba su mensaje..."></textarea>
                    <div class="editor-toolbar justify-content-between">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="file" id="coms-file-input" multiple class="d-none">
                            <button class="btn btn-sm btn-outline-secondary" id="coms-attach-btn">
                                <i class="bi bi-paperclip"></i> Adjuntar
                            </button>
                            <?php if ($coms_actor === 'ministerio'): ?>
                            <button class="btn btn-sm btn-outline-info" id="coms-plantilla-btn" title="Insertar plantilla de respuesta">
                                <i class="bi bi-file-earmark-text"></i> Plantilla
                            </button>
                            <?php endif; ?>
                            <small class="text-muted">Hasta 25 MB en total</small>
                        </div>
                        <button class="btn btn-primary btn-sm" id="coms-send-btn">
                            <i class="bi bi-send"></i> Enviar
                        </button>
                    </div>
                    <div class="small text-success mt-1 d-none" id="coms-draft-status">
                        <i class="bi bi-cloud-check"></i> Borrador guardado
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: nueva conversacion -->
<div class="modal fade" id="coms-modal-nueva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Nueva conversación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Asunto</label>
                    <input type="text" class="form-control" id="coms-new-titulo" maxlength="200" placeholder="Resumen breve del tema">
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" id="coms-new-categoria">
                        <option value="consulta">Consulta</option>
                        <option value="tramite">Tramite</option>
                        <option value="reclamo">Reclamo</option>
                        <?php if ($coms_puede_broadcast): ?>
                        <option value="comunicado">Comunicado</option>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if ($coms_puede_elegir_empresa || $coms_puede_broadcast): ?>
                <div class="mb-3">
                    <label class="form-label">Destinatario</label>
                    <select class="form-select" id="coms-new-destinatario">
                        <?php if ($coms_puede_broadcast): ?>
                        <option value="global">Todas las empresas (comunicado)</option>
                        <?php endif; ?>
                        <?php foreach ($coms_empresas_destino as $emp): ?>
                        <option value="empresa:<?= (int)$emp['id'] ?>"><?= e($emp['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Mensaje</label>
                    <textarea class="form-control" id="coms-new-contenido" rows="5" placeholder="Escriba el primer mensaje..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="coms-new-enviar"><i class="bi bi-send me-1"></i>Enviar</button>
            </div>
        </div>
    </div>
</div>

<?php if ($coms_actor === 'ministerio'): ?>
<!-- Modal: elegir plantilla -->
<div class="modal fade" id="coms-modal-plantilla" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Plantillas de respuesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-2 border-bottom">
                    <input type="text" class="form-control form-control-sm" id="coms-plantilla-search" placeholder="Buscar plantilla...">
                </div>
                <div id="coms-plantilla-list" style="max-height:360px;overflow-y:auto;">
                    <div class="text-center text-muted py-4"><i class="bi bi-hourglass-split"></i> Cargando...</div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <a href="plantillas.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear me-1"></i>Gestionar plantillas</a>
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?= asset_url('js/comunicaciones-panel.js') ?>"></script>
<script src="<?= PUBLIC_URL ?>/vendor/sortablejs/Sortable.min.js"></script>
<script src="<?= asset_url('js/comunicaciones-categorias.js') ?>"></script>
