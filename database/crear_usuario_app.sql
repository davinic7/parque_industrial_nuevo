-- =============================================================================
-- Usuario de base de datos de la aplicación con permisos mínimos
-- Portal Parque Industrial de Catamarca
--
-- QUÉ HACE: crea un usuario que solo puede leer y escribir DATOS en la base
-- `parque_industrial` (SELECT, INSERT, UPDATE, DELETE). No puede crear, alterar
-- ni borrar tablas, ni tocar otras bases. Si alguna vez se explotara una falla
-- de la aplicación, el daño queda limitado a los datos.
--
-- QUÉ NO HACE: no crea la base, no crea tablas y no borra ni modifica datos.
-- Es seguro de ejecutar sobre una base que ya tiene información.
--
-- CÓMO USARLO (con un usuario administrador de MySQL/MariaDB, no con la app):
--   1. Reemplazar CAMBIAR_ESTA_CONTRASENA por una contraseña larga y aleatoria
--      (p. ej. `openssl rand -base64 24`) y, si el servidor de base de datos es
--      otra máquina, '@localhost' por la IP o el host desde el que conecta la app.
--   2. mysql -u root -p < database/crear_usuario_app.sql
--   3. Poner ese usuario y contraseña en DB_USER / DB_PASS del `.env`.
--
-- POR QUÉ NO NECESITA MÁS PERMISOS: el código de la app no ejecuta CREATE, ALTER
-- ni DROP en funcionamiento normal. La única excepción es un ALTER TABLE
-- opcional en includes/mantenimiento.php que agrega el índice `idx_fecha` de
-- `visitas_empresa`, y ese índice ya viene en database/parque_industrial_oficial.sql.
--
-- La estructura (importar parque_industrial_oficial.sql, migraciones futuras)
-- se hace siempre con el usuario administrador, nunca con este.
-- =============================================================================

CREATE USER IF NOT EXISTS 'parque_app'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CONTRASENA';

-- Cubre también las dos vistas (v_empresas_completas, v_estadisticas_generales).
GRANT SELECT, INSERT, UPDATE, DELETE ON `parque_industrial`.* TO 'parque_app'@'localhost';

FLUSH PRIVILEGES;

-- Para comprobar los permisos concedidos:
--   SHOW GRANTS FOR 'parque_app'@'localhost';
