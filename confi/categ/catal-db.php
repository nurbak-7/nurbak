<?php
/* ============================================================
   Ruta: confi/categ/catal-db.php
   Módulo: Configuración / base de CATAL

   FUNCIÓN:
   Definir la conexión de la base de CATAL del sistema.

   CICLO:
   catal-db.php → contr/categ/catal-db.php → repositorios de los módulos.

   CONEXIÓN:
   La base de CATAL es independiente de LOGI.
   LOGI se mantiene exclusivamente para autenticación.

   CONFIGURACIÓN:
   Usa variables de entorno. En local, por defecto:
   base = CATAL | usuario = root | clave = vacía.
============================================================ */

declare(strict_types=1);

$host = getenv('DB_APP_HOST') ?: '127.0.0.1';
$port = getenv('DB_APP_PORT') ?: '3306';
$name = getenv('DB_CATAL_NAME') ?: 'catal';
$user = getenv('DB_APP_USER') ?: 'root';
$pass = getenv('DB_APP_PASS') ?: '';
$charset = 'utf8mb4';

return compact('host', 'port', 'name', 'user', 'pass', 'charset');
