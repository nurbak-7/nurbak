<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/worke/error.php
   Módulo: Catálogo / ASINC / WORKER
   Función: Registrar errores de procesamiento.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/job/repos.php';

final class WorkError
{
    private AsinRepo $repo;

    public function __construct(PDO $db)
    {
        $this->repo = new AsinRepo($db);
    }

    public function guardar(
        int $asinc,
        ?int $lote,
        ?int $fila,
        string $codigo,
        string $mens
    ): void {
        $this->repo->error($asinc, $lote, $fila, $codigo, $mens);
    }
}
