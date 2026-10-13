<?php
/* ============================================================
   Ruta: confi/categ/compr-db.php
   Módulo: Configuración / Base de Compras

   FUNCIÓN:
   Definir exclusivamente los datos de conexión de la base
   de datos de Compras: COMPR.

   IMPORTANTE:
   - CATAL = Catálogo.
   - INVE = Inventario.
   - COMPRDB = conexión de Compras.
   - LOGI = Autenticación.

   Este archivo NO crea la conexión PDO. Solo entrega la
   configuración al archivo contr/categ/compr-db.php.
============================================================ */

declare(strict_types=1);

/* ------------------------------------------------------------
   DATOS DE CONEXIÓN
   ------------------------------------------------------------
   Primero se revisan variables específicas de Compras.
   Si no existen, se usan las variables generales de la
   aplicación para mantener compatibilidad con el entorno local.
------------------------------------------------------------ */
$host = getenv('DB_COMPR_HOST') ?: (getenv('DB_APP_HOST') ?: '127.0.0.1');
$port = getenv('DB_COMPR_PORT') ?: (getenv('DB_APP_PORT') ?: '3306');
$name = getenv('DB_COMPR_NAME') ?: 'compr';
$user = getenv('DB_COMPR_USER') ?: (getenv('DB_APP_USER') ?: 'root');
$pass = getenv('DB_COMPR_PASS') ?: (getenv('DB_APP_PASS') ?: '');
$charset = 'utf8mb4';

/* ------------------------------------------------------------
   ENTREGA DE CONFIGURACIÓN
   ------------------------------------------------------------
   compact() construye el arreglo que recibirá la clase COMPRDB.
------------------------------------------------------------ */
return compact('host', 'port', 'name', 'user', 'pass', 'charset');
