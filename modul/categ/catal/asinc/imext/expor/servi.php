<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/expor/servi.php
   Módulo: Catálogo / ASINC / IMEXT / EXPORT
   Función: Coordinar el procesamiento de exportaciones.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/lotes.php';

final class ExpoServi
{
    public function __construct(private PDO $db)
    {
    }

    public function lote(array $trab, array $lote): array
    {
        return (new ExpoLotes($this->db))->ejecutar($trab, $lote);
    }
}
