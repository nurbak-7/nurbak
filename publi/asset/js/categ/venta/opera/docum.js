/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/docum.js
   Módulo: Operaciones / Documento

   RESPONSABILIDAD:
   Gestionar fecha, tipo, serie y número de documento.

   NUMERACIÓN:
   El tipo se determina por el RUC/DNI:
   - 8 dígitos  → BOLETA
   - 11 dígitos → FACTURA

   El número mostrado es una vista previa.
   El número definitivo se reserva al confirmar.
============================================================ */

(() => {

    const formatearNumero = valor => {
        const numer = Number(valor);
        if (!Number.isFinite(numer) || numer < 0) return '';

        return numer <= 9999
            ? String(Math.trunc(numer)).padStart(4, '0')
            : String(Math.trunc(numer)).padStart(8, '0');
    };

    const iniciar = ctx => {

        const { q, state } = ctx;

        const fecha = q('[data-opera-fecha]');
        const tipo = q('[data-opera-tipo]');
        const serie = q('[data-opera-serie]');
        const numer = q('[data-opera-numer]');

        const hoy = new Date();
        const fechaHoy =
            `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

        let solicitud = 0;

        const limpiarNumeracion = () => {
            state.serie = '';
            state.numer = 0;
            if (serie) serie.value = '';
            if (numer) numer.value = '';
        };

        const determinarTipo = valor => {
            const docum = String(valor ?? '')
                .replace(/\D/g, '');

            if (docum.length === 8) return 'BOLETA';
            if (docum.length === 11) return 'FACTURA';

            return '';
        };

        const cargarNumeracion = async () => {
            const docum = String(
                q('[data-opera-docum]')?.value || ''
            ).replace(/\D/g, '');

            /* Sin RUC/DNI ingresado, el documento inicial es BOLETA. */
            const valor = determinarTipo(docum) || (docum === '' ? 'BOLETA' : '');
            const id = ++solicitud;

            state.tipo = valor;
            limpiarNumeracion();

            if (tipo) tipo.value = valor;

            if (!valor) return;

            try {
                const body = new URLSearchParams({
                    accion: 'numeracion',
                    docum
                });

                const respuesta = await fetch(ctx.R, {
                    method: 'POST',
                    body,
                    cache: 'no-store'
                });

                const datos = await respuesta.json();

                if (id !== solicitud) return;

                if (!respuesta.ok || !datos.ok || !datos.numeracion) {
                    throw new Error(
                        datos.mensaje ||
                        'No se pudo obtener la numeración del documento.'
                    );
                }

                const doc = datos.numeracion;
                const siguiente = Number(doc.siguiente);

                if (!doc.serie || !Number.isFinite(siguiente) || siguiente < 1) {
                    throw new Error(
                        'La numeración recibida desde SQL no es válida.'
                    );
                }

                state.tipo = valor;
                state.serie = String(doc.serie);
                state.numer = siguiente;

                if (tipo) tipo.value = state.tipo;
                if (serie) serie.value = state.serie;
                if (numer) numer.value = formatearNumero(state.numer);

            } catch (error) {
                if (id !== solicitud) return;

                limpiarNumeracion();
                state.tipo = valor;

                if (tipo) tipo.value = valor;

                console.error('Numeración Operaciones:', error);
            }
        };

        const docum = q('[data-opera-docum]');

        docum?.addEventListener('input', () => {
            solicitud++;

            const valorDocum = docum.value
                .replace(/\D/g, '');

            const valor =
                determinarTipo(valorDocum) ||
                (valorDocum === '' ? 'BOLETA' : '');

            state.tipo = valor;

            limpiarNumeracion();

            if (tipo) tipo.value = state.tipo;
            if (!state.tipo) return;

            void cargarNumeracion();
        });

        docum?.addEventListener('blur', () => {
            void cargarNumeracion();
        });

        /*
         * CHANGE cubre cambios realizados por otro submódulo,
         * por ejemplo cuando CLIENTE selecciona un registro y
         * coloca su RUC/DNI en este mismo campo.
         *
         * INPUT cubre la escritura directa del usuario.
         */
        docum?.addEventListener('change', () => {
            const valorDocum = docum.value
                .replace(/\D/g, '');

            const valor =
                determinarTipo(valorDocum) ||
                (valorDocum === '' ? 'BOLETA' : '');

            state.tipo = valor;
            limpiarNumeracion();

            if (tipo) tipo.value = state.tipo;
            if (!state.tipo) return;

            void cargarNumeracion();
        });

        fecha.value = fechaHoy;
        state.fecha = fechaHoy;
        const documInicial = String(
            q('[data-opera-docum]')?.value || ''
        ).replace(/\D/g, '');

        state.tipo = determinarTipo(documInicial) || 'BOLETA';
        if (tipo) tipo.value = state.tipo;

        fecha.addEventListener('change', () => {
            state.fecha = fecha.value;
        });

        void cargarNumeracion();
    };

    window.NURBAK_OPERA_MODULOS =
        window.NURBAK_OPERA_MODULOS || {};

    window.NURBAK_OPERA_MODULOS.docum = iniciar;
    window.NURBAK_OPERA_MODULOS.formatearNumero = formatearNumero;

})();
