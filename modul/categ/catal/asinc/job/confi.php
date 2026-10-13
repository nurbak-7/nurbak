<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/job/confi.php
   Módulo: Catálogo / ASINC / JOB
   Función: Configuración común de procesos asíncronos.
============================================================ */
declare(strict_types=1);

return [
    'lote' => 5000,
    'intentos' => 3,
    'max_archivo' => 200 * 1024 * 1024,
    'tiempo' => 300,
];
