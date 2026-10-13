<?php
/* ============================================================
   Ruta: debug/php/vista/debug-vista.php
   Función: Receptor independiente del DEBUG de vista.

   RESPONSABILIDAD:
   - Recibir eventos del coordinador DEBUG.
   - Registrar cada evento en su propio LOG.
   - No ejecutar lógica funcional.
============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit;
}

$paso = trim((string) ($_POST['paso'] ?? ''));
if ($paso === '') {
    http_response_code(400);
    exit;
}

$ctx = $_POST['contexto'] ?? [];
if (!is_array($ctx)) {
    $ctx = [];
}

unset($ctx['clave'], $ctx['password'], $ctx['token'], $ctx['secret']);

$dat = $ctx === []
    ? ''
    : (string) json_encode(
        $ctx,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PARTIAL_OUTPUT_ON_ERROR
    );

$log = __DIR__ . '/debug-vista.log';
$line = sprintf(
    "[%s] %s%s%s",
    date('Y-m-d H:i:s'),
    $paso,
    $dat !== '' ? ' ' : '',
    $dat
) . PHP_EOL;

clearstatcache(true, $log);
if (is_file($log)) {
    $ult = filemtime($log);
    if ($ult !== false && (time() - $ult) >= 180) {
        file_put_contents($log, '', LOCK_EX);
    }
}

file_put_contents($log, $line, FILE_APPEND | LOCK_EX);

http_response_code(204);
exit;
