<?php
/* ============================================================
   Ruta: vista/categ/venta/opera/docum.php
   Módulo: Ventas / Operaciones / Documento

   RESPONSABILIDAD:
   Mostrar los datos básicos del docum.

   FECHA:
   La fecha visible corresponde a la fecha de operación.
   Por defecto utiliza la fecha actual.

   La fecha de emisión se obtiene internamente
   al momento de generar la operación.
============================================================ */
?>

<div class="opera-bloque opera-docum">

    <!-- ======================================================
         FECHA DE OPERACIÓN
    ======================================================= -->

    <div class="opera-fila">

        <label>FECHA:</label>

        <input
            type="date"
            data-opera-fecha
        >

    </div>


    <!-- ======================================================
         TIPO
    ======================================================= -->

    <div class="opera-fila">

        <label>TIPO:</label>

        <select
            data-opera-tipo
            disabled
        >
            <option value="BOLETA" selected>BOLETA</option>
            <option value="FACTURA">FACTURA</option>
        </select>

    </div>


    <!-- ======================================================
         SERIE / NÚMERO
    ======================================================= -->

    <div class="opera-fila">

        <label>SERIE / N°:</label>

        <input
            type="text"
            data-opera-serie
            readonly
        >

        <input
            type="text"
            data-opera-numer
            readonly
        >

    </div>

</div>