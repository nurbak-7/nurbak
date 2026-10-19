

<?php
/* ============================================================
   Ruta: confi/categ/catal-db.php
   Módulo: Configuración / base de CATAL

   FUNCIÓN:
   Definir los datos de conexión de la base de CATAL.

   CICLO:
   confi/categ/catal-db.php → contr/categ/catal-db.php → repositorios.

   CONEXIÓN:
   CATAL usa sus propias variables de entorno y permanece separada
   de LOGIN, que se reserva para la autenticación.

   CONFIGURACIÓN:
   En local, por defecto: base = catal, usuario = root,
   clave vacía y puerto 3306. En el servidor se usan las variables
   DB_CATAL_* y el conector PDO aplica TLS para el host remoto.
============================================================ */



$host = getenv('DB_CATAL_HOST') ?: '127.0.0.1';
$port = getenv('DB_CATAL_PORT') ?: '3306';
$name = getenv('DB_CATAL_NAME') ?: 'catal';
$user = getenv('DB_CATAL_USER') ?: 'root';
$pass = getenv('DB_CATAL_PASS') ?: '';
$charset = 'utf8mb4';
$cert = '/etc/ssl/certs/ca-certificates.crt';

return compact('host', 'port', 'name', 'user', 'pass', 'charset', 'cert');