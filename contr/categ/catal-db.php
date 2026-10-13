<?php
/* ============================================================
   Ruta: contr/categ/catal-db.php
   Módulo: Núcleo / base de CATAL

   FUNCIÓN:
   Crear y reutilizar la conexión PDO con la base de CATAL.

   CICLO:
   repositorio → CATALDB::abrir() → MySQL/MariaDB → consulta.

   CONEXIÓN:
   confi/categ/catal-db.php → configuración de la base de CATAL.
   Los repositorios de Catálogo y demás módulos utilizarán CATALDB.

   IMPORTANTE:
   LOGINDB continúa reservado para logi/logi.
============================================================ */

declare(strict_types=1);

final class CATALDB
{
    private static ?PDO $conexion = null;

    public static function abrir(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $config = require dirname(__DIR__, 2) . '/confi/categ/catal-db.php';

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
