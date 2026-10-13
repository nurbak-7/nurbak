<?php
/* ============================================================
   Ruta: vista/dash/dash-layo.php
   Función: Coordinar el layout del dashboard.

   Este archivo es el punto principal del dashboard.
   No contiene toda la cabecera, el contenido ni el pie.
   Solo llama cada parte en su orden.

   Única responsabilidad de este archivo:
   Preparar el docum HTML del dashboard y llamar a body.php.

   Jerarquía del LAYO:

   dash-layo.php
          ↓
      body.php
      ├── head.php
      ├── modal.php
      └── foot.php

   🔹 Nivel 1: Obtener el usuario
   🔹 Nivel 2: Preparar el docum HTML
   🔹 Nivel 3: Cargar body.php
   🔹 Nivel Final: Cerrar el docum
============================================================ */

$usuarioActual = CAuth::usuario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio | Sistema de Facturación</title>
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/layo.css">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/respo.css" data-respo-css="global">
</head>
<body class="acce-panel">
    <?php require __DIR__ . '/layo/body.php'; ?>
    <script src="<?= ASSET_URL ?>/js/respo.js" defer></script>
    <script src="<?= ASSET_URL ?>/js/layo.js" defer></script>
</body>
</html>
