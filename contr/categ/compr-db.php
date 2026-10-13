<?php
/* ============================================================
   Ruta: contr/categ/compr-db.php
   Módulo: Núcleo / Base de Compras

   FUNCIÓN:
   Crear y reutilizar una conexión PDO exclusiva para la base
   de datos compr.

   CICLO:
   controlador → COMPRDB::abrir() → MySQL/MariaDB → repositorio.

   DEPENDENCIAS:
   Las referencias de Catálogo e Inventario se consultan mediante
   sus propias conexiones PDO; no se usan tablas calificadas entre
   bases.
============================================================ */

declare(strict_types=1);

final class COMPRDB
{
    /* --------------------------------------------------------
       CONEXIÓN ÚNICA
       --------------------------------------------------------
       Se reutiliza la misma conexión durante la petición PHP.
    -------------------------------------------------------- */
    private static ?PDO $conexion = null;

    public static function abrir(): PDO
    {
        /* Si ya existe una conexión PDO válida, se reutiliza. */
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        /* Cargar la configuración desde confi/categ/compr-db.php. */
        $config = require dirname(__DIR__, 2) . '/confi/categ/compr-db.php';

        /* Construir el DSN de MySQL. */
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        /* Crear PDO con las mismas reglas de las demás conexiones. */
        self::$conexion = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$conexion;
    }
}
