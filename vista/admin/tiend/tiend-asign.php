<?php
/* ============================================================
   Ruta: vista/admin/tiend/tiend-asign.php
   Módulo: Administración / Tiendas
   Función: Seleccionar un usuario para asignarlo a TIEND.
============================================================ */
?>

<form class="admi-tiend-asignar" data-tiend-asignar-form>
    <input type="hidden" name="tiend" data-tiend-asignar-tiend>

    <div class="admi-tiend-form-campo">
        <label>BUSCAR USUARIO</label>
        <input
            type="search"
            data-tiend-usuario-buscar
            placeholder="Código, usuario o nombr..."
            autocomplete="off"
        >
    </div>

    <div class="admi-tiend-seleccion" data-tiend-usuario-lista>
        <p>Buscando usuarios disponibles...</p>
    </div>

    <div class="admi-tiend-asignar-resp">
        <label>
            <input type="checkbox" name="respo" value="1" data-tiend-es-resp>
            Designar como responsable principal
        </label>
    </div>

    <div class="admi-tiend-form-acciones">
        <button type="button" data-tiend-asignar-cancelar>CANCELAR</button>
        <button type="submit" data-tiend-asignar-guardar disabled>ASIGNAR</button>
    </div>
</form>
