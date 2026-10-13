<?php
/* ============================================================
   Ruta: vista/categ/compr/prove-mini.php
   Módulo: Compras / Proveedores / MINI
============================================================ */
declare(strict_types=1);
?>
<form class="prove-form" data-prov-form>
    <input
        type="text"
        placeholder="Razón social *"
        maxlength="180"
        data-prov-razon
        required
    >

    <input
        type="text"
        placeholder="RUC"
        maxlength="20"
        data-prov-ruc
    >

    <input
        type="text"
        placeholder="Contacto"
        maxlength="120"
        data-prov-conta
    >

    <input
        type="text"
        placeholder="Teléfono"
        maxlength="50"
        data-prov-telef
    >

    <input
        type="email"
        placeholder="Correo"
        maxlength="150"
        data-prov-email
    >

    <input
        type="text"
        placeholder="Dirección"
        maxlength="200"
        data-prov-direc
    >

    <button type="submit">
        Guardar
    </button>

    <button type="button" data-prov-cancelar>
        Cancelar
    </button>
</form>
