<?php
/* ============================================================
   Ruta: confi/recup/recup-oauth.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   confi/recup/recup-oauth.php — Configuración OAuth de Gmail
   ------------------------------------------------------------
   🔹 Configura el cliente OAuth de Google
   🔹 Genera la autorización inicial
   🔹 Intercambia el código por tokens
   🔹 Guarda el refresh token localmente
   🔹 Obtiene tokens de acceso para Gmail API
============================================================ */

declare(strict_types=1);

final class RecuOAuth
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    private const CLIENT_ID = '673715375803-at2pi90bfnfvm64d4atddugb1jftdq12.apps.googleusercontent.com';
    private const CLIENT_SECRET = '';
    private const REMITENTE = 'nurbakautomotive@gmail.com';
    private const REDIRECT_URI = '';

    private const TOKEN_FILE = __DIR__ . '/recu-oauth-token.php';

    public static function autorizacionUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => self::cfg('GMAIL_CLIENT_ID'),
            'redirect_uri' => self::redr(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    public static function intercambiarCodigo(string $code): bool
    {
        $respuesta = self::post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => self::cfg('GMAIL_CLIENT_ID'),
            'client_secret' => self::cfg('GMAIL_CLIENT_SECRET'),
            'redirect_uri' => self::redr(),
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($respuesta['access_token'])) {
            return false;
        }

        $actual = self::leerToken();

        $token = [
            'access_token' => (string) $respuesta['access_token'],
            'expires_at' => time() + (int) ($respuesta['expires_in'] ?? 3600),
            'refresh_token' => (string) ($respuesta['refresh_token'] ?? ($actual['refresh_token'] ?? '')),
        ];

        if ($token['refresh_token'] === '') {
            return false;
        }

        return self::guardarToken($token);
    }

    public static function accessToken(): ?string
    {
        $token = self::leerToken();

        if ($token === null || empty($token['refresh_token'])) {
            return null;
        }

        if (!empty($token['access_token']) && (int) ($token['expires_at'] ?? 0) > time() + 60) {
            return (string) $token['access_token'];
        }

        $respuesta = self::post(self::TOKEN_URL, [
            'client_id' => self::cfg('GMAIL_CLIENT_ID'),
            'client_secret' => self::cfg('GMAIL_CLIENT_SECRET'),
            'refresh_token' => (string) $token['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if (!isset($respuesta['access_token'])) {
            return null;
        }

        $token['access_token'] = (string) $respuesta['access_token'];
        $token['expires_at'] = time() + (int) ($respuesta['expires_in'] ?? 3600);

        self::guardarToken($token);

        return $token['access_token'];
    }

    public static function remitente(): string
    {
        return self::cfg('GMAIL_REMITENTE');
    }

    private static function leerToken(): ?array
    {
        if (!is_file(self::TOKEN_FILE)) {
            return null;
        }

        $token = require self::TOKEN_FILE;

        return is_array($token) ? $token : null;
    }

    private static function guardarToken(array $token): bool
    {
        $contenido = "<?php\nreturn " . var_export($token, true) . ";\n";

        return file_put_contents(
            self::TOKEN_FILE,
            $contenido,
            LOCK_EX
        ) !== false;
    }

    private static function cfg(string $key): string
    {
        $val = getenv($key);

        if ($val !== false && trim($val) !== '') {
            return trim($val);
        }

        return match ($key) {
            'GMAIL_CLIENT_ID' => self::CLIENT_ID,
            'GMAIL_REMITENTE' => self::REMITENTE,
            default => '',
        };
    }

    private static function base(): string
    {
        $sch = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $sch . '://' . $host . '/';
    }

    private static function redr(): string
    {
        $val = getenv('GMAIL_REDIRECT_URI');

        if ($val !== false && trim($val) !== '') {
            return trim($val);
        }

        return self::base() . 'publi/recup-oauth.php';
    }

    private static function post(string $url, array $datos): array
    {
        if (!function_exists('curl_init')) {
            return [];
        }

        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($datos),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $respuesta = curl_exec($curl);
        $codig = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($respuesta === false || $codig < 200 || $codig >= 300) {
            return [];
        }

        $json = json_decode($respuesta, true);

        return is_array($json) ? $json : [];
    }
}
