<?php
/**
 * Funciones Helper
 * Parque Industrial de Catamarca
 *
 * Cargador: las funciones están repartidas por tema en includes/funciones/:
 *   web.php        Base web: escape de HTML, CSRF, redirecciones, mensajes flash, URLs de estáticos, paginación.
 *   formato.php    Formato de fechas, números, monedas, textos, slugs y CUIT.
 *   validacion.php Validación de correos y CUIT.
 *   archivos.php   Archivos subidos: validación por MIME real, almacenamiento local o en Cloudinary y URLs de los archivos guardados.
 *   base_datos.php Ayudas de base de datos y configuración del sitio.
 *   actividad.php  Registro de actividad, notificaciones y estadísticas del sitio.
 *   correo.php     Envío de correos (Resend, Gmail/SMTP o mail()) y plantillas de mensajes.
 *   recaptcha.php  Google reCAPTCHA v2.
 *   visitas.php    Conteo de visitas a los perfiles públicos de empresas.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

foreach (['web', 'formato', 'validacion', 'archivos', 'base_datos', 'actividad', 'correo', 'recaptcha', 'visitas'] as $__modulo) {
    require_once __DIR__ . '/funciones/' . $__modulo . '.php';
}
unset($__modulo);
