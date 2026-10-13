/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/pagos.js
   Función: Recurso del sistema.

   NIVELES:
   1. Inicio del módulo
   2. Forma de pago
   3. Mostrar sugerencias
   4. Escribir medio de pago
   5. Enter directo
   6. Cerrar sugerencias
   7. Estado inicial
   8. Validar pago
   9. API del módulo
   FINAL. Registro del módulo

   Módulo: Operaciones / Pago

   RESPONSABILIDAD:
   Forma y medio de pagos.
============================================================ */

(() => {

    /* ========================================================
       NIVEL 1 — INICIO DEL MÓDULO
    ======================================================== */

    const iniciar = ctx => {

        const { q, state } = ctx;

        const forma =
            q('[data-opera-forma-pago]');

        const medio =
            q('[data-opera-medio-pago]');

        const sugerencias =
            q('[data-opera-medio-sugerencias]');

        const opcionesMedio = [
            'Efectivo',
            'Yape',
            'Plin',
            'Transferencia',
            'Tarjeta'
        ];


        /* ====================================================
           NIVEL 2 — FORMA DE PAGO
        ==================================================== */

        forma?.addEventListener('change', () => {

            state.formaPago =
                forma.value;

        });


        /* ====================================================
           NIVEL 3 — MOSTRAR SUGERENCIAS
        ==================================================== */

        const mostrarSugerencias = () => {

            if (!medio || !sugerencias) {
                return;
            }

            const texto =
                medio.value
                    .trim()
                    .toLowerCase();

            sugerencias.innerHTML = '';

            if (!texto) {
                return;
            }

            const coincidencias =
                opcionesMedio.filter(opcion =>
                    opcion
                        .toLowerCase()
                        .startsWith(texto)
                );

            coincidencias.forEach(opcion => {

                const item =
                    document.createElement('div');

                item.textContent =
                    opcion;

                item.addEventListener(
                    'mousedown',
                    e => {

                        e.preventDefault();

                        medio.value =
                            opcion;

                        state.medioPago =
                            opcion;

                        sugerencias.innerHTML =
                            '';

                    }
                );

                sugerencias.appendChild(
                    item
                );

            });

        };


        /* ====================================================
           NIVEL 4 — ESCRIBIR MEDIO DE PAGO
        ==================================================== */

        medio?.addEventListener(
            'input',
            () => {

                state.medioPago =
                    medio.value;

                mostrarSugerencias();

            }
        );


        /* ====================================================
           NIVEL 5 — ENTER DIRECTO
        ==================================================== */

        medio?.addEventListener(
            'keydown',
            e => {

                if (e.key !== 'Enter') {
                    return;
                }

                const texto =
                    medio.value
                        .trim()
                        .toLowerCase();

                if (!texto) {
                    return;
                }

                const encontrado =
                    opcionesMedio.find(opcion =>
                        opcion
                            .toLowerCase()
                            .startsWith(texto)
                    );

                if (!encontrado) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                medio.value =
                    encontrado;

                state.medioPago =
                    encontrado;

                sugerencias.innerHTML =
                    '';

            },
            true
        );


        /* ====================================================
           NIVEL 6 — CERRAR SUGERENCIAS
        ==================================================== */

        medio?.addEventListener(
            'blur',
            () => {

                setTimeout(() => {

                    if (sugerencias) {
                        sugerencias.innerHTML =
                            '';
                    }

                }, 150);

            }
        );


        /* ====================================================
           NIVEL 7 — ESTADO INICIAL
        ==================================================== */

        state.formaPago =
            forma?.value || '';

        state.medioPago =
            medio?.value || '';


        /* ====================================================
           NIVEL 8 — VALIDAR PAGO

           PAGOS lee sus propios campos y devuelve únicamente
           el resultado de la validación.
        ==================================================== */

        const validarPago = () => {

            const faltantes = [];

            const valorForma =
                String(
                    forma?.value ||
                    state.formaPago ||
                    ''
                ).trim();

            const valorMedio =
                String(
                    medio?.value ||
                    state.medioPago ||
                    ''
                ).trim();

            state.formaPago =
                valorForma;

            state.medioPago =
                valorMedio;

            if (!valorForma) {
                faltantes.push(
                    'forma de pago'
                );
            }

            if (!valorMedio) {
                faltantes.push(
                    'medio de pago'
                );
            }

            return {
                ok:
                    faltantes.length === 0,
                faltantes
            };

        };


        /* ====================================================
           NIVEL 9 — API DEL MÓDULO
        ==================================================== */

        ctx.api.validarPago =
            validarPago;

    };


    /* ========================================================
       NIVEL FINAL — REGISTRO DEL MÓDULO
    ======================================================== */

    window.NURBAK_OPERA_MODULOS =
        window.NURBAK_OPERA_MODULOS || {};

    window.NURBAK_OPERA_MODULOS.pagos =
        iniciar;

})();