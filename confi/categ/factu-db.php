<?php
/* ============================================================
   Ruta: confi/categ/factu-db.php
   Función: Archivo del sistema.
============================================================ */

declare(strict_types=1);
/* Configuración exclusiva de la base de datos de Facturación. */
$host = getenv('DB_FACTU_HOST') ?: (getenv('DB_APP_HOST') ?: '127.0.0.1');
$port = getenv('DB_FACTU_PORT') ?: (getenv('DB_APP_PORT') ?: '3306');
$name = getenv('DB_FACTU_NAME') ?: 'FACTU';
$user = getenv('DB_FACTU_USER') ?: (getenv('DB_APP_USER') ?: 'root');
$pass = getenv('DB_FACTU_PASS') ?: (getenv('DB_APP_PASS') ?: '');
$charset = 'utf8mb4';
return compact('host','port','name','user','pass','charset');
