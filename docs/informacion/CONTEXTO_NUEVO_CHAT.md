# Contexto — Parque Industrial de Catamarca

> Pasá este archivo al inicio del chat nuevo. Es todo lo que necesitás saber para continuar trabajando en este proyecto.

---

## El proyecto en una línea

Portal web PHP para el Parque Industrial de Catamarca (Argentina). Lo gestiona el Ministerio de Producción. Tiene **tres interfaces** independientes: sitio público, panel de empresa, y panel de ministerio.

---

## Stack técnico

| Qué | Cómo |
|-----|------|
| Lenguaje | PHP 8.2 puro, sin framework |
| DB | MySQL/MariaDB vía PDO (Laragon local) |
| Frontend | Bootstrap 5 + Vanilla JS, sin bundler |
| Mapa | Leaflet.js 1.9.4 + tiles Esri satélite |
| Uploads | Cloudinary (prod) o `public/uploads/` (local) |
| Deploy | Render.com |
| Fonts | Inter (body) + Montserrat (headings) |

---

## Estructura de carpetas

```
public/             → document root del servidor PHP
  *.php             → sitio público (index, empresas, mapa, noticias, etc.)
  empresa/          → dashboard empresa (protegido por rol)
  ministerio/       → panel admin (protegido por rol)
  css/
    styles.css      → CSS del sitio público
    empresa-app.css → CSS de ambos dashboards (empresa + ministerio)
  js/
    main.js         → JS general
    parque-leaflet.js → mapa interactivo
config/
  config.php        → constantes, sesión, helpers globales
  database.php      → PDO singleton (getDB())
includes/
  auth.php          → clase Auth: login, roles, lockout por IP
  funciones.php     → helpers: e(), csrf_field(), set_flash(), redirect(), etc.
  header.php        → navbar pública + meta SEO + breadcrumbs
  footer.php        → footer público
  empresa-header.php  → layout sidebar dashboard empresa
  ministerio-header.php → layout sidebar panel ministerio
database/
  parque_industrial_v2.sql  ← ARCHIVO UNIFICADO (usar este)
```

---

## Setup local

```bash
# Servidor PHP
php -S localhost:8080 -t public

# Importar DB (una sola vez, todo en uno)
mysql -u root -p < database/parque_industrial_v2.sql
```

**Laragon** corriendo con MySQL. phpMyAdmin en `http://localhost/phpmyadmin`.

El `.env` ya está creado en la raíz con la config de Laragon.

---

## Credenciales demo (todos usan `admin123`)

| Rol | Email |
|-----|-------|
| Admin | admin@parqueindustrial.gob.ar |
| Ministerio | ministerio@catamarca.gob.ar |
| Empresa demo | empresa@demo.com |
| Empresa real (ej) | empresa1@parqueindustrial.com |

Hash bcrypt de `admin123`: `$2y$10$xHWydNT4i4lVvD.OjuYOH.TjIJRZcoAS2nOO2vK1t7EBvHu4Dgo5C`

---

## Patrones de código que hay que respetar

### Guard de autenticación (top de cada página protegida)
```php
if (!$auth->requireRole(['empresa'], PUBLIC_URL . '/login.php')) exit;
```

### Queries — SIEMPRE PDO con placeholders
```php
$stmt = getDB()->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$id]);
```

### Output — SIEMPRE escapar con `e()`
```php
echo e($variable_de_usuario);
```

### Formularios POST
```php
// En el form: <?= csrf_field() ?>
// En el handler: if (!verify_csrf($_POST['csrf_token'])) { ... }
```

### Flash messages + redirect
```php
set_flash('success', 'Guardado correctamente.');
redirect(EMPRESA_URL . '/perfil.php');
```

---

## Base de datos — estado actual

**Archivo canónico:** `database/parque_industrial_v2.sql`  
Un solo import, sin pasos adicionales. Incluye todas las migraciones integradas.

### Tablas principales
| Tabla | Para qué |
|-------|---------|
| `usuarios` | Login. Roles: `empresa`, `ministerio`, `admin` |
| `empresas` | 79 empresas (ID 1 demo + IDs 22-99 reales) |
| `datos_empresa` | Declaraciones periódicas (consumo, empleo, producción) |
| `rubros` | 23 rubros en Title Case (Textil, Construcción, etc.) |
| `publicaciones` | Noticias/eventos. Flujo: borrador → pendiente → aprobado |
| `banners_home` | Carrusel home. Columnas: `tipo` (imagen/video), `url_video` |
| `mensajes` | Sistema de mensajería legacy (aún en uso) |
| `conversaciones` + `mensajes_v2` | Sistema de comunicaciones nuevo (centro de comunicaciones) |
| `formularios_dinamicos` | Formularios que crea ministerio, responde empresa |
| `formulario_respuestas` | Respuestas de empresas a formularios dinámicos |
| `notificaciones` | Notificaciones en campana del topbar |
| `configuracion_sitio` | Settings del portal (nombre, redes, coordenadas mapa) |
| `login_attempts` | Rate limiting / lockout por IP |
| `plantillas_respuesta` | Respuestas rápidas predefinidas para el ministerio |

### Vistas
- `v_empresas_completas` — JOIN empresas + último período de datos_empresa
- `v_estadisticas_generales` — conteos agregados para el home

### Columnas importantes a recordar
- `usuarios`: tiene `token_activacion`, `token_activacion_expira`, `email_verificado`
- `mensajes`: tiene columna `categoria VARCHAR(80)`
- `banners_home`: fue renombrada de `banners`, tiene `tipo ENUM('imagen','video')` y `url_video`

---

## Design System — "La Planta" (v2)

### Paleta
```
Navy (principal): #1b3a5c  /  dark: #0f2438  /  mid: #1e4a73
Amber (acento):   #c4601a  /  light: #e8813a  /  bg: #fdf0e4
Stone (fondo):    #f7f4f0  /  mid: #ede9e3  /  deep: #d9d3ca
Ink (texto):      #1a1714
Muted:            #6b6460
```

### Reglas de diseño aplicadas (NO romper)
- **Navbar** pública: fondo `#0f2438` sólido, sin gradiente
- **Active sidebar**: fondo semitransparente, **NUNCA** `border-left` de color
- **Action cards** dashboards: colores sólidos, sin gradientes
- **Sin** `border-left` > 1px como acento decorativo en cards/listas
- **Sin** gradient text (`background-clip: text`)
- Bootstrap CSS vars sobreescritos en `styles.css` para que `bg-primary` use `#1b3a5c`

### Archivos CSS relevantes
- `public/css/styles.css` — sitio público completo
- `public/css/empresa-app.css` — layout sidebar + topbar de dashboards

---

## Lo que ya está hecho

- [x] Setup local funcionando (Laragon + PHP built-in server)
- [x] DB unificada en un solo archivo (`parque_industrial_v2.sql`)
- [x] Todos los bugs de DB corregidos (banners_home, duplicados rubros/empresas, columnas faltantes, contraseñas)
- [x] Design System v2 aplicado (navbar, cards, sidebar, login, colores, tipografía)
- [x] Fuentes actualizadas a Inter + Montserrat
- [x] Bootstrap CSS vars sobreescritos para paleta Navy/Amber/Stone

---

## Lo que FALTA hacer (empezar por acá)

### Funcionalidades a probar y corregir (en orden de prioridad)

1. **Flujo crear empresa** (ministerio → nueva empresa → activar cuenta → login empresa)
   - Archivo: `public/ministerio/nueva-empresa.php`
   - El flujo manda email de activación — revisar si funciona con la config de `.env`
   - Página de activación: `public/activar-cuenta.php`

2. **Formularios dinámicos** (ministerio crea → empresa responde → ministerio revisa)
   - Ministerio: `public/ministerio/formularios.php`
   - Empresa: `public/empresa/formularios.php`
   - Revisar que el JSON de campos se guarde y renderice bien

3. **Publicaciones** (empresa propone → ministerio aprueba → aparece en noticias)
   - Empresa sube: `public/empresa/publicaciones.php`
   - Ministerio aprueba: `public/ministerio/publicaciones.php`
   - Pública: `public/noticias.php` y `public/publicacion.php`

4. **Banners del home** (ministerio sube → aparece en carrusel)
   - Ministerio: `public/ministerio/banners.php`
   - Home: `public/index.php` (query a `banners_home`)

5. **Centro de comunicaciones** (mensajería nueva con `conversaciones` + `mensajes_v2`)
   - `public/empresa/comunicaciones.php`
   - `public/ministerio/comunicaciones.php`
   - Las tablas del sistema v2 existen pero la UI puede tener bugs

6. **Export Excel/PDF** desde dashboards
   - Buscar en `public/ministerio/` archivos de export

7. **Declaración de datos** (empresa completa el formulario de producción/consumo del período)
   - `public/empresa/datos.php` o similar

### Mejoras UI/UX pendientes
- Responsive mobile (breakpoints en navbar, dashboards)
- Empty states cuando no hay datos (noticias vacías, sin formularios, etc.)
- Mensajes de error más descriptivos en formularios

---

## Comandos útiles para debugging

```php
// Ver vars de sesión
var_dump($_SESSION);

// Ver última query PDO (agregar en funciones.php si hace falta)
// PDO no tiene lastQuery() nativo — usar log o wrappear getDB()

// Hash de una contraseña nueva
echo password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);
```

---

## URLs del proyecto

- Sitio público: `http://localhost:8080/`
- Login: `http://localhost:8080/login.php`
- Dashboard empresa: `http://localhost:8080/empresa/dashboard.php`
- Panel ministerio: `http://localhost:8080/ministerio/dashboard.php`
- phpMyAdmin: `http://localhost/phpmyadmin`

---

## Constantes clave (de `config/config.php`)

```php
PUBLIC_URL      // URL base del sitio público
EMPRESA_URL     // URL base del panel empresa
MINISTERIO_URL  // URL base del panel ministerio
BASEPATH        // Ruta absoluta al filesystem del proyecto
CSRF_TOKEN_NAME // Nombre del campo CSRF en formularios
```

---

> **Tip para el nuevo chat:** Podés pedir screenshots del browser navegando a `http://localhost:8080` para ver el estado visual actual antes de editar código. El servidor PHP ya debería estar corriendo.
