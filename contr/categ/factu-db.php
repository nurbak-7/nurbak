<?php
/* ============================================================
   Ruta: contr/categ/factu-db.php
   Módulo: Núcleo / Base de Facturación

   FUNCIÓN:
   Crear y reutilizar una conexión PDO exclusiva para factu.

   FLUJO:
   controlador → FACTUDB::abrir() → MySQL/TiDB → repositorio.
============================================================ */

declare(strict_types=1);

final class FACTUDB
{
    private static ?PDO $conexion = null;

    public static function abrir(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $config = require dirname(__DIR__, 2) . '/confi/categ/factu-db.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        /* Verificar TLS para servidores remotos; local no requiere TLS. */
        if (!in_array(strtolower($config['host']), ['127.0.0.1', 'localhost', '::1'], true)) {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $config['cert'];
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        self::$conexion = new PDO($dsn, $config['user'], $config['pass'], $opciones);

        return self::$conexion;
    }
}
