<?php
/* ============================================================
   Ruta: vista/categ/venta/opera.php
   Módulo: Ventas / Operaciones

   RESPONSABILIDAD:
   Coordinar los bloques de la ventana Operaciones.
============================================================ */
?>

<section class="opera-panel" data-opera>

    <form class="opera-form" data-opera-form>

        <!-- ==================================================
             ENCABEZADO
        =================================================== -->
        <section class="opera-encabezado">

            <!-- BLOQUE 1 — DOCUMENTO -->
            <?php require __DIR__ . '/opera/docum.php'; ?>

            <!-- BLOQUE 2 — CLIENTE -->
            <?php require __DIR__ . '/clien/opera.php'; ?>

            <!-- BLOQUE 3 — OPERACIÓN -->
            <?php require __DIR__ . '/opera/opera.php'; ?>

        </section>

        <!-- ==================================================
             PRODUCTOS
        =================================================== -->
        <?php require __DIR__ . '/opera/produc.php'; ?>

        <!-- ==================================================
             TABLA DE PRODUCTOS
        =================================================== -->
        <?php require __DIR__ . '/opera/tabla.php'; ?>

        <!-- ==================================================
             TOTALES
        =================================================== -->
        <?php require __DIR__ . '/opera/total.php'; ?>

        <!-- ==================================================
             PAGO
        =================================================== -->
        <?php require __DIR__ . '/opera/pagos.php'; ?>

        <!-- ==================================================
             ACCIONES
        =================================================== -->
        <?php require __DIR__ . '/opera/accio.php'; ?>

    </form>

</section>
