<?php
include('cl/control.php');
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nurbak Automotive</title>
<link rel="icon" href="../im/ico/logo.jpg" type="image/jpg">
<link rel="stylesheet" href="../as/lp/lapnav.css">
<link rel="stylesheet" href="../as/lp/lapbod.css">
<link rel="stylesheet" href="../as/lp/lapfot.css">
<link rel="stylesheet" href="../as/mb/mobnav.css">
<link rel="stylesheet" href="../as/mb/mobini.css">
<link rel="stylesheet" href="../as/pg/debug.css">
<link rel="stylesheet" href="../as/pg/ini.css">
<link rel="stylesheet" href="../as/pg/pro.css">
<link rel="stylesheet" href="../as/pg/nos.css">
<link rel="stylesheet" href="../as/pg/cot.css">
</head>

<body>
<?php include 'navbar.php'; ?>

<main id="contenido">

<?php include($ruta); ?>

</main>

<?php include 'footer.php'; ?>
<script src="../js/nav/nav.js"></script>
<script src="../js/pg/ini.js"></script>
</body>
</html>