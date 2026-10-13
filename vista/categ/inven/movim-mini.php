<?php
/* ============================================================
   Ruta: vista/categ/inven/movim-mini.php
   Módulo: Inventario / Movimientos / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="inve-form" data-movi-form>
    <select data-movi-tien required>
    </select>
    <select data-movi-producto required>
    </select>

    <select data-movi-almacen required>
    </select>

    <select data-movi-tipo required>
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
        <option value="ajuste">Ajuste</option>
        <option value="salss">Salida sin stock</option>
    </select>

    <input
        type="number"
        min="0.001"
        step="0.001"
        placeholder="Cantidad *"
        data-movi-canti
        required
    >

    <input
        type="text"
        maxlength="200"
        placeholder="Motivo"
        data-movi-motvo
    >

    <button type="submit">
        Registrar
    </button>

    <button type="button" data-movi-cancelar>
        Cancelar
    </button>
</form>
