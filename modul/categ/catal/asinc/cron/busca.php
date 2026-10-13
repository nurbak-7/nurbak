<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/cron/busca.php
   Módulo: Catálogo / ASINC / CRON
   Función: Buscar el siguiente trabajo pendiente.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/job/repos.php';

final class CronBusca
{
    public function __construct(private PDO $db)
    {
    }

    public function uno(): ?array
    {
        return (new AsinRepo($this->db))->pendiente();
    }
}
