<?php
/* ============================================================
   Ruta: vista/categ/repor/infor.php
   Módulo: Reportes / Informes
============================================================ */
?>

<section
    class="modu-vista repo-info-vista"
    data-vista="informes"
>

    <h2>Informes</h2>

    <p>
        Resumen de datos de los módulos operativos.
    </p>


    <!-- ======================================================
         PROCESO
    ======================================================= -->

    <div class="repo-info-proceso">

        <div class="repo-info-cabecera">

            <span>
                INFORME DEL SISTEMA
            </span>

            <span id="repoInfoPorcentaje">
                0%
            </span>

        </div>


        <div class="repo-info-barra">

            <div
                id="repoInfoBarra"
                class="repo-info-barra-progreso">
            </div>

        </div>


        <!-- ==================================================
             UNA SOLA LÍNEA DE ESTADO
        =================================================== -->

        <div
            id="repoInfoTerminal"
            class="repo-info-terminal"
            aria-live="polite"
        >
            <span id="repoInfoLinea">
                [000%] ✓ INICIANDO PROCESO DE CONSULTA
            </span>
        </div>


        <div class="repo-info-resumen">

            <span id="repoInfoEstado">
                INICIANDO...
            </span>

            <span>
                ERRORES:
                <strong id="repoInfoErrores">
                    0
                </strong>
            </span>

        </div>

    </div>


    <!-- ======================================================
         ERRORES FINALES
    ======================================================= -->

    <div
        id="repoInfoErroresLista"
        class="repo-info-errores"
    >
    </div>


    <!-- ======================================================
         RESULTADOS
    ======================================================= -->

    <div
        id="repoInfoResultado"
        class="repo-info-resultado"
    >
    </div>

</section>