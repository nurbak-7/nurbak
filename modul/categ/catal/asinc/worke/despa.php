<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/worke/despa.php
   Módulo: Catálogo / ASINC / WORKER
   Función: Despachar cada trabajo hacia su área de ejecución.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/imext/impor/servi.php';
require_once dirname(__DIR__) . '/imext/expor/servi.php';
require_once dirname(__DIR__, 5) . '/contr/categ/inven-db.php';
require_once dirname(__DIR__, 5) . '/contr/login/login-db.php';

final class WorkDespa
{
    public function ejecutar(
        PDO $db,
        array $trab,
        array $lote
    ): array {
        if (str_starts_with((string) $trab['tipo'], 'IMPO_')) {
            return (new ImpoServi(
                $db,
                INVENDB::abrir(),
                LOGINDB::abrir()
            ))->lote($trab, $lote);
        }

        if (str_starts_with((string) $trab['tipo'], 'EXPO_')) {
            return (new ExpoServi($db))->lote($trab, $lote);
        }

        throw new RuntimeException('Tipo de proceso no soportado.');
    }
}
