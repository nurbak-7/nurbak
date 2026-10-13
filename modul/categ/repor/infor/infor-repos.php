<?php
/* ============================================================
   Ruta: modul/categ/repor/infor/infor-repos.php
   Módulo: Reportes / Informes

   RESPONSABILIDAD:
   Consultar datos agregados directamente desde las bases de
   origen.

   El proceso real se ejecuta POR ÁREA:

   CATÁLOGO
   INVENTARIO
   COMPRAS
   VENTAS
   FACTURACIÓN
   PAGOS

   Cada área abre su conexión y consulta sus tablas.

   Si una tabla falla:
   - Se registra el error.
   - Se devuelve el error.
   - El proceso puede continuar con la siguiente área.

   No copia ni modifica datos operativos.
============================================================ */

declare(strict_types=1);


/* ============================================================
   CONEXIONES
============================================================ */

require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/inven-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/compr-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/factu-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/pagos-db.php';


final class InfoRepo
{

    /* ========================================================
       DEFINICIÓN DE ÁREAS
    ======================================================== */

    private function fuentes(): array
    {
        return [

            'catalogo' => [
                'db' => CATALDB::class,

                'tablas' => [
                    'productos'      => 'produ',
                    'vehiculos'      => 'vehic',
                    'equivalencia' => 'equiv',
                ],
            ],

            'inventario' => [
                'db' => INVENDB::class,

                'tablas' => [
                    'almacenes'   => 'alma',
                    'stock'       => 'stoc',
                    'movimientos' => 'movi',
                ],
            ],

            'compras' => [
                'db' => COMPRDB::class,

                'tablas' => [
                    'proveedores' => 'prov',
                    'ordenes'     => 'ordc',
                    'recepciones' => 'rece',
                ],
            ],

            'ventas' => [
                'db' => VENTADB::class,

                'tablas' => [
                    'clientes'    => 'clie',
                    'operaciones' => 'oper',
                    'notas_venta' => 'notv',
                    'devoluciones'=> 'devo',
                ],
            ],

            'facturacion' => [
                'db' => FACTUDB::class,

                'tablas' => [
                    'facturas'      => 'fact',
                    'boletas'       => 'bole',
                    'notas_credito' => 'ncre',
                    'notas_debito'  => 'ndeb',
                    'guias'         => 'grem',
                ],
            ],

            'pagos' => [
                'db' => PAGOSDB::class,

                'tablas' => [
                    'pagos'    => 'regp',
                    'cajas'    => 'caja',
                    'creditos' => 'cred',
                ],
            ],
        ];
    }


    /* ========================================================
       PROCESAR UNA SOLA ÁREA
    ======================================================== */

    public function procesarArea(string $nombr): array
    {

        $fuentes = $this->fuentes();


        /* ====================================================
           VERIFICAR ÁREA
        ==================================================== */

        if (!isset($fuentes[$nombr])) {

            throw new InvalidArgumentException(
                'Área de informe no válida.'
            );
        }


        $fuente = $fuentes[$nombr];

        $resultado = [];

        $errores = [];


        /* ====================================================
           ABRIR CONEXIÓN DEL ÁREA
        ==================================================== */

        try {

            $db = call_user_func(
                [$fuente['db'], 'abrir']
            );

        } catch (Throwable $e) {

            $error = [

                'fuente'  => $nombr,

                'tabla'   => null,

                'etiqueta' => null,

                'tipo' =>
                    get_class($e),

                'mensaje' =>
                    $e->getMessage(),
            ];


            $errores[] = $error;


            


            /*
             * La conexión del área falló.
             *
             * No se detiene el sistema.
             * Se devuelve el error para que JS
             * continúe con la siguiente área.
             */

            return [

                'fuente' => $nombr,

                'datos' => [],

                'errores' => $errores,

                'total_errores' => count($errores),
            ];
        }


        /* ====================================================
           CONSULTAR TABLAS DEL ÁREA
        ==================================================== */

        foreach ($fuente['tablas'] as $etiqueta => $tabla) {

            try {

                $st = $db->query(
                    'SELECT COUNT(*) FROM `' . $tabla . '`'
                );


                $canti =
                    (int)$st->fetchColumn();


                $resultado[$etiqueta] =
                    $canti;


                


            } catch (Throwable $e) {

                /*
                 * La tabla falló.
                 * No detenemos el área.
                 */

                $resultado[$etiqueta] =
                    null;


                $error = [

                    'fuente' =>
                        $nombr,

                    'tabla' =>
                        $tabla,

                    'etiqueta' =>
                        $etiqueta,

                    'tipo' =>
                        get_class($e),

                    'mensaje' =>
                        $e->getMessage(),
                ];


                $errores[] =
                    $error;


                
            }
        }


        /* ====================================================
           ÁREA TERMINADA
        ==================================================== */

        


        return [

            'fuente' =>
                $nombr,

            'datos' =>
                $resultado,

            'errores' =>
                $errores,

            'total_errores' =>
                count($errores),
        ];
    }


    /* ========================================================
       RESUMEN COMPLETO
       Se conserva para compatibilidad.
    ======================================================== */

    public function resumen(): array
    {

        $fuentes =
            $this->fuentes();

        $resultado = [];

        $errores = [];


        foreach ($fuentes as $nombr => $fuente) {

            $r =
                $this->procesarArea($nombr);


            $resultado[$nombr] =
                $r['datos'];


            foreach ($r['errores'] as $error) {

                $errores[] =
                    $error;
            }
        }


        


        return [

            'fuentes' =>
                $resultado,

            'errores' =>
                $errores,

            'total_errores' =>
                count($errores),
        ];
    }
}