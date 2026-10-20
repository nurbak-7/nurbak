/* ============================================================
   Ruta: publi/asset/js/categ/catal/imext/impor.js
   Módulo: Catálogo / ASINC / IMEXT / IMPORT

   Función:
   Coordinar la interfaz de importación integral.

   NIVEL 1:
   - Crear importación.
   - Consultar proceso.
   - Mostrar progreso.
   - Mostrar errores persistidos.
============================================================ */

(() => {

    /* ========================================================
       NIVEL 1 — RUTA
    ======================================================== */

    const R =
        'modul/categ/catal/asinc/imext/impor/impor.php';


    /* ========================================================
       NIVEL 1 — INICIO
    ======================================================== */

    const iniciar = ventana => {

        const root =
            ventana?.querySelector?.('[data-impo]');

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

        const form =
            root.querySelector('[data-impo-form]');

        const modo =
            root.querySelector('[data-impo-modo]');

        const tiend =
            root.querySelector('[data-impo-tiend]');

        const arch =
            root.querySelector('[data-impo-arch]');

        const prog =
            root.querySelector('[data-impo-prog]');

        const estado =
            root.querySelector('[data-impo-estado]');

        const barra =
            root.querySelector('[data-impo-barra]');

        const detalle =
            root.querySelector('[data-impo-detalle]');

        const msg =
            root.querySelector('[data-impo-msg]');

        const erro =
            root.querySelector('[data-impo-erro]');

        const erroLista =
            root.querySelector('[data-impo-erro-lista]');


        /* ====================================================
           NIVEL 1 — CARGAR TIENDAS
        ==================================================== */

        const cargarTiendas = async () => {

            if (!tiend) {
                return;
            }

            const r = await fetch(
                `${R}?accion=tiendas`,
                {
                    cache: 'no-store'
                }
            );

            const d = await r.json();

            if (!r.ok || !d.ok) {
                throw new Error(
                    d.mensaje ||
                    'No se pudieron cargar las tiendas.'
                );
            }

            tiend.replaceChildren();

            const tiendas = d.tiendas || [];

            if (!tiendas.length) {
                const vacio =
                    document.createElement('option');

                vacio.value = '';
                vacio.textContent =
                    'Sin tiendas disponibles';
                vacio.disabled = true;
                tiend.appendChild(vacio);
                return;
            }

            tiendas.forEach(item => {

                const op =
                    document.createElement('option');

                op.value = String(item.id);

                op.textContent =
                    `${item.nombr} · ${item.codig}`;

                tiend.appendChild(op);
            });
        };


        /* ====================================================
           NIVEL 1 — CONSULTAR PROCESO
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

            return d;
        };


        /* ====================================================
           NIVEL 1 — LIMPIAR ERRORES
        ==================================================== */

        const limpiarErrores = () => {

            if (erroLista) {
                erroLista.replaceChildren();
            }

            if (erro) {
                erro.hidden = true;
            }
        };


        /* ====================================================
           NIVEL 1 — MOSTRAR ERRORES
        ==================================================== */

        const mostrarErrores = errores => {

            if (
                !erro ||
                !erroLista
            ) {
                return;
            }

            erroLista.replaceChildren();

            if (
                !Array.isArray(errores) ||
                errores.length === 0
            ) {
                erro.hidden = true;
                return;
            }


            errores.forEach(error => {

                const tr =
                    document.createElement('tr');

                const tdCodigo =
                    document.createElement('td');

                const tdMens =
                    document.createElement('td');

                const tdLote =
                    document.createElement('td');

                const tdFila =
                    document.createElement('td');


                tdCodigo.textContent =
                    error.codigo || 'ERROR';

                tdMens.textContent =
                    error.mens || 'Sin mensaje.';

                tdLote.textContent =
                    error.lote_id ?? '';

                tdFila.textContent =
                    error.fila ?? '';


                tr.append(
                    tdCodigo,
                    tdMens,
                    tdLote,
                    tdFila
                );

                erroLista.appendChild(tr);
            });


            erro.hidden = false;
        };


        /* ====================================================
           NIVEL 1 — VIGILAR PROCESO
        ==================================================== */

        const vigilar = async id => {

            prog.hidden = false;

            const limite = Date.now() + (330 * 1000);

            for (;;) {

                if (Date.now() >= limite) {
                    throw new Error(
                        'El proceso superó el tiempo máximo de espera. Revise el estado y los errores del proceso.'
                    );
                }

                const d =
                    await consultar(id);

                const p =
                    d.proceso || {};

                const errores =
                    d.errores || [];

                const porcentaje =
                    Number(p.progr || 0);


                /* ============================================
                   NIVEL 2 — ESTADO
                ============================================ */

                estado.textContent =
                    p.estado ||
                    'PROCESANDO';


                /* ============================================
                   NIVEL 2 — BARRA
                ============================================ */

                barra.style.width =
                    `${Math.max(
                        0,
                        Math.min(
                            100,
                            porcentaje
                        )
                    )}%`;


                /* ============================================
                   NIVEL 2 — DETALLE
                ============================================ */

                detalle.textContent =
                    `${p.proce || 0} procesados · ` +
                    `${p.corre || 0} correctos · ` +
                    `${p.erro || 0} errores`;


                /* ============================================
                   NIVEL 2 — ERRORES
                ============================================ */

                mostrarErrores(errores);


                /* ============================================
                   NIVEL 2 — FIN DEL PROCESO
                ============================================ */

                if (
                    p.estado === 'COMPLETADO' ||
                    p.estado === 'COMPLETADO_ERROR' ||
                    p.estado === 'ERROR'
                ) {
                    if (p.mens) {
                        msg.textContent = String(p.mens);
                    }

                    if (p.estado === 'ERROR' && !p.mens) {
                        msg.textContent =
                            'El proceso terminó con un error.';
                    }

                    break;
                }


                await new Promise(
                    resolve =>
                        setTimeout(resolve, 1000)
                );
            }
        };


        /* ====================================================
           NIVEL 1 — PREPARAR CONTEXTO
        ==================================================== */

        cargarTiendas().catch(error => {
            if (tiend) {
                tiend.replaceChildren();
            }

            msg.textContent = error.message;
        });


        /* ====================================================
           NIVEL 1 — CREAR IMPORTACIÓN
        ==================================================== */

        form.addEventListener(
            'submit',
            async evento => {

                evento.preventDefault();


                /* ============================================
                   NIVEL 2 — LIMPIAR RESULTADO ANTERIOR
                ============================================ */

                limpiarErrores();


                /* ============================================
                   NIVEL 2 — VALIDAR TIENDA
                ============================================ */

                if (!tiend?.value) {

                    msg.textContent =
                        'Seleccione una tienda.';

                    return;
                }


                /* ============================================
                   NIVEL 2 — VALIDAR ARCHIVO
                ============================================ */

                if (!arch.files.length) {

                    msg.textContent =
                        'Seleccione un archivo XLSX.';

                    return;
                }


                const archivo =
                    arch.files[0];


                if (
                    !archivo.name
                        .toLowerCase()
                        .endsWith('.xlsx')
                ) {

                    msg.textContent =
                        'Solo se acepta un archivo XLSX.';

                    return;
                }


                /* ============================================
                   NIVEL 2 — DATOS
                ============================================ */

                const datos =
                    new FormData();

                datos.append(
                    'accion',
                    'crear'
                );

                datos.append(
                    'modo',
                    modo?.value || 'AMBOS'
                );

                datos.append(
                    'tiend',
                    tiend.value
                );

                datos.append(
                    'archivo',
                    archivo
                );


                msg.textContent =
                    'Validando archivo XLSX...';


                try {

                    /* ========================================
                       NIVEL 3 — CREAR
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
                            'No se pudo crear el proceso.'
                        );
                    }


                    const id = Number(d.id);

                    if (!Number.isSafeInteger(id) || id < 1) {
                        throw new Error(
                            'SQL no devolvió un ID válido para el proceso.'
                        );
                    }

                    if (typeof d.estado !== 'string' || !d.estado) {
                        throw new Error(
                            'SQL no confirmó el estado del proceso creado.'
                        );
                    }

                    msg.textContent =
                        `Proceso SQL #${id} · ${d.estado}. Consultando estado real…`;


                    /* ========================================
                       NIVEL 3 — VIGILAR
                    ======================================== */

                    await vigilar(id);

                } catch (error) {

                    if (estado) {
                        estado.textContent = 'CONSULTA INTERRUMPIDA';
                    }

                    if (barra) {
                        barra.style.width = '0%';
                    }

                    msg.textContent = error instanceof Error
                        ? error.message
                        : 'No se pudo completar la consulta del proceso.';
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