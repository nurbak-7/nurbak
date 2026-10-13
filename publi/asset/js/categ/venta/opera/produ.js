/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/produ.js
   Módulo: Ventas / Operaciones / Productos

   RESPONSABILIDAD:
   Coordinar el botón Agregar producto, el MINI buscador y
   la incorporación de productos a la operación.

   NIVELES:
   1. Abrir MINI.
   2. Buscar productos en servidor.
   3. Seleccionar con teclado.
   4. Reservar mediante el servicio de Operaciones.
   5. Insertar/acumular producto.
   6. Mantener MINI abierto para el siguiente producto.

   IMPORTANTE:
   - El MINI NO se cierra después de agregar.
============================================================ */

(() => {

    /* ========================================================
       NIVEL 1 — CONFIGURACIÓN
    ======================================================== */
    const RUTA_MINI = 'vista/categ/venta/opera/produc-mini.php';
    const LIMITE = 20;

    /* ========================================================
       NIVEL 2 — PERSISTENCIA

       La escritura del borrador pertenece al coordinador OPER.
       PRODUCTOS solo solicita la acción mediante ctx.api.
    ======================================================== */
    /* ========================================================
       NIVEL 3 — ABRIR MINI
    ======================================================== */
    const abrirBuscador = async (ctx, ciclo) => {

        /* ====================================================
           NIVEL 3.1 — CONSULTAR AL COORDINADOR

           PRODUCTOS no valida cliente, vendedor ni almacén.
           Solo solicita al coordinador OPER la autorización
           para abrir el MINI.
        ==================================================== */
        const contexto =
            ctx.api.validarContextoOperacion?.();

        if (!contexto?.ok) {
            return null;
        }

        if (!window.NURBAK_MINI?.abrir) {
            throw new Error('MINI no está disponible.');
        }

        const mini = await window.NURBAK_MINI.abrir({
            titulo: 'Agregar producto',
            rutaVista: RUTA_MINI,
            modulo: 'opera-productos',
            clase: 'mini-opera-productos'
        });

        if (!mini) {
            throw new Error('No se pudo abrir el buscador.');
        }

        iniciarBuscador(ctx, mini, ciclo);
        return mini;
    };

    /* ========================================================
       NIVEL 4 — BUSCADOR DEL MINI
    ======================================================== */
    const iniciarBuscador = (ctx, mini, ciclo) => {

        const buscar = mini.querySelector('[data-opera-prod-buscar]');
        const estad = mini.querySelector('[data-opera-prod-estad]');
        const cuerpo = mini.querySelector('[data-opera-prod-resultados]');

        const infoResultados =
            mini.querySelector('[data-opera-prod-resultados-info]');

        const infoStock =
            mini.querySelector('[data-opera-prod-stock-info]');

        const infoReservado =
            mini.querySelector('[data-opera-prod-reservado-info]');

        const infoDisponible =
            mini.querySelector('[data-opera-prod-disponible-info]');

        if (
            !buscar ||
            !estad ||
            !cuerpo ||
            !infoResultados ||
            !infoStock ||
            !infoReservado ||
            !infoDisponible
        ) return;

        let temporizador = null;
        let solicitud = 0;
        let resultados = [];
        let indice = 0;
        let fuen = null;
        let vigilar = new Set();
        let aborto = null;
        let cerrado = false;
        let estados = new Map();
        let observado = 0;
        let offCierre = null;

        const limpiar = removerMini => {
            if (cerrado) return;
            cerrado = true;
            solicitud++;

            clearTimeout(temporizador);
            temporizador = null;

            if (aborto) {
                try { aborto.abort(); } catch (_) {}
                aborto = null;
            }

            if (fuen) {
                try { fuen.close(); } catch (_) {}
                fuen = null;
            }

            ciclo.recursos.delete(limpiar);
            offCierre?.();
            if (offCierre) ciclo.recursos.delete(offCierre);
            offCierre = null;
            estados.clear();
            observado = 0;

            if (removerMini && document.body.contains(mini)) {
                try {
                    window.NURBAK_MINI?.cerrar?.(mini);
                } catch (_) {}
            }
        };

        ciclo.recursos.add(limpiar);

        /* ====================================================
           NIVEL 4.0 — RESUMEN DEL PRODUCTO SELECCIONADO

           El resumen NO representa el primer resultado por defecto
           ni un total de todos los resultados. Siempre corresponde
           exclusivamente al producto actualmente seleccionado.
        ==================================================== */
        const actualizarResumen = () => {

            let producto = resultados[indice];

            if (!producto && observado > 0) {
                const estado = estados.get(observado);
                if (estado) {
                    producto = {
                        id: observado,
                        ...estado
                    };
                }
            }

            if (!producto) {
                infoStock.textContent = 'STOCK: 0';
                infoReservado.textContent = 'RESERVADO: 0';
                infoDisponible.textContent = 'DISPONIBLE: 0';
                return;
            }

            infoStock.textContent =
                `STOCK: ${Number(producto.canti ?? 0)}`;

            infoReservado.textContent =
                `RESERVADO: ${Number(producto.reser ?? 0)}`;

            infoDisponible.textContent =
                `DISPONIBLE: ${Number(producto.dispo ?? 0)}`;
        };

        const pintar = () => {

            cuerpo.innerHTML = resultados.map((producto, i) => `
                <tr
                    data-opera-prod-fila="${i}"
                    class="${i === indice ? 'is-seleccionada' : ''}"
                >
                    <td>${ctx.esc(producto.motor || '—')}</td>
                    <td>${ctx.esc(producto.model || '—')}</td>
                    <td>${ctx.esc(producto.codig || '—')}</td>
                    <td>${ctx.esc(producto.descr || '—')}</td>
                    <td>${ctx.esc(producto.origi || '—')}</td>
                    <td>${ctx.esc(producto.medid || '—')}</td>
                    <td>${ctx.esc(producto.super || '—')}</td>
                    <td>${ctx.esc(producto.anio || '—')}</td>
                    <td>${ctx.esc(producto.canti ?? '0')}</td>
                    <td>${Number(producto.preci || 0).toFixed(2)}</td>
                </tr>
            `).join('');

            actualizarResumen();
        };

        /* ====================================================
           NIVEL 4.1 — SINCRONIZACIÓN SSE

           El MINI mantiene su disponibilidad sincronizada sin
           exigir una nueva búsqueda al usuario. La búsqueda solo
           consulta; no crea reservas.
        ==================================================== */
        const csse = () => {
            if (fuen) {
                try { fuen.close(); } catch (_) {}
                fuen = null;
            }
        };

        const asse = () => {
            csse();

            const alma = String(ctx.state.almacenId || '');
            const ids = Array.from(vigilar)
                .map(id => Number(id))
                .filter(Number.isInteger)
                .filter(id => id > 0)
                .slice(0, 50);

            if (!alma || !ids.length || !window.EventSource) {
                return;
            }

            const params = new URLSearchParams({
                almac: alma,
                ids: ids.join(',')
            });

            const url =
                `modul/categ/venta/opera/sse.php?${params.toString()}`;

            fuen = new EventSource(url);

            fuen.addEventListener('stock', even => {
                try {
                    const datos = JSON.parse(even.data || '{}');
                    const esta = Array.isArray(datos.productos)
                        ? datos.productos
                        : [];

                    esta.forEach(stat => {
                        const id = Number(stat.produ || 0);
                        if (!id) return;

                        const estado = {
                            canti: Number(stat.canti ?? 0),
                            reser: Number(stat.reser ?? 0),
                            dispo: Number(stat.dispo ?? 0)
                        };

                        estados.set(id, estado);

                        const prod = resultados.find(
                            item => Number(item.id) === id
                        );

                        if (prod) {
                            Object.assign(prod, estado);
                        }
                    });

                    if (esta.length) {
                        indice = Math.min(
                            indice,
                            Math.max(0, resultados.length - 1)
                        );
                        pintar();
                    }

                } catch (erro) {
                }
            });

        };

        offCierre = window.NURBAK_SINC?.on(
            'mini:cerrando',
            evento => {
                if (evento?.mini === mini) limpiar(false);
            }
        ) || null;
        if (offCierre) ciclo.recursos.add(offCierre);

        const consultar = async () => {

            const texto = buscar.value.trim();
            const almacen = String(ctx.state.almacenId || '');

            if (!almacen) {
                csse();
                estad.textContent = 'Seleccione primero un almacén.';
                cuerpo.innerHTML = '';
                resultados = [];
                infoResultados.textContent = 'Sin resultados.';
                actualizarResumen();
                return;
            }

            const idSolicitud = ++solicitud;

            if (aborto) {
                try { aborto.abort(); } catch (_) {}
            }

            aborto = new AbortController();

            estad.textContent = 'Buscando...';
            cuerpo.innerHTML = '';

            const params = new URLSearchParams({
                accion: 'buscar-productos',
                buscar: texto,
                almac: almacen,
                limite: String(LIMITE)
            });

            try {

                const respuesta = await fetch(
                    `${ctx.R}?${params.toString()}`,
                    {
                        method: 'GET',
                        cache: 'no-store',
                        signal: aborto.signal,
                        headers: {
                            Accept: 'application/json'
                        }
                    }
                );

                const datos = await respuesta.json();

                if (idSolicitud !== solicitud) return;

                if (!respuesta.ok || !datos.ok) {
                    throw new Error(
                        datos.mensaje ||
                        'No se pudieron buscar productos.'
                    );
                }

                vigilar.clear();
            resultados = Array.isArray(datos.productos)
                    ? datos.productos.map(producto => {
                        const id = Number(producto.id || 0);
                        const estado = estados.get(id);
                        return estado
                            ? { ...producto, ...estado }
                            : producto;
                    })
                    : [];

                resultados.forEach(producto => {
                    const id = Number(producto.id);
                    if (Number.isInteger(id) && id > 0) {
                        vigilar.add(id);
                    }
                });

                indice = 0;
                pintar();

                infoResultados.textContent = resultados.length
                    ? `${resultados.length} resultado(s).`
                    : 'Sin resultados.';

                asse();
                estad.textContent = '';
                aborto = null;

            } catch (error) {

                if (error?.name === 'AbortError') return;
                if (idSolicitud !== solicitud) return;

                resultados = [];
                csse();
                cuerpo.innerHTML = '';
                infoResultados.textContent = 'Sin resultados.';
                actualizarResumen();
                estad.textContent = error.message;
            }
        };

        // SINCRONIZACIÓN | FASE 2
        const offProd = window.NURBAK_CATE_SINC?.onCambio(
            () => void consultar().catch(() => {}),
            { recu: 'produ' }
        ) || (() => {});
        const offStoc = window.NURBAK_CATE_SINC?.onCambio(
            () => void consultar().catch(() => {}),
            { recu: 'stock' }
        ) || (() => {});
        const offMovi = window.NURBAK_CATE_SINC?.onCambio(
            () => void consultar().catch(() => {}),
            { recu: 'movim' }
        ) || (() => {});
        ciclo.recursos.add(offProd);
        ciclo.recursos.add(offStoc);
        ciclo.recursos.add(offMovi);

        const agregarSeleccionado = async () => {

            const producto = resultados[indice];

            if (!producto) return;

            const existente = ctx.state.detalles.find(
                item => Number(item.produ) === Number(producto.id)
            );

            if (existente) {
                existente.canti =
                    Number(existente.canti) + 1;
            } else {
                ctx.state.detalles.push({
                    produ: Number(producto.id),
                    codig: producto.codig || '',
                    descr: producto.descr || '',
                    canti: 1,
                    preci: Number(producto.preci || 0)
                });
            }

            try {

                await ctx.api.sincronizarBorrador?.();

                /* El servidor devuelve el estado real de INVEN. */
                (ctx.state.estados || []).forEach(stat => {
                    const id = Number(stat.produ || 0);
                    if (!id) return;

                    estados.set(id, {
                        canti: Number(stat.canti ?? 0),
                        reser: Number(stat.reser ?? 0),
                        dispo: Number(stat.dispo ?? 0)
                    });
                });

                const prodId = Number(producto.id);
                if (Number.isInteger(prodId) && prodId > 0) {
                    observado = prodId;
                    vigilar.add(prodId);
                }

                ctx.api.dibujarDetalles?.();

                estad.textContent =
                    `${producto.codig || producto.descr} agregado.`;

                /*
                   IMPORTANTE:
                   No cerramos MINI.
                   Se limpia la búsqueda y el foco vuelve al mismo campo.
                */
                buscar.value = '';
                /*
                   El producto ya forma parte de la operación. La
                   conexión SSE permanece viva mientras el MINI siga
                   abierto para que este usuario continúe recibiendo
                   los cambios de disponibilidad.
                */
                resultados = [];
                indice = 0;
                cuerpo.innerHTML = '';
                infoResultados.textContent = 'Sin resultados.';
                actualizarResumen();

                setTimeout(() => buscar.focus(), 0);

            } catch (error) {

                /*
                   Si la reserva falla, se revierte el cambio local.
                */
                if (existente) {
                    existente.canti =
                        Number(existente.canti) - 1;

                    if (existente.canti <= 0) {
                        const pos =
                            ctx.state.detalles.indexOf(existente);
                        if (pos >= 0) {
                            ctx.state.detalles.splice(pos, 1);
                        }
                    }
                } else {
                    const pos =
                        ctx.state.detalles.findIndex(
                            item =>
                                Number(item.produ) ===
                                Number(producto.id)
                        );

                    if (pos >= 0) {
                        ctx.state.detalles.splice(pos, 1);
                    }
                }

                ctx.api.dibujarDetalles?.();

                estad.textContent = error.message;
            }
        };

        buscar.addEventListener('input', () => {

            csse();
            clearTimeout(temporizador);

            temporizador = setTimeout(
                consultar,
                250
            );
        });

        /* ====================================================
           NIVEL 4.2 — TECLADO DEL MINI

           El teclado pertenece al buscador MINI completo, no solo
           al input. Esto evita que Enter/Escape sean interceptados
           por el formulario o por otro controlador de la ventana.
        ==================================================== */
        mini.addEventListener('keydown', evento => {

            if (evento.key === 'ArrowDown') {
                evento.preventDefault();

                if (resultados.length) {
                    indice = Math.min(
                        indice + 1,
                        resultados.length - 1
                    );
                    pintar();
                }
                return;
            }

            if (evento.key === 'ArrowUp') {
                evento.preventDefault();

                if (resultados.length) {
                    indice = Math.max(indice - 1, 0);
                    pintar();
                }
                return;
            }

            if (evento.key === 'Enter') {
                evento.preventDefault();

                if (!resultados.length) return;

                void agregarSeleccionado();
                return;
            }

            if (evento.key === 'Escape') {
                evento.preventDefault();

                window.NURBAK_MINI?.cerrar?.(mini);
            }
        }, true);

        cuerpo.addEventListener('mousedown', evento => {

            const fila =
                evento.target.closest('[data-opera-prod-fila]');

            if (!fila) return;

            indice = Number(fila.dataset.operProdFila);
            pintar();
            buscar.focus();
        });

        consultar();
    };

    /* ========================================================
       NIVEL 5 — REGISTRO DEL MÓDULO
    ======================================================== */
    const iniciar = ctx => {

        const { q } = ctx;
        const ciclo = {
            recursos: new Set()
        };

        ctx.registrarLimpieza?.(() => {
            [...ciclo.recursos].forEach(limpiar => {
                try {
                    limpiar(true);
                } catch (_) {}
            });
            ciclo.recursos.clear();
        });

        q('[data-opera-agregar]')?.addEventListener(
            'click',
            async () => {
                try {
                    await abrirBuscador(ctx, ciclo);
                } catch (error) {
                    alert(error.message);
                }
            }
        );

        ctx.api.abrirBuscadorProducto = () =>
            abrirBuscador(ctx, ciclo);
    };

    window.NURBAK_OPERA_MODULOS =
        window.NURBAK_OPERA_MODULOS || {};

    window.NURBAK_OPERA_MODULOS.produ = iniciar;

})();
