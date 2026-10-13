<?php
/* ============================================================
   Ruta: modul/categ/repor/infor/infor-contr.php
   Módulo: Reportes / Informes

   RESPONSABILIDAD:
   Recibir la petición y ejecutar una consulta real por área.

   Cada petición procesa UNA sola área.
   El JavaScript llama a las áreas en orden.
============================================================ */

declare(strict_types=1);
/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../../contr/error.php';

header(
    'Content-Type: application/json; charset=utf-8'
);
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
    /* AUTORIZACIÓN — Paso 4 | Permiso INFOV requerido en el servidor. */
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('INFOV')) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

require_once __DIR__ .
    '/infor-repos.php';


/* ============================================================
   DATOS DE ENTRADA
============================================================ */

$accion = trim(
    (string)($_REQUEST['accion'] ?? 'area')
);

$area = trim(
    (string)($_REQUEST['area'] ?? '')
);





try {

    $repo = new InfoRepo();


    /* ========================================================
       PROCESAR ÁREA
    ======================================================== */

    if ($accion === 'area') {

        if ($area === '') {

            http_response_code(400);

            echo json_encode(
                [
                    'ok' =>
                        false,

                    'mensaje' =>
                        'No se indicó el área del informe.',
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            exit;
        }


        $r =
            $repo->procesarArea($area);


        


        echo json_encode(
            [
                'ok' =>
                    true,

                'fuente' =>
                    $r['fuente'] ?? $area,

                'datos' =>
                    $r['datos'] ?? [],

                'errores' =>
                    $r['errores'] ?? [],

                'total_errores' =>
                    (int)(
                        $r['total_errores'] ?? 0
                    ),
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


        exit;
    }


    /* ========================================================
       RESUMEN COMPLETO
       Se conserva.
    ======================================================== */

    if ($accion === 'resumen') {

        $r =
            $repo->resumen();


        echo json_encode(
            [
                'ok' =>
                    true,

                'fuentes' =>
                    $r['fuentes'] ?? [],

                'errores' =>
                    $r['errores'] ?? [],

                'total_errores' =>
                    (int)(
                        $r['total_errores'] ?? 0
                    ),
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


        exit;
    }


    /* ========================================================
       ACCIÓN INVÁLIDA
    ======================================================== */

    http_response_code(400);


    


    echo json_encode(
        [
            'ok' =>
                false,

            'mensaje' =>
                'Operación no válida.',
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


} catch (Throwable $e) {

    http_response_code(500);


    


    echo json_encode(
        [
            'ok' =>
                false,

            'mensaje' => errMsg($e),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}