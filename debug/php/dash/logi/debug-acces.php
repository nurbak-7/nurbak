<?php
/* ============================================================
   Ruta: debug/php/dash/logi/debug-acces.php
   Función: Escuchar exclusivamente eventos DEBUG de debug-acces.

   RESPONSABILIDAD ÚNICA:
   - Recibir eventos enviados por el DEBUG correspondiente.
   - Registrar cada evento en su propio LOG.

   INDEPENDENCIA:
   - No incluye archivos de modul/.
   - No ejecuta lógica funcional.
   - No modifica procesos funcionales.
   - No consulta la base funcional.
============================================================ */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');


/* ============================================================
   VALIDAR MÉTODO
============================================================ */

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
) {
    http_response_code(405);
    exit;
}


/* ============================================================
   RECIBIR PASO
============================================================ */

$paso = trim(
    (string) ($_POST['paso'] ?? '')
);

if ($paso === '') {
    http_response_code(400);
    exit;
}


/* ============================================================
   RECIBIR CONTEXTO
============================================================ */

$ctx = $_POST['contexto'] ?? [];

if (!is_array($ctx)) {
    $ctx = [];
}


/* ============================================================
   ELIMINAR DATOS SENSIBLES
============================================================ */

unset(
    $ctx['clave'],
    $ctx['password'],
    $ctx['token'],
    $ctx['secret']
);


/* ============================================================
   PREPARAR CONTEXTO
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
   ARCHIVO LOG
============================================================ */

$log = __DIR__ . '/debug-acces.log';


/* ============================================================
   PREPARAR LÍNEA
============================================================ */

$line = sprintf(
    "[%s] %s%s%s",
    date('Y-m-d H:i:s'),
    $paso,
    $dat !== '' ? ' ' : '',
    $dat
) . PHP_EOL;


/* ============================================================
   LIMPIAR CACHÉ DEL ESTADO DEL ARCHIVO
============================================================ */

clearstatcache(true, $log);


/* ============================================================
   LIMPIAR LOG POR INACTIVIDAD
============================================================ */

if (is_file($log)) {
    $ult = filemtime($log);

    if (
        $ult !== false &&
        (time() - $ult) >= 180
    ) {
        file_put_contents(
            $log,
            '',
            LOCK_EX
        );
    }
}


/* ============================================================
   ESCRIBIR LOG
============================================================ */

$escrito = file_put_contents(
    $log,
    $line,
    FILE_APPEND | LOCK_EX
);


/* ============================================================
   VALIDAR ESCRITURA
============================================================ */

if ($escrito === false) {
    http_response_code(500);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'No se pudo escribir el LOG DEBUG.',
            'ruta' => $log,
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* ============================================================
   RESPUESTA CORRECTA
============================================================ */

http_response_code(204);
exit;