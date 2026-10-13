<?php
/* ============================================================
   Ruta: modul/login/recup/recup-contr.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   modul/login/recup/recup-cont.php — Controlador de recuperación
   ------------------------------------------------------------
   🔹 Coordinar la solicitud de recuperación
   🔹 Coordinar la validación del token
   🔹 Coordinar el cambio de contraseña
   🔹 No contiene SQL
   🔹 No administra sesiones de usuario autenticado
============================================================ */

declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../contr/error.php';

require_once dirname(__DIR__, 3) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 3) . '/confi/recup/recup-gmail.php';
require_once __DIR__ . '/recup-repos.php';
require_once __DIR__ . '/recup-servi.php';

final class RecuCont
{
    public function mostrar(): void
    {
        $token = trim(
            (string) ($_GET['token'] ?? $_POST['token'] ?? '')
        );

        $error = null;
        $mensaje = null;

        try {

            $servicio = new RecuServ(
                new RecuRepo(LOGINDB::abrir()),
                new RecuMail()
            );

            /*
               ==================================================
               SOLICITUD DE RECUPERACIÓN
               ==================================================
            */

            if (
                $_SERVER['REQUEST_METHOD'] === 'POST'
                && $token === ''
                && isset($_POST['usuario'])
            ) {

                $usuario = trim(
                    (string) $_POST['usuario']
                );

                $resultado = $servicio->solicitar($usuario);

                if ($resultado !== null) {

                    $mensaje = 'Solicitud de recuperación procesada.';

                } else {

                    $error = 'No se pudo procesar la solicitud de recuperación.';
                }
            }

            /*
               ==================================================
               CAMBIO DE CONTRASEÑA
               ==================================================
            */

            elseif (
                $_SERVER['REQUEST_METHOD'] === 'POST'
                && $token !== ''
            ) {

                $clave = (string) (
                    $_POST['clave'] ?? ''
                );

                $confirmacion = (string) (
                    $_POST['confirmacion'] ?? ''
                );

                if (
                    $servicio->cambiarClave(
                        $token,
                        $clave,
                        $confirmacion
                    )
                ) {

                    $mensaje = 'Contraseña actualizada correctamente.';

                } else {

                    $error = 'El enlace no es válido o la contraseña no cumple los requisitos.';
                }
            }

            /*
               ==================================================
               VALIDACIÓN DEL TOKEN
               ==================================================
            */

            elseif ($token !== '') {

                if (
                    $servicio->validarToken($token) === null
                ) {

                    $error = 'El enlace no es válido o ya venció.';
                }
            }

        } catch (Throwable $exception) {

            $error = errMsg($exception);
        }

        /*
           =====================================================
           SELECCIÓN DE VISTA
           =====================================================
        */

        if ($token !== '') {

            require dirname(__DIR__, 3)
                . '/vista/authe/recup/recup-clave.php';

            return;
        }

        require dirname(__DIR__, 3)
            . '/vista/authe/recup/recup-login.php';
    }
}