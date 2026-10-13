<?php
/* ============================================================
   Ruta: vista/admin/tiend/tiend-usuar.php
   Módulo: Administración / Tiendas
   Función: Contenedor de usuarios asignados a una tienda.
   El contenido es cargado por tiend.js.
============================================================ */
?>

<div class="admi-tiend-usuarios" data-tiend-usuarios-mini>
    <div class="admi-tiend-usuarios-cabecera">
        <div data-tiend-usuarios-titulo>USUARIOS DE LA TIENDA</div>
        <div data-tiend-usuarios-estad></div>
    </div>

    <div class="admi-tiend-usuarios-info" data-tiend-usuarios-info></div>

    <div class="admi-tiend-usuarios-tabla-wrap">
        <table class="admi-tiend-usuarios-tabla">
            <thead>
                <tr>
                    <th>CÓDIGO</th>
                    <th>USUARIO</th>
                    <th>NOMBRE</th>
                    <th>RESPONSABLE</th>
                    <th>ACCIÓN</th>
                </tr>
            </thead>
            <tbody data-tiend-usuarios-lista>
                <tr><td colspan="5">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="admi-tiend-usuarios-acciones">
        <button type="button" data-tiend-asignar>ASIGNAR USUARIO</button>
        <button type="button" data-tiend-usuarios-cerrar>CERRAR</button>
    </div>
</div>
