<?php
/* ============================================================
   Ruta: vista/admin/usuar/usuar.php
   Función: Mostrar la ventana principal de Usuarios.

   🔹 Nivel 1: Presentar búsqueda y creación.
   🔹 Nivel 2: Presentar la tabla de usuarios.
   🔹 Nivel 3: Mostrar acciones por usuario.
   🔹 Nivel Final: Entregar los elementos que controla usuar.js.
============================================================ */
?>

<section class="admi-usua" data-admi-usua>
    <div class="admi-usua-cabecera">
        <h2>USUARIOS</h2>

        <div class="admi-usua-acciones">
            <input
                type="search"
                data-usua-buscar
                placeholder="Buscar usuario..."
                autocomplete="off"
            >

            <button type="button" data-usua-accion="nuevo">
                NUEVO
            </button>
        </div>
    </div>

    <div class="admi-usua-tabla-wrap">
        <table class="admi-usua-tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody data-usua-lista>
                <tr>
                    <td colspan="6">Cargando...</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
