<?php
/* ============================================================
   Ruta: modul/categ/catal/artic/artic-contr.php
   Módulo: Catálogo / Artículos
   Función: Entrada HTTP de la consulta consolidada.

   CONEXIONES:
   - CATAL → productos, vehículos y equivalencias.
   - INVEN → stock físico y almacenes activos.
============================================================ */

declare(strict_types=1);


/* ============================================================
   NIVEL 1
   MANEJO NORMAL DE ERRORES
============================================================ */

require_once __DIR__ . '/../../../../contr/error.php';

header('Content-Type: application/json; charset=utf-8');
/* SESIÓN — autoridad de acceso antes de ejecutar el módulo. */
require_once dirname(__DIR__,4) . '/contr/login/login-db.php';
require_once dirname(__DIR__,4) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__,4) . '/contr/login/contr-perm.php';
if (!CAuth::autenticado()) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
    /* AUTORIZACIÓN — Paso 4 | Permiso PRODV requerido en el servidor. */
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('PRODV')) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }


/* ============================================================
   NIVEL 1
   CONEXIONES
============================================================ */

require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/inven-db.php';
require_once __DIR__ . '/artic-repos.php';


/* ============================================================
   NIVEL 2
   ENTRADA
============================================================ */

$accio = trim(
    (string) ($_REQUEST['accio'] ?? 'lista')
);

$busc = trim(
    (string) ($_REQUEST['busc'] ?? '')
);

$limi = (int) (
    $_REQUEST['limi'] ?? 50
);


/* ============================================================
   NIVEL 2
   PROCESAR
============================================================ */

try {

    if ($accio !== 'lista') {
        throw new InvalidArgumentException(
            'Operación no válida.'
        );
    }

    $repo = new ArtiR(
        CATALDB::abrir(),
        INVENDB::abrir()
    );

    $rows = $repo->lista(
        $busc,
        $limi
    );


    /* ========================================================
       NIVEL 3
       RESPUESTA
    ======================================================== */

    echo json_encode(
        [
            'ok' => true,
            'artic' => $rows
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {

    http_response_code(
        $e instanceof InvalidArgumentException
            ? 400
            : 500
    );

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => errMsg($e)
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}
