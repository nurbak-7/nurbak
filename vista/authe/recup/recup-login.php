<?php
/* ============================================================
   Ruta: vista/authe/recup/recup-login.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   vista/authe/recup-logi.php — Solicitud de recuperación
   ------------------------------------------------------------
   🔹 Nivel 1: Mostrar formulario de recuperación
   🔹 Nivel 2: Presentar mensajes
   🔹 Nivel Final: No contiene lógica de acceso
============================================================ */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar acceso | Sistema de Facturación</title>
    <link rel="stylesheet" href="<?= htmlspecialchars((string) ASSET_URL, ENT_QUOTES, 'UTF-8') ?>/css/dash/login/acces.css">
    <link rel="stylesheet" href="<?= htmlspecialchars((string) ASSET_URL, ENT_QUOTES, 'UTF-8') ?>/css/respo.css" data-respo-css="global">
</head>
<body class="acce-login">
    <main class="acce-card">
        <h1>Recuperar acceso</h1>
        <p class="acce-subtitle">Solicita la recuperación de tu cuenta</p>

        <?php if (!empty($error)): ?>
            <p class="acce-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!empty($mensaje)): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" action="" class="acce-form">
            <label for="usuario">Usuario</label>
            <input id="usuario" name="usuario" type="text" autocomplete="username" required autofocus>

            <button type="submit">Solicitar recuperación</button>
        </form>

        <p>
            <a href="/">Volver al acceso</a>
        </p>
    </main>
</body>
</html>
