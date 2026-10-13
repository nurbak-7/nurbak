<?php
/* ============================================================
   Ruta: debug/php/categ/catal/asinc/imext/expor/debug-expor.php
   Módulo: DEBUG / CATAL / ASINC / IMEXT / EXPOR
   Función: Escuchar exclusivamente los eventos de diagnóstico
   del subproceso de exportación.

   RESPONSABILIDAD:
   - Recibir evidencia enviada por su JS DEBUG.
   - Registrar únicamente en su propio LOG.

   NO HACE:
   - No ejecuta la exportación.
   - No modifica ASINC.
   - No modifica lotes.
   - No modifica errores.
============================================================ */
declare(strict_types=1);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit; }
$paso = trim((string) ($_POST['paso'] ?? ''));
if ($paso === '') { http_response_code(400); exit; }
$ctx = $_POST['contexto'] ?? [];
if (!is_array($ctx)) { $ctx = []; }
$linea = sprintf('[%s] %s %s%s', date('Y-m-d H:i:s'), $paso, json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL);
$log = __DIR__ . '/debug-expor.log';

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

file_put_contents(__DIR__ . '/debug-expor.log', $linea, FILE_APPEND | LOCK_EX);
http_response_code(204);
