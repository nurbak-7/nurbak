<?php
/* ============================================================
   Ruta: vista/categ/venta/nvent/clien.php
   Módulo: Ventas / Nota de Venta / Cliente
   RESPONSABILIDAD: datos propios del comprador usados en la nota.
============================================================ */
?>
<div class="nvent-bloque nvent-cliente">
    <div class="nvent-fila nvent-fila-doble">
        <label>RUC/DNI:</label>
        <input type="text" name="docum" inputmode="numeric" autocomplete="off" maxlength="11" required data-nven-docum placeholder="Ingrese RUC o DNI">
        <label>RAZÓN SOCIAL:</label>
        <input type="text" name="razon" autocomplete="off" required data-nven-razon>
        <input type="hidden" name="clien" value="0" data-nven-clien>
    </div>
    <div class="nvent-fila">
        <label>DIRECCIÓN:</label>
        <input type="text" name="direc" autocomplete="street-address" data-nven-direc>
    </div>
</div>
