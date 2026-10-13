<?php
/* ============================================================
   Ruta: vista/categ/venta/nvent/pago.php
   Módulo: Ventas / Nota de Venta / Pago
   RESPONSABILIDAD: mostrar forma y medio de pago de la nota.
============================================================ */
?>
<div class="nvent-pago">
    <div class="nvent-pago-campo">
        <label>FORMA DE PAGO:</label>
        <select name="formp" data-nven-formp>
            <option value="CONTADO">Contado</option>
            <option value="CREDITO">Crédito</option>
        </select>
    </div>
    <div class="nvent-pago-campo">
        <label>MEDIO DE PAGO:</label>
        <input type="text" name="medi" list="nvent-medios" placeholder="Yape" autocomplete="off" data-nven-medi>
        <datalist id="nvent-medios">
            <option value="Efectivo"></option><option value="Yape"></option>
            <option value="Plin"></option><option value="Transferencia"></option>
            <option value="Tarjeta"></option>
        </datalist>
    </div>
</div>
