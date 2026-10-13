<?php
/* ============================================================
   Ruta: vista/admin/tiend/tiend.php
   Módulo: Administración / Tiendas
   Función: Presentar la administración principal de TIEND.

   TIEND se encarga de:
   - consultar tiendas;
   - crear y editar datos;
   - administrar usuarios asignados;
   - definir responsable principal;
   - activar / inactivar;
   - eliminar tiendas cuando corresponda.
============================================================ */
?>

<section class="admi-tiend" data-admi-tiend>
    <div class="admi-tiend-cabecera">
        <div>
            <h2>TIENDAS</h2>
            <p>Administra las tiendas y sus usuarios asignados.</p>
        </div>

        <div class="admi-tiend-acciones">
            <input
                type="search"
                data-tiend-buscar
                placeholder="Buscar código, nombr, dirección..."
                autocomplete="off"
            >
            <button type="button" data-tiend-nueva>NUEVA TIENDA</button>
        </div>
    </div>

    <div class="admi-tiend-tabla-wrap">
        <table class="admi-tiend-tabla">
            <thead>
                <tr>
                    <th>CÓDIGO</th>
                    <th>NOMBRE</th>
                    <th>DIRECCIÓN</th>
                    <th>TELÉFONO</th>
                    <th>USUARIOS</th>
                    <th>RESPONSABLE</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody data-tiend-lista>
                <tr>
                    <td colspan="8">Cargando tiendas...</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
