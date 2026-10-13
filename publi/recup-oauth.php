<?php
/* ============================================================
   Ruta: publi/recup-oauth.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   pub/recup-oauth.php — Autorización OAuth de Gmail
   ------------------------------------------------------------
   🔹 Punto de entrada exclusivo para autorizar Gmail
   🔹 Primera visita: redirige a Google
   🔹 Retorno de Google: guarda el refresh token
   🔹 No modifica la recuperación ni la tabla recup
============================================================ */

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../confi/recup/recup-oauth.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$error = null;
$mensaje = null;

if (isset($_GET['error'])) {
    $error = 'Google canceló o rechazó la autorización.';
} elseif (isset($_GET['code'])) {
    $state = (string) ($_GET['state'] ?? '');
    $guardado = (string) ($_SESSION['recu_oauth_state'] ?? '');

    if ($state === '' || $guardado === '' || !hash_equals($guardado, $state)) {
        $error = 'La autorización no es válida.';
    } elseif (RecuOAuth::intercambiarCodigo((string) $_GET['code'])) {
        unset($_SESSION['recu_oauth_state']);
        $mensaje = 'Gmail quedó autorizado correctamente para enviar los correos de recuperación.';
    } else {
        $error = 'No se pudo guardar la autorización de Gmail.';
    }
} else {
    $state = bin2hex(random_bytes(32));
    $_SESSION['recu_oauth_state'] = $state;

    header('Location: ' . RecuOAuth::autorizacionUrl($state));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorización Gmail | Sistema de Facturación</title>
    <link rel="stylesheet" href="publi/asset/css/dash/login/acces.css">
</head>
<body class="acce-login">
    <main class="acce-card">
        <h1>Autorización Gmail</h1>

        <?php if ($mensaje !== null): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
            <p><a href="./">Volver al acceso</a></p>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <p class="acce-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <p><a href="recu-oauth.php">Intentar nuevamente</a></p>
        <?php endif; ?>
    </main>
</body>
</html>
