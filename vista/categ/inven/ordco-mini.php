<?php
/* ============================================================
   Ruta: vista/categ/inven/ordco-mini.php
   Módulo: Inventario / Orden interna / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="ivfrm" data-ordc-form>
    <div class="ivgrp">
        <label>Tienda origen</label>

        <select data-ordc-tior required>
        </select>

        <label>Almacén origen</label>

        <select data-ordc-orige required>
        </select>
    </div>

    <div class="ivgrp">
        <label>Tienda destino</label>

        <select data-ordc-tide required>
        </select>

        <label>Almacén destino</label>

        <select data-ordc-desti required>
        </select>
    </div>

    <textarea
        maxlength="300"
        placeholder="Observación"
        data-ordc-obse
    ></textarea>

    <div class="ivdet">
        <select data-ordc-produ>
        </select>

        <input
            type="number"
            min="0.001"
            step="0.001"
            placeholder="Cantidad"
            data-ordc-canti
        >

        <button type="button" data-ordc-agre>
            Agregar
        </button>
    </div>

    <table class="ivtab">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th></th>
            </tr>
        </thead>
        <tbody data-ordc-deta></tbody>
    </table>

    <button type="submit">
        Guardar
    </button>

    <button type="button" data-ordc-canc>
        Cancelar
    </button>
</form>
