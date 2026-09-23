-- ============================================================
-- 023 - Contactos de emergencia y servicios
-- Directorio de teléfonos útiles (cortes de luz, agua, gas,
-- emergencias, administración del parque). Lo administra el
-- Ministerio; cada contacto puede ser público o sólo para empresas.
-- Ejecutar con un usuario administrador de la base (no parque_app).
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `contactos_emergencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(30) NOT NULL DEFAULT 'otros',
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `telefono_alt` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `visibilidad` enum('publico','empresas') NOT NULL DEFAULT 'publico',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_contactos_activo` (`activo`,`visibilidad`,`categoria`,`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Números nacionales de emergencia (el Ministerio agrega los de
-- energía, agua, gas y administración del parque desde su panel).
INSERT INTO `contactos_emergencia` (`categoria`,`nombre`,`telefono`,`descripcion`,`visibilidad`,`orden`)
SELECT * FROM (
  SELECT 'emergencias' c,'Bomberos' n,'100' t,'Incendios, rescates y derrames' d,'publico' v,1 o UNION ALL
  SELECT 'emergencias','Policía','101','Emergencias policiales','publico',2 UNION ALL
  SELECT 'emergencias','Defensa Civil','103','Catástrofes, temporales y evacuaciones','publico',3 UNION ALL
  SELECT 'emergencias','Emergencias médicas (SAME)','107','Ambulancias y urgencias médicas','publico',4
) s WHERE NOT EXISTS (SELECT 1 FROM `contactos_emergencia`);
