<?php
/* ============================================================
   Ruta: modul/categ/venta/opera/sse.php
   Módulo: Ventas / Operaciones / SSE

   RESPONSABILIDAD:
   Publicar en tiempo real el estado de stock y reservas
   de los productos consultados por Operaciones.

============================================================ */

declare(strict_types=1);

require_once dirname(__DIR__,4).'/contr/error.php';
require_once dirname(__DIR__,4).'/contr/login/contr-auth.php';
require_once dirname(__DIR__,4).'/contr/login/login-db.php';
require_once dirname(__DIR__,4).'/contr/login/contr-perm.php';

if (!CAuth::autenticado()) {
    http_response_code(401);
    exit('Sesión no válida.');
}

$contPerm = new ContPerm(LOGINDB::abrir());

if (!$contPerm->autorizar('VENTG')) {
    http_response_code(403);
    exit('Operación no autorizada.');
}

/*
   SSE mantiene esta solicitud abierta. Liberar el bloqueo de sesión
   después de autenticar y autorizar evita serializar las consultas
   funcionales que usan la misma sesión mientras el flujo está activo.
*/
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once dirname(__DIR__,4).'/contr/categ/inven-db.php';
require_once dirname(__DIR__,4).'/modul/categ/inven/reser/reser-repos.php';

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Accel-Buffering: no');

ignore_user_abort(false);
set_time_limit(0);

$alma = (int)($_GET['almac'] ?? 0);
$idsr = trim((string)($_GET['ids'] ?? ''));
$ids = [];

foreach (preg_split('/\s*,\s*/', $idsr, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $id) {
    $id = (int)$id;
    if ($id > 0) {
        $ids[$id] = $id;
    }
}

$ids = array_slice(array_values($ids), 0, 50);

if ($alma <= 0 || $ids === []) {
    http_response_code(400);
    echo ": parámetros no válidos\n\n";
    exit;
}

while (ob_get_level() > 0) {
    @ob_end_flush();
}

ob_implicit_flush(true);

try {
    $db = INVENDB::abrir();
    $rese = new ReseR($db);

    echo "retry: 3000\n\n";
    flush();

    $hash = '';
    $cicl = 0;

    while (!connection_aborted()) {
        $cicl++;

        $dato = json_encode(
            ['productos' => $rese->estado($alma, $ids)],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($dato === false) {
            throw new RuntimeException('No se pudo generar el estado SSE.');
        }

        $nuev = hash('sha256', $dato);

        if ($nuev !== $hash) {
            echo "event: stock\n";
            echo "data: {$dato}\n\n";
            $hash = $nuev;
        } elseif ($cicl % 5 === 0) {
            echo ": ping\n\n";
        }

        flush();
        sleep(2);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo ": error\n\n";
    flush();
}
