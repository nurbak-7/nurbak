<?php
/* ============================================================
   Ruta: vista/admin/tiend/tiend-formu.php
   Módulo: Administración / Tiendas
   Función: Formulario reutilizable para CREAR / EDITAR TIEND.
============================================================ */
?>

<form class="admi-tiend-form" data-tiend-form>
    <div class="admi-tiend-form-campo">
        <label>CÓDIGO</label>
        <input type="text" data-tiend-campo="codig" readonly>
    </div>

    <div class="admi-tiend-form-campo">
        <label>NOMBRE *</label>
        <input
            type="text"
            name="nombr"
            data-tiend-campo="nombr"
            maxlength="100"
            required
            autocomplete="off"
        >
    </div>

    <div class="admi-tiend-form-campo">
        <label>DIRECCIÓN</label>
        <input
            type="text"
            name="direc"
            data-tiend-campo="direc"
            maxlength="200"
            autocomplete="off"
        >
    </div>

    <div class="admi-tiend-form-campo">
        <label>TELÉFONO</label>
        <input
            type="text"
            name="telef"
            data-tiend-campo="telef"
            maxlength="30"
            autocomplete="off"
        >
    </div>

    <div class="admi-tiend-form-info">
        Una tienda nueva se crea <strong>INACTIVA</strong>.
        Para activarla deberá tener al menos un usuario responsable y un almacén principal activo.
    </div>

    <div class="admi-tiend-form-acciones">
        <button type="button" data-tiend-cancelar>CANCELAR</button>
        <button type="submit">GUARDAR</button>
    </div>
</form>
