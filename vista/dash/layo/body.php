<?php
/* ============================================================
   Ruta: vista/dash/layo/body.php
   Función: Mostrar el contenido central del dashboard.

   Este archivo contiene las categorías y sus submenús informativos.
   Más adelante el contenido cambiará sin recargar la página.

   🔹 Nivel 1: Mostrar bienvenida
   🔹 Nivel 2: Mostrar Productos, Inventario y Compras
   🔹 Nivel 3: Mostrar Ventas y Facturación
   🔹 Nivel 4: Mostrar Pagos y Reportes
   🔹 Nivel Final: Preparar el contenido dinámico

   Este archivo coordina las tres piezas visuales del LAYO:
   - head.php → cabecera y navbar
   - vent.php → ventana universal
   - mini.php → mini-ventana reutilizable
   - foot.php → pie del sistema
============================================================ */
require __DIR__ . '/head.php';
?>

<main class="dash-body">
    <section class="dash-welcome">
        <h1>FALTA DINAMICA</h1>
        <p>Seleccione una categoría para consultar sus opciones.</p>
    </section>
</main>

<?php require __DIR__ . '/modal.php'; ?>
<?php require __DIR__ . '/mini.php'; ?>
<?php require __DIR__ . '/foot.php'; ?>