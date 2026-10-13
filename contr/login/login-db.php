<?php
/* ============================================================
   Ruta: contr/login/login-db.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   contr/login/login-db.php — Conexión PDO central de LOGI
   ------------------------------------------------------------
   🔹 Nivel 1: Cargar la configuración de base de datos
   🔹 Nivel 2: Crear una conexión MySQL/MariaDB para la base `logi`
   🔹 Nivel 3: Reutilizar la conexión dentro de la solicitud
   🔹 Nivel Final: Todos los módulos LOGI reutilizan esta conexión
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

        // 🔹 Nivel 4: Abrir PDO con errores visibles y consultas preparadas.
        self::$conexion = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // 🔹 Nivel Final: Entregar la conexión a los repositorios.
        return self::$conexion;
    }
}

