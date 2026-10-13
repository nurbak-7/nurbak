<?php
/* ============================================================
   Ruta: modul/login/acces/acces-contr.php
   Función: Controlar el proceso completo del logi.

   Este archivo recibe la solicitud del navegador y decide qué
   debe ocurrir en cada momento.

   Archivos que carga:

   - contr/login/login-db.php
     Prepara la conexión PDO con la base de datos.

   - contr/login/contr-auth.php
     Inicia, guarda, comprueba y cierra la sesión.


   - acces-repos.php
     Busca el usuario en la tabla usuar.

   - acces-servi.php
     Comprueba la contraseña.

   Archivos que este controlador puede mostrar:

   - vista/authe/acces/acces-login.php
     Formulario de ingreso.

   - vista/dash/dash-layo.php
     Panel después del ingreso correcto.

   Secuencia:

   1. Iniciar la sesión.
   2. Revisar si el usuario quiere salir.
   3. Revisar si llegó el formulario.
   4. Enviar los datos al servicio.
   5. Crear la sesión si los datos son correctos.
   6. Mostrar el login o el panel.

   🔹 Nivel 1: Cargar los archivos necesarios
   🔹 Nivel 2: Iniciar la sesión
   🔹 Nivel 3: Recibir el formulario
   🔹 Nivel 4: Validar el usuario
   🔹 Nivel 5: Guardar la sesión
   🔹 Nivel Final: Mostrar la vista correspondiente
============================================================ */

declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../contr/error.php';

/* 🔹 Nivel 1: Cargar la conexión con LOGI. */
require_once dirname(__DIR__, 3) . '/contr/login/login-db.php';

/* 🔹 Nivel 1: Cargar el administrador de sesiones. */
require_once dirname(__DIR__, 3) . '/contr/login/contr-auth.php';

/* 🔹 Nivel 1: Cargar el registro de depuración. */

/*
   🔹 Nivel 1: Cargar el repositorio y el servicio del acceso.

   acces-repos.php → consulta la tabla usuar.
   acces-servi.php → valida los datos y la contraseña.
*/
require_once __DIR__ . '/acces-repos.php';
require_once __DIR__ . '/acces-servi.php';

/*
   Clase AcceCont:
   Controla el flujo principal del ingreso y la sesión.
*/
final class AccesCont
{
    /*
       🔹 Nivel 2: Iniciar o recuperar la sesión.

       También atiende la solicitud de cierre de sesión.
    */
    public function mostrar(): void
    {
        // Actividad real informada por la interfaz del panel.
        // Esta petición es la única que actualiza la marca de actividad.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === '1') {
            CAuth::iniciar(true);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'ok' => CAuth::autenticado(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        CAuth::iniciar();

        /* 🔹 Nivel 2: Cerrar sesión si llegó ?salir=1. */
        if (isset($_GET['salir'])) {
            

            CAuth::salir();

            header('Location: ./');
            exit;
        }

        /* Mensaje que recibirá la vista cuando el acceso sea rechazado. */
        $error = null;

        /*
           🔹 Nivel 3: Procesar el formulario únicamente cuando
           la solicitud utiliza POST.

           El formulario envía:
           $_POST['usuario']
           $_POST['clave']
        */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $clave = (string) ($_POST['clave'] ?? '');

            /* Nunca se registra la contraseña. */
            

            try {
                /*
                   🔹 Nivel 4: Construir el servicio de autenticación.

                   LOGINDB abre la conexión PDO.
                   AccesRepo consulta usuar.
                   AccesServ valida la contraseña.
                */
                $servicio = new AccesServ(
                    new AccesRepo(LOGINDB::abrir())
                );

                $registro = $servicio->ingresar($usuario, $clave);

                

                /*
                   🔹 Nivel 5: Crear la sesión únicamente si
                   el servicio devolvió un usuario válido.
                */
                if ($registro !== null) {
                    CAuth::entrar($registro);

                    

                    header('Location: ./');
                    exit;
                }

                /* Acceso rechazado: no se crea ninguna sesión. */
                

                $error = 'Usuario o contraseña incorrectos.';
            } catch (Throwable $exception) {
                /*
                   Registrar el error técnico completo para depuración,
                   pero mostrar un mensaje sencillo al usuario.
                */
                

                $error = errMsg($exception);
            }
        }

        /*
           🔹 Nivel Final: Mostrar el panel si existe una sesión válida.
        */
        if (CAuth::autenticado()) {
            require dirname(__DIR__, 3) . '/vista/dash/dash-layo.php';
            return;
        }

        /*
           Si no existe una sesión, mostrar el formulario de logi.
           $error contiene el resultado del intento anterior.
        */
        require dirname(__DIR__, 3) . '/vista/authe/acces/acces-login.php';
    }
}

/* 🔹 Nivel Final: Fin del controlador. */