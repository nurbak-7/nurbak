<?php
/* ============================================================
   Ruta: modul/login/recu/recup-repos.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   modul/login/recup/recup-repo.php — Repositorio de recuperación
   ------------------------------------------------------------
   🔹 Consultar usuario autorizado
   🔹 Crear token de recuperación
   🔹 Consultar token vigente
   🔹 Marcar token como usado
   🔹 Actualizar contraseña y seguridad
   🔹 Ejecutar únicamente operaciones sobre LOGI
============================================================ */

declare(strict_types=1);

final class RecuRepo
{
    public function __construct(private PDO $db)
    {
    }

    /*
       🔹 Consultar usuario autorizado.

       La recuperación queda habilitada únicamente para usuarios
       cuyo rol tenga totac = 1.
    */
    public function usuarioAutorizado(string $usuario): ?array
    {
        $sql = 'SELECT
                    u.id,
                    u.logi,
                    u.corre,
                    u.clave,
                    u.activ,
                    r.totac
                FROM usuar u
                INNER JOIN rol r ON r.id = u.rol
                WHERE u.logi = :usuario
                  AND u.activ = 1
                  AND r.activ = 1
                  AND r.totac = 1
                LIMIT 1';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'usuario' => $usuario,
        ]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /*
       🔹 Crear token de recuperación.

       El token real no se guarda.
       Se guarda únicamente su hash.

       MySQL calcula la fecha de vencimiento:
       NOW() + 5 minutos.
    */
    public function crearToken(int $usuaId, string $hash): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO recup (
                usuar,
                recu_hash,
                recu_expi
             ) VALUES (
                :usuar,
                :recu_hash,
                DATE_ADD(NOW(), INTERVAL 5 MINUTE)
             )'
        );

        $stmt->execute([
            'usuar' => $usuaId,
            'recu_hash' => $hash,
        ]);
    }

    /*
       🔹 Consultar token vigente.

       El token debe:
       - existir;
       - no haber sido utilizado;
       - no estar vencido.

       La fecha se compara mediante MySQL.
    */
    public function tokenVigente(string $hash): ?array
    {
        $sql = 'SELECT
                    id,
                    usuar,
                    recu_expi,
                    recu_usad
                FROM recup
                WHERE recu_hash = :recu_hash
                  AND recu_usad = 0
                  AND recu_expi > NOW()
                LIMIT 1';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'recu_hash' => $hash,
        ]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /*
       🔹 Marcar token como utilizado.
    */
    public function marcarUsado(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE recup
             SET recu_usad = 1
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
        ]);
    }

    /*
       🔹 Actualizar contraseña y reiniciar seguridad.

       Después de una recuperación correcta:
       - reemplaza clave;
       - reinicia los intentos fallidos;
       - elimina el bloqueo temporal.
    */
    public function actualizarClave(int $usuaId, string $hashClave): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuar
             SET clave = :clave,
                 fallo = 0,
                 bloqu = NULL
             WHERE id = :id'
        );

        $stmt->execute([
            'clave' => $hashClave,
            'id' => $usuaId,
        ]);
    }
}