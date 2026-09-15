-- =============================================
-- SEED: 5 formularios con preguntas, envíos,
--       destinatarios y respuestas de empresas
-- =============================================

-- ── 1. FORMULARIOS DINÁMICOS ──────────────────
INSERT INTO formularios_dinamicos (id, titulo, descripcion, estado, creado_por) VALUES
(3, 'Higiene y Seguridad',                  'Relevamiento del estado de higiene y seguridad en las instalaciones de cada empresa del parque.',              'publicado', 1),
(4, 'Materias de Trabajo',                  'Declaración de materias primas utilizadas, origen y principales dificultades de abastecimiento.',              'publicado', 1),
(5, 'Censo de Empleados',                   'Relevamiento de la dotación de personal activo, composición y nivel de formación.',                            'publicado', 1),
(6, 'Inspección de Vehículos Particulares', 'Control del estado documental y técnico de la flota vehicular de cada empresa.',                              'publicado', 1),
(7, 'Uso de Rutas Nacionales para Exportación', 'Relevamiento del uso de la red vial nacional para operaciones de exportación.',                           'publicado', 1);

-- ── 2. PREGUNTAS ──────────────────────────────

-- FORM 3: Higiene y Seguridad (q 20-24)
INSERT INTO formulario_preguntas (id, formulario_id, tipo, etiqueta, ayuda, requerido, opciones, orden) VALUES
(20, 3, 'radio',    '¿Cuenta con un responsable de Higiene y Seguridad designado?', 'Indique si existe una persona formalmente a cargo.',          1, '{"items":["Sí","No"]}', 1),
(21, 3, 'numero',   'Cantidad de accidentes laborales en el último trimestre',       'Incluir incidentes con y sin baja médica.',                   1, NULL,                   2),
(22, 3, 'checkbox', 'Elementos de Protección Personal (EPP) utilizados',            'Marque todos los EPP que usa su personal de forma regular.',  1, '{"items":["Casco","Guantes","Calzado de seguridad","Protección ocular","Protección auditiva"]}', 3),
(23, 3, 'fecha',    'Fecha de la última capacitación en emergencias',                'Incendios, evacuación, primeros auxilios, etc.',              1, NULL,                   4),
(24, 3, 'textarea', 'Observaciones generales sobre condiciones de seguridad',        'Describa novedades, mejoras pendientes o logros recientes.', 0, NULL,                   5);

-- FORM 4: Materias de Trabajo (q 25-29)
INSERT INTO formulario_preguntas (id, formulario_id, tipo, etiqueta, ayuda, requerido, opciones, orden) VALUES
(25, 4, 'textarea', 'Principales materias primas o insumos que utiliza',             'Liste los 3 o 4 insumos más importantes para su proceso productivo.', 1, NULL, 1),
(26, 4, 'radio',    'Origen predominante de sus materias primas',                    'Indique de dónde proviene la mayor parte de sus insumos.',            1, '{"items":["Local (Catamarca)","Nacional","Importado","Mixto"]}', 2),
(27, 4, 'radio',    '¿Cuenta con proveedores locales de Catamarca?',                 '',                                                                    1, '{"items":["Sí","No","En proceso"]}', 3),
(28, 4, 'numero',   'Consumo mensual aproximado de materia prima (en toneladas)',    'Si trabaja con unidades distintas, convierta a toneladas.',            1, NULL, 4),
(29, 4, 'select',   'Principal dificultad en el abastecimiento de materias primas',  '',                                                                    1, '{"items":["Precio","Disponibilidad","Calidad","Logística","Ninguna"]}', 5);

-- FORM 5: Censo de Empleados (q 30-34)
INSERT INTO formulario_preguntas (id, formulario_id, tipo, etiqueta, ayuda, requerido, opciones, orden) VALUES
(30, 5, 'numero',   'Cantidad total de empleados activos',                            'Incluir personal en relación de dependencia y contratados estables.', 1, NULL, 1),
(31, 5, 'radio',    'Distribución por género predominante',                           '',                                                                    1, '{"items":["Mayoría masculina","Mayoría femenina","Equilibrado"]}', 2),
(32, 5, 'numero',   'Empleados con discapacidad registrados',                         'Poner 0 si no aplica.',                                               1, NULL, 3),
(33, 5, 'numero',   'Empleados residentes en Catamarca Capital',                      '',                                                                    1, NULL, 4),
(34, 5, 'select',   'Nivel de formación predominante en su planta',                  '',                                                                    1, '{"items":["Primario","Secundario completo","Terciario o universitario","Posgrado"]}', 5);

-- FORM 6: Inspección de Vehículos (q 35-39)
INSERT INTO formulario_preguntas (id, formulario_id, tipo, etiqueta, ayuda, requerido, opciones, orden) VALUES
(35, 6, 'numero',   'Cantidad de vehículos propios de la empresa',                   'Incluir todos los rodados: autos, camionetas, camiones, maquinaria.',   1, NULL, 1),
(36, 6, 'radio',    '¿Los vehículos cuentan con VTV o revisión técnica al día?',     '',                                                                      1, '{"items":["Todos","La mayoría","Algunos","Ninguno"]}', 2),
(37, 6, 'fecha',    'Fecha de la última revisión interna de flota',                  'Inspección realizada por personal propio o taller contratado.',         1, NULL, 3),
(38, 6, 'checkbox', 'Tipos de vehículos que opera la empresa',                       '',                                                                      1, '{"items":["Automóviles","Camionetas","Camiones","Maquinaria pesada","Motocicletas"]}', 4),
(39, 6, 'radio',    '¿El seguro vehicular está actualizado?',                        '',                                                                      1, '{"items":["Sí, todos","Sí, parcialmente","No"]}', 5);

-- FORM 7: Rutas Nacionales para Exportación (q 40-44)
INSERT INTO formulario_preguntas (id, formulario_id, tipo, etiqueta, ayuda, requerido, opciones, orden) VALUES
(40, 7, 'radio',    '¿Su empresa realiza exportaciones actualmente?',                '',                                                                     1, '{"items":["Sí","No","En proceso de iniciar"]}', 1),
(41, 7, 'checkbox', 'Rutas nacionales que utiliza habitualmente',                    'Marque todas las que correspondan.',                                   0, '{"items":["RN 38","RN 60","RN 40","RN 157","RN 78"]}', 2),
(42, 7, 'select',   'Destino principal de las exportaciones',                        'Si no exporta, deje en blanco.',                                       0, '{"items":["Brasil","Chile","Uruguay","Bolivia","Europa","Asia","Otro / Mercado interno"]}', 3),
(43, 7, 'numero',   'Cantidad de envíos al exterior por mes',                        'Poner 0 si no exporta.',                                               1, NULL, 4),
(44, 7, 'textarea', 'Principal obstáculo logístico para la exportación',             'Describa costos, trámites, infraestructura vial u otros factores.',    0, NULL, 5);

-- ── 3. ENVÍOS (un envío por formulario, a las 5 empresas nuevas) ─
INSERT INTO formulario_envios (id, formulario_id, tipo_filtro, filtros_json, total_destinatarios, fecha_limite, enviado_por) VALUES
(2, 3, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}', 5, '2026-07-01', 1),
(3, 4, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}', 5, '2026-07-01', 1),
(4, 5, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}', 5, '2026-07-01', 1),
(5, 6, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}', 5, '2026-07-01', 1),
(6, 7, 'todos', '{"rubros":[],"ubicaciones":[],"estados":["activa"],"empresa_ids":[102,103,104,105,106]}', 5, '2026-07-01', 1);

-- ── 4. DESTINATARIOS (5 empresas × 5 envíos = 25 filas) ─────────
INSERT INTO formulario_destinatarios (id, envio_id, empresa_id, notificado, fecha_notificacion, plazo_hasta, respondido, fecha_respuesta) VALUES
-- Envio 2 (Form 3 H&S)
(2,  2, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 10:30:00'),
(3,  2, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 11:00:00'),
(4,  2, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 14:00:00'),
(5,  2, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 09:30:00'),
(6,  2, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 16:00:00'),
-- Envio 3 (Form 4 Materias)
(7,  3, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 11:00:00'),
(8,  3, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 12:00:00'),
(9,  3, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 15:00:00'),
(10, 3, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 10:00:00'),
(11, 3, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 17:00:00'),
-- Envio 4 (Form 5 Censo)
(12, 4, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 12:00:00'),
(13, 4, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 13:00:00'),
(14, 4, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 16:00:00'),
(15, 4, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 11:00:00'),
(16, 4, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 18:00:00'),
-- Envio 5 (Form 6 Vehiculos)
(17, 5, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 13:00:00'),
(18, 5, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 14:00:00'),
(19, 5, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 17:00:00'),
(20, 5, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 12:00:00'),
(21, 5, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 19:00:00'),
-- Envio 6 (Form 7 Rutas)
(22, 6, 102, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 14:00:00'),
(23, 6, 103, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 15:00:00'),
(24, 6, 104, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-12 18:00:00'),
(25, 6, 105, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-14 13:00:00'),
(26, 6, 106, 1, '2026-06-10 09:00:00', '2026-07-01', 1, '2026-06-13 20:00:00');

-- ── 5. RESPUESTAS ────────────────────────────────────────────────
-- Formato JSON: {"id_pregunta": "valor"} — checkbox como array JSON

-- FORM 3: Higiene y Seguridad
INSERT INTO formulario_respuestas (id, formulario_id, empresa_id, usuario_id, estado, respuestas, ip, enviado_at) VALUES
(2,  3, 102, 203, 'enviado', '{"20":"Sí","21":"1","22":["Casco","Calzado de seguridad","Protección ocular"],"23":"2026-05-15","24":"Mantenimiento preventivo de equipos completado en mayo. Sin incidentes graves en el período."}', '127.0.0.1', '2026-06-12 10:30:00'),
(3,  3, 103, 204, 'enviado', '{"20":"Sí","21":"0","22":["Casco","Guantes","Calzado de seguridad","Protección ocular","Protección auditiva"],"23":"2026-06-01","24":"EPP completos en toda la planta. Cero accidentes en el período. Se incorporaron cascos con visor integrado."}', '127.0.0.1', '2026-06-13 11:00:00'),
(4,  3, 104, 205, 'enviado', '{"20":"Sí","21":"0","22":["Guantes","Calzado de seguridad"],"23":"2026-04-20","24":"Personal de producción trabaja con guantes de nitrilo y calzado con puntera de acero. Sin novedades."}', '127.0.0.1', '2026-06-12 14:00:00'),
(5,  3, 105, 206, 'enviado', '{"20":"Sí","21":"1","22":["Casco","Guantes","Calzado de seguridad","Protección auditiva"],"23":"2026-05-28","24":"Un empleado sufrió corte leve en sala de desposte. Fue atendido en guardia y retomó actividades al día siguiente."}', '127.0.0.1', '2026-06-14 09:30:00'),
(6,  3, 106, 207, 'enviado', '{"20":"Sí","21":"0","22":["Guantes","Calzado de seguridad","Protección ocular"],"23":"2026-06-05","24":"Se renovaron todos los EPP en mayo. Personal actualizado en manejo de residuos peligrosos."}', '127.0.0.1', '2026-06-13 16:00:00');

-- FORM 4: Materias de Trabajo
INSERT INTO formulario_respuestas (id, formulario_id, empresa_id, usuario_id, estado, respuestas, ip, enviado_at) VALUES
(7,  4, 102, 203, 'enviado', '{"25":"Caliza, yeso, clinker importado y puzolana volcánica local","26":"Mixto","27":"Sí","28":"850","29":"Logística"}', '127.0.0.1', '2026-06-12 11:00:00'),
(8,  4, 103, 204, 'enviado', '{"25":"Barras de acero laminado en frío, chapas galvanizadas, perfiles de aluminio y electrodos de soldadura","26":"Nacional","27":"Sí","28":"45","29":"Precio"}', '127.0.0.1', '2026-06-13 12:00:00'),
(9,  4, 104, 205, 'enviado', '{"25":"Membrillo, durazno, alcayota, azúcar refinada y envases de vidrio","26":"Local (Catamarca)","27":"Sí","28":"12","29":"Disponibilidad"}', '127.0.0.1', '2026-06-12 15:00:00'),
(10, 4, 105, 206, 'enviado', '{"25":"Ganado bovino y caprino en pie, sal gruesa, tripas naturales y envases termosellables","26":"Local (Catamarca)","27":"Sí","28":"180","29":"Ninguna"}', '127.0.0.1', '2026-06-14 10:00:00'),
(11, 4, 106, 207, 'enviado', '{"25":"Plástico PET post-consumo, cartón corrugado, metales ferrosos y aluminio para reciclado","26":"Local (Catamarca)","27":"Sí","28":"65","29":"Ninguna"}', '127.0.0.1', '2026-06-13 17:00:00');

-- FORM 5: Censo de Empleados
INSERT INTO formulario_respuestas (id, formulario_id, empresa_id, usuario_id, estado, respuestas, ip, enviado_at) VALUES
(12, 5, 102, 203, 'enviado', '{"30":"42","31":"Mayoría masculina","32":"1","33":"38","34":"Secundario completo"}', '127.0.0.1', '2026-06-12 12:00:00'),
(13, 5, 103, 204, 'enviado', '{"30":"27","31":"Mayoría masculina","32":"0","33":"24","34":"Secundario completo"}', '127.0.0.1', '2026-06-13 13:00:00'),
(14, 5, 104, 205, 'enviado', '{"30":"18","31":"Mayoría femenina","32":"0","33":"18","34":"Terciario o universitario"}', '127.0.0.1', '2026-06-12 16:00:00'),
(15, 5, 105, 206, 'enviado', '{"30":"35","31":"Mayoría masculina","32":"2","33":"31","34":"Secundario completo"}', '127.0.0.1', '2026-06-14 11:00:00'),
(16, 5, 106, 207, 'enviado', '{"30":"14","31":"Equilibrado","32":"1","33":"13","34":"Terciario o universitario"}', '127.0.0.1', '2026-06-13 18:00:00');

-- FORM 6: Inspección de Vehículos
INSERT INTO formulario_respuestas (id, formulario_id, empresa_id, usuario_id, estado, respuestas, ip, enviado_at) VALUES
(17, 6, 102, 203, 'enviado', '{"35":"8","36":"Todos","37":"2026-05-20","38":["Camionetas","Camiones","Maquinaria pesada"],"39":"Sí, todos"}', '127.0.0.1', '2026-06-12 13:00:00'),
(18, 6, 103, 204, 'enviado', '{"35":"5","36":"Todos","37":"2026-06-10","38":["Automóviles","Camionetas","Camiones"],"39":"Sí, todos"}', '127.0.0.1', '2026-06-13 14:00:00'),
(19, 6, 104, 205, 'enviado', '{"35":"3","36":"Todos","37":"2026-06-01","38":["Automóviles","Camionetas"],"39":"Sí, todos"}', '127.0.0.1', '2026-06-12 17:00:00'),
(20, 6, 105, 206, 'enviado', '{"35":"12","36":"La mayoría","37":"2026-05-15","38":["Camionetas","Camiones"],"39":"Sí, parcialmente"}', '127.0.0.1', '2026-06-14 12:00:00'),
(21, 6, 106, 207, 'enviado', '{"35":"6","36":"Todos","37":"2026-06-08","38":["Camionetas","Camiones","Maquinaria pesada"],"39":"Sí, todos"}', '127.0.0.1', '2026-06-13 19:00:00');

-- FORM 7: Uso de Rutas Nacionales para Exportación
INSERT INTO formulario_respuestas (id, formulario_id, empresa_id, usuario_id, estado, respuestas, ip, enviado_at) VALUES
(22, 7, 102, 203, 'enviado', '{"40":"No","41":["RN 38"],"42":"Otro / Mercado interno","43":"0","44":"No realizamos exportaciones actualmente. Toda la producción se distribuye en el mercado regional."}', '127.0.0.1', '2026-06-12 14:00:00'),
(23, 7, 103, 204, 'enviado', '{"40":"En proceso de iniciar","41":["RN 38","RN 60"],"42":"Chile","43":"2","44":"Trámites de exportación en gestión con la Cámara de Comercio de Catamarca. Falta completar certificaciones."}', '127.0.0.1', '2026-06-13 15:00:00'),
(24, 7, 104, 205, 'enviado', '{"40":"Sí","41":["RN 38","RN 157"],"42":"Brasil","43":"3","44":"Demoras en aduana por falta de personal especializado en documentación de exportación de alimentos."}', '127.0.0.1', '2026-06-12 18:00:00'),
(25, 7, 105, 206, 'enviado', '{"40":"Sí","41":["RN 38","RN 60","RN 40"],"42":"Brasil","43":"8","44":"El principal problema es el costo de refrigeración en tránsito y la disponibilidad de camiones con cadena de frío."}', '127.0.0.1', '2026-06-14 13:00:00'),
(26, 7, 106, 207, 'enviado', '{"40":"No","41":["RN 38"],"42":"Otro / Mercado interno","43":"0","44":"Operamos exclusivamente en el mercado local. A futuro analizaremos exportación de materiales reciclados a destinos regionales."}', '127.0.0.1', '2026-06-13 20:00:00');
