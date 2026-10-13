<?php
/* ============================================================
   Ruta: vista/categ/venta/devo.php
   Módulo: Ventas / Devoluciones

   FUNCIÓN:
   Consultar devoluciones.

   Las devoluciones nuevas se manejarán como PENDIENTES antes de
   producir cualquier efecto en Inventario.
============================================================ */
?>
<section class="vent-panel" data-devo>
    <div class="vent-barra">
        <input type="search" placeholder="Buscar devolución..." data-devo-busc autocomplete="off">
    </div>
    <table class="vent-tabla">
        <thead><tr><th>ID</th><th>Operación</th><th>Cliente</th><th>Almacén</th><th>Estado</th><th>Fecha</th><th>Motivo</th></tr></thead>
        <tbody data-devo-cuerpo></tbody>
    </table>
    <p class="vent-mensaje" data-devo-mensaje>Sin registros</p>
</section>
