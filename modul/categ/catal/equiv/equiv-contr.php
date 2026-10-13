<?php
/* ============================================================
   Ruta: modul/categ/catal/equiv/equiv-contr.php
   Módulo: Catálogo / Equiv

   RESPONSABILIDAD:
   Recibir las solicitudes HTTP y dirigirlas al repositorio.

   OPERACIONES:
   - listar
   - opciones
   - agregar
   - eliminar
============================================================ */

declare(strict_types=1);


/* ============================================================
   NIVEL 1 — MANEJO NORMAL DE ERRORES
============================================================ */

require_once __DIR__ . '/../../../../contr/error.php';

header('Content-Type: application/json; charset=utf-8');


/* ============================================================
   SESIÓN — AUTORIDAD DE ACCESO
============================================================ */

require_once dirname(__DIR__, 4) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 4) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__, 4) . '/contr/login/contr-perm.php';


if (!CAuth::autenticado()) {
    http_response_code(401);

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => 'Sesión no válida.',
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* ============================================================
   AUTORIZACIÓN — PERMISO EQUIG
============================================================ */

$contPerm = new ContPerm(
    LOGINDB::abrir()
);


if (!$contPerm->autorizar('EQUIG')) {
    http_response_code(403);

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* ============================================================
   RECIBIR OPERACIÓN
============================================================ */

$accion = isset($_REQUEST['accion'])
    ? trim((string) $_REQUEST['accion'])
    : 'listar';


/* ============================================================
   MÉTODO HTTP SEGÚN OPERACIÓN
============================================================ */

CAuth::metod(
    $accion,
    [
        'agregar',
        'eliminar',
    ]
);


/* ============================================================
   CARGAR BASE DE DATOS Y REPOSITORIO
============================================================ */

require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';

require_once __DIR__ . '/equiv-repos.php';


/* ============================================================
   RECIBIR BÚSQUEDA
============================================================ */

$buscar = isset($_REQUEST['buscar'])
    ? trim((string) $_REQUEST['buscar'])
    : '';


/* ============================================================
   RECIBIR LÍMITE
============================================================ */

$limite = isset($_REQUEST['limite'])
    ? (int) $_REQUEST['limite']
    : 50;


/* ============================================================
   EJECUTAR OPERACIÓN
============================================================ */

try {

    $repo = new EquivRepo(
        CATALDB::abrir()
    );


    switch ($accion) {

        /* ====================================================
           LISTAR
        ==================================================== */

        case 'listar':

            $resultado = $repo->listar(
                $buscar,
                $limite
            );

            echo json_encode(
                [
                    'ok' => true,
                    'equiv' => $resultado,
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;


        /* ====================================================
           OPCIONES
        ==================================================== */

        case 'opciones':

            $productos = $repo->productos();

            $vehiculos = $repo->vehiculos();

            echo json_encode(
                [
                    'ok' => true,
                    'productos' => $productos,
                    'vehiculos' => $vehiculos,
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;


        /* ====================================================
           AGREGAR
        ==================================================== */

        case 'agregar':

            $prodId = isset($_REQUEST['produ'])
                ? (int) $_REQUEST['produ']
                : 0;

            $vehiId = isset($_REQUEST['vehic'])
                ? (int) $_REQUEST['vehic']
                : 0;


            if (
                $prodId <= 0 ||
                $vehiId <= 0
            ) {
                http_response_code(400);

                echo json_encode(
                    [
                        'ok' => false,
                        'mensaje' => 'Seleccione un producto y un vehículo.',
                    ],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );

                break;
            }


            $id = $repo->agregar(
                $prodId,
                $vehiId
            );


            echo json_encode(
                [
                    'ok' => true,
                    'id' => $id,
                    'mensaje' => 'Equivalente registrado.',
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;


        /* ====================================================
           ELIMINAR
        ==================================================== */

        case 'eliminar':

            $id = isset($_REQUEST['id'])
                ? (int) $_REQUEST['id']
                : 0;

            $eliminado = $repo->eliminar(
                $id
            );


            if (!$eliminado) {
                http_response_code(404);

                echo json_encode(
                    [
                        'ok' => false,
                        'mensaje' => 'La equivalencia no existe.',
                    ],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );

                break;
            }


            echo json_encode(
                [
                    'ok' => true,
                    'mensaje' => 'Equivalente eliminado.',
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;


        /* ====================================================
           OPERACIÓN NO VÁLIDA
        ==================================================== */

        default:

            http_response_code(400);

            echo json_encode(
                [
                    'ok' => false,
                    'mensaje' => 'Operación no válida.',
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;
    }


} catch (PDOException $e) {

    http_response_code(
        $e->getCode() === '23000'
            ? 409
            : 500
    );


    echo json_encode(
        [
            'ok' => false,
            'mensaje' => $e->getCode() === '23000'
                ? 'Este equivalente ya está registrado o los datos no existen.'
                : 'No se pudo completar la operación.',
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


} catch (Throwable $e) {

    http_response_code(500);


    echo json_encode(
        [
            'ok' => false,
            'mensaje' => errMsg($e),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}