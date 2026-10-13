<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/job/repos.php
   Módulo: Catálogo / ASINC / JOB
   Función: Persistencia de procesos, lotes y errores.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/estad.php';

final class AsinRepo
{
    /* ========================================================
       NIVEL 1 — CONSTRUCTOR
    ======================================================== */

    public function __construct(private PDO $db)
    {
    }


    /* ========================================================
       NIVEL 1 — CREAR PROCESO
    ======================================================== */

    public function crear(
        string $tipo,
        string $modo,
        ?string $archivo,
        int $total,
        ?int $tiend = null
    ): int {
        $st = $this->db->prepare(
            'INSERT INTO asinc
                (tipo, modo, estado, tiend, archivo, total)
             VALUES
                (:tipo, :modo, :estado, :tiend, :archivo, :total)'
        );

        $st->execute([
            ':tipo' => $tipo,
            ':modo' => $modo,
            ':estado' => AsinEsta::PEND,
            ':tiend' => $tiend,
            ':archivo' => $archivo,
            ':total' => $total,
        ]);

        return (int) $this->db->lastInsertId();
    }


    /* ========================================================
       NIVEL 1 — CREAR LOTES
    ======================================================== */

    public function crearLotes(
        int $asinc,
        int $total,
        int $tam
    ): void {
        if ($total < 1) {
            return;
        }

        $st = $this->db->prepare(
            'INSERT INTO lotes
                (asinc_id, numero, inicio, cantidad)
             VALUES
                (:asinc, :numero, :inicio, :cantidad)'
        );

        $numero = 1;

        for (
            $inicio = 1;
            $inicio <= $total;
            $inicio += $tam
        ) {
            $cantidad = min(
                $tam,
                $total - $inicio + 1
            );

            $st->execute([
                ':asinc' => $asinc,
                ':numero' => $numero++,
                ':inicio' => $inicio,
                ':cantidad' => $cantidad,
            ]);
        }
    }


    /* ========================================================
       NIVEL 1 — OBTENER PROCESO
    ======================================================== */

    public function obtener(int $id): ?array
    {
        $st = $this->db->prepare(
            'SELECT *
             FROM asinc
             WHERE id = :id
             LIMIT 1'
        );

        $st->execute([
            ':id' => $id,
        ]);

        $fila = $st->fetch();

        return $fila ?: null;
    }


    /* ========================================================
       NIVEL 1 — OBTENER PROCESO PENDIENTE
    ======================================================== */

    public function pendiente(): ?array
    {
        $st = $this->db->query(
            "SELECT *
             FROM asinc
             WHERE estado = 'PENDIENTE'
             ORDER BY id
             LIMIT 1"
        );

        $fila = $st->fetch();

        return $fila ?: null;
    }


    /* ========================================================
       NIVEL 1 — RESUMEN DE LOTES
    ======================================================== */

    public function resum(int $asinc): array
    {
        $st = $this->db->prepare(
            "SELECT
                COALESCE(SUM(estado = 'PENDIENTE'), 0) AS pend,
                COALESCE(SUM(estado = 'PROCESANDO'), 0) AS proce,
                COALESCE(SUM(estado = 'COMPLETADO'), 0) AS comp,
                COALESCE(SUM(estado = 'ERROR'), 0) AS erro
             FROM lotes
             WHERE asinc_id = :asinc"
        );

        $st->execute([
            ':asinc' => $asinc,
        ]);

        $fila = $st->fetch();

        return [
            'pend' => (int) ($fila['pend'] ?? 0),
            'proce' => (int) ($fila['proce'] ?? 0),
            'comp' => (int) ($fila['comp'] ?? 0),
            'erro' => (int) ($fila['erro'] ?? 0),
        ];
    }


    /* ========================================================
       NIVEL 1 — INICIAR PROCESO
    ======================================================== */

    public function iniciar(int $id): bool
    {
        $st = $this->db->prepare(
            "UPDATE asinc
             SET estado = 'PROCESANDO',
                 fechi = COALESCE(fechi, NOW()),
                 inten = inten + 1
             WHERE id = :id
               AND estado = 'PENDIENTE'"
        );

        $st->execute([
            ':id' => $id,
        ]);

        return $st->rowCount() === 1;
    }


    /* ========================================================
       NIVEL 1 — OBTENER LOTES PENDIENTES
    ======================================================== */

    /* ========================================================
       NIVEL 1 — DEVOLVER PROCESO A PENDIENTE
    ======================================================== */

    public function devolver(int $id): bool
    {
        $st = $this->db->prepare(
            "UPDATE asinc
             SET estado = 'PENDIENTE'
             WHERE id = :id
               AND estado = 'PROCESANDO'"
        );

        $st->execute([
            ':id' => $id,
        ]);

        return $st->rowCount() === 1;
    }


    public function lotes(int $asinc): array
    {
        $st = $this->db->prepare(
            "SELECT *
             FROM lotes
             WHERE asinc_id = :asinc
               AND estado = 'PENDIENTE'
             ORDER BY numero"
        );

        $st->execute([
            ':asinc' => $asinc,
        ]);

        return $st->fetchAll();
    }


    /* ========================================================
       NIVEL 1 — TOMAR LOTE
    ======================================================== */

    public function tomarLote(int $id): ?array
    {
        $this->db->beginTransaction();

        try {
            $st = $this->db->prepare(
                "SELECT *
                 FROM lotes
                 WHERE id = :id
                   AND estado = 'PENDIENTE'
                 FOR UPDATE"
            );

            $st->execute([
                ':id' => $id,
            ]);

            $fila = $st->fetch();

            if (!$fila) {
                $this->db->rollBack();
                return null;
            }

            $up = $this->db->prepare(
                "UPDATE lotes
                 SET estado = 'PROCESANDO',
                     fechi = NOW()
                 WHERE id = :id
                   AND estado = 'PENDIENTE'"
            );

            $up->execute([
                ':id' => $id,
            ]);

            $this->db->commit();

            $fila['estado'] = 'PROCESANDO';

            return $fila;

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /* ========================================================
       NIVEL 1 — CERRAR LOTE
    ======================================================== */

    public function cerrarLote(
        int $id,
        string $estado
    ): void {
        $st = $this->db->prepare(
            'UPDATE lotes
             SET estado = :estado,
                 fechf = NOW()
             WHERE id = :id'
        );

        $st->execute([
            ':estado' => $estado,
            ':id' => $id,
        ]);
    }


    /* ========================================================
       NIVEL 1 — ACTUALIZAR PROGRESO
    ======================================================== */

    public function progreso(
        int $id,
        int $proce,
        int $corre,
        int $erro
    ): void {
        $fila = $this->obtener($id);

        if (!$fila) {
            return;
        }

        $total = (int) $fila['total'];

        $progr = $total > 0
            ? round(
                (($proce) / $total) * 100,
                2
            )
            : 100;

        $st = $this->db->prepare(
            'UPDATE asinc
             SET proce = :proce,
                 corre = :corre,
                 erro = :erro,
                 progr = :progr
             WHERE id = :id'
        );

        $st->execute([
            ':proce' => $proce,
            ':corre' => $corre,
            ':erro' => $erro,
            ':progr' => min(100, $progr),
            ':id' => $id,
        ]);
    }


    /* ========================================================
       NIVEL 1 — FINALIZAR PROCESO
    ======================================================== */

    public function finalizar(
        int $id,
        string $estado,
        string $mens = ''
    ): void {
        $st = $this->db->prepare(
            'UPDATE asinc
             SET estado = :estado,
                 progr = 100,
                 fechf = NOW(),
                 mens = :mens
             WHERE id = :id'
        );

        $st->execute([
            ':estado' => $estado,
            ':mens' => $mens,
            ':id' => $id,
        ]);
    }


    /* ========================================================
       NIVEL 1 — FINALIZAR POR INCONSISTENCIA
    ======================================================== */

    public function fallo(
        int $id,
        string $mens
    ): void {
        $this->db->beginTransaction();

        try {
            $lot = $this->db->prepare(
                "UPDATE lotes
                 SET estado = 'ERROR',
                     fechf = NOW()
                 WHERE asinc_id = :asinc
                   AND estado IN ('PENDIENTE', 'PROCESANDO')"
            );

            $lot->execute([
                ':asinc' => $id,
            ]);

            $st = $this->db->prepare(
                "UPDATE asinc
                 SET estado = 'ERROR',
                     progr = 100,
                     fechf = NOW(),
                     mens = :mens
                 WHERE id = :id
                   AND estado IN ('PENDIENTE', 'PROCESANDO')"
            );

            $st->execute([
                ':id' => $id,
                ':mens' => $mens,
            ]);

            $this->db->commit();

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }


    /* ========================================================
       NIVEL 1 — GUARDAR ERROR
    ======================================================== */

    public function error(
        int $asinc,
        ?int $lote,
        ?int $fila,
        string $codigo,
        string $mens
    ): void {
        $st = $this->db->prepare(
            'INSERT INTO error
                (asinc_id, lote_id, fila, codigo, mens)
             VALUES
                (:asinc, :lote, :fila, :codigo, :mens)'
        );

        $st->execute([
            ':asinc' => $asinc,
            ':lote' => $lote,
            ':fila' => $fila,
            ':codigo' => $codigo,
            ':mens' => $mens,
        ]);
    }


    /* ========================================================
       NIVEL 1 — OBTENER ERRORES
    ======================================================== */

    public function errores(int $asinc): array
    {
        $st = $this->db->prepare(
            'SELECT
                id,
                asinc_id,
                lote_id,
                fila,
                codigo,
                mens,
                fechc
             FROM error
             WHERE asinc_id = :asinc
             ORDER BY id'
        );

        $st->execute([
            ':asinc' => $asinc,
        ]);

        return $st->fetchAll();
    }
}