<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/worke/progr.php
   Módulo: Catálogo / ASINC / WORKER
   Función: Actualizar el progreso del trabajo.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/job/repos.php';

final class WorkProgr
{
    private AsinRepo $repo;

    public function __construct(PDO $db)
    {
        $this->repo = new AsinRepo($db);
    }

    public function actualizar(
        int $id,
        int $proce,
        int $corre,
        int $erro
    ): void {
        $this->repo->progreso($id, $proce, $corre, $erro);
    }
}
