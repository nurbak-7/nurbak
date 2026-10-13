<?php
/* ============================================================
   Ruta: vista/categ/venta/nvent/form.php
   Módulo: Ventas / Nota de Venta / Formulario MINI

   RESPONSABILIDAD:
   Coordinar exclusivamente los bloques visuales de Nota de Venta.
   Conserva los selectores que utiliza el frontend nvent.js.
   Almacén y productos se consultan con los repositorios existentes
   de INVENTARIO y CATÁLOGO desde el controlador de nvent.
============================================================ */
$hoy = date('Y-m-d');
?>
<section class="nvent-panel" data-nven-form>
    <form class="nvent-form" data-nven-formulario>
        <section class="nvent-encabezado">
            <?php require __DIR__ . '/docum.php'; ?>
            <?php require __DIR__ . '/clien.php'; ?>
            <div class="nvent-bloque nvent-operacion">
                <?php require __DIR__ . '/vende.php'; ?>
                <?php require __DIR__ . '/almac.php'; ?>
            </div>
        </section>

        <?php require __DIR__ . '/produc.php'; ?>
        <?php require __DIR__ . '/tabla.php'; ?>
        <?php require __DIR__ . '/total.php'; ?>
        <?php require __DIR__ . '/pago.php'; ?>
        <?php require __DIR__ . '/accio.php'; ?>
    </form>
</section>
