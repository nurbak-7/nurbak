<?php
/* ============================================================
   Ruta: vista/categ/catal/vehic.php
   Módulo: Catálogo / Vehículos
   Función: Vista principal de Vehículos.
============================================================ */
?>
<section class="modu-vista catal-vehi" data-vehi>
    <div class="vehi-cab"><div class="vehi-busc"><label for="vehi-busc">Buscar vehículos</label><input id="vehi-busc" type="search" placeholder="Buscar marca, motor, modelo o año..." autocomplete="off" data-vehi-busc></div><button type="button" class="vehi-nuevo" data-vehi-nuevo>NUEVO</button></div>
    <div class="vehi-tabla"><table><thead><tr><th>ID</th><th>MARCA</th><th>MOTOR</th><th>MODELO</th><th>AÑO</th><th>ACCIÓN</th></tr></thead><tbody data-vehi-cuer></tbody></table><div data-vehi-vaci hidden></div></div>
</section>
