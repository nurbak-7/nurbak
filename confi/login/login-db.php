<?php
/* ============================================================
   Ruta: confi/login/login-db.php
   Función: Configuración de conexión a la base de datos LOGIN.
============================================================ */

/* ============================================================
   confi/login/login-db.php — Configuración de base de datos LOGIN
   ------------------------------------------------------------
   🔹 Nivel 1: Leer variables del entorno
   🔹 Nivel 2: Usar `login` como base central de seguridad
   🔹 Nivel 3: Definir MySQL/MariaDB y PDO
   🔹 Nivel Final: No guardar credenciales dentro del código
============================================================ */

declare(strict_types=1);

// 🔹 Nivel 1: Leer la configuración del entorno.
$host = getenv('DB_LOGIN_HOST') ?: '127.0.0.1';
$port = getenv('DB_LOGIN_PORT') ?: '3306';
$name = getenv('DB_LOGIN_NAME') ?: 'login';
$user = getenv('DB_LOGIN_USER') ?: 'root';
$pass = getenv('DB_LOGIN_PASS') ?: '';

// 🔹 Nivel 2: Definir los parámetros de MySQL/MariaDB.
$charset = 'utf8mb4';

// 🔹 Nivel Final: Entregar la configuración sin guardar credenciales fijas.
return compact('host', 'port', 'name', 'user', 'pass', 'charset');