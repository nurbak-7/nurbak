/* ============================================================
   Ruta: publi/asset/js/categ/catal/imext/expor.js
   Módulo: Catálogo / ASINC / IMEXT / EXPORT

   Función:
   Coordinar la exportación integral XLSX.

   FORMATO:
   - Una hoja por marca.
   - 15 columnas de IMEXT.
============================================================ */

(() => {

    /* ========================================================
       NIVEL 1 — RUTA
    ======================================================== */

    const R =
        'modul/categ/catal/asinc/imext/expor/expor.php';


    /* ========================================================
       NIVEL 1 — INICIO
    ======================================================== */

    const iniciar = ventana => {

        const root =
            ventana?.querySelector?.('[data-expo]');

        if (
            !root ||
            root.dataset.iniciado === 'si'
        ) {
            return;
        }

        root.dataset.iniciado = 'si';


        /* ====================================================
           NIVEL 2 — ELEMENTOS
        ==================================================== */

        const busc =
            root.querySelector('[data-expo-busc]');

        const boton =
            root.querySelector('[data-expo-enviar]');

        const prog =
            root.querySelector('[data-expo-prog]');

        const estado =
            root.querySelector('[data-expo-estado]');

        const barra =
            root.querySelector('[data-expo-barra]');

        const detalle =
            root.querySelector('[data-expo-detalle]');

        const msg =
            root.querySelector('[data-expo-msg]');


        /* ====================================================
           NIVEL 1 — CONSULTAR
        ==================================================== */

        const consultar = async id => {

            const r = await fetch(
                `${R}?accion=estado&id=${encodeURIComponent(id)}`,
                {
                    cache: 'no-store'
                }
            );

            const d = await r.json();

            if (!r.ok || !d.ok) {
                throw new Error(
                    d.mensaje ||
                    'No se pudo consultar el proceso.'
                );
            }

            return d.proceso;
        };


        /* ====================================================
           NIVEL 1 — VIGILAR
        ==================================================== */

        const vigilar = async id => {

            prog.hidden = false;

            const limite =
                Date.now() + (330 * 1000);

            for (;;) {

                if (Date.now() >= limite) {
                    throw new Error(
                        'El proceso superó el tiempo máximo de espera.'
                    );
                }

                const p =
                    await consultar(id);

                const porcentaje =
                    Number(p.progr || 0);

                estado.textContent =
                    p.estado || 'PROCESANDO';

                barra.style.width =
                    `${Math.max(
                        0,
                        Math.min(
                            100,
                            porcentaje
                        )
                    )}%`;

                detalle.textContent =
                    `${p.proce || 0} procesados de ` +
                    `${p.total || 0}`;

                if (
                    p.estado === 'COMPLETADO' ||
                    p.estado === 'COMPLETADO_ERROR' ||
                    p.estado === 'ERROR'
                ) {
                    return p;
                }

                await new Promise(
                    resolve =>
                        setTimeout(resolve, 1000)
                );
            }
        };


        /* ====================================================
           NIVEL 1 — EXPORTAR
        ==================================================== */

        boton.addEventListener(
            'click',
            async () => {

                boton.disabled = true;

                msg.textContent =
                    'Creando Excel integral...';

                try {

                    const datos =
                        new URLSearchParams();

                    datos.set(
                        'accion',
                        'crear'
                    );

                    datos.set(
                        'buscar',
                        busc?.value.trim() || ''
                    );


                    /* ========================================
                       NIVEL 2 — CREAR PROCESO
                    ======================================== */

                    const r =
                        await fetch(
                            R,
                            {
                                method: 'POST',
                                body: datos,
                                cache: 'no-store'
                            }
                        );

                    const d =
                        await r.json();

                    if (!r.ok || !d.ok) {
                        throw new Error(
                            d.mensaje ||
                            'No se pudo crear la exportación.'
                        );
                    }


                    /* ========================================
                       NIVEL 2 — VIGILAR
                    ======================================== */

                    const p =
                        await vigilar(d.id);


                    /* ========================================
                       NIVEL 2 — RESULTADO
                    ======================================== */

                    if (
                        p.estado === 'COMPLETADO'
                        || p.estado === 'COMPLETADO_ERROR'
                    ) {

                        const enlace =
                            document.createElement('a');

                        enlace.href =
                            `${R}?accion=descargar&id=` +
                            encodeURIComponent(d.id);

                        enlace.textContent =
                            'DESCARGAR EXCEL';

                        enlace.className =
                            'expo-descarga';

                        msg.replaceChildren(enlace);

                        if (
                            p.estado === 'COMPLETADO_ERROR'
                        ) {
                            msg.append(
                                document.createTextNode(
                                    ' — terminado con errores.'
                                )
                            );
                        }

                    } else {

                        msg.textContent =
                            'La exportación terminó con error.';
                    }

                } catch (error) {

                    msg.textContent =
                        error.message;

                } finally {

                    boton.disabled = false;
                }
            }
        );
    };


    /* ========================================================
       NIVEL 1 — EVENTO IMEXT
    ======================================================== */

    document.addEventListener(
        'imext:cargado',
        evento => {
            iniciar(
                evento.detail?.ventana
            );
        }
    );

})();
