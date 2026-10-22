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
    private PDO $db;
    private AsinRepo $repo;
    private array $confi;

    public function __construct(PDO $db)
    {
        $this->db = $db;
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
        if ($total < 1) {
            throw new InvalidArgumentException(
                'El proceso debe contener al menos una fila.'
            );
        }

        if ($this->db->inTransaction()) {
            throw new RuntimeException(
                'No se puede crear ASINC dentro de una transacción externa.'
            );
        }

        $this->db->beginTransaction();

        try {
            $id = $this->repo->crear(
                $tipo,
                $modo,
                $archivo,
                $total,
                $tiend
            );

            if ($id < 1) {
                throw new RuntimeException(
                    'SQL no devolvió un identificador válido para ASINC.'
                );
            }

            $this->repo->crearLotes(
                $id,
                $total,
                (int) $this->confi['lote']
            );

            $registro = $this->repo->obtener($id);

            if (
                !$registro
                || (int) $registro['id'] !== $id
                || (int) $registro['total'] !== $total
            ) {
                throw new RuntimeException(
                    'El registro ASINC no coincide con los datos creados en SQL.'
                );
            }

            $this->db->commit();

            return (int) $registro['id'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function obtener(int $id): ?array
    {
        return $this->repo->obtener($id);
    }
}