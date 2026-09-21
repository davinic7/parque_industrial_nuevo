# Pendientes — Portal Parque Industrial de Catamarca

Actualizado: 2026-09-21. Estado: rama `claude/modest-turing-z636ws`, árbol de trabajo limpio, todas las suites en verde (ver "Cómo probar").

## Por dónde continuar

La lista de **mejoras de código está cerrada**. Lo que queda es de **despliegue** y de **verificación con servicios reales**. Orden sugerido:

1. **Preparar el servidor de producción** — sección "Antes de desplegar" (abajo). Es lo obligatorio; sin esto no se publica. Los puntos más importantes: cambiar las contraseñas demo, usuario de base de datos con permisos mínimos, HTTPS, reCAPTCHA, `CRON_SECRET`.
2. **Programar los cron** en el servidor (`INSTALACION.md`, paso 6). Incluye `limpiar-historial.php`, que todavía no está programado: hasta entonces `log_actividad` y `visitas_empresa` siguen creciendo.
3. **Probar en un Apache real**: los `.htaccess` (bloqueo de scripts en `uploads/`, cabeceras) no se pudieron probar en local porque el servidor de PHP los ignora.
4. **Probar Cloudinary con una cuenta real** (solo se probó contra un Cloudinary simulado): subir un PDF, un Word y una imagen desde Comunicaciones y comprobar que abren.
5. **Dos tareas manuales en el entorno local** (este entorno de desarrollo no pudo hacerlas por permisos):
   - Borrar los 2 PDF de prueba (69 bytes) de `public/uploads/mensajes/`.
   - Ejecutar en la base local `DROP TABLE mensajes;` (tabla vieja, vacía, ya sin uso).
6. Opcional, si se quiere seguir mejorando el código: ver "Mejoras opcionales".

## Antes de desplegar a producción (obligatorio)

- [ ] **Cambiar contraseñas de las cuentas demo.** `admin@parqueindustrial.gob.ar`, `ministerio@catamarca.gob.ar` y `empresa@demo.com` tienen `admin123` en la base local; el seed de instalación usa `Demo1234`. No crear cuentas con clave conocida en producción.
- [ ] **Usuario de base de datos con permisos mínimos.** Hoy se usa `root` (contraseña `root123` en el `.env` local). Crear un usuario solo con permisos sobre `parque_industrial` (ver `INSTALACION.md`, sección 2.3).
- [ ] **`APP_ENV=production`** y `APP_DEBUG=0` en el servidor. Si falta la variable, ya es `production` por defecto, pero conviene declararla.
- [ ] **`FORCE_HTTPS=1`** y `SESSION_COOKIE_SECURE=1` (con HTTPS activo).
- [ ] **Claves de reCAPTCHA** (`RECAPTCHA_SITE_KEY` / `RECAPTCHA_SECRET_KEY`). Sin ellas, el login, la recuperación de contraseña y "Presentar proyecto" quedan abiertos a spam. En local están vacías.
- [ ] **`SITE_URL`** apuntando a la carpeta `public/` (raíz web), sin barra final.
- [ ] **Raíz web = `public/`.** Con Apache, `AllowOverride All` activo (el `dockerfile` ya lo configura) para que apliquen `public/.htaccess` y `public/uploads/.htaccess`.
- [ ] **Nginx (si no usan Apache):** agregar la regla que bloquea la ejecución de scripts en `uploads/` (`INSTALACION.md`, sección 4). Nginx no lee `.htaccess`.
- [ ] **`CRON_SECRET`** definido y las tareas programadas configuradas (`INSTALACION.md`, paso 6), incluido `limpiar-historial.php` (retención con `LOG_RETENCION_DIAS` / `VISITAS_RETENCION_DIAS`; por defecto 365 y 180 días).
- [ ] **Almacenamiento de archivos:** en hosting de disco efímero (p. ej. Render) hay que definir las tres variables `CLOUDINARY_*`; si no, los archivos subidos se pierden en cada despliegue.
- [ ] **Probar los `.htaccess` en Apache real.**

## Verificaciones pendientes con servicios reales

- [ ] **Cloudinary con cuenta real.** Los archivos ya guardados en disco local no se migran, y al reemplazar o borrar un archivo no se elimina el anterior en Cloudinary.
- [ ] **Correo (Resend o Gmail/SMTP)**: el envío real no se prueba en local.

## Mejoras opcionales

- [ ] **Archivos que aún superan las 500 líneas** (ya no mezclan JavaScript ni CSS en línea): `public/ministerio/sitio-publico.php` (595), `public/empresa/perfil.php` (573), `public/empresa/formulario_dinamico.php` (574), `public/ministerio/solicitudes-proyecto.php` (549) e `includes/comunicaciones.php` (552). Son vistas de PHP+HTML; partirlas en partials es posible pero aporta poco. Para verificar un refactor: `php tests/capturar_js.php <carpeta>` antes y después, y comparar el JavaScript, CSS y HTML resultantes.
- [ ] **Más pruebas automáticas.** El bloqueo de login por IP no se automatizó porque dejaría sin login a los demás tests durante 15 minutos.
- [ ] **Caché de portada y estadísticas: descartada.** Se midió: la portada hace 9 consultas (16 ms) y la página más pesada 14 (42 ms) con la base de demo; `get_config` ya se carga una vez por petición y el resto son agregados pequeños sobre `empresas`. Reevaluar solo si con datos de producción alguna página pasa de ~300 ms.

## Datos y base local

- La base local (`127.0.0.1`, `parque_industrial`) tiene el set de demo del seed viejo (8 empresas, 6 formularios), **no exactamente** los datos anteriores al 2026-09-19. Se perdieron: 1 solicitud de proyecto de prueba (`test@example.com`), 2 mensajes del Centro de Comunicaciones y 1 respuesta de formulario real.
- **No ejecutar `database/parque_industrial_oficial.sql` sobre una base con datos:** es un script de instalación completa (`DROP TABLE` + `CREATE TABLE` de todas las tablas, con `USE parque_industrial` fijo). Solo para bases nuevas.
- El SQL oficial define **28 tablas + 2 vistas**. Se quitaron las tablas sin uso `respuestas_formulario`, `formularios_config` y `mensajes` (esta última aún existe, vacía, en la base local: ver "Por dónde continuar", punto 5). Los archivos `database/parque_industrial.sql` y `parque_industrial_exportar.sql` son volcados antiguos y aún las incluyen.
- Los `seed_*.sql` borrados siguen en el historial de git (commit `b1f7a49`).

## Cómo probar

Requisito: PHP y Node. En Windows con Herd, usar el ejecutable directo (`C:\Users\<usuario>\.config\herd\bin\php84\php.exe`) y no el envoltorio `php.bat`, que rompe los argumentos con `<` o `|`. Al lanzar el servidor desde un script, cerrar el `php.exe` hijo (no solo el proceso que lo lanzó).

```bash
php -S localhost:8080 -t public      # servidor (la raíz web es public/)
npm run test:seguridad               # seguridad, <1 s, sin navegador ni servidor                        (11)
npm run test:aislamiento             # aislamiento entre empresas y escritura; crea/borra datos zz_test_* (20)
npm run test:ministerio              # permisos y escritura del panel del Ministerio                     (13)
npm run test:ministerio-gestion      # alta de empresas, reset de contraseña, formularios dinámicos y banners (23)
npm run test:cloudinary              # subidas a un Cloudinary simulado; levanta servidores en 8090/8091; exige .env sin CLOUDINARY_* (12)
npm run test:assets                  # ninguna página carga de un CDN y cada recurso local existe        (35)
npm run test:mantenimiento           # conteo de visitas (robots, refrescos) y purga del historial      (19)
npm run test:js-formulario           # navegador: JS del formulario dinámico (contador, mapas); crea un formulario temporal (7)
npm run test:sin-cdn                 # navegador con Internet bloqueado; usa las cuentas demo            (13)
npm run test:js-externo              # navegador: JS de public/js/ (perfil, lotes, sitio, comunicaciones...); usa las cuentas demo (20)
npm test                             # suite completa de navegador (npx playwright install chromium la primera vez) (91)
```

Todas necesitan la base de datos local. Salvo `test:seguridad` (no usa servidor) y `test:cloudinary` (levanta sus propios servidores), todas requieren además `php -S localhost:8080 -t public` en marcha. Las pruebas que crean datos los borran al terminar (prefijo `zz_test_`); si alguna quedara a medias, buscar filas con ese prefijo en `usuarios`, `empresas` y `formularios_dinamicos`.

## Hecho (resumen por tema)

**Seguridad**
- **Crítico corregido:** ejecución remota de código por subida de archivos (extensión tomada del nombre del cliente) en `presentar-proyecto.php` y en los formularios del Ministerio; ahora la extensión sale del MIME verificado.
- Una empresa podía publicar mensajes dentro de un comunicado global (visible para todas); ahora lectura y escritura están separadas (`coms_puede_escribir`).
- Cookie de sesión `SameSite=Lax` + modo estricto, cabeceras de seguridad, `APP_ENV` por defecto `production`, `.htaccess` (raíz y `public/`), `dockerfile` con `AllowOverride All`.
- **Crítico corregido:** `public/uploads/.htaccess` no existía en el repo (aunque `tests/seguridad.php` e `INSTALACION.md` ya lo daban por hecho); en un Apache real, cualquier archivo subido que lograra colarse con extensión `.php` se habría ejecutado como script. Creado: desactiva el motor PHP y niega el acceso a extensiones de script dentro de `uploads/`.
- Auditoría sin hallazgos en: SQL (parametrizado), CSRF, XSS reflejado y almacenado, permisos entre empresas y roles, cron, recuperación de contraseña, bloqueo de login.
- Pruebas: `seguridad`, `aislamiento`, `ministerio` y `ministerio-gestion` (arriba).

**Mensajería**
- Unificada en el Centro de Comunicaciones (`mensajes_v2`): `FEATURE_CENTRO_COMS` es siempre verdadero, se eliminaron `comunicados.php` y `mensajes-entrada.php` y las ramas viejas; tabla `mensajes` fuera del SQL oficial.
- Corregidos: la respuesta a una solicitud de proyecto iba a la tabla vieja y la empresa no la veía; los adjuntos locales del Centro se guardaban como nombre suelto y el enlace no abría.

**Archivos y almacenamiento**
- `store_upload()`: con Cloudinary configurado, imágenes (`image`) y documentos (`raw`) van a Cloudinary en Comunicaciones, formularios y "Presentar proyecto"; respaldo a disco local si falla. En la base se guarda la URL o el nombre local y las pantallas usan `uploads_resolve_url()`.

**Panel del Ministerio: alta de empresas, formularios dinámicos y banners**
- Nuevas pruebas (`tests/ministerio_gestion.php`, `npm run test:ministerio-gestion`): permisos de `nueva-empresa.php`, `empresa-detalle.php` (reset de contraseña), `formulario-nuevo.php`/`formulario-editar.php` y `banners.php` (una empresa o un anónimo no pueden ejecutar estas acciones, el Ministerio sí, y sus validaciones).
- Corregido: `formulario-nuevo.php` insertaba el formulario antes de validar sus preguntas; si una pregunta de tipo lista se enviaba sin opciones, quedaba un formulario "huérfano" sin preguntas en vez de rechazar todo el alta. Ahora está en una transacción, igual que `formulario-editar.php`.

**Sitio sin dependencias externas**
- Librerías en `public/vendor/` (Bootstrap, Bootstrap Icons, Font Awesome, Leaflet + Draw, Chart.js, SweetAlert2, SortableJS, Quill y tipografías); versiones y licencias en `public/vendor/README.md`. Solo siguen siendo externos los mapas (teselas OpenStreetMap/ArcGIS) y reCAPTCHA.
- `tests/assets.php` revisa con el tokenizador de PHP que ninguna etiqueta `<?= PUBLIC_URL ?>` quede dentro de código PHP (donde saldría literal).

**Rendimiento y crecimiento de tablas**
- Las visitas de robots y los refrescos (misma IP en 30 min) ya no cuentan ni escriben en `visitas_empresa` (antes inflaban el contador de las "empresas destacadas").
- Cron `limpiar-historial.php`: purga `log_actividad` (365 d) y `visitas_empresa` (180 d) por lotes, con piso de 30 días y `--dry-run`. Índice `idx_fecha` en `visitas_empresa`.

**Organización del código** (dos rondas; el comportamiento no cambió)
- JavaScript y CSS en línea movidos a `public/js/` y `public/css/`; los valores de PHP llegan por objetos globales (`__CFG`, `LOTES_CFG`, `SITIO_CFG`, `FD_CFG`). Helper `asset_url()` con versión por fecha de modificación.
- `includes/funciones.php` (1157 líneas) es ahora un cargador de nueve módulos temáticos en `includes/funciones/` (63 funciones verificadas por reflexión).
- Líneas: `comunicaciones_panel.php` 907→213, `perfil.php` 847→573, `lotes.php` 819→373, `formularios.php` 809→684, `sitio-publico.php` 736→596, `formulario-gestion.php` 715→414 (pestañas a `includes/partials/`), `formulario_dinamico.php` 711→574.
- Verificación: `php tests/capturar_js.php <carpeta>` guarda el JS/CSS/HTML efectivo de las páginas para compararlo antes y después de un refactor.
