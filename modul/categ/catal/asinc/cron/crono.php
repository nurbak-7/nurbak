<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/cron/crono.php
   Módulo: Catálogo / ASINC / CRON
   Función: Detectar trabajos pendientes y despacharlos.
   Ejecución: CLI / tarea programada.
============================================================ */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI solamente.\n");
}

require_once dirname(__DIR__, 5) . '/contr/categ/catal-db.php';
require_once __DIR__ . '/busca.php';
require_once __DIR__ . '/bloqu.php';
require_once __DIR__ . '/dispa.php';
require_once dirname(__DIR__) . '/job/repos.php';

$db = CATALDB::abrir();

$busca = new CronBusca($db);
$bloqu = new CronBloqu($db);
$dispa = new CronDispa();

$trab = $busca->uno();

if (!$trab) {
    exit("SIN_PENDIENTES\n");
}

$id = (int) $trab['id'];

if (!$bloqu->tomar($id)) {
    exit("NO_TOMADO\n");
}

if ($dispa->ejecutar($id)) {
    echo "DESPACHADO {$id}\n";
    exit;
}

(new AsinRepo($db))->devolver($id);

echo "NO_DESPACHADO {$id}\n";
