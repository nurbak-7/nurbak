<?php
/* ============================================================
   Ruta: vista/categ/inven/stock.php
   Módulo: Inventario / Stock
   Función: Consultar y asignar STOCK por TIEND → ALMAC.
============================================================ */
declare(strict_types=1);
?>
<section class="inve-stoc" data-stoc>
    <div class="inve-cabe">
        <select data-stoc-tien aria-label="Tienda">
        </select>

        <select data-stoc-alma aria-label="Almacén">
            <option value="">Todos los almacenes</option>
        </select>

        <input
            type="search"
            placeholder="Buscar producto o almacén..."
            autocomplete="off"
            data-stoc-busc
        >

        <button type="button" data-stoc-asignar>
            Asignar producto
        </button>
    </div>

    <div class="inve-tabla-cont">
        <table class="inve-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Código</th>
                    <th>Original</th>
                    <th>Tienda</th>
                    <th>Almacén</th>
                    <th>Tipo</th>
                    <th>Stock físico</th>
                    <th>Demanda sin stock</th>
                    <th>Mín.</th>
                    <th>Máx.</th>
                    <th>Actualizado</th>
                </tr>
            </thead>

            <tbody data-stoc-cuerpo></tbody>
        </table>

        <p class="inve-vacio" data-stoc-mensaje>
            Seleccione una tienda.
        </p>
    </div>
</section>
