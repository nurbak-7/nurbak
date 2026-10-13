<?php
/* ============================================================
   Ruta: vista/categ/venta/nven.php
   Módulo: Ventas / Notas de venta

   RESPONSABILIDAD:
   Consultar las notas de venta y permitir la apertura del MINI
   de Nota de Venta. La consulta pertenece a nvent; el formulario
   y su controlador son propios del módulo. Catálogo e Inventario
   aportan únicamente sus repositorios compartidos.
============================================================ */
?>
<section class="vent-panel" data-nven>
    <div class="vent-barra">
        <input type="search" placeholder="Buscar nota de venta..." data-nven-busc autocomplete="off">
        <button type="button" data-nven-nueva>+ Nueva Nota de Venta</button>
    </div>
    <table class="vent-tabla">
        <thead><tr><th>ID</th><th>Documento</th><th>Cliente</th><th>Estado</th><th>Fecha</th><th>Total</th><th>Acción</th></tr></thead>
        <tbody data-nven-cuerpo></tbody>
    </table>
    <p class="vent-mensaje" data-nven-mensaje>Sin registros</p>
</section>
