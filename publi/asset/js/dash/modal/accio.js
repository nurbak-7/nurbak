/* ============================================================
   Ruta: publi/asset/js/dash/modal/accio.js
   Función: Ejecutar las acciones propias de cada modal.

   Responsabilidad:
   - Minimizar.
   - Restaurar.
   - Maximizar.
   - Cerrar.
   - Conectar los controles.
============================================================ */

window.NURBAK_MODAL_ACCIO = (() => {

    const crear = ({ modalsAbiertas, traerAlFrente, ajustarDentroDelArea, ordenarMinimizadas }) => {

        const restaurarModal = (modal) => {
            const estabaMaximizada = modal.dataset.previaMaximizada === 'true';

            modal.classList.remove('is-minimizada');
            modal.style.left = modal.dataset.previaIzquierda || modal.style.left;
            modal.style.top = modal.dataset.previaArriba || modal.style.top;

            if (estabaMaximizada) {
                modal.classList.add('is-maximizada');
            }

            traerAlFrente(modal);
            ajustarDentroDelArea(modal);
            ordenarMinimizadas();

            
        };

        const minimizarModal = (modal) => {
            if (modal.classList.contains('is-minimizada')) {
                restaurarModal(modal);
                return;
            }

            modal.dataset.previaIzquierda = modal.style.left;
            modal.dataset.previaArriba = modal.style.top;
            modal.dataset.previaMaximizada = String(
                modal.classList.contains('is-maximizada')
            );

            modal.classList.remove('is-maximizada');
            modal.classList.add('is-minimizada');
            traerAlFrente(modal);
            ordenarMinimizadas();

            
        };

        const cambiarMaximizado = (modal) => {
            if (modal.classList.contains('is-minimizada')) {
                restaurarModal(modal);
                modal.classList.add('is-maximizada');
                traerAlFrente(modal);

                
                return;
            }

            const estaMaximizada = modal.classList.toggle('is-maximizada');
            traerAlFrente(modal);

            
        };

        const cerrarModal = (modal) => {
            const modulo = modal.dataset.modulo;
            const clave = modal.dataset.clave || modulo;

            /*
               Avisar antes de retirar la modal.
               Los módulos interesados pueden liberar recursos propios.
            */
            // SINCRONIZACIÓN | FASE 1
            // El núcleo publica el cierre para que el módulo libere sus recursos.
            const deta = { modulo, clave, modal };
            window.NURBAK_SINC?.emit('modal:cerrando', deta);

            modalsAbiertas.delete(clave);
            modal.remove();

            window.NURBAK_SINC?.emit('modal:cerrada', deta);
            ordenarMinimizadas();


        };

        const prepararControles = (modal) => {
            modal.querySelectorAll('[data-modal-accion]').forEach((control) => {
                control.addEventListener('click', () => {
                    const accion = control.dataset.modalAccion;

                    if (accion === 'minimizar') {
                        minimizarModal(modal);
                    }

                    if (accion === 'maximizar') {
                        cambiarMaximizado(modal);
                    }

                    if (accion === 'cerrar') {
                        cerrarModal(modal);
                    }
                });
            });
        };

        return { prepararControles, restaurarModal, minimizarModal, cambiarMaximizado, cerrarModal };
    };

    return { crear };
})();
