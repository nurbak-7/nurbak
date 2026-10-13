<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/job/estad.php
   Módulo: Catálogo / ASINC / JOB
   Función: Definir estados del proceso asíncrono.
============================================================ */
declare(strict_types=1);

final class AsinEsta
{
    public const PEND = 'PENDIENTE';
    public const PROCE = 'PROCESANDO';
    public const COMP = 'COMPLETADO';
    public const COMPERR = 'COMPLETADO_ERROR';
    public const ERROR = 'ERROR';
    public const CANCE = 'CANCELADO';
}
