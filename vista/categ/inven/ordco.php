<?php
/* ============================================================
   Ruta: vista/categ/inven/ordco.php
   Módulo: Inventario / Orden interna
   Función: Ordenar traslado entre ALMAC de una o dos TIEND.
============================================================ */
declare(strict_types=1);
?>
<section class="ordco" data-ordc>
    <div class="ivbar">
        <input
            type="search"
            placeholder="Buscar orden..."
            data-ordc-busc
        >

        <button type="button" data-ordc-nuev>
            Nueva orden
        </button>
    </div>

    <div class="ivtc">
        <table class="ivtab">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tienda origen</th>
                    <th>Almacén origen</th>
                    <th>Tienda destino</th>
                    <th>Almacén destino</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Observación</th>
                </tr>
            </thead>

            <tbody data-ordc-cuer></tbody>
        </table>

        <p data-ordc-mens>
            Cargando órdenes.
        </p>
    </div>
</section>
