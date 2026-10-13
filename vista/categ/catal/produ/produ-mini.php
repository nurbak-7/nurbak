<?php
/* ============================================================
   Ruta: vista/categ/catal/produ/produ-mini.php
   Módulo: Catálogo / Productos / MINI
   Función: Formulario de alta y edición.

   PRECIOS:
   - PCOMP → precio de compra.
   - PVENT → precio de venta.
============================================================ */
$id = (int) ($_GET['id'] ?? 0);
?>
<form class="catal-mini-form" data-prod-mini-form data-id="<?= $id ?>">
    <div class="catal-mini-grid">
        <label>Descripción *<input name="descr" required autocomplete="off"></label>
        <label>Código<input name="codig" autocomplete="off"></label>
        <label>Original<input name="origi" autocomplete="off"></label>
        <label>Medida<input name="medid" autocomplete="off"></label>
        <label>Super<input name="super" autocomplete="off"></label>
        <label>Equivalente<input name="equiv" autocomplete="off"></label>
        <label>Precio compra<input name="pcomp" type="number" step="0.01" min="0" value="0.00"></label>
        <label>Precio venta<input name="pvent" type="number" step="0.01" min="0" value="0.00"></label>
    </div>
    <div class="catal-mini-msg" data-mini-msg></div>
    <div class="catal-mini-acciones">
        <button type="submit">GUARDAR</button>
        <button type="button" data-mini-cerrar>CERRAR</button>
    </div>
</form>
