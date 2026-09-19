<?php
/**
 * Conteo de visitas a los perfiles públicos de empresas.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * ¿El User-Agent es de un robot/rastreador/herramienta? (sin User-Agent también se considera robot)
 * Los robots no cuentan como visitas: inflaban el contador con el que se ordenan las empresas destacadas.
 */
function es_bot_ua(?string $ua): bool {
    if ($ua === null || trim($ua) === '') {
        return true;
    }
    return (bool) preg_match('/bot\b|bot[\/;)_ -]|crawl|spider|slurp|scrape|facebookexternalhit|preview|monitor|uptime|curl\/|wget|python-requests|httpclient|go-http|libwww|java\//i', $ua);
}

/**
 * Registra una visita al perfil público de una empresa y suma al contador `empresas.visitas`.
 * No cuenta: robots, ni una segunda visita de la misma IP a la misma empresa dentro de $ventana_min
 * minutos (refrescos). Devuelve true si la visita se contó.
 *
 * Un solo INSERT ... WHERE NOT EXISTS (sin consulta previa) apoyado en el índice (empresa_id, created_at).
 */
function registrar_visita_empresa(PDO $db, int $empresa_id, ?string $ip, ?string $user_agent, int $ventana_min = 30): bool {
    if (es_bot_ua($user_agent)) {
        return false;
    }
    $ventana_min = max(1, $ventana_min);
    try {
        $st = $db->prepare("
            INSERT INTO visitas_empresa (empresa_id, ip, user_agent)
            SELECT ?, ?, ? FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM visitas_empresa
                WHERE empresa_id = ? AND ip <=> ? AND created_at > DATE_SUB(NOW(), INTERVAL $ventana_min MINUTE)
            )
        ");
        $st->execute([$empresa_id, $ip, mb_substr((string) $user_agent, 0, 255), $empresa_id, $ip]);
        $contada = $st->rowCount() === 1;
    } catch (Throwable $e) {
        $contada = true; // tabla aún no creada: se cuenta como antes
    }
    if ($contada) {
        $db->prepare("UPDATE empresas SET visitas = visitas + 1 WHERE id = ?")->execute([$empresa_id]);
    }
    return $contada;
}
