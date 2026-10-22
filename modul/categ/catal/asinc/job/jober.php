<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/job/jober.php
   Módulo: Catálogo / ASINC / JOB
   Función: Crear y consultar trabajos asíncronos.

   RESPONSABILIDAD:
   - Crear trabajos ASINC.
   - Crear los lotes del trabajo.
   - Consultar trabajos existentes.

   NO HACE:
   - No busca trabajos pendientes.
   - No ejecuta CRON.
   - No procesa trabajos.
   - No procesa lotes.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/repos.php';
require_once __DIR__ . '/confi.php';

final class Jober
{
    private AsinRepo $repo;
    private array $confi;

    public function __construct(PDO $db)
    {
        $this->repo = new AsinRepo($db);
        $this->confi = require __DIR__ . '/confi.php';
    }

    public function crear(
        string $tipo,
        string $modo,
        ?string $archivo,
        int $total,
        ?int $tiend = null
    ): int {
        $id = $this->repo->crear(
            $tipo,
            $modo,
            $archivo,
            $total,
            $tiend
        );

        $this->repo->crearLotes(
            $id,
            $total,
            (int) $this->confi['lote']
        );

        return $id;
    }

    public function obtener(int $id): ?array
    {
        return $this->repo->obtener($id);
    }
}