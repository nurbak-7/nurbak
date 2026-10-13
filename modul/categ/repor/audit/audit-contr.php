<?php
/* ============================================================
   Ruta: modul/categ/repor/audit/audit-contr.php
   Módulo: Reportes / Auditoría

   RESPONSABILIDAD:
   Recibir consultas del historial de auditoría.

   Devuelve:
   - registros encontrados
   - canti de registros
   - errores encontrados
============================================================ */

declare(strict_types=1);
/* Nivel 1 — Manejo normal de errores. */
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
    /* AUTORIZACIÓN — Paso 4 | Permiso AUDIV requerido en el servidor. */
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('AUDIV')) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

/* ============================================================
   DEPENDENCIAS
============================================================ */

require_once dirname(__DIR__, 4) . '/contr/categ/repor-db.php';

require_once __DIR__ . '/audit-repos.php';


/* ============================================================
   DATOS DE ENTRADA
============================================================ */

$accion = trim(
    (string)($_REQUEST['accion'] ?? 'listar')
);

$buscar = trim(
    (string)($_REQUEST['buscar'] ?? '')
);

$limite = (int)(
    $_REQUEST['limite'] ?? 50
);





/* ============================================================
   PROCESAR PETICIÓN
============================================================ */

try {

    /* ========================================================
       CONEXIÓN REAL A REPO
    ======================================================== */

    $db = REPORDB::abrir();


    /* ========================================================
       CREAR REPOSITORIO
    ======================================================== */

    $repo = new AudiRepo($db);


    /* ========================================================
       ACCIONES
    ======================================================== */

    switch ($accion) {


        /* ====================================================
           LISTAR AUDITORÍA
        ==================================================== */

        case 'listar':

            /*
             * Consulta real a repo.audit.
             */

            $r = $repo->listar(
                $buscar,
                $limite
            );


            /*
             * Registrar resultado.
             */

            


            /*
             * Respuesta al navegador.
             */

            echo json_encode(
                [
                    'ok' =>
                        true,

                    'auditoria' =>
                        $r,

                    'total_registros' =>
                        count($r),

                    'errores' =>
                        [],

                    'total_errores' =>
                        0,
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;


        /* ====================================================
           ACCIÓN NO VÁLIDA
        ==================================================== */

        default:

            http_response_code(400);


            


            echo json_encode(
                [
                    'ok' =>
                        false,

                    'mensaje' =>
                        'Operación no válida.',

                    'errores' => [
                        [
                            'fuente' =>
                                'AUDITORÍA',

                            'mensaje' =>
                                'Operación no válida.',
                        ],
                    ],

                    'total_errores' =>
                        1,
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;
    }


/* ============================================================
   ERROR GENERAL
============================================================ */

} catch (Throwable $e) {

    http_response_code(500);


    $error = [
        'fuente' =>
            'AUDITORÍA',

        'tipo' =>
            get_class($e),

        'mensaje' => errMsg($e),

        'archivo' =>
            $e->getFile(),

        'linea' =>
            $e->getLine(),
    ];


    


    /*
     * El error se devuelve al frontend.
     *
     * El JS decidirá cómo mostrarlo y finalizar
     * el recorrido sin dejar el proceso ejecutándose.
     */

    echo json_encode(
        [
            'ok' =>
                false,

            'auditoria' =>
                [],

            'total_registros' =>
                0,

            'errores' =>
                [
                    $error
                ],

            'total_errores' =>
                1,

            'mensaje' => errMsg($e),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}