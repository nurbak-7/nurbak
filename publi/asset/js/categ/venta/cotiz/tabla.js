/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/tabla.js
   Módulo: Ventas / Cotización / Tabla

   RESPONSABILIDAD:
   Dibujar, editar y eliminar filas del detalle.
   P.UNIT conserva precisión interna de hasta 10 decimales;
   P.UNIT y TOTAL se muestran con dos decimales.
============================================================ */
(() => {
    const iniciar = ctx => {
        const body = ctx.q('[data-cotiz-detalles]');
        if (!body) return;

        const decimales = 10000000000n;
        const escalaCent = 100000000n;

        const enteroSeguro = valor => {
            const texto = String(valor ?? '').trim();
            if (!/^\d+$/.test(texto)) return null;
            const num = Number(texto);
            return Number.isSafeInteger(num) && num > 0 ? num : null;
        };

        const centavos = valor => {
            const texto = String(valor ?? '').trim();
            const m = texto.match(/^(\d+)(?:\.(\d{0,2}))?$/);
            if (!m) return null;
            return BigInt(m[1]) * 100n + BigInt((m[2] || '').padEnd(2, '0') || '0');
        };

        const formatoCentavos = valor => {
            const cent = BigInt(valor);
            return `${cent / 100n}.${String(cent % 100n).padStart(2, '0')}`;
        };

        const unidadEscalada = valor => {
            const texto = String(valor ?? '0').trim();
            if (!/^\d+(?:\.\d{1,10})?$/.test(texto)) return null;
            const [entero, fraccion = ''] = texto.split('.');
            return BigInt(entero) * decimales + BigInt(fraccion.padEnd(10, '0'));
        };

        const precioDosDecimales = valor => {
            const escala = unidadEscalada(valor);
            if (escala === null) return '0.00';
            const cent = (escala + 50000000n) / escalaCent;
            return formatoCentavos(cent);
        };

        const importe = item => {
            const qty = enteroSeguro(item.canti);
            const unidad = unidadEscalada(item.preun);
            if (qty === null || unidad === null) return '0.00';
            const totalCent = (BigInt(qty) * unidad + escalaCent / 2n) / escalaCent;
            return formatoCentavos(totalCent);
        };

        const precioDesdeTotal = (total, cantidad) => {
            const qty = BigInt(cantidad);
            const escalado = (BigInt(total) * decimales / 100n + qty / 2n) / qty;
            const entero = escalado / decimales;
            const fraccion = String(escalado % decimales).padStart(10, '0');
            return `${entero}.${fraccion}`;
        };

        const dibujar = () => {
            body.innerHTML = ctx.state.detalles.map((item, i) => {
                item.preun = String(item.preun ?? '0');
                item.total = importe(item);
                const codig = item.vario
                    ? `<input type="text" maxlength="80" value="${ctx.esc(item.codig || '')}" data-cotiz-codig="${i}" aria-label="Código" autocomplete="off">`
                    : ctx.esc(item.codig || '—');
                const descr = item.vario
                    ? `<input type="text" maxlength="255" value="${ctx.esc(item.descr || '')}" data-cotiz-descr="${i}" aria-label="Descripción" autocomplete="off">`
                    : ctx.esc(item.descr || '');
                const canti = item.vario
                    ? `<input type="text" inputmode="numeric" pattern="[1-9][0-9]*" value="${ctx.esc(item.canti)}" data-cotiz-canti="${i}" aria-label="Cantidad" autocomplete="off">`
                    : ctx.esc(item.canti);
                const preun = item.vario
                    ? `<input type="text" inputmode="decimal" value="${precioDosDecimales(item.preun)}" data-cotiz-preun="${i}" aria-label="Precio unitario" autocomplete="off">`
                    : ctx.esc(precioDosDecimales(item.preun));
                const total = item.vario
                    ? `<input type="text" inputmode="decimal" value="${item.total}" data-cotiz-total-fila="${i}" aria-label="Total" autocomplete="off">`
                    : ctx.esc(item.total);
                return `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${codig}</td>
                        <td>${descr}</td>
                        <td>${canti}</td>
                        <td>${preun}</td>
                        <td>${total}</td>
                        <td><button type="button" data-cotiz-quitar="${i}" aria-label="Eliminar línea">×</button></td>
                    </tr>`;
            }).join('');
            ctx.api.calcularTotales?.();
        };

        const selectorEditable = 'input[data-cotiz-codig], input[data-cotiz-descr], input[data-cotiz-canti], input[data-cotiz-preun], input[data-cotiz-total-fila]';

        // Selecciona el valor completo; no lo borra hasta que el usuario escriba.
        body.addEventListener('focusin', evento => {
            const campo = evento.target.closest(selectorEditable);
            if (campo) campo.select();
        });

        body.addEventListener('click', evento => {
            const campo = evento.target.closest(selectorEditable);
            if (campo) campo.select();
        });

        body.addEventListener('input', evento => {
            const target = evento.target;
            const codig = target.closest('[data-cotiz-codig]');
            const descr = target.closest('[data-cotiz-descr]');
            if (codig) {
                const item = ctx.state.detalles[Number(codig.dataset.cotizCodig)];
                if (item) item.codig = codig.value;
                return;
            }
            if (descr) {
                const item = ctx.state.detalles[Number(descr.dataset.cotizDescr)];
                if (item) item.descr = descr.value;
                return;
            }

            const can = target.closest('[data-cotiz-canti]');
            const pre = target.closest('[data-cotiz-preun]');
            const total = target.closest('[data-cotiz-total-fila]');
            if (!can && !pre && !total) return;
            const campo = can || pre || total;
            const clave = can ? 'cotizCanti' : pre ? 'cotizPreun' : 'cotizTotalFila';
            const i = Number(campo.dataset[clave]);
            const item = ctx.state.detalles[i];
            if (!item) return;

            const texto = campo.value.trim();
            // Deja que el usuario vacíe el campo temporalmente al reemplazarlo.
            if (texto === '') return;

            if (can) {
                const qty = enteroSeguro(texto);
                if (qty === null) return;
                item.canti = qty;
                item.total = importe(item);
            } else if (pre) {
                const val = centavos(texto);
                if (val === null) return;
                item.preun = formatoCentavos(val);
                item.total = importe(item);
            } else {
                const qty = enteroSeguro(item.canti);
                const val = centavos(texto);
                if (qty === null || val === null) return;
                const precioInterno = precioDesdeTotal(val, qty);
                // Un total solo se acepta si 10 decimales internos permiten
                // reconstruir exactamente el importe redondeado de la línea.
                if (importe({canti:qty, preun:precioInterno}) !== formatoCentavos(val)) {
                    const mensaje = ctx.q('[data-cotiz-mensaje]');
                    if (mensaje) mensaje.textContent = 'Este total no puede representarse con 10 decimales de precio unitario para esa cantidad. Ajuste la cantidad o el total.';
                    return;
                }
                item.total = formatoCentavos(val);
                item.preun = precioInterno;
            }

            const mensaje = ctx.q('[data-cotiz-mensaje]');
            if (mensaje) mensaje.textContent = '';
            const totalInput = body.querySelector(`[data-cotiz-total-fila="${i}"]`);
            const preInput = body.querySelector(`[data-cotiz-preun="${i}"]`);
            if (totalInput && !total) totalInput.value = item.total;
            if (preInput && !pre) preInput.value = precioDosDecimales(item.preun);
            if (total && preInput) preInput.value = precioDosDecimales(item.preun);
            ctx.api.calcularTotales?.();
        });

        body.addEventListener('change', evento => {
            const can = evento.target.closest('[data-cotiz-canti]');
            const pre = evento.target.closest('[data-cotiz-preun]');
            const total = evento.target.closest('[data-cotiz-total-fila]');
            if (!can && !pre && !total) return;

            const campo = can || pre || total;
            const clave = can ? 'cotizCanti' : pre ? 'cotizPreun' : 'cotizTotalFila';
            const i = Number(campo.dataset[clave]);
            const item = ctx.state.detalles[i];
            if (!item) return;

            if (can) {
                const qty = enteroSeguro(campo.value);
                if (qty === null) {
                    dibujar();
                    return;
                }
                item.canti = qty;
                item.total = importe(item);
                dibujar();
                return;
            }

            if (pre) {
                const val = centavos(campo.value);
                if (val === null) {
                    dibujar();
                    return;
                }
                item.preun = formatoCentavos(val);
                item.total = importe(item);
                campo.value = precioDosDecimales(item.preun);
            } else {
                const val = centavos(campo.value);
                const qty = enteroSeguro(item.canti);
                if (val === null || qty === null) {
                    dibujar();
                    return;
                }
                const precioInterno = precioDesdeTotal(val, qty);
                if (importe({canti:qty, preun:precioInterno}) !== formatoCentavos(val)) {
                    const mensaje = ctx.q('[data-cotiz-mensaje]');
                    if (mensaje) mensaje.textContent = 'Este total no puede representarse con 10 decimales de precio unitario para esa cantidad. Ajuste la cantidad o el total.';
                    dibujar();
                    return;
                }
                item.total = formatoCentavos(val);
                item.preun = precioInterno;
                campo.value = item.total;
                const preInput = body.querySelector(`[data-cotiz-preun="${i}"]`);
                if (preInput) preInput.value = precioDosDecimales(item.preun);
            }
            const mensaje = ctx.q('[data-cotiz-mensaje]');
            if (mensaje) mensaje.textContent = '';
            ctx.api.calcularTotales?.();
        });

        body.addEventListener('click', evento => {
            const btn = evento.target.closest('[data-cotiz-quitar]');
            if (!btn) return;
            ctx.state.detalles.splice(Number(btn.dataset.cotizQuitar), 1);
            dibujar();
        });

        ctx.api.dibujarDetalles = dibujar;
        dibujar();
    };

    window.NURBAK_COTIZ_MODULOS.tabla = iniciar;
})();
