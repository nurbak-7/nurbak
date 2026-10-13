<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/expor/expor.php
   Módulo: Catálogo / ASINC / IMEXT / EXPORT

   Función:
   Endpoint para crear, consultar y descargar el Excel integral.

   FORMATO:
   Una hoja por MARCA.
============================================================ */
declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../../../../contr/error.php';

require_once dirname(__DIR__, 6) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 6) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__, 6) . '/contr/login/contr-perm.php';

if (!CAuth::autenticado()) {
    http_response_code(401);
    exit('Sesión no válida.');
}

$contPerm = new ContPerm(LOGINDB::abrir());
if (!$contPerm->autorizar('IMEXG')) {
    http_response_code(403);
    exit('Operación no autorizada.');
}

/* AUTORIZACIÓN — Paso 4 | IMEXG */


$accion = trim(
    (string) ($_REQUEST['accion'] ?? '')
);

CAuth::metod($accion, ['crear']);

if ($accion === 'descargar') {

    require_once dirname(__DIR__, 6)
        . '/contr/categ/catal-db.php';

    require_once dirname(__DIR__)
        . '/imext.php';

    $db = CATALDB::abrir();
    $job = ImextCoord::servicio($db);

    $dato = $job->obtener(
        (int) ($_GET['id'] ?? 0)
    );

    if (
        !$dato
        || !in_array(
            $dato['estado'],
            ['COMPLETADO', 'COMPLETADO_ERROR'],
            true
        )
    ) {
        http_response_code(404);
        exit('Archivo no disponible.');
    }

    $ruta = (string) $dato['archivo'];

    if (!is_file($ruta)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }

    header(
        'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    header(
        'Content-Disposition: attachment; filename="catalogo-integral.xlsx"'
    );

    header(
        'Content-Length: ' . filesize($ruta)
    );

    readfile($ruta);
    exit;
}

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once dirname(__DIR__, 6)
    . '/contr/categ/catal-db.php';

require_once dirname(__DIR__)
    . '/imext.php';

require_once __DIR__
    . '/filtr.php';

require_once __DIR__
    . '/archi.php';

require_once __DIR__
    . '/repos.php';

try {

    $db = CATALDB::abrir();

    $job = ImextCoord::servicio($db);

    if ($accion === 'crear') {

        $filtro = (new ExpoFiltr())
            ->leer($_POST);

        $repo = new ExpoRepo($db);

        $total = $repo->contar(
            $filtro['tipo'],
            $filtro['buscar']
        );

        if ($total < 1) {
            throw new RuntimeException(
                'No existen artículos para exportar.'
            );
        }

        $archivo = (new ExpoArchi())
            ->crear($filtro['tipo']);

        $id = $job->crear(
            $filtro['tipo'],
            'AMBOS',
            $archivo,
            $total
        );

        if ($filtro['buscar'] !== '') {
            $st = $db->prepare(
                'UPDATE asinc
                 SET mens=:mens
                 WHERE id=:id'
            );

            $st->execute([
                ':mens' => 'BUSCAR=' . $filtro['buscar'],
                ':id' => $id,
            ]);
        }

        echo json_encode(
            [
                'ok' => true,
                'id' => $id,
                'total' => $total,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    if ($accion === 'estado') {

        $dato = $job->obtener(
            (int) ($_GET['id'] ?? 0)
        );

        if (!$dato) {
            throw new RuntimeException(
                'Proceso no encontrado.'
            );
        }

        echo json_encode(
            [
                'ok' => true,
                'proceso' => $dato,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    throw new InvalidArgumentException(
        'Acción no válida.'
    );

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => errMsg($e),
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );
}
