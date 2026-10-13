<?php
/* ============================================================
   Ruta: modul/login/aut/autor-repos.php
   Módulo: LOGI / Autorización
   Función: Consultar en LOGI si un usuario puede ejecutar una
            acción o acceder a un módulo.

   RESPONSABILIDAD:
   - Consultar el acceso total del usuario.
   - Consultar permisos individuales.
   - No contiene reglas de interfaz.
   - No abre ventanas.
   - No procesa solicitudes HTTP.

   🔹 Nivel 1: Consultar acceso total.
   🔹 Nivel 2: Consultar permiso individual.
   🔹 Nivel Final: Ejecutar únicamente consultas sobre LOGI.
============================================================ */

declare(strict_types=1);

final class AutRepo
{
    public function __construct(
        private PDO $db
    ) {
    }

    /* ========================================================
       NIVEL 1 — CONSULTAR ACCESO TOTAL
    ======================================================== */

    public function tieneAccesoTotal(int $usuaId): bool
    {
        if ($usuaId <= 0) {
            return false;
        }

        $sql = "
            SELECT
                r.totac
            FROM usuar u
            INNER JOIN rol r
                ON r.id = u.rol
            WHERE
                u.id = :usuar
                AND u.activ = 1
                AND r.activ = 1
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuar' => $usuaId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /* ========================================================
       NIVEL 2 — CONSULTAR PERMISO INDIVIDUAL
    ======================================================== */

    public function tienePermiso(int $usuaId, string $permCodi): bool
    {
        if ($usuaId <= 0) {
            return false;
        }

        $permCodi = trim($permCodi);

        if ($permCodi === '') {
            return false;
        }

        $sql = "
            SELECT 1
            FROM usape up
            INNER JOIN usuar u
                ON u.id = up.usuar
            INNER JOIN permi p
                ON p.id = up.permi
            WHERE
                up.usuar = :usuar
                AND u.activ = 1
                AND p.codig = :codig
                AND p.activ = 1
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuar' => $usuaId,
            ':codig' => $permCodi
        ]);

        return $stmt->fetchColumn() !== false;
    }
}
