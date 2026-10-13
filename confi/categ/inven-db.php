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

$host = getenv('DB_INVEN_HOST') ?: (getenv('DB_APP_HOST') ?: '127.0.0.1');
$port = getenv('DB_INVEN_PORT') ?: (getenv('DB_APP_PORT') ?: '3306');
$name = getenv('DB_INVEN_NAME') ?: 'INVEN';
$user = getenv('DB_INVEN_USER') ?: (getenv('DB_APP_USER') ?: 'root');
$pass = getenv('DB_INVEN_PASS') ?: (getenv('DB_APP_PASS') ?: '');
$charset = 'utf8mb4';

return compact('host', 'port', 'name', 'user', 'pass', 'charset');
