<?php
/* ============================================================
   Ruta: vista/categ/repor/audit.php
   Módulo: Reportes / Auditoría
============================================================ */
?>

<section class="modu-vista repo-audi-vista" data-vista="auditoria">

    <h2>Auditoría</h2>
    <p>Registro de acciones realizadas en el sistema.</p>


    <!-- ======================================================
         PROCESO + INFORMACIÓN REVISADA
    ======================================================= -->

    <div class="repo-audi-proceso">


        <!-- ==================================================
             NIVEL 1 — PROCESO
        =================================================== -->

        <div class="repo-audi-proceso-col">

            <div class="repo-audi-cabecera">
                <span>AUDITORÍA DEL SISTEMA</span>
                <span id="repoAudiPorcentaje">0%</span>
            </div>

            <div class="repo-audi-barra">
                <div id="repoAudiBarra" class="repo-audi-barra-progreso"></div>
            </div>

            <div id="repoAudiTerminal"
                 class="repo-audi-terminal"
                 aria-live="polite">

                <span id="repoAudiLinea">
                    [000%] ✓ INICIANDO PROCESO DE AUDITORÍA
                </span>

            </div>

            <div class="repo-audi-resumen">
                <span id="repoAudiEstado">INICIANDO...</span>

                <span>
                    ERRORES:
                    <strong id="repoAudiErrores">0</strong>
                </span>
            </div>

        </div>


        <!-- ==================================================
             NIVEL 2 — INFORMACIÓN REVISADA
        =================================================== -->

        <div class="repo-audi-revisado">

            <div class="repo-audi-revisado-titulo">
                INFORMACIÓN REVISADA
            </div>


            <!-- ==============================================
                 NIVEL 3 — ESTRUCTURA REPO → AUDITORÍA → AUDI
            =============================================== -->

            <div class="repo-audi-arbol-niveles">


                <!-- REPO -->

                <div class="repo-audi-arbol-fuente">

                    <span>REPO</span>

                    <span id="repoAudiRepoEstado"
                          class="repo-audi-ok">
                        ...
                    </span>

                </div>


                <!-- AUDITORÍA -->

                <div class="repo-audi-arbol-nivel">

                    <span class="repo-audi-rama">└──</span>

                    <span>AUDITORÍA</span>

                    <span id="repoAudiAuditoriaEstado"
                          class="repo-audi-ok">
                        ...
                    </span>

                </div>


                <!-- AUDI -->

                <div class="repo-audi-arbol-nivel">

                    <span class="repo-audi-rama">└──</span>

                    <span>AUDI</span>

                    <span id="repoAudiAudiEstado"
                          class="repo-audi-ok">
                        ...
                    </span>

                </div>

            </div>


            <!-- ==============================================
                 NIVEL 4 — CAMPOS REVISADOS
            =============================================== -->

            <div class="repo-audi-arbol-campos">

                <div>
                    <span>Usuario</span>
                    <span id="repoAudiCampoUsuario"
                          class="repo-audi-ok">...</span>
                </div>

                <div>
                    <span>Módulo</span>
                    <span id="repoAudiCampoModulo"
                          class="repo-audi-ok">...</span>
                </div>

                <div>
                    <span>Acción</span>
                    <span id="repoAudiCampoAccion"
                          class="repo-audi-ok">...</span>
                </div>

                <div>
                    <span>Referencia</span>
                    <span id="repoAudiCampoReferencia"
                          class="repo-audi-ok">...</span>
                </div>

                <div>
                    <span>Detalle</span>
                    <span id="repoAudiCampoDetalle"
                          class="repo-audi-ok">...</span>
                </div>

                <div>
                    <span>Fecha</span>
                    <span id="repoAudiCampoFecha"
                          class="repo-audi-ok">...</span>
                </div>

            </div>

        </div>

    </div>


    <!-- ======================================================
         NIVEL 5 — DETALLE DE ERRORES
    ======================================================= -->

    <div id="repoAudiErroresLista"
         class="repo-audi-errores">
    </div>


    <!-- ======================================================
         NIVEL 6 — HISTORIAL DE AUDITORÍA
    ======================================================= -->

    <div class="repo-audi-historial">

        <div class="repo-audi-historial-titulo">
            HISTORIAL DE AUDITORÍA
        </div>


        <!-- BUSCADOR -->

        <div class="repo-audi-busqueda">

            <input type="search"
                   id="repoAudiBuscar"
                   placeholder="Buscar auditoría..."
                   autocomplete="off">

        </div>


        <!-- TABLA -->

        <div class="repo-audi-tabla-contenedor">

            <table>

                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Módulo</th>
                        <th>Acción</th>
                        <th>Referencia</th>
                        <th>Detalle</th>
                    </tr>
                </thead>

                <tbody id="repoAudiTabla">

                    <tr>
                        <td colspan="6">
                            Sin registros de auditoría.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</section>