<?php
/* ============================================================
   Ruta: vista/categ/venta/opera/pagos.php
   Módulo: Ventas / Operaciones / Pago

   RESPONSABILIDAD:
   Mostrar forma y medio de pago.
============================================================ */
?>

<div class="opera-pago">

    <div class="opera-pago-campo">

        <label>FORMA DE PAGO:</label>

        <select data-opera-forma-pago>
            <option value="CONTADO">Contado</option>
            <option value="CREDITO">Crédito</option>
        </select>

    </div>


    <div class="opera-pago-campo">

        <label>MEDIO DE PAGO:</label>

        <input
            type="text"
            data-opera-medio-pago
            placeholder="Yape"
            autocomplete="off"
        >

        <div
            class="opera-medio-sugerencias"
            data-opera-medio-sugerencias
        ></div>

    </div>

</div>