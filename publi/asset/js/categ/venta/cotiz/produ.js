/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/produ.js
   Módulo: Ventas / Cotización / Productos

   RESPONSABILIDAD:
   Abrir el MINI, buscar productos y agregarlos al detalle.

   IMPORTANTE:
   - Buscar no reserva.
   - Agregar a Cotización no reserva.
============================================================ */
(() => {
    const iniciar = ctx => {
        const boton = ctx.q('[data-cotiz-agregar]');
        if (!boton) return;

        const abrir = async () => {
            if (!window.NURBAK_MINI?.abrir) throw new Error('MINI no está disponible.');

            const mini = await window.NURBAK_MINI.abrir({
                titulo:'Agregar producto',
                rutaVista:'vista/categ/venta/cotiz/produc-mini.php',
                modulo:'cotiz-productos'
            });

            if (!mini) throw new Error('No se pudo abrir el buscador.');

            const buscar = mini.querySelector('[data-cotiz-prod-buscar]');
            const info = mini.querySelector('[data-cotiz-prod-info]');
            const cuerpo = mini.querySelector('[data-cotiz-prod-resultados]');
            if (!buscar || !info || !cuerpo) return;

            let rows = [];
            let indice = 0;
            let timer = null;

            const pintar = () => {
                cuerpo.innerHTML = rows.map((r,i) => `
                    <tr data-cotiz-prod-fila="${i}" class="${i === indice ? 'is-seleccionada' : ''}">
                        <td>${ctx.esc(r.motor || '—')}</td>
                        <td>${ctx.esc(r.model || '—')}</td>
                        <td>${ctx.esc(r.codig || '—')}</td>
                        <td>${ctx.esc(r.descr || '—')}</td>
                        <td>${ctx.esc(r.origi || '—')}</td>
                        <td>${ctx.esc(r.medid || '—')}</td>
                        <td>${ctx.esc(r.super || '—')}</td>
                        <td>${ctx.esc(r.anio || '—')}</td>
                        <td>${Number(r.preci || 0).toFixed(2)}</td>
                    </tr>`).join('');
                info.textContent = `${rows.length} resultado(s).`;
            };

            const buscarDatos = async () => {
                const datos = await ctx.pedir({
                    accion:'buscar-productos',
                    buscar:buscar.value.trim(),
                    limite:20
                });
                rows = datos.productos || [];
                indice = 0;
                pintar();
            };

            const agregar = () => {
                const r = rows[indice];
                if (!r) return;

                const vario = String(r.descr || '').trim().toUpperCase() === 'VARIOS';
                const existe = vario ? null : ctx.state.detalles.find(item =>
                    Number(item.produ) === Number(r.id) &&
                    String(item.codig || '') === String(r.codig || '') &&
                    String(item.descr || '') === String(r.descr || '') &&
                    Number(item.preun || 0) === Number(r.preci || 0)
                );
                if (existe) {
                    existe.canti = Number(existe.canti || 0) + 1;
                } else {
                    ctx.state.detalles.push({
                        produ:Number(r.id),
                        codig:r.codig || '',
                        descr:r.descr || '',
                        vario,
                        canti:1,
                        preun:Number(r.preci || 0)
                    });
                }

                ctx.api.dibujarDetalles?.();
            };

            buscar.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => buscarDatos().catch(error => {info.textContent = error.message;}), 220);
            });

            buscar.addEventListener('keydown', evento => {
                if (evento.key === 'ArrowDown') {
                    evento.preventDefault();
                    if (rows.length) { indice = (indice + 1) % rows.length; pintar(); }
                } else if (evento.key === 'ArrowUp') {
                    evento.preventDefault();
                    if (rows.length) { indice = (indice - 1 + rows.length) % rows.length; pintar(); }
                } else if (evento.key === 'Enter') {
                    evento.preventDefault();
                    agregar();
                } else if (evento.key === 'Escape') {
                    window.NURBAK_MINI.cerrar?.(mini);
                }
            });

            cuerpo.addEventListener('click', evento => {
                const fila = evento.target.closest('[data-cotiz-prod-fila]');
                if (!fila) return;
                indice = Number(fila.dataset.cotizProdFila);
                pintar();
                agregar();
            });

            ctx.registrarLimpieza(() => clearTimeout(timer));
            await buscarDatos();
            buscar.focus();
        };

        boton.addEventListener('click', () => void abrir().catch(error => {
            ctx.q('[data-cotiz-mensaje]').textContent = error.message;
        }));
    };

    window.NURBAK_COTIZ_MODULOS.produ = iniciar;
})();
