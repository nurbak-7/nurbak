<?php
/* ============================================================
   Ruta: vista/categ/venta/clien/opera.php
   Módulo: Ventas / Operaciones / Cliente

   RESPONSABILIDAD:
   Mostrar y seleccionar los datos del cliente.
============================================================ */
?>

<!-- ========================================================
     DATOS DEL CLIENTE
========================================================= -->
<div class="opera-bloque opera-cliente">

    <div class="opera-fila">
        <label>RUC/DNI:</label>
        <input
            type="text"
            data-opera-docum
            placeholder="Ingrese RUC o DNI"
            autocomplete="off"
        >

        <label>RAZÓN SOCIAL:</label>
        <input
            type="text"
            data-opera-razon
            autocomplete="off"
        >
    </div>

    <div class="opera-fila">
        <label>DIRECCIÓN:</label>
        <input
            type="text"
            data-opera-direc
            autocomplete="off"
        >
    </div>

    <!-- Cliente real utilizado por el backend -->
    <select data-opera-clien required hidden>
        <option value="">Cliente *</option>
    </select>

</div>
