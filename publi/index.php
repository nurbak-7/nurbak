<?php
/* ============================================================
   Ruta: publi/index.php
   Función: Punto de entrada público del sistema.

   Este archivo recibe la solicitud después de:

   index.php

   Su función es preparar la respuesta, indicar dónde están los
   recursos visuales, cargar el controlador y ejecutar el logi.

   Secuencia:

   1. Preparar la respuesta HTML.
   2. Definir la carpeta de CSS y JavaScript.
   3. Cargar el controlador del acceso.
   4. Ejecutar el controlador.

   🔹 Nivel 1: Preparar la respuesta HTML
   🔹 Nivel 2: Definir la carpeta de recursos
   🔹 Nivel 3: Cargar el controlador
   🔹 Nivel Final: Ejecutar el controlador
============================================================ */

/*
   🔹 Nivel 1: Preparar la respuesta HTML.

   header informa al navegador que PHP enviará una página HTML
   usando la codificación UTF-8.

   Esto permite mostrar correctamente caracteres como:

   á, é, í, ó, ú y ñ
*/
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');


/*
   🔹 Nivel 2: Definir la carpeta de recursos.

   El CSS y el JavaScript están guardados en:

   publi/asset/

   El sistema se está abriendo desde la raíz:

   http://localhost/

   Por eso el navegador debe buscar los recursos empezando por:

   publi/asset/

   Las vistas utilizarán esta constante para cargar:

   publi/asset/css/dash/login/acces.css
   publi/asset/js/dash/logi/acces.js
*/
define('ASSET_URL', 'publi/asset');

/*
   🔹 Nivel 3: Cargar el controlador del acceso.

   __DIR__ representa la carpeta actual:

   publi/

   ../ significa subir una carpeta:

   

   Después se agrega la ruta del controlador:

   modul/login/acces/acces-contr.php

   require_once carga ese archivo una sola vez.
*/
require_once __DIR__ . '/../modul/login/acces/acces-contr.php';

/*
   🔹 Nivel Final: Ejecutar el controlador.

   new AccesCont() crea un objeto de la clase AcceCont.

   ->mostrar() ejecuta el método mostrar().

   Ese método decide qué mostrar:

   vista/authe/acce-logi.php
   o
   vista/dash/dash-inic.php
*/
(new AccesCont())->mostrar();
