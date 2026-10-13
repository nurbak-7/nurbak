/* ============================================================
   Ruta: publi/asset/js/categ/catal/imext/imext.js
   Módulo: Catálogo / ASINC / IMEXT

   Función:
   Coordinador de los módulos IMPORT y EXPORT.

   RESPONSABILIDAD:
   - Cargar los JS propios de IMEXT.
   - No contiene lógica de importación ni exportación.
============================================================ */

(() => {
    const archivos = [
        'impor.js',
        'expor.js'
    ];

    /* INTEGRACIÓN | FASE 3: cargas compartidas y paralelas. */
    const pendientes = new Map();

    const cargar = archivo => {
        if (pendientes.has(archivo)) {
            return pendientes.get(archivo);
        }

        const tarea = new Promise((ok, error) => {
        const existe = document.querySelector(`[data-imext-js="${archivo}"]`);

        if (existe) {
            if (existe.dataset.imextEstado === 'cargado') {
                ok();
                return;
            }

            if (existe.dataset.imextEstado === 'cargando') {
                existe.addEventListener('load', ok, {once:true});
                existe.addEventListener('error', () => {
                    existe.remove();
                    error(new Error(`No se pudo cargar ${archivo}`));
                }, {once:true});
                return;
            }

            existe.remove();
        }

        const script = document.createElement('script');
        script.src = `publi/asset/js/categ/catal/imext/${archivo}`;
        script.dataset.imextJs = archivo;
        script.dataset.imextEstado = 'cargando';
        script.onload = () => {
            script.dataset.imextEstado = 'cargado';
            ok();
        };
        script.onerror = () =>
            error(new Error(`No se pudo cargar ${archivo}`));

        document.body.appendChild(script);
        });

        pendientes.set(archivo, tarea);
        return tarea.finally(() => pendientes.delete(archivo));
    };

    const iniciar = async ventana => {
        if (!ventana || ventana.dataset.imextIniciado === 'si' || ventana.dataset.imextIniciando === 'si') {
            return;
        }

        ventana.dataset.imextIniciando = 'si';
        try {
            await Promise.all(archivos.map(cargar));

            document.dispatchEvent(new CustomEvent('imext:cargado', {
            detail: { ventana }
            }));
            ventana.dataset.imextIniciado = 'si';
        } finally {
            delete ventana.dataset.imextIniciando;
        }
    };

    document.addEventListener('modu:cargado', evento => {
        const modulo = evento.detail?.modulo;

        if (modulo !== 'imext') {
            return;
        }

        iniciar(evento.detail.modal).catch(error => {
            console.error('IMEXT:', error);
        });
    });
})();
