<?php
/* ============================================================
   Ruta: vista/categ/venta/nvent/produc-mini.php
   Módulo: Ventas / Nota de Venta / Productos / MINI
   Responsabilidad: estructura visual; búsqueda coordinada por nvent.js.
============================================================ */
?>
<section class="nvent-prod-mini" data-nven-prod-mini>
    <div class="nvent-prod-mini-buscador">
        <label for="nven-prod-buscar">BUSCAR PRODUCTO:</label>
        <input id="nven-prod-buscar" type="search" data-nven-prod-buscar
               placeholder="Código, descripción, OEM, medida, motor, modelo..." autocomplete="off" autofocus>
    </div>
    <div class="nvent-prod-mini-info">
        <span data-nven-prod-info>Sin resultados.</span>
        <span data-nven-prod-stock>STOCK: 0</span>
        <span data-nven-prod-reser>RESERVADO: 0</span>
        <span data-nven-prod-dispo>DISPONIBLE: 0</span>
    </div>
    <div class="nvent-prod-mini-tabla-wrap">
        <table class="nvent-prod-mini-tabla">
            <thead><tr><th>MOTOR</th><th>MODEL</th><th>CODIG</th><th>DESCR</th><th>ORIGI</th><th>MEDID</th><th>SUPER</th><th>AÑO</th><th>STOCK</th><th>P.UNIT</th></tr></thead>
            <tbody data-nven-prod-resultados></tbody>
        </table>
    </div>
    <p class="nvent-prod-mini-ayuda">↑ ↓ seleccionar · ENTER agregar · ESC cerrar</p>
</section>
