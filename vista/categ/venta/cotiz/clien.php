<?php
/* ============================================================
   Ruta: vista/categ/venta/cotiz/clien.php
   Módulo: Ventas / Cotización / Cliente

   RESPONSABILIDAD:
   Mostrar RUC/DNI, razón social y dirección.
============================================================ */
?>
<div class="cotiz-bloque cotiz-cliente">
    <h3 class="cotiz-bloque-titulo">Datos de cliente</h3>
    <div class="cotiz-fila cotiz-fila-doble">
        <label>RUC/DNI:</label>
        <input type="text" data-cotiz-docum autocomplete="off" placeholder="Ingrese RUC o DNI">
        <label>RAZÓN SOCIAL:</label>
        <input type="text" data-cotiz-razon autocomplete="off">
    </div>

    <div class="cotiz-fila">
        <label>DIRECCIÓN:</label>
        <input type="text" data-cotiz-direc autocomplete="off">
    </div>

    <input type="hidden" data-cotiz-clien value="0">
</div>
