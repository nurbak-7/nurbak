<?php
/* ============================================================
   Ruta: contr/login/contr-perm.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   contr/login/cont-permi.php — Control central de permisos
   ------------------------------------------------------------
   🔹 Nivel 1: Identificar al usuario autenticado
   🔹 Nivel 2: Consultar el rol y acceso total del usuario
   🔹 Nivel 3: Permitir acceso total mediante rol
   🔹 Nivel 4: Comprobar permisos individuales del usuario
   🔹 Nivel 5: Autorizar o rechazar una operación
   🔹 Nivel Final: Centralizar la autorización del sistema LOGI
============================================================ */

class ContPerm
{
    public function __construct(
        private PDO $db
    ) {}


    /* ========================================================
       NIVEL 1 — IDENTIFICAR AL USUARIO AUTENTICADO
    ======================================================== */

    private function usuarioId(): int
    {
        return (int) ($_SESSION['usuario']['id'] ?? 0);
    }


    /* ========================================================
       NIVEL 2 — CONSULTAR EL ROL Y ACCESO TOTAL
    ======================================================== */

    private function accesoTotal(): bool
    {
        $usuaId = $this->usuarioId();

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
       NIVEL 3 — COMPROBAR ACCESO TOTAL
    ======================================================== */

    public function tieneAccesoTotal(): bool
    {
        return $this->accesoTotal();
    }


    /* ========================================================
       NIVEL 4 — COMPROBAR PERMISO INDIVIDUAL
    ======================================================== */

    public function tienePermiso(string $permCodi): bool
    {
        if ($this->accesoTotal()) {
            return true;
        }

        $usuaId = $this->usuarioId();

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
            INNER JOIN permi p
                ON p.id = up.permi
            WHERE
                up.usuar = :usuar
                AND p.codig = :codig
                AND p.activ = 1
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuar' => $usuaId,
            ':codig' => $permCodi
        ]);

        return (bool) $stmt->fetchColumn();
    }


    /* ========================================================
       NIVEL 5 — AUTORIZAR O RECHAZAR UNA OPERACIÓN
    ======================================================== */

    public function autorizar(string $permCodi): bool
    {
        return $this->tienePermiso($permCodi);
    }
}