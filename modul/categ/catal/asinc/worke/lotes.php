<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/worke/lotes.php
   Módulo: Catálogo / ASINC / WORKER
   Función: Obtener y cerrar lotes de un trabajo.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/job/repos.php';

final class WorkLotes
{
    private AsinRepo $repo;

    public function __construct(PDO $db)
    {
        $this->repo = new AsinRepo($db);
    }

    public function pendientes(int $id): array
    {
        return $this->repo->lotes($id);
    }

    public function tomar(int $id): ?array
    {
        return $this->repo->tomarLote($id);
    }

    public function cerrar(int $id, string $estado): void
    {
        $this->repo->cerrarLote($id, $estado);
    }
}
