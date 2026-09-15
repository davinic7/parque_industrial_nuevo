-- =============================================
-- SEED: Datos completos de perfil para 3 empresas
-- 102 Catamarca Cementos | 103 NorAnd Metalúrgica | 104 Dulces del Norte
-- =============================================

-- ── 1. DATOS DE PRODUCCIÓN (datos_empresa) ───────────────────────

INSERT INTO datos_empresa
  (id, empresa_id, periodo,
   dotacion_total, empleados_masculinos, empleados_femeninos, empleados_otros,
   capacidad_instalada, porcentaje_capacidad_uso, produccion_mensual, unidad_produccion,
   consumo_energia, consumo_agua, consumo_gas,
   conexion_red_agua, pozo_agua, conexion_gas_natural, conexion_cloacas,
   exporta, productos_exporta, paises_exporta, monto_exportaciones,
   importa, productos_importa, paises_importa, monto_importaciones,
   emisiones_co2, fuente_emision_principal,
   inversion_anual, inversion_maquinaria, inversion_infraestructura,
   rango_facturacion, certificaciones,
   estado, declaracion_jurada, fecha_declaracion)
VALUES

-- Catamarca Cementos (empresa 102)
(2, 102, '2026-Q1',
 42, 36, 6, 0,
 '120000 tn/año', 78.50, '7800', 'toneladas/mes',
 185000.00, 4200.00, 12500.00,
 1, 0, 1, 1,
 0, NULL, NULL, NULL,
 1, 'Clinker, yeso sintético', 'Brasil, China', 850000.00,
 48.2000, 'Proceso de calcinación (horno rotativo)',
 2800000.00, 1500000.00, 1300000.00,
 'grande', 'ISO 9001:2015, IRAM 50000',
 'aprobado', 1, '2026-04-05 10:00:00'),

-- NorAnd Metalúrgica (empresa 103)
(3, 103, '2026-Q1',
 27, 23, 4, 0,
 '500 tn estructuras/año', 65.00, '28', 'toneladas/mes',
 72000.00, 850.00, 3200.00,
 1, 0, 1, 1,
 1, 'Estructuras metálicas, carpintería de aluminio', 'Chile', 320000.00,
 1, 'Acero laminado, electrodos de soldadura', 'Brasil', 180000.00,
 18.5000, 'Proceso de soldadura y corte térmico',
 950000.00, 680000.00, 270000.00,
 'mediana', 'ISO 3834-2, AWS D1.1',
 'aprobado', 1, '2026-04-08 11:00:00'),

-- Dulces del Norte (empresa 104)
(4, 104, '2026-Q1',
 18, 5, 13, 0,
 '80 tn dulces/año', 55.00, '6', 'toneladas/mes',
 28000.00, 1200.00, 1800.00,
 1, 0, 1, 1,
 1, 'Dulce de membrillo, mermeladas artesanales', 'Brasil, Uruguay', 95000.00,
 0, NULL, NULL, NULL,
 4.8000, 'Cocción industrial (gas natural)',
 380000.00, 210000.00, 170000.00,
 'pequeña', 'SENASA Hab. Nacional, BPM ANMAT',
 'aprobado', 1, '2026-04-10 09:30:00');

-- ── 2. GALERÍA DE IMÁGENES (empresa_imagenes) ────────────────────
-- Se usan URLs de Picsum Photos (imágenes de muestra reales, sin subir archivos)

INSERT INTO empresa_imagenes (empresa_id, url, nombre, orden) VALUES
-- Catamarca Cementos (102) — 3 imágenes
(102, 'https://picsum.photos/seed/cementos1/800/450', 'Vista exterior de planta', 1),
(102, 'https://picsum.photos/seed/cementos2/800/450', 'Silo de almacenamiento', 2),
(102, 'https://picsum.photos/seed/cementos3/800/450', 'Línea de envasado', 3),

-- NorAnd Metalúrgica (103) — 3 imágenes
(103, 'https://picsum.photos/seed/metal1/800/450', 'Taller de estructuras', 1),
(103, 'https://picsum.photos/seed/metal2/800/450', 'Proceso de soldadura TIG', 2),
(103, 'https://picsum.photos/seed/metal3/800/450', 'Almacén de materiales', 3),

-- Dulces del Norte (104) — 3 imágenes
(104, 'https://picsum.photos/seed/dulces1/800/450', 'Sala de cocción', 1),
(104, 'https://picsum.photos/seed/dulces2/800/450', 'Línea de envasado artesanal', 2),
(104, 'https://picsum.photos/seed/dulces3/800/450', 'Productos terminados', 3);

-- ── 3. MARCAR PERFIL COMPLETO Y VERIFICADO ──────────────────────
UPDATE empresas
SET perfil_completo = 1, verificada = 1
WHERE id IN (102, 103, 104);
