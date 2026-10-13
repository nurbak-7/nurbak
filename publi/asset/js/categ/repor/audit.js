/* ============================================================
   Ruta: publi/asset/js/categ/repor/audit.js
   Módulo: Reportes / Auditoría

   RESPONSABILIDAD:
   - Archivo principal del módulo.
   - Cargar el archivo de análisis.
   - Entregar la ventana al análisis.
============================================================ */

const cargarAnalisisaudi = () => {

    const ruta = 'publi/asset/js/categ/repor/audit/audit-anali.js';

    const existente = document.querySelector(
        `[data-repo-analisis="${ruta}"]`
    );

    if (existente) {
        return Promise.resolve();
    }

    return new Promise((resolver, rechazar) => {

        const programa = document.createElement('script');

        programa.src = ruta;
        programa.dataset.repoAnalisis = ruta;

        programa.addEventListener(
            'load',
            resolver,
            { once: true }
        );

        programa.addEventListener(
            'error',
            () => rechazar(
                new Error(`No se pudo cargar ${ruta}`)
            ),
            { once: true }
        );

        document.body.appendChild(programa);
    });
};


/* ============================================================
   EVENTO DEL SISTEMA DE VENTANAS
============================================================ */

document.addEventListener(
    'modu:cargado',
    async (e) => {

        if (e.detail?.modulo !== 'auditoria') {
            return;
        }

        const ventana = e.detail.modal;

        if (!ventana) {
            return;
        }

        try {
            await cargarAnalisisaudi();

            if (window.NURBAK_REPO_AUDI_ANALISIS?.iniciar) {
                window.NURBAK_REPO_AUDI_ANALISIS.iniciar(ventana);
            }
        } catch (error) {
            console.error('Reportes: error cargando análisis.', error);
        }
    }
);
