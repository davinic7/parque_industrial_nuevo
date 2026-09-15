-- ============================================================================
-- PARQUE INDUSTRIAL DE CATAMARCA - Base de datos oficial
-- ============================================================================
-- Archivo unificado para despliegue completo.
-- Ejecutar UNA SOLA VEZ en un servidor MySQL/MariaDB limpio.
--
-- Requisitos: MySQL 5.7+ o MariaDB 10.4+
-- Encoding:   UTF-8 (utf8mb4)
--
-- Instrucciones:
--   1. Abrir phpMyAdmin o consola MySQL
--   2. Importar este archivo completo
--   3. Se creara la base de datos "parque_industrial" con todas las tablas
--      y los datos iniciales necesarios para el funcionamiento del sistema.
--
-- Usuarios iniciales:
--   - admin@parqueindustrial.gob.ar  / admin123  (rol: admin)
--   - ministerio@catamarca.gob.ar    / admin123  (rol: ministerio)
--
-- IMPORTANTE: Cambiar las contrasenas despues del primer inicio de sesion.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `parque_industrial`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `parque_industrial`;

SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;

-- ============================================================================
-- ESTRUCTURA DE TABLAS
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Tabla: usuarios
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('empresa','ministerio','admin') NOT NULL DEFAULT 'empresa',
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL,
  `token_recuperacion` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `token_activacion` varchar(255) DEFAULT NULL,
  `token_activacion_expira` datetime DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: empresas
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `empresas`;
CREATE TABLE `empresas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `rubro` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL COMMENT 'PI El Pantanillo, Capital, etc',
  `direccion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email_contacto` varchar(255) DEFAULT NULL,
  `contacto_nombre` varchar(255) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `imagen_portada` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','activa','suspendida','inactiva') DEFAULT 'pendiente',
  `perfil_completo` tinyint(1) DEFAULT 0,
  `verificada` tinyint(1) DEFAULT 0,
  `visitas` int(11) DEFAULT 0,
  `lote_declarado` varchar(50) DEFAULT NULL COMMENT 'Numero de lote declarado por la empresa',
  `lote_solicitud_estado` enum('sin_solicitud','pendiente','asignado') NOT NULL DEFAULT 'sin_solicitud',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_rubro` (`rubro`),
  KEY `idx_ubicacion` (`ubicacion`),
  KEY `idx_estado` (`estado`),
  KEY `idx_visitas` (`visitas`),
  FULLTEXT KEY `idx_busqueda` (`nombre`,`razon_social`,`descripcion`,`rubro`),
  CONSTRAINT `empresas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: rubros
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `rubros`;
CREATE TABLE `rubros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL COMMENT 'Color hex para graficos',
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: ubicaciones
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ubicaciones`;
CREATE TABLE `ubicaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `latitud_centro` decimal(10,8) DEFAULT NULL,
  `longitud_centro` decimal(11,8) DEFAULT NULL,
  `poligono_geojson` text DEFAULT NULL COMMENT 'GeoJSON del poligono del area',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: configuracion_sitio
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `configuracion_sitio`;
CREATE TABLE `configuracion_sitio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('text','textarea','number','boolean','json','image') DEFAULT 'text',
  `grupo` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: datos_empresa
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `datos_empresa`;
CREATE TABLE `datos_empresa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `periodo` varchar(20) NOT NULL COMMENT 'Ej: 2025-Q1, 2025-Q2',
  `dotacion_total` int(11) DEFAULT 0,
  `empleados_masculinos` int(11) DEFAULT 0,
  `empleados_femeninos` int(11) DEFAULT 0,
  `empleados_otros` int(11) DEFAULT 0,
  `capacidad_instalada` varchar(255) DEFAULT NULL,
  `porcentaje_capacidad_uso` decimal(5,2) DEFAULT NULL,
  `produccion_mensual` varchar(255) DEFAULT NULL,
  `unidad_produccion` varchar(50) DEFAULT NULL,
  `consumo_energia` decimal(12,2) DEFAULT NULL COMMENT 'kWh mensuales',
  `consumo_agua` decimal(12,2) DEFAULT NULL COMMENT 'm3 mensuales',
  `consumo_gas` decimal(12,2) DEFAULT NULL COMMENT 'm3 mensuales',
  `conexion_red_agua` tinyint(1) DEFAULT 0,
  `pozo_agua` tinyint(1) DEFAULT 0,
  `conexion_gas_natural` tinyint(1) DEFAULT 0,
  `conexion_cloacas` tinyint(1) DEFAULT 0,
  `exporta` tinyint(1) DEFAULT 0,
  `productos_exporta` text DEFAULT NULL,
  `paises_exporta` varchar(255) DEFAULT NULL,
  `monto_exportaciones` decimal(15,2) DEFAULT NULL,
  `importa` tinyint(1) DEFAULT 0,
  `productos_importa` text DEFAULT NULL,
  `paises_importa` varchar(255) DEFAULT NULL,
  `monto_importaciones` decimal(15,2) DEFAULT NULL,
  `emisiones_co2` decimal(12,4) DEFAULT NULL COMMENT 'Toneladas CO2 equivalente',
  `fuente_emision_principal` varchar(100) DEFAULT NULL,
  `inversion_anual` decimal(15,2) DEFAULT NULL,
  `inversion_maquinaria` decimal(15,2) DEFAULT NULL,
  `inversion_infraestructura` decimal(15,2) DEFAULT NULL,
  `rango_facturacion` enum('micro','pequena','mediana','grande') DEFAULT NULL,
  `certificaciones` text DEFAULT NULL COMMENT 'ISO, etc.',
  `estado` enum('borrador','enviado','aprobado','rechazado') DEFAULT 'borrador',
  `declaracion_jurada` tinyint(1) DEFAULT 0,
  `fecha_declaracion` datetime DEFAULT NULL,
  `ip_declaracion` varchar(45) DEFAULT NULL,
  `observaciones_ministerio` text DEFAULT NULL,
  `revisado_por` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresa_periodo` (`empresa_id`,`periodo`),
  KEY `revisado_por` (`revisado_por`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `datos_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `datos_empresa_ibfk_2` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: lotes
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `lotes`;
CREATE TABLE `lotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_lote` varchar(50) NOT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `superficie_m2` decimal(10,2) DEFAULT NULL,
  `estado` enum('disponible','ocupado','reservado') DEFAULT 'disponible',
  `geometria_terreno` longtext DEFAULT NULL COMMENT 'GeoJSON Polygon serializado',
  `empresa_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_lote` (`numero_lote`),
  KEY `idx_estado` (`estado`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `lotes_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: empresa_imagenes
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `empresa_imagenes`;
CREATE TABLE `empresa_imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: visitas_empresa
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `visitas_empresa`;
CREATE TABLE `visitas_empresa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_empresa_fecha` (`empresa_id`,`created_at`),
  CONSTRAINT `visitas_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: publicaciones
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `publicaciones`;
CREATE TABLE `publicaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) DEFAULT NULL COMMENT 'NULL si es del ministerio',
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('noticia','evento','promocion','comunicado') DEFAULT 'noticia',
  `titulo` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `extracto` text DEFAULT NULL,
  `contenido` longtext DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `publicado` tinyint(1) DEFAULT 0,
  `destacado` tinyint(1) DEFAULT 0,
  `mostrar_en_inicio` tinyint(1) DEFAULT 0,
  `estado` enum('borrador','pendiente','aprobado','rechazado') DEFAULT 'borrador',
  `aprobado_por` int(11) DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `motivo_rechazo` text DEFAULT NULL,
  `fecha_publicacion` datetime DEFAULT NULL,
  `fecha_expiracion` datetime DEFAULT NULL,
  `visitas` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `aprobado_por` (`aprobado_por`),
  KEY `idx_estado` (`estado`),
  KEY `idx_publicado` (`publicado`),
  KEY `idx_fecha` (`fecha_publicacion`),
  CONSTRAINT `publicaciones_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publicaciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publicaciones_ibfk_3` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: archivos_publicacion
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `archivos_publicacion`;
CREATE TABLE `archivos_publicacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicacion_id` int(11) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `publicacion_id` (`publicacion_id`),
  CONSTRAINT `archivos_publicacion_ibfk_1` FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: publicacion_likes
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `publicacion_likes`;
CREATE TABLE `publicacion_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicacion_id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_like_ip` (`publicacion_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: banners_home
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `banners_home`;
CREATE TABLE `banners_home` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) DEFAULT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `tipo` enum('imagen','video') NOT NULL DEFAULT 'imagen',
  `url_video` varchar(500) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: notificaciones
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'perfil_editado, formulario_enviado, etc',
  `titulo` varchar(255) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
  `leida` tinyint(1) DEFAULT 0,
  `fecha_lectura` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_leida` (`usuario_id`,`leida`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: log_actividad
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `log_actividad`;
CREATE TABLE `log_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: login_attempts
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `intentos` int(11) NOT NULL DEFAULT 1,
  `ultimo_intento` datetime NOT NULL DEFAULT current_timestamp(),
  `bloqueado_hasta` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`),
  KEY `idx_bloqueado` (`bloqueado_hasta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: password_reset_requests
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_requests`;
CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: mensajes (v1 - legado)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `mensajes`;
CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `remitente_id` int(11) NOT NULL,
  `destinatario_id` int(11) DEFAULT NULL COMMENT 'NULL = mensaje al ministerio',
  `empresa_id` int(11) DEFAULT NULL,
  `asunto` varchar(255) NOT NULL,
  `categoria` varchar(80) DEFAULT NULL,
  `contenido` text NOT NULL,
  `adjuntos` text DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_lectura` datetime DEFAULT NULL,
  `archivado` tinyint(1) DEFAULT 0,
  `mensaje_padre_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `remitente_id` (`remitente_id`),
  KEY `empresa_id` (`empresa_id`),
  KEY `mensaje_padre_id` (`mensaje_padre_id`),
  KEY `idx_destinatario` (`destinatario_id`),
  KEY `idx_leido` (`leido`),
  CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mensajes_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mensajes_ibfk_3` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mensajes_ibfk_4` FOREIGN KEY (`mensaje_padre_id`) REFERENCES `mensajes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: conversaciones
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `conversaciones`;
CREATE TABLE `conversaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `iniciada_por` enum('empresa','ministerio','sistema') NOT NULL,
  `categoria` enum('tramite','consulta','reclamo','comunicado','formulario','sistema') NOT NULL DEFAULT 'consulta',
  `estado` enum('abierta','cerrada','archivada') NOT NULL DEFAULT 'abierta',
  `referencia_tipo` varchar(40) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `ultimo_mensaje_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_conv_referencia` (`referencia_tipo`,`referencia_id`),
  KEY `idx_empresa_estado` (`empresa_id`,`estado`),
  KEY `idx_estado_ultimo` (`estado`),
  KEY `idx_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: mensajes_v2
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `mensajes_v2`;
CREATE TABLE `mensajes_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversacion_id` int(11) NOT NULL,
  `remitente_id` int(11) DEFAULT NULL,
  `remitente_tipo` enum('empresa','ministerio','sistema') NOT NULL,
  `contenido` mediumtext NOT NULL,
  `es_borrador` tinyint(1) NOT NULL DEFAULT 0,
  `leido_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conv_created` (`conversacion_id`,`created_at`),
  KEY `idx_conv_no_leidos` (`conversacion_id`),
  KEY `idx_remitente` (`remitente_id`,`es_borrador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: adjuntos_mensajes
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `adjuntos_mensajes`;
CREATE TABLE `adjuntos_mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mensaje_id` int(11) NOT NULL,
  `archivo_url` varchar(500) NOT NULL COMMENT 'Path local o URL Cloudinary',
  `archivo_nombre` varchar(255) NOT NULL,
  `archivo_tipo` varchar(100) NOT NULL COMMENT 'MIME type',
  `archivo_tamano` int(10) unsigned NOT NULL COMMENT 'Bytes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mensaje` (`mensaje_id`),
  CONSTRAINT `fk_adj_mensaje` FOREIGN KEY (`mensaje_id`) REFERENCES `mensajes_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: comunicado_visto
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `comunicado_visto`;
CREATE TABLE `comunicado_visto` (
  `conversacion_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `leido_at` datetime DEFAULT NULL,
  `archivado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`conversacion_id`,`empresa_id`),
  KEY `idx_empresa_leido` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: plantillas_respuesta
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `plantillas_respuesta`;
CREATE TABLE `plantillas_respuesta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) NOT NULL,
  `contenido` text NOT NULL,
  `categoria` enum('tramite','consulta','reclamo','comunicado','formulario','sistema','general') NOT NULL DEFAULT 'general',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activa_orden` (`activa`,`orden`,`titulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: formularios_config
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `formularios_config`;
CREATE TABLE `formularios_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `campos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Estructura JSON de campos' CHECK (json_valid(`campos`)),
  `activo` tinyint(1) DEFAULT 1,
  `obligatorio` tinyint(1) DEFAULT 0,
  `fecha_limite` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: respuestas_formulario
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `respuestas_formulario`;
CREATE TABLE `respuestas_formulario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formulario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `respuestas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`respuestas`)),
  `estado` enum('borrador','enviado','aprobado','rechazado') DEFAULT 'borrador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_form_empresa` (`formulario_id`,`empresa_id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `respuestas_formulario_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios_config` (`id`) ON DELETE CASCADE,
  CONSTRAINT `respuestas_formulario_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: formularios_dinamicos
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `formularios_dinamicos`;
CREATE TABLE `formularios_dinamicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
  `creado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `survey_json` longtext DEFAULT NULL COMMENT 'Definicion completa del formulario en formato SurveyJS JSON',
  PRIMARY KEY (`id`),
  KEY `idx_fd_estado` (`estado`),
  KEY `idx_fd_creado_por` (`creado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: formulario_preguntas
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `formulario_preguntas`;
CREATE TABLE `formulario_preguntas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formulario_id` int(11) NOT NULL,
  `tipo` enum('texto','textarea','numero','fecha','select','radio','checkbox','archivo_adjunto','archivo','direccion') NOT NULL DEFAULT 'texto',
  `etiqueta` varchar(255) NOT NULL,
  `ayuda` varchar(255) DEFAULT NULL,
  `requerido` tinyint(1) DEFAULT 0,
  `opciones` longtext DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `min_valor` decimal(15,4) DEFAULT NULL COMMENT 'Solo para tipo=numero',
  `max_valor` decimal(15,4) DEFAULT NULL COMMENT 'Solo para tipo=numero',
  PRIMARY KEY (`id`),
  KEY `idx_formulario` (`formulario_id`),
  CONSTRAINT `formulario_preguntas_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios_dinamicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: formulario_envios
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `formulario_envios`;
CREATE TABLE `formulario_envios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formulario_id` int(11) NOT NULL,
  `tipo_filtro` varchar(40) NOT NULL DEFAULT 'todos',
  `filtros_json` text DEFAULT NULL,
  `total_destinatarios` int(11) NOT NULL DEFAULT 0,
  `fecha_limite` date DEFAULT NULL,
  `enviado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fe_formulario` (`formulario_id`),
  KEY `idx_fe_enviado_por` (`enviado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: formulario_destinatarios
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `formulario_destinatarios`;
CREATE TABLE `formulario_destinatarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `envio_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `notificado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_notificacion` datetime DEFAULT NULL,
  `plazo_hasta` date DEFAULT NULL,
  `respondido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_respuesta` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_dest_envio_empresa` (`envio_id`,`empresa_id`),
  KEY `idx_fd_empresa` (`empresa_id`),
  KEY `idx_fd_respondido` (`respondido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: formulario_respuestas
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `formulario_respuestas`;
CREATE TABLE `formulario_respuestas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formulario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `estado` enum('borrador','enviado') NOT NULL DEFAULT 'borrador',
  `respuestas` longtext NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `enviado_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fr_formulario_empresa` (`formulario_id`,`empresa_id`),
  KEY `idx_fr_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: solicitudes_proyecto
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `solicitudes_proyecto`;
CREATE TABLE `solicitudes_proyecto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contacto` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nombre_empresa` varchar(255) DEFAULT NULL,
  `tipo_persona` enum('fisica','juridica') DEFAULT NULL,
  `dni_titulares` varchar(255) DEFAULT NULL,
  `cuit_cuil` varchar(20) DEFAULT NULL,
  `resumen_proyecto` text DEFAULT NULL,
  `solicita_cita` tinyint(1) NOT NULL DEFAULT 0,
  `rubro` varchar(100) DEFAULT NULL,
  `rubro_actividad` varchar(255) DEFAULT NULL,
  `tiene_contrato_constitutivo` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_acta_autoridades` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_inscripcion_registro` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_inscripcion_arca` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_inscripcion_arcat` tinyint(1) NOT NULL DEFAULT 0,
  `otras_inscripciones` varchar(500) DEFAULT NULL,
  `tiene_estudio_ambiental` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_croquis_obra` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_cronograma_obra` tinyint(1) NOT NULL DEFAULT 0,
  `telefono` varchar(50) DEFAULT NULL,
  `estado` enum('nueva','en_carpeta','eliminada') NOT NULL DEFAULT 'nueva',
  `observaciones` text DEFAULT NULL,
  `archivos` text DEFAULT NULL,
  `archivo_1` varchar(500) DEFAULT NULL,
  `archivo_2` varchar(500) DEFAULT NULL,
  `archivo_3` varchar(500) DEFAULT NULL,
  `archivo_4` varchar(500) DEFAULT NULL,
  `archivo_5` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VISTAS
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Vista: v_empresas_completas
-- ----------------------------------------------------------------------------
DROP VIEW IF EXISTS `v_empresas_completas`;
CREATE ALGORITHM=UNDEFINED
  DEFINER=`root`@`localhost` SQL SECURITY DEFINER
  VIEW `v_empresas_completas` AS
  SELECT
    `e`.`id` AS `id`,
    `e`.`usuario_id` AS `usuario_id`,
    `e`.`nombre` AS `nombre`,
    `e`.`razon_social` AS `razon_social`,
    `e`.`cuit` AS `cuit`,
    `e`.`rubro` AS `rubro`,
    `e`.`descripcion` AS `descripcion`,
    `e`.`ubicacion` AS `ubicacion`,
    `e`.`direccion` AS `direccion`,
    `e`.`latitud` AS `latitud`,
    `e`.`longitud` AS `longitud`,
    `e`.`telefono` AS `telefono`,
    `e`.`email_contacto` AS `email_contacto`,
    `e`.`contacto_nombre` AS `contacto_nombre`,
    `e`.`sitio_web` AS `sitio_web`,
    `e`.`facebook` AS `facebook`,
    `e`.`instagram` AS `instagram`,
    `e`.`linkedin` AS `linkedin`,
    `e`.`logo` AS `logo`,
    `e`.`imagen_portada` AS `imagen_portada`,
    `e`.`estado` AS `estado`,
    `e`.`perfil_completo` AS `perfil_completo`,
    `e`.`verificada` AS `verificada`,
    `e`.`visitas` AS `visitas`,
    `e`.`created_at` AS `created_at`,
    `e`.`updated_at` AS `updated_at`,
    `de`.`dotacion_total` AS `dotacion_total`,
    `de`.`empleados_masculinos` AS `empleados_masculinos`,
    `de`.`empleados_femeninos` AS `empleados_femeninos`,
    `de`.`consumo_energia` AS `consumo_energia`,
    `de`.`consumo_agua` AS `consumo_agua`,
    `de`.`exporta` AS `exporta`,
    `de`.`importa` AS `importa`,
    `de`.`emisiones_co2` AS `emisiones_co2`,
    `de`.`periodo` AS `ultimo_periodo`
  FROM (`empresas` `e`
    LEFT JOIN `datos_empresa` `de`
      ON (`e`.`id` = `de`.`empresa_id`
          AND `de`.`periodo` = (
            SELECT MAX(`datos_empresa`.`periodo`)
            FROM `datos_empresa`
            WHERE `datos_empresa`.`empresa_id` = `e`.`id`
          )));

-- ----------------------------------------------------------------------------
-- Vista: v_estadisticas_generales
-- ----------------------------------------------------------------------------
DROP VIEW IF EXISTS `v_estadisticas_generales`;
CREATE ALGORITHM=UNDEFINED
  DEFINER=`root`@`localhost` SQL SECURITY DEFINER
  VIEW `v_estadisticas_generales` AS
  SELECT
    (SELECT COUNT(0) FROM `empresas` WHERE `empresas`.`estado` = 'activa') AS `total_empresas_activas`,
    (SELECT COUNT(0) FROM `empresas`) AS `total_empresas`,
    (SELECT COALESCE(SUM(`de`.`dotacion_total`),0)
     FROM (`datos_empresa` `de` JOIN `empresas` `e` ON(`de`.`empresa_id` = `e`.`id`))
     WHERE `e`.`estado` = 'activa'
       AND `de`.`periodo` = (
         SELECT MAX(`datos_empresa`.`periodo`)
         FROM `datos_empresa`
         WHERE `datos_empresa`.`empresa_id` = `de`.`empresa_id`
       )) AS `total_empleados`,
    (SELECT COUNT(DISTINCT `empresas`.`rubro`) FROM `empresas` WHERE `empresas`.`estado` = 'activa') AS `total_rubros`,
    (SELECT COUNT(0) FROM `publicaciones` WHERE `publicaciones`.`publicado` = 1 AND `publicaciones`.`estado` = 'aprobado') AS `total_publicaciones`;

-- ============================================================================
-- DATOS INICIALES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Usuarios del sistema (admin y ministerio)
-- Contrasena por defecto: admin123 (cambiar inmediatamente)
-- ----------------------------------------------------------------------------
INSERT INTO `usuarios` (`id`, `email`, `password`, `rol`, `activo`, `email_verificado`, `created_at`) VALUES
(1, 'admin@parqueindustrial.gob.ar', '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'admin', 1, 1, NOW()),
(2, 'ministerio@catamarca.gob.ar', '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'ministerio', 1, 1, NOW());

-- ----------------------------------------------------------------------------
-- Rubros industriales
-- ----------------------------------------------------------------------------
INSERT INTO `rubros` (`id`, `nombre`, `descripcion`, `icono`, `color`, `activo`, `orden`, `created_at`) VALUES
(1, 'Textil', NULL, NULL, '#3498db', 1, 1, NOW()),
(2, 'Construcción', NULL, NULL, '#e74c3c', 1, 2, NOW()),
(3, 'Metalúrgica', NULL, NULL, '#95a5a6', 1, 3, NOW()),
(4, 'Alimentos', NULL, NULL, '#27ae60', 1, 4, NOW()),
(5, 'Transporte', NULL, NULL, '#f39c12', 1, 5, NOW()),
(6, 'Reciclado', NULL, NULL, '#2ecc71', 1, 6, NOW()),
(7, 'Hormigón', NULL, NULL, '#7f8c8d', 1, 7, NOW()),
(8, 'Electrodomésticos', NULL, NULL, '#9b59b6', 1, 8, NOW()),
(9, 'Medicamentos', NULL, NULL, '#1abc9c', 1, 9, NOW()),
(10, 'Calzados', NULL, NULL, '#e67e22', 1, 10, NOW()),
(11, 'Fibra de Vidrio', NULL, NULL, '#34495e', 1, 11, NOW()),
(12, 'Combustibles', NULL, NULL, '#c0392b', 1, 12, NOW()),
(13, 'Minería', NULL, NULL, '#8e44ad', 1, 13, NOW()),
(14, 'Química', NULL, NULL, '#16a085', 1, 14, NOW()),
(15, 'Maquinaria Industrial', NULL, NULL, '#2c3e50', 1, 15, NOW()),
(16, 'Autopartes', NULL, NULL, '#d35400', 1, 16, NOW()),
(17, 'Frigorífico', NULL, NULL, '#2980b9', 1, 17, NOW()),
(18, 'Lácteos', NULL, NULL, '#f1c40f', 1, 18, NOW()),
(19, 'Otros', NULL, NULL, '#bdc3c7', 1, 99, NOW()),
(20, 'Plásticos', NULL, NULL, '#6c757d', 1, 20, NOW()),
(21, 'Agroindustria', NULL, NULL, '#28a745', 1, 21, NOW()),
(22, 'Motocicletas', NULL, NULL, '#fd7e14', 1, 22, NOW()),
(23, 'Dulces', NULL, NULL, '#e83e8c', 1, 23, NOW());

-- ----------------------------------------------------------------------------
-- Configuracion del sitio
-- ----------------------------------------------------------------------------
INSERT INTO `configuracion_sitio` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `updated_at`) VALUES
(1, 'sitio_nombre', 'Parque Industrial de Catamarca', 'text', 'general', 'Nombre del sitio', NOW()),
(2, 'sitio_descripcion', 'Portal del Parque Industrial de la Provincia de Catamarca', 'textarea', 'general', 'Descripción del sitio', NOW()),
(3, 'sitio_email', 'contacto@parqueindustrial.gob.ar', 'text', 'contacto', 'Email de contacto', NOW()),
(4, 'sitio_telefono', '(0383) 4123456', 'text', 'contacto', 'Teléfono de contacto', NOW()),
(5, 'sitio_direccion', 'San Fernando del Valle de Catamarca, Argentina', 'text', 'contacto', 'Dirección física', NOW()),
(6, 'mapa_lat_centro', '-28.4696', 'text', 'mapa', 'Latitud centro del mapa', NOW()),
(7, 'mapa_lng_centro', '-65.7795', 'text', 'mapa', 'Longitud centro del mapa', NOW()),
(8, 'mapa_zoom_inicial', '12', 'number', 'mapa', 'Zoom inicial del mapa', NOW()),
(9, 'redes_facebook', 'https://facebook.com/parqueindustrialcatamarca', 'text', 'redes', 'Facebook', NOW()),
(10, 'redes_instagram', 'https://instagram.com/parqueindustrialcatamarca', 'text', 'redes', 'Instagram', NOW()),
(11, 'redes_twitter', '', 'text', 'redes', 'Twitter/X', NOW()),
(12, 'texto_sobre_nosotros', 'El Parque Industrial de Catamarca es un polo de desarrollo productivo estratégico para la región del NOA, ubicado en la localidad de El Pantanillo. Cuenta con infraestructura moderna, servicios de agua, gas, energía eléctrica y conectividad, y alberga empresas de diversos rubros industriales.', 'textarea', 'contenido', 'Texto sobre nosotros', NOW()),
(13, 'mostrar_estadisticas_publicas', '1', 'boolean', 'privacidad', 'Mostrar estadísticas al público', NOW());

-- ----------------------------------------------------------------------------
-- Ubicaciones
-- ----------------------------------------------------------------------------
INSERT INTO `ubicaciones` (`id`, `nombre`, `descripcion`, `latitud_centro`, `longitud_centro`, `poligono_geojson`, `activo`, `created_at`) VALUES
(1, 'PI El Pantanillo', 'Parque Industrial El Pantanillo', -28.46960000, -65.77950000, NULL, 1, NOW()),
(2, 'Capital', 'San Fernando del Valle de Catamarca', -28.46960000, -65.78520000, NULL, 1, NOW()),
(3, 'Valle Viejo', NULL, -28.39170000, -65.70950000, NULL, 1, NOW()),
(4, 'Recreo', NULL, -29.28330000, -65.06670000, NULL, 1, NOW());

-- ============================================================================
-- RESTAURAR CONFIGURACION
-- ============================================================================

SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
SET SQL_NOTES=@OLD_SQL_NOTES;

-- ============================================================================
-- FIN - Base de datos lista para usar
-- ============================================================================
