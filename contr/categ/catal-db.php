<?php
/* ============================================================
   Ruta: contr/categ/catal-db.php
   Módulo: Núcleo / base de CATAL

   FUNCIÓN:
   Crear y reutilizar la conexión PDO con la base de CATAL.

   CICLO:
   repositorio → CATALDB::abrir() → MySQL/TiDB → consulta.

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

        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        /* ========================================================
           NIVEL — TLS PARA HOSTS REMOTOS
           Mantener la conexión local sin opciones TLS adicionales.
        ======================================================== */

        if (!in_array(strtolower($config['host']), ['127.0.0.1', 'localhost', '::1'], true)) {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $config['cert'];
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        self::$conexion = new PDO($dsn, $config['user'], $config['pass'], $opciones);

        return self::$conexion;
    }
}