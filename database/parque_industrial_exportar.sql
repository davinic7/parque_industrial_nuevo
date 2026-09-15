-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: parque_industrial
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `adjuntos_mensajes`
--

DROP TABLE IF EXISTS `adjuntos_mensajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adjuntos_mensajes`
--

LOCK TABLES `adjuntos_mensajes` WRITE;
/*!40000 ALTER TABLE `adjuntos_mensajes` DISABLE KEYS */;
/*!40000 ALTER TABLE `adjuntos_mensajes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archivos_publicacion`
--

DROP TABLE IF EXISTS `archivos_publicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archivos_publicacion`
--

LOCK TABLES `archivos_publicacion` WRITE;
/*!40000 ALTER TABLE `archivos_publicacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `archivos_publicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners_home`
--

DROP TABLE IF EXISTS `banners_home`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners_home`
--

LOCK TABLES `banners_home` WRITE;
/*!40000 ALTER TABLE `banners_home` DISABLE KEYS */;
/*!40000 ALTER TABLE `banners_home` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comunicado_visto`
--

DROP TABLE IF EXISTS `comunicado_visto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comunicado_visto` (
  `conversacion_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `leido_at` datetime DEFAULT NULL,
  `archivado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`conversacion_id`,`empresa_id`),
  KEY `idx_empresa_leido` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comunicado_visto`
--

LOCK TABLES `comunicado_visto` WRITE;
/*!40000 ALTER TABLE `comunicado_visto` DISABLE KEYS */;
/*!40000 ALTER TABLE `comunicado_visto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_sitio`
--

DROP TABLE IF EXISTS `configuracion_sitio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_sitio`
--

LOCK TABLES `configuracion_sitio` WRITE;
/*!40000 ALTER TABLE `configuracion_sitio` DISABLE KEYS */;
INSERT INTO `configuracion_sitio` VALUES (1,'sitio_nombre','Parque Industrial de Catamarca','text','general','Nombre del sitio','2026-09-01 00:27:05'),(2,'sitio_descripcion','Portal del Parque Industrial de la Provincia de Catamarca','textarea','general','Descripción del sitio','2026-09-01 00:27:05'),(3,'sitio_email','contacto@parqueindustrial.gob.ar','text','contacto','Email de contacto','2026-09-01 00:27:05'),(4,'sitio_telefono','(0383) 4123456','text','contacto','Teléfono de contacto','2026-09-01 00:27:05'),(5,'sitio_direccion','San Fernando del Valle de Catamarca, Argentina','text','contacto','Dirección física','2026-09-01 00:27:05'),(6,'mapa_lat_centro','-28.4696','text','mapa','Latitud centro del mapa','2026-09-01 00:27:05'),(7,'mapa_lng_centro','-65.7795','text','mapa','Longitud centro del mapa','2026-09-01 00:27:05'),(8,'mapa_zoom_inicial','12','number','mapa','Zoom inicial del mapa','2026-09-01 00:27:05'),(9,'redes_facebook','https://facebook.com/parqueindustrialcatamarca','text','redes','Facebook','2026-09-01 00:27:05'),(10,'redes_instagram','https://instagram.com/parqueindustrialcatamarca','text','redes','Instagram','2026-09-01 00:27:05'),(11,'redes_twitter','','text','redes','Twitter/X','2026-09-01 00:27:05'),(12,'texto_sobre_nosotros','El Parque Industrial de Catamarca es un polo de desarrollo productivo estratégico para la región del NOA, ubicado en la localidad de El Pantanillo. Cuenta con infraestructura moderna, servicios de agua, gas, energía eléctrica y conectividad, y alberga empresas de diversos rubros industriales.','textarea','contenido','Texto sobre nosotros','2026-09-01 00:27:05'),(13,'mostrar_estadisticas_publicas','1','boolean','privacidad','Mostrar estadísticas al público','2026-09-01 00:27:05');
/*!40000 ALTER TABLE `configuracion_sitio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversaciones`
--

DROP TABLE IF EXISTS `conversaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversaciones`
--

LOCK TABLES `conversaciones` WRITE;
/*!40000 ALTER TABLE `conversaciones` DISABLE KEYS */;
INSERT INTO `conversaciones` VALUES (1,'Nuevo formulario: prueba',100,'ministerio','formulario','abierta','formulario_dinamico',1,'2026-05-29 01:21:30','2026-09-01 00:27:06','2026-09-01 00:27:06'),(2,'Consulta sobre habilitación de galpón',1,'empresa','consulta','abierta',NULL,NULL,'2026-06-06 15:56:56','2026-09-01 00:27:06','2026-09-01 00:27:06');
/*!40000 ALTER TABLE `conversaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `datos_empresa`
--

DROP TABLE IF EXISTS `datos_empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresa_periodo` (`empresa_id`,`periodo`),
  KEY `revisado_por` (`revisado_por`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `datos_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `datos_empresa_ibfk_2` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_empresa`
--

LOCK TABLES `datos_empresa` WRITE;
/*!40000 ALTER TABLE `datos_empresa` DISABLE KEYS */;
INSERT INTO `datos_empresa` VALUES (1,100,'2026-Q2',55,49,6,0,'10000',85.00,'8500','',25000.00,500.00,200.00,1,1,1,1,0,'','',NULL,0,'','',NULL,2500.0000,'Combustibles',NULL,NULL,NULL,NULL,NULL,'enviado',1,'2026-06-18 09:27:55','::1',NULL,NULL,NULL,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(2,102,'2026-Q1',42,36,6,0,'120000 tn/año',78.50,'7800','toneladas/mes',185000.00,4200.00,12500.00,1,0,1,1,0,NULL,NULL,NULL,1,'Clinker, yeso sintético','Brasil, China',850000.00,48.2000,'Proceso de calcinación (horno rotativo)',2800000.00,1500000.00,1300000.00,'grande','ISO 9001:2015, IRAM 50000','aprobado',1,'2026-04-05 10:00:00',NULL,NULL,NULL,NULL,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(3,103,'2026-Q1',27,23,4,0,'500 tn estructuras/año',65.00,'28','toneladas/mes',72000.00,850.00,3200.00,1,0,1,1,1,'Estructuras metálicas, carpintería de aluminio','Chile',320000.00,1,'Acero laminado, electrodos de soldadura','Brasil',180000.00,18.5000,'Proceso de soldadura y corte térmico',950000.00,680000.00,270000.00,'mediana','ISO 3834-2, AWS D1.1','aprobado',1,'2026-04-08 11:00:00',NULL,NULL,NULL,NULL,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(4,104,'2026-Q1',18,5,13,0,'80 tn dulces/año',55.00,'6','toneladas/mes',28000.00,1200.00,1800.00,1,0,1,1,1,'Dulce de membrillo, mermeladas artesanales','Brasil, Uruguay',95000.00,0,NULL,NULL,NULL,4.8000,'Cocción industrial (gas natural)',380000.00,210000.00,170000.00,'','SENASA Hab. Nacional, BPM ANMAT','aprobado',1,'2026-04-10 09:30:00',NULL,NULL,NULL,NULL,'2026-09-01 00:27:06','2026-09-01 00:27:06');
/*!40000 ALTER TABLE `datos_empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empresa_imagenes`
--

DROP TABLE IF EXISTS `empresa_imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empresa_imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresa_imagenes`
--

LOCK TABLES `empresa_imagenes` WRITE;
/*!40000 ALTER TABLE `empresa_imagenes` DISABLE KEYS */;
INSERT INTO `empresa_imagenes` VALUES (10,102,'empresa_102_gal_1.jpg','Planta de molienda',1,'2026-09-01 00:27:06'),(11,102,'empresa_102_gal_2.jpg','Área de envasado',2,'2026-09-01 00:27:06'),(12,102,'empresa_102_gal_3.jpg','Laboratorio de calidad',3,'2026-09-01 00:27:06'),(13,103,'empresa_103_gal_1.webp','Taller de soldadura',1,'2026-09-01 00:27:06'),(14,104,'empresa_104_gal_1.jpg','Línea de producción',1,'2026-09-01 00:27:06'),(15,104,'empresa_104_gal_2.jpg','Productos terminados',2,'2026-09-01 00:27:06'),(16,105,'empresa_105_gal_1.jpg','Sala de faena',1,'2026-09-01 00:27:06'),(17,105,'empresa_105_gal_2.jpg','Cámara frigorífica',2,'2026-09-01 00:27:06'),(18,106,'empresa_106_gal_1.jpg','Planta de clasificación',1,'2026-09-01 00:27:06'),(19,106,'empresa_106_gal_2.jpg','Prensa de reciclado',2,'2026-09-01 00:27:06');
/*!40000 ALTER TABLE `empresa_imagenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empresas`
--

DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `lote_declarado` varchar(50) DEFAULT NULL COMMENT 'Número de lote declarado por la empresa',
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
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas`
--

LOCK TABLES `empresas` WRITE;
/*!40000 ALTER TABLE `empresas` DISABLE KEYS */;
INSERT INTO `empresas` VALUES (1,3,'Empresa Demo S.R.L.','','','Textil','Empresa de manufactura especializada en productos industriales para la región.','PI El Pantanillo','',-28.53000000,-65.80200000,'3834000001','','Juan Pérez','','','',NULL,NULL,NULL,'activa',0,0,0,'L-05','pendiente','2026-09-01 00:27:05','2026-09-01 00:27:05'),(100,201,'prueba',NULL,NULL,'Textil',NULL,'Catamarca Capital',NULL,-28.46960000,-65.77950000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'activa',0,0,7,NULL,'sin_solicitud','2026-09-01 00:27:05','2026-09-01 00:27:05'),(101,202,'Textil del Norte S.A.','','','Textil','Empresa textil especializada en producción de tejidos industriales para la región de Catamarca.','PI El Pantanillo','Parque Industrial El Pantanillo, Catamarca',-28.53650000,-65.79650000,'3834001122','','','','','',NULL,NULL,NULL,'activa',0,0,0,NULL,'sin_solicitud','2026-09-01 00:27:05','2026-09-01 00:27:05'),(102,203,'Catamarca Cementos','Catamarca Cementos S.A.','30-71234567-1','Construcción','Producción y comercialización de cemento portland y materiales de construcción para obra civil e industrial en la región del NOA. Planta de molienda con capacidad de 120.000 toneladas anuales.','PI El Pantanillo','Lote 12, Parque Industrial El Pantanillo, Catamarca',-28.53950000,-65.80550000,'383-4512340','contacto@catamarcacementos.com.ar','Ing. Carlos Medina','www.catamarcacementos.com.ar','https://facebook.com/catamarcacementos','https://instagram.com/catamarcacementos',NULL,'empresa_102_logo.png',NULL,'activa',1,1,4,'12','asignado','2026-09-01 00:27:05','2026-09-01 13:07:07'),(103,204,'NorAnd Metalúrgica','NorAnd Metalúrgica S.R.L.','30-71345678-9','Metalúrgica','Fabricación de estructuras metálicas, carpintería de aluminio y soldadura industrial. Proveedor de proyectos mineros, civiles y del sector energético en Catamarca y provincias vecinas.','PI El Pantanillo','Lote 7, Parque Industrial El Pantanillo, Catamarca',-28.54300000,-65.79750000,'383-4523451','info@norandmetalurgica.com.ar','Roberto Giménez',NULL,NULL,'https://instagram.com/norandmetalurgica','https://linkedin.com/company/norand-metalurgica','empresa_103_logo.jpg',NULL,'activa',1,1,0,'7','asignado','2026-09-01 00:27:05','2026-09-01 13:04:25'),(104,205,'Dulces del Norte','Dulces del Norte S.A.','30-71456789-0','Dulces','Elaboración artesanal e industrial de dulces regionales, mermeladas y conservas con frutas de la región andina catamarqueña: membrillo, durazno, alcayota y nogal. Certificación SENASA habilitada.','PI El Pantanillo','Lote 3, Parque Industrial El Pantanillo, Catamarca',-28.52700000,-65.79550000,'383-4534562','ventas@dulcesdelnorte.com.ar','María Estela Quiroga','www.dulcesdelnorte.com.ar','https://facebook.com/dulcesdelnorteca','https://instagram.com/dulcesdelnorte_cat',NULL,'empresa_104_logo.png',NULL,'activa',1,1,0,'3','asignado','2026-09-01 00:27:05','2026-09-01 13:04:25'),(105,206,'Frigorífico Andino','Frigorífico Andino S.R.L.','30-71567890-1','Frigorífico','Faena y procesamiento de carne bovina y caprina con habilitación SENASA nacional. Capacidad de 200 cabezas diarias. Distribución regional en frío y exportación a mercados del Mercosur.','PI El Pantanillo','Lote 18, Parque Industrial El Pantanillo, Catamarca',-28.53400000,-65.80100000,'383-4545673','administracion@frigoandinoca.com.ar','Luis Alberto Soria',NULL,'https://facebook.com/frigorifico.andino',NULL,NULL,'empresa_105_logo.webp',NULL,'activa',1,1,0,'18','asignado','2026-09-01 00:27:05','2026-09-01 13:04:25'),(106,207,'ReciclaCAT','ReciclaCAT S.R.L.','30-71678901-2','Reciclado','Gestión integral de residuos sólidos industriales: acopio, clasificación y recuperación de plásticos, metales, papel y vidrio. Planta certificada por el Ministerio de Ambiente de Catamarca. Economía circular aplicada al sector productivo.','PI El Pantanillo','Lote 22, Parque Industrial El Pantanillo, Catamarca',-28.54500000,-65.80150000,'383-4556784','contacto@reciclacat.com.ar','Ana Paula Ferreyra','www.reciclacat.com.ar',NULL,'https://instagram.com/reciclacat','https://linkedin.com/company/reciclacat','empresa_106_logo.avif',NULL,'activa',1,1,0,'22','asignado','2026-09-01 00:27:05','2026-09-01 13:04:25');
/*!40000 ALTER TABLE `empresas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formulario_destinatarios`
--

DROP TABLE IF EXISTS `formulario_destinatarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formulario_destinatarios`
--

LOCK TABLES `formulario_destinatarios` WRITE;
/*!40000 ALTER TABLE `formulario_destinatarios` DISABLE KEYS */;
INSERT INTO `formulario_destinatarios` VALUES (1,1,100,1,'2026-05-29 01:16:56','2026-06-07',1,'2026-05-29 01:19:54','2026-09-01 00:27:06'),(2,2,102,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 10:30:00','2026-09-01 00:27:06'),(3,2,103,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 11:00:00','2026-09-01 00:27:06'),(4,2,104,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 14:00:00','2026-09-01 00:27:06'),(5,2,105,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-14 09:30:00','2026-09-01 00:27:06'),(6,2,106,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 16:00:00','2026-09-01 00:27:06'),(7,3,102,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 11:00:00','2026-09-01 00:27:06'),(8,3,103,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 12:00:00','2026-09-01 00:27:06'),(9,3,104,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 15:00:00','2026-09-01 00:27:06'),(10,3,105,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-14 10:00:00','2026-09-01 00:27:06'),(11,3,106,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 17:00:00','2026-09-01 00:27:06'),(12,4,102,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 12:00:00','2026-09-01 00:27:06'),(13,4,103,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 13:00:00','2026-09-01 00:27:06'),(14,4,104,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 16:00:00','2026-09-01 00:27:06'),(15,4,105,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-14 11:00:00','2026-09-01 00:27:06'),(16,4,106,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 18:00:00','2026-09-01 00:27:06'),(17,5,102,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 13:00:00','2026-09-01 00:27:06'),(18,5,103,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 14:00:00','2026-09-01 00:27:06'),(19,5,104,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 17:00:00','2026-09-01 00:27:06'),(20,5,105,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-14 12:00:00','2026-09-01 00:27:06'),(21,5,106,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 19:00:00','2026-09-01 00:27:06'),(22,6,102,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 14:00:00','2026-09-01 00:27:06'),(23,6,103,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 15:00:00','2026-09-01 00:27:06'),(24,6,104,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-12 18:00:00','2026-09-01 00:27:06'),(25,6,105,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-14 13:00:00','2026-09-01 00:27:06'),(26,6,106,1,'2026-06-10 09:00:00','2026-07-01',1,'2026-06-13 20:00:00','2026-09-01 00:27:06');
/*!40000 ALTER TABLE `formulario_destinatarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formulario_envios`
--

DROP TABLE IF EXISTS `formulario_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formulario_envios`
--

LOCK TABLES `formulario_envios` WRITE;
/*!40000 ALTER TABLE `formulario_envios` DISABLE KEYS */;
INSERT INTO `formulario_envios` VALUES (1,1,'todos','{\"rubros\":[],\"ubicaciones\":[],\"estados\":[\"activa\"],\"empresa_ids\":[]}',1,'2026-06-07',1,'2026-09-01 00:27:06'),(2,3,'todos','{\"rubros\":[],\"ubicaciones\":[],\"estados\":[\"activa\"],\"empresa_ids\":[102,103,104,105,106]}',5,'2026-07-01',1,'2026-09-01 00:27:06'),(3,4,'todos','{\"rubros\":[],\"ubicaciones\":[],\"estados\":[\"activa\"],\"empresa_ids\":[102,103,104,105,106]}',5,'2026-07-01',1,'2026-09-01 00:27:06'),(4,5,'todos','{\"rubros\":[],\"ubicaciones\":[],\"estados\":[\"activa\"],\"empresa_ids\":[102,103,104,105,106]}',5,'2026-07-01',1,'2026-09-01 00:27:06'),(5,6,'todos','{\"rubros\":[],\"ubicaciones\":[],\"estados\":[\"activa\"],\"empresa_ids\":[102,103,104,105,106]}',5,'2026-07-01',1,'2026-09-01 00:27:06'),(6,7,'todos','{\"rubros\":[],\"ubicaciones\":[],\"estados\":[\"activa\"],\"empresa_ids\":[102,103,104,105,106]}',5,'2026-07-01',1,'2026-09-01 00:27:06');
/*!40000 ALTER TABLE `formulario_envios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formulario_preguntas`
--

DROP TABLE IF EXISTS `formulario_preguntas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formulario_preguntas`
--

LOCK TABLES `formulario_preguntas` WRITE;
/*!40000 ALTER TABLE `formulario_preguntas` DISABLE KEYS */;
INSERT INTO `formulario_preguntas` VALUES (10,1,'texto','texto corto','prueba',0,NULL,1,'2026-09-01 00:27:06',NULL,NULL),(11,1,'textarea','parrafo','parrafo',0,NULL,2,'2026-09-01 00:27:06',NULL,NULL),(12,1,'numero','numero','numero',0,NULL,3,'2026-09-01 00:27:06',1.0000,1.0000),(13,1,'fecha','fecha','fecha',0,NULL,4,'2026-09-01 00:27:06',NULL,NULL),(14,1,'select','lista desplegable','probar comas',0,'{\"items\":[\"1\",\"2\",\"3\",\"4\",\"5\"]}',5,'2026-09-01 00:27:06',NULL,NULL),(15,1,'radio','opcion unica','',0,'{\"items\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]}',6,'2026-09-01 00:27:06',NULL,NULL),(16,1,'checkbox','multiples','',0,'{\"items\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]}',7,'2026-09-01 00:27:06',NULL,NULL),(17,1,'texto','tablas','',0,'{\"cols\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"],\"rows\":[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]}',8,'2026-09-01 00:27:06',NULL,NULL),(18,1,'archivo','archivo imagen','',0,NULL,9,'2026-09-01 00:27:06',NULL,NULL),(19,1,'direccion','direcccion','',0,NULL,10,'2026-09-01 00:27:06',NULL,NULL),(20,3,'radio','¿Cuenta con un responsable de Higiene y Seguridad designado?','Indique si existe una persona formalmente a cargo.',1,'{\"items\":[\"Sí\",\"No\"]}',1,'2026-09-01 00:27:06',NULL,NULL),(21,3,'numero','Cantidad de accidentes laborales en el último trimestre','Incluir incidentes con y sin baja médica.',1,NULL,2,'2026-09-01 00:27:06',NULL,NULL),(22,3,'checkbox','Elementos de Protección Personal (EPP) utilizados','Marque todos los EPP que usa su personal de forma regular.',1,'{\"items\":[\"Casco\",\"Guantes\",\"Calzado de seguridad\",\"Protección ocular\",\"Protección auditiva\"]}',3,'2026-09-01 00:27:06',NULL,NULL),(23,3,'fecha','Fecha de la última capacitación en emergencias','Incendios, evacuación, primeros auxilios, etc.',1,NULL,4,'2026-09-01 00:27:06',NULL,NULL),(24,3,'textarea','Observaciones generales sobre condiciones de seguridad','Describa novedades, mejoras pendientes o logros recientes.',0,NULL,5,'2026-09-01 00:27:06',NULL,NULL),(25,4,'textarea','Principales materias primas o insumos que utiliza','Liste los 3 o 4 insumos más importantes para su proceso productivo.',1,NULL,1,'2026-09-01 00:27:06',NULL,NULL),(26,4,'radio','Origen predominante de sus materias primas','Indique de dónde proviene la mayor parte de sus insumos.',1,'{\"items\":[\"Local (Catamarca)\",\"Nacional\",\"Importado\",\"Mixto\"]}',2,'2026-09-01 00:27:06',NULL,NULL),(27,4,'radio','¿Cuenta con proveedores locales de Catamarca?','',1,'{\"items\":[\"Sí\",\"No\",\"En proceso\"]}',3,'2026-09-01 00:27:06',NULL,NULL),(28,4,'numero','Consumo mensual aproximado de materia prima (en toneladas)','Si trabaja con unidades distintas, convierta a toneladas.',1,NULL,4,'2026-09-01 00:27:06',NULL,NULL),(29,4,'select','Principal dificultad en el abastecimiento de materias primas','',1,'{\"items\":[\"Precio\",\"Disponibilidad\",\"Calidad\",\"Logística\",\"Ninguna\"]}',5,'2026-09-01 00:27:06',NULL,NULL),(30,5,'numero','Cantidad total de empleados activos','Incluir personal en relación de dependencia y contratados estables.',1,NULL,1,'2026-09-01 00:27:06',NULL,NULL),(31,5,'radio','Distribución por género predominante','',1,'{\"items\":[\"Mayoría masculina\",\"Mayoría femenina\",\"Equilibrado\"]}',2,'2026-09-01 00:27:06',NULL,NULL),(32,5,'numero','Empleados con discapacidad registrados','Poner 0 si no aplica.',1,NULL,3,'2026-09-01 00:27:06',NULL,NULL),(33,5,'numero','Empleados residentes en Catamarca Capital','',1,NULL,4,'2026-09-01 00:27:06',NULL,NULL),(34,5,'select','Nivel de formación predominante en su planta','',1,'{\"items\":[\"Primario\",\"Secundario completo\",\"Terciario o universitario\",\"Posgrado\"]}',5,'2026-09-01 00:27:06',NULL,NULL),(35,6,'numero','Cantidad de vehículos propios de la empresa','Incluir todos los rodados: autos, camionetas, camiones, maquinaria.',1,NULL,1,'2026-09-01 00:27:06',NULL,NULL),(36,6,'radio','¿Los vehículos cuentan con VTV o revisión técnica al día?','',1,'{\"items\":[\"Todos\",\"La mayoría\",\"Algunos\",\"Ninguno\"]}',2,'2026-09-01 00:27:06',NULL,NULL),(37,6,'fecha','Fecha de la última revisión interna de flota','Inspección realizada por personal propio o taller contratado.',1,NULL,3,'2026-09-01 00:27:06',NULL,NULL),(38,6,'checkbox','Tipos de vehículos que opera la empresa','',1,'{\"items\":[\"Automóviles\",\"Camionetas\",\"Camiones\",\"Maquinaria pesada\",\"Motocicletas\"]}',4,'2026-09-01 00:27:06',NULL,NULL),(39,6,'radio','¿El seguro vehicular está actualizado?','',1,'{\"items\":[\"Sí, todos\",\"Sí, parcialmente\",\"No\"]}',5,'2026-09-01 00:27:06',NULL,NULL),(40,7,'radio','¿Su empresa realiza exportaciones actualmente?','',1,'{\"items\":[\"Sí\",\"No\",\"En proceso de iniciar\"]}',1,'2026-09-01 00:27:06',NULL,NULL),(41,7,'checkbox','Rutas nacionales que utiliza habitualmente','Marque todas las que correspondan.',0,'{\"items\":[\"RN 38\",\"RN 60\",\"RN 40\",\"RN 157\",\"RN 78\"]}',2,'2026-09-01 00:27:06',NULL,NULL),(42,7,'select','Destino principal de las exportaciones','Si no exporta, deje en blanco.',0,'{\"items\":[\"Brasil\",\"Chile\",\"Uruguay\",\"Bolivia\",\"Europa\",\"Asia\",\"Otro / Mercado interno\"]}',3,'2026-09-01 00:27:06',NULL,NULL),(43,7,'numero','Cantidad de envíos al exterior por mes','Poner 0 si no exporta.',1,NULL,4,'2026-09-01 00:27:06',NULL,NULL),(44,7,'textarea','Principal obstáculo logístico para la exportación','Describa costos, trámites, infraestructura vial u otros factores.',0,NULL,5,'2026-09-01 00:27:06',NULL,NULL);
/*!40000 ALTER TABLE `formulario_preguntas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formulario_respuestas`
--

DROP TABLE IF EXISTS `formulario_respuestas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formulario_respuestas`
--

LOCK TABLES `formulario_respuestas` WRITE;
/*!40000 ALTER TABLE `formulario_respuestas` DISABLE KEYS */;
INSERT INTO `formulario_respuestas` VALUES (1,1,100,201,'borrador','{\"10\":\"corto\",\"11\":\"Texto largo de prueba para el campo párrafo.\",\"12\":\"2\",\"13\":\"2026-05-30\",\"14\":\"2\",\"15\":\"4\",\"16\":[\"3\",\"4\",\"5\"],\"19\":\"-28.53500100,-65.81239700\"}','::1','2026-05-29 01:19:57','2026-09-01 00:27:06','2026-09-01 00:27:06'),(2,3,102,203,'enviado','{\"20\":\"Sí\",\"21\":\"1\",\"22\":[\"Casco\",\"Calzado de seguridad\",\"Protección ocular\"],\"23\":\"2026-05-15\",\"24\":\"Mantenimiento preventivo de equipos completado en mayo. Sin incidentes graves en el período.\"}','127.0.0.1','2026-06-12 10:30:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(3,3,103,204,'enviado','{\"20\":\"Sí\",\"21\":\"0\",\"22\":[\"Casco\",\"Guantes\",\"Calzado de seguridad\",\"Protección ocular\",\"Protección auditiva\"],\"23\":\"2026-06-01\",\"24\":\"EPP completos en toda la planta. Cero accidentes en el período.\"}','127.0.0.1','2026-06-13 11:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(4,3,104,205,'enviado','{\"20\":\"Sí\",\"21\":\"0\",\"22\":[\"Guantes\",\"Calzado de seguridad\"],\"23\":\"2026-04-20\",\"24\":\"Personal de producción trabaja con guantes de nitrilo y calzado con puntera de acero. Sin novedades.\"}','127.0.0.1','2026-06-12 14:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(5,3,105,206,'enviado','{\"20\":\"Sí\",\"21\":\"1\",\"22\":[\"Casco\",\"Guantes\",\"Calzado de seguridad\",\"Protección auditiva\"],\"23\":\"2026-05-28\",\"24\":\"Un empleado sufrió corte leve en sala de desposte. Fue atendido en guardia y retomó actividades al día siguiente.\"}','127.0.0.1','2026-06-14 09:30:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(6,3,106,207,'enviado','{\"20\":\"Sí\",\"21\":\"0\",\"22\":[\"Guantes\",\"Calzado de seguridad\",\"Protección ocular\"],\"23\":\"2026-06-05\",\"24\":\"Se renovaron todos los EPP en mayo. Personal actualizado en manejo de residuos peligrosos.\"}','127.0.0.1','2026-06-13 16:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(7,4,102,203,'enviado','{\"25\":\"Caliza, yeso, clinker importado y puzolana volcánica local\",\"26\":\"Mixto\",\"27\":\"Sí\",\"28\":\"850\",\"29\":\"Logística\"}','127.0.0.1','2026-06-12 11:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(8,4,103,204,'enviado','{\"25\":\"Barras de acero laminado en frío, chapas galvanizadas, perfiles de aluminio y electrodos de soldadura\",\"26\":\"Nacional\",\"27\":\"Sí\",\"28\":\"45\",\"29\":\"Precio\"}','127.0.0.1','2026-06-13 12:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(9,4,104,205,'enviado','{\"25\":\"Membrillo, durazno, alcayota, azúcar refinada y envases de vidrio\",\"26\":\"Local (Catamarca)\",\"27\":\"Sí\",\"28\":\"12\",\"29\":\"Disponibilidad\"}','127.0.0.1','2026-06-12 15:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(10,4,105,206,'enviado','{\"25\":\"Ganado bovino y caprino en pie, sal gruesa, tripas naturales y envases termosellables\",\"26\":\"Local (Catamarca)\",\"27\":\"Sí\",\"28\":\"180\",\"29\":\"Ninguna\"}','127.0.0.1','2026-06-14 10:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(11,4,106,207,'enviado','{\"25\":\"Plástico PET post-consumo, cartón corrugado, metales ferrosos y aluminio para reciclado\",\"26\":\"Local (Catamarca)\",\"27\":\"Sí\",\"28\":\"65\",\"29\":\"Ninguna\"}','127.0.0.1','2026-06-13 17:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(12,5,102,203,'enviado','{\"30\":\"42\",\"31\":\"Mayoría masculina\",\"32\":\"1\",\"33\":\"38\",\"34\":\"Secundario completo\"}','127.0.0.1','2026-06-12 12:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(13,5,103,204,'enviado','{\"30\":\"27\",\"31\":\"Mayoría masculina\",\"32\":\"0\",\"33\":\"24\",\"34\":\"Secundario completo\"}','127.0.0.1','2026-06-13 13:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(14,5,104,205,'enviado','{\"30\":\"18\",\"31\":\"Mayoría femenina\",\"32\":\"0\",\"33\":\"18\",\"34\":\"Terciario o universitario\"}','127.0.0.1','2026-06-12 16:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(15,5,105,206,'enviado','{\"30\":\"35\",\"31\":\"Mayoría masculina\",\"32\":\"2\",\"33\":\"31\",\"34\":\"Secundario completo\"}','127.0.0.1','2026-06-14 11:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(16,5,106,207,'enviado','{\"30\":\"14\",\"31\":\"Equilibrado\",\"32\":\"1\",\"33\":\"13\",\"34\":\"Terciario o universitario\"}','127.0.0.1','2026-06-13 18:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(17,6,102,203,'enviado','{\"35\":\"8\",\"36\":\"Todos\",\"37\":\"2026-05-20\",\"38\":[\"Camionetas\",\"Camiones\",\"Maquinaria pesada\"],\"39\":\"Sí, todos\"}','127.0.0.1','2026-06-12 13:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(18,6,103,204,'enviado','{\"35\":\"5\",\"36\":\"Todos\",\"37\":\"2026-06-10\",\"38\":[\"Automóviles\",\"Camionetas\",\"Camiones\"],\"39\":\"Sí, todos\"}','127.0.0.1','2026-06-13 14:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(19,6,104,205,'enviado','{\"35\":\"3\",\"36\":\"Todos\",\"37\":\"2026-06-01\",\"38\":[\"Automóviles\",\"Camionetas\"],\"39\":\"Sí, todos\"}','127.0.0.1','2026-06-12 17:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(20,6,105,206,'enviado','{\"35\":\"12\",\"36\":\"La mayoría\",\"37\":\"2026-05-15\",\"38\":[\"Camionetas\",\"Camiones\"],\"39\":\"Sí, parcialmente\"}','127.0.0.1','2026-06-14 12:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(21,6,106,207,'enviado','{\"35\":\"6\",\"36\":\"Todos\",\"37\":\"2026-06-08\",\"38\":[\"Camionetas\",\"Camiones\",\"Maquinaria pesada\"],\"39\":\"Sí, todos\"}','127.0.0.1','2026-06-13 19:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(22,7,102,203,'enviado','{\"40\":\"No\",\"41\":[\"RN 38\"],\"42\":\"Otro / Mercado interno\",\"43\":\"0\",\"44\":\"No realizamos exportaciones actualmente. Toda la producción se distribuye en el mercado regional.\"}','127.0.0.1','2026-06-12 14:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(23,7,103,204,'enviado','{\"40\":\"En proceso de iniciar\",\"41\":[\"RN 38\",\"RN 60\"],\"42\":\"Chile\",\"43\":\"2\",\"44\":\"Trámites de exportación en gestión con la Cámara de Comercio de Catamarca. Falta completar certificaciones.\"}','127.0.0.1','2026-06-13 15:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(24,7,104,205,'enviado','{\"40\":\"Sí\",\"41\":[\"RN 38\",\"RN 157\"],\"42\":\"Brasil\",\"43\":\"3\",\"44\":\"Demoras en aduana por falta de personal especializado en documentación de exportación de alimentos.\"}','127.0.0.1','2026-06-12 18:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(25,7,105,206,'enviado','{\"40\":\"Sí\",\"41\":[\"RN 38\",\"RN 60\",\"RN 40\"],\"42\":\"Brasil\",\"43\":\"8\",\"44\":\"El principal problema es el costo de refrigeración en tránsito y la disponibilidad de camiones con cadena de frío.\"}','127.0.0.1','2026-06-14 13:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06'),(26,7,106,207,'enviado','{\"40\":\"No\",\"41\":[\"RN 38\"],\"42\":\"Otro / Mercado interno\",\"43\":\"0\",\"44\":\"Operamos exclusivamente en el mercado local. A futuro analizaremos exportación de materiales reciclados a destinos regionales.\"}','127.0.0.1','2026-06-13 20:00:00','2026-09-01 00:27:06','2026-09-01 00:27:06');
/*!40000 ALTER TABLE `formulario_respuestas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formularios_config`
--

DROP TABLE IF EXISTS `formularios_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formularios_config`
--

LOCK TABLES `formularios_config` WRITE;
/*!40000 ALTER TABLE `formularios_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `formularios_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formularios_dinamicos`
--

DROP TABLE IF EXISTS `formularios_dinamicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formularios_dinamicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
  `creado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `survey_json` longtext DEFAULT NULL COMMENT 'Definici├│n completa del formulario en formato SurveyJS JSON',
  PRIMARY KEY (`id`),
  KEY `idx_fd_estado` (`estado`),
  KEY `idx_fd_creado_por` (`creado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formularios_dinamicos`
--

LOCK TABLES `formularios_dinamicos` WRITE;
/*!40000 ALTER TABLE `formularios_dinamicos` DISABLE KEYS */;
INSERT INTO `formularios_dinamicos` VALUES (1,'prueba','para prueba','publicado',1,'2026-09-01 00:27:06','2026-09-01 00:27:06',NULL),(3,'Higiene y Seguridad','Relevamiento del estado de higiene y seguridad en las instalaciones de cada empresa del parque.','publicado',1,'2026-09-01 00:27:06','2026-09-01 00:27:06',NULL),(4,'Materias de Trabajo','Declaración de materias primas utilizadas, origen y principales dificultades de abastecimiento.','publicado',1,'2026-09-01 00:27:06','2026-09-01 00:27:06',NULL),(5,'Censo de Empleados','Relevamiento de la dotación de personal activo, composición y nivel de formación.','publicado',1,'2026-09-01 00:27:06','2026-09-01 00:27:06',NULL),(6,'Inspección de Vehículos Particulares','Control del estado documental y técnico de la flota vehicular de cada empresa.','publicado',1,'2026-09-01 00:27:06','2026-09-01 00:27:06',NULL),(7,'Uso de Rutas Nacionales para Exportación','Relevamiento del uso de la red vial nacional para operaciones de exportación.','publicado',1,'2026-09-01 00:27:06','2026-09-01 00:27:06',NULL);
/*!40000 ALTER TABLE `formularios_dinamicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_actividad`
--

DROP TABLE IF EXISTS `log_actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_actividad`
--

LOCK TABLES `log_actividad` WRITE;
/*!40000 ALTER TABLE `log_actividad` DISABLE KEYS */;
INSERT INTO `log_actividad` VALUES (117,2,NULL,'login','usuarios',2,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 00:29:50'),(118,2,NULL,'login','usuarios',2,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.40609.0 Chrome/148.0.7778.280 Safari/537.36 MSIX','2026-09-01 13:14:47'),(119,2,NULL,'logout','usuarios',2,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.40609.0 Chrome/148.0.7778.280 Safari/537.36 MSIX','2026-09-01 13:15:38'),(120,203,102,'login','usuarios',203,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.40609.0 Chrome/148.0.7778.280 Safari/537.36 MSIX','2026-09-01 13:16:38');
/*!40000 ALTER TABLE `log_actividad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `intentos` int(11) NOT NULL DEFAULT 1,
  `ultimo_intento` datetime NOT NULL DEFAULT current_timestamp(),
  `bloqueado_hasta` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`),
  KEY `idx_bloqueado` (`bloqueado_hasta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lotes`
--

DROP TABLE IF EXISTS `lotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lotes`
--

LOCK TABLES `lotes` WRITE;
/*!40000 ALTER TABLE `lotes` DISABLE KEYS */;
INSERT INTO `lotes` VALUES (1,'a3','norte, zona A',5938.00,'ocupado','{\"type\":\"Polygon\",\"coordinates\":[[[-65.79995691776277,-28.53102352748398],[-65.7992058992386,-28.530495675123433],[-65.79833686351778,-28.531457118516656],[-65.79916298389436,-28.53197554023588],[-65.79995691776277,-28.53102352748398]]]}',100,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(2,'l_a2','sur',7467.00,'disponible','{\"type\":\"Polygon\",\"coordinates\":[[[-65.799164889147,-28.530406423040013],[-65.7982740043284,-28.529793733430314],[-65.7972865175415,-28.530924849927768],[-65.79830620498447,-28.531377293127623],[-65.799164889147,-28.530406423040013]]]}',NULL,'2026-09-01 00:27:06','2026-09-01 00:27:06');
/*!40000 ALTER TABLE `lotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes`
--

DROP TABLE IF EXISTS `mensajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes`
--

LOCK TABLES `mensajes` WRITE;
/*!40000 ALTER TABLE `mensajes` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensajes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes_v2`
--

DROP TABLE IF EXISTS `mensajes_v2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes_v2`
--

LOCK TABLES `mensajes_v2` WRITE;
/*!40000 ALTER TABLE `mensajes_v2` DISABLE KEYS */;
INSERT INTO `mensajes_v2` VALUES (1,1,1,'ministerio','El Ministerio le asignó un nuevo formulario para completar. Use el botón \"Completar formulario\" para acceder.\n\npara prueba',0,'2026-05-29 01:17:09','2026-09-01 00:27:06'),(2,1,201,'empresa','listo',0,'2026-05-29 01:21:52','2026-09-01 00:27:06'),(4,2,3,'empresa','Quisiera saber qué documentación necesito para habilitar un nuevo galpón. Muchas gracias.',0,'2026-06-06 15:56:47','2026-09-01 00:27:06'),(6,2,1,'ministerio','Buenos días. Para habilitar un nuevo galpón necesita presentar: plano aprobado, habilitación municipal, certificado de bomberos y seguro contra incendios. Puede acercarse a la oficina para iniciar el trámite.',0,NULL,'2026-09-01 00:27:06');
/*!40000 ALTER TABLE `mensajes_v2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES (1,201,'formulario_nuevo','Nuevo formulario: prueba','Debe completar el formulario asignado por el ministerio.','/empresa/formulario_dinamico.php?id=1',NULL,0,NULL,'2026-09-01 00:27:06'),(2,202,'formulario_pendiente','Complete su formulario trimestral','El Ministerio solicita que complete su declaración jurada trimestral.','/empresa/formularios.php',NULL,0,NULL,'2026-09-01 00:27:06'),(3,2,'formulario_enviado','Formulario recibido','prueba envió su declaración trimestral (2026-Q2)','/ministerio/formularios.php',NULL,1,NULL,'2026-09-01 00:27:06'),(4,1,'formulario_enviado','Formulario recibido','prueba envió su declaración trimestral (2026-Q2)','/ministerio/formularios.php',NULL,0,NULL,'2026-09-01 00:27:06');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_requests`
--

DROP TABLE IF EXISTS `password_reset_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_requests`
--

LOCK TABLES `password_reset_requests` WRITE;
/*!40000 ALTER TABLE `password_reset_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plantillas_respuesta`
--

DROP TABLE IF EXISTS `plantillas_respuesta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plantillas_respuesta`
--

LOCK TABLES `plantillas_respuesta` WRITE;
/*!40000 ALTER TABLE `plantillas_respuesta` DISABLE KEYS */;
INSERT INTO `plantillas_respuesta` VALUES (1,'Recibimos su consulta','Estimado/a,\r\n\r\nHemos recibido su consulta y ser?? derivada al ??rea correspondiente.\r\n\r\nLe responderemos a la brevedad.\r\n\r\nSaludos cordiales,\r\nMinisterio de Industria','consulta',1,1,'2026-05-28 20:32:13','2026-05-30 20:53:22'),(2,'Documentaci??n requerida','Estimado/a,\n\nPara continuar con su tr??mite necesitamos que adjunte la siguiente documentaci??n:\n\n- \n- \n\nQuedamos a disposici??n.\n\nSaludos cordiales,\nMinisterio de Industria','tramite',2,1,'2026-05-28 20:32:13','2026-05-28 20:32:13'),(3,'Tr??mite aprobado','Estimado/a,\n\nNos complace informarle que su tr??mite ha sido aprobado.\n\nSaludos cordiales,\nMinisterio de Industria','tramite',3,1,'2026-05-28 20:32:13','2026-05-28 20:32:13'),(4,'Formulario con observaciones','Estimado/a,\n\nHemos revisado su declaraci??n de datos y encontramos las siguientes observaciones:\n\n- \n\nPor favor corrija y reenv??e.\n\nSaludos cordiales,\nMinisterio de Industria','formulario',4,1,'2026-05-28 20:32:13','2026-05-28 20:32:13');
/*!40000 ALTER TABLE `plantillas_respuesta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publicacion_likes`
--

DROP TABLE IF EXISTS `publicacion_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publicacion_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicacion_id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_like_ip` (`publicacion_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publicacion_likes`
--

LOCK TABLES `publicacion_likes` WRITE;
/*!40000 ALTER TABLE `publicacion_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `publicacion_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publicaciones`
--

DROP TABLE IF EXISTS `publicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publicaciones`
--

LOCK TABLES `publicaciones` WRITE;
/*!40000 ALTER TABLE `publicaciones` DISABLE KEYS */;
INSERT INTO `publicaciones` VALUES (1,102,203,'evento','Charla de Higiene y Seguridad en el Trabajo','charla-higiene-seguridad-catamarca-cementos','El equipo de Catamarca Cementos participó de una jornada obligatoria de higiene y seguridad dictada por profesionales de la ART, con foco en el trabajo en altura y manejo de materiales pesados.','<p>El pasado jueves, todo el personal de planta de <strong>Catamarca Cementos S.A.</strong> participó de una jornada completa de Higiene y Seguridad en el Trabajo organizada junto a nuestra Aseguradora de Riesgos del Trabajo.</p><p>Los temas abordados incluyeron: uso correcto de EPP, trabajo en altura, manipulación segura de materiales a granel y protocolo ante emergencias químicas. La capacitación fue dictada por la Lic. Valeria Rodríguez, especialista en seguridad industrial.</p><p>Este tipo de formación es parte de nuestro compromiso permanente con el bienestar de nuestros colaboradores.</p>',NULL,1,1,0,'aprobado',1,'2026-06-01 10:00:00',NULL,'2026-06-01 10:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(2,102,203,'noticia','Visita de alumnos del Colegio Técnico N°2 a nuestra planta','visita-colegio-tecnico-catamarca-cementos','Estudiantes de 5° año de la orientación Construcciones del Colegio Técnico N°2 visitaron las instalaciones de Catamarca Cementos para conocer el proceso productivo en planta.','<p>El martes recibimos la visita de 28 alumnos de 5° año del <strong>Colegio Técnico Provincial N°2</strong>, orientación Construcciones Civiles, acompañados por sus docentes.</p><p>Durante el recorrido los estudiantes conocieron de cerca el proceso de molienda y envasado del cemento portland, las medidas de seguridad industrial aplicadas y las oportunidades laborales que ofrece el sector.</p><p>Desde Catamarca Cementos consideramos fundamental el vínculo entre la industria y la educación técnica.</p>',NULL,1,0,0,'aprobado',1,'2026-06-05 09:00:00',NULL,'2026-06-05 09:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(3,103,204,'evento','Capacitación en RCP y Primeros Auxilios para todo el personal','capacitacion-rcp-primeros-auxilios-norand','NorAnd Metalúrgica llevó a cabo una capacitación en RCP y primeros auxilios certificada por la Cruz Roja Argentina, con participación de 34 operarios y técnicos de la empresa.','<p><strong>NorAnd Metalúrgica S.R.L.</strong> realizó una jornada de capacitación en Reanimación Cardiopulmonar (RCP) y Primeros Auxilios dictada por instructores certificados de la <em>Cruz Roja Argentina</em>, filial Catamarca.</p><p>Participaron 34 personas entre operarios, técnicos y personal administrativo. Los participantes practicaron maniobras de RCP en maniquíes, uso del DEA y atención inicial ante quemaduras y cortes.</p><p>Al finalizar, cada participante recibió su certificado de aprobación con validez de 2 años.</p>',NULL,1,1,1,'aprobado',1,'2026-06-03 08:00:00',NULL,'2026-06-03 08:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(4,103,204,'noticia','Incorporamos nueva línea de soldadura TIG para proyectos mineros','nueva-linea-soldadura-tig-norand','La empresa amplió su capacidad productiva con equipos de soldadura TIG de última generación, orientados a proveer al sector minero de estructuras de alta precisión.','<p>En el marco de nuestra expansión productiva, <strong>NorAnd Metalúrgica</strong> incorporó tres equipos de soldadura TIG (Tungsten Inert Gas) de la marca Lincoln Electric, que permiten trabajar con acero inoxidable, aluminio y aleaciones especiales requeridas por el sector minero.</p><p>Esta inversión responde a la creciente demanda de estructuras metálicas de alta precisión para proyectos en las minas de la región. La nueva línea genera 8 puestos de trabajo directos adicionales.</p>',NULL,1,0,0,'aprobado',1,'2026-06-07 11:00:00',NULL,'2026-06-07 11:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(5,104,205,'evento','Jornada de Buenas Prácticas de Manufactura (BPM)','jornada-bpm-dulces-del-norte','El equipo de producción de Dulces del Norte completó la capacitación anual en Buenas Prácticas de Manufactura alimentaria, requisito obligatorio para mantener la habilitación SENASA.','<p>Como parte del cumplimiento normativo ante <strong>SENASA</strong>, el personal de producción y control de calidad de <strong>Dulces del Norte S.A.</strong> completó la capacitación anual en Buenas Prácticas de Manufactura (BPM) para la industria alimentaria.</p><p>Los temas incluidos fueron: higiene personal y del establecimiento, control de plagas, trazabilidad de lotes, gestión de alérgenos y cadena de frío.</p>',NULL,1,0,0,'aprobado',1,'2026-05-28 10:00:00',NULL,'2026-05-28 10:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(6,104,205,'noticia','Dulces del Norte presente en la Feria Regional de Sabores de Catamarca','feria-regional-sabores-dulces-del-norte','Participamos de la Feria Regional de Sabores con nuestra línea completa de dulces y mermeladas artesanales, donde obtuvimos el reconocimiento al mejor producto de fruta andina.','<p><strong>Dulces del Norte</strong> participó de la edición 2026 de la <em>Feria Regional de Sabores</em> realizada en el Centro Cultural del Bicentenario de Catamarca, presentando su línea completa de mermeladas de membrillo, dulce de alcayota, nueces confitadas y conservas regionales.</p><p>Recibimos el reconocimiento al <strong>mejor producto de fruta andina</strong> otorgado por el jurado de la feria.</p>',NULL,1,1,1,'aprobado',1,'2026-06-10 12:00:00',NULL,'2026-06-10 12:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(7,105,206,'noticia','Auditoría SENASA superada: habilitación nacional renovada','auditoria-senasa-frigorifico-andino','Frigorífico Andino superó satisfactoriamente la auditoría anual de SENASA, renovando su habilitación para faena y exportación por tres años más.','<p><strong>Frigorífico Andino S.R.L.</strong> superó con calificación sobresaliente la auditoría anual realizada por técnicos del <strong>SENASA</strong>, renovando su habilitación nacional para faena, procesamiento y exportación de carnes bovinas y caprinas por un período de tres años.</p><p>La inspección evaluó las condiciones edilicias, el sistema de trazabilidad SIGA, la cadena de frío, el bienestar animal y el manejo de efluentes.</p>',NULL,1,1,0,'aprobado',1,'2026-06-08 09:00:00',NULL,'2026-06-08 09:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(8,105,206,'evento','Visita del Colegio Agrotécnico Provincial a nuestras instalaciones','visita-colegio-agrotecnico-frigorifico-andino','Estudiantes de la orientación Producción Agropecuaria del Colegio Agrotécnico visitaron Frigorífico Andino para conocer el proceso de faena y las normas de bienestar animal.','<p>Recibimos la visita de 22 alumnos de 6° año de la <strong>Escuela Agrotécnica Provincial</strong> de la ciudad de Catamarca, orientación Producción Agropecuaria, junto a sus docentes.</p><p>Los estudiantes recorrieron las instalaciones de faena, sala de desposte, cámara frigorífica y área de despacho.</p>',NULL,1,0,0,'aprobado',1,'2026-06-11 10:00:00',NULL,'2026-06-11 10:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(9,106,207,'evento','Charla sobre Economía Circular en el Parque Industrial','charla-economia-circular-reciclacat','ReciclaCAT organizó una charla abierta sobre economía circular y gestión de residuos industriales dirigida a todas las empresas del Parque Industrial El Pantanillo.','<p><strong>ReciclaCAT S.R.L.</strong> organizó una charla abierta titulada <em>\"Economía Circular: del residuo al recurso\"</em> en el salón de usos múltiples del Parque Industrial El Pantanillo, con participación de representantes de 11 empresas del parque y personal del Ministerio de Ambiente de Catamarca.</p><p>Al finalizar, se firmaron acuerdos de gestión conjunta de residuos con tres empresas del parque.</p>',NULL,1,1,1,'aprobado',1,'2026-06-09 15:00:00',NULL,'2026-06-09 15:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(10,106,207,'noticia','Campaña de concientización ambiental en escuelas primarias de Catamarca','campana-concientizacion-ambiental-reciclacat','ReciclaCAT llevó su programa educativo de separación de residuos a 5 escuelas primarias de la capital catamarqueña, alcanzando a más de 600 alumnos en una semana.','<p>Durante la semana del 2 al 6 de junio, en el marco del <em>Día Mundial del Medio Ambiente</em>, <strong>ReciclaCAT</strong> visitó cinco escuelas primarias de San Fernando del Valle de Catamarca con su programa educativo <em>\"Separar es cuidar\"</em>.</p><p>En total, participaron más de 600 alumnos de entre 8 y 12 años.</p>',NULL,1,0,0,'aprobado',1,'2026-06-12 08:00:00',NULL,'2026-06-12 08:00:00',NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06'),(11,NULL,1,'noticia','Gran apertura del Parque Industrial Digital','gran-apertura-del-parque-industrial-digital','El Parque Industrial de Catamarca lanza su plataforma digital para la gestión integral de empresas radicadas.','<p>El <strong>Ministerio de Producción y Desarrollo Económico</strong> de la Provincia de Catamarca presenta la plataforma digital del Parque Industrial, un sistema integral que permite a las empresas radicadas gestionar sus declaraciones juradas, completar formularios del ministerio, publicar noticias y eventos, y acceder a estadísticas del parque en tiempo real.</p><p>Esta herramienta representa un paso fundamental en la modernización y digitalización de la gestión industrial de la provincia.</p>',NULL,1,1,1,'aprobado',1,'2026-08-31 21:27:06',NULL,NULL,NULL,0,'2026-09-01 00:27:06','2026-09-01 00:27:06');
/*!40000 ALTER TABLE `publicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `respuestas_formulario`
--

DROP TABLE IF EXISTS `respuestas_formulario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `respuestas_formulario`
--

LOCK TABLES `respuestas_formulario` WRITE;
/*!40000 ALTER TABLE `respuestas_formulario` DISABLE KEYS */;
/*!40000 ALTER TABLE `respuestas_formulario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rubros`
--

DROP TABLE IF EXISTS `rubros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rubros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL COMMENT 'Color hex para gr??ficos',
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rubros`
--

LOCK TABLES `rubros` WRITE;
/*!40000 ALTER TABLE `rubros` DISABLE KEYS */;
INSERT INTO `rubros` VALUES (1,'Textil',NULL,NULL,'#3498db',1,1,'2026-09-01 00:27:05'),(2,'Construcción',NULL,NULL,'#e74c3c',1,2,'2026-09-01 00:27:05'),(3,'Metalúrgica',NULL,NULL,'#95a5a6',1,3,'2026-09-01 00:27:05'),(4,'Alimentos',NULL,NULL,'#27ae60',1,4,'2026-09-01 00:27:05'),(5,'Transporte',NULL,NULL,'#f39c12',1,5,'2026-09-01 00:27:05'),(6,'Reciclado',NULL,NULL,'#2ecc71',1,6,'2026-09-01 00:27:05'),(7,'Hormigón',NULL,NULL,'#7f8c8d',1,7,'2026-09-01 00:27:05'),(8,'Electrodomésticos',NULL,NULL,'#9b59b6',1,8,'2026-09-01 00:27:05'),(9,'Medicamentos',NULL,NULL,'#1abc9c',1,9,'2026-09-01 00:27:05'),(10,'Calzados',NULL,NULL,'#e67e22',1,10,'2026-09-01 00:27:05'),(11,'Fibra de Vidrio',NULL,NULL,'#34495e',1,11,'2026-09-01 00:27:05'),(12,'Combustibles',NULL,NULL,'#c0392b',1,12,'2026-09-01 00:27:05'),(13,'Minería',NULL,NULL,'#8e44ad',1,13,'2026-09-01 00:27:05'),(14,'Química',NULL,NULL,'#16a085',1,14,'2026-09-01 00:27:05'),(15,'Maquinaria Industrial',NULL,NULL,'#2c3e50',1,15,'2026-09-01 00:27:05'),(16,'Autopartes',NULL,NULL,'#d35400',1,16,'2026-09-01 00:27:05'),(17,'Frigorífico',NULL,NULL,'#2980b9',1,17,'2026-09-01 00:27:05'),(18,'Lácteos',NULL,NULL,'#f1c40f',1,18,'2026-09-01 00:27:05'),(19,'Otros',NULL,NULL,'#bdc3c7',1,99,'2026-09-01 00:27:05'),(20,'Plásticos',NULL,NULL,'#6c757d',1,20,'2026-09-01 00:27:05'),(21,'Agroindustria',NULL,NULL,'#28a745',1,21,'2026-09-01 00:27:05'),(22,'Motocicletas',NULL,NULL,'#fd7e14',1,22,'2026-09-01 00:27:05'),(23,'Dulces',NULL,NULL,'#e83e8c',1,23,'2026-09-01 00:27:05');
/*!40000 ALTER TABLE `rubros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_proyecto`
--

DROP TABLE IF EXISTS `solicitudes_proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_proyecto`
--

LOCK TABLES `solicitudes_proyecto` WRITE;
/*!40000 ALTER TABLE `solicitudes_proyecto` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_proyecto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicaciones`
--

DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ubicaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `latitud_centro` decimal(10,8) DEFAULT NULL,
  `longitud_centro` decimal(11,8) DEFAULT NULL,
  `poligono_geojson` text DEFAULT NULL COMMENT 'GeoJSON del pol??gono del ??rea',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones`
--

LOCK TABLES `ubicaciones` WRITE;
/*!40000 ALTER TABLE `ubicaciones` DISABLE KEYS */;
INSERT INTO `ubicaciones` VALUES (1,'PI El Pantanillo','Parque Industrial El Pantanillo',-28.46960000,-65.77950000,NULL,1,'2026-09-01 00:27:05'),(2,'Capital','San Fernando del Valle de Catamarca',-28.46960000,-65.78520000,NULL,1,'2026-09-01 00:27:05'),(3,'Valle Viejo',NULL,-28.39170000,-65.70950000,NULL,1,'2026-09-01 00:27:05'),(4,'Recreo',NULL,-29.28330000,-65.06670000,NULL,1,'2026-09-01 00:27:05');
/*!40000 ALTER TABLE `ubicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin@parqueindustrial.gob.ar','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','admin',1,'2026-08-31 21:27:05',NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05'),(2,'ministerio@catamarca.gob.ar','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','ministerio',1,'2026-09-01 10:14:47',NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 13:14:47'),(3,'empresa@demo.com','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,NULL,NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05'),(201,'prueba@gmail.com','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,NULL,NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05'),(202,'textildelnorte@empresa.com','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,NULL,NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05'),(203,'contacto@catamarcacementos.com.ar','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,'2026-09-01 10:16:38',NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 13:16:38'),(204,'info@norandmetalurgica.com.ar','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,NULL,NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05'),(205,'ventas@dulcesdelnorte.com.ar','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,NULL,NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05'),(206,'administracion@frigoandinoca.com.ar','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,NULL,NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05'),(207,'contacto@reciclacat.com.ar','$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m','empresa',1,NULL,NULL,NULL,NULL,NULL,1,'2026-09-01 00:27:05','2026-09-01 00:27:05');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_empresas_completas`
--

DROP TABLE IF EXISTS `v_empresas_completas`;
/*!50001 DROP VIEW IF EXISTS `v_empresas_completas`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_empresas_completas` AS SELECT
 1 AS `id`,
  1 AS `usuario_id`,
  1 AS `nombre`,
  1 AS `razon_social`,
  1 AS `cuit`,
  1 AS `rubro`,
  1 AS `descripcion`,
  1 AS `ubicacion`,
  1 AS `direccion`,
  1 AS `latitud`,
  1 AS `longitud`,
  1 AS `telefono`,
  1 AS `email_contacto`,
  1 AS `contacto_nombre`,
  1 AS `sitio_web`,
  1 AS `facebook`,
  1 AS `instagram`,
  1 AS `linkedin`,
  1 AS `logo`,
  1 AS `imagen_portada`,
  1 AS `estado`,
  1 AS `perfil_completo`,
  1 AS `verificada`,
  1 AS `visitas`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `dotacion_total`,
  1 AS `empleados_masculinos`,
  1 AS `empleados_femeninos`,
  1 AS `consumo_energia`,
  1 AS `consumo_agua`,
  1 AS `exporta`,
  1 AS `importa`,
  1 AS `emisiones_co2`,
  1 AS `ultimo_periodo` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_estadisticas_generales`
--

DROP TABLE IF EXISTS `v_estadisticas_generales`;
/*!50001 DROP VIEW IF EXISTS `v_estadisticas_generales`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_estadisticas_generales` AS SELECT
 1 AS `total_empresas_activas`,
  1 AS `total_empresas`,
  1 AS `total_empleados`,
  1 AS `total_rubros`,
  1 AS `total_publicaciones` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `visitas_empresa`
--

DROP TABLE IF EXISTS `visitas_empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitas_empresa`
--

LOCK TABLES `visitas_empresa` WRITE;
/*!40000 ALTER TABLE `visitas_empresa` DISABLE KEYS */;
INSERT INTO `visitas_empresa` VALUES (12,102,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.40609.0 Chrome/148.0.7778.280 Safari/537.36 MSIX',NULL,'2026-09-01 13:05:07'),(13,102,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.40609.0 Chrome/148.0.7778.280 Safari/537.36 MSIX',NULL,'2026-09-01 13:07:07');
/*!40000 ALTER TABLE `visitas_empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'parque_industrial'
--

--
-- Final view structure for view `v_empresas_completas`
--

/*!50001 DROP VIEW IF EXISTS `v_empresas_completas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_empresas_completas` AS select `e`.`id` AS `id`,`e`.`usuario_id` AS `usuario_id`,`e`.`nombre` AS `nombre`,`e`.`razon_social` AS `razon_social`,`e`.`cuit` AS `cuit`,`e`.`rubro` AS `rubro`,`e`.`descripcion` AS `descripcion`,`e`.`ubicacion` AS `ubicacion`,`e`.`direccion` AS `direccion`,`e`.`latitud` AS `latitud`,`e`.`longitud` AS `longitud`,`e`.`telefono` AS `telefono`,`e`.`email_contacto` AS `email_contacto`,`e`.`contacto_nombre` AS `contacto_nombre`,`e`.`sitio_web` AS `sitio_web`,`e`.`facebook` AS `facebook`,`e`.`instagram` AS `instagram`,`e`.`linkedin` AS `linkedin`,`e`.`logo` AS `logo`,`e`.`imagen_portada` AS `imagen_portada`,`e`.`estado` AS `estado`,`e`.`perfil_completo` AS `perfil_completo`,`e`.`verificada` AS `verificada`,`e`.`visitas` AS `visitas`,`e`.`created_at` AS `created_at`,`e`.`updated_at` AS `updated_at`,`de`.`dotacion_total` AS `dotacion_total`,`de`.`empleados_masculinos` AS `empleados_masculinos`,`de`.`empleados_femeninos` AS `empleados_femeninos`,`de`.`consumo_energia` AS `consumo_energia`,`de`.`consumo_agua` AS `consumo_agua`,`de`.`exporta` AS `exporta`,`de`.`importa` AS `importa`,`de`.`emisiones_co2` AS `emisiones_co2`,`de`.`periodo` AS `ultimo_periodo` from (`empresas` `e` left join `datos_empresa` `de` on(`e`.`id` = `de`.`empresa_id` and `de`.`periodo` = (select max(`datos_empresa`.`periodo`) from `datos_empresa` where `datos_empresa`.`empresa_id` = `e`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_estadisticas_generales`
--

/*!50001 DROP VIEW IF EXISTS `v_estadisticas_generales`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_estadisticas_generales` AS select (select count(0) from `empresas` where `empresas`.`estado` = 'activa') AS `total_empresas_activas`,(select count(0) from `empresas`) AS `total_empresas`,(select coalesce(sum(`de`.`dotacion_total`),0) from (`datos_empresa` `de` join `empresas` `e` on(`de`.`empresa_id` = `e`.`id`)) where `e`.`estado` = 'activa' and `de`.`periodo` = (select max(`datos_empresa`.`periodo`) from `datos_empresa` where `datos_empresa`.`empresa_id` = `de`.`empresa_id`)) AS `total_empleados`,(select count(distinct `empresas`.`rubro`) from `empresas` where `empresas`.`estado` = 'activa') AS `total_rubros`,(select count(0) from `publicaciones` where `publicaciones`.`publicado` = 1 and `publicaciones`.`estado` = 'aprobado') AS `total_publicaciones` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-01 10:17:57
