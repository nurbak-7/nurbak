<?php
/* ============================================================
   Ruta: vista/categ/venta/cotiz/vende.php
   Módulo: Ventas / Cotización / Vendedor

   RESPONSABILIDAD:
   Resolver vendedor por código y mostrar el usuario resuelto.
   El segundo campo corresponde exclusivamente a PLACA.
============================================================ */
?>
<div class="cotiz-bloque cotiz-vendedor">
    <h3 class="cotiz-bloque-titulo" style="display:none">Otros</h3>
    <div class="cotiz-fila">
        <label>VENDEDOR:</label>
        <input type="text" data-cotiz-vende-cod placeholder="Ingresar código" maxlength="20" autocomplete="off">
    </div>

    <div class="cotiz-fila">
        <label>PLACA:</label>
        <input type="text" data-cotiz-placa placeholder="SSS-SSS" maxlength="7" autocomplete="off">
    </div>

    <input type="hidden" data-cotiz-vende value="0">
</div>
