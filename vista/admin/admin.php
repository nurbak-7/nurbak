<?php
/* ============================================================
   Ruta: vista/admin/admin.php
   Función: Punto principal del módulo de administración.

   🔹 Nivel 1: Administración
   🔹 Nivel 2: Coordina los submódulos administrativos.
   🔹 Nivel 3: Permisos, usuarios y tiendas.
   🔹 Nivel Final: Presentar la estructura administrativa.
============================================================ */
?>

<section class="admi" data-admi>

    <div class="admi-head">
        <h2>ADMINISTRACIÓN</h2>
        <p>Gestión de permisos, usuarios y tiendas.</p>
    </div>

    <div class="admi-menu">
        <button type="button" data-admi="perm">PERMISOS</button>
        <button type="button" data-admi="usua">USUARIOS</button>
        <button type="button" data-admi="tiend">TIENDAS</button>
    </div>

</section>
