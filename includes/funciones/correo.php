<?php
/**
 * Envío de correos (Resend, Gmail/SMTP o mail()) y plantillas de mensajes.
 * Parte de las funciones helper: se carga desde includes/funciones.php.
 */

if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

/**
 * Indica si hay soporte para enviar correos (Resend o Gmail configurados).
 */
function can_send_mail(): bool {
    if (!empty(getenv('RESEND_API_KEY'))) return true;
    if (!empty(getenv('GMAIL_USER')) && !empty(getenv('GMAIL_APP_PASSWORD'))) return true;
    return false;
}

/**
 * Envía un email vía Resend API.
 * Requiere RESEND_API_KEY en variables de entorno.
 * Retorna true si Resend aceptó el mensaje (HTTP 200).
 */
function resend_send_email(string $to, string $subject, string $body, ?string $html_body = null): bool {
    $api_key = getenv('RESEND_API_KEY');
    if (empty($api_key)) {
        error_log("resend_send_email: RESEND_API_KEY no configurada");
        return false;
    }

    $from = getenv('MAIL_FROM') ?: 'no-reply@parqueindustrial.gob.ar';

    $payload_data = [
        'from'    => 'Parque Industrial <' . $from . '>',
        'to'      => [$to],
        'subject' => $subject,
        'text'    => $body,
    ];
    if ($html_body !== null && $html_body !== '') {
        $payload_data['html'] = $html_body;
    }
    $payload = json_encode($payload_data);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        error_log("resend_send_email: curl error — $curl_err");
        return false;
    }
    if ($http_code !== 200 && $http_code !== 201) {
        error_log("resend_send_email: HTTP $http_code a $to — $response");
        return false;
    }
    return true;
}

/**
 * Envía email vía Gmail SMTP usando cURL + SMTPS (puerto 465).
 * Requiere GMAIL_USER y GMAIL_APP_PASSWORD en el entorno.
 */
function gmail_send_email(string $to, string $subject, string $body, ?string $html_body = null): bool {
    $user = getenv('GMAIL_USER');
    $pass = getenv('GMAIL_APP_PASSWORD');
    if (!$user || !$pass) {
        error_log('gmail_send_email: GMAIL_USER o GMAIL_APP_PASSWORD no configurados');
        return false;
    }

    $date = date('r');
    $name = 'Parque Industrial';

    if ($html_body !== null && $html_body !== '') {
        $boundary = 'pi_' . bin2hex(random_bytes(8));
        $headers  = "Date: $date\r\nTo: <$to>\r\nFrom: $name <$user>\r\nSubject: $subject\r\n"
                  . "MIME-Version: 1.0\r\n"
                  . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
        $raw  = $headers;
        $raw .= "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n$body\r\n";
        $raw .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n$html_body\r\n";
        $raw .= "--$boundary--\r\n";
    } else {
        $raw = "Date: $date\r\nTo: <$to>\r\nFrom: $name <$user>\r\n"
             . "Subject: $subject\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n$body";
    }

    $stream = fopen('php://temp', 'rw');
    fwrite($stream, $raw);
    rewind($stream);

    $ch = curl_init('smtps://smtp.gmail.com:465');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_UPLOAD         => true,
        CURLOPT_MAIL_FROM      => "<$user>",
        CURLOPT_MAIL_RCPT      => ["<$to>"],
        CURLOPT_READDATA       => $stream,
        CURLOPT_INFILESIZE     => strlen($raw),
        CURLOPT_USERNAME       => $user,
        CURLOPT_PASSWORD       => $pass,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    $curl_err = curl_error($ch);
    $curl_no  = curl_errno($ch);
    curl_close($ch);
    fclose($stream);

    if ($curl_err) {
        error_log("gmail_send_email: curl error $curl_no — $curl_err");
        return false;
    }
    return true;
}

/**
 * Envía email usando Resend si está configurado, si no intenta Gmail SMTP.
 */
function send_email(string $to, string $subject, string $body, ?string $html_body = null): bool {
    if (!empty(getenv('RESEND_API_KEY'))) {
        $ok = resend_send_email($to, $subject, $body, $html_body);
        if ($ok) return true;
        error_log("send_email: Resend falló para $to, intentando Gmail");
    }
    if (!empty(getenv('GMAIL_USER')) && !empty(getenv('GMAIL_APP_PASSWORD'))) {
        return gmail_send_email($to, $subject, $body, $html_body);
    }
    error_log("send_email: ningún proveedor de email configurado");
    return false;
}

/**
 * Email de activación de cuenta empresa (enlace con token).
 */
function enviar_email_activacion_empresa(string $destino_email, string $nombre_empresa, string $url_activacion): bool {
    $asunto = 'Active su cuenta - Parque Industrial';
    $cuerpo  = "Estimado/a,\n\n";
    $cuerpo .= "Se ha registrado la empresa \"$nombre_empresa\" en el sistema del Parque Industrial de Catamarca.\n\n";
    $cuerpo .= "Para crear su contraseña y activar el acceso, abra el siguiente enlace (válido por 48 horas):\n\n";
    $cuerpo .= "$url_activacion\n\n";
    $cuerpo .= "Si usted no solicitó este registro, ignore este mensaje.\n\n";
    $cuerpo .= "Saludos cordiales,\nMinisterio de Producción — Parque Industrial de Catamarca";
    $ok = send_email($destino_email, $asunto, $cuerpo);
    if (!$ok) error_log("enviar_email_activacion: fallo al enviar a $destino_email");
    return $ok;
}

/**
 * Email con enlace para restablecer contraseña.
 */
function enviar_email_recuperacion_password(string $destino_email, string $url_reset): bool {
    $asunto  = 'Restablecer contraseña - Parque Industrial';

    // Texto plano: el enlace va envuelto entre < > para que clientes que auto-linkifican
    // (Outlook, Gmail) no incluyan el salto de línea ni el punto final dentro del href.
    $cuerpo  = "Recibimos una solicitud para restablecer la contraseña de su cuenta.\n\n";
    $cuerpo .= "Si fue usted, abra este enlace (válido por 1 hora):\n\n";
    $cuerpo .= "<$url_reset>\n\n";
    $cuerpo .= "Si no solicitó el cambio, ignore este correo.\n\n";
    $cuerpo .= "Parque Industrial de Catamarca";

    // HTML: enlace explícito en <a href>. No depende de auto-linkificación del cliente.
    $url_html = htmlspecialchars($url_reset, ENT_QUOTES, 'UTF-8');
    $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>';
    $html .= '<body style="font-family:Arial,sans-serif;color:#333;background:#f5f5f5;margin:0;padding:24px;">';
    $html .= '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:8px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">';
    $html .= '<h2 style="color:#1a5276;margin-top:0;">Restablecer contraseña</h2>';
    $html .= '<p>Recibimos una solicitud para restablecer la contraseña de su cuenta.</p>';
    $html .= '<p>Si fue usted, haga clic en el siguiente botón <strong>(válido por 1 hora)</strong>:</p>';
    $html .= '<p style="text-align:center;margin:32px 0;">';
    $html .= '<a href="' . $url_html . '" style="background:#1a5276;color:#fff;padding:14px 28px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:600;">Restablecer contraseña</a>';
    $html .= '</p>';
    $html .= '<p style="font-size:13px;color:#666;">Si el botón no funciona, copie y pegue este enlace en su navegador:</p>';
    $html .= '<p style="font-size:13px;word-break:break-all;background:#f9f9f9;padding:12px;border-radius:4px;border:1px solid #eee;">';
    $html .= '<a href="' . $url_html . '" style="color:#1a5276;">' . $url_html . '</a>';
    $html .= '</p>';
    $html .= '<hr style="border:none;border-top:1px solid #eee;margin:24px 0;">';
    $html .= '<p style="font-size:12px;color:#999;">Si no solicitó el cambio, puede ignorar este correo. Su contraseña no se modificará hasta que abra el enlace y la cambie usted mismo.</p>';
    $html .= '<p style="font-size:12px;color:#999;margin-bottom:0;">Parque Industrial de Catamarca</p>';
    $html .= '</div></body></html>';

    $ok = send_email($destino_email, $asunto, $cuerpo, $html);
    if (!$ok) error_log("enviar_email_recuperacion: fallo al enviar a $destino_email");
    return $ok;
}

/**
 * Aviso de contraseña cambiada.
 */
function enviar_email_password_cambiada(string $destino_email): bool {
    $asunto  = 'Su contraseña fue actualizada - Parque Industrial';
    $cuerpo  = "Le informamos que la contraseña de su cuenta se modificó correctamente.\n\n";
    $cuerpo .= "Si no fue usted, contacte de inmediato al administrador.\n\n";
    $cuerpo .= "Parque Industrial de Catamarca";
    return send_email($destino_email, $asunto, $cuerpo);
}

/**
 * Notificación por correo: nuevo formulario dinámico asignado.
 */
function enviar_email_formulario_nuevo(string $destino_email, string $titulo_formulario, string $url_formulario): bool {
    $asunto  = 'Nuevo formulario disponible - Parque Industrial';
    $cuerpo  = "Tiene un formulario pendiente de completar:\n\n";
    $cuerpo .= "$titulo_formulario\n\n";
    $cuerpo .= "Acceda desde:\n$url_formulario\n\n";
    $cuerpo .= "Parque Industrial de Catamarca";
    return send_email($destino_email, $asunto, $cuerpo);
}

/**
 * Enviar email con credenciales de acceso a una empresa recién registrada.
 * Usa mail() de PHP. Requiere servidor de correo configurado.
 * @return bool true si se envió, false si falló
 */
function enviar_email_credenciales_empresa($destino_email, $nombre_empresa, $password_temporal, $url_login = '') {
    $url_login = $url_login ?: (defined('PUBLIC_URL') ? PUBLIC_URL . '/login.php' : '');
    $asunto  = 'Credenciales de acceso - Parque Industrial';
    $cuerpo  = "Estimado/a,\n\n";
    $cuerpo .= "Se ha registrado su empresa \"$nombre_empresa\" en el sistema del Parque Industrial de Catamarca.\n\n";
    $cuerpo .= "Sus credenciales de acceso son:\n";
    $cuerpo .= "  Email: $destino_email\n";
    $cuerpo .= "  Contraseña temporal: $password_temporal\n\n";
    $cuerpo .= "Le recomendamos cambiar la contraseña al primer ingreso.\n";
    if ($url_login) $cuerpo .= "\nAcceso al panel: $url_login\n";
    $cuerpo .= "\nSaludos cordiales,\nMinisterio de Producción — Parque Industrial de Catamarca";
    $ok = send_email($destino_email, $asunto, $cuerpo);
    if (!$ok) error_log("enviar_credenciales: fallo al enviar email a $destino_email");
    return $ok;
}
