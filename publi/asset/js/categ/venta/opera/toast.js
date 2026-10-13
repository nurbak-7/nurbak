/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/toast.js
   Módulo: Ventas / Operaciones / Toast

   NIVELES:
   1. Inicio del módulo
   2. Capa de Toast
   3. Cerrar Toast
   4. Mostrar resultado
   5. Confirmación
   6. API del módulo


   RESPONSABILIDAD:
   - Mostrar la confirmación centrada de Guardar/Cancelar.
   - Mostrar el resultado temporal de la acción.
   - No modificar fetch ni otras APIs del sistema.
============================================================ */
(() => {
    const MOD = 'vent/oper';

    const iniciar = ({ ventana } = {}) => {
        const root = ventana?.querySelector('[data-opera]');
        if (!root) return null;

        if (root.__nurToas) return root.__nurToas;

        let conf = null;
        let activo = false;
        let reloj = 0;

        const capa = () => {
            let c = document.querySelector('[data-opera-toas]');

            if (!c) {
                c = document.createElement('div');
                c.dataset.operaToas = '';
                document.body.appendChild(c);
            }

            return c;
        };

        const quit = () => {
            if (reloj) {
                clearTimeout(reloj);
                reloj = 0;
            }

            if (conf) {
                conf.remove();
                conf = null;
            }

            activo = false;
        };

        const resu = (texto, tipo = 'exi', ms = 2200) => {

            const c = capa();
            const t = document.createElement('div');

            t.dataset.operaToasResu = tipo;
            t.textContent = String(texto ?? '');
            c.appendChild(t);

            requestAnimationFrame(() => {
                t.dataset.operaToasOn = 'si';
            });

            window.setTimeout(() => {
                t.dataset.operaToasOn = 'no';
                window.setTimeout(() => t.remove(), 180);
            }, ms);

            return t;
        };

        const confm = (tipo, guar, canc) => {
            if (activo) return;
            quit();
            activo = true;

            const c = capa();

            conf = document.createElement('div');
            conf.dataset.operaToasConf = '';
            const mensaje = tipo === 'guar'
                ? 'La operación actual se guardará y quedará registrada en el sistema.'
                : 'La operación actual se cancelará y no será registrada.';

            conf.innerHTML = `
                <div data-opera-toas-conf-box role="dialog" aria-modal="true">
                    <p>${mensaje}</p>
                    <div data-opera-toas-conf-btn>
                        <button type="button" data-opera-toas-guar>[ Guardar ]</button>
                        <button type="button" data-opera-toas-canc>[ Cancelar ]</button>
                    </div>
                </div>
            `;

            c.appendChild(conf);

            const btnGuar = conf.querySelector('[data-opera-toas-guar]');
            const btnCanc = conf.querySelector('[data-opera-toas-canc]');

            const activar = (valor) => {
                btnGuar.disabled = valor;
                btnCanc.disabled = valor;
            };

            const ejecutar = async funcion => {
                if (!activo) return;

                activar(true);

                try {
                    await funcion?.();
                } catch (error) {
                    activar(false);
                    resu(
                        error?.message || 'No se pudo completar la operación.',
                        'err'
                    );
                }
            };

            btnGuar?.addEventListener('click', () => {
                void ejecutar(guar);
            });

            btnCanc?.addEventListener('click', () => {
                void ejecutar(canc);
            });
        };

        const api = {
            resu,
            conf: confm,
            quit
        };

        root.__nurToas = api;
        window.NURBAK_TOAST?.registrar(MOD, api);
        return api;
    };

    window.NURBAK_TOAST_MODULOS =
        window.NURBAK_TOAST_MODULOS || {};

    window.NURBAK_TOAST_MODULOS[MOD] = iniciar;
})();
