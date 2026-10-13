<?php
/* ============================================================
   Ruta: vista/authe/acces/acces-login.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   vista/authe/acces/acces-login.php — Vista de acceso
   ------------------------------------------------------------
   🔹 Nivel 1: Mostrar el formulario de ingreso
   🔹 Nivel 2: Presentar mensajes de validación
   🔹 Nivel Final: Enlazar CSS y JavaScript externos
============================================================ */
?>
<!-- 🔹 Nivel 1: Definir el docum y enlazar los estilos externos. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Proyect Nurb</title>
    <link rel="stylesheet" href="<?= htmlspecialchars((string) ASSET_URL, ENT_QUOTES, 'UTF-8') ?>/css/dash/login/acces.css">
    <link rel="stylesheet" href="<?= htmlspecialchars((string) ASSET_URL, ENT_QUOTES, 'UTF-8') ?>/css/respo.css" data-respo-css="global">
</head>
<body class="acce-login acce-login-inicio">
    <!-- 🔹 Nivel 2: Mostrar la tarjeta principal de acceso. -->
    <main class="acce-card">
        <h1>PROYECT NURB</h1>
        <p class="acce-subtitle">Ingresa tus datos para continuar</p>

        <!-- 🔹 Nivel 3: Mostrar un error si el controlador lo envía. -->
        <?php if (!empty($error)): ?>
            <p class="acce-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <!-- 🔹 Nivel 4: Enviar usuario y contraseña al controlador. -->
        <form method="post" action="" class="acce-form">
            <input id="usuario" name="usuario" type="text" placeholder="Usuario" aria-label="Usuario" autocomplete="username" required autofocus>
            <input id="clave" name="clave" type="password" placeholder="Contraseña" aria-label="Contraseña" autocomplete="current-password" required minlength="4" maxlength="15">
            <button type="submit">Ingresar</button>
        </form>

        <p>
            <a href="publi/publi-recup.php">¿Olvidaste tu contraseña?</a>
        </p>
    </main>

    <!-- 🔹 Nivel Final: Cargar el JavaScript externo del acceso. -->
    <script src="<?= htmlspecialchars((string) ASSET_URL, ENT_QUOTES, 'UTF-8') ?>/js/dash/logi/acces.js" defer></script>
</body>
</html>
