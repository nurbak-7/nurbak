<?php
/* ============================================================
   Ruta: contr/categ/inven-db.php
   Módulo: Núcleo / Base Inventario

   FUNCIÓN:
   Crear y reutilizar la conexión PDO con la base INVEN.

   CICLO:
   repositorio → INVENDB::abrir() → MySQL/MariaDB → consulta.

   CONEXIONES:
   - confi/categ/inven-db.php → configuración de INVEN.
   - CATALDB mantiene la conexión independiente de Catálogo.
============================================================ */

declare(strict_types=1);

final class INVENDB
{
    private static ?PDO $conexion = null;

    public static function abrir(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $config = require dirname(__DIR__, 2) . '/confi/categ/inven-db.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        self::$conexion = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$conexion;
    }
}
