<?php
/**
 * Registro de actividad, notificaciones y estadísticas del sitio.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Texto amigable para la línea de tiempo del panel empresa (log_actividad.accion).
 */
function empresa_traducir_accion_log(string $accion): string {
    static $map = [
        'perfil_actualizado' => 'Perfil de empresa actualizado',
        'publicacion_enviada' => 'Publicación enviada a revisión',
        'publicacion_guardada' => 'Borrador de publicación guardado',
        'formulario_enviado' => 'Declaración jurada enviada',
        'formulario_guardado' => 'Borrador de formulario guardado',
        'mensaje_enviado_ministerio' => 'Mensaje enviado al Ministerio',
        'logout' => 'Cierre de sesión',
        'login' => 'Inicio de sesión',
    ];

    return $map[$accion] ?? ucfirst(str_replace('_', ' ', $accion));
}

/**
 * Registrar actividad
 */
function log_activity($accion, $tabla = null, $registro_id = null, $datos_anteriores = null, $datos_nuevos = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO log_actividad 
            (usuario_id, empresa_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SESSION['empresa_id'] ?? null,
            $accion,
            $tabla,
            $registro_id,
            $datos_anteriores ? json_encode($datos_anteriores) : null,
            $datos_nuevos ? json_encode($datos_nuevos) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

/**
 * Crear notificación
 */
function crear_notificacion($usuario_id, $tipo, $titulo, $mensaje = null, $url = null, $datos = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, url, datos)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuario_id, $tipo, $titulo, $mensaje, $url, $datos ? json_encode($datos) : null]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtener estadísticas generales
 */
function get_estadisticas_generales() {
    try {
        $db = getDB();
        
        // Total empresas
        $stmt = $db->query("SELECT COUNT(*) as total FROM empresas");
        $total_empresas = $stmt->fetch()['total'];
        
        // Empresas activas (consideramos todas como activas si no tienen estado)
        $stmt = $db->query("SELECT COUNT(*) as total FROM empresas WHERE estado = 'activa' OR estado IS NULL");
        $total_activas = $stmt->fetch()['total'];
        
        // Total rubros únicos
        $stmt = $db->query("SELECT COUNT(DISTINCT rubro) as total FROM empresas WHERE rubro IS NOT NULL");
        $total_rubros = $stmt->fetch()['total'];
        
        // Total empleados (de datos_empresa o campo dotacion si existe)
        $stmt = $db->query("SELECT COALESCE(SUM(dotacion_total), 0) as total FROM datos_empresa");
        $total_empleados = $stmt->fetch()['total'];
        
        // Si no hay datos en datos_empresa, estimar
        if ($total_empleados == 0) {
            $total_empleados = $total_activas * 15; // Estimado promedio
        }
        
        return [
            'total_empresas' => $total_empresas,
            'total_empresas_activas' => $total_activas ?: $total_empresas,
            'total_rubros' => $total_rubros,
            'total_empleados' => $total_empleados
        ];
    } catch (Exception $e) {
        return ['total_empresas_activas' => 0, 'total_empresas' => 0, 'total_empleados' => 0, 'total_rubros' => 0];
    }
}

/**
 * Obtener rubros con conteo (directo de empresas)
 */
function get_rubros_con_conteo() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT
                e.rubro AS nombre,
                COUNT(*) AS total_empresas,
                COALESCE(r.color, '#3498db') AS color
            FROM empresas e
            LEFT JOIN rubros r ON r.nombre = e.rubro AND r.activo = 1
            WHERE e.rubro IS NOT NULL AND e.rubro != '' AND e.estado = 'activa'
            GROUP BY e.rubro, r.color
            ORDER BY total_empresas DESC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}
