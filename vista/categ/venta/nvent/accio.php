<?php
/* ============================================================
   Ruta: vista/categ/venta/nvent/accio.php
   Módulo: Ventas / Nota de Venta / Acciones
   RESPONSABILIDAD: observación, cancelar y emitir la Nota de Venta.
============================================================ */
?>
<div class="nvent-observacion">
    <label for="nven-obser">OBSERVACIÓN:</label>
    <textarea id="nven-obser" name="obser" rows="2" data-nven-obser></textarea>
</div>
<div class="nvent-acciones">
    <button type="button" data-nven-cancelar>[ Cancelar ]</button>
    <button type="button" data-nven-emitir>[ Guardar ]</button>
</div>
<p class="nvent-mensaje-form" role="status" data-nven-form-mensaje>Seleccione el almacén y agregue productos.</p>
