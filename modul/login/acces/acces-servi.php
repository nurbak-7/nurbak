<?php
/* ============================================================
   Ruta: modul/login/acces/acces-servi.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   modul/login/acces/acces-servi.php — Servicio de autenticación
   ------------------------------------------------------------
   🔹 Nivel 1: Validar los datos recibidos
   🔹 Nivel 2: Consultar el usuario mediante el repositorio
   🔹 Nivel 3: Comprobar el bloqueo temporal
   🔹 Nivel 4: Verificar la contraseña con password_verify
   🔹 Nivel 5: Controlar los intentos fallidos
   🔹 Nivel 6: Restablecer la seguridad al ingresar correctamente
   🔹 Nivel Final: Devolver el usuario válido sin mostrar HTML

   SEGURIDAD:
   - Máximo: 3 intentos fallidos.
   - Al tercer fallo: bloqueo temporal.
   - Duración del bloqueo: 10 minutos.
   - El tiempo del bloqueo lo calcula MySQL.
   - La vigencia del bloqueo también la comprueba MySQL.
   - Usuario inexistente: no incrementa ningún contador.
   - Mensaje externo: siempre genérico.
============================================================ */

declare(strict_types=1);

final class AccesServ
{
    private const MAX_INTENTOS = 3;

    public function __construct(private AccesRepo $repo)
    {
    }

    public function ingresar(string $usuario, string $clave): ?array
    {
        // 🔹 Nivel 1: Limpiar y comprobar los datos recibidos.
        $usuario = trim($usuario);

        if ($usuario === '' || $clave === '') {
            

            return null;
        }

        // 🔹 Nivel 2: Buscar el usuario mediante el repositorio.
        $registro = $this->repo->porUsuario($usuario);

        

        /*
           Si el usuario no existe, no se incrementa ningún contador.

           Tampoco se informa si el usuario existe o no.
           El controlador mostrará el mensaje genérico.
        */
        if ($registro === null) {
            

            return null;
        }

        // 🔹 Nivel 3: Comprobar si el bloqueo todavía está vigente.
        if ($this->repo->bloqueoVigente((int) $registro['id'])) {
            

            return null;
        }

        /*
           Si existe una fecha de bloqueo pero MySQL confirma
           que ya venció, limpiamos el estad de seguridad.
        */
        if (
            $registro['bloqu'] !== null &&
            $registro['bloqu'] !== ''
        ) {
            $this->repo->reiniciarSeguridad((int) $registro['id']);

            $registro['fallo'] = 0;
            $registro['bloqu'] = null;

            
        }

        // 🔹 Nivel 4: Verificar la contraseña.
        if (!password_verify($clave, (string) $registro['clave'])) {

            $intentos = (int) $registro['fallo'] + 1;

            

            /*
               Al tercer intento, el repositorio aplica
               el bloqueo temporal de 10 minutos.
            */
            if ($intentos >= self::MAX_INTENTOS) {

                $this->repo->bloquear(
                    (int) $registro['id']
                );

                

                return null;
            }

            /*
               Primer y segundo intento fallido.
            */
            $this->repo->registrarFallo(
                (int) $registro['id'],
                $intentos
            );

            return null;
        }

        

        // 🔹 Nivel 5: Reiniciar la seguridad después del acceso correcto.
        $this->repo->reiniciarSeguridad((int) $registro['id']);

        

        // 🔹 Nivel 6: Actualizar el último acceso.
        $this->repo->actualizarAcceso((int) $registro['id']);

        

        return $registro;
    }
}