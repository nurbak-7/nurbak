<?php
/* ============================================================
   Ruta: vista/categ/catal/imext/expor.php
   Módulo: Catálogo / ASINC / IMEXT / Exportación

   Función:
   Solicitar la exportación integral del catálogo.

   FORMATO:
   - XLSX.
   - Una hoja por MARCA.
   - MARCA se registra en Vehículos y organiza las hojas del intercambio Excel.
============================================================ */
declare(strict_types=1);
?>

<section class="expo" data-expo>

    <div class="expo-cab">

        <div class="expo-tipo">
            <span>FORMATO</span>
            <strong>CATÁLOGO INTEGRAL XLSX</strong>
        </div>

        <div class="expo-busc">
            <label for="expo-busc">
                FILTRO
            </label>

            <input
                id="expo-busc"
                type="search"
                placeholder="Marca, motor, modelo, descripción, código..."
                autocomplete="off"
                data-expo-busc
            >
        </div>

        <button
            type="button"
            data-expo-enviar
        >
            EXPORTAR
        </button>

    </div>

    <p class="expo-info">
        Se genera una hoja por marca con la estructura integral de IMEXT.
    </p>

    <div
        class="expo-prog"
        data-expo-prog
        hidden
    >
        <strong data-expo-estado>
            Esperando...
        </strong>

        <div class="expo-barra">
            <span data-expo-barra></span>
        </div>

        <small data-expo-detalle></small>
    </div>

    <p
        class="expo-msg"
        data-expo-msg
    ></p>

</section>
