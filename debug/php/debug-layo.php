<?php
/* ============================================================
   Ruta: debug/php/debug-layo.php
   Función: Receptor raíz de eventos del DEBUG.

   RESPONSABILIDAD:
   - Recibir eventos del DEBUG raíz.
   - Validar y normalizar los datos.
   - Registrar eventos resumidos en debug-layo.log.
   - Mantener el receptor completamente independiente.

   No ejecuta archivos funcionales.
   No consulta la base funcional.
   No modifica procesos del sistema.
============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit;
}

$paso = trim((string) ($_POST['paso'] ?? ''));

if ($paso === '' || strlen($paso) > 100) {
    http_response_code(400);
    exit;
}

$ctx = $_POST['contexto'] ?? [];

if (!is_array($ctx)) {
    $ctx = [];
}

/* ============================================================
   SEGURIDAD
   Nunca registrar credenciales ni secretos.
============================================================ */

unset(
    $ctx['clave'],
    $ctx['password'],
    $ctx['token'],
    $ctx['secret'],
    $ctx['authorization'],
    $ctx['cookie']
);

/* ============================================================
   NORMALIZACIÓN
   Mantener el LOG legible y resumido.
============================================================ */

$limpiar = static function ($valor): string {
    if (is_array($valor) || is_object($valor)) {
        $valor = json_encode(
            $valor,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }

    return trim(preg_replace(
        '/\s+/u',
        ' ',
        (string) ($valor ?? '')
    ));
};

foreach ($ctx as $clave => $valor) {
    $ctx[$clave] = $limpiar($valor);

    if (strlen($ctx[$clave]) > 1200) {
        $ctx[$clave] = substr($ctx[$clave], 0, 1200) . '…';
    }
}

$dat = $ctx === []
    ? ''
    : (string) json_encode(
        $ctx,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PARTIAL_OUTPUT_ON_ERROR
    );

if (strlen($dat) > 8000) {
    $dat = substr($dat, 0, 8000) . '…';
}

$log = __DIR__ . '/debug-layo.log';
$dir = dirname($log);

if (!is_dir($dir)) {
    http_response_code(500);
    exit;
}

/* ============================================================
   LIMPIEZA POR INACTIVIDAD
============================================================ */

clearstatcache(true, $log);

if (is_file($log)) {
    $ult = filemtime($log);

    if ($ult !== false && (time() - $ult) >= 180) {
        @file_put_contents($log, '', LOCK_EX);
    }
}

/* ============================================================
   REGISTRO
============================================================ */

$line = sprintf(
    '[%s] %s%s%s',
    date('Y-m-d H:i:s'),
    $paso,
    $dat !== '' ? ' ' : '',
    $dat
) . PHP_EOL;

if (@file_put_contents(
    $log,
    $line,
    FILE_APPEND | LOCK_EX
) === false) {
    http_response_code(500);
    exit;
}

http_response_code(204);
exit;
