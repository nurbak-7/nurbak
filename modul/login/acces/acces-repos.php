<?php
/* ============================================================
   Ruta: modul/login/acces/acces-repos.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   modul/login/acces/acces-repos.php — Repositorio de autenticación
   ------------------------------------------------------------
   🔹 Nivel 1: Consultar el usuario
   🔹 Nivel 2: Registrar intentos fallidos
   🔹 Nivel 3: Aplicar bloqueo temporal
   🔹 Nivel 4: Comprobar bloqueo vigente
   🔹 Nivel 5: Reiniciar la seguridad
   🔹 Nivel 6: Registrar último acceso
   🔹 Nivel Final: Ejecutar únicamente operaciones sobre LOGI
============================================================ */

declare(strict_types=1);

final class AccesRepo
{
    public function __construct(private PDO $db)
    {
    }

    /*
       🔹 Nivel 1: Buscar un usuario activ.
    */
    public function porUsuario(string $usuario): ?array
    {
        $sql = 'SELECT
                    id,
                    empre,
                    rol,
                    operc,
                    logi,
                    nombr,
                    clave,
                    corre,
                    activ,
                    fallo,
                    bloqu,
                    ultim
                FROM usuar
                WHERE logi = :usuario
                  AND activ = 1
                LIMIT 1';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'usuario' => $usuario,
        ]);

        

        $resultado = $stmt->fetch();

        

        return $resultado ?: null;
    }

    /*
       🔹 Nivel 2: Registrar un intento fallido.

       El contador pertenece únicamente al usuario encontrado.
    */
    public function registrarFallo(int $id, int $intentos): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuar
             SET fallo = :fallo
             WHERE id = :id'
        );

        $stmt->execute([
            'fallo' => $intentos,
            'id' => $id,
        ]);

        
    }

    /*
       🔹 Nivel 3: Aplicar el bloqueo temporal.

       MySQL calcula directamente:
       hora actual + 10 minutos.

       No se utiliza la hora de PHP.
    */
    public function bloquear(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuar
             SET fallo = 3,
                 bloqu = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
        ]);

        
    }

    /*
       🔹 Nivel 4: Comprobar si el bloqueo sigue vigente.

       La comparación se realiza completamente en MySQL:
       bloqu > NOW()

       Así PHP y MySQL no dependen de tener el mismo horario.
    */
    public function bloqueoVigente(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM usuar
             WHERE id = :id
               AND bloqu IS NOT NULL
               AND bloqu > NOW()
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $id,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /*
       🔹 Nivel 5: Reiniciar el estad de seguridad.

       Se utiliza:
       - Después de un ingreso correcto.
       - Cuando el bloqueo temporal ya venció.
       - Después de una recuperación de contraseña.
    */
    public function reiniciarSeguridad(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuar
             SET fallo = 0,
                 bloqu = NULL
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
        ]);

        
    }

    /*
       🔹 Nivel 6: Registrar el último acceso correcto.
    */
    public function actualizarAcceso(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuar
             SET ultim = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
        ]);

        
    }
}