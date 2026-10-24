<?php
/* ============================================================
   Ruta: vista/categ/catal/artic.php
   Módulo: Catálogo / Artículos
   Función: Consulta general consolidada.

   Artículo NO es una entidad CRUD.
   Consolida Producto + Equivalente + Vehículo + Inventario.
   El buscador consulta automáticamente al servidor.
============================================================ */
?>
<section class="catal-arti" data-arti>
    <div class="arti-busc"><label for="arti-busc">Buscar artículos</label><input id="arti-busc" type="search" placeholder="Buscar motor, model, descripción, código, origi, vehículo o año..." autocomplete="off" data-arti-busc></div>
    <div class="arti-tabla arti-tabla-escr"><table><thead><tr><th>ID</th><th>MOTOR</th><th>MODELO</th><th>DESCRIPCIÓN</th><th>CODIG</th><th>ORIGINAL</th><th>MEDIDA</th><th>SUPER</th><th>EQUIVALENTE</th><th>AÑO</th><th>QTY</th><th>PCOMP</th><th>PVENT</th><th>FECHA</th></tr></thead><tbody data-arti-cuer></tbody></table></div>
    <div class="arti-movil" data-arti-movil>
        <div class="arti-grupo">
            <table><thead><tr><th>ID</th><th>MOTOR</th><th>MODEL</th><th>DESCR</th><th>CODIG</th></tr></thead><tbody data-arti-mov-uno></tbody></table>
        </div>
        <div class="arti-grupo">
            <table><thead><tr><th>ORIGI</th><th>MEDID</th><th>SUPER</th><th>EQUIV</th><th>ANIO</th></tr></thead><tbody data-arti-mov-dos></tbody></table>
        </div>
        <div class="arti-grupo">
            <table><thead><tr><th>QTY</th><th>PCOMP</th><th>PVENT</th><th>FECHA</th></tr></thead><tbody data-arti-mov-tres></tbody></table>
        </div>
    </div>
    <div class="arti-vaci" data-arti-vaci hidden></div>
</section>
