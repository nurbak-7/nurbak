/* ============================================================
   Ruta: publi/asset/js/categ/inven/ordco.js
   Módulo: Inventario / Orden interna
   Función: Coordinar ALMAC origen → ALMAC destino.
============================================================ */

(() => {

    const rta =
        'modul/categ/inven/ordco/ordco-contr.php';

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

    const ini = con => {

        const rai =
            con?.matches?.('[data-ordc]')
                ? con
                : con?.querySelector?.('[data-ordc]');

        if (
            !rai ||
            rai.dataset.ini === '1'
        ) {
            return;
        }

        rai.dataset.ini = '1';

        const q =
            s => rai.querySelector(s);

        const busc =
            q('[data-ordc-busc]');

        const cuer =
            q('[data-ordc-cuer]');

        const mens =
            q('[data-ordc-mens]');

        const nuevo =
            q('[data-ordc-nuev]');

        const req = async (
            act,
            dat = {}
        ) => {

            const p =
                new URLSearchParams({
                    acci: act,
                    ...dat
                });

            const r =
                await fetch(
                    `${rta}?${p}`,
                    {
                        cache: 'no-store',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json'
                        }
                    }
                );

            const d =
                await r.json();

            if (
                !r.ok ||
                !d.ok
            ) {
                throw new Error(
                    d.mensaje ||
                    `HTTP ${r.status}`
                );
            }

            return d;
        };

        const lista = async () => {

            const datos =
                await req(
                    'lista',
                    {
                        busc:
                            busc.value.trim(),
                        limi: 100
                    }
                );

            const arr =
                datos.ordco || [];

            cuer.innerHTML =
                arr
                    .map(x => `
                        <tr>
                            <td>${esc(x.id)}</td>
                            <td>${esc(x.tiend_orig || '—')}</td>
                            <td>${esc(x.ori || '—')}</td>
                            <td>${esc(x.tiend_dest || '—')}</td>
                            <td>${esc(x.des || '—')}</td>
                            <td>${esc(x.estad)}</td>
                            <td>${esc(x.fecha)}</td>
                            <td>${esc(x.obser || '—')}</td>
                        </tr>
                    `)
                    .join('');

            mens.hidden =
                arr.length > 0;

            if (!arr.length) {
                mens.textContent =
                    'Sin órdenes.';
            }
        };

        const abrir = async () => {

            if (!(await auth('OCOMG'))) {
                return;
            }

            if (!window.NURBAK_MINI?.abrir) {
                throw new Error(
                    'MINI no está disponible.'
                );
            }

            const mini =
                await window.NURBAK_MINI.abrir({
                    titulo: 'Nueva orden de compra interna',
                    rutaVista:
                        'vista/categ/inven/ordco-mini.php',
                    modulo: 'ordco-mini',
                    clase: 'ordco-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-ordc-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            const tior =
                form.querySelector(
                    '[data-ordc-tior]'
                );

            const tide =
                form.querySelector(
                    '[data-ordc-tide]'
                );

            const orig =
                form.querySelector(
                    '[data-ordc-orige]'
                );

            const dest =
                form.querySelector(
                    '[data-ordc-desti]'
                );

            const prod =
                form.querySelector(
                    '[data-ordc-produ]'
                );

            const cant =
                form.querySelector(
                    '[data-ordc-canti]'
                );

            const deta =
                form.querySelector(
                    '[data-ordc-deta]'
                );

            const obse =
                form.querySelector(
                    '[data-ordc-obse]'
                );

            let pro = [];
            let det = [];

            const dib = () => {

                deta.innerHTML =
                    det
                        .map(
                            (x, i) => `
                                <tr>
                                    <td>${esc(x.txt)}</td>
                                    <td>${esc(x.canti)}</td>
                                    <td>
                                        <button
                                            type="button"
                                            data-ordc-del="${i}"
                                        >
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                            `
                        )
                        .join('');
            };

            const tin = async () => {

                const oriActual =
                    String(
                        tior.value || ''
                    );

                const desActual =
                    String(
                        tide.value || ''
                    );

                const d =
                    await req('opcio');

                const arr =
                    d.tiendas || [];

                const html =
                    arr.length
                        ? arr
                            .map(x => `
                            <option value="${esc(x.id)}">
                                ${esc(x.codig)}
                                —
                                ${esc(x.nombr)}
                                </option>
                            `)
                            .join('')
                        : '<option value="" disabled>Sin tiendas disponibles</option>';

                tior.innerHTML = html;
                tide.innerHTML = html;

                pro =
                    d.produ || [];

                prod.innerHTML =
                    pro.length
                        ? pro
                            .map(x => `
                            <option value="${esc(x.id)}">
                                ${esc(x.descr)}
                                ${
                                    x.codig
                                        ? ' | ' + esc(x.codig)
                                        : ''
                                }
                                </option>
                            `)
                            .join('')
                        : '<option value="" disabled>Sin productos disponibles</option>';

                if (
                    oriActual &&
                    [...tior.options]
                        .some(
                            o =>
                                o.value ===
                                oriActual
                        )
                ) {
                    tior.value =
                        oriActual;
                }

                if (
                    desActual &&
                    [...tide.options]
                        .some(
                            o =>
                                o.value ===
                                desActual
                        )
                ) {
                    tide.value =
                        desActual;
                }
            };

            const alm = async (
                tid,
                sel
            ) => {

                if (!tid) {
                    sel.innerHTML = '';
                    return;
                }

                const d =
                    await req(
                        'opcio',
                        {
                            tiend: tid
                        }
                    );

                const arr =
                    d.almacenes || [];

                sel.innerHTML =
                    arr.length
                        ? arr
                            .map(x => `
                                <option value="${esc(x.id)}">
                                    ${esc(x.nombr)}
                                    ${
                                        Number(x.prin) === 1
                                            ? ' — PRINCIPAL'
                                            : ''
                                    }
                                </option>
                            `)
                            .join('')
                        : '<option value="" disabled>Sin almacenes disponibles</option>';
            };

            const cargar = async () => {

                const oriActual =
                    String(
                        tior.value || ''
                    );

                const desActual =
                    String(
                        tide.value || ''
                    );

                await tin();

                if (
                    oriActual &&
                    [...tior.options]
                        .some(
                            o =>
                                o.value ===
                                oriActual
                        )
                ) {
                    tior.value =
                        oriActual;
                }

                if (
                    desActual &&
                    [...tide.options]
                        .some(
                            o =>
                                o.value ===
                                desActual
                        )
                ) {
                    tide.value =
                        desActual;
                }

                await Promise.all([
                    alm(
                        tior.value,
                        orig
                    ),
                    alm(
                        tide.value,
                        dest
                    )
                ]);
            };

            tior.addEventListener(
                'change',
                async () => {

                    try {
                        await alm(
                            tior.value,
                            orig
                        );
                    } catch (error) {
                        alert(
                            error.message
                        );
                    }
                }
            );

            tide.addEventListener(
                'change',
                () => {
                    alm(
                        tide.value,
                        dest
                    ).catch(error =>
                        alert(
                            error.message
                        )
                    );
                }
            );

            form.querySelector(
                '[data-ordc-agre]'
            )?.addEventListener(
                'click',
                () => {

                    const id =
                        Number(
                            prod.value || 0
                        );

                    const can =
                        Number(
                            cant.value || 0
                        );

                    const x =
                        pro.find(
                            v =>
                                Number(v.id) === id
                        );

                    if (
                        !x ||
                        can <= 0
                    ) {
                        alert(
                            'Producto y cantidad son obligatorios.'
                        );
                        return;
                    }

                    det.push({
                        produ: id,
                        canti: can,
                        txt:
                            `${x.descr}${
                                x.codig
                                    ? ' | ' + x.codig
                                    : ''
                            }`
                    });

                    dib();

                    cant.value = '';
                    prod.focus();
                }
            );

            deta.addEventListener(
                'click',
                evento => {

                    const boton =
                        evento.target.closest(
                            '[data-ordc-del]'
                        );

                    if (!boton) {
                        return;
                    }

                    det.splice(
                        Number(
                            boton.dataset.ordcDel
                        ),
                        1
                    );

                    dib();
                }
            );

            form.querySelector(
                '[data-ordc-canc]'
            )?.addEventListener(
                'click',
                () => window.NURBAK_MINI.cerrar(mini)
            );

            form.addEventListener(
                'submit',
                async evento => {

                    evento.preventDefault();

                    if (
                        !orig.value ||
                        !dest.value ||
                        !det.length
                    ) {
                        alert(
                            'Seleccione origen, destino y agregue productos.'
                        );
                        return;
                    }

                    if (
                        orig.value ===
                        dest.value
                    ) {
                        alert(
                            'El almacén origen y destino deben ser diferentes.'
                        );
                        return;
                    }

                    try {

                        const datos =
                            await req(
                                'agreg',
                                {
                                    orige:
                                        orig.value,
                                    desti:
                                        dest.value,
                                    obse:
                                        obse.value,
                                    deto:
                                        JSON.stringify(
                                            det.map(
                                                x => ({
                                                    produ:
                                                        x.produ,
                                                    canti:
                                                        x.canti
                                                })
                                            )
                                        )
                                }
                            );

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'ordco',
                            recu: 'ordco',
                            acci: 'crear',
                            id: Number(
                                datos.id || 0
                            ),
                            dato: datos
                        });

                        det = [];
                        dib();

                        await lista();

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

            window.NURBAK_ADMI_SINC?.onCambio(
                () =>
                    cargar().catch(() => {}),
                {
                    recu: 'tiend'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () =>
                    cargar().catch(() => {}),
                {
                    recu: 'almac'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () =>
                    cargar().catch(() => {}),
                {
                    recu: 'produ'
                },
                mini
            );

            await cargar();

            tior.focus();
        };

        nuevo.addEventListener(
            'click',
            () => {
                abrir().catch(error => {
                    alert(error.message);
                });
            }
        );

        window.NURBAK_ADMI_SINC?.onCambio(
            () =>
                lista().catch(() => {}),
            {
                recu: 'tiend'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                lista().catch(() => {}),
            {
                recu: 'almac'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                lista().catch(() => {}),
            {
                recu: 'produ'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                lista().catch(() => {}),
            {
                recu: 'ordco'
            },
            con
        );

        let t;

        busc.addEventListener(
            'input',
            () => {

                clearTimeout(t);

                t =
                    setTimeout(
                        () =>
                            lista().catch(
                                error =>
                                    alert(
                                        error.message
                                    )
                            ),
                        220
                    );
            }
        );

        lista().catch(error => {
            mens.textContent =
                error.message;
            mens.hidden = false;
        });
    };

    document.addEventListener(
        'modu:cargado',
        evento => {

            if (
                evento.detail?.modulo ===
                'ordco'
            ) {
                ini(
                    evento.detail.modal
                );
            }
        }
    );

    document
        .querySelectorAll('[data-ordc]')
        .forEach(ini);

})();
