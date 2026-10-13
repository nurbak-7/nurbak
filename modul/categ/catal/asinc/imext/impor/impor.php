<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/impor.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT

   Función:
   Endpoint para crear y consultar importaciones
   integrales XLSX.

   NIVEL 1:
   - Crear proceso de importación.
   - Consultar estado del proceso.
   - Consultar errores persistidos del proceso.

   NIVEL 2:
   - No ejecuta CRON.
   - No ejecuta WORKE.
   - No modifica la tabla error.
   - Solo entrega información al frontend.
============================================================ */
declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../../../../contr/error.php';

header('Content-Type: application/json; charset=utf-8');

try {

    /* ========================================================
       NIVEL 1 — CONTROL DE ERRORES
    ======================================================== */

    set_error_handler(
        static function (
            int $nivel,
            string $mensaje,
            string $archivo,
            int $linea
        ): bool {
            throw new ErrorException(
                $mensaje,
                0,
                $nivel,
                $archivo,
                $linea
            );
        }
    );


    /* ========================================================
       NIVEL 1 — CONEXIONES
    ======================================================== */

    require_once dirname(__DIR__, 6) . '/contr/categ/catal-db.php';
    require_once dirname(__DIR__) . '/imext.php';
    require_once dirname(__DIR__, 2) . '/job/repos.php';
    require_once __DIR__ . '/archi.php';
    require_once __DIR__ . '/servi.php';
    require_once dirname(__DIR__, 6) . '/contr/categ/inven-db.php';
    require_once dirname(__DIR__, 6) . '/contr/login/login-db.php';
    require_once dirname(__DIR__, 6) . '/contr/login/contr-auth.php';
    require_once dirname(__DIR__, 6) . '/contr/login/contr-perm.php';

    if (!CAuth::autenticado()) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'mensaje'=>'Sesión no válida.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('IMEXG')) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'mensaje'=>'Operación no autorizada.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* AUTORIZACIÓN — Paso 4 | IMEXG */

    $accion = trim((string) ($_REQUEST['accion'] ?? ''));
    CAuth::metod($accion, ['crear']);

    $log = LOGINDB::abrir();

    $db = CATALDB::abrir();
    $inve = INVENDB::abrir();
    $job = ImextCoord::servicio($db);

    $impo = new ImpoServi(
        $db,
        $inve,
        $log
    );

    $erro = new AsinRepo($db);

    /* ========================================================
       NIVEL 1 — TIENDAS ACTIVAS
    ======================================================== */

    if ($accion === 'tiendas') {

        echo json_encode(
            [
                'ok' => true,
                'tiendas' => $impo->tiendas(),
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }


    /* ========================================================
       NIVEL 1 — CREAR IMPORTACIÓN
    ======================================================== */

    if ($accion === 'crear') {

        /* ----------------------------------------------------
           NIVEL 2 — MODO
        ---------------------------------------------------- */

        $modo = strtoupper(
            trim(
                (string) ($_POST['modo'] ?? 'AMBOS')
            )
        );

        if (!in_array(
            $modo,
            ['NUEVO', 'ACTUAL', 'AMBOS'],
            true
        )) {
            throw new InvalidArgumentException(
                'Modo de importación no válido.'
            );
        }


        /* ----------------------------------------------------
           NIVEL 2 — TIENDA
        ---------------------------------------------------- */

        $tiend = (int) (
            $_POST['tiend'] ?? 0
        );

        $impo->contexto($tiend);


        /* ----------------------------------------------------
           NIVEL 2 — ARCHIVO
        ---------------------------------------------------- */

        $archi = new ImpoArchi();

        $ruta = $archi->guardar(
            $_FILES['archivo'] ?? []
        );

        $total = $archi->contar($ruta);


        /* ----------------------------------------------------
           NIVEL 2 — CREAR PROCESO
        ---------------------------------------------------- */

        $id = $job->crear(
            'IMPO_INTEG',
            $modo,
            $ruta,
            $total,
            $tiend
        );


        /* ----------------------------------------------------
           NIVEL 2 — RESPUESTA
        ---------------------------------------------------- */

        echo json_encode(
            [
                'ok' => true,
                'id' => $id,
                'total' => $total,
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }


    /* ========================================================
       NIVEL 1 — CONSULTAR ESTADO
    ======================================================== */

    if ($accion === 'estado') {

        /* ----------------------------------------------------
           NIVEL 2 — ID
        ---------------------------------------------------- */

        $id = (int) (
            $_GET['id'] ?? 0
        );

        if ($id < 1) {
            throw new InvalidArgumentException(
                'ID de proceso no válido.'
            );
        }


        /* ----------------------------------------------------
           NIVEL 2 — PROCESO
        ---------------------------------------------------- */

        $dato = $job->obtener($id);

        if (!$dato) {
            throw new RuntimeException(
                'Proceso no encontrado.'
            );
        }


        /* ----------------------------------------------------
           NIVEL 2 — ERRORES PERSISTIDOS
        ---------------------------------------------------- */

        $errores = $erro->errores($id);


        /* ----------------------------------------------------
           NIVEL 2 — RESPUESTA
        ---------------------------------------------------- */

        echo json_encode(
            [
                'ok' => true,

                'proceso' => $dato,

                'errores' => $errores,
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }


    /* ========================================================
       NIVEL 1 — ACCIÓN NO VÁLIDA
    ======================================================== */

    throw new InvalidArgumentException(
        'Acción no válida.'
    );


/* ============================================================
   NIVEL 1 — ERROR DEL ENDPOINT
============================================================ */

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => errMsg($e),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}