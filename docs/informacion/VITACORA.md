# Bitácora de Trabajo — Parque Industrial de Catamarca

> Proyecto: Portal PHP para el Parque Industrial de Catamarca (Argentina)  
> Repo original: https://github.com/davinic7/parque_industrial  
> Deploy en producción: https://parque-industrial.onrender.com/  
> Fecha de inicio: 2026-05-27  
> Responsable: fdeluque74@gmail.com

---

## Contexto del Proyecto

Portal web con tres interfaces distintas:
- **Sitio público** (`public/*.php`) — visible para visitantes
- **Dashboard empresa** (`public/empresa/`) — panel privado de cada empresa
- **Panel ministerio** (`public/ministerio/`) — administración general

Stack: PHP puro (sin framework), MySQL/MariaDB, Bootstrap 5, Leaflet.js, sin bundler.

### Credenciales de demo (base de datos seed)
| Rol | Email | Password (hash `$2y$10$F1X4mm...`) |
|-----|-------|------|
| admin | admin@parqueindustrial.gob.ar | (desconocida — hash en SQL) |
| ministerio | ministerio@catamarca.gob.ar | (misma hash) |
| empresa demo | empresa@demo.com | (misma hash) |

---

## FASE 1 — Análisis y Setup Local (2026-05-27)

### Bugs Críticos Encontrados

#### 1. BASE DE DATOS — Tabla `banners_home` no existe
- **Archivo**: `public/index.php:38`
- **Problema**: El código hace `SELECT 1 FROM banners_home LIMIT 1` pero la tabla creada en el schema se llama `banners`.
- **Impacto**: El carrusel de la home nunca muestra banners (siempre cae en el bloque `catch`).
- **Fix pendiente**: Renombrar la consulta a `banners` y adaptar la query al schema real.

#### 2. BASE DE DATOS — Duplicados masivos en `rubros`
- **Archivo**: `parque_industrial.sql` (root)
- **Problema**: Los rubros con IDs 20-25, 26-31, 32-37, y 38-43 son exactamente los mismos datos (`PLÁSTICOS`, `QUÍMICA`, `AGROINDUSTRIA`, `MOTOCICLETAS`, `FIBRA DE VIDRIO`, `DULCES`) repetidos 4 veces por múltiples importaciones fallidas.
- **Impacto**: Los filtros por rubro muestran duplicados, estadísticas infladas.
- **Fix pendiente**: Limpiar duplicados, agregar constraint UNIQUE en `nombre`.

#### 3. BASE DE DATOS — Duplicados en `empresas`
- **Archivo**: `parque_industrial.sql` (root)
- **Problema**: Las empresas aparecen dos veces. IDs 1-21 son el primer batch, luego IDs 22-99 repiten las mismas empresas (con diferentes IDs pero mismo `usuario_id`). Por ejemplo, ALGODONERA DEL VALLE aparece como id=2 y id=23, ambas con `usuario_id=102`.
- **Impacto**: Cada usuario de empresa ve dos registros de "su" empresa.
- **Fix pendiente**: Eliminar el primer batch de registros (1-21) o el segundo.

#### 4. BASE DE DATOS — Columna `token_expira` vs `token_expiracion`
- **Archivo**: `parque_industrial.sql` (schema principal) vs `config/database.sql` (schema viejo)
- **Problema**: El schema real en `parque_industrial.sql` usa `token_expira` en la tabla `usuarios`, pero el schema alternativo `config/database.sql` usa `token_expiracion`. Auth.php puede referirse a la columna incorrecta.
- **Fix pendiente**: Verificar qué columna usa `includes/auth.php` y unificar.

#### 5. BASE DE DATOS — Desconexión entre `empresas.rubro` y tabla `rubros`
- **Problema**: `empresas.rubro` es `varchar(100)` con texto libre (ej: `'TEXTIL'`, `'CONSTRUCCIÓN'`) sin FK a la tabla `rubros`. Los valores en empresas están en MAYÚSCULAS mientras que `rubros` tiene case mixto (`'Textil'`, `'Construcción'`).
- **Impacto**: Los filtros por rubro no funcionan bien, las estadísticas por rubro son incorrectas.
- **Fix pendiente**: Normalizar case en `empresas.rubro` O agregar FK a `rubros.id`.

#### 6. TABLA USUARIOS — columnas faltantes para activación de cuenta
- **Archivos**: `includes/auth.php:78`, `public/activar-cuenta.php`, `public/ministerio/nueva-empresa.php`
- **Problema**: `auth.php::registerEmpresaPending()` hace INSERT con columnas `token_activacion`, `token_activacion_expira`, `email_verificado` que no existían en el schema de `usuarios`.
- **Impacto**: Crear empresa nueva desde el ministerio falla con error SQL.
- **Fix aplicado**: `database/cleanup_seed.sql` agrega las 3 columnas con ALTER TABLE.

#### 7. TABLAS FALTANTES — `login_attempts` y `password_reset_requests`
- **Problema**: Auth.php usa estas tablas para bloqueo por IP y rate limiting de recuperación de contraseña, pero no estaban en `parque_industrial.sql`.
- **Impacto**: No es fatal (auth.php tiene try/catch), pero los bloqueos y límites no funcionan.
- **Fix aplicado**: `database/cleanup_seed.sql` las crea correctamente.

#### 8. ARCHIVOS REDUNDANTES / CONFLICTO DE SCHEMAS
- `config/database.sql` — schema antiguo/draft que NO es el que se usa. Crea confusión.
- `parque_industrial.sql` (en el ROOT) — dump de phpMyAdmin. Debería estar en `database/`.
- `assets/css/estilos.css` — CSS duplicado, el CSS real está en `public/css/`.
- Las tablas de migraciones 015-018 agregan columnas/tablas que NO están en el schema principal (`parque_industrial.sql`), por lo que hay que aplicarlas DESPUÉS de importar el schema base.

#### 9. LÓGICA — Tabla `mensajes` obsoleta vs sistema v2
- La migración `017_migrar_mensajes_a_v2.sql` refactoriza el sistema de mensajería pero el schema base en `parque_industrial.sql` todavía tiene la tabla `mensajes` original (sin las columnas nuevas del v2).
- **Impacto**: Las páginas del centro de comunicaciones (`/empresa/comunicaciones.php`, `/ministerio/comunicaciones.php`) pueden fallar.

---

### Setup Local — Instrucciones definitivas

**Prerequisitos:** PHP 8.2 (ya instalado) + Laragon (MySQL + phpMyAdmin).

```
# ORDEN DE IMPORTACIÓN (con MySQL de Laragon corriendo):
# 1. Crear la DB en phpMyAdmin: http://localhost/phpmyadmin → "Nueva" → parque_industrial
# 2. Importar el schema base:
#    phpMyAdmin → parque_industrial → Importar → parque_industrial.sql (en la RAÍZ del proyecto)
# 3. Importar el script de correcciones:
#    phpMyAdmin → parque_industrial → Importar → database/cleanup_seed.sql
# 4. Importar las migraciones del sistema de mensajería (en orden):
#    database/015_mensajes_categoria.sql
#    database/016_centro_comunicaciones.sql
#    database/017_migrar_mensajes_a_v2.sql
#    database/018_plantillas_respuesta.sql
# 5. Correr el servidor PHP:
#    (desde la raíz del proyecto, en PowerShell)
#    php -S localhost:8080 -t public
# 6. Abrir http://localhost:8080 en el browser
```

**Credenciales de acceso tras la importación:**
| Rol | Email | Contraseña |
|-----|-------|------------|
| Admin | admin@parqueindustrial.gob.ar | admin123 |
| Ministerio | ministerio@catamarca.gob.ar | admin123 |
| Empresa demo | empresa@demo.com | admin123 |

**Nota:** El `.env` ya está creado con la configuración correcta para Laragon.

---

## Trabajo Pendiente (Backlog)

### Prioridad ALTA (bloquean funcionalidad básica) — COMPLETADO
- [x] Crear `.env` local → `database/cleanup_seed.sql`
- [x] Renombrar tabla `banners` → `banners_home` + agregar columnas `tipo`/`url_video` → `cleanup_seed.sql`
- [x] Agregar columnas faltantes en `usuarios` (token_activacion, etc.) → `cleanup_seed.sql`
- [x] Crear tablas faltantes `login_attempts`, `password_reset_requests` → `cleanup_seed.sql`
- [x] Limpiar duplicados en `rubros` y `empresas` → `cleanup_seed.sql`
- [x] Actualizar contraseñas de usuarios seed a "admin123" → `cleanup_seed.sql`
- [x] Levantar el proyecto localmente en http://localhost:8080 (Laragon + `php -S localhost:8080 -t public`)
- [x] Normalizar `empresas.rubro` a Title Case (78 filas actualizadas con BINARY comparison)
- [x] Corregir label "Sectores" → "Rubros" en `public/mapa.php`
- [x] Mejorar tile dashboard empresa: "1 Formularios pendientes" → "Pendiente / Declaración período"
- [x] **Unificar toda la base de datos** → `database/parque_industrial_v2.sql` (ver Sesión 3)

### Prioridad MEDIA (próximo a atacar)
- [ ] **Re-importar la DB** con el nuevo archivo unificado (ver instrucciones debajo)
- [ ] Probar flujo completo: crear empresa desde ministerio → activar cuenta → login empresa
- [ ] Probar formularios dinámicos (crear formulario ministerio → empresa completa → ministerio revisa)
- [ ] Probar publicaciones (empresa sube → ministerio aprueba → aparece en noticias públicas)
- [ ] Probar banners (ministerio sube imagen → aparece en carrusel del home)
- [ ] Probar export Excel y PDF desde gráficos/ministerio

### Prioridad BAJA (mejoras UI/UX) — PARCIALMENTE COMPLETADO
- [x] Aplicar skill `impeccable` — Design System v2 implementado (ver Sesión 2)
- [ ] Revisar responsive en mobile
- [ ] Mejorar empty states (noticias vacías, banners sin datos, etc.)

### Prioridad MEDIA (afectan UX y datos)
- [ ] Verificar y corregir columna `token_expira` en auth.php
- [ ] Verificar que el sistema de mensajería v2 funcione tras la importación
- [ ] Revisar si hay más referencias a tablas que no existen

---

## SESIÓN 3 — Unificación de Base de Datos (2026-05-27)

### Objetivo
Unificar todos los archivos SQL (schema base + cleanup + migraciones 015-018) en un **único archivo importable** sin pasos adicionales.

### Resultado
Creado: **`database/parque_industrial_v2.sql`**

| Característica | Detalle |
|---|---|
| Tablas | 22 tablas (+ 3 heredadas de formularios_dinamicos con inline FK) |
| Vistas | 2 (`v_empresas_completas`, `v_estadisticas_generales`) |
| Seed users | 3 admin + 78 empresas (todos con `admin123`) |
| Seed empresas | ID 1 (demo) + IDs 22-99 (sin duplicados IDs 2-21) |
| Seed rubros | 23 rubros, sin duplicados, Title Case |
| Migraciones integradas | 015 (categoria en mensajes), 016 (centro comunicaciones), 017 (index ux_conv_referencia), 018 (plantillas_respuesta) |
| Correcciones integradas | banners_home, token_activacion, login_attempts, password_reset_requests, normalización de rubros |

### Instrucción de re-importación (desde cero)
```sql
-- Opción A: CLI
mysql -u root -p < database/parque_industrial_v2.sql

-- Opción B: phpMyAdmin
-- Ir a "Bases de datos" → si existe parque_industrial, eliminarla
-- Luego: Nueva → "parque_industrial" → Importar → database/parque_industrial_v2.sql
-- El archivo ya incluye CREATE DATABASE + USE, así que no hace falta crearla antes.
```

### Archivos obsoletos (NO borrar, guardar como referencia histórica)
- `parque_industrial.sql` (root) — dump original phpMyAdmin con bugs
- `database/cleanup_seed.sql` — correcciones aplicadas al seed original
- `database/015_mensajes_categoria.sql` — migración integrada en v2
- `database/016_centro_comunicaciones.sql` — migración integrada en v2
- `database/017_migrar_mensajes_a_v2.sql` — data migration (vacía en seed; no aplica)
- `database/017b_migrar_mensajes_phpmyadmin.sql` — ídem
- `database/018_plantillas_respuesta.sql` — migración integrada en v2
- `config/database.sql` — schema antiguo/draft, no usar

### Prioridad BAJA (mejoras visuales y limpieza)
- [x] Eliminar `config/database.sql` (ya no existe)
- [ ] Eliminar o mover `assets/css/estilos.css`
- [ ] Revisar `test_pantanillo.js` — determinar si los tests son útiles o están desactualizados
- [ ] Revisar `.gitignore` para asegurar que `.env` y `logs/` estén excluidos
- [ ] Mejorar UI de páginas identificadas como incompletas

---

## Skills / Herramientas Disponibles

| Skill | Uso en este proyecto |
|-------|---------------------|
| `impeccable` | Auditar y mejorar UI/UX de las interfaces públicas y dashboards |
| `ui-ux-pro-max` | Redesign más profundo con componentes Bootstrap / Tailwind |
| `pdf` | Generar reportes PDF desde el panel ministerio |
| `xlsx` | Exportar datos de empresas a Excel |
| `docx` | Generar documentos Word para comunicados/formularios |
| `pptx` | Presentaciones del estado del parque (no prioritario) |
| `verify` | Verificar que un fix funciona en el navegador |
| `run` | Levantar y ver el proyecto en el browser |
| `code-review` | Revisar PRs o diffs antes de deployar |
| `security-review` | Auditar seguridad del código PHP |
| `claude-api` | Si se agrega IA (chatbot, procesamiento de formularios) |

### Skills sugeridas para instalar

| Tipo de skill | Por qué sería útil |
|--------------|-------------------|
| **MySQL / Database Admin** | Para ejecutar queries de limpieza, diff de schemas, generar migrations |
| **Docker Compose generator** | Para crear docker-compose.yml con PHP + MySQL + phpMyAdmin |
| **PHP linter / static analysis** | Para detectar errores de tipo, variables no definidas, código muerto |
| **Diff / merge de SQL schemas** | Para unificar `parque_industrial.sql` con las migraciones 015-018 sin conflictos |
| **Playwright / E2E test runner** | Ya tiene `test_pantanillo.js`, podría expandirse para validar flujos críticos |

---

## Log de Sesiones

### Sesión 1 — 2026-05-27
**Setup y corrección de bugs críticos**
- Exploración completa del repositorio, identificación de 9 bugs críticos
- Creado `.env` (Laragon + PHP built-in server)
- Creado `database/cleanup_seed.sql` — todos los fixes de schema en un solo archivo
- Creado `database/fix_fk_empresa.sql` — fix para error FK en import phpMyAdmin
- Creado `database/017b_migrar_mensajes_phpmyadmin.sql` — versión sin CTEs para phpMyAdmin
- **Proyecto corriendo en http://localhost:8080** — sin errores PHP
- Verificadas todas las interfaces: home, login, dashboard ministerio, dashboard empresa, mapa, estadísticas, gráficos, noticias, directorio
- Normalizado `empresas.rubro` a Title Case — 78 filas (fix binario en MySQL)
- Corregido label "Sectores" → "Rubros" en mapa
- Mejorado tile dashboard empresa: "1 Formularios pendientes" → "Pendiente / Declaración período"

**Archivos creados/modificados:**
- `.env` (nuevo)
- `database/cleanup_seed.sql` (nuevo)
- `database/fix_fk_empresa.sql` (nuevo)
- `database/017b_migrar_mensajes_phpmyadmin.sql` (nuevo)
- `.claude/launch.json` (nuevo)
- `public/mapa.php` (label fix)
- `public/empresa/dashboard.php` (tile UX fix)

**Próximo paso**: Probar flujos completos (crear empresa, formularios, publicaciones, banners)

---

### Sesión 2 — 2026-05-27
**Design System v2 — "La Planta" (impeccable skill)**

**Objetivo**: Aplicar rediseño completo UI/UX con la skill `impeccable`. Registro mixto: brand para el sitio público, product para los dashboards. Personalidad "Industrial moderno" — territorial, sólido, contemporáneo. Anti-referencia: "web de municipio viejo".

**Archivos creados:**
- `PRODUCT.md` (nuevo) — contexto de producto, usuarios, tono, paleta, principios
- `DESIGN.md` (nuevo) — sistema de diseño completo: tokens, tipografía, elevación, componentes, Do's & Don'ts

**Archivos modificados:**
- `includes/header.php` — fuente Inter añadida (Inter + Montserrat en lugar de Roboto + Montserrat)
- `public/css/styles.css` — **reescritura completa** del sistema de diseño:
  - Paleta nueva: navy profundo `#1b3a5c` + ámbar terracota `#c4601a` + neutros cálidos warm stone `#f7f4f0`
  - Overrides Bootstrap CSS vars (`--bs-primary`, `--bs-body-bg`, etc.)
  - Navbar: sólido, sin gradiente diagonal, active = background tint (no border-bottom stripe)
  - Stat cards: ícono plano en stone-bg (no círculo con gradiente SaaS)
  - Botón primario: ámbar (no azul genérico)
  - `.dashboard-card`: eliminado `border-left: 4px solid` (BANNED) → solo sombra
  - `.sidebar-menu a.active`: eliminado `border-left: 3px solid` (BANNED) → background tint
  - Dato impacto strip: sólido ámbar (no degradado)
  - Sección headers: alineación izquierda, divisor ámbar
  - Footer: limpio, sin gradiente
  - Body background: `#f7f4f0` (warm stone, nunca blanco puro)
- `public/css/empresa-app.css` — actualización del panel empresa/ministerio:
  - Paleta actualizada a nueva paleta navy/ámbar
  - Sidebar: fondo sólido `#0f2438` (sin gradiente)
  - `.empresa-sidebar-nav a.active`: eliminado `border-left: 3px solid #27ae60` (BANNED) → background tint
  - Action cards: colores sólidos (sin gradientes)
  - Stat tiles: Montserrat con tracking negativo, labels uppercase
- `public/login.php` — rediseño completo:
  - Fondo sólido navy oscuro `#0f2438` (no gradiente)
  - Branding "Parque Industrial / CATAMARCA" con jerarquía correcta
  - Botón "Ingresar": ámbar (`#c4601a`)
  - Inputs con bordes `#d9d3ca` y focus navy

**Cambios visuales clave:**
| Antes | Después |
|-------|---------|
| Navbar con `linear-gradient(135deg, ...)` | Navbar fondo sólido `#0f2438` |
| Botón "Ingresar" verde pill | Botón "Ingresar" ámbar pill |
| Active nav link: border-bottom verde | Active nav link: background tint |
| Stat card icons: círculo gradiente azul | Stat card icons: cuadrado plano stone-bg |
| Dashboard cards: border-left 4px acento | Dashboard cards: sin stripe, solo sombra |
| Sidebar active: border-left 3px verde | Sidebar active: background tint |
| Body bg: `#f8f9fa` (gris Bootstrap) | Body bg: `#f7f4f0` (warm stone) |
| Action cards: gradientes diagonales | Action cards: colores sólidos |
| bg-primary (Bootstrap azul #0d6efd) | bg-primary (nuestro navy #1b3a5c) |
| Login: gradiente azul + botón azul | Login: navy oscuro + botón ámbar |
| Botón "Ver perfil": azul primario | Botón "Ver perfil": ámbar |
| Rubro badges: azul primario | Rubro badges: ámbar |
| Placeholders logo empresa: gris frío | Placeholders logo empresa: warm stone |

**Estado post-sesión:**
- Sitio público: home, empresas, mapa, footer — ✅ rediseñados
- Dashboard empresa: sidebar, tiles, action cards, timeline — ✅ rediseñados
- Dashboard ministerio: sidebar, stat tiles, acciones rápidas — ✅ rediseñados
- Login: ✅ rediseñado

**Próximo paso**: Probar flujos completos (crear empresa, formularios, publicaciones, banners) + dar lista de funciones a corregir/implementar.

---

---

## SESIÓN 4 — 2026-06-05
**Panel empresa: onboarding, formularios, métricas y comunicaciones**

### Bugs corregidos esta sesión

#### BUG CRÍTICO — `perfil.php`: el UPDATE nunca ejecutaba
- **Archivo**: `public/empresa/perfil.php` líneas 63-105
- **Problema**: El bloque `UPDATE empresas SET...` estaba dentro del `else` de `if (empty($field_errors))`, es decir, solo corría cuando HAY errores de validación — y dentro de ese else chequeaba `if (empty($field_errors))` que nunca podía ser verdadero.
- **Efecto**: Los cambios de perfil nunca se guardaban en la BD. Sin mensaje de éxito visible.
- **Fix**: Movido el bloque UPDATE fuera del if/else, con su propio `if (empty($field_errors))`.

#### BUG MENOR — `dashboard.php`: campo `ubicacion` inexistente en cálculo de perfil
- **Archivo**: `public/empresa/dashboard.php` línea 25
- **Problema**: `$campos_perfil` incluía `'ubicacion'` pero la columna en la tabla `empresas` que llena el formulario se llama `'direccion'`. El campo `ubicacion` lo setea el ministerio (zona del parque), no la empresa desde su perfil.
- **Efecto**: El % de perfil completo siempre era 1 punto más bajo de lo real.
- **Fix**: Cambiado `'ubicacion'` → `'direccion'` en el array `$campos_perfil`.

### Estado de bugs históricos del VITACORA (verificado 2026-06-05)
Todos los bugs de sesiones anteriores confirmados resueltos en la BD actual:
- ✅ banners_home: tabla existe, código correcto, tabla vacía (carrusel vacío = normal)
- ✅ Duplicados rubros: 23 rubros, sin duplicados
- ✅ Duplicados empresas: 2 registros, sin duplicados
- ✅ token_expira: columna correcta en usuarios, auth.php la usa bien
- ✅ Tablas faltantes: login_attempts, password_reset_requests, notificaciones — todas existen
- ✅ config/database.sql: eliminado (ya no existe)

### Funcionalidades nuevas implementadas

#### Onboarding empresa
- Card "Primeros pasos" en dashboard con checklist (perfil + formulario de presentación)
- Se oculta sola cuando ambos pasos están completos
- Link "Presentación" en sidebar (aparece solo si el ministerio creó ese formulario dinámico)

#### Historial de formularios dinámicos
- Nueva sección "Formularios del Ministerio" en `empresa/formularios.php`
- Muestra formularios asignados (pendiente/borrador/enviado) con acciones Responder/Continuar/Ver
- Botón "← Volver a mis formularios" en `formulario_dinamico.php`

#### Métricas empresa (`mis-datos.php`)
- Nueva página con Chart.js: empleados (línea), género (donut), consumos (barras), capacidad (barras), producción (tabla), comercio exterior (cards), CO₂ (línea)
- Solo muestra datos de declaraciones enviadas/aprobadas
- Link "Mis datos" en sidebar empresa

#### Config métricas ministerio (`empresa-metricas.php`)
- Nueva página en panel ministerio (sidebar: Contenido del sitio → Métricas empresa)
- 9 checkboxes para elegir qué bloques ve cada empresa
- Guardado en `configuracion_sitio` — mismo patrón que estadísticas públicas

---

## SESIÓN 5 — QA Final Completo (2026-06-06)

### Objetivo
Testeo visual y funcional end-to-end del portal completo antes de la entrega. Recorrido ordenado: sitio público → panel empresa → panel ministerio.

### Mapa de archivos verificado
- Archivo huérfano confirmado: `public/css/empresa-inbox.css` — solo referenciado en docs, no incluido por ningún PHP.
- `public/parque.php` y `public/nosotros.php` — son redirects 301 intencionales a `el-parque.php` (SEO).
- `public/ministerio/nosotros-editar.php` — redirige a `sitio-publico.php?tab=el_parque`; código muerto debajo del redirect (sin impacto).
- Contraseña de todos los usuarios seed: **`admin123`**

### Bugs encontrados y corregidos

#### Bug 1 — Eje Y con decimales en gráfico "Empresas por Rubro"
- **Archivo**: `public/ministerio/dashboard.php:201`
- **Problema**: Chart.js mostraba `0.5` en el eje Y cuando solo había 1 empresa (valor entero pequeño).
- **Fix**: Agregado `ticks: { precision: 0, stepSize: 1 }` a la config del eje Y.

#### Bug 2 — Dropdown "Enviados (pendientes)" truncado
- **Archivo**: `public/ministerio/formularios.php:120`
- **Problema**: Columna `col-md-2` demasiado estrecha; el texto "Enviados (pendientes)" aparecía cortado como "Enviados (pend".
- **Fix**: Cambiado a `col-md-3`.

#### Bug 3 — Tildes faltantes en "conversación"
- **Archivo**: `includes/partials/comunicaciones_panel.php`
- **Problema**: Tres ocurrencias de "conversacion" sin tilde (botón "Nueva conversacion", estado vacío, título del modal).
- **Fix**: Corregido a "conversación" en las 3 apariciones.

#### Bug 4 — Input de logo truncado ("Sin a...ados")
- **Archivo**: `public/empresa/perfil.php:348`
- **Problema**: `<input type="file" class="form-control">` en columna estrecha mostraba el texto nativo del browser "Sin archivos seleccionados" recortado a "Sin a...ados".
- **Fix**: Reemplazado por botón custom "Seleccionar logo" (oculta el input nativo) + `<span id="logoFileName">` que se actualiza vía JS al seleccionar archivo. La preview de imagen sigue funcionando.

#### Bug 5 — Plural incorrecto en banner de impacto del home
- **Archivo**: `public/index.php:96`
- **Problema**: El texto siempre decía "N rubros industriales" en plural, incluso cuando N=1 ("1 rubros industriales"). Ídem para "empleos directos".
- **Fix**: Lógica singular/plural: `1 rubro industrial` / `N rubros industriales`; `1 empleo directo` / `N empleos directos`.

### Páginas verificadas sin bugs

**Sitio público:**
- `index.php` — hero, KPIs, banner impacto, grid de empresas, footer ✅
- `presentar-proyecto.php` — formulario público completo con breadcrumb ✅
- `el-parque.php` — carga con contenido y mapa ✅

**Panel empresa:**
- `empresa/dashboard.php` — KPIs, acciones rápidas, banner de empresa suspendida ✅
- `empresa/perfil.php` — formulario de edición con logo custom, galería ✅
- `empresa/formularios.php` — declaración jurada completa (secciones: personal, capacidad, consumos, huella carbono) ✅
- `empresa/publicaciones.php` — estado vacío con botón "Crear publicación" ✅
- `empresa/comunicaciones.php` — Centro de Comunicaciones con lista de conversaciones ✅

**Panel ministerio:**
- `ministerio/dashboard.php` — 6 KPIs, acciones rápidas, gráfico barras (eje Y entero), mini mapa Leaflet, actividad reciente ✅
- `ministerio/formularios.php` — tabla con filtros (dropdown "Enviados (pendientes)" completo), modales detalle y revisión ✅
- `ministerio/comunicaciones.php` — lista de conversaciones con tildes correctas ✅
- `ministerio/sitio-publico.php` — 3 tabs (Inicio, El Parque, Contacto) funcionando ✅
- `ministerio/publicaciones.php` — 2 tabs (Contenido propio, Revisión empresas) ✅
- `ministerio/empresa-metricas.php` — 9 bloques configurables con altura uniforme (h-100) ✅
- `ministerio/graficos.php` — gráficos con datos de seed, mapa de calor Leaflet ✅
- `ministerio/exportar.php` — botones Excel/CSV para directorio y declaraciones ✅
- `ministerio/reporte.php` — redirige con flash "No hay períodos con datos declarados" cuando no hay datos ✅

### Comportamientos esperados (no son bugs)
- Gráficos vacíos ("Evolución de empleo", "Consumos por rubro", "Huella de carbono") — el seed no tiene declaraciones con datos de consumo.
- Banner "Su empresa ha sido suspendida temporalmente" en dashboard empresa demo — el seed crea la empresa en estado `suspendida`.
- `ministerio/reporte.php` redirige al dashboard — correcto cuando no hay períodos declarados.

### Archivos modificados en esta sesión
- `public/ministerio/dashboard.php` — fix eje Y Chart.js
- `includes/partials/comunicaciones_panel.php` — fix tildes "conversación"
- `public/ministerio/formularios.php` — fix ancho columna dropdown
- `public/empresa/perfil.php` — fix input logo custom
- `public/index.php` — fix plural singular rubros/empleados

---

## Sesión 6 — Correcciones visuales + Mejoras en todos los mapas (2026-06-06)

### Bugs corregidos

| # | Archivo | Descripción |
|---|---------|-------------|
| 1 | `public/ministerio/dashboard.php` | Acciones Rápidas: 5 botones con distinto tamaño y color → grid uniforme `btn btn-primary`, `row-cols-md-5`, `d-flex flex-column` con ícono centrado y texto |
| 2 | `includes/ministerio_layout_header.php` | Ítem "Empresas" en sidebar sin ícono → `fa-buildings` (FA Pro, roto) reemplazado por `fa-city` (FA Free) |
| 3 | `public/mapa.php` | Mapa interactivo principal usaba tiles Esri hardcodeados sin llamar a `ParqueLeaflet.addSatelliteLayer()` → sin restricciones de zoom/bounds. Corregido: ahora usa la función compartida (aplica `constrainMap` internamente: minZoom 14, maxZoom 19, maxBounds del parque) |
| 4 | `public/mapa.php` | `coloresRubro` sin normalizar tildes: `METALURGICA` no matcheaba `METALÚRGICA`, etc. → agregado helper `getRubroColor()` que normaliza via `NFD` antes de buscar en el mapa |
| 5 | `public/ministerio/graficos.php` | Heatmap inicializaba en zoom 12, por debajo del `MAP_MIN_ZOOM = 14` que impone `addSatelliteLayer()` → corregido a zoom 15 |

### Contexto de todos los mapas del portal

| Mapa | Archivo | Propósito | Interactivo | Freezed |
|------|---------|-----------|-------------|---------|
| `#mapFull` | `public/mapa.php` | Mapa público completo: lista de empresas + filtros + marcadores | Sí | No |
| `#mapParqueIndex` | `public/index.php` | Miniatura en el hero del home: solo polígono del parque | No | Sí |
| `#empresaMap` | `public/empresa.php` | Ubicación de empresa en su perfil público | No | Sí |
| `#mapElParque` | `public/el-parque.php` | Página institucional: mapa explorable del parque | Sí | No |
| `#mapPicker` | `public/empresa/perfil.php` | Picker de coordenadas para que la empresa marque su ubicación | Sí | No |
| *(dinámico)* | `public/empresa/formulario_dinamico.php` | Campos de tipo "ubicación" en formularios configurables | Sí | No |
| `#miniMap` | `public/ministerio/dashboard.php` | Miniatura del parque en el dashboard ministerio | No | Sí |
| `#heatMap` | `public/ministerio/graficos.php` | Mapa de calor de empleados por empresa | No | No |
| `#mapDetalle` | `public/ministerio/empresa-detalle.php` | Ubicación de empresa en el panel ministerio | No | Sí |

Todos usan `ParqueLeaflet.addSatelliteLayer()` (que internamente aplica `constrainMap`: minZoom 14, maxZoom 19, bounds del parque). La capa de tiles Esri World Imagery más overlay OSM semitransparente (0.35) en `mapa.php` para efecto híbrido (etiquetas de calles sobre satélite).

### Archivos modificados en esta sesión
- `public/ministerio/dashboard.php` — Acciones Rápidas unificadas
- `includes/ministerio_layout_header.php` — ícono Empresas fa-city
- `public/mapa.php` — tiles → addSatelliteLayer, helper getRubroColor con normalización NFD
- `public/ministerio/graficos.php` — zoom heatmap 12 → 15

*Este archivo se actualiza en cada sesión de trabajo. Usarlo como punto de entrada en nuevos chats.*
