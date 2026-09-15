-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-06-2026 a las 23:59:49
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `parque_industrial`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `adjuntos_mensajes`
--

CREATE TABLE `adjuntos_mensajes` (
  `id` int(11) NOT NULL,
  `mensaje_id` int(11) NOT NULL,
  `archivo_url` varchar(500) NOT NULL COMMENT 'Path local o URL Cloudinary',
  `archivo_nombre` varchar(255) NOT NULL,
  `archivo_tipo` varchar(100) NOT NULL COMMENT 'MIME type',
  `archivo_tamano` int(10) UNSIGNED NOT NULL COMMENT 'Bytes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_publicacion`
--

CREATE TABLE `archivos_publicacion` (
  `id` int(11) NOT NULL,
  `publicacion_id` int(11) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banners_home`
--

CREATE TABLE `banners_home` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicado_visto`
--

CREATE TABLE `comunicado_visto` (
  `conversacion_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `leido_at` datetime DEFAULT NULL,
  `archivado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sitio`
--

CREATE TABLE `configuracion_sitio` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('text','textarea','number','boolean','json','image') DEFAULT 'text',
  `grupo` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sitio`
--

INSERT INTO `configuracion_sitio` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `updated_at`) VALUES
(1, 'sitio_nombre', 'Parque Industrial de Catamarca', 'text', 'general', 'Nombre del sitio', '2026-05-28 20:32:12'),
(2, 'sitio_descripcion', 'Portal del Parque Industrial de la Provincia de Catamarca', 'textarea', 'general', 'Descripci??n del sitio', '2026-05-28 20:32:12'),
(3, 'sitio_email', 'contacto@parqueindustrial.gob.ar', 'text', 'contacto', 'Email de contacto', '2026-05-28 20:32:12'),
(4, 'sitio_telefono', '(0383) 4123456', 'text', 'contacto', 'Tel??fono de contacto', '2026-05-28 20:32:12'),
(5, 'sitio_direccion', 'San Fernando del Valle de Catamarca, Argentina', 'text', 'contacto', 'Direcci??n f??sica', '2026-05-28 20:32:12'),
(6, 'mapa_lat_centro', '-28.4696', 'text', 'mapa', 'Latitud centro del mapa', '2026-05-28 20:32:12'),
(7, 'mapa_lng_centro', '-65.7795', 'text', 'mapa', 'Longitud centro del mapa', '2026-05-28 20:32:12'),
(8, 'mapa_zoom_inicial', '12', 'number', 'mapa', 'Zoom inicial del mapa', '2026-05-28 20:32:12'),
(9, 'redes_facebook', 'https://facebook.com/parqueindustrialcatamarca', 'text', 'redes', 'Facebook', '2026-05-28 20:32:12'),
(10, 'redes_instagram', 'https://instagram.com/parqueindustrialcatamarca', 'text', 'redes', 'Instagram', '2026-05-28 20:32:12'),
(11, 'redes_twitter', '', 'text', 'redes', 'Twitter/X', '2026-05-28 20:32:12'),
(12, 'texto_sobre_nosotros', 'El Parque Industrial de Catamarca es un polo de desarrollo...', 'textarea', 'contenido', 'Texto sobre nosotros', '2026-05-28 20:32:12'),
(13, 'mostrar_estadisticas_publicas', '1', 'boolean', 'privacidad', 'Mostrar estad??sticas al p??blico', '2026-05-28 20:32:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversaciones`
--

CREATE TABLE `conversaciones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `iniciada_por` enum('empresa','ministerio','sistema') NOT NULL,
  `categoria` enum('tramite','consulta','reclamo','comunicado','formulario','sistema') NOT NULL DEFAULT 'consulta',
  `estado` enum('abierta','cerrada','archivada') NOT NULL DEFAULT 'abierta',
  `referencia_tipo` varchar(40) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `ultimo_mensaje_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `conversaciones`
--

INSERT INTO `conversaciones` (`id`, `titulo`, `empresa_id`, `iniciada_por`, `categoria`, `estado`, `referencia_tipo`, `referencia_id`, `ultimo_mensaje_at`, `created_at`, `updated_at`) VALUES
(1, 'Nuevo formulario: prueba', 100, 'ministerio', 'formulario', 'abierta', 'formulario_dinamico', 1, '2026-05-29 01:21:30', '2026-05-29 04:16:56', '2026-05-29 04:21:30'),
(2, 'Consulta sobre habilitación de galpón', 1, 'empresa', 'consulta', 'abierta', NULL, NULL, '2026-06-06 15:56:56', '2026-06-05 20:59:47', '2026-06-06 18:56:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_empresa`
--

CREATE TABLE `datos_empresa` (
  `id` int(11) NOT NULL,
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
  `rango_facturacion` enum('micro','peque??a','mediana','grande') DEFAULT NULL,
  `certificaciones` text DEFAULT NULL COMMENT 'ISO, etc.',
  `estado` enum('borrador','enviado','aprobado','rechazado') DEFAULT 'borrador',
  `declaracion_jurada` tinyint(1) DEFAULT 0,
  `fecha_declaracion` datetime DEFAULT NULL,
  `ip_declaracion` varchar(45) DEFAULT NULL,
  `observaciones_ministerio` text DEFAULT NULL,
  `revisado_por` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
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
  `lote_declarado` varchar(50) DEFAULT NULL COMMENT 'Número de lote declarado por la empresa',
  `lote_solicitud_estado` enum('sin_solicitud','pendiente','asignado') NOT NULL DEFAULT 'sin_solicitud',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `usuario_id`, `nombre`, `razon_social`, `cuit`, `rubro`, `descripcion`, `ubicacion`, `direccion`, `latitud`, `longitud`, `telefono`, `email_contacto`, `contacto_nombre`, `sitio_web`, `facebook`, `instagram`, `linkedin`, `logo`, `imagen_portada`, `estado`, `perfil_completo`, `verificada`, `visitas`, `created_at`, `updated_at`) VALUES
(1, 3, 'Empresa Demo S.R.L.', '', '', 'Textil', 'Empresa de manufactura especializada en productos industriales para la región.', 'PI El Pantanillo', '', NULL, NULL, '3834000001', '', 'Juan P??rez', '', '', '', NULL, NULL, NULL, 'suspendida', 0, 0, 0, '2026-05-28 20:32:12', '2026-06-10 20:40:49'),
(100, 201, 'prueba', NULL, NULL, 'Textil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'activa', 0, 0, 7, '2026-05-29 04:14:10', '2026-06-10 21:36:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_imagenes`
--

CREATE TABLE `empresa_imagenes` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formularios_config`
--

CREATE TABLE `formularios_config` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `campos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Estructura JSON de campos' CHECK (json_valid(`campos`)),
  `activo` tinyint(1) DEFAULT 1,
  `obligatorio` tinyint(1) DEFAULT 0,
  `fecha_limite` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formularios_dinamicos`
--

CREATE TABLE `formularios_dinamicos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
  `creado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `survey_json` longtext DEFAULT NULL COMMENT 'Definici├│n completa del formulario en formato SurveyJS JSON'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `formularios_dinamicos`
--

INSERT INTO `formularios_dinamicos` (`id`, `titulo`, `descripcion`, `estado`, `creado_por`, `created_at`, `updated_at`, `survey_json`) VALUES
(1, 'prueba', 'para prueba', 'publicado', 1, '2026-05-28 20:41:35', '2026-05-29 04:16:15', NULL),
(2, 'asda', 'asas', 'borrador', 1, '2026-05-29 05:31:15', '2026-05-29 05:31:15', '{}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formulario_destinatarios`
--

CREATE TABLE `formulario_destinatarios` (
  `id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `notificado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_notificacion` datetime DEFAULT NULL,
  `plazo_hasta` date DEFAULT NULL,
  `respondido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_respuesta` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `formulario_destinatarios`
--

INSERT INTO `formulario_destinatarios` (`id`, `envio_id`, `empresa_id`, `notificado`, `fecha_notificacion`, `plazo_hasta`, `respondido`, `fecha_respuesta`, `created_at`) VALUES
(1, 1, 100, 1, '2026-05-29 01:16:56', '2026-06-07', 1, '2026-05-29 01:19:54', '2026-05-29 04:16:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formulario_envios`
--

CREATE TABLE `formulario_envios` (
  `id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `tipo_filtro` varchar(40) NOT NULL DEFAULT 'todos',
  `filtros_json` text DEFAULT NULL,
  `total_destinatarios` int(11) NOT NULL DEFAULT 0,
  `fecha_limite` date DEFAULT NULL,
  `enviado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `formulario_envios`
--

INSERT INTO `formulario_envios` (`id`, `formulario_id`, `tipo_filtro`, `filtros_json`, `total_destinatarios`, `fecha_limite`, `enviado_por`, `created_at`) VALUES
(1, 1, 'todos', '{\"rubros\":[],\"ubicaciones\":[],\"estados\":[\"activa\"],\"empresa_ids\":[]}', 1, '2026-06-07', 1, '2026-05-29 04:16:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formulario_preguntas`
--

CREATE TABLE `formulario_preguntas` (
  `id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `tipo` enum('texto','textarea','numero','fecha','select','radio','checkbox','archivo_adjunto','archivo','direccion') NOT NULL DEFAULT 'texto',
  `etiqueta` varchar(255) NOT NULL,
  `ayuda` varchar(255) DEFAULT NULL,
  `requerido` tinyint(1) DEFAULT 0,
  `opciones` longtext DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `min_valor` decimal(15,4) DEFAULT NULL COMMENT 'Solo para tipo=numero',
  `max_valor` decimal(15,4) DEFAULT NULL COMMENT 'Solo para tipo=numero'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `formulario_preguntas`
--

INSERT INTO `formulario_preguntas` (`id`, `formulario_id`, `tipo`, `etiqueta`, `ayuda`, `requerido`, `opciones`, `orden`, `created_at`, `min_valor`, `max_valor`) VALUES
(10, 1, 'texto', 'texto corto', 'prueba', 0, NULL, 1, '2026-05-29 04:11:27', NULL, NULL),
(11, 1, 'textarea', 'parrafo', 'parrafo', 0, NULL, 2, '2026-05-29 04:11:27', NULL, NULL),
(12, 1, 'numero', 'numero', 'numero', 0, NULL, 3, '2026-05-29 04:11:27', 1.0000, 1.0000),
(13, 1, 'fecha', 'fecha', 'fecha', 0, NULL, 4, '2026-05-29 04:11:27', NULL, NULL),
(14, 1, 'select', 'lista desplegable', 'probar comas', 0, '{\"items\":[\"1\",\"2\",\"3\",\"4\",\"5\"]}', 5, '2026-05-29 04:11:27', NULL, NULL),
(15, 1, 'radio', 'opcion unica', '', 0, '{\"items\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]}', 6, '2026-05-29 04:11:27', NULL, NULL),
(16, 1, 'checkbox', 'multiples', '', 0, '{\"items\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]}', 7, '2026-05-29 04:11:27', NULL, NULL),
(17, 1, 'texto', 'tablas', '', 0, '{\"cols\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"],\"rows\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]}', 8, '2026-05-29 04:11:27', NULL, NULL),
(18, 1, 'archivo', 'archivo imagen', '', 0, NULL, 9, '2026-05-29 04:11:27', NULL, NULL),
(19, 1, 'direccion', 'direcccion', '', 0, NULL, 10, '2026-05-29 04:11:27', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formulario_respuestas`
--

CREATE TABLE `formulario_respuestas` (
  `id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `estado` enum('borrador','enviado') NOT NULL DEFAULT 'borrador',
  `respuestas` longtext NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `enviado_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `formulario_respuestas`
--

INSERT INTO `formulario_respuestas` (`id`, `formulario_id`, `empresa_id`, `usuario_id`, `estado`, `respuestas`, `ip`, `enviado_at`, `created_at`, `updated_at`) VALUES
(1, 1, 100, 201, 'borrador', '{\"10\":\"corto\",\"11\":\"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\\r\\naaaaaaaaaaaaaaaaaaaa\\r\\na\\r\\na\\r\\na\\r\\n\\r\\na\\r\\na\\r\\na\\r\\n\\r\\na\\r\\na\\r\\na\\r\\na\\r\\n\\r\\na\\r\\na\\r\\na\\r\\n\\r\\na\\r\\na\\r\\na\\r\\n\\r\\na\\r\\na\\r\\na\\r\\n\\r\\na\\r\\na\\r\\na\\r\\n\\r\\na\",\"12\":\"2\",\"13\":\"2026-05-30\",\"14\":\"2\",\"15\":\"4\",\"16\":[\"3\",\"4\",\"5\",\"6\"],\"17\":\"[[\\\"qwqeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\"],[\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\"],[\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\"],[\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\"],[\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\"],[\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"\\\",\\\"aaaaaaaaa\\\"]]\",\"18\":\"6a1913ea1e68a_1780028394.jpg\",\"19\":\"-28.53500100,-65.81239700\"}', '::1', '2026-05-29 01:19:57', '2026-05-29 04:17:18', '2026-05-29 04:20:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `intentos` int(11) NOT NULL DEFAULT 1,
  `ultimo_intento` datetime NOT NULL DEFAULT current_timestamp(),
  `bloqueado_hasta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_actividad`
--

CREATE TABLE `log_actividad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `log_actividad`
--

INSERT INTO `log_actividad` (`id`, `usuario_id`, `empresa_id`, `accion`, `tabla_afectada`, `registro_id`, `datos_anteriores`, `datos_nuevos`, `ip`, `user_agent`, `created_at`) VALUES
(1, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', NULL, '2025-12-17 11:04:48'),
(2, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', NULL, '2025-12-17 11:07:27'),
(3, 2, NULL, 'login', 'usuarios', 2, NULL, NULL, '::1', NULL, '2025-12-17 11:07:52'),
(4, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 20:40:21'),
(5, 1, NULL, 'formulario_dinamico_creado', 'formularios_dinamicos', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 20:41:35'),
(6, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:06:21'),
(7, 1, NULL, 'empresa_suspender', 'empresas', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:06:29'),
(8, 1, NULL, 'formulario_dinamico_archivado', 'formularios_dinamicos', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:07:04'),
(9, 1, NULL, 'formulario_dinamico_borrador', 'formularios_dinamicos', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:07:09'),
(10, 1, NULL, 'formulario_dinamico_editado', 'formularios_dinamicos', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:10:28'),
(11, 1, NULL, 'formulario_dinamico_editado', 'formularios_dinamicos', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:11:27'),
(12, 1, NULL, 'registro', 'usuarios', 201, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:14:10'),
(13, 1, NULL, 'empresa_registrada', 'empresas', 100, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:14:10'),
(14, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:14:28'),
(15, 201, 100, 'login', 'usuarios', 201, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:14:44'),
(16, 201, 100, 'logout', 'usuarios', 201, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:15:48'),
(17, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:15:53'),
(18, 1, NULL, 'formulario_dinamico_publicado', 'formularios_dinamicos', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:16:15'),
(19, 1, NULL, 'empresa_activar', 'empresas', 100, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:16:34'),
(20, 1, NULL, 'formulario_envio_masivo', 'formulario_envios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:16:56'),
(21, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:17:03'),
(22, 201, 100, 'login', 'usuarios', 201, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:17:06'),
(23, 201, 100, 'formulario_dinamico_borrador', 'formulario_respuestas', 100, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:17:18'),
(24, 201, 100, 'formulario_dinamico_enviado', 'formulario_respuestas', 100, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:19:54'),
(25, 201, 100, 'formulario_dinamico_enviado', 'formulario_respuestas', 100, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:19:57'),
(26, 201, 100, 'formulario_dinamico_borrador', 'formulario_respuestas', 100, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:20:52'),
(27, 201, 100, 'logout', 'usuarios', 201, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:21:41'),
(28, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 04:21:44'),
(29, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 05:22:10'),
(30, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 05:30:57'),
(31, 1, NULL, 'formulario_dinamico_creado', 'formularios_dinamicos', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 05:31:15'),
(32, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 19:39:41'),
(33, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-30 20:39:43'),
(34, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 20:45:45'),
(35, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.11187.1 Chrome/146.0.7680.216 Electron/41.6.1 Safari/537.36 MSIX', '2026-06-05 20:11:44'),
(36, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.11187.1 Chrome/146.0.7680.216 Electron/41.6.1 Safari/537.36 MSIX', '2026-06-05 20:37:19'),
(37, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.11187.1 Chrome/146.0.7680.216 Electron/41.6.1 Safari/537.36 MSIX', '2026-06-05 20:44:46'),
(38, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.11187.1 Chrome/146.0.7680.216 Electron/41.6.1 Safari/537.36 MSIX', '2026-06-05 20:55:49'),
(39, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.11187.1 Chrome/146.0.7680.216 Electron/41.6.1 Safari/537.36 MSIX', '2026-06-05 20:56:05'),
(40, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":null,\"cuit\":null,\"rubro\":\"Textil\",\"descripcion\":null,\"ubicacion\":\"PI El Pantanillo\",\"direccion\":null,\"latitud\":null,\"longitud\":null,\"telefono\":\"3834123456\",\"email_contacto\":null,\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":null,\"facebook\":null,\"instagram\":null,\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-05-29 01:06:29\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.11187.1 Chrome/146.0.7680.216 Electron/41.6.1 Safari/537.36 MSIX', '2026-06-05 21:08:05'),
(41, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 18:56:13'),
(42, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 19:07:35'),
(43, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:12:57'),
(44, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:15:24'),
(45, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:15:28'),
(46, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:20:27'),
(47, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:20:44'),
(48, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:21:45'),
(49, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:22:00'),
(50, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:24:26'),
(51, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:24:44'),
(52, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:27:12'),
(53, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:27:18'),
(54, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:27:36'),
(55, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:28:05'),
(56, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:29:11'),
(57, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 20:33:04'),
(58, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-06 21:33:43'),
(59, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 18:27:47'),
(60, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 19:29:48'),
(61, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 20:30:44'),
(62, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 20:35:13'),
(63, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:40:47'),
(64, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":\"\",\"cuit\":\"\",\"rubro\":\"Textil\",\"descripcion\":\"Empresa de manufactura especializada en productos industriales para la regi\\u00f3n.\",\"ubicacion\":\"PI El Pantanillo\",\"direccion\":\"\",\"latitud\":null,\"longitud\":null,\"telefono\":\"3834123456\",\"email_contacto\":\"\",\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":\"\",\"facebook\":\"\",\"instagram\":\"\",\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-06-05 18:08:05\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:40:49'),
(65, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:40:51'),
(66, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:40:52'),
(67, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:41:21'),
(68, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:41:22'),
(69, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:43:11'),
(70, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":\"\",\"cuit\":\"\",\"rubro\":\"Textil\",\"descripcion\":\"Empresa de manufactura especializada en productos industriales para la regi\\u00f3n.\",\"ubicacion\":\"PI El Pantanillo\",\"direccion\":\"\",\"latitud\":null,\"longitud\":null,\"telefono\":\"3834000001\",\"email_contacto\":\"\",\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":\"\",\"facebook\":\"\",\"instagram\":\"\",\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-06-10 17:40:49\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:43:13'),
(71, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:43:14'),
(72, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:43:15'),
(73, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:43:21'),
(74, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 20:43:21'),
(75, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:14:49'),
(76, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":\"\",\"cuit\":\"\",\"rubro\":\"Textil\",\"descripcion\":\"Empresa de manufactura especializada en productos industriales para la regi\\u00f3n.\",\"ubicacion\":\"PI El Pantanillo\",\"direccion\":\"\",\"latitud\":null,\"longitud\":null,\"telefono\":\"3834000001\",\"email_contacto\":\"\",\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":\"\",\"facebook\":\"\",\"instagram\":\"\",\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-06-10 17:40:49\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:14:51'),
(77, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:14:53'),
(78, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:14:53'),
(79, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:01'),
(80, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:01'),
(81, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:14'),
(82, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":\"\",\"cuit\":\"\",\"rubro\":\"Textil\",\"descripcion\":\"Empresa de manufactura especializada en productos industriales para la regi\\u00f3n.\",\"ubicacion\":\"PI El Pantanillo\",\"direccion\":\"\",\"latitud\":null,\"longitud\":null,\"telefono\":\"3834000001\",\"email_contacto\":\"\",\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":\"\",\"facebook\":\"\",\"instagram\":\"\",\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-06-10 17:40:49\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:16'),
(83, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:17'),
(84, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:18'),
(85, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:23'),
(86, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:15:24'),
(87, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:16:02'),
(88, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:17:11'),
(89, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:17:43'),
(90, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:18:27'),
(91, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":\"\",\"cuit\":\"\",\"rubro\":\"Textil\",\"descripcion\":\"Empresa de manufactura especializada en productos industriales para la regi\\u00f3n.\",\"ubicacion\":\"PI El Pantanillo\",\"direccion\":\"\",\"latitud\":null,\"longitud\":null,\"telefono\":\"3834000001\",\"email_contacto\":\"\",\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":\"\",\"facebook\":\"\",\"instagram\":\"\",\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-06-10 17:40:49\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:18:29'),
(92, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:18:31'),
(93, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:18:31'),
(94, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:18:36'),
(95, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:18:37'),
(96, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:21:39'),
(97, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:22:58'),
(98, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:23:37'),
(99, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:27:04'),
(100, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:27:07'),
(101, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:27:08'),
(102, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:28:44'),
(103, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:30:03'),
(104, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:30:32'),
(105, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":\"\",\"cuit\":\"\",\"rubro\":\"Textil\",\"descripcion\":\"Empresa de manufactura especializada en productos industriales para la regi\\u00f3n.\",\"ubicacion\":\"PI El Pantanillo\",\"direccion\":\"\",\"latitud\":null,\"longitud\":null,\"telefono\":\"3834000001\",\"email_contacto\":\"\",\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":\"\",\"facebook\":\"\",\"instagram\":\"\",\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-06-10 17:40:49\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:30:34'),
(106, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:30:35'),
(107, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:30:36'),
(108, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:30:42'),
(109, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:30:43'),
(110, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 21:35:19'),
(111, 3, 1, 'login', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:36:47'),
(112, 3, 1, 'perfil_actualizado', 'empresas', 1, '{\"id\":1,\"usuario_id\":3,\"nombre\":\"Empresa Demo S.R.L.\",\"razon_social\":\"\",\"cuit\":\"\",\"rubro\":\"Textil\",\"descripcion\":\"Empresa de manufactura especializada en productos industriales para la regi\\u00f3n.\",\"ubicacion\":\"PI El Pantanillo\",\"direccion\":\"\",\"latitud\":null,\"longitud\":null,\"telefono\":\"3834000001\",\"email_contacto\":\"\",\"contacto_nombre\":\"Juan P??rez\",\"sitio_web\":\"\",\"facebook\":\"\",\"instagram\":\"\",\"linkedin\":null,\"logo\":null,\"imagen_portada\":null,\"estado\":\"suspendida\",\"perfil_completo\":0,\"verificada\":0,\"visitas\":0,\"created_at\":\"2026-05-28 17:32:12\",\"updated_at\":\"2026-06-10 17:40:49\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:36:49'),
(113, 3, 1, 'logout', 'usuarios', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:36:50'),
(114, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:36:51'),
(115, 1, NULL, 'logout', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:36:56'),
(116, 1, NULL, 'login', 'usuarios', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', '2026-06-10 21:36:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lotes`
--

CREATE TABLE `lotes` (
  `id` int(11) NOT NULL,
  `numero_lote` varchar(50) NOT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `superficie_m2` decimal(10,2) DEFAULT NULL,
  `estado` enum('disponible','ocupado','reservado') DEFAULT 'disponible',
  `geometria_terreno` longtext DEFAULT NULL COMMENT 'GeoJSON Polygon serializado',
  `empresa_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `lotes`
--

INSERT INTO `lotes` (`id`, `numero_lote`, `sector`, `superficie_m2`, `estado`, `geometria_terreno`, `empresa_id`, `created_at`, `updated_at`) VALUES
(1, 'a3', 'norte, zona A', 5938.00, 'ocupado', '{\"type\":\"Polygon\",\"coordinates\":[[[-65.79995691776277,-28.53102352748398],[-65.7992058992386,-28.530495675123433],[-65.79833686351778,-28.531457118516656],[-65.79916298389436,-28.53197554023588],[-65.79995691776277,-28.53102352748398]]]}', 100, '2026-06-10 20:36:36', '2026-06-10 20:39:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_v2`
--

CREATE TABLE `mensajes_v2` (
  `id` int(11) NOT NULL,
  `conversacion_id` int(11) NOT NULL,
  `remitente_id` int(11) DEFAULT NULL,
  `remitente_tipo` enum('empresa','ministerio','sistema') NOT NULL,
  `contenido` mediumtext NOT NULL,
  `es_borrador` tinyint(1) NOT NULL DEFAULT 0,
  `leido_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mensajes_v2`
--

INSERT INTO `mensajes_v2` (`id`, `conversacion_id`, `remitente_id`, `remitente_tipo`, `contenido`, `es_borrador`, `leido_at`, `created_at`) VALUES
(1, 1, 1, 'ministerio', 'El Ministerio le asignó un nuevo formulario para completar. Use el botón \"Completar formulario\" para acceder.\n\npara prueba', 0, '2026-05-29 01:17:09', '2026-05-29 04:16:56'),
(2, 1, 201, 'empresa', 'listo', 0, '2026-05-29 01:21:52', '2026-05-29 04:21:30'),
(3, 1, 201, 'empresa', '', 1, NULL, '2026-05-29 04:21:30'),
(4, 2, 3, 'empresa', 'Quisiera saber qué documentación necesito para habilitar un nuevo galpón. Muchas gracias.', 0, '2026-06-06 15:56:47', '2026-06-05 20:59:47'),
(5, 2, 1, 'ministerio', 'ok', 1, NULL, '2026-06-06 18:56:54'),
(6, 2, 1, 'ministerio', 'ok', 0, NULL, '2026-06-06 18:56:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'perfil_editado, formulario_enviado, etc',
  `titulo` varchar(255) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
  `leida` tinyint(1) DEFAULT 0,
  `fecha_lectura` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `tipo`, `titulo`, `mensaje`, `url`, `datos`, `leida`, `fecha_lectura`, `created_at`) VALUES
(1, 201, 'formulario_nuevo', 'Nuevo formulario: prueba', 'Debe completar el formulario asignado por el ministerio.', 'http://localhost:8080/empresa/formulario_dinamico.php?id=1', NULL, 0, NULL, '2026-05-29 04:16:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plantillas_respuesta`
--

CREATE TABLE `plantillas_respuesta` (
  `id` int(11) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `contenido` text NOT NULL,
  `categoria` enum('tramite','consulta','reclamo','comunicado','formulario','sistema','general') NOT NULL DEFAULT 'general',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `plantillas_respuesta`
--

INSERT INTO `plantillas_respuesta` (`id`, `titulo`, `contenido`, `categoria`, `orden`, `activa`, `created_at`, `updated_at`) VALUES
(1, 'Recibimos su consulta', 'Estimado/a,\r\n\r\nHemos recibido su consulta y ser?? derivada al ??rea correspondiente.\r\n\r\nLe responderemos a la brevedad.\r\n\r\nSaludos cordiales,\r\nMinisterio de Industria', 'consulta', 1, 1, '2026-05-28 20:32:13', '2026-05-30 20:53:22'),
(2, 'Documentaci??n requerida', 'Estimado/a,\n\nPara continuar con su tr??mite necesitamos que adjunte la siguiente documentaci??n:\n\n- \n- \n\nQuedamos a disposici??n.\n\nSaludos cordiales,\nMinisterio de Industria', 'tramite', 2, 1, '2026-05-28 20:32:13', '2026-05-28 20:32:13'),
(3, 'Tr??mite aprobado', 'Estimado/a,\n\nNos complace informarle que su tr??mite ha sido aprobado.\n\nSaludos cordiales,\nMinisterio de Industria', 'tramite', 3, 1, '2026-05-28 20:32:13', '2026-05-28 20:32:13'),
(4, 'Formulario con observaciones', 'Estimado/a,\n\nHemos revisado su declaraci??n de datos y encontramos las siguientes observaciones:\n\n- \n\nPor favor corrija y reenv??e.\n\nSaludos cordiales,\nMinisterio de Industria', 'formulario', 4, 1, '2026-05-28 20:32:13', '2026-05-28 20:32:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones`
--

CREATE TABLE `publicaciones` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicacion_likes`
--

CREATE TABLE `publicacion_likes` (
  `id` int(11) NOT NULL,
  `publicacion_id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas_formulario`
--

CREATE TABLE `respuestas_formulario` (
  `id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `respuestas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`respuestas`)),
  `estado` enum('borrador','enviado','aprobado','rechazado') DEFAULT 'borrador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rubros`
--

CREATE TABLE `rubros` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL COMMENT 'Color hex para gr??ficos',
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rubros`
--

INSERT INTO `rubros` (`id`, `nombre`, `descripcion`, `icono`, `color`, `activo`, `orden`, `created_at`) VALUES
(1, 'Textil', NULL, NULL, '#3498db', 1, 1, '2026-05-28 20:32:12'),
(2, 'Construcci??n', NULL, NULL, '#e74c3c', 1, 2, '2026-05-28 20:32:12'),
(3, 'Metal??rgica', NULL, NULL, '#95a5a6', 1, 3, '2026-05-28 20:32:12'),
(4, 'Alimentos', NULL, NULL, '#27ae60', 1, 4, '2026-05-28 20:32:12'),
(5, 'Transporte', NULL, NULL, '#f39c12', 1, 5, '2026-05-28 20:32:12'),
(6, 'Reciclado', NULL, NULL, '#2ecc71', 1, 6, '2026-05-28 20:32:12'),
(7, 'Hormig??n', NULL, NULL, '#7f8c8d', 1, 7, '2026-05-28 20:32:12'),
(8, 'Electrodom??sticos', NULL, NULL, '#9b59b6', 1, 8, '2026-05-28 20:32:12'),
(9, 'Medicamentos', NULL, NULL, '#1abc9c', 1, 9, '2026-05-28 20:32:12'),
(10, 'Calzados', NULL, NULL, '#e67e22', 1, 10, '2026-05-28 20:32:12'),
(11, 'Fibra de Vidrio', NULL, NULL, '#34495e', 1, 11, '2026-05-28 20:32:12'),
(12, 'Combustibles', NULL, NULL, '#c0392b', 1, 12, '2026-05-28 20:32:12'),
(13, 'Miner??a', NULL, NULL, '#8e44ad', 1, 13, '2026-05-28 20:32:12'),
(14, 'Qu??mica', NULL, NULL, '#16a085', 1, 14, '2026-05-28 20:32:12'),
(15, 'Maquinaria Industrial', NULL, NULL, '#2c3e50', 1, 15, '2026-05-28 20:32:12'),
(16, 'Autopartes', NULL, NULL, '#d35400', 1, 16, '2026-05-28 20:32:12'),
(17, 'Frigor??fico', NULL, NULL, '#2980b9', 1, 17, '2026-05-28 20:32:12'),
(18, 'L??cteos', NULL, NULL, '#f1c40f', 1, 18, '2026-05-28 20:32:12'),
(19, 'Otros', NULL, NULL, '#bdc3c7', 1, 99, '2026-05-28 20:32:12'),
(20, 'Pl??sticos', NULL, NULL, '#6c757d', 1, 20, '2026-05-28 20:32:12'),
(21, 'Agroindustria', NULL, NULL, '#28a745', 1, 21, '2026-05-28 20:32:12'),
(22, 'Motocicletas', NULL, NULL, '#fd7e14', 1, 22, '2026-05-28 20:32:12'),
(23, 'Dulces', NULL, NULL, '#e83e8c', 1, 23, '2026-05-28 20:32:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_proyecto`
--

CREATE TABLE `solicitudes_proyecto` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `latitud_centro` decimal(10,8) DEFAULT NULL,
  `longitud_centro` decimal(11,8) DEFAULT NULL,
  `poligono_geojson` text DEFAULT NULL COMMENT 'GeoJSON del pol??gono del ??rea',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ubicaciones`
--

INSERT INTO `ubicaciones` (`id`, `nombre`, `descripcion`, `latitud_centro`, `longitud_centro`, `poligono_geojson`, `activo`, `created_at`) VALUES
(1, 'PI El Pantanillo', NULL, -28.46960000, -65.77950000, NULL, 1, '2026-05-28 20:32:12'),
(2, 'Capital', NULL, -28.46960000, -65.78520000, NULL, 1, '2026-05-28 20:32:12'),
(3, 'Valle Viejo', NULL, -28.39170000, -65.70950000, NULL, 1, '2026-05-28 20:32:12'),
(4, 'Recreo', NULL, -29.28330000, -65.06670000, NULL, 1, '2026-05-28 20:32:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `password`, `rol`, `activo`, `ultimo_acceso`, `token_recuperacion`, `token_expira`, `token_activacion`, `token_activacion_expira`, `email_verificado`, `created_at`, `updated_at`) VALUES
(1, 'admin@parqueindustrial.gob.ar', '$2y$10$xHWydNT4i4lVvD.OjuYOH.TjIJRZcoAS2nOO2vK1t7EBvHu4Dgo5C', 'admin', 1, '2026-06-10 18:36:57', NULL, NULL, NULL, NULL, 1, '2026-05-28 20:32:12', '2026-06-10 21:36:57'),
(2, 'ministerio@catamarca.gob.ar', '$2y$10$xHWydNT4i4lVvD.OjuYOH.TjIJRZcoAS2nOO2vK1t7EBvHu4Dgo5C', 'ministerio', 1, NULL, NULL, NULL, NULL, NULL, 1, '2026-05-28 20:32:12', '2026-05-28 20:32:12'),
(3, 'empresa@demo.com', '$2y$10$xHWydNT4i4lVvD.OjuYOH.TjIJRZcoAS2nOO2vK1t7EBvHu4Dgo5C', 'empresa', 1, '2026-06-10 18:36:47', NULL, NULL, NULL, NULL, 1, '2026-05-28 20:32:12', '2026-06-10 21:36:47'),
(201, 'prueba@gmail.com', '$2y$10$EUM.QG71sKF3oHOzKKSL8.EeGQkiGXqgDZTBBC/OfHQihfr1M2fri', 'empresa', 1, '2026-05-29 01:17:06', NULL, NULL, NULL, NULL, 1, '2026-05-29 04:14:10', '2026-05-29 04:17:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitas_empresa`
--

CREATE TABLE `visitas_empresa` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `visitas_empresa`
--

INSERT INTO `visitas_empresa` (`id`, `empresa_id`, `ip`, `user_agent`, `referer`, `created_at`) VALUES
(4, 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', NULL, '2026-06-10 20:40:24'),
(5, 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', NULL, '2026-06-10 20:43:06'),
(6, 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', NULL, '2026-06-10 21:14:41'),
(7, 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', NULL, '2026-06-10 21:15:09'),
(8, 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', NULL, '2026-06-10 21:18:22'),
(9, 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', NULL, '2026-06-10 21:30:26'),
(10, 100, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36', NULL, '2026-06-10 21:36:42');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_empresas_completas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_empresas_completas` (
`id` int(11)
,`usuario_id` int(11)
,`nombre` varchar(255)
,`razon_social` varchar(255)
,`cuit` varchar(20)
,`rubro` varchar(100)
,`descripcion` text
,`ubicacion` varchar(100)
,`direccion` varchar(255)
,`latitud` decimal(10,8)
,`longitud` decimal(11,8)
,`telefono` varchar(50)
,`email_contacto` varchar(255)
,`contacto_nombre` varchar(255)
,`sitio_web` varchar(255)
,`facebook` varchar(255)
,`instagram` varchar(255)
,`linkedin` varchar(255)
,`logo` varchar(255)
,`imagen_portada` varchar(255)
,`estado` enum('pendiente','activa','suspendida','inactiva')
,`perfil_completo` tinyint(1)
,`verificada` tinyint(1)
,`visitas` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`dotacion_total` int(11)
,`empleados_masculinos` int(11)
,`empleados_femeninos` int(11)
,`consumo_energia` decimal(12,2)
,`consumo_agua` decimal(12,2)
,`exporta` tinyint(1)
,`importa` tinyint(1)
,`emisiones_co2` decimal(12,4)
,`ultimo_periodo` varchar(20)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_estadisticas_generales`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_estadisticas_generales` (
`total_empresas_activas` bigint(21)
,`total_empresas` bigint(21)
,`total_empleados` decimal(32,0)
,`total_rubros` bigint(21)
,`total_publicaciones` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_empresas_completas`
--
DROP TABLE IF EXISTS `v_empresas_completas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_empresas_completas`  AS SELECT `e`.`id` AS `id`, `e`.`usuario_id` AS `usuario_id`, `e`.`nombre` AS `nombre`, `e`.`razon_social` AS `razon_social`, `e`.`cuit` AS `cuit`, `e`.`rubro` AS `rubro`, `e`.`descripcion` AS `descripcion`, `e`.`ubicacion` AS `ubicacion`, `e`.`direccion` AS `direccion`, `e`.`latitud` AS `latitud`, `e`.`longitud` AS `longitud`, `e`.`telefono` AS `telefono`, `e`.`email_contacto` AS `email_contacto`, `e`.`contacto_nombre` AS `contacto_nombre`, `e`.`sitio_web` AS `sitio_web`, `e`.`facebook` AS `facebook`, `e`.`instagram` AS `instagram`, `e`.`linkedin` AS `linkedin`, `e`.`logo` AS `logo`, `e`.`imagen_portada` AS `imagen_portada`, `e`.`estado` AS `estado`, `e`.`perfil_completo` AS `perfil_completo`, `e`.`verificada` AS `verificada`, `e`.`visitas` AS `visitas`, `e`.`created_at` AS `created_at`, `e`.`updated_at` AS `updated_at`, `de`.`dotacion_total` AS `dotacion_total`, `de`.`empleados_masculinos` AS `empleados_masculinos`, `de`.`empleados_femeninos` AS `empleados_femeninos`, `de`.`consumo_energia` AS `consumo_energia`, `de`.`consumo_agua` AS `consumo_agua`, `de`.`exporta` AS `exporta`, `de`.`importa` AS `importa`, `de`.`emisiones_co2` AS `emisiones_co2`, `de`.`periodo` AS `ultimo_periodo` FROM (`empresas` `e` left join `datos_empresa` `de` on(`e`.`id` = `de`.`empresa_id` and `de`.`periodo` = (select max(`datos_empresa`.`periodo`) from `datos_empresa` where `datos_empresa`.`empresa_id` = `e`.`id`))) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_estadisticas_generales`
--
DROP TABLE IF EXISTS `v_estadisticas_generales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_estadisticas_generales`  AS SELECT (select count(0) from `empresas` where `empresas`.`estado` = 'activa') AS `total_empresas_activas`, (select count(0) from `empresas`) AS `total_empresas`, (select coalesce(sum(`de`.`dotacion_total`),0) from (`datos_empresa` `de` join `empresas` `e` on(`de`.`empresa_id` = `e`.`id`)) where `e`.`estado` = 'activa' and `de`.`periodo` = (select max(`datos_empresa`.`periodo`) from `datos_empresa` where `datos_empresa`.`empresa_id` = `de`.`empresa_id`)) AS `total_empleados`, (select count(distinct `empresas`.`rubro`) from `empresas` where `empresas`.`estado` = 'activa') AS `total_rubros`, (select count(0) from `publicaciones` where `publicaciones`.`publicado` = 1 and `publicaciones`.`estado` = 'aprobado') AS `total_publicaciones` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `adjuntos_mensajes`
--
ALTER TABLE `adjuntos_mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mensaje` (`mensaje_id`);

--
-- Indices de la tabla `archivos_publicacion`
--
ALTER TABLE `archivos_publicacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publicacion_id` (`publicacion_id`);

--
-- Indices de la tabla `banners_home`
--
ALTER TABLE `banners_home`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comunicado_visto`
--
ALTER TABLE `comunicado_visto`
  ADD PRIMARY KEY (`conversacion_id`,`empresa_id`),
  ADD KEY `idx_empresa_leido` (`empresa_id`);

--
-- Indices de la tabla `configuracion_sitio`
--
ALTER TABLE `configuracion_sitio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_conv_referencia` (`referencia_tipo`,`referencia_id`),
  ADD KEY `idx_empresa_estado` (`empresa_id`,`estado`),
  ADD KEY `idx_estado_ultimo` (`estado`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Indices de la tabla `datos_empresa`
--
ALTER TABLE `datos_empresa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_empresa_periodo` (`empresa_id`,`periodo`),
  ADD KEY `revisado_por` (`revisado_por`),
  ADD KEY `idx_periodo` (`periodo`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_rubro` (`rubro`),
  ADD KEY `idx_ubicacion` (`ubicacion`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_visitas` (`visitas`);
ALTER TABLE `empresas` ADD FULLTEXT KEY `idx_busqueda` (`nombre`,`razon_social`,`descripcion`,`rubro`);

--
-- Indices de la tabla `empresa_imagenes`
--
ALTER TABLE `empresa_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `formularios_config`
--
ALTER TABLE `formularios_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `formularios_dinamicos`
--
ALTER TABLE `formularios_dinamicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fd_estado` (`estado`),
  ADD KEY `idx_fd_creado_por` (`creado_por`);

--
-- Indices de la tabla `formulario_destinatarios`
--
ALTER TABLE `formulario_destinatarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_dest_envio_empresa` (`envio_id`,`empresa_id`),
  ADD KEY `idx_fd_empresa` (`empresa_id`),
  ADD KEY `idx_fd_respondido` (`respondido`);

--
-- Indices de la tabla `formulario_envios`
--
ALTER TABLE `formulario_envios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fe_formulario` (`formulario_id`),
  ADD KEY `idx_fe_enviado_por` (`enviado_por`);

--
-- Indices de la tabla `formulario_preguntas`
--
ALTER TABLE `formulario_preguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_formulario` (`formulario_id`);

--
-- Indices de la tabla `formulario_respuestas`
--
ALTER TABLE `formulario_respuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fr_formulario_empresa` (`formulario_id`,`empresa_id`),
  ADD KEY `idx_fr_estado` (`estado`);

--
-- Indices de la tabla `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`ip`),
  ADD KEY `idx_bloqueado` (`bloqueado_hasta`);

--
-- Indices de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `idx_fecha` (`created_at`);

--
-- Indices de la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_numero_lote` (`numero_lote`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_empresa` (`empresa_id`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `remitente_id` (`remitente_id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `mensaje_padre_id` (`mensaje_padre_id`),
  ADD KEY `idx_destinatario` (`destinatario_id`),
  ADD KEY `idx_leido` (`leido`);

--
-- Indices de la tabla `mensajes_v2`
--
ALTER TABLE `mensajes_v2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conv_created` (`conversacion_id`,`created_at`),
  ADD KEY `idx_conv_no_leidos` (`conversacion_id`),
  ADD KEY `idx_remitente` (`remitente_id`,`es_borrador`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_leida` (`usuario_id`,`leida`);

--
-- Indices de la tabla `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_fecha` (`ip`,`created_at`);

--
-- Indices de la tabla `plantillas_respuesta`
--
ALTER TABLE `plantillas_respuesta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activa_orden` (`activa`,`orden`,`titulo`);

--
-- Indices de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `aprobado_por` (`aprobado_por`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_publicado` (`publicado`),
  ADD KEY `idx_fecha` (`fecha_publicacion`);

--
-- Indices de la tabla `publicacion_likes`
--
ALTER TABLE `publicacion_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_like_ip` (`publicacion_id`,`ip`);

--
-- Indices de la tabla `respuestas_formulario`
--
ALTER TABLE `respuestas_formulario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_form_empresa` (`formulario_id`,`empresa_id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indices de la tabla `rubros`
--
ALTER TABLE `rubros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `solicitudes_proyecto`
--
ALTER TABLE `solicitudes_proyecto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_rol` (`rol`);

--
-- Indices de la tabla `visitas_empresa`
--
ALTER TABLE `visitas_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa_fecha` (`empresa_id`,`created_at`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `adjuntos_mensajes`
--
ALTER TABLE `adjuntos_mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `archivos_publicacion`
--
ALTER TABLE `archivos_publicacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `banners_home`
--
ALTER TABLE `banners_home`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_sitio`
--
ALTER TABLE `configuracion_sitio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `datos_empresa`
--
ALTER TABLE `datos_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT de la tabla `empresa_imagenes`
--
ALTER TABLE `empresa_imagenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `formularios_config`
--
ALTER TABLE `formularios_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `formularios_dinamicos`
--
ALTER TABLE `formularios_dinamicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `formulario_destinatarios`
--
ALTER TABLE `formulario_destinatarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `formulario_envios`
--
ALTER TABLE `formulario_envios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `formulario_preguntas`
--
ALTER TABLE `formulario_preguntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `formulario_respuestas`
--
ALTER TABLE `formulario_respuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `log_actividad`
--
ALTER TABLE `log_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT de la tabla `lotes`
--
ALTER TABLE `lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_v2`
--
ALTER TABLE `mensajes_v2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plantillas_respuesta`
--
ALTER TABLE `plantillas_respuesta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `publicacion_likes`
--
ALTER TABLE `publicacion_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `respuestas_formulario`
--
ALTER TABLE `respuestas_formulario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rubros`
--
ALTER TABLE `rubros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `solicitudes_proyecto`
--
ALTER TABLE `solicitudes_proyecto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT de la tabla `visitas_empresa`
--
ALTER TABLE `visitas_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `adjuntos_mensajes`
--
ALTER TABLE `adjuntos_mensajes`
  ADD CONSTRAINT `fk_adj_mensaje` FOREIGN KEY (`mensaje_id`) REFERENCES `mensajes_v2` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `archivos_publicacion`
--
ALTER TABLE `archivos_publicacion`
  ADD CONSTRAINT `archivos_publicacion_ibfk_1` FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `datos_empresa`
--
ALTER TABLE `datos_empresa`
  ADD CONSTRAINT `datos_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `datos_empresa_ibfk_2` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD CONSTRAINT `empresas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `formulario_preguntas`
--
ALTER TABLE `formulario_preguntas`
  ADD CONSTRAINT `formulario_preguntas_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios_dinamicos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD CONSTRAINT `lotes_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mensajes_ibfk_3` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mensajes_ibfk_4` FOREIGN KEY (`mensaje_padre_id`) REFERENCES `mensajes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD CONSTRAINT `publicaciones_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `publicaciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `publicaciones_ibfk_3` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `respuestas_formulario`
--
ALTER TABLE `respuestas_formulario`
  ADD CONSTRAINT `respuestas_formulario_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios_config` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `respuestas_formulario_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `visitas_empresa`
--
ALTER TABLE `visitas_empresa`
  ADD CONSTRAINT `visitas_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;
-- ============================================================
-- MIGRACIÓN: columnas de solicitud de lote en empresas
-- Ejecutar una sola vez en bases de datos existentes:
-- ============================================================
ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `lote_declarado` varchar(50) DEFAULT NULL COMMENT 'Número de lote declarado por la empresa',
  ADD COLUMN IF NOT EXISTS `lote_solicitud_estado` enum('sin_solicitud','pendiente','asignado') NOT NULL DEFAULT 'sin_solicitud';

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
