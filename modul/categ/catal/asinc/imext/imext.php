<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/imext.php
   Módulo: Catálogo / ASINC / IMEXT
   Función: Coordinar los submódulos IMPORT y EXPORT.

   RESPONSABILIDAD:
   - Centralizar recursos comunes de IMEXT.
   - No contiene lógica de importación ni exportación.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/job/servi.php';

final class ImextCoord
{
    public static function servicio(PDO $db): JobServi
    {
        return new JobServi($db);
    }
}
