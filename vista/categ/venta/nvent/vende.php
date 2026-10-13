<?php
/* ============================================================
   Ruta: vista/categ/venta/nvent/vende.php
   Módulo: Ventas / Nota de Venta / Vendedor y vehículo

   RESPONSABILIDAD:
   Capturar el código del vendedor y la placa asociada a la venta.
   El código se resuelve en el backend de Nota de Venta.
============================================================ */
?>
<div class="nvent-fila">
    <label>VENDEDOR:</label>
    <input type="text" data-nven-vende-cod placeholder="Ingresar código" maxlength="20" autocomplete="off">
    <input type="hidden" name="vende" value="0" data-nven-vende>
</div>
<div class="nvent-fila">
    <label>PLACA:</label>
    <input type="text" name="placa" data-nven-placa placeholder="SSS-SSS" maxlength="7" autocomplete="off">
</div>
