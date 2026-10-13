<?php
/* ============================================================
   Ruta: vista/categ/catal/imext/impor.php
   Módulo: Catálogo / ASINC / IMEXT / Importación

   Función:
   Vista interna para importar el catálogo integral.

   FORMATO:
   - Archivo XLSX.
   - Una hoja por MARCA.
   - Una fila contiene vehículo, producto, inventario y
     equivalencia.
============================================================ */
declare(strict_types=1);
?>

<section class="impo" data-impo>

    <div class="impo-cab">
        <div>
            <label for="impo-tiend">TIENDA</label>

            <select id="impo-tiend" data-impo-tiend required>
                
            </select>
        </div>

        <div>
            <label for="impo-modo">MODO</label>

            <select id="impo-modo" data-impo-modo>
                <option value="AMBOS">
                    NUEVOS + ACTUALIZAR
                </option>

                <option value="NUEVO">
                    SOLO NUEVOS
                </option>

                <option value="ACTUAL">
                    SOLO ACTUALIZAR
                </option>
            </select>
        </div>
    </div>


    <form class="impo-form" data-impo-form>

        <label class="impo-archivo">
            <span>ARCHIVO XLSX · HOJAS POR MARCA</span>

            <input
                type="file"
                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                data-impo-arch
                required
            >
        </label>

        <button
            type="submit"
            data-impo-enviar
        >
            IMPORTAR
        </button>

    </form>


    <div
        class="impo-prog"
        data-impo-prog
        hidden
    >

        <strong data-impo-estado>
            Esperando...
        </strong>

        <div class="impo-barra">
            <span data-impo-barra></span>
        </div>

        <small data-impo-detalle></small>

    </div>


    <!-- ========================================================
         NIVEL 1 — MENSAJE DEL PROCESO
    ========================================================= -->

    <p
        class="impo-msg"
        data-impo-msg
    ></p>


    <!-- ========================================================
         NIVEL 1 — ERRORES DEL PROCESO
    ========================================================= -->

    <div
        class="impo-erro"
        data-impo-erro
        hidden
    >

        <strong class="impo-erro-titu">
            ERRORES
        </strong>

        <div class="impo-erro-lista">
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>MENSAJE</th>
                        <th>LOTE</th>
                        <th>FILA</th>
                    </tr>
                </thead>

                <tbody data-impo-erro-lista></tbody>
            </table>
        </div>

    </div>

</section>