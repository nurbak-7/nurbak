/* ============================================================
   Ruta: publi/asset/js/categ/compr/recep.js
   Módulo: Compras / Recepción
   Función: Preparar una recepción y enviarla al servidor.
============================================================ */

(() => {

    const R =
        'modul/categ/compr/recep/recep-contr.php';

    const esc = v => String(v ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const auth = async permiso => {

        if (!window.NURBAK_LOGI_AUT?.autorizar) {
            return false;
        }

        const res =
            await window.NURBAK_LOGI_AUT.autorizar(
                permiso
            );

        return res.autorizado === true;
    };

    const iniciar = c => {

        const r =
            c.matches?.('[data-rece]')
                ? c
                : c.querySelector?.('[data-rece]');

        if (
            !r ||
            r.dataset.iniciado === 'si'
        ) {
            return;
        }

        r.dataset.iniciado = 'si';

        const q = selector =>
            r.querySelector(selector);

        const b = q('[data-rece-busc]');
        const tb = q('[data-rece-cuerpo]');
        const msg = q('[data-rece-mensaje]');
        const nuevo = q('[data-rece-nuevo]');

        const pedir = async (
            accion,
            opciones = {}
        ) => {

            const params =
                new URLSearchParams({
                    accion
                });

            const res = await fetch(
                `${R}?${params.toString()}`,
                {
                    method:
                        opciones.method || 'GET',
                    body:
                        opciones.body || undefined,
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json'
                    }
                }
            );

            const datos =
                await res.json();

            if (
                !res.ok ||
                !datos.ok
            ) {
                throw Error(
                    datos.mensaje ||
                    `HTTP ${res.status}`
                );
            }

            return datos;
        };

        const listar = async () => {

            msg.hidden = false;
            msg.textContent =
                'Cargando recepciones...';

            tb.innerHTML = '';

            try {

                const params =
                    new URLSearchParams({
                        accion: 'listar',
                        buscar: b.value.trim(),
                        limite: '50'
                    });

                const res = await fetch(
                    `${R}?${params.toString()}`,
                    {
                        cache: 'no-store',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json'
                        }
                    }
                );

                const datos =
                    await res.json();

                if (
                    !res.ok ||
                    !datos.ok
                ) {
                    throw Error(
                        datos.mensaje ||
                        `HTTP ${res.status}`
                    );
                }

                const filas =
                    datos.recepciones || [];

                tb.innerHTML =
                    filas
                        .map(x => `
                            <tr>
                                <td>${esc(x.id)}</td>
                                <td>${esc(x.razon)}</td>
                                <td>${esc(x.almacen)}</td>
                                <td>${x.comex ? esc(x.comex) : '—'}</td>
                                <td>${esc(x.estad)}</td>
                                <td>${esc(x.fecha)}</td>
                                <td>${esc(x.obser || '—')}</td>
                                <td>
                                    ${
                                        x.estad === 'pendiente'
                                            ? `
                                                <button
                                                    type="button"
                                                    data-rece-reint="${x.id}"
                                                >
                                                    Reintentar
                                                </button>
                                            `
                                            : '—'
                                    }
                                </td>
                            </tr>
                        `)
                        .join('');

                msg.textContent = 'Sin registros';
                msg.hidden = filas.length > 0;

            } catch (error) {

                msg.textContent =
                    'No se pudieron cargar las recepciones.';

                console.error(
                    'Recepción:',
                    error
                );
            }
        };

        const opciones = async mini => {

            const form =
                mini.querySelector(
                    '[data-rece-form]'
                );

            const p =
                form.querySelector(
                    '[data-rece-proveedor]'
                );

            const o =
                form.querySelector(
                    '[data-rece-comex]'
                );

            const a =
                form.querySelector(
                    '[data-rece-almacen]'
                );

            const pr =
                form.querySelector(
                    '[data-rece-producto]'
                );

            const datos =
                await pedir('opciones');

            const proveedores = datos.proveedores || [];

            p.innerHTML =
                proveedores.length
                    ? proveedores
                        .map(x => `
                            <option value="${esc(x.id)}">
                                ${esc(x.razon)}
                                ${x.ruc ? ' | ' + esc(x.ruc) : ''}
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin proveedores disponibles</option>';

            o.innerHTML =
                '<option value="">Sin compra externa</option>' +
                (datos.comex || [])
                    .map(x => `
                        <option
                            value="${esc(x.id)}"
                            data-proveedor="${esc(x.prove)}"
                        >
                            #${esc(x.id)}
                            | ${esc(x.razon)}
                            | ${esc(x.estad)}
                        </option>
                    `)
                    .join('');

            const almacenes = datos.almacenes || [];

            a.innerHTML =
                almacenes.length
                    ? almacenes
                        .map(x => `
                            <option value="${esc(x.id)}">
                                ${esc(x.nombr)}
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin almacenes disponibles</option>';

            const productos =
                datos.productos || [];

            pr.innerHTML =
                productos.length
                    ? productos
                        .map(x => `
                            <option value="${esc(x.id)}">
                                ${esc(x.descr)}
                                |
                                ${esc(x.codig || x.origi || '')}
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin productos disponibles</option>';
        };

        const abrir = async () => {

            if (!(await auth('RECEG'))) {
                return;
            }

            if (!window.NURBAK_MINI?.abrir) {
                throw new Error(
                    'MINI no está disponible.'
                );
            }

            const mini =
                await window.NURBAK_MINI.abrir({
                    titulo: 'Nueva recepción',
                    rutaVista:
                        'vista/categ/compr/recep-mini.php',
                    modulo: 'recep-mini',
                    clase: 'recep-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-rece-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            const p =
                form.querySelector(
                    '[data-rece-proveedor]'
                );

            const o =
                form.querySelector(
                    '[data-rece-comex]'
                );

            const a =
                form.querySelector(
                    '[data-rece-almacen]'
                );

            const pr =
                form.querySelector(
                    '[data-rece-producto]'
                );

            const ca =
                form.querySelector(
                    '[data-rece-canti]'
                );

            const co =
                form.querySelector(
                    '[data-rece-costo]'
                );

            const db =
                form.querySelector(
                    '[data-rece-detalles]'
                );

            const obse =
                form.querySelector(
                    '[data-rece-obser]'
                );

            let productos = [];
            let detalles = [];

            const dib = () => {

                db.innerHTML =
                    detalles
                        .map((x, i) => `
                            <tr>
                                <td>${esc(x.texto)}</td>
                                <td>${x.canti}</td>
                                <td>${x.costo.toFixed(2)}</td>
                                <td>
                                    <button
                                        type="button"
                                        data-rdel="${i}"
                                    >
                                        Quitar
                                    </button>
                                </td>
                            </tr>
                        `)
                        .join('');
            };

            const cargarOpciones = async () => {

                await opciones(mini);

                const data =
                    await pedir(
                        'opciones'
                    );

                productos =
                    data.productos || [];
            };

            const emitir = datos => {

                window.NURBAK_CATE_SINC?.emitir({
                    modu: 'recep',
                    recu: 'recep',
                    acci: 'crear',
                    id: Number(datos.id || 0),
                    dato: datos
                });

                if (datos.inv) {
                    window.NURBAK_CATE_SINC?.emitir({
                        modu: 'recep',
                        recu: 'stock',
                        acci: 'actua',
                        id: 0,
                        dato: datos
                    });
                }

                if (datos.cate) {
                    window.NURBAK_CATE_SINC?.emitir({
                        modu: 'recep',
                        recu: 'produ',
                        acci: 'actua',
                        id: 0,
                        dato: datos
                    });
                }

                if (datos.comx) {
                    window.NURBAK_CATE_SINC?.emitir({
                        modu: 'recep',
                        recu: 'comex',
                        acci: 'actua',
                        id: 0,
                        dato: datos
                    });
                }
            };

            form.querySelector(
                '[data-rece-cancelar]'
            )?.addEventListener(
                'click',
                () => window.NURBAK_MINI.cerrar(mini)
            );

            o.addEventListener(
                'change',
                () => {

                    const opcion =
                        o.selectedOptions[0];

                    if (
                        opcion?.dataset.proveedor
                    ) {
                        p.value =
                            opcion.dataset.proveedor;
                    }
                }
            );

            form.querySelector(
                '[data-rece-agregar]'
            )?.addEventListener(
                'click',
                () => {

                    const id = Number(pr.value);
                    const canti = Number(ca.value);
                    const costo = Number(co.value);

                    const producto =
                        productos.find(
                            x => Number(x.id) === id
                        );

                    if (
                        !producto ||
                        canti <= 0 ||
                        costo < 0
                    ) {
                        alert(
                            'Seleccione producto e indique canti y costo válidos.'
                        );
                        return;
                    }

                    detalles.push({
                        produ: id,
                        canti,
                        costu: costo,
                        costo,
                        texto:
                            `${producto.descr}${
                                producto.codig
                                    ? ' | ' + producto.codig
                                    : ''
                            }`
                    });

                    dib();

                    ca.value = '';
                    co.value = '';
                    pr.focus();
                }
            );

            db.addEventListener(
                'click',
                evento => {

                    const boton =
                        evento.target.closest(
                            '[data-rdel]'
                        );

                    if (!boton) {
                        return;
                    }

                    detalles.splice(
                        Number(boton.dataset.rdel),
                        1
                    );

                    dib();
                }
            );

            form.addEventListener(
                'submit',
                async evento => {

                    evento.preventDefault();

                    if (
                        !p.value ||
                        !a.value ||
                        detalles.length === 0
                    ) {
                        alert(
                            'Seleccione proveedor, almacén y agregue productos.'
                        );
                        return;
                    }

                    try {

                        const body =
                            new URLSearchParams({
                                accion: 'agregar',
                                prove: p.value,
                                comex: o.value,
                                almac: a.value,
                                obser: obse.value,
                                detalles:
                                    JSON.stringify(
                                        detalles.map(
                                            x => ({
                                                produ: x.produ,
                                                canti: x.canti,
                                                costu: x.costu
                                            })
                                        )
                                    )
                            });

                        const res =
                            await fetch(
                                R,
                                {
                                    method: 'POST',
                                    body,
                                    credentials: 'same-origin',
                                    headers: {
                                        Accept: 'application/json'
                                    }
                                }
                            );

                        const datos =
                            await res.json();

                        if (
                            !res.ok ||
                            !datos.ok
                        ) {
                            throw Error(
                                datos.mensaje ||
                                'No se pudo registrar.'
                            );
                        }

                        emitir(datos);

                        form.reset();
                        detalles = [];
                        dib();

                        await listar();

                        window.NURBAK_MINI.cerrar(
                            mini
                        );

                    } catch (error) {

                        alert(
                            error.message
                        );
                    }
                }
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () => cargarOpciones().catch(() => {}),
                {
                    recu: 'prove'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () => cargarOpciones().catch(() => {}),
                {
                    recu: 'comex'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () => cargarOpciones().catch(() => {}),
                {
                    recu: 'almac'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () => cargarOpciones().catch(() => {}),
                {
                    recu: 'produ'
                },
                mini
            );

            await cargarOpciones();

            p.focus();
        };

        const reint = async id => {

            if (!(await auth('RECEG'))) {
                return;
            }

            try {

                const res =
                    await fetch(
                        R,
                        {
                            method: 'POST',
                            body:
                                new URLSearchParams({
                                    accion: 'reint',
                                    id: String(id)
                                }),
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json'
                            }
                        }
                    );

                const datos =
                    await res.json();

                if (
                    !res.ok ||
                    !datos.ok
                ) {
                    throw Error(
                        datos.mensaje ||
                        'No se pudo reintentar.'
                    );
                }

                window.NURBAK_CATE_SINC?.emitir({
                    modu: 'recep',
                    recu: 'recep',
                    acci: 'reint',
                    id: Number(id || 0),
                    dato: datos
                });

                alert(datos.mensaje);

                await listar();

            } catch (error) {

                alert(
                    error.message
                );
            }
        };

        nuevo.addEventListener(
            'click',
            () => {
                abrir().catch(error => {
                    alert(error.message);
                });
            }
        );

        tb.addEventListener(
            'click',
            evento => {

                const boton =
                    evento.target.closest(
                        '[data-rece-reint]'
                    );

                if (boton) {
                    reint(
                        Number(
                            boton.dataset.receReint
                        )
                    );
                }
            }
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'prove'
            },
            c
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'comex'
            },
            c
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'almac'
            },
            c
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'produ'
            },
            c
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'recep'
            },
            c
        );

        let t;

        b.addEventListener(
            'input',
            () => {

                clearTimeout(t);

                t = setTimeout(
                    listar,
                    250
                );
            }
        );

        listar();
    };

    document.addEventListener(
        'modu:cargado',
        evento => {

            if (
                evento.detail?.modulo ===
                'recepcion'
            ) {
                iniciar(
                    evento.detail.modal
                );
            }
        }
    );

    document
        .querySelectorAll('[data-rece]')
        .forEach(iniciar);

})();
