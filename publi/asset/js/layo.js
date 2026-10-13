/* ============================================================
   Ruta: publi/asset/js/layo.js
   Función: Coordinar todo el JavaScript del sistema.

   LAYO →
   ├── core/ → funciones generales
   │   └── sincr/ → sincronización y estado del núcleo
   ├── categ/ → módulos de categorías
   ├── dash/ → funciones del dashboard
   │   ├── modal/ → ventanas
   │   └── login/ → acceso y sesión
   └── admin/ → administración
       ├── acce/ → acceso
       ├── permi/ → permisos
       ├── usuar/ → usuarios
       └── tiend/ → tiendas

   Este archivo es el coordinador raíz.
   No contiene lógica de módulos.
============================================================ */

(() => {
    // SINCRONIZACIÓN | FASE 1
    // El núcleo se carga primero y las áreas independientes se cargan en paralelo.
    const nucleo = [
        'publi/asset/js/core/sincr/sinc.js',
        'publi/asset/js/core/sincr/esta.js',
        'publi/asset/js/core/estil.js',
    ];

    const areas = [
        'publi/asset/js/categ/sincr/sinc.js',
        'publi/asset/js/admin/sincr/sinc.js',
        'publi/asset/js/categ/categ.js',
        'publi/asset/js/dash/dash.js',
        'publi/asset/js/admin/admin.js',
    ];

    // SINCRONIZACIÓN | FASE 2
    // Los adaptadores de área quedan disponibles antes de abrir cualquier módulo.

    const cargarToastOpcional = () => {
        const archivo = 'publi/asset/js/core/toast.js';
        if (document.querySelector(`[data-layo-toast="${archivo}"]`)) return;

        const script = document.createElement('script');
        script.src = archivo;
        script.dataset.layoToast = archivo;
        script.addEventListener('error', () => {
            console.warn('LAYO: Toast no disponible. El sistema continúa normalmente.');
        }, { once: true });
        document.body.appendChild(script);
    };

    const cargar = archivo => new Promise((resolver, rechazar) => {
        const nombr = archivo.split('/').pop();
        const selector = `[data-layo-js="${archivo}"]`;

        if (document.querySelector(selector)) {
            resolver();
            return;
        }

        const script = document.createElement('script');
        script.src = archivo;
        script.dataset.layoJs = archivo;
        script.addEventListener('load', resolver, { once: true });
        script.addEventListener('error', () => {
            rechazar(new Error(`No se pudo cargar ${nombr}`));
        }, { once: true });

        document.body.appendChild(script);
    });

    const iniciar = async () => {
        try {

            await Promise.all(nucleo.map(cargar));
            await Promise.all(areas.map(cargar));

            if (window.NURBAK_DASH_READY) {
                await window.NURBAK_DASH_READY;
            }

            window.NURBAK_ESTA?.poner('nuc', { listo: true });
            window.NURBAK_SINC?.emit('nuc:listo', { listo: true });

            // Toast es opcional: nunca bloquea LAYO ni la ventana universal.
            cargarToastOpcional();
        } catch (error) {
            console.error('LAYO:', error);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();