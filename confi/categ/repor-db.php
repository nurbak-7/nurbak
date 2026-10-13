<?php
/* ============================================================
   Ruta: confi/categ/repor-db.php
   Módulo: Configuración / Base de Reportes

   FUNCIÓN:
   Entregar la configuración exclusiva de la base "REPO".
   Reportes/Informes consulta las bases operativas; Auditoría
   utiliza REPO para conservar su historial.
============================================================ */
declare(strict_types=1);

$host = getenv('DB_REPOR_HOST') ?: (getenv('DB_APP_HOST') ?: '127.0.0.1');
$port = getenv('DB_REPOR_PORT') ?: (getenv('DB_APP_PORT') ?: '3306');
$name = getenv('DB_REPOR_NAME') ?: 'REPO';
$user = getenv('DB_REPOR_USER') ?: (getenv('DB_APP_USER') ?: 'root');
$pass = getenv('DB_REPOR_PASS') ?: (getenv('DB_APP_PASS') ?: '');
$charset = 'utf8mb4';

return compact('host', 'port', 'name', 'user', 'pass', 'charset');
