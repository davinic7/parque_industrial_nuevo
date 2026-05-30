<?php /* Template HTML del builder de preguntas — incluido por formulario-nuevo y formulario-editar */ ?>
<template id="tplPregunta">
<div class="card mb-3 pregunta-item border">
    <div class="card-body p-3">
        <div class="row g-2 align-items-start">

            <!-- Tipo -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Tipo de campo</label>
                <select class="form-select form-select-sm pregunta-tipo" data-name="pregunta_tipo"></select>
            </div>

            <!-- Etiqueta -->
            <div class="col-md-6">
                <label class="form-label small fw-semibold mb-1">Pregunta / Título</label>
                <input type="text" class="form-control form-control-sm pregunta-label"
                       data-name="pregunta_label" maxlength="200"
                       placeholder="Escribí la pregunta aquí">
            </div>

            <!-- Requerido + quitar -->
            <div class="col-md-3 d-flex align-items-end justify-content-between">
                <div class="form-check mt-4">
                    <input class="form-check-input pregunta-req" type="checkbox"
                           value="1" data-name="pregunta_requerido">
                    <label class="form-check-label small">Obligatorio</label>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove mt-3" title="Quitar pregunta">
                    <i class="bi bi-trash"></i>
                </button>
            </div>

            <!-- Info del tipo -->
            <div class="col-12">
                <div class="tipo-info text-muted" style="font-size:.8rem;"></div>
            </div>

            <!-- Opciones (select / radio / checkbox) -->
            <div class="col-12 options-container d-none">
                <label class="form-label small fw-semibold mb-1">
                    Opciones
                    <span class="text-muted fw-normal">(máx. 10 · hasta 50 caracteres c/u)</span>
                </label>
                <div class="opciones-lista mb-2"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm agregar-opcion">
                    <i class="bi bi-plus me-1"></i>Agregar opción
                </button>
                <!-- Se llena antes del submit vía JS -->
                <textarea class="d-none opciones-hidden" data-name="pregunta_opciones"></textarea>
            </div>

            <!-- Archivo adjunto ministerio (reemplaza tabla) -->
            <div class="col-12 adj-container d-none">
                <label class="form-label small fw-semibold mb-1">
                    Archivo adjunto <span class="text-muted fw-normal">(imagen o PDF · opcional)</span>
                </label>
                <input type="file" class="form-control form-control-sm"
                       data-name="pregunta_adj_file" accept="image/*,.pdf">
                <div class="form-text" style="font-size:.78rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    Se mostrará a la empresa junto con el título de la pregunta.
                    La empresa responderá con texto libre.
                </div>
            </div>

            <!-- Número: min / max -->
            <div class="col-12 numero-container d-none">
                <div class="row g-2">
                    <div class="col-sm-3">
                        <label class="form-label small mb-1">Valor mínimo <span class="text-muted">(opc.)</span></label>
                        <input type="number" step="any" class="form-control form-control-sm pregunta-min"
                               data-name="pregunta_min">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label small mb-1">Valor máximo <span class="text-muted">(opc.)</span></label>
                        <input type="number" step="any" class="form-control form-control-sm pregunta-max"
                               data-name="pregunta_max">
                    </div>
                </div>
            </div>

            <!-- Ayuda -->
            <div class="col-12">
                <label class="form-label small mb-1">
                    Texto de ayuda <span class="text-muted fw-normal">(opcional · se muestra debajo de la pregunta)</span>
                </label>
                <input type="text" class="form-control form-control-sm pregunta-ayuda"
                       data-name="pregunta_ayuda" maxlength="150"
                       placeholder="Ej: Ingrese el valor en kilogramos">
            </div>

        </div>
    </div>
</div>
</template>
