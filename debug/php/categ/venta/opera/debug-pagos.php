<?php
/* ============================================================
   Ruta: debug/php/categ/venta/opera/debug-pagos.php
   Función: Recurso del sistema.

   NIVELES:
   1. Inicio DEBUG
   2. Validar método
   3. Recibir evento
   4. Recibir contexto
   5. Preparar datos
   6. Registrar LOG
   7. Responder

   Módulo: DEBUG / Ventas / Operaciones / Pagos

   RESPONSABILIDAD ÚNICA:
   Recibir evidencia enviada por debug-pagos.js y registrarla.

   INDEPENDENCIA:
   - No incluye archivos de modul/.
   - No ejecuta lógica funcional.
   - No modifica procesos funcionales.
   - No consulta la base funcional.
============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');


/* ============================================================
   NIVEL 1 — INICIO DEBUG
============================================================ */


/* ============================================================
   NIVEL 2 — VALIDAR MÉTODO
============================================================ */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {

    http_response_code(405);
    exit;

}


/* ============================================================
   NIVEL 3 — RECIBIR EVENTO
============================================================ */

$paso = trim(
    (string) ($_POST['paso'] ?? '')
);

if ($paso === '') {

    http_response_code(400);
    exit;

}


/* ============================================================
   NIVEL 4 — RECIBIR CONTEXTO
============================================================ */

$ctx = $_POST['contexto'] ?? [];

if (!is_array($ctx)) {
    $ctx = [];
}

unset(
    $ctx['clave'],
    $ctx['password'],
    $ctx['token'],
    $ctx['secret']
);


/* ============================================================
   NIVEL 5 — PREPARAR DATOS
============================================================ */

$dat = $ctx === []
    ? ''
    : (string) json_encode(
        $ctx,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PARTIAL_OUTPUT_ON_ERROR
    );


/* ============================================================
   NIVEL 6 — REGISTRAR LOG
============================================================ */

$log =
    __DIR__ . '/debug-pagos.log';

$line = sprintf(
    "[%s] %s%s%s",
    date('Y-m-d H:i:s'),
    $paso,
    $dat !== '' ? ' ' : '',
    $dat
) . PHP_EOL;

/* ============================================================
   NIVEL 6
   DESTROY AUTOMÁTICO DEL LOG

   Si este LOG lleva 180 segundos o más sin recibir eventos,
   se limpia antes de registrar el nuevo evento.
   Cada LOG controla su propia inactividad.
============================================================ */
clearstatcache(true, $log);
if (is_file($log)) {
    $ult = filemtime($log);
    if ($ult !== false && (time() - $ult) >= 180) {
        file_put_contents($log, '', LOCK_EX);
    }
}

file_put_contents(
    $log,
    $line,
    FILE_APPEND | LOCK_EX
);


/* ============================================================
   NIVEL 7 — RESPONDER
============================================================ */

http_response_code(204);
exit;