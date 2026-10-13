/* ============================================================
   Ruta: publi/asset/js/categ/compr/prove.js
   Módulo: Compras / Proveedores

   FUNCIÓN:
   Conectar la vista con prove-cont.php.

   Flujo:
   vista → JavaScript → controlador → repositorio → compr.prove
   y la respuesta vuelve hasta la tabla.
============================================================ */

(() => {

    const RUTA =
        'modul/categ/compr/prove/prove-contr.php';

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

    const iniciar = contenedor => {

        const raiz =
            contenedor.matches?.('[data-prov]')
                ? contenedor
                : contenedor.querySelector?.('[data-prov]');

        if (
            !raiz ||
            raiz.dataset.iniciado === 'si'
        ) {
            return;
        }

        raiz.dataset.iniciado = 'si';

        const busc =
            raiz.querySelector('[data-prov-busc]');

        const cuerpo =
            raiz.querySelector('[data-prov-cuerpo]');

        const msg =
            raiz.querySelector('[data-prov-mensaje]');

        const nuevo =
            raiz.querySelector('[data-prov-nuevo]');

        const consultar = async () => {

            msg.hidden = false;
            msg.textContent =
                'Cargando proveedores...';

            cuerpo.innerHTML = '';

            try {

                const params = new URLSearchParams({
                    accion: 'listar',
                    buscar: busc.value.trim(),
                    limite: '50'
                });

                const res = await fetch(
                    `${RUTA}?${params}`,
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
                    datos.proveedores || [];

                cuerpo.innerHTML =
                    filas
                        .map(x => `
                            <tr>
                                <td>${esc(x.id)}</td>
                                <td>${esc(x.razon)}</td>
                                <td>${esc(x.ruc || '—')}</td>
                                <td>${esc(x.conta || '—')}</td>
                                <td>${esc(x.telef || '—')}</td>
                                <td>${esc(x.email || '—')}</td>
                                <td>
                                    ${
                                        Number(x.estad) === 1
                                            ? 'Activo'
                                            : 'Inactivo'
                                    }
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        data-prov-estad="${esc(x.id)}"
                                        data-estad="${
                                            Number(x.estad) === 1
                                                ? 0
                                                : 1
                                        }"
                                    >
                                        ${
                                            Number(x.estad) === 1
                                                ? 'Desactivar'
                                                : 'Activar'
                                        }
                                    </button>
                                </td>
                            </tr>
                        `)
                        .join('');

                msg.textContent = 'Sin registros';
                msg.hidden = filas.length > 0;

            } catch (error) {

                msg.hidden = false;
                msg.textContent =
                    'No se pudieron cargar los proveedores.';

                console.error(
                    'Proveedores:',
                    error
                );
            }
        };

        const abrir = async () => {

            if (!(await auth('PROVG'))) {
                return;
            }

            if (!window.NURBAK_MINI?.abrir) {
                throw new Error(
                    'MINI no está disponible.'
                );
            }

            const mini =
                await window.NURBAK_MINI.abrir({
                    titulo: 'Nuevo proveedor',
                    rutaVista:
                        'vista/categ/compr/prove-mini.php',
                    modulo: 'prove-mini',
                    clase: 'prove-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-prov-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            const campos = {
                razon: form.querySelector(
                    '[data-prov-razon]'
                ),
                ruc: form.querySelector(
                    '[data-prov-ruc]'
                ),
                conta: form.querySelector(
                    '[data-prov-conta]'
                ),
                telef: form.querySelector(
                    '[data-prov-telef]'
                ),
                email: form.querySelector(
                    '[data-prov-email]'
                ),
                direc: form.querySelector(
                    '[data-prov-direc]'
                )
            };

            form.querySelector(
                '[data-prov-cancelar]'
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
                            razon: campos.razon.value,
                            ruc: campos.ruc.value,
                            conta: campos.conta.value,
                            telef: campos.telef.value,
                            email: campos.email.value,
                            direc: campos.direc.value
                        });

                    try {

                        const res =
                            await fetch(
                                RUTA,
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
                                'No se pudo guardar.'
                            );
                        }

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'prove',
                            recu: 'prove',
                            acci: 'crear',
                            id: Number(
                                datos.id || 0
                            ),
                            dato: datos
                        });

                        form.reset();

                        await consultar();

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

        cuerpo.addEventListener(
            'click',
            async evento => {

                const boton =
                    evento.target.closest(
                        '[data-prov-estad]'
                    );

                if (!boton) {
                    return;
                }

                try {

                    const res =
                        await fetch(
                            RUTA,
                            {
                                method: 'POST',
                                body:
                                    new URLSearchParams({
                                        accion: 'estad',
                                        id:
                                            boton.dataset.provEstad,
                                        estad:
                                            boton.dataset.estad
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
                            'No se pudo actualizar.'
                        );
                    }

                    window.NURBAK_CATE_SINC?.emitir({
                        modu: 'prove',
                        recu: 'prove',
                        acci: 'esta',
                        id: Number(
                            boton.dataset.provEstad || 0
                        ),
                        dato: datos
                    });

                    await consultar();

                } catch (error) {

                    alert(
                        error.message
                    );
                }
            }
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => consultar(),
            {
                recu: 'prove'
            },
            contenedor
        );

        let t;

        busc.addEventListener(
            'input',
            () => {

                clearTimeout(t);

                t = setTimeout(
                    consultar,
                    250
                );
            }
        );

        consultar();
    };

    document.addEventListener(
        'modu:cargado',
        evento => {

            if (
                evento.detail?.modulo ===
                'proveedores'
            ) {
                iniciar(
                    evento.detail.modal
                );
            }
        }
    );

    document
        .querySelectorAll('[data-prov]')
        .forEach(iniciar);

})();
