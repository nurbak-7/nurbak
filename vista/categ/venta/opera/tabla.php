<?php
/* ============================================================
   Ruta: vista/categ/venta/opera/tabla.php
   Módulo: Ventas / Operaciones / Tabla

   RESPONSABILIDAD:
   Mostrar las filas de productos agregados.

   ESTRUCTURA:
   - opera-tabla-area → área con altura mínima de trabajo.
   - opera-detalle    → tabla real.
   - tbody           → filas generadas dinámicamente por tabla.js.
============================================================ */
?>

<div class="opera-tabla-area">
    <table class="opera-tabla opera-detalle">
        <thead>
            <tr>
                <th>IT</th>
                <th>CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th>QTY</th>
                <th>P.UNIT</th>
                <th>TOTAL</th>
                <th></th>
            </tr>
        </thead>
        <tbody data-opera-detalles></tbody>
    </table>
</div>
