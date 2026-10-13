/* ============================================================
   Ruta: publi/asset/js/categ/venta/clien.js
   Función: Recurso del sistema.
============================================================ */

/* ============================================================
   Ventas / Clientes
   Consulta el controlador y dibuja la tabla. La vista es dinámica,
   por eso la inicialización espera al evento modu:cargado.
============================================================ */

(() => {

    const R =
        'modul/categ/venta/clien/clien-contr.php';

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
            c.matches?.('[data-clie]')
                ? c
                : c.querySelector?.('[data-clie]');

        if (
            !r ||
            r.dataset.iniciado === 'si'
        ) {
            return;
        }

        r.dataset.iniciado = 'si';

        const q =
            s => r.querySelector(s);

        const b =
            q('[data-clie-busc]');

        const tb =
            q('[data-clie-cuerpo]');

        const msg =
            q('[data-clie-mensaje]');

        const nuevo =
            q('[data-clie-nuevo]');

        const listar = async () => {

            msg.hidden = false;
            msg.textContent =
                'Cargando clientes...';

            tb.innerHTML = '';

            try {

                const params =
                    new URLSearchParams({
                        accion: 'listar',
                        buscar:
                            b.value.trim(),
                        limite: '50'
                    });

                const x =
                    await fetch(
                        `${R}?${params}`,
                        {
                            cache: 'no-store',
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json'
                            }
                        }
                    );

                const d =
                    await x.json();

                if (
                    !x.ok ||
                    !d.ok
                ) {
                    throw Error(
                        d.mensaje ||
                        `HTTP ${x.status}`
                    );
                }

                const a =
                    d.clientes || [];

                tb.innerHTML =
                    a
                        .map(x => `
                            <tr>
                                <td>${esc(x.id)}</td>
                                <td>${esc(x.docum || '—')}</td>
                                <td>${esc(x.razon)}</td>
                                <td>${esc(x.telef || '—')}</td>
                                <td>${esc(x.email || '—')}</td>
                                <td>
                                    ${
                                        Number(x.estad) === 1
                                            ? 'Activo'
                                            : 'Inactivo'
                                    }
                                </td>
                            </tr>
                        `)
                        .join('');

                msg.textContent =
                    'Sin registros';

                msg.hidden =
                    a.length > 0;

            } catch (error) {

                msg.hidden = false;
                msg.textContent =
                    'No se pudieron cargar los clientes.';

                console.error(
                    'Clientes:',
                    error
                );
            }
        };

        const abrir = async () => {

            if (!(await auth('CLIEG'))) {
                return;
            }

            if (!window.NURBAK_MINI?.abrir) {
                throw new Error(
                    'MINI no está disponible.'
                );
            }

            const mini =
                await window.NURBAK_MINI.abrir({
                    titulo: 'Nuevo cliente',
                    rutaVista:
                        'vista/categ/venta/clien-mini.php',
                    modulo: 'clien-mini',
                    clase: 'clien-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-clie-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            const campos = {
                tipo:
                    form.querySelector(
                        '[data-clie-tipo]'
                    ),
                docum:
                    form.querySelector(
                        '[data-clie-docum]'
                    ),
                razon:
                    form.querySelector(
                        '[data-clie-nombr]'
                    ),
                telef:
                    form.querySelector(
                        '[data-clie-telef]'
                    ),
                email:
                    form.querySelector(
                        '[data-clie-email]'
                    ),
                direc:
                    form.querySelector(
                        '[data-clie-direc]'
                    )
            };

            form.querySelector(
                '[data-clie-cancelar]'
            )?.addEventListener(
                'click',
                () => window.NURBAK_MINI.cerrar(mini)
            );

            form.addEventListener(
                'submit',
                async evento => {

                    evento.preventDefault();

                    const body =
                        new URLSearchParams({
                            accion: 'agregar',
                            tipod:
                                campos.tipo.value,
                            docum:
                                campos.docum.value,
                            razon:
                                campos.razon.value,
                            telef:
                                campos.telef.value,
                            email:
                                campos.email.value,
                            direc:
                                campos.direc.value
                        });

                    try {

                        const x =
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

                        const d =
                            await x.json();

                        if (
                            !x.ok ||
                            !d.ok
                        ) {
                            throw Error(
                                d.mensaje ||
                                'No se pudo guardar.'
                            );
                        }

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'clien',
                            recu: 'clien',
                            acci: 'crear',
                            id: Number(
                                d.id || 0
                            ),
                            dato: d
                        });

                        form.reset();

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

            campos.razon.focus();
        };

        nuevo.addEventListener(
            'click',
            () => {
                abrir().catch(error => {
                    alert(error.message);
                });
            }
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                listar().catch(
                    () => {}
                ),
            {
                recu: 'clien'
            },
            c
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                listar().catch(
                    () => {}
                ),
            {
                recu: 'cotiz'
            },
            c
        );

        let t;

        b.addEventListener(
            'input',
            () => {

                clearTimeout(t);

                t =
                    setTimeout(
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
                'clientes'
            ) {
                iniciar(
                    evento.detail.modal
                );
            }
        }
    );

    document
        .querySelectorAll('[data-clie]')
        .forEach(iniciar);

})();
