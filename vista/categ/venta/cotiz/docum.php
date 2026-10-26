<?php
/* ============================================================
   Ruta: vista/categ/venta/cotiz/docum.php
   Módulo: Ventas / Cotización / Documento

   RESPONSABILIDAD:
   Mostrar fecha, vigencia, serie y número.
============================================================ */
?>
<div class="cotiz-bloque cotiz-documento">
    <h3 class="cotiz-bloque-titulo">Datos de cotización</h3>
    <div class="cotiz-fila">
        <label>FECHA:</label>
        <input type="date" data-cotiz-fecha>
    </div>

    <div class="cotiz-fila">
        <label>VIGENCIA:</label>
        <input type="date" data-cotiz-vigia readonly>
    </div>

    <div class="cotiz-fila">
        <label>SERIE / N°:</label>
        <input type="text" data-cotiz-serie readonly>
        <input type="text" data-cotiz-numero readonly>
    </div>
</div>
