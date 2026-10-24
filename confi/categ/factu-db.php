<?php
/* ============================================================
   Ruta: confi/categ/factu-db.php
   Módulo: Configuración / Base de Facturación

   FUNCIÓN:
   Entregar la configuración exclusiva de la base factu.
   La conexión PDO se crea en contr/categ/factu-db.php.

   CONEXIÓN:
   Reutiliza las credenciales comunes DB_LOGIN_* del servidor
   y selecciona únicamente la base de datos correspondiente.
============================================================ */

declare(strict_types=1);

$host = getenv('DB_LOGIN_HOST') ?: '127.0.0.1';
$port = getenv('DB_LOGIN_PORT') ?: '3306';
$name = 'factu';
$user = getenv('DB_LOGIN_USER') ?: 'root';
$pass = getenv('DB_LOGIN_PASS') ?: '';
$charset = 'utf8mb4';
$cert = '/etc/ssl/certs/ca-certificates.crt';

return compact('host', 'port', 'name', 'user', 'pass', 'charset', 'cert');
