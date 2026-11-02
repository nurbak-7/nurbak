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
            'nombre' => 'F R MORY E.I.R.L.',
            'activi' => 'VENTA DE REPUESTOS Y ACCESORIOS EN GENERAL',
            'direc'  => 'AV. IQUITOS 248 INT. 5 LIMA - LIMA - LA VICTORIA',
            'ruc'    => '20610958029',
            'logo'   => __DIR__ . '/logo.jpg',

            /* CUENTAS BANCARIAS — USO EXCLUSIVO DE COTIZACIÓN */
            'cuen' => [
                'titu' => [
                    'FORMAS DE PAGO',
                    'CUENTA CORRIENTE',
                    'CUENTA CCI',
                ],
                'fila' => [
                    [
                        'banc' => 'BCP',
                        'cta'  => '191-7108240-0-03',
                        'cci'  => '002-19100710824000358',
                    ],
                    [
                        'banc' => 'YAPE',
                        'cta'  => '902-536-971',
                        'cci'  => 'FRANK Y. CONTRERAS A.',
                    ],
                ],
            ],

            /* MARCAS — 8 COLUMNAS × 2 FILAS, SOLO COTIZACIÓN */
            'marc' => [
                [
                    ['nomb' => 'HONDA',      'colo' => [0, 0, 0]],
                    ['nomb' => 'HYUNDAI',    'colo' => [0, 48, 105]],
                    ['nomb' => 'MAZDA',      'colo' => [0, 166, 214]],
                    ['nomb' => 'MITSUBISHI', 'colo' => [128, 42, 132]],
                    ['nomb' => 'NISSAN',     'colo' => [0, 153, 76]],
                    ['nomb' => 'SUZUKI',     'colo' => [210, 35, 42]],
                    ['nomb' => 'SUBARU',     'colo' => [0, 105, 190]],
                    ['nomb' => 'TOYOTA',     'colo' => [0, 0, 0]],
                ],
                [
                    ['nomb' => 'CHANGAN',   'colo' => [0, 0, 0]],
                    ['nomb' => 'CHERY',     'colo' => [0, 0, 0]],
                    ['nomb' => 'DFSK',      'colo' => [0, 0, 0]],
                    ['nomb' => 'DONGFENG',  'colo' => [0, 0, 0]],
                    ['nomb' => 'FAW',       'colo' => [0, 0, 0]],
                    ['nomb' => 'GRET WALL', 'colo' => [0, 0, 0]],
                    ['nomb' => 'GEELY',     'colo' => [0, 0, 0]],
                    ['nomb' => 'CHEVROLET', 'colo' => [0, 166, 214]],
                ],
            ],
        ],
    ];
}
