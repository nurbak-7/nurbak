<?php
/* ============================================================
   Ruta: vista/categ/venta/nvent/docum.php
   Módulo: Ventas / Nota de Venta / Documento
   RESPONSABILIDAD: fecha, tipo de documento del comprador,
   serie y número de Nota de Venta.
============================================================ */
?>
<div class="nvent-bloque nvent-documento">
    <div class="nvent-fila">
        <label>FECHA:</label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($hoy, ENT_QUOTES, 'UTF-8') ?>" required data-nven-fecha>
    </div>
    <div class="nvent-fila">
        <label>TIPO DOC.:</label>
        <select data-nven-tipd disabled aria-label="Tipo de comprobante detectado por DNI o RUC">
            <option value="BOLETA" selected>BOLETA</option>
            <option value="FACTURA">FACTURA</option>
        </select>
    </div>
    <div class="nvent-fila">
        <label>SERIE / N°:</label>
        <input type="text" name="serie" value="" readonly data-nven-serie aria-label="Serie de Nota de Venta">
        <input type="text" name="numer" value="" readonly data-nven-numer aria-label="Siguiente número de Nota de Venta">
    </div>
</div>
