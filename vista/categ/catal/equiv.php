<?php
/* ============================================================
   Ruta: vista/categ/catal/equiv.php
   Módulo: Catálogo / Equiv
   Función: Consulta y administración de relaciones mediante MINI.
============================================================ */
?>
<section class="modu-vista catal-equiv" data-equiv>
    <div class="equiv-cab"><div class="equiv-busc"><label for="equiv-busc">Buscar equivalente</label><input id="equiv-busc" type="search" placeholder="Buscar producto, código, marca, motor o modelo..." autocomplete="off" data-equiv-busc></div><button type="button" data-equiv-nuevo>NUEVO</button></div>
    <div class="equiv-tabla"><table><thead><tr><th>ID</th><th>Producto</th><th>Código</th><th>Original</th><th>Marca</th><th>Motor</th><th>Modelo</th><th>Año</th><th>Generación</th><th>Acción</th></tr></thead><tbody data-equiv-cuer></tbody></table></div>
    <div data-equiv-vaci hidden></div>
</section>
