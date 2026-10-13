<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/job/servi.php
   Módulo: Catálogo / ASINC / JOB
   Función: Servicio público para crear y consultar trabajos.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/jober.php';
require_once __DIR__ . '/repos.php';
require_once dirname(__DIR__) . '/coord/desp.php';

final class JobServi
{
    /* ========================================================
       NIVEL 1 — CONSTRUCTOR
    ======================================================== */

    public function __construct(private PDO $db)
    {
    }


    /* ========================================================
       NIVEL 1 — CREAR TRABAJO
    ======================================================== */

    public function crear(
        string $tipo,
        string $modo,
        ?string $archivo,
        int $total,
        ?int $tiend = null
    ): int {
        /* ====================================================
           NIVEL 2 — CREAR PROCESO Y LOTES
        ==================================================== */

        $id = (new Jober($this->db))->crear(
            $tipo,
            $modo,
            $archivo,
            $total,
            $tiend
        );


        /* ====================================================
           NIVEL 2 — ACTIVAR COORDINADOR
        ==================================================== */

        $coord = new CoordDesp();

        if (!$coord->activar()) {
            (new AsinRepo($this->db))->fallo(
                $id,
                'COORD_ERROR: no se pudo activar el coordinador ASINC.'
            );

            throw new RuntimeException(
                'No se pudo activar el coordinador ASINC.'
            );
        }


        /* ====================================================
           NIVEL 2 — DEVOLVER ID
        ==================================================== */

        return $id;
    }


    /* ========================================================
       NIVEL 1 — OBTENER TRABAJO
    ======================================================== */

    public function obtener(int $id): ?array
    {
        return (new Jober($this->db))->obtener($id);
    }
}