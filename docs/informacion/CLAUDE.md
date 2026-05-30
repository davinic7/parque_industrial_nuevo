# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Parque Industrial de Catamarca** — a PHP portal and admin panel for an industrial park in Argentina. Three distinct interfaces: public-facing site, company dashboard (`/empresa`), and ministry management panel (`/ministerio`).

## Development Commands

```bash
# Local development server
php -S localhost:8080 -t public

# Import database schema (first-time setup — archivo unificado)
mysql -u root -p < database/parque_industrial_v2.sql

# Docker build & run
docker build -t parque-industrial .
docker run -p 80:8080 --env-file .env parque-industrial
```

No build step — JS/CSS are served as-is, no compilation needed.

## Environment Setup

Copy `.env.example` to `.env`. Key variables:

- `APP_ENV` — `development` | `production` (controls error display)
- `SITE_URL` — base URL (e.g., `http://localhost:8080`)
- `DB_*` — MySQL connection; use `DB_SSL_CA=config/ca.pem` for cloud databases
- `CLOUDINARY_*` — required for persistent file uploads in production (Render wipes disk on redeploy)
- `CRON_SECRET` — validates HTTP-triggered cron requests

## Architecture

### Page Structure

Every page follows the same pattern — no framework, no router:

```
public/           → document root (web server points here)
config/           → config.php (constants), database.php (PDO singleton)
includes/         → shared PHP: auth.php, funciones.php, layout headers/footers
database/         → SQL schema + migration patches
```

**Public pages** (`public/*.php`): load config, query DB, include `header.php`, render HTML, include `footer.php`.

**Dashboard pages** (`public/empresa/*.php`, `public/ministerio/*.php`): same pattern but with role-check guards and dashboard-specific layout headers.

### Authentication & Sessions

`includes/auth.php` — `Auth` class handles login, role checks, IP-based lockout.

Guard pattern at top of every protected page:
```php
if (!$auth->requireRole(['empresa'], PUBLIC_URL . '/login.php')) exit;
```

Session keys: `user_id`, `user_email`, `user_rol` (`empresa`/`ministerio`/`admin`), `empresa_id`, `logged_in`, `csrf_token`.

### Data Flow

1. Form POST → same page or API endpoint
2. CSRF check: `verify_csrf($_POST['csrf_token'])`
3. PDO prepared statement (`?` placeholders only — no string interpolation in queries)
4. `set_flash()` → `redirect()` → `show_flash()` on next page

### Key Helper Functions (`includes/funciones.php`)

- `e($str)` — HTML escape (use everywhere user content is output)
- `csrf_field()` / `verify_csrf()` — CSRF protection
- `set_flash()` / `get_flash()` / `show_flash()` — session-based flash messages
- `redirect($url)` — send Location header + exit
- `upload_image_storage()` — auto-routes to Cloudinary or local disk
- `log_activity($user_id, $action, $description)` — audit log
- `get_periodo_actual()` — returns current submission period (YYYY-MM)
- `format_date()`, `format_number()`, `format_currency()` — localized formatting

### File Uploads

Two-path strategy: if Cloudinary env vars are set → upload to Cloudinary (returns absolute URL). Otherwise → write to `public/uploads/` (ephemeral; unsuitable for production on Render).

### Frontend

- **Bootstrap 5** — all layout, modals, forms, navigation
- **Leaflet.js 1.9.4** + Esri satellite tiles — interactive map (`public/js/parque-leaflet.js`)
- **Vanilla JS** (`public/js/main.js`) — scroll animations, auto-dismiss alerts, Bootstrap tooltips, deletion confirmations, image preview on file input
- No npm, no bundler

### Database

MySQL/MariaDB via PDO singleton (`getDB()` from `config/database.php`). Key tables: `empresas`, `usuarios`, `datos_empresa`, `formularios_dinamicos`, `mensajes`, `notificaciones`, `publicaciones`, `log_actividad`. Two views: `v_empresas_completas` (profile completion %), `v_estadisticas_generales` (aggregate stats).

Manual migrations: add SQL files to `database/` and run them manually — no migration tool.

### Cron Jobs

HTTP-triggered scripts in `public/ministerio/cron/`, validated via `CRON_SECRET` header in `includes/cron_guard.php`. No queue or retry logic.

## Security Invariants

- Always use `e()` when echoing any user-supplied data
- Always use PDO prepared statements — never interpolate variables into SQL strings
- Always verify CSRF tokens on POST handlers: `verify_csrf($_POST['csrf_token'])`
- `password_hash()` / `password_verify()` for all passwords — never store plain text
