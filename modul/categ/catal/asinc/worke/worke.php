<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/worke/worke.php
   Módulo: Catálogo / ASINC / WORKER
   Función: Ejecutar un trabajo por lotes.
   Ejecución: CLI.
============================================================ */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI solamente.\n");
}

$id = (int) ($argv[1] ?? 0);

if ($id < 1) {
    exit("ID_INVALIDO\n");
}

$db = null;
$repo = null;

try {
    /* ========================================================
       NIVEL 1 — CONEXIÓN Y REPOSITORIO DEL PROCESO
       Los errores de inicialización también deben llegar a IMEXT.
    ======================================================== */

    require_once dirname(__DIR__, 5) . '/contr/categ/catal-db.php';
    require_once dirname(__DIR__) . '/job/repos.php';

    $db = CATALDB::abrir();
    $repo = new AsinRepo($db);
    $trab = $repo->obtener($id);

    if (!$trab) {
        throw new RuntimeException('TRABAJO_NO_EXISTE: no existe el proceso solicitado en CATAL.');
    }

    if ($trab['estado'] !== 'PROCESANDO') {
        throw new RuntimeException(
            'ESTADO_INVALIDO: el proceso está en estado '
            . (string) $trab['estado']
            . ' y WORKER esperaba PROCESANDO.'
        );
    }

    /* ========================================================
       NIVEL 1 — COMPONENTES DE EJECUCIÓN
    ======================================================== */

    require_once __DIR__ . '/lotes.php';
    require_once __DIR__ . '/progr.php';
    require_once __DIR__ . '/error.php';
    require_once __DIR__ . '/despa.php';

    $lotes = new WorkLotes($db);
    $progr = new WorkProgr($db);
    $erro = new WorkError($db);
    $despa = new WorkDespa();

    $proce = (int) $trab['proce'];
    $corre = (int) $trab['corre'];
    $erroN = (int) $trab['erro'];

    /* ========================================================
       NIVEL 1 — PROCESAR LOTES
    ======================================================== */

    foreach ($lotes->pendientes($id) as $ref) {
        $lote = $lotes->tomar((int) $ref['id']);

        if (!$lote) {
            continue;
        }

        try {
            $res = $despa->ejecutar($db, $trab, $lote);

            $proce += (int) ($res['proce'] ?? $lote['cantidad']);
            $corre += (int) ($res['corre'] ?? 0);
            $erroN += (int) ($res['erro'] ?? 0);

            $progr->actualizar($id, $proce, $corre, $erroN);
            $lotes->cerrar((int) $lote['id'], 'COMPLETADO');
        } catch (Throwable $e) {
            $erroN += (int) $lote['cantidad'];
            $erro->guardar(
                $id,
                (int) $lote['id'],
                null,
                'WORK_ERROR',
                $e->getMessage()
            );
            $progr->actualizar($id, $proce, $corre, $erroN);
            $lotes->cerrar((int) $lote['id'], 'ERROR');
        }
    }

    /* ========================================================
       NIVEL 1 — FINALIZAR PROCESO
    ======================================================== */

    $actual = $repo->obtener($id);

    if (!$actual) {
        throw new RuntimeException('TRABAJO_NO_EXISTE: no se pudo recuperar el proceso al finalizar.');
    }

    if ((int) $actual['proce'] < (int) $actual['total']) {
        throw new RuntimeException(
            'WORKER_INCOMPLETO: procesados '
            . (int) $actual['proce']
            . ' de '
            . (int) $actual['total']
            . ' filas.'
        );
    }

    $estado = ((int) $actual['erro']) > 0
        ? 'COMPLETADO_ERROR'
        : 'COMPLETADO';

    $repo->finalizar(
        $id,
        $estado,
        $estado === 'COMPLETADO'
            ? 'Proceso completado.'
            : 'Proceso completado con errores.'
    );

    echo "FINALIZADO {$id}\n";

} catch (Throwable $e) {
    /* ========================================================
       NIVEL 1 — MOSTRAR LA FALLA EN LA VENTANA IMEXT
       Se utiliza el campo mens y el estado del proceso existentes.
       No se crea un log ni una tabla adicional.
    ======================================================== */

    if ($repo instanceof AsinRepo) {
        try {
            $repo->fallo(
                $id,
                'WORKER_ERROR: ' . $e->getMessage()
            );
        } catch (Throwable $persistencia) {
            // Si la base no permite guardar el error, no se oculta
            // ni se reemplaza el mensaje original en el flujo de PHP.
        }
    }

    echo 'WORKER_ERROR ' . $e->getMessage() . "\n";
    exit(1);
}
