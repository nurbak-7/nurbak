<?php
/* ============================================================
   Ruta: contr/login/login-db.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   contr/login/login-db.php — Conexión PDO central de LOGI
   ------------------------------------------------------------
   🔹 Nivel 1: Mantener una única conexión por solicitud
   🔹 Nivel 2: Reutilizar la conexión si ya existe
   🔹 Nivel 3: Cargar la configuración y construir el DSN
   🔹 Nivel 4: Definir las opciones de conexión PDO
   🔹 Nivel 5: Configurar y verificar el certificado TLS
   🔹 Nivel 6: Crear la conexión PDO
   🔹 Nivel Final: Entregar la conexión a los repositorios
============================================================ */

declare(strict_types=1);

final class LOGINDB
{
    // 🔹 Nivel 1: Guardar una única conexión durante la solicitud.
    private static ?PDO $conexion = null;

    public static function abrir(): PDO
    {
        // 🔹 Nivel 2: Reutilizar la conexión si ya fue creada.
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        // 🔹 Nivel 3: Cargar la configuración y construir el DSN.
        $config = require dirname(__DIR__, 2) . '/confi/login/login-db.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        // 🔹 Nivel 4: Definir errores, resultados y consultas preparadas.
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        /* ============================================================
           NIVEL 5 — CERTIFICADO TLS
           ------------------------------------------------------------
           🔹 Verificar el certificado en conexiones remotas.
           🔹 Mantener la conexión local sin configuración TLS adicional.
        ============================================================ */

        if (!in_array(strtolower($config['host']), ['127.0.0.1', 'localhost', '::1'], true)) {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $config['cert'];
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        // 🔹 Nivel 6: Abrir la conexión PDO.
        self::$conexion = new PDO($dsn, $config['user'], $config['pass'], $opciones);

        // 🔹 Nivel Final: Entregar la conexión a los repositorios.
        return self::$conexion;
    }
}