<?php
/* ============================================================
   Ruta: confi/categ/pagos-db.php
   Módulo: Configuración / Base de Pagos

   FUNCIÓN:
   Entregar exclusivamente la configuración de la base de datos
   "PAGO". La conexión PDO se crea en contr/categ/pagos-db.php.
============================================================ */
declare(strict_types=1);

$host = getenv('DB_PAGOS_HOST') ?: (getenv('DB_APP_HOST') ?: '127.0.0.1');
$port = getenv('DB_PAGOS_PORT') ?: (getenv('DB_APP_PORT') ?: '3306');
$name = getenv('DB_PAGOS_NAME') ?: 'PAGO';
$user = getenv('DB_PAGOS_USER') ?: (getenv('DB_APP_USER') ?: 'root');
$pass = getenv('DB_PAGOS_PASS') ?: (getenv('DB_APP_PASS') ?: '');
$charset = 'utf8mb4';

return compact('host', 'port', 'name', 'user', 'pass', 'charset');
