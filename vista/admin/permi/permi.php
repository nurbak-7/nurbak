<?php
/* ============================================================
   Ruta: vista/admin/permi/permi.php
   Función: Presentar la tabla de usuarios y el detalle de sus
   permisos dentro del submódulo PERM.

   NIVEL 1: Buscador y CREAR.
   NIVEL 2: Tabla de usuarios.
   NIVEL 3: VER con submenú por hover.
   NIVEL FINAL: Reservar la estructura para PERM-JS.
============================================================ */
?>

<section class="admi-perm" data-admi-perm>
    <!-- ======================================================
         NIVEL 1 — BUSCADOR Y CREAR
    ======================================================= -->
    <h2>PERMISOS</h2>
    <div class="admi-perm-acciones">
        <input type="search" placeholder="Buscar usuario..." data-perm-buscar autocomplete="off">
        <button type="button" data-perm-accion="crear">CREAR</button>
    </div>

    <!-- ======================================================
         NIVEL 2 — TABLA
    ======================================================= -->
    <div class="admi-perm-tabla-wrap">
        <table class="admi-perm-tabla">
            <thead>
                <tr>
                    <th>Código Usuario</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Permisos</th>
                    <th>Detalle</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody data-perm-lista>
                <tr><td colspan="7">Cargando usuarios...</td></tr>
            </tbody>
        </table>
    </div>
</section>
