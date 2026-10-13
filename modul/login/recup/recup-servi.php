<?php
/* ============================================================
   Ruta: modul/login/recu/recup-servi.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   modul/login/recup/recup-serv.php — Servicio de recuperación
   ------------------------------------------------------------
   🔹 Validar solicitud
   🔹 Generar token temporal
   🔹 Preparar recuperación
   🔹 Validar token
   🔹 Cambiar contraseña
   🔹 No mostrar HTML ni ejecutar SQL directamente
============================================================ */

declare(strict_types=1);

final class RecuServ
{
    private const LONGITUD_MIN_CLAVE = 4;
    private const LONGITUD_MAX_CLAVE = 15;

    public function __construct(
        private RecuRepo $repo,
        private RecuMail $mail
    ) {
    }

    /*
       🔹 Solicitar recuperación.

       Solo puede continuar un usuario autorizado.
       El token real se devuelve únicamente para preparar
       el enlace de recuperación.
    */
    public function solicitar(string $usuario): ?array
    {
        $usuario = trim($usuario);

        if ($usuario === '') {
            return null;
        }

        $registro = $this->repo->usuarioAutorizado($usuario);

        if ($registro === null) {
            return null;
        }

        /*
           La cuenta debe tener un correo registrado.
        */
        if (empty($registro['corre'])) {
            return null;
        }

        /*
           Generar token criptográficamente seguro.
           El token real NO se guarda en la base de datos.
        */
        $token = bin2hex(random_bytes(32));

        /*
           Guardar únicamente el hash del token.
        */
        $hash = hash('sha256', $token);

        $this->repo->crearToken(
            (int) $registro['id'],
            $hash
        );

        if (!$this->mail->enviar(
            (string) $registro['corre'],
            (string) $registro['logi'],
            $token
        )) {
            return null;
        }

        return [
            'correo' => $registro['corre'],
            'logi' => $registro['logi'],
        ];
    }

    /*
       🔹 Validar token.

       El repositorio comprueba:
       - existencia;
       - que no haya sido usado;
       - que no esté vencido.

       El vencimiento lo comprueba MySQL.
    */
    public function validarToken(string $token): ?array
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $hash = hash('sha256', $token);

        return $this->repo->tokenVigente($hash);
    }

    /*
       🔹 Cambiar contraseña.

       Solo se permite mediante un token vigente.
    */
    public function cambiarClave(
        string $token,
        string $clave,
        string $confirmacion
    ): bool {
        if ($token === '' || $clave === '' || $confirmacion === '') {
            return false;
        }

        /*
           La nueva contraseña debe tener entre
           4 y 15 caracteres.
        */
        $longitud = strlen($clave);

        if (
            $longitud < self::LONGITUD_MIN_CLAVE ||
            $longitud > self::LONGITUD_MAX_CLAVE
        ) {
            return false;
        }

        /*
           Confirmar que ambas contraseñas sean iguales.
        */
        if (!hash_equals($clave, $confirmacion)) {
            return false;
        }

        /*
           Volver a validar el token antes de modificar
           la contraseña.
        */
        $registro = $this->validarToken($token);

        if ($registro === null) {
            return false;
        }

        /*
           Generar el hash seguro de la nueva contraseña.
        */
        $hashClave = password_hash(
            $clave,
            PASSWORD_DEFAULT
        );

        /*
           Actualizar contraseña y reiniciar seguridad.
        */
        $this->repo->actualizarClave(
            (int) $registro['usuar'],
            $hashClave
        );

        /*
           El token queda inutilizable después de utilizarse.
        */
        $this->repo->marcarUsado(
            (int) $registro['id']
        );

        return true;
    }
}