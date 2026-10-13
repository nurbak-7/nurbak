/* ============================================================
   Ruta: publi/asset/js/dash/modal/recup.js
   Función: Cargar exclusivamente JavaScript funcional de módulos.
   La carga y gestión de CSS pertenece a core/estil.js.
============================================================ */

window.NURBAK_MODAL_RECUP = (() => {
    const jsPend = new Map();

    const obtenerRuta = recu => {
        const clave = String(recu || '');
        const mapa = clave.startsWith('admi/')
            ? window.NURBAK_ADMI
            : window.NURBAK_CATE;
        return mapa?.rutaJs?.(clave) || '';
    };

    const cargarJs = ruta => {
        if (!ruta) {
            return Promise.reject(new Error('No se indicó una ruta JavaScript.'));
        }

        if (jsPend.has(ruta)) return jsPend.get(ruta);

        const promesa = new Promise((resolver, rechazar) => {
            const selector = `[data-modu-js="${ruta}"]`;
            const existente = document.querySelector(selector);

            if (existente) {
                if (existente.dataset.moduEstado === 'cargado') {
                    resolver();
                    return;
                }
                if (existente.dataset.moduEstado === 'cargando') {
                    existente.addEventListener('load', resolver, { once: true });
                    existente.addEventListener('error', () => {
                        existente.remove();
                        rechazar(new Error(`No se pudo cargar ${ruta}`));
                    }, { once: true });
                    return;
                }
                existente.remove();
            }

            const script = document.createElement('script');
            script.src = ruta;
            script.dataset.moduJs = ruta;
            script.dataset.moduEstado = 'cargando';
            script.addEventListener('load', () => {
                script.dataset.moduEstado = 'cargado';
                resolver();
            }, { once: true });
            script.addEventListener('error', () => {
                script.remove();
                rechazar(new Error(`No se pudo cargar ${ruta}`));
            }, { once: true });
            document.body.appendChild(script);
        });

        jsPend.set(ruta, promesa);
        promesa.catch(() => jsPend.delete(ruta));
        return promesa;
    };

    const crear = () => ({
        cargar: async recu => {
            const ruta = obtenerRuta(recu);
            if (!ruta) throw new Error(`No hay JavaScript definido para ${recu}`);
            await cargarJs(ruta);
        }
    });

    return { crear };
})();
