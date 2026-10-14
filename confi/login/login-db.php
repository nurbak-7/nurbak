<?php
/* ============================================================
   Ruta: confi/login/login-db.php
   Función: Configuración de conexión a la base de datos LOGIN.
============================================================ */

/* ============================================================
   CONFIGURACIÓN — LOGIN
   ------------------------------------------------------------
   NIVEL 1: Leer variables del entorno.
   NIVEL 2: Definir la conexión a LOGIN.
   NIVEL 3: Definir la autoridad de certificación TLS.
   NIVEL FINAL: Entregar la configuración sin credenciales fijas.
============================================================ */

declare(strict_types=1);

/* ============================================================
   NIVEL 1 — VARIABLES DEL ENTORNO
============================================================ */

$host = getenv('DB_LOGIN_HOST') ?: '127.0.0.1';
$port = getenv('DB_LOGIN_PORT') ?: '3306';
$name = getenv('DB_LOGIN_NAME') ?: 'login';
$user = getenv('DB_LOGIN_USER') ?: 'root';
$pass = getenv('DB_LOGIN_PASS') ?: '';

/* ============================================================
   NIVEL 2 — PARÁMETROS DE CONEXIÓN
============================================================ */

$charset = 'utf8mb4';

/* ============================================================
   NIVEL 3 — CERTIFICADO TLS
============================================================ */

$cert = '/etc/ssl/certs/ca-certificates.crt';

/* ============================================================
   NIVEL FINAL — ENTREGAR CONFIGURACIÓN
============================================================ */

return compact('host', 'port', 'name', 'user', 'pass', 'charset', 'cert');