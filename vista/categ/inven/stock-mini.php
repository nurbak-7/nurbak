<?php
/* ============================================================
   Ruta: vista/categ/inven/stock-mini.php
   Módulo: Inventario / Stock / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="inve-form" data-stoc-form>
    <select data-stoc-tien required>
    </select>
    <select data-stoc-producto required>
    </select>

    <select data-stoc-almacen required>
    </select>

    <input
        type="number"
        min="0"
        step="0.001"
        value="0"
        placeholder="Mínimo"
        data-stoc-minimo
    >

    <input
        type="number"
        min="0"
        step="0.001"
        value="0"
        placeholder="Máximo"
        data-stoc-maximo
    >

    <button type="submit">
        Guardar
    </button>

    <button type="button" data-stoc-cancelar>
        Cancelar
    </button>
</form>
