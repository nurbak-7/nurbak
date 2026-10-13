<?php
/* ============================================================
   Ruta: vista/authe/recup/recup-clave.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   vista/authe/recup-clav.php — Nueva contraseña
   ------------------------------------------------------------
   🔹 Nivel 1: Mostrar formulario de nueva contraseña
   🔹 Nivel 2: Presentar mensajes
   🔹 Nivel Final: No contiene SQL ni lógica de seguridad
============================================================ */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña | Sistema de Facturación</title>
    <link rel="stylesheet" href="<?= htmlspecialchars((string) ASSET_URL, ENT_QUOTES, 'UTF-8') ?>/css/dash/login/acces.css">
    <link rel="stylesheet" href="<?= htmlspecialchars((string) ASSET_URL, ENT_QUOTES, 'UTF-8') ?>/css/respo.css" data-respo-css="global">
</head>
<body class="acce-login">
    <main class="acce-card">
        <h1>Nueva contraseña</h1>
        <p class="acce-subtitle">Establece una nueva contraseña para tu cuenta</p>

        <?php if (!empty($error)): ?>
            <p class="acce-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!empty($mensaje)): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (empty($mensaje)): ?>
            <form method="post" action="" class="acce-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <label for="clave">Nueva contraseña</label>
                <input id="clave" name="clave" type="password" minlength="8" autocomplete="new-password" required autofocus>

                <label for="confirmacion">Confirmar contraseña</label>
                <input id="confirmacion" name="confirmacion" type="password" minlength="8" autocomplete="new-password" required>

                <button type="submit">Cambiar contraseña</button>
            </form>
        <?php endif; ?>

        <p>
            <a href="/">Volver al acceso</a>
        </p>
    </main>
</body>
</html>
