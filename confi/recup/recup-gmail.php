<?php
/* ============================================================
   Ruta: confi/recup/recup-gmail.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   confi/recup/recup-mail.php — Correo de recuperación
   ------------------------------------------------------------
   🔹 Envía el correo mediante Gmail API
   🔹 Usa OAuth 2.0 para autorizar la cuenta remitente
   🔹 No consulta la base de datos
============================================================ */

declare(strict_types=1);

require_once __DIR__ . '/recup-oauth.php';

final class RecuMail
{

    private static function url(): string
    {
        $val = getenv('RECU_URL');

        if ($val !== false && trim($val) !== '') {
            return trim($val);
        }

        $sch = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $sch . '://' . $host . '/publi/publi-recup.php';
    }

    public function enviar(
        string $destinatario,
        string $usuario,
        string $token
    ): bool {
        $destinatario = trim($destinatario);
        $usuario = trim($usuario);

        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $accessToken = RecuOAuth::accessToken();

        if ($accessToken === null) {
            return false;
        }

        $enlace = self::url() . '?token=' . rawurlencode($token);
        $asunto = 'Recuperación de acceso - Sistema de Facturación';

        $cuerpo = "Hola {$usuario},\r\n\r\n"
            . "Hemos recibido una solicitud para cambiar la contraseña de tu cuenta.\r\n\r\n"
            . "Para establecer una nueva contraseña, utiliza el siguiente enlace:\r\n\r\n"
            . $enlace . "\r\n\r\n"
            . "Este enlace es válido durante 5 minutos y solo puede utilizarse una vez.\r\n\r\n"
            . "Si no solicitaste este cambio, puedes ignorar este mensaje.\r\n\r\n"
            . "Sistema de Facturación";

        $mensaje = ''
            . 'From: Sistema de Facturación <' . RecuOAuth::remitente() . ">\r\n"
            . 'To: <' . $destinatario . ">\r\n"
            . 'Subject: ' . $asunto . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . "\r\n"
            . $cuerpo;

        $raw = rtrim(strtr(base64_encode($mensaje), '+/', '-_'), '=');

        if (!function_exists('curl_init')) {
            return false;
        }

        $curl = curl_init(
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'
        );

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['raw' => $raw], JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $respuesta = curl_exec($curl);
        $codig = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $respuesta !== false && $codig >= 200 && $codig < 300;
    }
}
