/* ============================================================
   Ruta: publi/asset/js/categ/venta/nvent/toast.js
   Módulo: Ventas / Nota de Venta / Toast
   RESPONSABILIDAD: confirmaciones y avisos propios de nvent.
   Se registra en el coordinador Toast global existente.
============================================================ */
(() => {
    const MOD = 'vent/nvent';
    const iniciar = ({ ventana } = {}) => {
        const root = ventana?.querySelector('[data-nven-form]');
        if (!root) return null;
        if (root.__nvenToas) return root.__nvenToas;

        let capa = null;
        const obtenerCapa = () => {
            if (capa?.isConnected) return capa;
            capa = document.createElement('div');
            capa.dataset.nvenToas = '';
            document.body.appendChild(capa);
            return capa;
        };
        const quit = () => capa?.querySelectorAll('[data-nven-toas-conf]').forEach(el => el.remove());
        const resu = (texto, tipo = 'exi', ms = 2400) => {
            const c = obtenerCapa();
            const t = document.createElement('div');
            t.dataset.nvenToasResu = ['exi', 'err', 'info', 'alerta'].includes(tipo) ? tipo : 'info';
            t.textContent = String(texto ?? '');
            c.appendChild(t);
            requestAnimationFrame(() => { t.dataset.nvenToasOn = 'si'; });
            setTimeout(() => {
                t.dataset.nvenToasOn = 'no';
                setTimeout(() => t.remove(), 200);
            }, ms);
            return t;
        };
        const conf = (tipo, guardar, cancelar) => {
            const c = obtenerCapa();
            quit();
            const box = document.createElement('div');
            box.dataset.nvenToasConf = '';
            const esGuardar = tipo === 'guar';
            box.innerHTML = `<div data-nven-toas-conf-box role="dialog" aria-modal="true"><p>${esGuardar
                ? 'La Nota de Venta se guardará y quedará registrada en el sistema.'
                : 'La Nota de Venta actual se cerrará sin guardarse.'}</p><div data-nven-toas-conf-btn><button type="button" data-nven-toas-act>${esGuardar ? '[ Guardar ]' : '[ Cancelar ]'}</button><button type="button" data-nven-toas-vol>[ Volver ]</button></div></div>`;
            c.appendChild(box);
            const act = box.querySelector('[data-nven-toas-act]');
            const volver = box.querySelector('[data-nven-toas-vol]');
            act?.addEventListener('click', async () => {
                act.disabled = true;
                volver.disabled = true;
                try {
                    await (esGuardar ? guardar : cancelar)?.();
                    box.remove();
                } catch (error) {
                    act.disabled = false;
                    volver.disabled = false;
                    resu(error?.message || 'No se pudo completar la acción.', 'err');
                }
            });
            volver?.addEventListener('click', () => box.remove());
        };
        const api = { resu, conf, quit };
        root.__nvenToas = api;
        window.NURBAK_TOAST?.registrar(MOD, api);
        return api;
    };
    window.NURBAK_TOAST_MODULOS = window.NURBAK_TOAST_MODULOS || {};
    window.NURBAK_TOAST_MODULOS[MOD] = iniciar;
})();
