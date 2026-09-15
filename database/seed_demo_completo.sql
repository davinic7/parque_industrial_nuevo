-- =============================================================================
-- SEED COMPLETO PARA DEMO — Parque Industrial de Catamarca
-- Generado: 2026-08-31
--
-- INSTRUCCIONES:
--   1. Asegurate de tener la base de datos "parque_industrial" creada con su schema
--   2. Importá este archivo desde phpMyAdmin o con:
--      mysql -u root parque_industrial < seed_demo_completo.sql
--
-- CUENTAS DE ACCESO (todas usan la misma contraseña: Demo1234):
--   admin@parqueindustrial.gob.ar     → rol: admin
--   ministerio@catamarca.gob.ar       → rol: ministerio
--   empresa@demo.com                  → rol: empresa (Empresa Demo S.R.L.)
--   contacto@catamarcacementos.com.ar  → rol: empresa (Catamarca Cementos)
--   info@norandmetalurgica.com.ar      → rol: empresa (NorAnd Metalúrgica)
--   ventas@dulcesdelnorte.com.ar       → rol: empresa (Dulces del Norte)
--   administracion@frigoandinoca.com.ar→ rol: empresa (Frigorífico Andino)
--   contacto@reciclacat.com.ar         → rol: empresa (ReciclaCAT)
--
-- NOTA: Este archivo NO modifica la estructura de tablas, solo inserta datos.
--       Si una tabla ya tiene datos, se limpian antes de insertar.
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

START TRANSACTION;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. CONFIGURACIÓN DEL SITIO
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `configuracion_sitio`;

INSERT INTO `configuracion_sitio` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `updated_at`) VALUES
(1,  'sitio_nombre',                'Parque Industrial de Catamarca',                                   'text',     'general',     'Nombre del sitio',                  NOW()),
(2,  'sitio_descripcion',           'Portal del Parque Industrial de la Provincia de Catamarca',        'textarea', 'general',     'Descripción del sitio',             NOW()),
(3,  'sitio_email',                 'contacto@parqueindustrial.gob.ar',                                 'text',     'contacto',    'Email de contacto',                 NOW()),
(4,  'sitio_telefono',              '(0383) 4123456',                                                   'text',     'contacto',    'Teléfono de contacto',              NOW()),
(5,  'sitio_direccion',             'San Fernando del Valle de Catamarca, Argentina',                    'text',     'contacto',    'Dirección física',                  NOW()),
(6,  'mapa_lat_centro',             '-28.4696',                                                         'text',     'mapa',        'Latitud centro del mapa',           NOW()),
(7,  'mapa_lng_centro',             '-65.7795',                                                         'text',     'mapa',        'Longitud centro del mapa',          NOW()),
(8,  'mapa_zoom_inicial',           '12',                                                               'number',   'mapa',        'Zoom inicial del mapa',             NOW()),
(9,  'redes_facebook',              'https://facebook.com/parqueindustrialcatamarca',                    'text',     'redes',       'Facebook',                          NOW()),
(10, 'redes_instagram',             'https://instagram.com/parqueindustrialcatamarca',                   'text',     'redes',       'Instagram',                         NOW()),
(11, 'redes_twitter',               '',                                                                 'text',     'redes',       'Twitter/X',                         NOW()),
(12, 'texto_sobre_nosotros',        'El Parque Industrial de Catamarca es un polo de desarrollo productivo estratégico para la región del NOA, ubicado en la localidad de El Pantanillo. Cuenta con infraestructura moderna, servicios de agua, gas, energía eléctrica y conectividad, y alberga empresas de diversos rubros industriales.', 'textarea', 'contenido', 'Texto sobre nosotros', NOW()),
(13, 'mostrar_estadisticas_publicas','1',                                                               'boolean',  'privacidad',  'Mostrar estadísticas al público',   NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 2. RUBROS
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `rubros`;

INSERT INTO `rubros` (`id`, `nombre`, `descripcion`, `icono`, `color`, `activo`, `orden`, `created_at`) VALUES
(1,  'Textil',                NULL, NULL, '#3498db', 1, 1,  NOW()),
(2,  'Construcción',          NULL, NULL, '#e74c3c', 1, 2,  NOW()),
(3,  'Metalúrgica',           NULL, NULL, '#95a5a6', 1, 3,  NOW()),
(4,  'Alimentos',             NULL, NULL, '#27ae60', 1, 4,  NOW()),
(5,  'Transporte',            NULL, NULL, '#f39c12', 1, 5,  NOW()),
(6,  'Reciclado',             NULL, NULL, '#2ecc71', 1, 6,  NOW()),
(7,  'Hormigón',              NULL, NULL, '#7f8c8d', 1, 7,  NOW()),
(8,  'Electrodomésticos',     NULL, NULL, '#9b59b6', 1, 8,  NOW()),
(9,  'Medicamentos',          NULL, NULL, '#1abc9c', 1, 9,  NOW()),
(10, 'Calzados',              NULL, NULL, '#e67e22', 1, 10, NOW()),
(11, 'Fibra de Vidrio',       NULL, NULL, '#34495e', 1, 11, NOW()),
(12, 'Combustibles',          NULL, NULL, '#c0392b', 1, 12, NOW()),
(13, 'Minería',               NULL, NULL, '#8e44ad', 1, 13, NOW()),
(14, 'Química',               NULL, NULL, '#16a085', 1, 14, NOW()),
(15, 'Maquinaria Industrial', NULL, NULL, '#2c3e50', 1, 15, NOW()),
(16, 'Autopartes',            NULL, NULL, '#d35400', 1, 16, NOW()),
(17, 'Frigorífico',           NULL, NULL, '#2980b9', 1, 17, NOW()),
(18, 'Lácteos',               NULL, NULL, '#f1c40f', 1, 18, NOW()),
(19, 'Otros',                 NULL, NULL, '#bdc3c7', 1, 99, NOW()),
(20, 'Plásticos',             NULL, NULL, '#6c757d', 1, 20, NOW()),
(21, 'Agroindustria',         NULL, NULL, '#28a745', 1, 21, NOW()),
(22, 'Motocicletas',          NULL, NULL, '#fd7e14', 1, 22, NOW()),
(23, 'Dulces',                NULL, NULL, '#e83e8c', 1, 23, NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 3. UBICACIONES
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `ubicaciones`;

INSERT INTO `ubicaciones` (`id`, `nombre`, `descripcion`, `latitud_centro`, `longitud_centro`, `poligono_geojson`, `activo`, `created_at`) VALUES
(1, 'PI El Pantanillo', 'Parque Industrial El Pantanillo',     -28.46960000, -65.77950000, NULL, 1, NOW()),
(2, 'Capital',          'San Fernando del Valle de Catamarca', -28.46960000, -65.78520000, NULL, 1, NOW()),
(3, 'Valle Viejo',      NULL,                                  -28.39170000, -65.70950000, NULL, 1, NOW()),
(4, 'Recreo',           NULL,                                  -29.28330000, -65.06670000, NULL, 1, NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 4. USUARIOS
-- Contraseña universal para demo: Demo1234
-- Hash bcrypt generado con password_hash('Demo1234', PASSWORD_DEFAULT)
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `usuarios`;

-- Hash bcrypt verificado para "Demo1234":
INSERT INTO `usuarios` (`id`, `email`, `password`, `rol`, `activo`, `ultimo_acceso`, `token_recuperacion`, `token_expira`, `token_activacion`, `token_activacion_expira`, `email_verificado`, `created_at`, `updated_at`) VALUES
(1,   'admin@parqueindustrial.gob.ar',        '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'admin',      1, NOW(), NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(2,   'ministerio@catamarca.gob.ar',           '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'ministerio', 1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(3,   'empresa@demo.com',                      '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(201, 'prueba@gmail.com',                      '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(202, 'textildelnorte@empresa.com',             '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(203, 'contacto@catamarcacementos.com.ar',      '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(204, 'info@norandmetalurgica.com.ar',          '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(205, 'ventas@dulcesdelnorte.com.ar',           '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(206, 'administracion@frigoandinoca.com.ar',    '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW()),
(207, 'contacto@reciclacat.com.ar',             '$2y$10$2YcdFda6ppKMQ7mlUFC9HeW9.RkzlpdmlePwnuzPk71FSVecXh12m', 'empresa',    1, NULL,  NULL, NULL, NULL, NULL, 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 5. EMPRESAS
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `empresa_imagenes`;
DELETE FROM `empresas`;

INSERT INTO `empresas` (`id`, `usuario_id`, `nombre`, `razon_social`, `cuit`, `rubro`, `descripcion`, `ubicacion`, `direccion`, `latitud`, `longitud`, `telefono`, `email_contacto`, `contacto_nombre`, `sitio_web`, `facebook`, `instagram`, `linkedin`, `logo`, `imagen_portada`, `estado`, `perfil_completo`, `verificada`, `visitas`, `created_at`, `updated_at`, `lote_declarado`, `lote_solicitud_estado`) VALUES
(1,   3,   'Empresa Demo S.R.L.',  '',                         '',                 'Textil',        'Empresa de manufactura especializada en productos industriales para la región.',                                                                                                                                                     'PI El Pantanillo', '',                                                       -28.53000000, -65.80200000, '3834000001',      '',                                    'Juan Pérez',            '',                               '',                                         '',                                        NULL,  NULL,  NULL,  'activa', 0, 0, 0, NOW(), NOW(), 'L-05',  'pendiente'),
(100, 201, 'prueba',               NULL,                       NULL,               'Textil',        NULL,                                                                                                                                                                                                                                'Catamarca Capital', NULL,                                                    -28.46960000, -65.77950000, NULL,              NULL,                                  NULL,                    NULL,                             NULL,                                       NULL,                                      NULL,  NULL,  NULL,  'activa', 0, 0, 7, NOW(), NOW(), NULL,    'sin_solicitud'),
(101, 202, 'Textil del Norte S.A.','',                         '',                 'Textil',        'Empresa textil especializada en producción de tejidos industriales para la región de Catamarca.',                                                                                                                                    'PI El Pantanillo', 'Parque Industrial El Pantanillo, Catamarca',             -28.53650000, -65.79650000, '3834001122',      '',                                    '',                      '',                               '',                                         '',                                        NULL,  NULL,  NULL,  'activa', 0, 0, 0, NOW(), NOW(), NULL,    'sin_solicitud'),
(102, 203, 'Catamarca Cementos',   'Catamarca Cementos S.A.',  '30-71234567-1',    'Construcción',  'Producción y comercialización de cemento portland y materiales de construcción para obra civil e industrial en la región del NOA. Planta de molienda con capacidad de 120.000 toneladas anuales.',                                    'PI El Pantanillo', 'Lote 12, Parque Industrial El Pantanillo, Catamarca',    -28.53950000, -65.80550000, '383-4512340',     'contacto@catamarcacementos.com.ar',    'Ing. Carlos Medina',    'www.catamarcacementos.com.ar',    'https://facebook.com/catamarcacementos',    'https://instagram.com/catamarcacementos',  NULL,  'empresa_102_logo.png',  NULL,  'activa', 1, 1, 2, NOW(), NOW(), '12',    'asignado'),
(103, 204, 'NorAnd Metalúrgica',   'NorAnd Metalúrgica S.R.L.','30-71345678-9',   'Metalúrgica',   'Fabricación de estructuras metálicas, carpintería de aluminio y soldadura industrial. Proveedor de proyectos mineros, civiles y del sector energético en Catamarca y provincias vecinas.',                                             'PI El Pantanillo', 'Lote 7, Parque Industrial El Pantanillo, Catamarca',     -28.54300000, -65.79750000, '383-4523451',     'info@norandmetalurgica.com.ar',        'Roberto Giménez',       NULL,                             NULL,                                       'https://instagram.com/norandmetalurgica', 'https://linkedin.com/company/norand-metalurgica', 'empresa_103_logo.jpg', NULL, 'activa', 1, 1, 0, NOW(), NOW(), '7', 'asignado'),
(104, 205, 'Dulces del Norte',     'Dulces del Norte S.A.',    '30-71456789-0',    'Dulces',        'Elaboración artesanal e industrial de dulces regionales, mermeladas y conservas con frutas de la región andina catamarqueña: membrillo, durazno, alcayota y nogal. Certificación SENASA habilitada.',                                  'PI El Pantanillo', 'Lote 3, Parque Industrial El Pantanillo, Catamarca',     -28.52700000, -65.79550000, '383-4534562',     'ventas@dulcesdelnorte.com.ar',         'María Estela Quiroga',  'www.dulcesdelnorte.com.ar',      'https://facebook.com/dulcesdelnorteca',     'https://instagram.com/dulcesdelnorte_cat', NULL,  'empresa_104_logo.png',  NULL,  'activa', 1, 1, 0, NOW(), NOW(), '3',     'asignado'),
(105, 206, 'Frigorífico Andino',   'Frigorífico Andino S.R.L.','30-71567890-1',   'Frigorífico',   'Faena y procesamiento de carne bovina y caprina con habilitación SENASA nacional. Capacidad de 200 cabezas diarias. Distribución regional en frío y exportación a mercados del Mercosur.',                                            'PI El Pantanillo', 'Lote 18, Parque Industrial El Pantanillo, Catamarca',    -28.53400000, -65.80100000, '383-4545673',     'administracion@frigoandinoca.com.ar',  'Luis Alberto Soria',    NULL,                             'https://facebook.com/frigorifico.andino',  NULL,                                      NULL,  'empresa_105_logo.webp',  NULL,  'activa', 1, 1, 0, NOW(), NOW(), '18',    'asignado'),
(106, 207, 'ReciclaCAT',           'ReciclaCAT S.R.L.',        '30-71678901-2',    'Reciclado',     'Gestión integral de residuos sólidos industriales: acopio, clasificación y recuperación de plásticos, metales, papel y vidrio. Planta certificada por el Ministerio de Ambiente de Catamarca. Economía circular aplicada al sector productivo.', 'PI El Pantanillo', 'Lote 22, Parque Industrial El Pantanillo, Catamarca', -28.54500000, -65.80150000, '383-4556784', 'contacto@reciclacat.com.ar',       'Ana Paula Ferreyra',    'www.reciclacat.com.ar',          NULL,                                       'https://instagram.com/reciclacat',        'https://linkedin.com/company/reciclacat', 'empresa_106_logo.avif', NULL, 'activa', 1, 1, 0, NOW(), NOW(), '22', 'asignado');


-- ─────────────────────────────────────────────────────────────────────────────
-- 6. IMÁGENES DE GALERÍA DE EMPRESAS
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO `empresa_imagenes` (`id`, `empresa_id`, `url`, `nombre`, `orden`, `created_at`) VALUES
(10, 102, 'empresa_102_gal_1.jpg',  'Planta de molienda',       1, NOW()),
(11, 102, 'empresa_102_gal_2.jpg',  'Área de envasado',         2, NOW()),
(12, 102, 'empresa_102_gal_3.jpg',  'Laboratorio de calidad',   3, NOW()),
(13, 103, 'empresa_103_gal_1.webp', 'Taller de soldadura',      1, NOW()),
(14, 104, 'empresa_104_gal_1.jpg',  'Línea de producción',      1, NOW()),
(15, 104, 'empresa_104_gal_2.jpg',  'Productos terminados',     2, NOW()),
(16, 105, 'empresa_105_gal_1.jpg',  'Sala de faena',            1, NOW()),
(17, 105, 'empresa_105_gal_2.jpg',  'Cámara frigorífica',       2, NOW()),
(18, 106, 'empresa_106_gal_1.jpg',  'Planta de clasificación',  1, NOW()),
(19, 106, 'empresa_106_gal_2.jpg',  'Prensa de reciclado',      2, NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 7. DATOS TRIMESTRALES (DECLARACIONES JURADAS)
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `datos_empresa`;

INSERT INTO `datos_empresa` (`id`, `empresa_id`, `periodo`, `dotacion_total`, `empleados_masculinos`, `empleados_femeninos`, `empleados_otros`, `capacidad_instalada`, `porcentaje_capacidad_uso`, `produccion_mensual`, `unidad_produccion`, `consumo_energia`, `consumo_agua`, `consumo_gas`, `conexion_red_agua`, `pozo_agua`, `conexion_gas_natural`, `conexion_cloacas`, `exporta`, `productos_exporta`, `paises_exporta`, `monto_exportaciones`, `importa`, `productos_importa`, `paises_importa`, `monto_importaciones`, `emisiones_co2`, `fuente_emision_principal`, `inversion_anual`, `inversion_maquinaria`, `inversion_infraestructura`, `rango_facturacion`, `certificaciones`, `estado`, `declaracion_jurada`, `fecha_declaracion`, `ip_declaracion`, `observaciones_ministerio`, `revisado_por`, `fecha_revision`, `created_at`, `updated_at`) VALUES
(1, 100, '2026-Q2', 55, 49, 6, 0, '10000', 85.00, '8500', '', 25000.00, 500.00, 200.00, 1, 1, 1, 1, 0, '', '', NULL, 0, '', '', NULL, 2500.0000, 'Combustibles', NULL, NULL, NULL, NULL, NULL, 'enviado', 1, '2026-06-18 09:27:55', '::1', NULL, NULL, NULL, NOW(), NOW()),
(2, 102, '2026-Q1', 42, 36, 6, 0, '120000 tn/año', 78.50, '7800', 'toneladas/mes', 185000.00, 4200.00, 12500.00, 1, 0, 1, 1, 0, NULL, NULL, NULL, 1, 'Clinker, yeso sintético', 'Brasil, China', 850000.00, 48.2000, 'Proceso de calcinación (horno rotativo)', 2800000.00, 1500000.00, 1300000.00, 'grande', 'ISO 9001:2015, IRAM 50000', 'aprobado', 1, '2026-04-05 10:00:00', NULL, NULL, NULL, NULL, NOW(), NOW()),
(3, 103, '2026-Q1', 27, 23, 4, 0, '500 tn estructuras/año', 65.00, '28', 'toneladas/mes', 72000.00, 850.00, 3200.00, 1, 0, 1, 1, 1, 'Estructuras metálicas, carpintería de aluminio', 'Chile', 320000.00, 1, 'Acero laminado, electrodos de soldadura', 'Brasil', 180000.00, 18.5000, 'Proceso de soldadura y corte térmico', 950000.00, 680000.00, 270000.00, 'mediana', 'ISO 3834-2, AWS D1.1', 'aprobado', 1, '2026-04-08 11:00:00', NULL, NULL, NULL, NULL, NOW(), NOW()),
(4, 104, '2026-Q1', 18, 5, 13, 0, '80 tn dulces/año', 55.00, '6', 'toneladas/mes', 28000.00, 1200.00, 1800.00, 1, 0, 1, 1, 1, 'Dulce de membrillo, mermeladas artesanales', 'Brasil, Uruguay', 95000.00, 0, NULL, NULL, NULL, 4.8000, 'Cocción industrial (gas natural)', 380000.00, 210000.00, 170000.00, 'pequeña', 'SENASA Hab. Nacional, BPM ANMAT', 'aprobado', 1, '2026-04-10 09:30:00', NULL, NULL, NULL, NULL, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 8. LOTES CON GEOMETRÍA (MAPA)
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `lotes`;

INSERT INTO `lotes` (`id`, `numero_lote`, `sector`, `superficie_m2`, `estado`, `geometria_terreno`, `empresa_id`, `created_at`, `updated_at`) VALUES
(1, 'a3',   'norte, zona A', 5938.00, 'ocupado',     '{"type":"Polygon","coordinates":[[[-65.79995691776277,-28.53102352748398],[-65.7992058992386,-28.530495675123433],[-65.79833686351778,-28.531457118516656],[-65.79916298389436,-28.53197554023588],[-65.79995691776277,-28.53102352748398]]]}', 100, NOW(), NOW()),
(2, 'l_a2', 'sur',           7467.00, 'disponible',  '{"type":"Polygon","coordinates":[[[-65.799164889147,-28.530406423040013],[-65.7982740043284,-28.529793733430314],[-65.7972865175415,-28.530924849927768],[-65.79830620498447,-28.531377293127623],[-65.799164889147,-28.530406423040013]]]}', NULL, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 9. FORMULARIOS DINÁMICOS
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `formulario_respuestas`;
DELETE FROM `formulario_destinatarios`;
DELETE FROM `formulario_envios`;
DELETE FROM `formulario_preguntas`;
DELETE FROM `formularios_dinamicos`;

INSERT INTO `formularios_dinamicos` (`id`, `titulo`, `descripcion`, `estado`, `creado_por`, `created_at`, `updated_at`, `survey_json`) VALUES
(1, 'prueba',                                   'para prueba',                                                                                                 'publicado', 1, NOW(), NOW(), NULL),
(3, 'Higiene y Seguridad',                      'Relevamiento del estado de higiene y seguridad en las instalaciones de cada empresa del parque.',              'publicado', 1, NOW(), NOW(), NULL),
(4, 'Materias de Trabajo',                      'Declaración de materias primas utilizadas, origen y principales dificultades de abastecimiento.',              'publicado', 1, NOW(), NOW(), NULL),
(5, 'Censo de Empleados',                       'Relevamiento de la dotación de personal activo, composición y nivel de formación.',                            'publicado', 1, NOW(), NOW(), NULL),
(6, 'Inspección de Vehículos Particulares',      'Control del estado documental y técnico de la flota vehicular de cada empresa.',                               'publicado', 1, NOW(), NOW(), NULL),
(7, 'Uso de Rutas Nacionales para Exportación', 'Relevamiento del uso de la red vial nacional para operaciones de exportación.',                                'publicado', 1, NOW(), NOW(), NULL);


-- ─────────────────────────────────────────────────────────────────────────────
-- 10. PREGUNTAS DE FORMULARIOS
-- ─────────────────────────────────────────────────────────────────────────────

-- Formulario 1: prueba (preguntas de testing)
INSERT INTO `formulario_preguntas` (`id`, `formulario_id`, `tipo`, `etiqueta`, `ayuda`, `requerido`, `opciones`, `orden`, `created_at`, `min_valor`, `max_valor`) VALUES
(10, 1, 'texto',    'texto corto',        'prueba',         0, NULL,                                                     1,  NOW(), NULL,   NULL),
(11, 1, 'textarea', 'parrafo',            'parrafo',        0, NULL,                                                     2,  NOW(), NULL,   NULL),
(12, 1, 'numero',   'numero',             'numero',         0, NULL,                                                     3,  NOW(), 1.0000, 1.0000),
(13, 1, 'fecha',    'fecha',              'fecha',          0, NULL,                                                     4,  NOW(), NULL,   NULL),
(14, 1, 'select',   'lista desplegable',  'probar comas',   0, '{"items":["1","2","3","4","5"]}',                         5,  NOW(), NULL,   NULL),
(15, 1, 'radio',    'opcion unica',       '',               0, '{"items":["1","2","3","4","5","6"]}',                     6,  NOW(), NULL,   NULL),
(16, 1, 'checkbox', 'multiples',          '',               0, '{"items":["1","2","3","4","5","6"]}',                     7,  NOW(), NULL,   NULL),
(17, 1, 'texto',    'tablas',             '',               0, '{"cols":["1","2","3","4","5","6"],"rows":["1","2","3","4","5","6"]}', 8, NOW(), NULL, NULL),
(18, 1, 'archivo',  'archivo imagen',     '',               0, NULL,                                                     9,  NOW(), NULL,   NULL),
(19, 1, 'direccion','direcccion',         '',               0, NULL,                                                     10, NOW(), NULL,   NULL);

-- Formulario 3: Higiene y Seguridad
INSERT INTO `formulario_preguntas` (`id`, `formulario_id`, `tipo`, `etiqueta`, `ayuda`, `requerido`, `opciones`, `orden`, `created_at`) VALUES
(20, 3, 'radio',    '¿Cuenta con un responsable de Higiene y Seguridad designado?',     'Indique si existe una persona formalmente a cargo.',                       1, '{"items":["Sí","No"]}',                                                                                         1, NOW()),
(21, 3, 'numero',   'Cantidad de accidentes laborales en el último trimestre',           'Incluir incidentes con y sin baja médica.',                                1, NULL,                                                                                                             2, NOW()),
(22, 3, 'checkbox', 'Elementos de Protección Personal (EPP) utilizados',                'Marque todos los EPP que usa su personal de forma regular.',                1, '{"items":["Casco","Guantes","Calzado de seguridad","Protección ocular","Protección auditiva"]}',                  3, NOW()),
(23, 3, 'fecha',    'Fecha de la última capacitación en emergencias',                    'Incendios, evacuación, primeros auxilios, etc.',                            1, NULL,                                                                                                             4, NOW()),
(24, 3, 'textarea', 'Observaciones generales sobre condiciones de seguridad',            'Describa novedades, mejoras pendientes o logros recientes.',                0, NULL,                                                                                                             5, NOW());

-- Formulario 4: Materias de Trabajo
INSERT INTO `formulario_preguntas` (`id`, `formulario_id`, `tipo`, `etiqueta`, `ayuda`, `requerido`, `opciones`, `orden`, `created_at`) VALUES
(25, 4, 'textarea', 'Principales materias primas o insumos que utiliza',                 'Liste los 3 o 4 insumos más importantes para su proceso productivo.',      1, NULL,                                                                                                             1, NOW()),
(26, 4, 'radio',    'Origen predominante de sus materias primas',                        'Indique de dónde proviene la mayor parte de sus insumos.',                  1, '{"items":["Local (Catamarca)","Nacional","Importado","Mixto"]}',                                                  2, NOW()),
(27, 4, 'radio',    '¿Cuenta con proveedores locales de Catamarca?',                     '',                                                                         1, '{"items":["Sí","No","En proceso"]}',                                                                              3, NOW()),
(28, 4, 'numero',   'Consumo mensual aproximado de materia prima (en toneladas)',        'Si trabaja con unidades distintas, convierta a toneladas.',                 1, NULL,                                                                                                             4, NOW()),
(29, 4, 'select',   'Principal dificultad en el abastecimiento de materias primas',      '',                                                                         1, '{"items":["Precio","Disponibilidad","Calidad","Logística","Ninguna"]}',                                           5, NOW());

-- Formulario 5: Censo de Empleados
INSERT INTO `formulario_preguntas` (`id`, `formulario_id`, `tipo`, `etiqueta`, `ayuda`, `requerido`, `opciones`, `orden`, `created_at`) VALUES
(30, 5, 'numero',   'Cantidad total de empleados activos',                               'Incluir personal en relación de dependencia y contratados estables.',       1, NULL,                                                                                                             1, NOW()),
(31, 5, 'radio',    'Distribución por género predominante',                              '',                                                                         1, '{"items":["Mayoría masculina","Mayoría femenina","Equilibrado"]}',                                                2, NOW()),
(32, 5, 'numero',   'Empleados con discapacidad registrados',                            'Poner 0 si no aplica.',                                                    1, NULL,                                                                                                             3, NOW()),
(33, 5, 'numero',   'Empleados residentes en Catamarca Capital',                         '',                                                                         1, NULL,                                                                                                             4, NOW()),
(34, 5, 'select',   'Nivel de formación predominante en su planta',                      '',                                                                         1, '{"items":["Primario","Secundario completo","Terciario o universitario","Posgrado"]}',                             5, NOW());

-- Formulario 6: Inspección de Vehículos
INSERT INTO `formulario_preguntas` (`id`, `formulario_id`, `tipo`, `etiqueta`, `ayuda`, `requerido`, `opciones`, `orden`, `created_at`) VALUES
(35, 6, 'numero',   'Cantidad de vehículos propios de la empresa',                       'Incluir todos los rodados: autos, camionetas, camiones, maquinaria.',       1, NULL,                                                                                                             1, NOW()),
(36, 6, 'radio',    '¿Los vehículos cuentan con VTV o revisión técnica al día?',         '',                                                                         1, '{"items":["Todos","La mayoría","Algunos","Ninguno"]}',                                                            2, NOW()),
(37, 6, 'fecha',    'Fecha de la última revisión interna de flota',                      'Inspección realizada por personal propio o taller contratado.',             1, NULL,                                                                                                             3, NOW()),
(38, 6, 'checkbox', 'Tipos de vehículos que opera la empresa',                           '',                                                                         1, '{"items":["Automóviles","Camionetas","Camiones","Maquinaria pesada","Motocicletas"]}',                             4, NOW()),
(39, 6, 'radio',    '¿El seguro vehicular está actualizado?',                            '',                                                                         1, '{"items":["Sí, todos","Sí, parcialmente","No"]}',                                                                 5, NOW());

-- Formulario 7: Uso de Rutas para Exportación
INSERT INTO `formulario_preguntas` (`id`, `formulario_id`, `tipo`, `etiqueta`, `ayuda`, `requerido`, `opciones`, `orden`, `created_at`) VALUES
(40, 7, 'radio',    '¿Su empresa realiza exportaciones actualmente?',                    '',                                                                         1, '{"items":["Sí","No","En proceso de iniciar"]}',                                                                   1, NOW()),
(41, 7, 'checkbox', 'Rutas nacionales que utiliza habitualmente',                        'Marque todas las que correspondan.',                                       0, '{"items":["RN 38","RN 60","RN 40","RN 157","RN 78"]}',                                                            2, NOW()),
(42, 7, 'select',   'Destino principal de las exportaciones',                            'Si no exporta, deje en blanco.',                                           0, '{"items":["Brasil","Chile","Uruguay","Bolivia","Europa","Asia","Otro / Mercado interno"]}',                        3, NOW()),
(43, 7, 'numero',   'Cantidad de envíos al exterior por mes',                            'Poner 0 si no exporta.',                                                   1, NULL,                                                                                                             4, NOW()),
(44, 7, 'textarea', 'Principal obstáculo logístico para la exportación',                 'Describa costos, trámites, infraestructura vial u otros factores.',         0, NULL,                                                                                                             5, NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 11. ENVÍOS DE FORMULARIOS
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO `formulario_envios` (`id`, `formulario_id`, `tipo_filtro`, `filtros_json`, `total_destinatarios`, `fecha_limite`, `enviado_por`, `created_at`) VALUES
(1, 1, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[]}',                          1, '2026-06-07', 1, NOW()),
(2, 3, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}',       5, '2026-07-01', 1, NOW()),
(3, 4, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}',       5, '2026-07-01', 1, NOW()),
(4, 5, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}',       5, '2026-07-01', 1, NOW()),
(5, 6, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}',       5, '2026-07-01', 1, NOW()),
(6, 7, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}',       5, '2026-07-01', 1, NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 12. DESTINATARIOS DE FORMULARIOS
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO `formulario_destinatarios` (`id`, `envio_id`, `empresa_id`, `notificado`, `fecha_notificacion`, `plazo_hasta`, `respondido`, `fecha_respuesta`, `created_at`) VALUES
-- Formulario 1 → prueba
(1,  1, 100, 1, '2026-05-29 01:16:56', '2026-06-07', 1, '2026-05-29 01:19:54', NOW()),
-- Formulario 3: Higiene y Seguridad
(2,  2, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 10:30:00', NOW()),
(3,  2, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 11:00:00', NOW()),
(4,  2, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 14:00:00', NOW()),
(5,  2, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 09:30:00', NOW()),
(6,  2, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 16:00:00', NOW()),
-- Formulario 4: Materias de Trabajo
(7,  3, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 11:00:00', NOW()),
(8,  3, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 12:00:00', NOW()),
(9,  3, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 15:00:00', NOW()),
(10, 3, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 10:00:00', NOW()),
(11, 3, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 17:00:00', NOW()),
-- Formulario 5: Censo de Empleados
(12, 4, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 12:00:00', NOW()),
(13, 4, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 13:00:00', NOW()),
(14, 4, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 16:00:00', NOW()),
(15, 4, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 11:00:00', NOW()),
(16, 4, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 18:00:00', NOW()),
-- Formulario 6: Inspección de Vehículos
(17, 5, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 13:00:00', NOW()),
(18, 5, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 14:00:00', NOW()),
(19, 5, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 17:00:00', NOW()),
(20, 5, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 12:00:00', NOW()),
(21, 5, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 19:00:00', NOW()),
-- Formulario 7: Uso de Rutas para Exportación
(22, 6, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 14:00:00', NOW()),
(23, 6, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 15:00:00', NOW()),
(24, 6, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 18:00:00', NOW()),
(25, 6, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 13:00:00', NOW()),
(26, 6, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 20:00:00', NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 13. RESPUESTAS DE FORMULARIOS (26 respuestas)
-- ─────────────────────────────────────────────────────────────────────────────

-- Formulario 1 (prueba) — respuesta de testing
INSERT INTO `formulario_respuestas` (`id`, `formulario_id`, `empresa_id`, `usuario_id`, `estado`, `respuestas`, `ip`, `enviado_at`, `created_at`, `updated_at`) VALUES
(1, 1, 100, 201, 'borrador', '{"10":"corto","11":"Texto largo de prueba para el campo párrafo.","12":"2","13":"2026-05-30","14":"2","15":"4","16":["3","4","5"],"19":"-28.53500100,-65.81239700"}', '::1', '2026-05-29 01:19:57', NOW(), NOW());

-- Formulario 3: Higiene y Seguridad — respuestas de las 5 empresas
INSERT INTO `formulario_respuestas` (`id`, `formulario_id`, `empresa_id`, `usuario_id`, `estado`, `respuestas`, `ip`, `enviado_at`, `created_at`, `updated_at`) VALUES
(2,  3, 102, 203, 'enviado', '{"20":"Sí","21":"1","22":["Casco","Calzado de seguridad","Protección ocular"],"23":"2026-05-15","24":"Mantenimiento preventivo de equipos completado en mayo. Sin incidentes graves en el período."}',                             '127.0.0.1', '2026-06-12 10:30:00', NOW(), NOW()),
(3,  3, 103, 204, 'enviado', '{"20":"Sí","21":"0","22":["Casco","Guantes","Calzado de seguridad","Protección ocular","Protección auditiva"],"23":"2026-06-01","24":"EPP completos en toda la planta. Cero accidentes en el período."}',                           '127.0.0.1', '2026-06-13 11:00:00', NOW(), NOW()),
(4,  3, 104, 205, 'enviado', '{"20":"Sí","21":"0","22":["Guantes","Calzado de seguridad"],"23":"2026-04-20","24":"Personal de producción trabaja con guantes de nitrilo y calzado con puntera de acero. Sin novedades."}',                                        '127.0.0.1', '2026-06-12 14:00:00', NOW(), NOW()),
(5,  3, 105, 206, 'enviado', '{"20":"Sí","21":"1","22":["Casco","Guantes","Calzado de seguridad","Protección auditiva"],"23":"2026-05-28","24":"Un empleado sufrió corte leve en sala de desposte. Fue atendido en guardia y retomó actividades al día siguiente."}','127.0.0.1', '2026-06-14 09:30:00', NOW(), NOW()),
(6,  3, 106, 207, 'enviado', '{"20":"Sí","21":"0","22":["Guantes","Calzado de seguridad","Protección ocular"],"23":"2026-06-05","24":"Se renovaron todos los EPP en mayo. Personal actualizado en manejo de residuos peligrosos."}',                              '127.0.0.1', '2026-06-13 16:00:00', NOW(), NOW());

-- Formulario 4: Materias de Trabajo
INSERT INTO `formulario_respuestas` (`id`, `formulario_id`, `empresa_id`, `usuario_id`, `estado`, `respuestas`, `ip`, `enviado_at`, `created_at`, `updated_at`) VALUES
(7,  4, 102, 203, 'enviado', '{"25":"Caliza, yeso, clinker importado y puzolana volcánica local","26":"Mixto","27":"Sí","28":"850","29":"Logística"}',                                                                                '127.0.0.1', '2026-06-12 11:00:00', NOW(), NOW()),
(8,  4, 103, 204, 'enviado', '{"25":"Barras de acero laminado en frío, chapas galvanizadas, perfiles de aluminio y electrodos de soldadura","26":"Nacional","27":"Sí","28":"45","29":"Precio"}',                                       '127.0.0.1', '2026-06-13 12:00:00', NOW(), NOW()),
(9,  4, 104, 205, 'enviado', '{"25":"Membrillo, durazno, alcayota, azúcar refinada y envases de vidrio","26":"Local (Catamarca)","27":"Sí","28":"12","29":"Disponibilidad"}',                                                          '127.0.0.1', '2026-06-12 15:00:00', NOW(), NOW()),
(10, 4, 105, 206, 'enviado', '{"25":"Ganado bovino y caprino en pie, sal gruesa, tripas naturales y envases termosellables","26":"Local (Catamarca)","27":"Sí","28":"180","29":"Ninguna"}',                                             '127.0.0.1', '2026-06-14 10:00:00', NOW(), NOW()),
(11, 4, 106, 207, 'enviado', '{"25":"Plástico PET post-consumo, cartón corrugado, metales ferrosos y aluminio para reciclado","26":"Local (Catamarca)","27":"Sí","28":"65","29":"Ninguna"}',                                            '127.0.0.1', '2026-06-13 17:00:00', NOW(), NOW());

-- Formulario 5: Censo de Empleados
INSERT INTO `formulario_respuestas` (`id`, `formulario_id`, `empresa_id`, `usuario_id`, `estado`, `respuestas`, `ip`, `enviado_at`, `created_at`, `updated_at`) VALUES
(12, 5, 102, 203, 'enviado', '{"30":"42","31":"Mayoría masculina","32":"1","33":"38","34":"Secundario completo"}',        '127.0.0.1', '2026-06-12 12:00:00', NOW(), NOW()),
(13, 5, 103, 204, 'enviado', '{"30":"27","31":"Mayoría masculina","32":"0","33":"24","34":"Secundario completo"}',        '127.0.0.1', '2026-06-13 13:00:00', NOW(), NOW()),
(14, 5, 104, 205, 'enviado', '{"30":"18","31":"Mayoría femenina","32":"0","33":"18","34":"Terciario o universitario"}',   '127.0.0.1', '2026-06-12 16:00:00', NOW(), NOW()),
(15, 5, 105, 206, 'enviado', '{"30":"35","31":"Mayoría masculina","32":"2","33":"31","34":"Secundario completo"}',        '127.0.0.1', '2026-06-14 11:00:00', NOW(), NOW()),
(16, 5, 106, 207, 'enviado', '{"30":"14","31":"Equilibrado","32":"1","33":"13","34":"Terciario o universitario"}',        '127.0.0.1', '2026-06-13 18:00:00', NOW(), NOW());

-- Formulario 6: Inspección de Vehículos
INSERT INTO `formulario_respuestas` (`id`, `formulario_id`, `empresa_id`, `usuario_id`, `estado`, `respuestas`, `ip`, `enviado_at`, `created_at`, `updated_at`) VALUES
(17, 6, 102, 203, 'enviado', '{"35":"8","36":"Todos","37":"2026-05-20","38":["Camionetas","Camiones","Maquinaria pesada"],"39":"Sí, todos"}',           '127.0.0.1', '2026-06-12 13:00:00', NOW(), NOW()),
(18, 6, 103, 204, 'enviado', '{"35":"5","36":"Todos","37":"2026-06-10","38":["Automóviles","Camionetas","Camiones"],"39":"Sí, todos"}',                 '127.0.0.1', '2026-06-13 14:00:00', NOW(), NOW()),
(19, 6, 104, 205, 'enviado', '{"35":"3","36":"Todos","37":"2026-06-01","38":["Automóviles","Camionetas"],"39":"Sí, todos"}',                            '127.0.0.1', '2026-06-12 17:00:00', NOW(), NOW()),
(20, 6, 105, 206, 'enviado', '{"35":"12","36":"La mayoría","37":"2026-05-15","38":["Camionetas","Camiones"],"39":"Sí, parcialmente"}',                  '127.0.0.1', '2026-06-14 12:00:00', NOW(), NOW()),
(21, 6, 106, 207, 'enviado', '{"35":"6","36":"Todos","37":"2026-06-08","38":["Camionetas","Camiones","Maquinaria pesada"],"39":"Sí, todos"}',           '127.0.0.1', '2026-06-13 19:00:00', NOW(), NOW());

-- Formulario 7: Uso de Rutas para Exportación
INSERT INTO `formulario_respuestas` (`id`, `formulario_id`, `empresa_id`, `usuario_id`, `estado`, `respuestas`, `ip`, `enviado_at`, `created_at`, `updated_at`) VALUES
(22, 7, 102, 203, 'enviado', '{"40":"No","41":["RN 38"],"42":"Otro / Mercado interno","43":"0","44":"No realizamos exportaciones actualmente. Toda la producción se distribuye en el mercado regional."}',                                                      '127.0.0.1', '2026-06-12 14:00:00', NOW(), NOW()),
(23, 7, 103, 204, 'enviado', '{"40":"En proceso de iniciar","41":["RN 38","RN 60"],"42":"Chile","43":"2","44":"Trámites de exportación en gestión con la Cámara de Comercio de Catamarca. Falta completar certificaciones."}',                                   '127.0.0.1', '2026-06-13 15:00:00', NOW(), NOW()),
(24, 7, 104, 205, 'enviado', '{"40":"Sí","41":["RN 38","RN 157"],"42":"Brasil","43":"3","44":"Demoras en aduana por falta de personal especializado en documentación de exportación de alimentos."}',                                                             '127.0.0.1', '2026-06-12 18:00:00', NOW(), NOW()),
(25, 7, 105, 206, 'enviado', '{"40":"Sí","41":["RN 38","RN 60","RN 40"],"42":"Brasil","43":"8","44":"El principal problema es el costo de refrigeración en tránsito y la disponibilidad de camiones con cadena de frío."}',                                       '127.0.0.1', '2026-06-14 13:00:00', NOW(), NOW()),
(26, 7, 106, 207, 'enviado', '{"40":"No","41":["RN 38"],"42":"Otro / Mercado interno","43":"0","44":"Operamos exclusivamente en el mercado local. A futuro analizaremos exportación de materiales reciclados a destinos regionales."}',                            '127.0.0.1', '2026-06-13 20:00:00', NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 14. CONVERSACIONES Y MENSAJES
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `mensajes_v2`;
DELETE FROM `comunicado_visto`;
DELETE FROM `conversaciones`;

INSERT INTO `conversaciones` (`id`, `titulo`, `empresa_id`, `iniciada_por`, `categoria`, `estado`, `referencia_tipo`, `referencia_id`, `ultimo_mensaje_at`, `created_at`, `updated_at`) VALUES
(1, 'Nuevo formulario: prueba',                        100, 'ministerio', 'formulario', 'abierta', 'formulario_dinamico', 1, '2026-05-29 01:21:30', NOW(), NOW()),
(2, 'Consulta sobre habilitación de galpón',           1,   'empresa',    'consulta',   'abierta', NULL,                  NULL, '2026-06-06 15:56:56', NOW(), NOW());

INSERT INTO `mensajes_v2` (`id`, `conversacion_id`, `remitente_id`, `remitente_tipo`, `contenido`, `es_borrador`, `leido_at`, `created_at`) VALUES
(1, 1, 1,   'ministerio', 'El Ministerio le asignó un nuevo formulario para completar. Use el botón "Completar formulario" para acceder.\n\npara prueba', 0, '2026-05-29 01:17:09', NOW()),
(2, 1, 201, 'empresa',    'listo', 0, '2026-05-29 01:21:52', NOW()),
(4, 2, 3,   'empresa',    'Quisiera saber qué documentación necesito para habilitar un nuevo galpón. Muchas gracias.', 0, '2026-06-06 15:56:47', NOW()),
(6, 2, 1,   'ministerio', 'Buenos días. Para habilitar un nuevo galpón necesita presentar: plano aprobado, habilitación municipal, certificado de bomberos y seguro contra incendios. Puede acercarse a la oficina para iniciar el trámite.', 0, NULL, NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 15. PUBLICACIONES (NOTICIAS Y EVENTOS)
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `publicaciones`;

INSERT INTO `publicaciones` (`id`, `empresa_id`, `usuario_id`, `tipo`, `titulo`, `slug`, `extracto`, `contenido`, `imagen`, `publicado`, `destacado`, `mostrar_en_inicio`, `estado`, `aprobado_por`, `fecha_aprobacion`, `motivo_rechazo`, `fecha_publicacion`, `fecha_expiracion`, `visitas`, `created_at`, `updated_at`) VALUES
(1, 102, 203, 'evento',  'Charla de Higiene y Seguridad en el Trabajo',
    'charla-higiene-seguridad-catamarca-cementos',
    'El equipo de Catamarca Cementos participó de una jornada obligatoria de higiene y seguridad dictada por profesionales de la ART, con foco en el trabajo en altura y manejo de materiales pesados.',
    '<p>El pasado jueves, todo el personal de planta de <strong>Catamarca Cementos S.A.</strong> participó de una jornada completa de Higiene y Seguridad en el Trabajo organizada junto a nuestra Aseguradora de Riesgos del Trabajo.</p><p>Los temas abordados incluyeron: uso correcto de EPP, trabajo en altura, manipulación segura de materiales a granel y protocolo ante emergencias químicas. La capacitación fue dictada por la Lic. Valeria Rodríguez, especialista en seguridad industrial.</p><p>Este tipo de formación es parte de nuestro compromiso permanente con el bienestar de nuestros colaboradores.</p>',
    NULL, 1, 1, 0, 'aprobado', 1, '2026-06-01 10:00:00', NULL, '2026-06-01 10:00:00', NULL, 0, NOW(), NOW()),

(2, 102, 203, 'noticia', 'Visita de alumnos del Colegio Técnico N°2 a nuestra planta',
    'visita-colegio-tecnico-catamarca-cementos',
    'Estudiantes de 5° año de la orientación Construcciones del Colegio Técnico N°2 visitaron las instalaciones de Catamarca Cementos para conocer el proceso productivo en planta.',
    '<p>El martes recibimos la visita de 28 alumnos de 5° año del <strong>Colegio Técnico Provincial N°2</strong>, orientación Construcciones Civiles, acompañados por sus docentes.</p><p>Durante el recorrido los estudiantes conocieron de cerca el proceso de molienda y envasado del cemento portland, las medidas de seguridad industrial aplicadas y las oportunidades laborales que ofrece el sector.</p><p>Desde Catamarca Cementos consideramos fundamental el vínculo entre la industria y la educación técnica.</p>',
    NULL, 1, 0, 0, 'aprobado', 1, '2026-06-05 09:00:00', NULL, '2026-06-05 09:00:00', NULL, 0, NOW(), NOW()),

(3, 103, 204, 'evento',  'Capacitación en RCP y Primeros Auxilios para todo el personal',
    'capacitacion-rcp-primeros-auxilios-norand',
    'NorAnd Metalúrgica llevó a cabo una capacitación en RCP y primeros auxilios certificada por la Cruz Roja Argentina, con participación de 34 operarios y técnicos de la empresa.',
    '<p><strong>NorAnd Metalúrgica S.R.L.</strong> realizó una jornada de capacitación en Reanimación Cardiopulmonar (RCP) y Primeros Auxilios dictada por instructores certificados de la <em>Cruz Roja Argentina</em>, filial Catamarca.</p><p>Participaron 34 personas entre operarios, técnicos y personal administrativo. Los participantes practicaron maniobras de RCP en maniquíes, uso del DEA y atención inicial ante quemaduras y cortes.</p><p>Al finalizar, cada participante recibió su certificado de aprobación con validez de 2 años.</p>',
    NULL, 1, 1, 1, 'aprobado', 1, '2026-06-03 08:00:00', NULL, '2026-06-03 08:00:00', NULL, 0, NOW(), NOW()),

(4, 103, 204, 'noticia', 'Incorporamos nueva línea de soldadura TIG para proyectos mineros',
    'nueva-linea-soldadura-tig-norand',
    'La empresa amplió su capacidad productiva con equipos de soldadura TIG de última generación, orientados a proveer al sector minero de estructuras de alta precisión.',
    '<p>En el marco de nuestra expansión productiva, <strong>NorAnd Metalúrgica</strong> incorporó tres equipos de soldadura TIG (Tungsten Inert Gas) de la marca Lincoln Electric, que permiten trabajar con acero inoxidable, aluminio y aleaciones especiales requeridas por el sector minero.</p><p>Esta inversión responde a la creciente demanda de estructuras metálicas de alta precisión para proyectos en las minas de la región. La nueva línea genera 8 puestos de trabajo directos adicionales.</p>',
    NULL, 1, 0, 0, 'aprobado', 1, '2026-06-07 11:00:00', NULL, '2026-06-07 11:00:00', NULL, 0, NOW(), NOW()),

(5, 104, 205, 'evento',  'Jornada de Buenas Prácticas de Manufactura (BPM)',
    'jornada-bpm-dulces-del-norte',
    'El equipo de producción de Dulces del Norte completó la capacitación anual en Buenas Prácticas de Manufactura alimentaria, requisito obligatorio para mantener la habilitación SENASA.',
    '<p>Como parte del cumplimiento normativo ante <strong>SENASA</strong>, el personal de producción y control de calidad de <strong>Dulces del Norte S.A.</strong> completó la capacitación anual en Buenas Prácticas de Manufactura (BPM) para la industria alimentaria.</p><p>Los temas incluidos fueron: higiene personal y del establecimiento, control de plagas, trazabilidad de lotes, gestión de alérgenos y cadena de frío.</p>',
    NULL, 1, 0, 0, 'aprobado', 1, '2026-05-28 10:00:00', NULL, '2026-05-28 10:00:00', NULL, 0, NOW(), NOW()),

(6, 104, 205, 'noticia', 'Dulces del Norte presente en la Feria Regional de Sabores de Catamarca',
    'feria-regional-sabores-dulces-del-norte',
    'Participamos de la Feria Regional de Sabores con nuestra línea completa de dulces y mermeladas artesanales, donde obtuvimos el reconocimiento al mejor producto de fruta andina.',
    '<p><strong>Dulces del Norte</strong> participó de la edición 2026 de la <em>Feria Regional de Sabores</em> realizada en el Centro Cultural del Bicentenario de Catamarca, presentando su línea completa de mermeladas de membrillo, dulce de alcayota, nueces confitadas y conservas regionales.</p><p>Recibimos el reconocimiento al <strong>mejor producto de fruta andina</strong> otorgado por el jurado de la feria.</p>',
    NULL, 1, 1, 1, 'aprobado', 1, '2026-06-10 12:00:00', NULL, '2026-06-10 12:00:00', NULL, 0, NOW(), NOW()),

(7, 105, 206, 'noticia', 'Auditoría SENASA superada: habilitación nacional renovada',
    'auditoria-senasa-frigorifico-andino',
    'Frigorífico Andino superó satisfactoriamente la auditoría anual de SENASA, renovando su habilitación para faena y exportación por tres años más.',
    '<p><strong>Frigorífico Andino S.R.L.</strong> superó con calificación sobresaliente la auditoría anual realizada por técnicos del <strong>SENASA</strong>, renovando su habilitación nacional para faena, procesamiento y exportación de carnes bovinas y caprinas por un período de tres años.</p><p>La inspección evaluó las condiciones edilicias, el sistema de trazabilidad SIGA, la cadena de frío, el bienestar animal y el manejo de efluentes.</p>',
    NULL, 1, 1, 0, 'aprobado', 1, '2026-06-08 09:00:00', NULL, '2026-06-08 09:00:00', NULL, 0, NOW(), NOW()),

(8, 105, 206, 'evento',  'Visita del Colegio Agrotécnico Provincial a nuestras instalaciones',
    'visita-colegio-agrotecnico-frigorifico-andino',
    'Estudiantes de la orientación Producción Agropecuaria del Colegio Agrotécnico visitaron Frigorífico Andino para conocer el proceso de faena y las normas de bienestar animal.',
    '<p>Recibimos la visita de 22 alumnos de 6° año de la <strong>Escuela Agrotécnica Provincial</strong> de la ciudad de Catamarca, orientación Producción Agropecuaria, junto a sus docentes.</p><p>Los estudiantes recorrieron las instalaciones de faena, sala de desposte, cámara frigorífica y área de despacho.</p>',
    NULL, 1, 0, 0, 'aprobado', 1, '2026-06-11 10:00:00', NULL, '2026-06-11 10:00:00', NULL, 0, NOW(), NOW()),

(9, 106, 207, 'evento',  'Charla sobre Economía Circular en el Parque Industrial',
    'charla-economia-circular-reciclacat',
    'ReciclaCAT organizó una charla abierta sobre economía circular y gestión de residuos industriales dirigida a todas las empresas del Parque Industrial El Pantanillo.',
    '<p><strong>ReciclaCAT S.R.L.</strong> organizó una charla abierta titulada <em>"Economía Circular: del residuo al recurso"</em> en el salón de usos múltiples del Parque Industrial El Pantanillo, con participación de representantes de 11 empresas del parque y personal del Ministerio de Ambiente de Catamarca.</p><p>Al finalizar, se firmaron acuerdos de gestión conjunta de residuos con tres empresas del parque.</p>',
    NULL, 1, 1, 1, 'aprobado', 1, '2026-06-09 15:00:00', NULL, '2026-06-09 15:00:00', NULL, 0, NOW(), NOW()),

(10, 106, 207, 'noticia', 'Campaña de concientización ambiental en escuelas primarias de Catamarca',
    'campana-concientizacion-ambiental-reciclacat',
    'ReciclaCAT llevó su programa educativo de separación de residuos a 5 escuelas primarias de la capital catamarqueña, alcanzando a más de 600 alumnos en una semana.',
    '<p>Durante la semana del 2 al 6 de junio, en el marco del <em>Día Mundial del Medio Ambiente</em>, <strong>ReciclaCAT</strong> visitó cinco escuelas primarias de San Fernando del Valle de Catamarca con su programa educativo <em>"Separar es cuidar"</em>.</p><p>En total, participaron más de 600 alumnos de entre 8 y 12 años.</p>',
    NULL, 1, 0, 0, 'aprobado', 1, '2026-06-12 08:00:00', NULL, '2026-06-12 08:00:00', NULL, 0, NOW(), NOW()),

(11, NULL, 1, 'noticia', 'Gran apertura del Parque Industrial Digital',
    'gran-apertura-del-parque-industrial-digital',
    'El Parque Industrial de Catamarca lanza su plataforma digital para la gestión integral de empresas radicadas.',
    '<p>El <strong>Ministerio de Producción y Desarrollo Económico</strong> de la Provincia de Catamarca presenta la plataforma digital del Parque Industrial, un sistema integral que permite a las empresas radicadas gestionar sus declaraciones juradas, completar formularios del ministerio, publicar noticias y eventos, y acceder a estadísticas del parque en tiempo real.</p><p>Esta herramienta representa un paso fundamental en la modernización y digitalización de la gestión industrial de la provincia.</p>',
    NULL, 1, 1, 1, 'aprobado', 1, NOW(), NULL, NULL, NULL, 0, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 16. NOTIFICACIONES
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `notificaciones`;

INSERT INTO `notificaciones` (`id`, `usuario_id`, `tipo`, `titulo`, `mensaje`, `url`, `datos`, `leida`, `fecha_lectura`, `created_at`) VALUES
(1, 201, 'formulario_nuevo',    'Nuevo formulario: prueba',                  'Debe completar el formulario asignado por el ministerio.',                     '/empresa/formulario_dinamico.php?id=1', NULL, 0, NULL, NOW()),
(2, 202, 'formulario_pendiente','Complete su formulario trimestral',          'El Ministerio solicita que complete su declaración jurada trimestral.',         '/empresa/formularios.php',              NULL, 0, NULL, NOW()),
(3, 2,   'formulario_enviado',  'Formulario recibido',                       'prueba envió su declaración trimestral (2026-Q2)',                              '/ministerio/formularios.php',           NULL, 0, NULL, NOW()),
(4, 1,   'formulario_enviado',  'Formulario recibido',                       'prueba envió su declaración trimestral (2026-Q2)',                              '/ministerio/formularios.php',           NULL, 0, NULL, NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- 17. VISTAS (recrear para asegurar compatibilidad)
-- ─────────────────────────────────────────────────────────────────────────────
DROP VIEW IF EXISTS `v_empresas_completas`;
CREATE VIEW `v_empresas_completas` AS
SELECT e.id, e.usuario_id, e.nombre, e.razon_social, e.cuit, e.rubro,
       e.descripcion, e.ubicacion, e.direccion, e.latitud, e.longitud,
       e.telefono, e.email_contacto, e.contacto_nombre, e.sitio_web,
       e.facebook, e.instagram, e.linkedin, e.logo, e.imagen_portada,
       e.estado, e.perfil_completo, e.verificada, e.visitas,
       e.created_at, e.updated_at,
       de.dotacion_total, de.empleados_masculinos, de.empleados_femeninos,
       de.consumo_energia, de.consumo_agua, de.exporta, de.importa,
       de.emisiones_co2, de.periodo AS ultimo_periodo
FROM empresas e
LEFT JOIN datos_empresa de ON e.id = de.empresa_id
  AND de.periodo = (SELECT MAX(periodo) FROM datos_empresa WHERE empresa_id = e.id);

DROP VIEW IF EXISTS `v_estadisticas_generales`;
CREATE VIEW `v_estadisticas_generales` AS
SELECT
  (SELECT COUNT(*) FROM empresas WHERE estado = 'activa') AS total_empresas_activas,
  (SELECT COUNT(*) FROM empresas) AS total_empresas,
  (SELECT COALESCE(SUM(de.dotacion_total), 0)
   FROM datos_empresa de JOIN empresas e ON de.empresa_id = e.id
   WHERE e.estado = 'activa'
     AND de.periodo = (SELECT MAX(periodo) FROM datos_empresa WHERE empresa_id = de.empresa_id)
  ) AS total_empleados,
  (SELECT COUNT(DISTINCT rubro) FROM empresas WHERE estado = 'activa') AS total_rubros,
  (SELECT COUNT(*) FROM publicaciones WHERE publicado = 1 AND estado = 'aprobado') AS total_publicaciones;


-- ─────────────────────────────────────────────────────────────────────────────
-- 18. LIMPIAR TABLAS DE SOPORTE (sin datos demo necesarios)
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM `log_actividad`;
DELETE FROM `login_attempts`;
DELETE FROM `visitas_empresa`;
DELETE FROM `solicitudes_proyecto`;


COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- FIN DEL SEED
-- Total: 10 usuarios, 7 empresas, 4 declaraciones juradas, 6 formularios,
--        26 respuestas, 11 publicaciones, 2 lotes, 23 rubros, 4 ubicaciones,
--        2 conversaciones con mensajes.
-- =============================================================================
