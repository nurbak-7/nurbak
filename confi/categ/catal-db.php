<?php
declare(strict_types=1);

/* ============================================================
   Ruta: confi/categ/catal-db.php
   Módulo: Configuración / base de CATAL

   FUNCIÓN:
   Entregar la configuración de conexión de CATAL.

   CONEXIÓN:
   Reutilizar las credenciales de TiDB ya existentes en Render.
   La base seleccionada por este módulo es catal.
   LOGIN conserva su conector y su base independientes.
============================================================ */

$host = getenv('DB_LOGIN_HOST') ?: '127.0.0.1';
$port = getenv('DB_LOGIN_PORT') ?: '3306';
$name = 'catal';
$user = getenv('DB_LOGIN_USER') ?: 'root';
$pass = getenv('DB_LOGIN_PASS') ?: '';
$charset = 'utf8mb4';
$cert = '/etc/ssl/certs/ca-certificates.crt';

return compact(
    'host',
    'port',
    'name',
    'user',
    'pass',
    'charset',
    'cert'
);