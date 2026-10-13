<?php
/* ============================================================
   Ruta: vista/categ/compr/recep-mini.php
   Módulo: Compras / Recepción / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="rece-form" data-rece-form>
    <div class="rece-campos">
        <select data-rece-proveedor required>
        </select>

        <select data-rece-comex>
            <option value="">Sin compra externa</option>
        </select>

        <select data-rece-almacen required>
        </select>

        <textarea
            placeholder="Observación"
            maxlength="300"
            data-rece-obser
        ></textarea>
    </div>

    <div class="rece-detalle">
        <select data-rece-producto>
        </select>

        <input
            type="number"
            min="0.001"
            step="0.001"
            placeholder="Cantidad"
            data-rece-canti
        >

        <input
            type="number"
            min="0"
            step="0.01"
            placeholder="Costo unitario"
            data-rece-costo
        >

        <button type="button" data-rece-agregar>
            Agregar
        </button>
    </div>

    <table class="rece-tabla">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Costo</th>
                <th></th>
            </tr>
        </thead>
        <tbody data-rece-detalles></tbody>
    </table>

    <button type="submit">
        Registrar recepción
    </button>

    <button type="button" data-rece-cancelar>
        Cancelar
    </button>
</form>
