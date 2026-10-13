/* ============================================================
   Ruta: publi/asset/js/categ/repor/infor/infor-anali.js
   Función: Recurso del sistema.
============================================================ */
/* ============================================================
   INFORMES
   Recorrido real por áreas
============================================================ */

let ventanaInformes = null;
let procesoInformesActivo = false;
let controladorInformes = null;
let observadorInformes = null;


/* ============================================================
   DETENER PROCESO
============================================================ */

const detenerInformes = () => {

    if (controladorInformes) {

        controladorInformes.abort();

        controladorInformes = null;
    }


    if (observadorInformes) {

        observadorInformes.disconnect();

        observadorInformes = null;
    }


    procesoInformesActivo = false;

    ventanaInformes = null;
};


/* ============================================================
   INICIAR INFORMES
============================================================ */

window.NURBAK_REPO_INFO_ANALISIS = {

    iniciar: (ventana) => {

    if (!ventana) return;


    detenerInformes();


    ventanaInformes = ventana;

    procesoInformesActivo = true;


    /* ========================================================
       ELEMENTOS
    ======================================================== */

    const porcentaje =
        ventana.querySelector(
            '#repoInfoPorcentaje'
        );


    const barra =
        ventana.querySelector(
            '#repoInfoBarra'
        );


    const linea =
        ventana.querySelector(
            '#repoInfoLinea'
        );


    const estad =
        ventana.querySelector(
            '#repoInfoEstado'
        );


    const erroresContador =
        ventana.querySelector(
            '#repoInfoErrores'
        );


    const erroresLista =
        ventana.querySelector(
            '#repoInfoErroresLista'
        );


    const resultado =
        ventana.querySelector(
            '#repoInfoResultado'
        );


    if (
        !linea ||
        !resultado
    ) {

        detenerInformes();

        return;
    }


    /* ========================================================
       DATOS DEL PROCESO
    ======================================================== */

    let errores = [];

    let fuentesResultado = {};


    const areas = [

        {
            nombr: 'catalogo',
            etiqueta: 'CATÁLOGO',
            inicio: 10,
            fin: 20
        },

        {
            nombr: 'inventario',
            etiqueta: 'INVENTARIO',
            inicio: 25,
            fin: 35
        },

        {
            nombr: 'compras',
            etiqueta: 'COMPRAS',
            inicio: 40,
            fin: 50
        },

        {
            nombr: 'ventas',
            etiqueta: 'VENTAS',
            inicio: 55,
            fin: 65
        },

        {
            nombr: 'facturacion',
            etiqueta: 'FACTURACIÓN',
            inicio: 70,
            fin: 80
        },

        {
            nombr: 'pagos',
            etiqueta: 'PAGOS',
            inicio: 85,
            fin: 95
        }
    ];


    /* ========================================================
       ESCAPAR HTML
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
       CAMBIAR PORCENTAJE Y LÍNEA
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
       ACTUALIZAR ESTADO
    ======================================================== */

    const actualizarEstado = (texto) => {

        if (estad) {

            estad.textContent =
                texto;
        }
    };


    /* ========================================================
       REGISTRAR ERROR
    ======================================================== */

    const registrarError = (error) => {

        errores.push(error);


        if (erroresContador) {

            erroresContador.textContent =
                errores.length;
        }
    };


    /* ========================================================
       MOSTRAR ERRORES FINALES
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

            <div class="repo-info-errores-titulo">
                DETALLE DE ERRORES
            </div>

            ${errores.map(
                (error, indice) => {

                    const fuente =
                        escapar(
                            error.fuente || 'Área'
                        );

                    const tabla =
                        error.tabla
                            ? ` → ${escapar(error.tabla)}`
                            : '';

                    const mensaje =
                        escapar(
                            error.mensaje ||
                            'Error no especificado.'
                        );


                    return `
                        <div class="repo-info-error">

                            <strong>
                                ${indice + 1}.
                                ${fuente}${tabla}
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
       MOSTRAR RESULTADOS
    ======================================================== */

    const mostrarResultados = () => {

        const nombres = {

            catalogo: 'Catálogo',

            inventario: 'Inventario',

            compras: 'Compras',

            ventas: 'Ventas',

            facturacion: 'Facturación',

            pagos: 'Pagos'
        };


        resultado.innerHTML =
            Object.entries(
                fuentesResultado
            )
            .map(
                ([fuente, datos]) => {

                    return `

                        <div class="repo-info-fuente">

                            <h3>
                                ${escapar(
                                    nombres[fuente] ||
                                    fuente
                                )}
                            </h3>

                            <ul>

                                ${
                                    Object.entries(
                                        datos || {}
                                    )
                                    .map(
                                        ([tabla, canti]) => {

                                            return `
                                                <li>

                                                    ${escapar(tabla)}:

                                                    <strong>

                                                        ${
                                                            canti === null
                                                                ? 'Sin datos'
                                                                : escapar(canti)
                                                        }

                                                    </strong>

                                                </li>
                                            `;
                                        }
                                    )
                                    .join('')
                                }

                            </ul>

                        </div>
                    `;
                }
            )
            .join('');
    };


    /* ========================================================
       OBSERVAR CIERRE DE VENTANA
    ======================================================== */

    observadorInformes =
        new MutationObserver(() => {

            if (
                procesoInformesActivo &&
                !document.body.contains(ventana)
            ) {

                detenerInformes();
            }
        });


    observadorInformes.observe(
        document.body,
        {
            childList: true,
            subtree: true
        }
    );


    /* ========================================================
       CONSULTAR UNA ÁREA REAL
    ======================================================== */

    const consultarArea = async (area) => {

        if (!procesoInformesActivo) {

            return null;
        }


        actualizarProceso(
            area.inicio,
            `INGRESANDO A ${area.etiqueta}`
        );


        actualizarEstado(
            `CONSULTANDO ${area.etiqueta}...`
        );


        controladorInformes =
            new AbortController();


        try {

            const url =
                `modul/categ/repor/infor/infor-contr.php?accion=area&area=${encodeURIComponent(area.nombr)}`;


            const respuesta =
                await fetch(
                    url,
                    {
                        cache: 'no-store',
                        signal:
                            controladorInformes.signal
                    }
                );


            if (!respuesta.ok) {

                throw new Error(
                    `Error HTTP ${respuesta.status}.`
                );
            }


            const data =
                await respuesta.json();


            if (!data.ok) {

                throw new Error(
                    data.mensaje ||
                    'No se pudo procesar el área.'
                );
            }


            /* ================================================
               GUARDAR DATOS DEL ÁREA
            ================================================= */

            fuentesResultado[area.nombr] =
                data.datos || {};


            /* ================================================
               GUARDAR ERRORES DEL ÁREA
            ================================================= */

            if (
                Array.isArray(data.errores)
            ) {

                data.errores.forEach(
                    registrarError
                );
            }


            /* ================================================
               MOSTRAR RESULTADO DEL ÁREA
            ================================================= */

            if (
                Array.isArray(data.errores) &&
                data.errores.length
            ) {

                actualizarProceso(
                    area.fin,
                    `${area.etiqueta} FINALIZADO CON ERRORES`,
                    'error'
                );

            } else {

                actualizarProceso(
                    area.fin,
                    `${area.etiqueta} FINALIZADO`
                );
            }


            actualizarEstado(
                `${area.etiqueta} FINALIZADO`
            );


            return data;


        } catch (error) {

            /*
             * Si la ventana se cerró,
             * no registrar nada.
             */

            if (!procesoInformesActivo) {

                return null;
            }


            const errorArea = {

                fuente:
                    area.etiqueta,

                tabla:
                    null,

                etiqueta:
                    null,

                tipo:
                    error.name,

                mensaje:
                    error.name === 'AbortError'
                        ? 'Consulta cancelada.'
                        : error.message
            };


            registrarError(
                errorArea
            );


            actualizarProceso(
                area.fin,
                `${area.etiqueta} → ERROR: ${error.message}`,
                'error'
            );


            actualizarEstado(
                `${area.etiqueta} FINALIZADO CON ERROR`
            );


            /*
             * MUY IMPORTANTE:
             *
             * No hacemos throw.
             *
             * El proceso continúa con la siguiente área.
             */

            return null;


        } finally {

            controladorInformes = null;
        }
    };


    /* ========================================================
       FINALIZAR INFORME
    ======================================================== */

    const finalizar = () => {

        if (
            !ventana ||
            !document.body.contains(ventana)
        ) {

            detenerInformes();

            return;
        }


        actualizarProceso(
            100,
            'INFORME FINALIZADO',
            'ok'
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


        mostrarErrores();

        mostrarResultados();


        procesoInformesActivo =
            false;


        controladorInformes = null;


        if (observadorInformes) {

            observadorInformes.disconnect();

            observadorInformes = null;
        }


        ventanaInformes = null;
    };


    /* ========================================================
       EJECUTAR RECORRIDO COMPLETO
    ======================================================== */

    const ejecutar = async () => {

        /* ====================================================
           000%
        ==================================================== */

        actualizarProceso(
            0,
            'INICIANDO PROCESO DE CONSULTA'
        );


        actualizarEstado(
            'INICIANDO...'
        );


        await new Promise(
            resolver =>
                setTimeout(
                    resolver,
                    300
                )
        );


        /* ====================================================
           RECORRER LAS ÁREAS UNA POR UNA
        ==================================================== */

        for (const area of areas) {

            if (!procesoInformesActivo) {

                return;
            }


            await consultarArea(area);


            if (!procesoInformesActivo) {

                return;
            }


            await new Promise(
                resolver =>
                    setTimeout(
                        resolver,
                        250
                    )
            );
        }


        /* ====================================================
           100%
        ==================================================== */

        finalizar();
    };


    ejecutar();
    }
};
