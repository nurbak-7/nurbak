<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/cron/bloqu.php
   Módulo: Catálogo / ASINC / CRON
   Función: Bloquear lógicamente un trabajo antes de despacharlo.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/job/repos.php';

final class CronBloqu
{
    public function __construct(private PDO $db)
    {
    }

    public function tomar(int $id): bool
    {
        return (new AsinRepo($this->db))->iniciar($id);
    }
}
