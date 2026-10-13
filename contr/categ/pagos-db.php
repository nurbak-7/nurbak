<?php
/* ============================================================
   Ruta: contr/categ/pagos-db.php
   Módulo: Núcleo / Base de Pagos

   FUNCIÓN:
   Crear y reutilizar una conexión PDO exclusiva para la BD pago.

   FLUJO:
   controlador → PAGOSDB::abrir() → MySQL → repositorio.
============================================================ */
declare(strict_types=1);

final class PAGOSDB
{
    private static ?PDO $conexion = null;

    public static function abrir(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $config = require dirname(__DIR__, 2) . '/confi/categ/pagos-db.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'], $config['port'], $config['name'], $config['charset']
        );

        self::$conexion = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$conexion;
    }
}
