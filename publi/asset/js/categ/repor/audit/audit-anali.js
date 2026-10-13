/* ============================================================
   Ruta: publi/asset/js/categ/repor/audit/audit-anali.js
   Módulo: Reportes / Auditoría

   RESPONSABILIDAD:

   - Ejecutar una consulta real.
   - Recorrer el proceso de Auditoría.
   - Mostrar el avance en porcentaje.
   - Mostrar una sola línea de proceso.
   - Registrar los errores encontrados.
   - Continuar el proceso aunque existan errores.
   - Finalizar siempre en 100%.
   - Mostrar el detalle de errores.
   - Mostrar el historial de auditoría.
   - Mostrar la información realmente revisada.
   - Permitir volver a abrir la ventana.
   - Detener el proceso si la ventana es cerrada.

   NO UTILIZA:
   - intentos
   - reintentos
   - setInterval()
============================================================ */


/* ============================================================
   1. ESTADO GENERAL
============================================================ */

let ventanaAuditoria = null;
let procesoAuditoriaActivo = false;
let controladorAuditoria = null;
let observadorAuditoria = null;


/* ============================================================
   2. DETENER PROCESO
============================================================ */

const detenerAuditoria = () => {

    if (controladorAuditoria) {

        controladorAuditoria.abort();

        controladorAuditoria = null;
    }


    if (observadorAuditoria) {

        observadorAuditoria.disconnect();

        observadorAuditoria = null;
    }


    procesoAuditoriaActivo = false;

    ventanaAuditoria = null;
};


/* ============================================================
   3. INICIAR AUDITORÍA
============================================================ */

window.NURBAK_REPO_AUDI_ANALISIS = {

    iniciar: (ventana) => {

    if (!ventana) {
        return;
    }


    detenerAuditoria();


    ventanaAuditoria = ventana;

    procesoAuditoriaActivo = true;


    /* ========================================================
       4. ELEMENTOS
    ======================================================== */

    const porcentaje =
        ventana.querySelector(
            '#repoAudiPorcentaje'
        );


    const barra =
        ventana.querySelector(
            '#repoAudiBarra'
        );


    const linea =
        ventana.querySelector(
            '#repoAudiLinea'
        );


    const estad =
        ventana.querySelector(
            '#repoAudiEstado'
        );


    const erroresContador =
        ventana.querySelector(
            '#repoAudiErrores'
        );


    const erroresLista =
        ventana.querySelector(
            '#repoAudiErroresLista'
        );


    const tabla =
        ventana.querySelector(
            '#repoAudiTabla'
        );


    const buscar =
        ventana.querySelector(
            '#repoAudiBuscar'
        );


    /* ========================================================
       INFORMACIÓN REVISADA
    ======================================================== */

    const repoEstado =
        ventana.querySelector(
            '#repoAudiRepoEstado'
        );


    const auditoriaEstado =
        ventana.querySelector(
            '#repoAudiAuditoriaEstado'
        );


    const audiEstado =
        ventana.querySelector(
            '#repoAudiAudiEstado'
        );


    const campoUsuario =
        ventana.querySelector(
            '#repoAudiCampoUsuario'
        );


    const campoModulo =
        ventana.querySelector(
            '#repoAudiCampoModulo'
        );


    const campoAccion =
        ventana.querySelector(
            '#repoAudiCampoAccion'
        );


    const campoReferencia =
        ventana.querySelector(
            '#repoAudiCampoReferencia'
        );


    const campoDetalle =
        ventana.querySelector(
            '#repoAudiCampoDetalle'
        );


    const campoFecha =
        ventana.querySelector(
            '#repoAudiCampoFecha'
        );


    if (
        !linea ||
        !tabla
    ) {

        detenerAuditoria();

        return;
    }


    /* ========================================================
       5. DATOS
    ======================================================== */

    let errores = [];

    let historialAuditoria = [];


    /* ========================================================
       6. ESCAPAR HTML
    ======================================================== */

    const escapar = (valor) => {

        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };


    /* ========================================================
       7. ESTADO DE INFORMACIÓN REVISADA
    ======================================================== */

    const estadoRevision = (
        elemento,
        correcto
    ) => {

        if (!elemento) {
            return;
        }


        elemento.textContent =
            correcto
                ? 'OK'
                : 'ERROR';


        elemento.style.color =
            correcto
                ? '#4caf50'
                : '#d66';
    };


    /* ========================================================
       8. ACTUALIZAR INFORMACIÓN REVISADA
    ======================================================== */

    const actualizarInformacionRevisada = (
        correcto
    ) => {

        /*
         * La consulta correcta demuestra que:
         *
         * REPO → conexión disponible.
         * AUDITORÍA → consulta ejecutada.
         * AUDI → tabla consultada.
         *
         * Los seis campos son los campos obtenidos
         * por la consulta del historial.
         */

        estadoRevision(
            repoEstado,
            correcto
        );


        estadoRevision(
            auditoriaEstado,
            correcto
        );


        estadoRevision(
            audiEstado,
            correcto
        );


        estadoRevision(
            campoUsuario,
            correcto
        );


        estadoRevision(
            campoModulo,
            correcto
        );


        estadoRevision(
            campoAccion,
            correcto
        );


        estadoRevision(
            campoReferencia,
            correcto
        );


        estadoRevision(
            campoDetalle,
            correcto
        );


        estadoRevision(
            campoFecha,
            correcto
        );
    };


    /* ========================================================
       9. ACTUALIZAR PROCESO
    ======================================================== */

    const actualizarProceso = (
        valor,
        mensaje,
        tipo = 'ok'
    ) => {

        if (porcentaje) {

            porcentaje.textContent =
                `${valor}%`;
        }


        if (barra) {

            barra.style.width =
                `${valor}%`;
        }


        const simbolo =
            tipo === 'error'
                ? '⚠'
                : '✓';


        linea.textContent =
            `[${String(valor).padStart(3, '0')}%] ${simbolo} ${mensaje}`;


        linea.style.color =
            tipo === 'error'
                ? '#ff9b9b'
                : '#8fda8f';
    };


    /* ========================================================
       10. ACTUALIZAR ESTADO
    ======================================================== */

    const actualizarEstado = (texto) => {

        if (estad) {

            estad.textContent =
                texto;
        }
    };


    /* ========================================================
       11. REGISTRAR ERROR
    ======================================================== */

    const registrarError = (error) => {

        errores.push(error);


        if (erroresContador) {

            erroresContador.textContent =
                errores.length;
        }
    };


    /* ========================================================
       12. MOSTRAR ERRORES
    ======================================================== */

    const mostrarErrores = () => {

        if (!erroresLista) {
            return;
        }


        if (!errores.length) {

            erroresLista.innerHTML = '';

            return;
        }


        erroresLista.innerHTML = `

            <div class="repo-audi-errores-titulo">
                DETALLE DE ERRORES
            </div>


            ${errores.map(
                (error, indice) => {

                    const fuente =
                        escapar(
                            error.fuente ||
                            'AUDITORÍA'
                        );


                    const tablaError =
                        error.tabla
                            ? ` → ${escapar(error.tabla)}`
                            : '';


                    const mensaje =
                        escapar(
                            error.mensaje ||
                            'Error no especificado.'
                        );


                    return `

                        <div class="repo-audi-error">

                            <strong>
                                ${indice + 1}.
                                ${fuente}${tablaError}
                            </strong>

                            <span>
                                ${mensaje}
                            </span>

                        </div>

                    `;
                }
            ).join('')}

        `;
    };


    /* ========================================================
       13. MOSTRAR HISTORIAL
    ======================================================== */

    const mostrarHistorial = () => {

        if (!tabla) {
            return;
        }


        if (!historialAuditoria.length) {

            tabla.innerHTML = `

                <tr>

                    <td colspan="6">
                        Sin registros de auditoría.
                    </td>

                </tr>

            `;

            return;
        }


        tabla.innerHTML =
            historialAuditoria.map(x => `

                <tr>

                    <td>
                        ${escapar(x.fecha)}
                    </td>

                    <td>
                        ${escapar(x.usuar)}
                    </td>

                    <td>
                        ${escapar(x.modulo)}
                    </td>

                    <td>
                        ${escapar(x.accion)}
                    </td>

                    <td>
                        ${escapar(x.referencia)}
                    </td>

                    <td>
                        ${escapar(x.detalle)}
                    </td>

                </tr>

            `).join('');
    };


    /* ========================================================
       14. OBSERVAR CIERRE DE VENTANA
    ======================================================== */

    observadorAuditoria =
        new MutationObserver(() => {

            if (
                procesoAuditoriaActivo &&
                !document.body.contains(ventana)
            ) {

                detenerAuditoria();
            }
        });


    observadorAuditoria.observe(
        document.body,
        {
            childList: true,
            subtree: true
        }
    );


    /* ========================================================
       15. CONSULTA REAL
    ======================================================== */

    const consultarAuditoria = async () => {

        if (!procesoAuditoriaActivo) {
            return null;
        }


        /* ----------------------------------------------------
           25%
        ---------------------------------------------------- */

        actualizarProceso(
            25,
            'PREPARANDO CONSULTA DE AUDITORÍA'
        );


        actualizarEstado(
            'PREPARANDO...'
        );


        await new Promise(
            resolver =>
                setTimeout(
                    resolver,
                    300
                )
        );


        if (!procesoAuditoriaActivo) {
            return null;
        }


        /* ----------------------------------------------------
           50%
        ---------------------------------------------------- */

        actualizarProceso(
            50,
            'CONSULTANDO REGISTROS DE AUDITORÍA'
        );


        actualizarEstado(
            'CONSULTANDO AUDITORÍA...'
        );


        controladorAuditoria =
            new AbortController();


        try {

            const q =
                encodeURIComponent(
                    buscar?.value.trim() || ''
                );


            const url =
                `modul/categ/repor/audit/audit-contr.php?accion=listar&buscar=${q}&limite=50`;


            const respuesta =
                await fetch(
                    url,
                    {
                        cache: 'no-store',
                        signal:
                            controladorAuditoria.signal
                    }
                );


            /* ------------------------------------------------
               Leer JSON aunque HTTP sea 500.
            ------------------------------------------------ */

            let data = null;


            try {

                data =
                    await respuesta.json();

            } catch (error) {

                throw new Error(
                    `Respuesta inválida del servidor. HTTP ${respuesta.status}.`
                );
            }


            /* ------------------------------------------------
               ERROR DEL BACKEND
            ------------------------------------------------ */

            if (!data.ok) {

                const errorBackend =
                    data.errores?.[0] || {

                        fuente: 'AUDITORÍA',

                        mensaje:
                            data.mensaje ||
                            `Error HTTP ${respuesta.status}.`
                    };


                registrarError(
                    errorBackend
                );


                actualizarInformacionRevisada(
                    false
                );


                actualizarProceso(
                    50,
                    'AUDITORÍA CONSULTADA CON ERROR',
                    'error'
                );


                actualizarEstado(
                    'FINALIZADO CON ERROR'
                );


                return {
                    auditoria: []
                };
            }


            /* ------------------------------------------------
               CONSULTA CORRECTA
            ------------------------------------------------ */

            historialAuditoria =
                Array.isArray(data.auditoria)
                    ? data.auditoria
                    : [];


            /*
             * La respuesta correcta demuestra que la fuente
             * REPO y la tabla AUDI fueron consultadas.
             */

            actualizarInformacionRevisada(
                true
            );


            /* ------------------------------------------------
               ERRORES ADICIONALES
            ------------------------------------------------ */

            if (
                Array.isArray(data.errores)
            ) {

                data.errores.forEach(
                    registrarError
                );
            }


            actualizarProceso(
                75,
                errores.length
                    ? 'AUDITORÍA PROCESADA CON ERRORES'
                    : 'PROCESANDO REGISTROS DE AUDITORÍA',
                errores.length
                    ? 'error'
                    : 'ok'
            );


            actualizarEstado(
                errores.length
                    ? 'PROCESADO CON ERRORES'
                    : 'PROCESANDO...'
            );


            return data;


        } catch (error) {

            /* ------------------------------------------------
               Ventana cerrada.
            ------------------------------------------------ */

            if (
                error.name === 'AbortError' ||
                !procesoAuditoriaActivo
            ) {

                return null;
            }


            const errorAuditoria = {

                fuente: 'AUDITORÍA',

                tabla: null,

                etiqueta: null,

                tipo:
                    error.name,

                mensaje:
                    error.message
            };


            registrarError(
                errorAuditoria
            );


            actualizarInformacionRevisada(
                false
            );


            actualizarProceso(
                75,
                `AUDITORÍA → ERROR: ${error.message}`,
                'error'
            );


            actualizarEstado(
                'FINALIZADO CON ERROR'
            );


            return null;


        } finally {

            controladorAuditoria = null;
        }
    };


    /* ========================================================
       16. FINALIZAR
    ======================================================== */

    const finalizar = () => {

        if (
            !ventana ||
            !document.body.contains(ventana)
        ) {

            detenerAuditoria();

            return;
        }


        mostrarErrores();

        mostrarHistorial();


        actualizarProceso(
            100,
            'AUDITORÍA FINALIZADA'
        );


        actualizarEstado(
            errores.length
                ? 'FINALIZADO CON ERRORES'
                : 'FINALIZADO'
        );


        if (erroresContador) {

            erroresContador.textContent =
                errores.length;
        }


        procesoAuditoriaActivo =
            false;


        controladorAuditoria =
            null;


        if (observadorAuditoria) {

            observadorAuditoria.disconnect();

            observadorAuditoria = null;
        }


        ventanaAuditoria = null;
    };


    /* ========================================================
       17. EJECUTAR PROCESO
    ======================================================== */

    const ejecutar = async () => {

        actualizarProceso(
            0,
            'INICIANDO PROCESO DE AUDITORÍA'
        );


        actualizarEstado(
            'INICIANDO...'
        );


        /*
         * Antes de iniciar la consulta real, los elementos
         * de información todavía no están confirmados.
         */

        actualizarInformacionRevisada(
            false
        );


        await new Promise(
            resolver =>
                setTimeout(
                    resolver,
                    300
                )
        );


        if (!procesoAuditoriaActivo) {
            return;
        }


        await consultarAuditoria();


        if (!procesoAuditoriaActivo) {
            return;
        }


        await new Promise(
            resolver =>
                setTimeout(
                    resolver,
                    350
                )
        );


        if (!procesoAuditoriaActivo) {
            return;
        }


        finalizar();
    };


    /* ========================================================
       18. EJECUTAR
    ======================================================== */

    ejecutar();
    }
};
