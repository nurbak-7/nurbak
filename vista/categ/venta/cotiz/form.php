<?php
/* ============================================================
   Ruta: vista/categ/venta/cotiz/form.php
   Módulo: Ventas / Cotización / Formulario

   RESPONSABILIDAD:
   Coordinar exclusivamente los bloques visuales de Cotización.
   La ventana sigue la separación usada por Operaciones.
============================================================ */
?>
<section class="cotiz-panel" data-cotiz data-cotiz-id="<?= (int)($_GET['id'] ?? 0) ?>">
    <form class="cotiz-form" data-cotiz-form>

        <section class="cotiz-encabezado">
            <?php require __DIR__ . '/docum.php'; ?>
            <?php require __DIR__ . '/clien.php'; ?>
            <?php require __DIR__ . '/vende.php'; ?>
        </section>

        <?php require __DIR__ . '/produc.php'; ?>
        <?php require __DIR__ . '/tabla.php'; ?>
        <?php require __DIR__ . '/total.php'; ?>
        <?php require __DIR__ . '/accio.php'; ?>

    </form>
</section>
