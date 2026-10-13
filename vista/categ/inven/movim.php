<?php
/* ============================================================
   Ruta: vista/categ/inven/movim.php
   Módulo: Inventario / Movimientos
   Función: Registrar movimientos dentro de TIEND → ALMAC.
============================================================ */
declare(strict_types=1);
?>
<section class="inve-movi" data-movi>
    <div class="inve-cabe">
        <select data-movi-tien aria-label="Tienda">
        </select>

        <select data-movi-alma aria-label="Almacén">
            <option value="">Todos los almacenes</option>
        </select>

        <input
            type="search"
            placeholder="Buscar movimiento..."
            autocomplete="off"
            data-movi-busc
        >

        <button type="button" data-movi-nuevo>
            Registrar movimiento
        </button>
    </div>

    <div class="inve-tabla-cont">
        <table class="inve-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Código</th>
                    <th>Tienda</th>
                    <th>Almacén</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Motivo</th>
                    <th>Fecha</th>
                </tr>
            </thead>

            <tbody data-movi-cuerpo></tbody>
        </table>

        <p class="inve-vacio" data-movi-mensaje>
            Seleccione una tienda.
        </p>
    </div>
</section>
