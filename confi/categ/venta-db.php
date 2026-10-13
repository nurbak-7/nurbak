<?php
/* ============================================================
   Ruta: confi/categ/venta-db.php
   Módulo: Configuración / Base de Ventas

   FUNCIÓN:
   Entregar exclusivamente la configuración de conexión de la
   base de datos "VENTA".

   IMPORTANTE:
   - VENTA es una base independiente.
   - Las referencias a CATAL e INVE son referencias lógicas.
   - Este archivo NO abre PDO.
   - La conexión PDO se crea en contr/categ/venta-db.php.
============================================================ */

declare(strict_types=1);

$host = getenv('DB_VENTA_HOST') ?: (getenv('DB_APP_HOST') ?: '127.0.0.1');
$port = getenv('DB_VENTA_PORT') ?: (getenv('DB_APP_PORT') ?: '3306');
$name = getenv('DB_VENTA_NAME') ?: 'VENTA';
$user = getenv('DB_VENTA_USER') ?: (getenv('DB_APP_USER') ?: 'root');
$pass = getenv('DB_VENTA_PASS') ?: (getenv('DB_APP_PASS') ?: '');
$charset = 'utf8mb4';

return compact('host', 'port', 'name', 'user', 'pass', 'charset');
