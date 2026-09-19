# Manual de instalación — Portal Parque Industrial de Catamarca

Sistema web del Parque Industrial de Catamarca con tres partes: sitio público, panel de empresas (`/empresa`) y panel del Ministerio (`/ministerio`).

Desarrollo: DaviNic Developer — Jorge David Nicolau López — davinic7@gmail.com — 3834-277003

---

## 1. Resumen técnico

| Componente | Detalle |
|---|---|
| Lenguaje | PHP 8.2, sin framework ni Composer |
| Base de datos | MySQL 5.7+ / 8.x o MariaDB 10.4+ (utf8mb4, InnoDB) |
| Servidor web | Apache 2.4 (con `mod_rewrite`) o Nginx + PHP-FPM |
| Frontend | Bootstrap 5, Leaflet 1.9.4, JavaScript sin compilar (no requiere Node ni npm); librerías incluidas en `public/vendor/` |
| Raíz web | La carpeta **`public/`** |
| Estructura | 28 tablas + 2 vistas |

---

## 2. Requisitos del servidor

### 2.1 Sistema operativo y recursos

- Linux recomendado (Ubuntu Server 22.04/24.04 o Debian 12). Funciona también en Windows con Apache.
- Mínimo: 2 vCPU, 2 GB RAM, 20 GB de disco (el disco crece con los archivos que suben las empresas).

### 2.2 PHP 8.2 (o superior 8.x)

Extensiones necesarias:

| Extensión | Uso |
|---|---|
| `pdo_mysql` | Conexión a la base de datos |
| `curl` (con soporte SSL) | Envío de correos, reCAPTCHA y Cloudinary |
| `mbstring` | Manejo de texto |
| `iconv` | Exportaciones |
| `openssl` | Conexiones seguras |
| `json`, `session` | Incluidas por defecto |

En Ubuntu/Debian:

```bash
sudo apt install apache2 php8.2 libapache2-mod-php8.2 php8.2-mysql php8.2-curl php8.2-mbstring
sudo a2enmod rewrite
```

Valores de `php.ini` (el sistema admite archivos de hasta 10 MB):

```ini
upload_max_filesize = 12M
post_max_size = 16M
memory_limit = 256M
max_execution_time = 60
date.timezone = America/Argentina/Catamarca
```

### 2.3 Base de datos

- MySQL 8 o MariaDB 10.6+ (recomendado).
- Una base `parque_industrial` y **un usuario exclusivo** con permisos sólo sobre esa base (no usar `root`).

### 2.4 Salida a Internet del servidor

El servidor necesita salida HTTPS hacia estos servicios **sólo si se usan** (ver sección 5):

| Destino | Para qué |
|---|---|
| `api.resend.com:443` | Envío de correos (opción A) |
| `smtp.gmail.com:465` | Envío de correos (opción B) |
| `www.google.com:443` | Validación de reCAPTCHA |
| `api.cloudinary.com:443` | Almacenamiento externo de imágenes (opcional) |

Bootstrap, los iconos, las tipografías, Leaflet, Chart.js y el resto de librerías se sirven desde el propio sitio (`public/vendor/`), por lo que **no dependen de ningún CDN**. Lo único que los navegadores de los usuarios piden a servicios externos son las teselas de los mapas (OpenStreetMap y Esri/ArcGIS) y, si está activado, reCAPTCHA (`www.google.com`, `www.gstatic.com`). Si la red del Ministerio filtra esos dominios, el sitio se ve completo pero sin el fondo del mapa.

---

## 3. Contenido del paquete entregado

```
parque_industrial/
├── config/          Configuración (config.php, database.php)
├── includes/        Código compartido (autenticación, funciones, plantillas)
├── public/          ← RAÍZ WEB (lo único que debe ser accesible desde Internet)
│   ├── empresa/     Panel de empresas
│   ├── ministerio/  Panel del Ministerio (incluye cron/)
│   ├── api/         Endpoints internos
│   └── uploads/     Archivos subidos por usuarios (requiere escritura)
├── database/
│   └── parque_industrial_oficial.sql   ← Script de instalación
├── logs/            Registro de errores (requiere escritura)
├── .env.example     Plantilla de configuración
└── INSTALACION.md   Este documento
```

---

## 4. Instalación paso a paso

### Paso 1 — Copiar los archivos

```bash
sudo mkdir -p /var/www/parque_industrial
# copiar el contenido del paquete dentro de esa carpeta
sudo chown -R www-data:www-data /var/www/parque_industrial
sudo find /var/www/parque_industrial -type d -exec chmod 755 {} \;
sudo find /var/www/parque_industrial -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/parque_industrial/public/uploads /var/www/parque_industrial/logs
```

### Paso 2 — Crear la base de datos

El script `database/parque_industrial_oficial.sql` crea la base, las tablas, las vistas y los datos iniciales (usuarios administradores, rubros, configuración del sitio y ubicación del parque). No incluye empresas de prueba.

```bash
# 1) Crear usuario (como administrador de MySQL)
mysql -u root -p
```

```sql
CREATE DATABASE parque_industrial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'parque_app'@'localhost' IDENTIFIED BY 'CONTRASEÑA_SEGURA';
GRANT ALL PRIVILEGES ON parque_industrial.* TO 'parque_app'@'localhost';
FLUSH PRIVILEGES;
```

```bash
# 2) Importar la estructura (como administrador de MySQL)
mysql -u root -p < /var/www/parque_industrial/database/parque_industrial_oficial.sql
```

> **Nota sobre las vistas:** el script define las vistas con `DEFINER=root@localhost`. Si se importa con otro usuario o el servidor no tiene `root@localhost`, la importación falla o las vistas quedan inutilizables. En ese caso, borrar las líneas `DEFINER=...` del script antes de importar, o importarlo con un usuario administrador.

### Paso 3 — Configurar el archivo `.env`

```bash
cd /var/www/parque_industrial
cp .env.example .env
chmod 640 .env && chown www-data:www-data .env
```

Valores mínimos para producción:

```ini
APP_ENV=production
APP_DEBUG=0
SITE_URL=https://parqueindustrial.catamarca.gob.ar
SESSION_COOKIE_SECURE=1
FORCE_HTTPS=1

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=parque_industrial
DB_USER=parque_app
DB_PASS=CONTRASEÑA_SEGURA
DB_CHARSET=utf8mb4
DB_SSL_CA=

CRON_SECRET=una_clave_larga_y_aleatoria
```

Importante:

- `SITE_URL` va **sin barra final** y es la dirección exacta del sitio. Se usa para generar los enlaces de los correos.
- En todos los entornos (también en desarrollo) `SITE_URL` debe apuntar a la carpeta `public/`, que es la raíz web (paso 4). Si no, los enlaces se rompen.
- Con Apache, `AllowOverride All` debe estar activo en el DocumentRoot para que se apliquen `public/.htaccess` y `public/uploads/.htaccess` (el `dockerfile` ya lo configura).

### Paso 4 — Configurar el servidor web

**Apache** (`/etc/apache2/sites-available/parque_industrial.conf`):

```apache
<VirtualHost *:80>
    ServerName parqueindustrial.catamarca.gob.ar
    DocumentRoot /var/www/parque_industrial/public

    <Directory /var/www/parque_industrial/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/parque_error.log
    CustomLog ${APACHE_LOG_DIR}/parque_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite parque_industrial && sudo systemctl reload apache2
```

**Nginx + PHP-FPM:**

```nginx
server {
    listen 80;
    server_name parqueindustrial.catamarca.gob.ar;
    root /var/www/parque_industrial/public;
    index index.php;
    client_max_body_size 16M;

    location / { try_files $uri $uri/ =404; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    # Nunca ejecutar PHP dentro de uploads
    location ~* ^/uploads/.*\.(php|phtml|phar)$ { deny all; }
}
```

> En Apache la protección de `uploads/` ya viene en `public/uploads/.htaccess`. En Nginx hay que agregar la regla de arriba porque Nginx no lee `.htaccess`.

### Paso 5 — HTTPS

Instalar un certificado (del Gobierno o Let's Encrypt):

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d parqueindustrial.catamarca.gob.ar
```

### Paso 6 — Tareas programadas (cron)

```bash
sudo crontab -u www-data -e
```

```cron
# Recordatorios de formularios con vencimiento próximo (diario 08:00)
0 8 * * * php /var/www/parque_industrial/public/ministerio/cron/recordatorios-formularios.php
# Limpieza de tokens vencidos (cada hora)
0 * * * * php /var/www/parque_industrial/public/ministerio/cron/limpiar-tokens.php
# Limpieza de intentos de login (diario 03:00)
0 3 * * * php /var/www/parque_industrial/public/ministerio/cron/limpiar-login-attempts.php
# Purga del historial que crece sin límite: registro de actividad y visitas (diario 03:30)
30 3 * * * php /var/www/parque_industrial/public/ministerio/cron/limpiar-historial.php
```

`limpiar-historial.php` conserva por defecto 365 días de `log_actividad` y 180 de `visitas_empresa` (se cambia con `LOG_RETENCION_DIAS` y `VISITAS_RETENCION_DIAS` en el `.env`; el mínimo es 30). `--dry-run` solo informa cuántas filas borraría. Además crea, si falta, el índice `idx_fecha` de `visitas_empresa` (necesario en bases instaladas antes de septiembre de 2026).

Por consola no necesitan clave. Si se ejecutan por HTTP, requieren `?key=CRON_SECRET`.

### Paso 7 — Primer ingreso y seguridad

Usuarios iniciales creados por el script:

| Rol | Usuario |
|---|---|
| Administrador | `admin@parqueindustrial.gob.ar` |
| Ministerio | `ministerio@catamarca.gob.ar` |

La contraseña inicial se entrega **por separado**. **Cambiarla en el primer ingreso** y, si corresponde, reemplazar esos correos por cuentas reales del Ministerio.

---

## 5. Servicios externos

### 5.1 Correo electrónico (necesario para activar cuentas de empresas y recuperar contraseñas)

El sistema envía correos con **una** de estas opciones. Sin ninguna, el resto del sistema funciona, pero no salen los mails de activación ni de recuperación.

**Opción A — Resend (recomendada):** crear cuenta en resend.com, verificar el dominio del remitente (registros DNS) y generar una API key.

```ini
RESEND_API_KEY=re_xxxxxxxx
MAIL_FROM=no-reply@parqueindustrial.catamarca.gob.ar
```

**Opción B — Cuenta de Gmail/Google Workspace** con contraseña de aplicación:

```ini
GMAIL_USER=cuenta@dominio
GMAIL_APP_PASSWORD=xxxx xxxx xxxx xxxx
```

> Si el Ministerio quiere usar **su propio servidor de correo (SMTP)**, hay que avisar al desarrollador: requiere una adaptación menor del código.

### 5.2 Google reCAPTCHA v2 (protección de formularios)

Registrar el dominio del sitio en https://www.google.com/recaptcha/admin y cargar:

```ini
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
```

### 5.3 Cloudinary (opcional)

Sin estas variables, las imágenes y adjuntos se guardan en `public/uploads/` del propio servidor, que es lo recomendado en un servidor propio con backups. **Con hosting de disco efímero (p. ej. Render) son obligatorias**: los archivos de `public/uploads/` se pierden en cada despliegue.

Con las tres variables definidas, se guardan en Cloudinary (carpeta `parque_industrial/<tipo>`): las imágenes como `image` y los demás documentos (PDF, Word, Excel, ZIP…) como `raw`. Esto cubre logos, galería, publicaciones, adjuntos del Centro de Comunicaciones, archivos de formularios y documentos de "Presentar proyecto". Si Cloudinary falla en una subida, ese archivo se guarda en disco local como respaldo (y se pierde en el próximo despliegue, así que conviene revisar el log de errores: `cloudinary_upload`).

```ini
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
```

---

## 6. Verificación posterior a la instalación

- [ ] El inicio (`/`) carga con estilos e imágenes.
- [ ] `/mapa.php` muestra el mapa.
- [ ] `/login.php` permite ingresar con el usuario del Ministerio.
- [ ] Desde el panel del Ministerio se puede crear una empresa y le llega el correo de activación.
- [ ] La empresa puede subir su logo (verifica permisos de `uploads/`).
- [ ] "Recuperar contraseña" envía el correo.
- [ ] `/presentar-proyecto.php` envía una solicitud y aparece en el panel del Ministerio.
- [ ] `https://SITIO/.env`, `https://SITIO/config/config.php` y `https://SITIO/database/` devuelven 404 (no son accesibles desde el navegador).
- [ ] `logs/error.log` no registra errores nuevos.
- [ ] Los cron se ejecutan (`php …/limpiar-tokens.php` debe mostrar `OK tokens limpiados`; `php …/limpiar-historial.php --dry-run` debe mostrar `SIMULACION …`).

---

## 7. Mantenimiento

### Copias de seguridad (diarias)

```bash
# Base de datos
mysqldump -u parque_app -p --single-transaction --routines parque_industrial | gzip > /backups/parque_$(date +%F).sql.gz
# Archivos subidos
tar czf /backups/uploads_$(date +%F).tar.gz -C /var/www/parque_industrial/public uploads
```

Guardar también una copia del `.env`, que contiene las claves.

### Actualizaciones

1. Hacer un backup de la base y de `uploads/`.
2. Reemplazar el código **sin sobrescribir** `.env`, `public/uploads/` ni `logs/`.
3. Si la versión trae cambios de base de datos, ejecutar los scripts SQL que se indiquen.

### Registros

- Errores de la aplicación: `logs/error.log`.
- Errores del servidor web: logs de Apache/Nginx.

---

## 8. Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| "Error de conexión a la base de datos" | Datos `DB_*` incorrectos en `.env` o el usuario no tiene permisos |
| Sitio sin estilos o enlaces rotos | `SITE_URL` mal escrita o la raíz web no apunta a `public/` |
| Error al subir archivos | Permisos de `public/uploads/` o límites de `php.ini` |
| No llegan correos | Falta `RESEND_API_KEY`/`GMAIL_*` o el servidor no tiene salida a Internet |
| Error al importar el SQL ("DEFINER") | Ver nota del Paso 2 |
| Los paneles no muestran estadísticas | Las vistas no se crearon: reimportar con usuario administrador |
| Redirecciones infinitas a HTTPS | `FORCE_HTTPS=1` detrás de un proxy que no envía `X-Forwarded-Proto` |
