<?php
/* ============================================================
   Ruta: vista/categ/compr/comex-mini.php
   Módulo: Compras / Compra externa / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="cpfrm" data-comx-form>
    <select data-comx-prove required>
    </select>

    <textarea
        maxlength="300"
        placeholder="Observación"
        data-comx-obse
    ></textarea>

    <div class="cpdet">
        <select data-comx-produ>
        </select>

        <input
            type="number"
            min="0.001"
            step="0.001"
            placeholder="Cantidad"
            data-comx-canti
        >

        <input
            type="number"
            min="0"
            step="0.01"
            placeholder="PCOMP"
            data-comx-preun
        >

        <button type="button" data-comx-agre>
            Agregar
        </button>
    </div>

    <table class="cptab">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>PCOMP</th>
                <th>Subtotal</th>
                <th></th>
            </tr>
        </thead>
        <tbody data-comx-deta></tbody>
    </table>

    <div>
        Total:
        <strong data-comx-total>0.00</strong>
    </div>

    <button type="submit">
        Guardar
    </button>

    <button type="button" data-comx-canc>
        Cancelar
    </button>
</form>
