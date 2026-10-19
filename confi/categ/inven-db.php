<?php
/* ============================================================
   Ruta: confi/categ/inven-db.php
   Módulo: Configuración / Base Inventario

   FUNCIÓN:
   Definir la conexión exclusiva de la base de datos INVEN.

   IMPORTANTE:
   - CATAL continúa siendo la base de CATAL.
   - INVEN es independiente.
   - LOGI continúa reservado para autenticación.
============================================================ */

declare(strict_types=1);

$host = getenv('DB_LOGIN_HOST') ?: '127.0.0.1';
$port = getenv('DB_LOGIN_PORT') ?: '3306';
$name = 'INVEN';
$user = getenv('DB_LOGIN_USER') ?: 'root';
$pass = getenv('DB_LOGIN_PASS') ?: '';
$charset = 'utf8mb4';
$cert = '/etc/ssl/certs/ca-certificates.crt';

return compact('host', 'port', 'name', 'user', 'pass', 'charset', 'cert');