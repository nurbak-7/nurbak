<?php
/* ============================================================
   Ruta: debug/php/categ/venta/opera/debug-produ.php
   Función: Receptor exclusivo del DEBUG PRODUCTOS de OPERACIONES.

   JERARQUÍA:
   VENTAS / OPERACIONES / PRODUCTOS / MINI

   RESPONSABILIDAD:
   - Recibir únicamente evidencias de PRODUCTOS/MINI.
   - Normalizar los datos.
   - Registrar el proceso en debug-produ.log.

   INDEPENDENCIA:
   - No incluye archivos de modul/.
   - No ejecuta lógica funcional.
   - No consulta la base funcional.
   - No modifica procesos funcionales.
============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit;
}

$paso = trim((string)($_POST['paso'] ?? ''));

if (
    $paso === '' ||
    strlen($paso) > 100 ||
    !str_starts_with($paso, 'PRODU_')
) {
    http_response_code(400);
    exit;
}

$ctx = $_POST['contexto'] ?? [];

if (!is_array($ctx)) {
    $ctx = [];
}

unset(
    $ctx['clave'],
    $ctx['password'],
    $ctx['token'],
    $ctx['secret'],
    $ctx['authorization'],
    $ctx['cookie']
);

$limpiar = static function ($valor): string {
    if (is_array($valor) || is_object($valor)) {
        $valor = json_encode(
            $valor,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }

    $valor = preg_replace(
        '/\s+/u',
        ' ',
        (string)($valor ?? '')
    );

    return trim((string)$valor);
};

foreach ($ctx as $clave => $valor) {
    $valor = $limpiar($valor);

    if (strlen($valor) > 1200) {
        $valor = substr($valor, 0, 1200) . '…';
    }

    $ctx[$clave] = $valor;
}

$dat = $ctx === []
    ? ''
    : (string)json_encode(
        $ctx,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PARTIAL_OUTPUT_ON_ERROR
    );

if (strlen($dat) > 8000) {
    $dat = substr($dat, 0, 8000) . '…';
}

$log = __DIR__ . '/debug-produ.log';

clearstatcache(true, $log);

if (is_file($log)) {
    $ult = filemtime($log);

    if (
        $ult !== false &&
        (time() - $ult) >= 180
    ) {
        @file_put_contents(
            $log,
            '',
            LOCK_EX
        );
    }
}

$linea = sprintf(
    '[%s] %s%s%s',
    date('Y-m-d H:i:s'),
    $paso,
    $dat !== '' ? ' ' : '',
    $dat
) . PHP_EOL;

if (@file_put_contents(
    $log,
    $linea,
    FILE_APPEND | LOCK_EX
) === false) {
    http_response_code(500);
    exit;
}

http_response_code(204);
exit;
