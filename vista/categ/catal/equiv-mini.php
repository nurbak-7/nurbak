<?php
/* ============================================================
   Ruta: vista/categ/catal/equiv-mini.php
   Módulo: Catálogo / Equiv / MINI
   Función: Formulario para crear una relación Producto ↔ Vehículo.
============================================================ */
?>
<form class="catal-mini-form" data-equiv-mini-form>
    <label>Producto *<select name="produ" required data-equiv-mini-prod></select></label>
    <label>Vehículo *<select name="vehic" required data-equiv-mini-vehi></select></label>
    <div class="catal-mini-msg" data-mini-msg></div>
    <div class="catal-mini-acciones"><button type="submit">GUARDAR</button><button type="button" data-mini-cerrar>CERRAR</button></div>
</form>
