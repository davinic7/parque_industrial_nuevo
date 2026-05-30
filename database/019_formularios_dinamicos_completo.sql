-- =============================================================
-- MIGRACIÓN 019: Formularios Dinámicos — tablas completas
-- Fecha: 2026-05-28
-- Compatible con MySQL 8.4 (sin inline FK — evita errno 150)
-- Ejecutar DESPUÉS de importar parque_industrial_v2.sql
-- =============================================================

USE parque_industrial;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. FORMULARIOS DINÁMICOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `formularios_dinamicos` (
    `id`          int NOT NULL AUTO_INCREMENT,
    `titulo`      varchar(255) NOT NULL,
    `descripcion` text         DEFAULT NULL,
    `estado`      enum('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
    `creado_por`  int          DEFAULT NULL,
    `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at`  timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_fd_estado`     (`estado`),
    KEY `idx_fd_creado_por` (`creado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. FORMULARIO_PREGUNTAS — extender enum + agregar min/max
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `formulario_preguntas` (
    `id`            int NOT NULL AUTO_INCREMENT,
    `formulario_id` int NOT NULL,
    `tipo`          enum('texto','textarea','numero','fecha','select','radio','checkbox','tabla','archivo','direccion') NOT NULL DEFAULT 'texto',
    `etiqueta`      varchar(255) NOT NULL,
    `ayuda`         varchar(255) DEFAULT NULL,
    `requerido`     tinyint(1)   NOT NULL DEFAULT 0,
    `opciones`      longtext     DEFAULT NULL,
    `min_valor`     decimal(15,4) DEFAULT NULL,
    `max_valor`     decimal(15,4) DEFAULT NULL,
    `orden`         int          NOT NULL DEFAULT 0,
    `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_fp_formulario` (`formulario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existe, asegurar las columnas extra
ALTER TABLE `formulario_preguntas`
    MODIFY COLUMN `tipo`
        enum('texto','textarea','numero','fecha','select','radio','checkbox','tabla','archivo','direccion')
        NOT NULL DEFAULT 'texto';

ALTER TABLE `formulario_preguntas`
    ADD COLUMN IF NOT EXISTS `min_valor` decimal(15,4) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `max_valor` decimal(15,4) DEFAULT NULL;

-- ------------------------------------------------------------
-- 3. FORMULARIO_RESPUESTAS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `formulario_respuestas` (
    `id`            int NOT NULL AUTO_INCREMENT,
    `formulario_id` int NOT NULL,
    `empresa_id`    int NOT NULL,
    `usuario_id`    int DEFAULT NULL,
    `estado`        enum('borrador','enviado') NOT NULL DEFAULT 'borrador',
    `respuestas`    longtext NOT NULL,
    `ip`            varchar(45)  DEFAULT NULL,
    `enviado_at`    datetime     DEFAULT NULL,
    `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at`    timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_fr_formulario_empresa` (`formulario_id`, `empresa_id`),
    KEY `idx_fr_estado`             (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. FORMULARIO_ENVIOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `formulario_envios` (
    `id`                  int NOT NULL AUTO_INCREMENT,
    `formulario_id`       int NOT NULL,
    `tipo_filtro`         varchar(40) NOT NULL DEFAULT 'todos',
    `filtros_json`        text        DEFAULT NULL,
    `total_destinatarios` int         NOT NULL DEFAULT 0,
    `fecha_limite`        date        DEFAULT NULL,
    `enviado_por`         int         DEFAULT NULL,
    `created_at`          timestamp   NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_fe_formulario`  (`formulario_id`),
    KEY `idx_fe_enviado_por` (`enviado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. FORMULARIO_DESTINATARIOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `formulario_destinatarios` (
    `id`                 int NOT NULL AUTO_INCREMENT,
    `envio_id`           int NOT NULL,
    `empresa_id`         int NOT NULL,
    `notificado`         tinyint(1) NOT NULL DEFAULT 0,
    `fecha_notificacion` datetime   DEFAULT NULL,
    `plazo_hasta`        date       DEFAULT NULL,
    `respondido`         tinyint(1) NOT NULL DEFAULT 0,
    `fecha_respuesta`    datetime   DEFAULT NULL,
    `created_at`         timestamp  NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_dest_envio_empresa` (`envio_id`, `empresa_id`),
    KEY `idx_fd_empresa`    (`empresa_id`),
    KEY `idx_fd_respondido` (`respondido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. Tablas del sistema de comunicaciones (fallaron por timestamp)
--    Recrear si no existen, sin DEFAULT NULL en TIMESTAMP
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `conversaciones` (
    `id`               int NOT NULL AUTO_INCREMENT,
    `titulo`           varchar(200) NOT NULL,
    `empresa_id`       int          DEFAULT NULL,
    `iniciada_por`     enum('empresa','ministerio','sistema') NOT NULL,
    `categoria`        enum('tramite','consulta','reclamo','comunicado','formulario','sistema') NOT NULL DEFAULT 'consulta',
    `estado`           enum('abierta','cerrada','archivada') NOT NULL DEFAULT 'abierta',
    `referencia_tipo`  varchar(40)  DEFAULT NULL,
    `referencia_id`    int          DEFAULT NULL,
    `ultimo_mensaje_at` datetime    DEFAULT NULL,
    `created_at`       timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at`       timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_conv_referencia` (`referencia_tipo`, `referencia_id`),
    KEY `idx_empresa_estado`  (`empresa_id`, `estado`),
    KEY `idx_estado_ultimo`   (`estado`),
    KEY `idx_categoria`       (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mensajes_v2` (
    `id`               int NOT NULL AUTO_INCREMENT,
    `conversacion_id`  int NOT NULL,
    `remitente_id`     int DEFAULT NULL,
    `remitente_tipo`   enum('empresa','ministerio','sistema') NOT NULL,
    `contenido`        mediumtext NOT NULL,
    `es_borrador`      tinyint(1) NOT NULL DEFAULT 0,
    `leido_at`         datetime   DEFAULT NULL,
    `created_at`       timestamp  NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_conv_created`   (`conversacion_id`, `created_at`),
    KEY `idx_conv_no_leidos` (`conversacion_id`),
    KEY `idx_remitente`      (`remitente_id`, `es_borrador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comunicado_visto` (
    `conversacion_id` int NOT NULL,
    `empresa_id`      int NOT NULL,
    `leido_at`        datetime   DEFAULT NULL,
    `archivado`       tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`conversacion_id`, `empresa_id`),
    KEY `idx_empresa_leido` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas adicionales que pueden faltar
CREATE TABLE IF NOT EXISTS `solicitudes_proyecto` (
    `id`               int NOT NULL AUTO_INCREMENT,
    `nombre`           varchar(255) NOT NULL,
    `email`            varchar(255) NOT NULL,
    `empresa_proyecto` varchar(255) DEFAULT NULL,
    `descripcion`      text         DEFAULT NULL,
    `rubro`            varchar(100) DEFAULT NULL,
    `telefono`         varchar(50)  DEFAULT NULL,
    `estado`           enum('nueva','en_carpeta','eliminada') NOT NULL DEFAULT 'nueva',
    `observaciones`    text         DEFAULT NULL,
    `archivos`         text         DEFAULT NULL,
    `created_at`       timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated_at`       timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lotes` (
    `id`         int NOT NULL AUTO_INCREMENT,
    `nombre`     varchar(100) NOT NULL,
    `zona`       varchar(100) DEFAULT NULL,
    `superficie` decimal(10,2) DEFAULT NULL,
    `estado`     enum('disponible','ocupado','reservado') DEFAULT 'disponible',
    `empresa_id` int DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `empresa_imagenes` (
    `id`         int NOT NULL AUTO_INCREMENT,
    `empresa_id` int NOT NULL,
    `url`        varchar(500) NOT NULL,
    `nombre`     varchar(255) DEFAULT NULL,
    `orden`      int NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `publicacion_likes` (
    `id`             int NOT NULL AUTO_INCREMENT,
    `publicacion_id` int NOT NULL,
    `ip`             varchar(45) NOT NULL,
    `created_at`     timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_like_ip` (`publicacion_id`, `ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Verificación final
-- ------------------------------------------------------------
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'parque_industrial'
ORDER BY TABLE_NAME;
