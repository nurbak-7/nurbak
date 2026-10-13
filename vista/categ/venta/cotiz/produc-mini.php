<?php
/* ============================================================
   Ruta: vista/categ/venta/cotiz/produc-mini.php
   Módulo: Ventas / Cotización / MINI Productos

   RESPONSABILIDAD:
   Mostrar únicamente el contenido del MINI de productos.
============================================================ */
?>
<div class="cotiz-mini-prod">
    <div class="cotiz-mini-busca">
        <label>BUSCAR PRODUCTO:</label>
        <input type="search" data-cotiz-prod-buscar placeholder="Código, descripción, OEM, medida, motor, modelo..." autocomplete="off">
    </div>

    <div class="cotiz-mini-info">
        <span data-cotiz-prod-info>Sin resultados.</span>
    </div>

    <div class="cotiz-mini-tabla-wrap">
        <table class="cotiz-mini-tabla">
            <thead>
                <tr>
                    <th>MOTOR</th>
                    <th>MODEL</th>
                    <th>CODIG</th>
                    <th>DESCR</th>
                    <th>ORIGI</th>
                    <th>MEDID</th>
                    <th>SUPER</th>
                    <th>AÑO</th>
                    <th>P.UNIT</th>
                </tr>
            </thead>
            <tbody data-cotiz-prod-resultados></tbody>
        </table>
    </div>

    <p class="cotiz-mini-ayuda">↑ ↓ seleccionar · ENTER agregar · ESC cerrar</p>
</div>
