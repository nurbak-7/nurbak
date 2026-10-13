/* ============================================================
   Ruta: publi/asset/js/dash/dash.js
   Función: Cargar las dependencias propias del dashboard.
============================================================ */

(() => {
    const archivos = [
        'publi/asset/js/login/autor/autor.js',
        'publi/asset/js/dash/modal/modal.js',
        'publi/asset/js/dash/mini/mini.js'
    ];

    const cargar = ruta => new Promise((resolver, rechazar) => {
        if (document.querySelector(`[data-dash-js="${ruta}"]`)) {
            resolver();
            return;
        }

        const script = document.createElement('script');
        script.src = ruta;
        script.dataset.dashJs = ruta;
        script.addEventListener('load', resolver, { once: true });
        script.addEventListener('error', () => rechazar(new Error(`No se pudo cargar ${ruta}`)), { once: true });
        document.body.appendChild(script);
    });

    const iniciar = async () => {
        // SINCRONIZACIÓN | FASE 1
        // AUT, MODAL y MINI son recursos independientes durante la carga.
        await Promise.all(archivos.map(cargar));

        // Esperar a que MODAL y MINI terminen su inicialización real,
        // no solamente a que sus archivos JS hayan sido descargados.
        const esp = [
            window.NURBAK_MODAL_READY,
            window.NURBAK_MINI_READY,
        ].filter(valor => valor && typeof valor.then === 'function');

        await Promise.all(esp);
        window.NURBAK_ESTA?.poner('dash', { listo: true });
        window.NURBAK_SINC?.emit('dash:listo', { listo: true });
    };

    window.NURBAK_DASH_READY = new Promise((resolver, rechazar) => {
        const arrancar = async () => {
            try {
                await iniciar();
                resolver(true);
            } catch (error) {
                rechazar(error);
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', arrancar, { once: true });
        } else {
            arrancar();
        }
    });
})();
