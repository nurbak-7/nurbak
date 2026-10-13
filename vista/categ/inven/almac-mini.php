<?php
/* ============================================================
   Ruta: vista/categ/inven/almac-mini.php
   Módulo: Inventario / Almacenes / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="inve-form" data-alma-form>
    <input
        type="hidden"
        data-alma-tien
    >

    <input
        type="text"
        placeholder="Nombre *"
        maxlength="100"
        data-alma-nombr
        required
    >

    <input
        type="text"
        placeholder="Descripción"
        maxlength="200"
        data-alma-descr
    >

    <input
        type="text"
        placeholder="Dirección"
        maxlength="200"
        data-alma-direc
    >

    <label>
        <input
            type="checkbox"
            data-alma-prin
        >
        Principal
    </label>

    <button type="submit">
        Guardar
    </button>

    <button type="button" data-alma-cancelar>
        Cancelar
    </button>
</form>
