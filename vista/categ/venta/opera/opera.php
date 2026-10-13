<?php
/* ============================================================
   Ruta: vista/categ/venta/opera/oper.php
   Módulo: Ventas / Operaciones / Operación

   RESPONSABILIDAD:
   Mostrar los datos propios de la operación.

   CAMPOS:
   - Vendedor
   - Placa
   - Almacén
============================================================ */
?>

<!-- =========================================================
     BLOQUE OPERACIÓN
========================================================== -->

<div class="opera-bloque opera-operacion">

    <!-- =====================================================
         VENDEDOR
         Un solo input: se escribe el código y el mismo campo
         muestra automáticamente nombr + primer apellido.
    ====================================================== -->

    <div class="opera-fila">

        <label>VENDEDOR:</label>

        <input
            type="text"
            data-opera-vendedor
            placeholder="Ingresar código"
            maxlength="20"
            autocomplete="off"
        >

    </div>

    <!-- =====================================================
         PLACA
    ====================================================== -->

    <div class="opera-fila">

        <label>PLACA:</label>

        <input
            type="text"
            data-opera-placa
            placeholder="Ingresar"
            autocomplete="off"
         maxlength="7">

    </div>

    <!-- =====================================================
         ALMACÉN
    ====================================================== -->

    <div class="opera-fila">

        <label>ALMACÉN:</label>

        <select
            data-opera-almacen
            required
        >
        </select>

    </div>

</div>
