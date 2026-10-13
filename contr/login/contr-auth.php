<?php
/* ============================================================
   Ruta: contr/login/contr-auth.php

   Módulo: LOGI / Autenticación

   FUNCIÓN:
   Gestionar la sesión del usuario después de que LOGI haya
   validado correctamente sus credenciales.

   ESTE ARCHIVO NO:
   - consulta directamente la base de datos;
   - valida la contraseña;
   - guarda la contraseña en la sesión.

   El flujo de autenticación es:

   acce-logi.php
        ↓
   acces-contr.php
        ↓
   acces-servi.php
        ↓
   acces-repos.php
        ↓
   LOGI / usuar
        ↓
   CAuth::entrar()
        ↓
   $_SESSION
        ↓
   Dashboard / Navbar

   CAMPOS DE LOGI UTILIZADOS:
   - id
   - empre
   - rol
   - operc
   - logi
   - nombr
   - corre
   - activ

   clave se utiliza únicamente durante la autenticación
   y NO se almacena dentro de $_SESSION.

   RESPONSABILIDADES:
   1. Iniciar la sesión.
   2. Controlar el tiempo de inactividad.
   3. Regenerar el identificador de sesión al ingresar.
   4. Guardar los datos necesarios del usuario autenticado.
   5. Permitir consultar el usuario actual.
   6. Cerrar completamente la sesión.

   🔹 Nivel 1: Iniciar sesión.
   🔹 Nivel 2: Controlar inactividad.
   🔹 Nivel 3: Registrar usuario autenticado.
   🔹 Nivel 4: Consultar estad de autenticación.
   🔹 Nivel 5: Obtener usuario actual.
   🔹 Nivel Final: Cerrar sesión.

   IMPORTANTE:
   Los nombres utilizados aquí corresponden directamente a
   la estructura abreviada actual de LOGI.
============================================================ */

declare(strict_types=1);

final class CAuth
{
    /*
       Tiempo de inactividad actual de prueba.

       30 = 30 segundos.

       Cuando el usuario deja de realizar actividad durante este
       período, la sesión se cierra automáticamente.

       Más adelante puede cambiarse al tiempo definitivo sin
       modificar el resto de la autenticación.
    */
    private const TIEMPO_INACTIVIDAD = 30;

    /*
       🔹 Nivel 1: Iniciar la sesión.
    */
    public static function iniciar(bool $act = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }

        /*
           🔹 Nivel 2: Comprobar la inactividad.

           Consultar la sesión no cuenta como actividad.
           La marca de actividad solo se actualiza cuando el
           llamador lo solicita explícitamente.
        */
        if (!isset($_SESSION['usuario']['id'], $_SESSION['ultima_actividad'])) {
            return;
        }

        $tiempoSinActividad = time() - (int) $_SESSION['ultima_actividad'];

        if ($tiempoSinActividad >= self::TIEMPO_INACTIVIDAD) {
            self::salir();
            return;
        }

        if ($act) {
            $_SESSION['ultima_actividad'] = time();
        }
    }

    /*
       🔹 Nivel 3: Guardar el usuario autenticado.

       Los nombres utilizados aquí corresponden directamente
       a las columnas actuales de LOGI.

       clave NO se guarda en la sesión.
    */
    public static function entrar(array $usuario): void
    {
        self::iniciar();
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id'       => (int) $usuario['id'],
            'empre'  => $usuario['empre'] !== null
                ? (int) $usuario['empre']
                : null,
            'rol'   => (int) $usuario['rol'],
            'operc'=> $usuario['operc'],
            'logi'=> $usuario['logi'],
            'nombr'=> $usuario['nombr'],
            'corre'=> $usuario['corre'],
            'activ'   => (int) $usuario['activ'],
        ];

        /* Comenzar a contar desde el momento del ingreso. */
        $_SESSION['ultima_actividad'] = time();
    }

    /*
       🔹 Nivel 3: Comprobar el acceso.
    */
    public static function autenticado(): bool
    {
        self::iniciar();

        return isset($_SESSION['usuario']['id']);
    }

    /*
       🔹 Nivel 3: Obtener los datos del usuario actual.
    */
    public static function usuario(): ?array
    {
        self::iniciar();

        return $_SESSION['usuario'] ?? null;
    }

    /*
       🔹 Nivel auxiliar: exigir POST para las acciones mutables.

       Las consultas pueden continuar por GET.
       Las acciones incluidas en $post solo se ejecutan por POST.
    */
    public static function metod(string $acci, array $post): void
    {
        if (
            !in_array($acci, $post, true)
            || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
        ) {
            return;
        }

        header('Allow: POST');
        http_response_code(405);
        echo json_encode(
            [
                'ok' => false,
                'mensaje' => 'La operación requiere una solicitud POST.',
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    /*
       🔹 Nivel Final: Cerrar la sesión.
    */
    public static function salir(): void
    {
        self::iniciarSinComprobar();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametros['path'],
                $parametros['domain'],
                (bool) $parametros['secure'],
                (bool) $parametros['httponly']
            );
        }

        session_destroy();
    }

    /*
       🔹 Nivel auxiliar:
       Iniciar la sesión sin comprobar inactividad.
    */
    private static function iniciarSinComprobar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }
}
