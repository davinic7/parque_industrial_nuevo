-- =============================================================
-- MIGRACIÓN 020: Columna survey_json para SurveyJS
-- Fecha: 2026-05-29
-- =============================================================

USE parque_industrial;

ALTER TABLE `formularios_dinamicos`
    ADD COLUMN IF NOT EXISTS `survey_json` longtext DEFAULT NULL
    COMMENT 'Definición completa del formulario en formato SurveyJS JSON';
