-- =============================================================
-- MIGRACIÓN 021: Reemplazar tipo 'tabla' por 'archivo_adjunto'
-- =============================================================
USE parque_industrial;

-- Convertir registros tabla existentes a texto (por si acaso)
UPDATE formulario_preguntas SET tipo = 'texto' WHERE tipo = 'tabla';

-- Actualizar el enum
ALTER TABLE `formulario_preguntas`
    MODIFY COLUMN `tipo`
        enum('texto','textarea','numero','fecha','select','radio','checkbox','archivo_adjunto','archivo','direccion')
        NOT NULL DEFAULT 'texto';
