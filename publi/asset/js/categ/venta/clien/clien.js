/* ============================================================
   Ruta: publi/asset/js/categ/venta/clien/clien.js
   Módulo: Ventas / Cliente

   RESPONSABILIDAD:
   Coordinar el proceso de cliente.

   Flujo:
   RUC/DNI → LOCAL → EXTERNO → datos editables.
============================================================ */
(() => {
    const archivos = ['local.js', 'exter.js', 'edita.js'];

    /* INTEGRACIÓN | FASE 3: evitar cargas duplicadas entre operaciones. */
    const pendientes = new Map();

    const cargar = archivo => {
        if (pendientes.has(archivo)) {
            return pendientes.get(archivo);
        }

        const tarea = new Promise((ok, error) => {
        if (document.querySelector(`[data-opera-clie-js="${archivo}"]`)) {
            ok();
            return;
        }

        const script = document.createElement('script');
        script.src = `publi/asset/js/categ/venta/clien/${archivo}`;
        script.dataset.operaClienJs = archivo;
        script.onload = ok;
        script.onerror = () => error(new Error(`No se pudo cargar ${archivo}`));
        document.body.appendChild(script);
        });

        pendientes.set(archivo, tarea);
        return tarea.finally(() => pendientes.delete(archivo));
    };

    const iniciar = async ctx => {
        await Promise.all(archivos.map(cargar));

        const { q, state } = ctx;
        const docum = q('[data-opera-docum]');
        const razon = q('[data-opera-razon]');
        const direc = q('[data-opera-direc]');
        const clien = q('[data-opera-clien]');

        if (!docum) return;

        window.NURBAK_OPERA_CLIENTE.edit?.(ctx);

        const llenar = datos => {
            const anterior = docum.value;
            docum.value = datos.docum || docum.value || '';

            /*
             * El valor puede ser colocado programáticamente al
             * seleccionar/recuperar un cliente. Notificamos el cambio
             * al campo para que DOCUMENTO recalcule tipo y numeración.
             */
            if (docum.value !== anterior) {
                docum.dispatchEvent(new Event('change', { bubbles: true }));
            }

            razon.value = datos.razon || datos.razon || '';
            direc.value = datos.direc || '';

            state.clienId = datos.id ? String(datos.id) : '';
            state.clien = {
                id: state.clienId,
                docum: docum.value.trim(),
                razon: razon.value.trim(),
                direc: direc.value.trim()
            };

            if (clien && state.clienId) clien.value = state.clienId;
        };

        let temporizador = null;
        let solicitud = 0;
        let ultimaConsulta = '';

        const esDocumentoCompleto = numer => {
            if (numer.length === 11) return true;

            if (numer.length === 8) {
                /* RUC peruano: 10, 15, 17 o 20. */
                if (/^(10|15|17|20)/.test(numer)) return false;
                return true;
            }

            return false;
        };

        const procesar = async () => {
            const numer = docum.value.replace(/\D/g, '');

            if (!esDocumentoCompleto(numer)) return;
            if (numer === ultimaConsulta) return;

            const idSolicitud = ++solicitud;
            ultimaConsulta = numer;
            state.clienId = '';

            try {
                const local =
                    await window.NURBAK_OPERA_CLIENTE.local(
                        ctx,
                        numer
                    );

                if (
                    idSolicitud !== solicitud ||
                    docum.value.replace(/\D/g, '') !== numer
                ) return;

                if (local) {
                    llenar(local);
                    return;
                }

                const externo =
                    await window.NURBAK_OPERA_CLIENTE.externo(
                        ctx,
                        numer
                    );

                if (
                    idSolicitud !== solicitud ||
                    docum.value.replace(/\D/g, '') !== numer
                ) return;

                if (externo.ok && externo.clien) {
                    llenar({
                        docum: numer,
                        razon:
                            externo.clien.razon,
                        direc:
                            externo.clien.direc
                    });
                }

            } catch (error) {

                if (idSolicitud !== solicitud) return;

                console.warn(
                    'Cliente:',
                    error.message
                );
            }
        };

        docum.addEventListener('input', () => {

            state.clienId = '';
            state.clien = null;
            clearTimeout(temporizador);
            solicitud++;

            const numer =
                docum.value.replace(/\D/g, '');

            if (!esDocumentoCompleto(numer)) return;

            temporizador = setTimeout(
                procesar,
                550
            );
        });

        docum.addEventListener('blur', () => {

            clearTimeout(temporizador);

            const numer =
                docum.value.replace(/\D/g, '');

            if (esDocumentoCompleto(numer)) {
                procesar();
            }
        });

        clien?.addEventListener('change', () => {
            const option = clien.options[clien.selectedIndex];
            if (!option || !clien.value) return;
            llenar({
                id: clien.value,
                docum: option.dataset.docum || '',
                razon: option.dataset.razon || '',
                direc: option.dataset.direc || ''
            });
        });
    };

    window.NURBAK_OPERA_MODULOS = window.NURBAK_OPERA_MODULOS || {};
    window.NURBAK_OPERA_MODULOS.clien = iniciar;
})();
