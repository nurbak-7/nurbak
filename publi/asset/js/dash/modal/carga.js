/* ============================================================
   Ruta: publi/asset/js/dash/modal/carga.js
   Función: Cargar la vista PHP y coordinar las cargas independientes
            de JavaScript y CSS para el recurso solicitado.
============================================================ */

window.NURBAK_MODAL_CARGA = (() => {
    const crear = ({ cargarJs, cargarCss }) => {
        const cargar = async (modal, rutaVista, recu, modulo, areaMenu = '') => {
            const contenido = modal.querySelector('[data-modal-contenido]');
            const area = areaMenu || modal.dataset.area || '';
            const clave = String(recu || '');
            const nombreModulo = modulo || modal.dataset.modulo || '';

            if (!contenido) return;

            if (!rutaVista || !clave) {
                contenido.innerHTML = '<p>El submódulo seleccionado no tiene una vista asignada.</p>';
                return;
            }

            modal.dataset.modalEstado = 'cargando';
            window.NURBAK_SINC?.emit('modal:cargando', {
                modulo: modal.dataset.modulo || '', area, modal, rutaVista
            });
            contenido.innerHTML = '<p>Cargando contenido...</p>';

            window.NURBAK_SINC?.emit('modu:cargando', {
                modulo: nombreModulo, area, recu: clave, modal, rutaVista
            });

            try {
                const respuesta = await fetch(rutaVista, {
                    method: 'GET',
                    cache: 'no-store'
                });
                if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);

                contenido.innerHTML = await respuesta.text();

                // Ambos coordinadores trabajan en paralelo, cada uno en su área.
                await Promise.all([
                    cargarJs(clave),
                    cargarCss(clave)
                ]);

                modal.dataset.modalEstado = 'listo';
                window.NURBAK_SINC?.emit('modu:cargado', {
                    modulo: nombreModulo, area, recu: clave, modal, rutaVista
                });
                window.NURBAK_SINC?.emit('modal:listo', {
                    modulo: modal.dataset.modulo || '', area, modal, rutaVista
                });
            } catch (error) {
                modal.dataset.modalEstado = 'error';
                contenido.innerHTML = '<p>No se pudo cargar el archivo del módulo.</p>';
                const mensaje = error instanceof Error ? error.message : String(error);
                window.NURBAK_SINC?.emit('modu:error', {
                    modulo: nombreModulo, area, recu: clave, modal, rutaVista, error: mensaje
                });
                window.NURBAK_SINC?.emit('modal:error', {
                    modulo: modal.dataset.modulo || '', area, modal, rutaVista, error: mensaje
                });
            }
        };

        return { cargar };
    };

    return { crear };
})();
