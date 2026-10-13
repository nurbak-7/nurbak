<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/coord/busca.php
   Módulo: Catálogo / ASINC / COORD
   Función: Consultar el estado de la cola ASINC.

   RESPONSABILIDAD:
   - Obtener el siguiente trabajo PENDIENTE.
   - Obtener un trabajo por ID.
   - Detectar trabajos PROCESANDO.

   NO HACE:
   - No crea trabajos.
   - No inicia trabajos.
   - No ejecuta CRON.
   - No procesa trabajos.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__) . '/job/repos.php';

final class CoordBusca
{
    /* ========================================================
       NIVEL 1 — CONSTRUCTOR
    ======================================================== */

    public function __construct(private PDO $db)
    {
    }


    /* ========================================================
       NIVEL 1 — OBTENER TRABAJO PENDIENTE
    ======================================================== */

    public function pendiente(): ?array
    {
        return (new AsinRepo($this->db))->pendiente();
    }


    /* ========================================================
       NIVEL 1 — OBTENER TRABAJO POR ID
    ======================================================== */

    public function obtener(int $id): ?array
    {
        return (new AsinRepo($this->db))->obtener($id);
    }


    /* ========================================================
       NIVEL 1 — OBTENER TRABAJO PROCESANDO
    ======================================================== */

    public function proc(): ?array
    {
        $st = $this->db->query(
            "SELECT *
             FROM asinc
             WHERE estado = 'PROCESANDO'
             ORDER BY fechi, id
             LIMIT 1"
        );

        $fila = $st->fetch();

        return $fila ?: null;
    }


    /* ========================================================
       NIVEL 1 — RESUMEN DE LOTES
    ======================================================== */

    public function resum(int $id): array
    {
        return (new AsinRepo($this->db))->resum($id);
    }


    /* ========================================================
       NIVEL 1 — FINALIZAR POR INCONSISTENCIA
    ======================================================== */

    public function fallo(
        int $id,
        string $mens
    ): void {
        (new AsinRepo($this->db))->fallo($id, $mens);
    }


    /* ========================================================
       NIVEL 1 — DETECTAR TRABAJO PROCESANDO
    ======================================================== */

    public function procesa(): bool
    {
        $st = $this->db->query(
            "SELECT id
             FROM asinc
             WHERE estado = 'PROCESANDO'
             ORDER BY id
             LIMIT 1"
        );

        return (bool) $st->fetchColumn();
    }
}