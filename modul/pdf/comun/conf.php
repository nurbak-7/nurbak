<?php
/* ============================================================
   Ruta: modul/pdf/comun/conf.php
   Módulo: PDF / Común

   NIVEL 1 — CONFIGURACIÓN VISUAL
   ------------------------------------------------------------
   Define el formato que utiliza el coordinador base.

   No contiene datos obtenidos desde las categorías.
   La empresa temporal queda aquí mientras el sistema termina.
============================================================ */
declare(strict_types=1);

function cfg(): array
{
    return [
        /* NIVEL 1.1 — PÁGINA */
        'marg' => 10,
        'auto' => 10,
        'page' => 'A4',
        'orie' => 'P',
        'unit' => 'mm',
        'font' => 'Courier',

        /* NIVEL 1.2 — TIPOS QUE USAN CIERRE FISCAL */
        'fisc' => [
            'FACTURA',
            'BOLETA',
            'NOTA DE CRÉDITO',
            'NOTA DE DÉBITO',
        ],

        /* NIVEL 1.3 — EMPRESA TEMPORAL */
        'empresa' => [
            'nombre' => 'F-R MORY E.I.R.L.',
            'activi' => 'VENTA DE REPUESTOS Y ACCESORIOS EN GENERAL',
            'direc'  => 'AV. IQUITOS 248 INT. 5 LIMA - LIMA - LA VICTORIA',
            'ruc'    => '20610958029',
            'logo'   => __DIR__ . '/logo.jpg',
        ],
    ];
}
