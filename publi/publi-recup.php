<?php
/* ============================================================
   Ruta: publi/publi-recup.php
   Función: Punto de entrada público exclusivo de recuperación.

   🔹 Nivel 1: Preparar la respuesta HTML
   🔹 Nivel 2: Definir los recursos públicos
   🔹 Nivel 3: Cargar el controlador de recuperación
   🔹 Nivel Final: Ejecutar el controlador
============================================================ */

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

define('ASSET_URL', 'publi/asset');

require_once __DIR__ . '/../modul/login/recup/recup-contr.php';

(new RecuCont())->mostrar();
