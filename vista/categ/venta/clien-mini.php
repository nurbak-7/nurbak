<?php
/* ============================================================
   Ruta: vista/categ/venta/clien-mini.php
   Módulo: Ventas / Clientes / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="vent-form" data-clie-form>
    <select data-clie-tipo>
        <option value="DNI">DNI</option>
        <option value="RUC">RUC</option>
        <option value="CE">CE</option>
    </select>

    <input
        type="text"
        placeholder="Documento"
        data-clie-docum
    >

    <input
        type="text"
        placeholder="Nombre / razón social *"
        data-clie-nombr
        required
    >

    <input
        type="text"
        placeholder="Teléfono"
        data-clie-telef
    >

    <input
        type="email"
        placeholder="Correo"
        data-clie-email
    >

    <input
        type="text"
        placeholder="Dirección"
        data-clie-direc
    >

    <button type="submit">
        Guardar
    </button>

    <button type="button" data-clie-cancelar>
        Cancelar
    </button>
</form>
