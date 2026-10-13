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

require_once dirname(__DIR__, 5) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__) . '/job/repos.php';
require_once __DIR__ . '/lotes.php';
require_once __DIR__ . '/progr.php';
require_once __DIR__ . '/error.php';
require_once __DIR__ . '/despa.php';

$db = CATALDB::abrir();
$repo = new AsinRepo($db);
$trab = $repo->obtener($id);

if (!$trab) {
    exit("TRABAJO_NO_EXISTE\n");
}

if ($trab['estado'] !== 'PROCESANDO') {
    exit("ESTADO_INVALIDO\n");
}

$lotes = new WorkLotes($db);
$progr = new WorkProgr($db);
$erro = new WorkError($db);
$despa = new WorkDespa();

$proce = (int) $trab['proce'];
$corre = (int) $trab['corre'];
$erroN = (int) $trab['erro'];

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

$actual = $repo->obtener($id);
if ($actual) {
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
}

echo "FINALIZADO {$id}\n";
