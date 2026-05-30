-- ============================================================
-- parque_industrial_v2.sql
-- Base de datos unificada — Parque Industrial de Catamarca
-- ============================================================
-- Un único archivo, importable desde phpMyAdmin o CLI en un paso.
-- Incluye schema completo, todas las migraciones 015-018 integradas,
-- seed data limpio (sin duplicados, contraseñas admin123).
--
-- Uso:
--   mysql -u root -p < database/parque_industrial_v2.sql
--   o importar desde phpMyAdmin seleccionando este archivo.
--
-- Contraseñas demo (todas usan "admin123"):
--   admin@parqueindustrial.gob.ar  →  admin
--   ministerio@catamarca.gob.ar    →  ministerio
--   empresa@demo.com               →  empresa demo (empresa_id=1)
--   empresa1@parqueindustrial.com  →  empresa id 22 (ABC Construcciones)
--   (y así para todos los cuits@parqueindustrial.com)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- BASE DE DATOS
-- ============================================================
CREATE DATABASE IF NOT EXISTS `parque_industrial`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `parque_industrial`;

-- ============================================================
-- LIMPIAR TABLAS EXISTENTES (orden inverso a FKs)
-- ============================================================
DROP TABLE IF EXISTS `comunicado_visto`;
DROP TABLE IF EXISTS `adjuntos_mensajes`;
DROP TABLE IF EXISTS `mensajes_v2`;
DROP TABLE IF EXISTS `conversaciones`;
DROP TABLE IF EXISTS `plantillas_respuesta`;
DROP TABLE IF EXISTS `formulario_respuestas`;
DROP TABLE IF EXISTS `formulario_preguntas`;
DROP TABLE IF EXISTS `formularios_dinamicos`;
DROP TABLE IF EXISTS `respuestas_formulario`;
DROP TABLE IF EXISTS `archivos_publicacion`;
DROP TABLE IF EXISTS `publicaciones`;
DROP TABLE IF EXISTS `notificaciones`;
DROP TABLE IF EXISTS `mensajes`;
DROP TABLE IF EXISTS `log_actividad`;
DROP TABLE IF EXISTS `visitas_empresa`;
DROP TABLE IF EXISTS `datos_empresa`;
DROP TABLE IF EXISTS `empresas`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `banners_home`;
DROP TABLE IF EXISTS `formularios_config`;
DROP TABLE IF EXISTS `configuracion_sitio`;
DROP TABLE IF EXISTS `ubicaciones`;
DROP TABLE IF EXISTS `rubros`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `password_reset_requests`;
DROP VIEW  IF EXISTS `v_empresas_completas`;
DROP VIEW  IF EXISTS `v_estadisticas_generales`;

-- ============================================================
-- TABLAS SIN DEPENDENCIAS EXTERNAS
-- ============================================================

CREATE TABLE `rubros` (
    `id`          int(11)      NOT NULL,
    `nombre`      varchar(100) NOT NULL,
    `descripcion` text         DEFAULT NULL,
    `icono`       varchar(50)  DEFAULT NULL,
    `color`       varchar(7)   DEFAULT NULL COMMENT 'Color hex para gráficos',
    `activo`      tinyint(1)   DEFAULT 1,
    `orden`       int(11)      DEFAULT 0,
    `created_at`  timestamp    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rubros` (`id`, `nombre`, `descripcion`, `icono`, `color`, `activo`, `orden`) VALUES
(1,  'Textil',               NULL, NULL, '#3498db', 1,  1),
(2,  'Construcción',         NULL, NULL, '#e74c3c', 1,  2),
(3,  'Metalúrgica',          NULL, NULL, '#95a5a6', 1,  3),
(4,  'Alimentos',            NULL, NULL, '#27ae60', 1,  4),
(5,  'Transporte',           NULL, NULL, '#f39c12', 1,  5),
(6,  'Reciclado',            NULL, NULL, '#2ecc71', 1,  6),
(7,  'Hormigón',             NULL, NULL, '#7f8c8d', 1,  7),
(8,  'Electrodomésticos',    NULL, NULL, '#9b59b6', 1,  8),
(9,  'Medicamentos',         NULL, NULL, '#1abc9c', 1,  9),
(10, 'Calzados',             NULL, NULL, '#e67e22', 1, 10),
(11, 'Fibra de Vidrio',      NULL, NULL, '#34495e', 1, 11),
(12, 'Combustibles',         NULL, NULL, '#c0392b', 1, 12),
(13, 'Minería',              NULL, NULL, '#8e44ad', 1, 13),
(14, 'Química',              NULL, NULL, '#16a085', 1, 14),
(15, 'Maquinaria Industrial',NULL, NULL, '#2c3e50', 1, 15),
(16, 'Autopartes',           NULL, NULL, '#d35400', 1, 16),
(17, 'Frigorífico',          NULL, NULL, '#2980b9', 1, 17),
(18, 'Lácteos',              NULL, NULL, '#f1c40f', 1, 18),
(19, 'Otros',                NULL, NULL, '#bdc3c7', 1, 99),
(20, 'Plásticos',            NULL, NULL, '#6c757d', 1, 20),
(21, 'Agroindustria',        NULL, NULL, '#28a745', 1, 21),
(22, 'Motocicletas',         NULL, NULL, '#fd7e14', 1, 22),
(23, 'Dulces',               NULL, NULL, '#e83e8c', 1, 23);

-- --------------------------------------------------------

CREATE TABLE `ubicaciones` (
    `id`               int(11)        NOT NULL,
    `nombre`           varchar(100)   NOT NULL,
    `descripcion`      text           DEFAULT NULL,
    `latitud_centro`   decimal(10,8)  DEFAULT NULL,
    `longitud_centro`  decimal(11,8)  DEFAULT NULL,
    `poligono_geojson` text           DEFAULT NULL COMMENT 'GeoJSON del polígono del área',
    `activo`           tinyint(1)     DEFAULT 1,
    `created_at`       timestamp      NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ubicaciones` (`id`, `nombre`, `latitud_centro`, `longitud_centro`, `activo`) VALUES
(1, 'PI El Pantanillo', -28.46960000, -65.77950000, 1),
(2, 'Capital',          -28.46960000, -65.78520000, 1),
(3, 'Valle Viejo',      -28.39170000, -65.70950000, 1),
(4, 'Recreo',           -29.28330000, -65.06670000, 1);

-- --------------------------------------------------------

CREATE TABLE `configuracion_sitio` (
    `id`          int(11)                                                   NOT NULL,
    `clave`       varchar(100)                                              NOT NULL,
    `valor`       text                                                      DEFAULT NULL,
    `tipo`        enum('text','textarea','number','boolean','json','image') DEFAULT 'text',
    `grupo`       varchar(50)                                               DEFAULT NULL,
    `descripcion` varchar(255)                                              DEFAULT NULL,
    `updated_at`  timestamp                                                 NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion_sitio` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`) VALUES
(1,  'sitio_nombre',               'Parque Industrial de Catamarca',                        'text',     'general',   'Nombre del sitio'),
(2,  'sitio_descripcion',          'Portal del Parque Industrial de la Provincia de Catamarca', 'textarea','general', 'Descripción del sitio'),
(3,  'sitio_email',                'contacto@parqueindustrial.gob.ar',                      'text',     'contacto',  'Email de contacto'),
(4,  'sitio_telefono',             '(0383) 4123456',                                        'text',     'contacto',  'Teléfono de contacto'),
(5,  'sitio_direccion',            'San Fernando del Valle de Catamarca, Argentina',         'text',     'contacto',  'Dirección física'),
(6,  'mapa_lat_centro',            '-28.4696',                                              'text',     'mapa',      'Latitud centro del mapa'),
(7,  'mapa_lng_centro',            '-65.7795',                                              'text',     'mapa',      'Longitud centro del mapa'),
(8,  'mapa_zoom_inicial',          '12',                                                    'number',   'mapa',      'Zoom inicial del mapa'),
(9,  'redes_facebook',             'https://facebook.com/parqueindustrialcatamarca',        'text',     'redes',     'Facebook'),
(10, 'redes_instagram',            'https://instagram.com/parqueindustrialcatamarca',       'text',     'redes',     'Instagram'),
(11, 'redes_twitter',              '',                                                      'text',     'redes',     'Twitter/X'),
(12, 'texto_sobre_nosotros',       'El Parque Industrial de Catamarca es un polo de desarrollo...', 'textarea','contenido','Texto sobre nosotros'),
(13, 'mostrar_estadisticas_publicas','1',                                                   'boolean',  'privacidad','Mostrar estadísticas al público');

-- --------------------------------------------------------

CREATE TABLE `banners_home` (
    `id`          int(11)                      NOT NULL,
    `titulo`      varchar(255)                 DEFAULT NULL,
    `subtitulo`   varchar(255)                 DEFAULT NULL,
    `imagen`      varchar(255)                 NOT NULL,
    `url`         varchar(255)                 DEFAULT NULL,
    `orden`       int(11)                      DEFAULT 0,
    `activo`      tinyint(1)                   DEFAULT 1,
    `tipo`        enum('imagen','video')        NOT NULL DEFAULT 'imagen',
    `url_video`   varchar(500)                 DEFAULT NULL,
    `fecha_inicio` date                        DEFAULT NULL,
    `fecha_fin`   date                         DEFAULT NULL,
    `created_at`  timestamp                    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `formularios_config` (
    `id`          int(11)   NOT NULL,
    `nombre`      varchar(100) NOT NULL,
    `descripcion` text      DEFAULT NULL,
    `campos`      longtext  CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Estructura JSON de campos' CHECK (json_valid(`campos`)),
    `activo`      tinyint(1) DEFAULT 1,
    `obligatorio` tinyint(1) DEFAULT 0,
    `fecha_limite` date     DEFAULT NULL,
    `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at`  timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `login_attempts` (
    `ip`              varchar(45)  NOT NULL,
    `email`           varchar(255) DEFAULT NULL,
    `intentos`        int(11)      NOT NULL DEFAULT 1,
    `ultimo_intento`  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `bloqueado_hasta` datetime     DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `password_reset_requests` (
    `id`         int(11)      NOT NULL,
    `ip`         varchar(45)  NOT NULL,
    `created_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USUARIOS (sin FK externas — FK a empresa viene después)
-- Password hash para "admin123":
--   $2y$10$xHWydNT4i4lVvD.OjuYOH.TjIJRZcoAS2nOO2vK1t7EBvHu4Dgo5C
-- ============================================================
CREATE TABLE `usuarios` (
    `id`                     int(11)                            NOT NULL,
    `email`                  varchar(255)                       NOT NULL,
    `password`               varchar(255)                       NOT NULL,
    `rol`                    enum('empresa','ministerio','admin') NOT NULL DEFAULT 'empresa',
    `activo`                 tinyint(1)                         DEFAULT 1,
    `ultimo_acceso`          datetime                           DEFAULT NULL,
    `token_recuperacion`     varchar(255)                       DEFAULT NULL,
    `token_expira`           datetime                           DEFAULT NULL,
    `token_activacion`       varchar(255)                       DEFAULT NULL,
    `token_activacion_expira` datetime                          DEFAULT NULL,
    `email_verificado`       tinyint(1)                         NOT NULL DEFAULT 1,
    `created_at`             timestamp                          NOT NULL DEFAULT current_timestamp(),
    `updated_at`             timestamp                          NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hash único para "admin123" — todos los usuarios demo
SET @H = '$2y$10$xHWydNT4i4lVvD.OjuYOH.TjIJRZcoAS2nOO2vK1t7EBvHu4Dgo5C';

INSERT INTO `usuarios` (`id`, `email`, `password`, `rol`, `activo`, `email_verificado`) VALUES
(1,   'admin@parqueindustrial.gob.ar',     @H, 'admin',      1, 1),
(2,   'ministerio@catamarca.gob.ar',       @H, 'ministerio', 1, 1),
(3,   'empresa@demo.com',                  @H, 'empresa',    1, 1),
(101, 'empresa1@parqueindustrial.com',     @H, 'empresa',    1, 1),
(102, '30611724728@parqueindustrial.com',  @H, 'empresa',    1, 1),
(103, '30714224669@parqueindustrial.com',  @H, 'empresa',    1, 1),
(104, '20173731486@parqueindustrial.com',  @H, 'empresa',    1, 1),
(105, '30708834153@parqueindustrial.com',  @H, 'empresa',    1, 1),
(106, '30695139299@parqueindustrial.com',  @H, 'empresa',    1, 1),
(107, '30668122961@parqueindustrial.com',  @H, 'empresa',    1, 1),
(108, '30707474021@parqueindustrial.com',  @H, 'empresa',    1, 1),
(109, '30714268844@parqueindustrial.com',  @H, 'empresa',    1, 1),
(110, '30715835947@parqueindustrial.com',  @H, 'empresa',    1, 1),
(111, '30715948377@parqueindustrial.com',  @H, 'empresa',    1, 1),
(112, '30715402587@parqueindustrial.com',  @H, 'empresa',    1, 1),
(113, '30570245690@parqueindustrial.com',  @H, 'empresa',    1, 1),
(115, '33716822899@parqueindustrial.com',  @H, 'empresa',    1, 1),
(116, '20187854807@parqueindustrial.com',  @H, 'empresa',    1, 1),
(117, '30715142348@parqueindustrial.com',  @H, 'empresa',    1, 1),
(118, '30708933291@parqueindustrial.com',  @H, 'empresa',    1, 1),
(119, '30709565899@parqueindustrial.com',  @H, 'empresa',    1, 1),
(120, '30713626305@parqueindustrial.com',  @H, 'empresa',    1, 1),
(121, '20953571103@parqueindustrial.com',  @H, 'empresa',    1, 1),
(122, '30715673416@parqueindustrial.com',  @H, 'empresa',    1, 1),
(145, '23171503469@parqueindustrial.com',  @H, 'empresa',    1, 1),
(146, '30500987185@parqueindustrial.com',  @H, 'empresa',    1, 1),
(147, '30712544364@parqueindustrial.com',  @H, 'empresa',    1, 1),
(148, '20311263901@parqueindustrial.com',  @H, 'empresa',    1, 1),
(149, '30716834383@parqueindustrial.com',  @H, 'empresa',    1, 1),
(150, '30716527804@parqueindustrial.com',  @H, 'empresa',    1, 1),
(151, '20285404496@parqueindustrial.com',  @H, 'empresa',    1, 1),
(152, '30695160344@parqueindustrial.com',  @H, 'empresa',    1, 1),
(153, '30710492332@parqueindustrial.com',  @H, 'empresa',    1, 1),
(154, '30711278903@parqueindustrial.com',  @H, 'empresa',    1, 1),
(155, '30647755867@parqueindustrial.com',  @H, 'empresa',    1, 1),
(156, '30579939008@parqueindustrial.com',  @H, 'empresa',    1, 1),
(157, '30500833781@parqueindustrial.com',  @H, 'empresa',    1, 1),
(158, '30711409242@parqueindustrial.com',  @H, 'empresa',    1, 1),
(159, '30668133297@parqueindustrial.com',  @H, 'empresa',    1, 1),
(160, '30711012865@parqueindustrial.com',  @H, 'empresa',    1, 1),
(161, '30715977865@parqueindustrial.com',  @H, 'empresa',    1, 1),
(162, '30708048948@parqueindustrial.com',  @H, 'empresa',    1, 1),
(163, '30695160417@parqueindustrial.com',  @H, 'empresa',    1, 1),
(164, '30716786591@parqueindustrial.com',  @H, 'empresa',    1, 1),
(165, '30601819380@parqueindustrial.com',  @H, 'empresa',    1, 1),
(166, '30715002325@parqueindustrial.com',  @H, 'empresa',    1, 1),
(167, '30624002578@parqueindustrial.com',  @H, 'empresa',    1, 1),
(168, '30668086825@parqueindustrial.com',  @H, 'empresa',    1, 1),
(169, '30708919868@parqueindustrial.com',  @H, 'empresa',    1, 1),
(170, '30710482167@parqueindustrial.com',  @H, 'empresa',    1, 1),
(171, '20228289397@parqueindustrial.com',  @H, 'empresa',    1, 1),
(172, '30718193083@parqueindustrial.com',  @H, 'empresa',    1, 1),
(173, '20161181170@parqueindustrial.com',  @H, 'empresa',    1, 1),
(174, '30717572617@parqueindustrial.com',  @H, 'empresa',    1, 1),
(175, '30607170238@parqueindustrial.com',  @H, 'empresa',    1, 1),
(176, '30710771223@parqueindustrial.com',  @H, 'empresa',    1, 1),
(177, '30707524843@parqueindustrial.com',  @H, 'empresa',    1, 1),
(178, '30715877186@parqueindustrial.com',  @H, 'empresa',    1, 1),
(179, '30710564953@parqueindustrial.com',  @H, 'empresa',    1, 1),
(180, '30716246120@parqueindustrial.com',  @H, 'empresa',    1, 1),
(181, '30711748446@parqueindustrial.com',  @H, 'empresa',    1, 1),
(182, '30715503375@parqueindustrial.com',  @H, 'empresa',    1, 1),
(183, '30718820231@parqueindustrial.com',  @H, 'empresa',    1, 1),
(184, '30710691661@parqueindustrial.com',  @H, 'empresa',    1, 1),
(185, '30715640291@parqueindustrial.com',  @H, 'empresa',    1, 1),
(186, '30718884361@parqueindustrial.com',  @H, 'empresa',    1, 1),
(187, '30714774642@parqueindustrial.com',  @H, 'empresa',    1, 1),
(188, '30718698940@parqueindustrial.com',  @H, 'empresa',    1, 1),
(189, '33718582900@parqueindustrial.com',  @H, 'empresa',    1, 1),
(190, '30707511385@parqueindustrial.com',  @H, 'empresa',    1, 1),
(191, '30597919154@parqueindustrial.com',  @H, 'empresa',    1, 1),
(192, '30658240656@parqueindustrial.com',  @H, 'empresa',    1, 1),
(193, '30717200418@parqueindustrial.com',  @H, 'empresa',    1, 1),
(194, '30607152566@parqueindustrial.com',  @H, 'empresa',    1, 1),
(195, '30527607872@parqueindustrial.com',  @H, 'empresa',    1, 1),
(196, '30660356424@parqueindustrial.com',  @H, 'empresa',    1, 1),
(197, '30714726818@parqueindustrial.com',  @H, 'empresa',    1, 1),
(198, '30600376752@parqueindustrial.com',  @H, 'empresa',    1, 1),
(199, '30502793175@parqueindustrial.com',  @H, 'empresa',    1, 1),
(200, '30612340133@parqueindustrial.com',  @H, 'empresa',    1, 1);

-- ============================================================
-- EMPRESAS — ID 1 (demo) + IDs 22-99 (definitivos)
-- Sin IDs 2-21 (eran duplicados del primer batch de prueba).
-- Rubros normalizados a Title Case para coincidir con rubros.nombre.
-- ============================================================
CREATE TABLE `empresas` (
    `id`             int(11)                                          NOT NULL,
    `usuario_id`     int(11)                                          DEFAULT NULL,
    `nombre`         varchar(255)                                     NOT NULL,
    `razon_social`   varchar(255)                                     DEFAULT NULL,
    `cuit`           varchar(20)                                      DEFAULT NULL,
    `rubro`          varchar(100)                                     DEFAULT NULL,
    `descripcion`    text                                             DEFAULT NULL,
    `ubicacion`      varchar(100)                                     DEFAULT NULL COMMENT 'PI El Pantanillo, Capital, etc',
    `direccion`      varchar(255)                                     DEFAULT NULL,
    `latitud`        decimal(10,8)                                    DEFAULT NULL,
    `longitud`       decimal(11,8)                                    DEFAULT NULL,
    `telefono`       varchar(50)                                      DEFAULT NULL,
    `email_contacto` varchar(255)                                     DEFAULT NULL,
    `contacto_nombre` varchar(255)                                    DEFAULT NULL,
    `sitio_web`      varchar(255)                                     DEFAULT NULL,
    `facebook`       varchar(255)                                     DEFAULT NULL,
    `instagram`      varchar(255)                                     DEFAULT NULL,
    `linkedin`       varchar(255)                                     DEFAULT NULL,
    `logo`           varchar(255)                                     DEFAULT NULL,
    `imagen_portada` varchar(255)                                     DEFAULT NULL,
    `estado`         enum('pendiente','activa','suspendida','inactiva') DEFAULT 'pendiente',
    `perfil_completo` tinyint(1)                                      DEFAULT 0,
    `verificada`     tinyint(1)                                       DEFAULT 0,
    `visitas`        int(11)                                          DEFAULT 0,
    `created_at`     timestamp                                        NOT NULL DEFAULT current_timestamp(),
    `updated_at`     timestamp                                        NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `empresas` (`id`, `usuario_id`, `nombre`, `cuit`, `rubro`, `ubicacion`, `latitud`, `longitud`, `telefono`, `contacto_nombre`, `estado`) VALUES
(1,   3,   'Empresa Demo S.R.L.',                                   NULL,              'Textil',                'PI El Pantanillo', NULL,          NULL,          '3834123456',            'Juan Pérez',                        'activa'),
(22,  101,  'ABC Construcciones S.R.L.',                             NULL,              'Construcción',          'PI El Pantanillo', -28.46960000, -65.77950000, '3834781396',             'César Bursi',                       'activa'),
(23,  102,  'Algodonera del Valle S.A.',                             '30-61172472-8',   'Textil',                'PI El Pantanillo', -28.46760000, -65.77750000, '3834205107',             'Ing. Carlos Pinetta',               'activa'),
(24,  103,  'Áridos del Valle S.R.L.',                               '30-71422466-9',   'Construcción',          'PI El Pantanillo', -28.46560000, -65.77550000, '3834582815',             'Eduardo Seleme',                    'activa'),
(25,  104,  'ATC Antonio Tadeo Cabrera',                             '20-17373148-6',   'Metalúrgica',           'PI El Pantanillo', -28.46360000, -65.77350000, '3834637350',             'Antonio Cabrera',                   'activa'),
(26,  105,  'Asha Construcciones S.R.L.',                            '30-70883415-3',   'Construcción',          'PI El Pantanillo', -28.46160000, -65.77150000, '3834637350',             'Antonio Cabrera',                   'activa'),
(27,  106,  'BG Cons SRL',                                          '30-69513929-9',   'Construcción',          'PI El Pantanillo', -28.45960000, -65.76950000, '3834995711',             'Ricardo Vega',                      'activa'),
(28,  107,  'Inges S.R.L.',                                         '30-66812296-1',   'Maquinaria Industrial', 'PI El Pantanillo', -28.45760000, -65.76750000, '3834265913',             'Arturo Castellon',                  'activa'),
(29,  108,  'Block S.R.L.',                                         '30-70747402-1',   'Hormigón',              'PI El Pantanillo', -28.45560000, -65.76550000, '3834431684 / 3834543355','Felipe Loss',                       'activa'),
(30,  109,  'Botas Catamarca S.A.',                                 '30-71426884-4',   'Calzados',              'PI El Pantanillo', -28.45360000, -65.76350000, '3834405390',             'Gustavo Muia / Miguel Muzla',       'activa'),
(31,  110,  'JL Uniformes S.R.L.',                                  '30-71583594-7',   'Textil',                'PI El Pantanillo', -28.45160000, -65.76150000, '3834405390',             'Gustavo Muia / Miguel Muzla',       'activa'),
(32,  111,  'Construcciones Metálicas S.A.S.',                      '30-71594837-7',   'Metalúrgica',           'PI El Pantanillo', -28.46860000, -65.77950000, '3834058944',             'Adrián Morano',                     'activa'),
(33,  112,  'Corralón Lavalle S.R.L.',                              '30-71540258-7',   'Construcción',          'PI El Pantanillo', -28.46660000, -65.77750000, '3834406384 / 3834516885','Martin Fikinger / Luis Santucho',   'activa'),
(34,  113,  'Grupo TN Platex - Coteca S.A.',                        '30-57024569-0',   'Textil',                'PI El Pantanillo', -28.46460000, -65.77550000, '3804497001',             'Ing Jorge TN',                      'activa'),
(35,  113,  'Grupo TN Platex - Textiles Industriales Big Bags',     '30-57024569-0',   'Textil',                'PI El Pantanillo', -28.46260000, -65.77350000, '91123406535',            'Federico Flores',                   'activa'),
(36,  115,  'Colores Andinos S.R.L.',                               '33-71682289-9',   'Química',               'PI El Pantanillo', -28.46060000, -65.77150000, '3804364747',             'Mustafa Yoma',                      'activa'),
(37,  116,  'Dante Ricardo Porcel de Peralta',                      '20-18785480-7',   'Metalúrgica',           'PI El Pantanillo', -28.45860000, -65.76950000, '3834319265',             'Ricardo Peralta',                   'activa'),
(38,  117,  'Canbir S.R.L.',                                        '30-71514234-8',   'Construcción',          'PI El Pantanillo', -28.45660000, -65.76750000, '3834379770',             'Daniel Birgi',                      'activa'),
(39,  118,  'CVA S.H.',                                             '30-70893329-1',   'Construcción',          'PI El Pantanillo', -28.45460000, -65.76550000, '3834612244',             'Carlos Vergara',                    'activa'),
(40,  119,  'Edificat S.A.',                                        '30-70956589-9',   'Metalúrgica',           'PI El Pantanillo', -28.45260000, -65.76350000, '3834240527',             'Arq. Luis Cantarell',               'activa'),
(41,  120,  'Expreso Diemar del Noroeste S.R.L.',                   '30-71362630-5',   'Transporte',            'PI El Pantanillo', -28.45060000, -65.76150000, '91133697910',            'Diego Colona',                      'activa'),
(42,  121,  'Fripp S.A.',                                           '20-95357110-3',   'Plásticos',             'PI El Pantanillo', -28.46760000, -65.77950000, '3834240527',             'Arq. Luis Cantarell',               'activa'),
(43,  122,  'FMF Mining & Services S.R.L.',                         '30-71567341-6',   'Fibra de Vidrio',       'PI El Pantanillo', -28.46560000, -65.77750000, '3813360920',             'Denis Brizuela',                    'activa'),
(44,  145,  'Carlos Alberto Larcher - Frigorífico Trasmontaña',     '23-17150346-9',   'Alimentos',             'PI El Pantanillo', -28.46360000, -65.77550000, '3834210176 / 3834422199','Carlos Larcher',                    'activa'),
(45,  146,  'Gador S.A.',                                           '30-50098718-5',   'Medicamentos',          'PI El Pantanillo', -28.46160000, -65.77350000, '3834355888',             'Ernesto Martínez',                  'activa'),
(46,  147,  'Arpa S.A.',                                            '30-71254436-4',   'Hormigón',              'PI El Pantanillo', -28.45960000, -65.77150000, '3834538524',             'José Gaso',                         'activa'),
(47,  148,  'Rodriguez Franco Luis - Gasfran',                      '20-31126390-1',   'Transporte',            'PI El Pantanillo', -28.45760000, -65.76950000, '3834600413',             'Franco Rodriguez',                  'activa'),
(48,  149,  'Greencat S.R.L.',                                      '30-71683438-3',   'Construcción',          'PI El Pantanillo', -28.45560000, -65.76750000, '3834371006',             'Felipe Loss',                       'activa'),
(49,  150,  'Grupo Cinco Energy S.R.L.',                            '30-71652780-4',   'Construcción',          'PI El Pantanillo', -28.45360000, -65.76550000, '38344692007',            'Laura Brandan',                     'activa'),
(50,  151,  'Gustavo Emilio Córdoba - Resindus',                    '20-28540449-6',   'Construcción',          'PI El Pantanillo', -28.45160000, -65.76350000, '3834309464',             'Gustavo Cordoba',                   'activa'),
(51,  152,  'Hidroper S.R.L.',                                      '30-69516034-4',   'Minería',               'PI El Pantanillo', -28.44960000, -65.76150000, '3834188258',             'Administración',                    'activa'),
(52,  153,  'Instalaciones Catamarca S.R.L.',                       '30-71049233-2',   'Construcción',          'PI El Pantanillo', -28.46660000, -65.77950000, '3834558751 / 3834594209','Carlos Moreno / Martin Moreno',     'activa'),
(53,  154,  'La Ciudadela S.R.L.',                                  '30-71127890-3',   'Alimentos',             'PI El Pantanillo', -28.46460000, -65.77750000, '3814683184',             'Roberto Farias Menéndez',           'activa'),
(54,  155,  'Distribuidora La Mendocina SRL',                       '30-64775586-7',   'Plásticos',             'PI El Pantanillo', -28.46260000, -65.77550000, '3834227530',             'Jonathan de Arco',                  'activa'),
(55,  156,  'Transporte La Sevillanita S.R.L.',                     '30-57993900-8',   'Transporte',            'PI El Pantanillo', -28.46060000, -65.77350000, '3834255179',             'Marcelo Mansilla',                  'activa'),
(56,  157,  'Longvie S.A.',                                         '30-50083378-1',   'Electrodomésticos',     'PI El Pantanillo', -28.45860000, -65.77150000, '3834315506',             'Ing. Oscar Schonhals',              'activa'),
(57,  158,  'Marimari Modular S.A.',                                '30-71140924-2',   'Metalúrgica',           'PI El Pantanillo', -28.45660000, -65.76950000, '3834464277',             'Luis Nour',                         'activa'),
(58,  159,  'Matias Amengual S.R.L.',                               '30-66813329-7',   'Combustibles',          'PI El Pantanillo', -28.45460000, -65.76750000, '3834626994',             'Luis Nieva',                        'activa'),
(59,  160,  'MBA Ingeniería S.R.L.',                                '30-71101286-5',   'Construcción',          'PI El Pantanillo', -28.45260000, -65.76550000, '3834273518',             'Antonio Mazuco',                    'activa'),
(60,  161,  'Agrolactea del NOA S.R.L. - Lácteos Micky',            '30-71597786-5',   'Alimentos',             'PI El Pantanillo', -28.45060000, -65.76350000, '3834261678',             'Ing. Daiana Martin Lazo',           'activa'),
(61,  162,  'Minera Petrea S.R.L.',                                 '30-70804894-8',   'Minería',               'PI El Pantanillo', -28.44860000, -65.76150000, '3834464200',             'Liliana D´agostini',                'activa'),
(62,  163,  'Natilla S.A.',                                         '30-69516041-7',   'Alimentos',             'PI El Pantanillo', -28.46560000, -65.77950000, '3834680649',             'Hugo Natilla',                      'activa'),
(63,  164,  'HL Catamarca S.A.',                                    '30-71678659-1',   'Electrodomésticos',     'PI El Pantanillo', -28.46360000, -65.77750000, '1160283877',             'Miguel Ferrari',                    'activa'),
(64,  165,  'Nortextil S.A.',                                       '30-60181938-0',   'Textil',                'PI El Pantanillo', -28.46160000, -65.77550000, '3834683285 / 3834641783','Mario Ahumada / Marcelo Avellaneda','activa'),
(65,  166,  'One Construcciones S.R.L.',                            '30-71500232-5',   'Construcción',          'PI El Pantanillo', -28.45960000, -65.77350000, '383-4599627',            'Verona Stefanoff',                  'activa'),
(66,  167,  'Puma Catamarca S.A.',                                  '30-62400257-8',   'Motocicletas',          'PI El Pantanillo', -28.45760000, -65.77150000, '3834509565 / 3834509564','Felipe Franco / Daniel Franco',     'activa'),
(67,  168,  'Roccia S.A.',                                          '30-66808682-5',   'Calzados',              'PI El Pantanillo', -28.45560000, -65.76950000, '3834407407 / 3834791530','Gonzalo Sevillano / Verónica',      'activa'),
(68,  169,  'Rocotex S.R.L.',                                       '30-70891986-8',   'Textil',                'PI El Pantanillo', -28.45360000, -65.76750000, '91144448215',            'Marcelo Derwill / Osvaldo Olguin',  'activa'),
(69,  170,  'Router S.A. Mercomat',                                 '30-71048216-7',   'Construcción',          'PI El Pantanillo', -28.45160000, -65.76550000, '3834519017',             'Silvio Rodriguez',                  'activa'),
(70,  171,  'Roque Astudillo - Reciclado',                          '20-22828939-7',   'Reciclado',             'PI El Pantanillo', -28.44960000, -65.76350000, '3834320067',             'Roque Astudillo',                   'activa'),
(71,  172,  'Depósito Martínez S.R.L.',                             '30-71819308-3',   'Reciclado',             'PI El Pantanillo', -28.44760000, -65.76150000, '3468644474',             'Dorian Campos',                     'activa'),
(72,  173,  'Luis Laza - Recuperadora',                             '20-16118117-0',   'Reciclado',             'PI El Pantanillo', -28.46460000, -65.77950000, '3834620951',             'Luis Laza',                         'activa'),
(73,  174,  'Smbcons S.R.L.',                                       '30-71757261-7',   'Construcción',          'PI El Pantanillo', -28.46260000, -65.77750000, '3834291311',             'Marcelo Billincanta',               'activa'),
(74,  175,  'Tevinor S.A.',                                         '30-60717023-8',   'Textil',                'PI El Pantanillo', -28.46060000, -65.77550000, '3834950201',             'Walter Nieva',                      'activa'),
(75,  176,  'Textil de los Andes S.A.',                             '30-71077122-3',   'Textil',                'PI El Pantanillo', -28.45860000, -65.77350000, '3834972678',             'Mariano Botella',                   'activa'),
(76,  177,  'San José Obrero S.R.L.',                               '30-70752484-3',   'Transporte',            'PI El Pantanillo', -28.45660000, -65.77150000, '3834435940 / 3834432238','Jose Guillermo Arce',               'activa'),
(77,  178,  'Transporte Urine SRL',                                 '30-71587718-6',   'Medicamentos',          'PI El Pantanillo', -28.45460000, -65.76950000, '3834290072',             'Matias Soria',                      'activa'),
(78,  179,  'Vialnort S.R.L.',                                      '30-71056495-3',   'Hormigón',              'PI El Pantanillo', -28.45260000, -65.76750000, '3834305526',             'Jorge Aparicio',                    'activa'),
(79,  180,  'Imeff Electrodomésticos S.R.L.',                       '30-71624612-0',   'Electrodomésticos',     'PI El Pantanillo', -28.45060000, -65.76550000, '91160283878',            'David Santillan',                   'activa'),
(80,  181,  'Paradigma del Norte S.R.L.',                           '30-71174844-6',   'Reciclado',             'PI El Pantanillo', -28.44860000, -65.76350000, '3834603995',             'Carlos P.',                         'activa'),
(81,  182,  'VCC S.R.L.',                                           '30-71550337-5',   'Textil',                'PI El Pantanillo', -28.44660000, -65.76150000, '91122389967',            'Rubén Calderone',                   'activa'),
(82,  183,  'Lizclor S.R.L.',                                       '30-71882023-1',   'Química',               'PI El Pantanillo', -28.46360000, -65.77950000, '3834594209',             'Ing. Martin Moreno',                'activa'),
(83,  184,  'Tecmic S.R.L.',                                        '30-71069166-1',   'Maquinaria Industrial', 'PI El Pantanillo', -28.46160000, -65.77750000, '3512303939',             'Carlos Lurgo',                      'activa'),
(84,  185,  'Wayku S.R.L.',                                         '30-71564029-1',   'Agroindustria',         'PI El Pantanillo', -28.45960000, -65.77550000, '3834047161',             'Julio Aibar',                       'activa'),
(85,  186,  'Recupero Catamarca S.R.L.',                            '30-71888436-1',   'Autopartes',            'PI El Pantanillo', -28.45760000, -65.77350000, '3834685192',             'Fernando P.',                       'activa'),
(86,  187,  'Mape S.R.L.',                                          '30-71477464-2',   'Frigorífico',           'PI El Pantanillo', -28.45560000, -65.77150000, '3482539455',             'Matias Sartor',                     'activa'),
(87,  188,  'Tecnofibra S.A.S.',                                    '30-71869894-0',   'Fibra de Vidrio',       'PI El Pantanillo', -28.45360000, -65.76950000, '3815109238',             'Marco Peñaloza',                    'activa'),
(88,  189,  'Catamarca Transporte S.A.U.',                          '33-71858290-0',   'Transporte',            'PI El Pantanillo', -28.45160000, -65.76750000, '3834026819',             'Eduardo Andrada',                   'activa'),
(89,  190,  'Servicentro Lavalle S.R.L.',                           '30-70751138-5',   'Combustibles',          'PI El Pantanillo', -28.44960000, -65.76550000, '3854880012',             'Emilio Alderete',                   'activa'),
(90,  191,  'Confecat S.A.',                                        '30-59791915-4',   'Textil',                'Capital',          -28.46960000, -65.78520000, '3834507380',             'Carlos Muia / Rosana Costilla',     'activa'),
(91,  192,  'RA Intertrading S.A.',                                 '30-65824065-6',   'Textil',                'Capital',          -28.46760000, -65.78320000, '3816013286 / 1134279620','Adolfo Atienza - Gerente',          'activa'),
(92,  193,  'Indumentaria Catamarca S.A.',                          '30-71720041-8',   'Textil',                'Capital',          -28.46560000, -65.78120000, '3834394240 / 3834625861','Julio Serrano - Gerente',           'activa'),
(93,  194,  'Macata S.A.',                                          '30-60715256-6',   'Textil',                'Valle Viejo',      -28.39170000, -65.70950000, '91144007774',            'Alejandra - Administracion',        'activa'),
(94,  195,  'Cooperativa de Tamberos Catamarca Ltda - Cotali',      '30-52760787-2',   'Lácteos',               'Valle Viejo',      -28.38970000, -65.70750000, '3834926123',             'Joaquin Reyes',                     'activa'),
(95,  196,  'Regionales del Norte S.R.L. - Cuesta del Portezuelo', '30-66035642-4',   'Dulces',                'Valle Viejo',      -28.38770000, -65.70550000, '3834658120',             'Juan Pablo CP',                     'activa'),
(96,  197,  'Grupo Fibran Sur S.A.',                                '30-71472681-8',   'Textil',                'Valle Viejo',      -28.38570000, -65.70350000, '3834427700 / 3834537922','Jorge Rodriguez / Julio Herrera',   'activa'),
(97,  198,  'Tejica S.A.',                                          '30-60037675-2',   'Textil',                'Recreo',           -29.28330000, -65.06670000, '91164044596',            'Leonardo Peretta',                  'activa'),
(98,  199,  'Arcor S.A.',                                           '30-50279317-5',   'Alimentos',             'Recreo',           -29.28130000, -65.06470000, '3834298159',             'Pamela Espinosa',                   'activa'),
(99,  200,  'Sabri S.A.',                                           '30-61234013-3',   'Textil',                'Recreo',           -29.27930000, -65.06270000, '9115603111',             'Carlos Brieva',                     'activa');

-- ============================================================
-- TABLAS DEPENDIENTES DE usuarios / empresas
-- ============================================================

CREATE TABLE `datos_empresa` (
    `id`                      int(11)                                              NOT NULL,
    `empresa_id`              int(11)                                              NOT NULL,
    `periodo`                 varchar(20)                                          NOT NULL COMMENT 'Ej: 2025-Q1, 2025-Q2',
    `dotacion_total`          int(11)                                              DEFAULT 0,
    `empleados_masculinos`    int(11)                                              DEFAULT 0,
    `empleados_femeninos`     int(11)                                              DEFAULT 0,
    `empleados_otros`         int(11)                                              DEFAULT 0,
    `capacidad_instalada`     varchar(255)                                         DEFAULT NULL,
    `porcentaje_capacidad_uso` decimal(5,2)                                        DEFAULT NULL,
    `produccion_mensual`      varchar(255)                                         DEFAULT NULL,
    `unidad_produccion`       varchar(50)                                          DEFAULT NULL,
    `consumo_energia`         decimal(12,2)                                        DEFAULT NULL COMMENT 'kWh mensuales',
    `consumo_agua`            decimal(12,2)                                        DEFAULT NULL COMMENT 'm3 mensuales',
    `consumo_gas`             decimal(12,2)                                        DEFAULT NULL COMMENT 'm3 mensuales',
    `conexion_red_agua`       tinyint(1)                                           DEFAULT 0,
    `pozo_agua`               tinyint(1)                                           DEFAULT 0,
    `conexion_gas_natural`    tinyint(1)                                           DEFAULT 0,
    `conexion_cloacas`        tinyint(1)                                           DEFAULT 0,
    `exporta`                 tinyint(1)                                           DEFAULT 0,
    `productos_exporta`       text                                                 DEFAULT NULL,
    `paises_exporta`          varchar(255)                                         DEFAULT NULL,
    `monto_exportaciones`     decimal(15,2)                                        DEFAULT NULL,
    `importa`                 tinyint(1)                                           DEFAULT 0,
    `productos_importa`       text                                                 DEFAULT NULL,
    `paises_importa`          varchar(255)                                         DEFAULT NULL,
    `monto_importaciones`     decimal(15,2)                                        DEFAULT NULL,
    `emisiones_co2`           decimal(12,4)                                        DEFAULT NULL COMMENT 'Toneladas CO2 equivalente',
    `fuente_emision_principal` varchar(100)                                        DEFAULT NULL,
    `inversion_anual`         decimal(15,2)                                        DEFAULT NULL,
    `inversion_maquinaria`    decimal(15,2)                                        DEFAULT NULL,
    `inversion_infraestructura` decimal(15,2)                                      DEFAULT NULL,
    `rango_facturacion`       enum('micro','pequeña','mediana','grande')           DEFAULT NULL,
    `certificaciones`         text                                                 DEFAULT NULL COMMENT 'ISO, etc.',
    `estado`                  enum('borrador','enviado','aprobado','rechazado')    DEFAULT 'borrador',
    `declaracion_jurada`      tinyint(1)                                           DEFAULT 0,
    `fecha_declaracion`       datetime                                             DEFAULT NULL,
    `ip_declaracion`          varchar(45)                                          DEFAULT NULL,
    `observaciones_ministerio` text                                                DEFAULT NULL,
    `revisado_por`            int(11)                                              DEFAULT NULL,
    `fecha_revision`          datetime                                             DEFAULT NULL,
    `created_at`              timestamp                                            NOT NULL DEFAULT current_timestamp(),
    `updated_at`              timestamp                                            NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `publicaciones` (
    `id`               int(11)                                             NOT NULL,
    `empresa_id`       int(11)                                             DEFAULT NULL COMMENT 'NULL si es del ministerio',
    `usuario_id`       int(11)                                             NOT NULL,
    `tipo`             enum('noticia','evento','promocion','comunicado')   DEFAULT 'noticia',
    `titulo`           varchar(255)                                        NOT NULL,
    `slug`             varchar(255)                                        NOT NULL,
    `extracto`         text                                                DEFAULT NULL,
    `contenido`        longtext                                            DEFAULT NULL,
    `imagen`           varchar(255)                                        DEFAULT NULL,
    `publicado`        tinyint(1)                                          DEFAULT 0,
    `destacado`        tinyint(1)                                          DEFAULT 0,
    `mostrar_en_inicio` tinyint(1)                                         DEFAULT 0,
    `estado`           enum('borrador','pendiente','aprobado','rechazado') DEFAULT 'borrador',
    `aprobado_por`     int(11)                                             DEFAULT NULL,
    `fecha_aprobacion` datetime                                            DEFAULT NULL,
    `motivo_rechazo`   text                                                DEFAULT NULL,
    `fecha_publicacion` datetime                                           DEFAULT NULL,
    `fecha_expiracion` datetime                                            DEFAULT NULL,
    `visitas`          int(11)                                             DEFAULT 0,
    `created_at`       timestamp                                           NOT NULL DEFAULT current_timestamp(),
    `updated_at`       timestamp                                           NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `archivos_publicacion` (
    `id`              int(11)      NOT NULL,
    `publicacion_id`  int(11)      NOT NULL,
    `nombre_original` varchar(255) NOT NULL,
    `nombre_archivo`  varchar(255) NOT NULL,
    `tipo_mime`       varchar(100) DEFAULT NULL,
    `tamano`          int(11)      DEFAULT NULL,
    `created_at`      timestamp    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- mensajes: tabla legacy (+ columna categoria de migración 015)
CREATE TABLE `mensajes` (
    `id`               int(11)      NOT NULL,
    `remitente_id`     int(11)      NOT NULL,
    `destinatario_id`  int(11)      DEFAULT NULL COMMENT 'NULL = mensaje al ministerio',
    `empresa_id`       int(11)      DEFAULT NULL,
    `asunto`           varchar(255) NOT NULL,
    `categoria`        varchar(80)  DEFAULT NULL,
    `contenido`        text         NOT NULL,
    `adjuntos`         text         DEFAULT NULL,
    `leido`            tinyint(1)   DEFAULT 0,
    `fecha_lectura`    datetime     DEFAULT NULL,
    `archivado`        tinyint(1)   DEFAULT 0,
    `mensaje_padre_id` int(11)      DEFAULT NULL,
    `created_at`       timestamp    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `notificaciones` (
    `id`            int(11)      NOT NULL,
    `usuario_id`    int(11)      NOT NULL,
    `tipo`          varchar(50)  NOT NULL COMMENT 'perfil_editado, formulario_enviado, etc',
    `titulo`        varchar(255) NOT NULL,
    `mensaje`       text         DEFAULT NULL,
    `url`           varchar(255) DEFAULT NULL,
    `datos`         longtext     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
    `leida`         tinyint(1)   DEFAULT 0,
    `fecha_lectura` datetime     DEFAULT NULL,
    `created_at`    timestamp    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `log_actividad` (
    `id`              int(11)      NOT NULL,
    `usuario_id`      int(11)      DEFAULT NULL,
    `empresa_id`      int(11)      DEFAULT NULL,
    `accion`          varchar(100) NOT NULL,
    `tabla_afectada`  varchar(50)  DEFAULT NULL,
    `registro_id`     int(11)      DEFAULT NULL,
    `datos_anteriores` longtext    CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
    `datos_nuevos`    longtext     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
    `ip`              varchar(45)  DEFAULT NULL,
    `user_agent`      varchar(255) DEFAULT NULL,
    `created_at`      timestamp    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `log_actividad` (`id`, `usuario_id`, `empresa_id`, `accion`, `tabla_afectada`, `registro_id`, `ip`, `created_at`) VALUES
(1, 3, 1, 'login',  'usuarios', 3, '::1', '2025-12-17 11:04:48'),
(2, 3, 1, 'logout', 'usuarios', 3, '::1', '2025-12-17 11:07:27'),
(3, 2, NULL, 'login','usuarios', 2, '::1', '2025-12-17 11:07:52');

-- --------------------------------------------------------

CREATE TABLE `visitas_empresa` (
    `id`         int(11)      NOT NULL,
    `empresa_id` int(11)      NOT NULL,
    `ip`         varchar(45)  DEFAULT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `referer`    varchar(255) DEFAULT NULL,
    `created_at` timestamp    NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visitas_empresa` (`id`, `empresa_id`, `ip`, `created_at`) VALUES
(1, 98, '::1', '2025-12-17 12:15:48'),
(2, 98, '::1', '2025-12-17 12:16:10'),
(3, 98, '::1', '2025-12-17 12:16:22');

-- --------------------------------------------------------

CREATE TABLE `respuestas_formulario` (
    `id`            int(11)  NOT NULL,
    `formulario_id` int(11)  NOT NULL,
    `empresa_id`    int(11)  NOT NULL,
    `respuestas`    longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`respuestas`)),
    `estado`        enum('borrador','enviado','aprobado','rechazado')   DEFAULT 'borrador',
    `created_at`    timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at`    timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FORMULARIOS DINÁMICOS (ministerio crea → empresa responde)
-- ============================================================
CREATE TABLE `formularios_dinamicos` (
    `id`          int(11)      NOT NULL AUTO_INCREMENT,
    `titulo`      varchar(255) NOT NULL,
    `descripcion` text         DEFAULT NULL,
    `estado`      enum('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
    `creado_por`  int(11)      DEFAULT NULL,
    `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at`  timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_estado`     (`estado`),
    KEY `idx_creado_por` (`creado_por`),
    CONSTRAINT `formularios_dinamicos_ibfk_1`
        FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `formulario_preguntas` (
    `id`            int(11)      NOT NULL AUTO_INCREMENT,
    `formulario_id` int(11)      NOT NULL,
    `tipo`          enum('texto','textarea','numero','fecha','select','radio','checkbox','tabla') NOT NULL,
    `etiqueta`      varchar(255) NOT NULL,
    `ayuda`         varchar(255) DEFAULT NULL,
    `requerido`     tinyint(1)   DEFAULT 0,
    `opciones`      longtext     DEFAULT NULL,
    `orden`         int(11)      DEFAULT 0,
    `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_formulario` (`formulario_id`),
    CONSTRAINT `formulario_preguntas_ibfk_1`
        FOREIGN KEY (`formulario_id`) REFERENCES `formularios_dinamicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `formulario_respuestas` (
    `id`            int(11)      NOT NULL AUTO_INCREMENT,
    `formulario_id` int(11)      NOT NULL,
    `empresa_id`    int(11)      NOT NULL,
    `usuario_id`    int(11)      DEFAULT NULL,
    `estado`        enum('borrador','enviado') NOT NULL DEFAULT 'borrador',
    `respuestas`    longtext     NOT NULL,
    `ip`            varchar(45)  DEFAULT NULL,
    `enviado_at`    datetime     DEFAULT NULL,
    `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at`    timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_formulario_empresa` (`formulario_id`, `empresa_id`),
    KEY `idx_estado` (`estado`),
    CONSTRAINT `formulario_respuestas_ibfk_1`
        FOREIGN KEY (`formulario_id`) REFERENCES `formularios_dinamicos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `formulario_respuestas_ibfk_2`
        FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `formulario_respuestas_ibfk_3`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CENTRO DE COMUNICACIONES (migraciones 016 + 017 + 018)
-- ============================================================

CREATE TABLE `conversaciones` (
    `id`               int(11)      NOT NULL AUTO_INCREMENT,
    `titulo`           varchar(200) NOT NULL,
    `empresa_id`       int(11)      DEFAULT NULL COMMENT 'NULL = comunicado global',
    `iniciada_por`     enum('empresa','ministerio','sistema') NOT NULL,
    `categoria`        enum('tramite','consulta','reclamo','comunicado','formulario','sistema') NOT NULL DEFAULT 'consulta',
    `estado`           enum('abierta','cerrada','archivada') NOT NULL DEFAULT 'abierta',
    `referencia_tipo`  varchar(40)  DEFAULT NULL,
    `referencia_id`    int(11)      DEFAULT NULL,
    `ultimo_mensaje_at` timestamp   DEFAULT NULL,
    `created_at`       timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at`       timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_conv_referencia` (`referencia_tipo`, `referencia_id`),
    INDEX `idx_empresa_estado`  (`empresa_id`, `estado`),
    INDEX `idx_estado_ultimo`   (`estado`, `ultimo_mensaje_at`),
    INDEX `idx_categoria`       (`categoria`),
    INDEX `idx_referencia`      (`referencia_tipo`, `referencia_id`),
    CONSTRAINT `fk_conv_empresa`
        FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mensajes_v2` (
    `id`               int(11)      NOT NULL AUTO_INCREMENT,
    `conversacion_id`  int(11)      NOT NULL,
    `remitente_id`     int(11)      DEFAULT NULL,
    `remitente_tipo`   enum('empresa','ministerio','sistema') NOT NULL,
    `contenido`        mediumtext   NOT NULL,
    `es_borrador`      tinyint(1)   NOT NULL DEFAULT 0,
    `leido_at`         timestamp    DEFAULT NULL,
    `created_at`       timestamp    NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    INDEX `idx_conv_created`   (`conversacion_id`, `created_at`),
    INDEX `idx_conv_no_leidos` (`conversacion_id`, `leido_at`),
    INDEX `idx_remitente`      (`remitente_id`, `es_borrador`),
    CONSTRAINT `fk_msg_conversacion`
        FOREIGN KEY (`conversacion_id`) REFERENCES `conversaciones` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_remitente`
        FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `adjuntos_mensajes` (
    `id`              int(11)        NOT NULL AUTO_INCREMENT,
    `mensaje_id`      int(11)        NOT NULL,
    `archivo_url`     varchar(500)   NOT NULL COMMENT 'Path local o URL Cloudinary',
    `archivo_nombre`  varchar(255)   NOT NULL,
    `archivo_tipo`    varchar(100)   NOT NULL COMMENT 'MIME type',
    `archivo_tamano`  int(10) unsigned NOT NULL COMMENT 'Bytes',
    `created_at`      timestamp      NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    INDEX `idx_mensaje` (`mensaje_id`),
    CONSTRAINT `fk_adj_mensaje`
        FOREIGN KEY (`mensaje_id`) REFERENCES `mensajes_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comunicado_visto` (
    `conversacion_id` int(11)    NOT NULL,
    `empresa_id`      int(11)    NOT NULL,
    `leido_at`        timestamp  DEFAULT NULL,
    `archivado`       tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`conversacion_id`, `empresa_id`),
    INDEX `idx_empresa_leido` (`empresa_id`, `leido_at`),
    CONSTRAINT `fk_cv_conversacion`
        FOREIGN KEY (`conversacion_id`) REFERENCES `conversaciones` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cv_empresa`
        FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plantillas_respuesta` (
    `id`         int(11)      NOT NULL AUTO_INCREMENT,
    `titulo`     varchar(120) NOT NULL,
    `contenido`  text         NOT NULL,
    `categoria`  enum('tramite','consulta','reclamo','comunicado','formulario','sistema','general') NOT NULL DEFAULT 'general',
    `orden`      int(11)      NOT NULL DEFAULT 0,
    `activa`     tinyint(1)   NOT NULL DEFAULT 1,
    `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    INDEX `idx_activa_orden` (`activa`, `orden`, `titulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `plantillas_respuesta` (`titulo`, `contenido`, `categoria`, `orden`) VALUES
('Recibimos su consulta',
 'Estimado/a,\n\nHemos recibido su consulta y será derivada al área correspondiente.\n\nLe responderemos a la brevedad.\n\nSaludos cordiales,\nMinisterio de Industria',
 'consulta', 1),
('Documentación requerida',
 'Estimado/a,\n\nPara continuar con su trámite necesitamos que adjunte la siguiente documentación:\n\n- \n- \n\nQuedamos a disposición.\n\nSaludos cordiales,\nMinisterio de Industria',
 'tramite', 2),
('Trámite aprobado',
 'Estimado/a,\n\nNos complace informarle que su trámite ha sido aprobado.\n\nSaludos cordiales,\nMinisterio de Industria',
 'tramite', 3),
('Formulario con observaciones',
 'Estimado/a,\n\nHemos revisado su declaración de datos y encontramos las siguientes observaciones:\n\n- \n\nPor favor corrija y reenvíe.\n\nSaludos cordiales,\nMinisterio de Industria',
 'formulario', 4);

-- ============================================================
-- ÍNDICES, PKs Y AUTO_INCREMENT (tablas sin inline PK)
-- ============================================================

ALTER TABLE `rubros`
    ADD PRIMARY KEY (`id`);
ALTER TABLE `rubros`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

ALTER TABLE `ubicaciones`
    ADD PRIMARY KEY (`id`);
ALTER TABLE `ubicaciones`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `configuracion_sitio`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `clave` (`clave`);
ALTER TABLE `configuracion_sitio`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `banners_home`
    ADD PRIMARY KEY (`id`);
ALTER TABLE `banners_home`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `formularios_config`
    ADD PRIMARY KEY (`id`);
ALTER TABLE `formularios_config`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `login_attempts`
    ADD PRIMARY KEY (`ip`),
    ADD INDEX `idx_bloqueado` (`bloqueado_hasta`);

ALTER TABLE `password_reset_requests`
    ADD PRIMARY KEY (`id`),
    ADD INDEX `idx_ip_fecha` (`ip`, `created_at`);
ALTER TABLE `password_reset_requests`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `email` (`email`),
    ADD KEY `idx_email` (`email`),
    ADD KEY `idx_rol`   (`rol`);
ALTER TABLE `usuarios`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

ALTER TABLE `empresas`
    ADD PRIMARY KEY (`id`),
    ADD KEY `usuario_id`     (`usuario_id`),
    ADD KEY `idx_rubro`      (`rubro`),
    ADD KEY `idx_ubicacion`  (`ubicacion`),
    ADD KEY `idx_estado`     (`estado`),
    ADD KEY `idx_visitas`    (`visitas`);
ALTER TABLE `empresas`
    ADD FULLTEXT KEY `idx_busqueda` (`nombre`, `razon_social`, `descripcion`, `rubro`);
ALTER TABLE `empresas`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

ALTER TABLE `datos_empresa`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `uk_empresa_periodo` (`empresa_id`, `periodo`),
    ADD KEY `revisado_por` (`revisado_por`),
    ADD KEY `idx_periodo`  (`periodo`),
    ADD KEY `idx_estado`   (`estado`);
ALTER TABLE `datos_empresa`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `publicaciones`
    ADD PRIMARY KEY (`id`),
    ADD KEY `empresa_id`    (`empresa_id`),
    ADD KEY `usuario_id`    (`usuario_id`),
    ADD KEY `aprobado_por`  (`aprobado_por`),
    ADD KEY `idx_estado`    (`estado`),
    ADD KEY `idx_publicado` (`publicado`),
    ADD KEY `idx_fecha`     (`fecha_publicacion`);
ALTER TABLE `publicaciones`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `archivos_publicacion`
    ADD PRIMARY KEY (`id`),
    ADD KEY `publicacion_id` (`publicacion_id`);
ALTER TABLE `archivos_publicacion`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `mensajes`
    ADD PRIMARY KEY (`id`),
    ADD KEY `remitente_id`     (`remitente_id`),
    ADD KEY `empresa_id`       (`empresa_id`),
    ADD KEY `mensaje_padre_id` (`mensaje_padre_id`),
    ADD KEY `idx_destinatario` (`destinatario_id`),
    ADD KEY `idx_leido`        (`leido`);
ALTER TABLE `mensajes`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notificaciones`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_usuario_leida` (`usuario_id`, `leida`);
ALTER TABLE `notificaciones`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `log_actividad`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_usuario` (`usuario_id`),
    ADD KEY `idx_empresa` (`empresa_id`),
    ADD KEY `idx_fecha`   (`created_at`);
ALTER TABLE `log_actividad`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `visitas_empresa`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_empresa_fecha` (`empresa_id`, `created_at`);
ALTER TABLE `visitas_empresa`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `respuestas_formulario`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `uk_form_empresa` (`formulario_id`, `empresa_id`),
    ADD KEY `empresa_id` (`empresa_id`);
ALTER TABLE `respuestas_formulario`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ============================================================
-- CLAVES FORÁNEAS
-- ============================================================

ALTER TABLE `empresas`
    ADD CONSTRAINT `empresas_ibfk_1`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `datos_empresa`
    ADD CONSTRAINT `datos_empresa_ibfk_1`
        FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `datos_empresa_ibfk_2`
        FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `publicaciones`
    ADD CONSTRAINT `publicaciones_ibfk_1`
        FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `publicaciones_ibfk_2`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `publicaciones_ibfk_3`
        FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `archivos_publicacion`
    ADD CONSTRAINT `archivos_publicacion_ibfk_1`
        FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE;

ALTER TABLE `mensajes`
    ADD CONSTRAINT `mensajes_ibfk_1`
        FOREIGN KEY (`remitente_id`)    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `mensajes_ibfk_2`
        FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `mensajes_ibfk_3`
        FOREIGN KEY (`empresa_id`)      REFERENCES `empresas` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `mensajes_ibfk_4`
        FOREIGN KEY (`mensaje_padre_id`) REFERENCES `mensajes` (`id`) ON DELETE SET NULL;

ALTER TABLE `notificaciones`
    ADD CONSTRAINT `notificaciones_ibfk_1`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `visitas_empresa`
    ADD CONSTRAINT `visitas_empresa_ibfk_1`
        FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

ALTER TABLE `respuestas_formulario`
    ADD CONSTRAINT `respuestas_formulario_ibfk_1`
        FOREIGN KEY (`formulario_id`) REFERENCES `formularios_config` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `respuestas_formulario_ibfk_2`
        FOREIGN KEY (`empresa_id`)    REFERENCES `empresas` (`id`)          ON DELETE CASCADE;

-- ============================================================
-- VISTAS
-- ============================================================

CREATE OR REPLACE VIEW `v_empresas_completas` AS
SELECT
    e.id, e.usuario_id, e.nombre, e.razon_social, e.cuit, e.rubro,
    e.descripcion, e.ubicacion, e.direccion, e.latitud, e.longitud,
    e.telefono, e.email_contacto, e.contacto_nombre,
    e.sitio_web, e.facebook, e.instagram, e.linkedin,
    e.logo, e.imagen_portada, e.estado,
    e.perfil_completo, e.verificada, e.visitas,
    e.created_at, e.updated_at,
    de.dotacion_total, de.empleados_masculinos, de.empleados_femeninos,
    de.consumo_energia, de.consumo_agua,
    de.exporta, de.importa, de.emisiones_co2,
    de.periodo AS ultimo_periodo
FROM empresas e
LEFT JOIN datos_empresa de
    ON  e.id = de.empresa_id
    AND de.periodo = (
        SELECT MAX(periodo) FROM datos_empresa WHERE empresa_id = e.id
    );

CREATE OR REPLACE VIEW `v_estadisticas_generales` AS
SELECT
    (SELECT COUNT(*) FROM empresas WHERE estado = 'activa')                       AS total_empresas_activas,
    (SELECT COUNT(*) FROM empresas)                                                AS total_empresas,
    (SELECT COALESCE(SUM(de.dotacion_total), 0)
     FROM datos_empresa de
     JOIN empresas e ON de.empresa_id = e.id
     WHERE e.estado = 'activa'
       AND de.periodo = (SELECT MAX(periodo) FROM datos_empresa WHERE empresa_id = de.empresa_id)
    )                                                                              AS total_empleados,
    (SELECT COUNT(DISTINCT rubro) FROM empresas WHERE estado = 'activa')          AS total_rubros,
    (SELECT COUNT(*) FROM publicaciones WHERE publicado = 1 AND estado = 'aprobado') AS total_publicaciones;

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- FIN — parque_industrial_v2.sql
-- ============================================================
