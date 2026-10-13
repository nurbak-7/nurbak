/* ============================================================
   Ruta: publi/asset/js/dash/modal/movim.js
   Función: Controlar el movimiento y redimensionamiento de las modals.

   La modal se mueve desde su cabecera y se redimensiona desde
   sus bordes y esquinas. Los botones no activan ninguna de las dos acciones.
============================================================ */

window.NURBAK_MODAL_MOVIM = (() => {

    const crear = ({ areaModals, traerAlFrente }) => {

        const preparar = (modal) => {
            const cabecera = modal.querySelector('[data-modal-arrastre]');

            if (!cabecera) {
                return;
            }

            let moviendo = false;
            let diferenciaX = 0;
            let diferenciaY = 0;

            cabecera.addEventListener('pointerdown', (evento) => {
                if (
                    evento.button !== 0 ||
                    !(evento.buttons & 1) ||
                    evento.target.closest('[data-modal-accion]') ||
                    modal.classList.contains('is-maximizada') ||
                    modal.classList.contains('is-minimizada')
                ) {
                    return;
                }

                const rectModal = modal.getBoundingClientRect();
                const rectArea = areaModals.getBoundingClientRect();

                moviendo = true;
                diferenciaX = evento.clientX - rectModal.left;
                diferenciaY = evento.clientY - rectModal.top;

                modal.style.left = `${rectModal.left - rectArea.left}px`;
                modal.style.top = `${rectModal.top - rectArea.top}px`;
                modal.style.right = 'auto';
                traerAlFrente(modal);

                cabecera.setPointerCapture(evento.pointerId);
                evento.preventDefault();
            });

            cabecera.addEventListener('pointermove', (evento) => {
                if (!moviendo) {
                    return;
                }

                /* El movimiento solo existe mientras el botón izquierdo siga presionado. */
                if (!(evento.buttons & 1)) {
                    terminarArrastre(evento);
                    return;
                }

                const rectArea = areaModals.getBoundingClientRect();
                const maximoX = Math.max(0, areaModals.clientWidth - modal.offsetWidth);
                const maximoY = Math.max(0, areaModals.clientHeight - modal.offsetHeight);
                const posicionX = evento.clientX - rectArea.left - diferenciaX;
                const posicionY = evento.clientY - rectArea.top - diferenciaY;
                const nuevaX = Math.max(0, Math.min(maximoX, posicionX));
                const nuevaY = Math.max(0, Math.min(maximoY, posicionY));

                modal.style.left = `${nuevaX}px`;
                modal.style.top = `${nuevaY}px`;
            });

            const terminarArrastre = (evento) => {
                if (!moviendo) {
                    return;
                }

                moviendo = false;

                if (cabecera.hasPointerCapture(evento.pointerId)) {
                    cabecera.releasePointerCapture(evento.pointerId);
                }

                
            };

            cabecera.addEventListener('pointerup', terminarArrastre);
            cabecera.addEventListener('pointercancel', terminarArrastre);
            window.addEventListener('pointerup', terminarArrastre);
            window.addEventListener('pointercancel', terminarArrastre);
            window.addEventListener('blur', () => {
                moviendo = false;
                modal.style.cursor = '';
            });
        };

        /* ====================================================
           NIVEL 2 — REDIMENSIONAR MODALANA

           El usuario puede cambiar ancho y alto desde cualquiera
           de los cuatro bordes y las cuatro esquinas.
           El tamaño queda limitado por el área visible del dashboard.
        ==================================================== */

        const prepararRedimension = (modal) => {
            const zona = 8;
            const minimoAncho = 320;
            const minimoAlto = 300;

            let redimensionando = false;
            let borde = '';
            let inicioX = 0;
            let inicioY = 0;
            let inicioLeft = 0;
            let inicioTop = 0;
            let inicioAncho = 0;
            let inicioAlto = 0;

            const obtenerBorde = (evento) => {
                if (
                    modal.classList.contains('is-maximizada') ||
                    modal.classList.contains('is-minimizada')
                ) {
                    return '';
                }

                const rect = modal.getBoundingClientRect();
                const izquierda = evento.clientX - rect.left <= zona;
                const derecha = rect.right - evento.clientX <= zona;
                const arriba = evento.clientY - rect.top <= zona;
                const abajo = rect.bottom - evento.clientY <= zona;

                if (arriba && izquierda) return 'nw';
                if (arriba && derecha) return 'ne';
                if (abajo && izquierda) return 'sw';
                if (abajo && derecha) return 'se';
                if (arriba) return 'n';
                if (abajo) return 's';
                if (izquierda) return 'w';
                if (derecha) return 'e';
                return '';
            };

            const actualizarCursor = (evento) => {
                if (redimensionando) return;

                const bordeActual = obtenerBorde(evento);
                const cursores = {
                    n: 'ns-resize',
                    s: 'ns-resize',
                    e: 'ew-resize',
                    w: 'ew-resize',
                    ne: 'nesw-resize',
                    sw: 'nesw-resize',
                    nw: 'nwse-resize',
                    se: 'nwse-resize',
                };

                modal.style.cursor = cursores[bordeActual] || '';
            };

            modal.addEventListener('pointermove', actualizarCursor);

            modal.addEventListener('pointerdown', (evento) => {
                if (evento.button !== 0 || !(evento.buttons & 1)) {
                    return;
                }

                const bordeActual = obtenerBorde(evento);

                if (!bordeActual) return;

                const rectModal = modal.getBoundingClientRect();
                const rectArea = areaModals.getBoundingClientRect();

                redimensionando = true;
                borde = bordeActual;
                inicioX = evento.clientX;
                inicioY = evento.clientY;
                inicioLeft = rectModal.left - rectArea.left;
                inicioTop = rectModal.top - rectArea.top;
                inicioAncho = rectModal.width;
                inicioAlto = rectModal.height;

                modal.style.left = `${inicioLeft}px`;
                modal.style.top = `${inicioTop}px`;
                modal.style.right = 'auto';
                modal.style.width = `${inicioAncho}px`;
                modal.style.height = `${inicioAlto}px`;
                modal.style.cursor = {
                    n: 'ns-resize', s: 'ns-resize',
                    e: 'ew-resize', w: 'ew-resize',
                    ne: 'nesw-resize', sw: 'nesw-resize',
                    nw: 'nwse-resize', se: 'nwse-resize',
                }[bordeActual];

                traerAlFrente(modal);
                modal.setPointerCapture(evento.pointerId);
                evento.preventDefault();
                evento.stopPropagation();
            });

            modal.addEventListener('pointermove', (evento) => {
                if (!redimensionando) return;

                /* El redimensionamiento solo existe mientras el botón izquierdo siga presionado. */
                if (!(evento.buttons & 1)) {
                    terminarRedimension(evento);
                    return;
                }

                const rectArea = areaModals.getBoundingClientRect();
                const deltaX = evento.clientX - inicioX;
                const deltaY = evento.clientY - inicioY;

                let left = inicioLeft;
                let top = inicioTop;
                let ancho = inicioAncho;
                let alto = inicioAlto;

                if (borde.includes('e')) {
                    ancho = inicioAncho + deltaX;
                }

                if (borde.includes('s')) {
                    alto = inicioAlto + deltaY;
                }

                if (borde.includes('w')) {
                    ancho = inicioAncho - deltaX;
                    left = inicioLeft + deltaX;
                }

                if (borde.includes('n')) {
                    alto = inicioAlto - deltaY;
                    top = inicioTop + deltaY;
                }

                const limiteAncho = Math.max(1, areaModals.clientWidth - left);
                const limiteAlto = Math.max(1, areaModals.clientHeight - top);
                const minimoW = Math.min(minimoAncho, limiteAncho);
                const minimoH = Math.min(minimoAlto, limiteAlto);

                if (ancho < minimoW) {
                    if (borde.includes('w')) left = inicioLeft + (inicioAncho - minimoW);
                    ancho = minimoW;
                }

                if (alto < minimoH) {
                    if (borde.includes('n')) top = inicioTop + (inicioAlto - minimoH);
                    alto = minimoH;
                }

                if (ancho > limiteAncho) {
                    ancho = limiteAncho;
                    if (borde.includes('w')) left = inicioLeft + (inicioAncho - ancho);
                }

                if (alto > limiteAlto) {
                    alto = limiteAlto;
                    if (borde.includes('n')) top = inicioTop + (inicioAlto - alto);
                }

                left = Math.max(0, left);
                top = Math.max(0, top);

                modal.style.left = `${left}px`;
                modal.style.top = `${top}px`;
                modal.style.width = `${ancho}px`;
                modal.style.height = `${alto}px`;
            });

            const terminarRedimension = (evento) => {
                if (!redimensionando) return;

                redimensionando = false;
                borde = '';
                modal.style.cursor = '';

                if (modal.hasPointerCapture(evento.pointerId)) {
                    modal.releasePointerCapture(evento.pointerId);
                }
            };

            modal.addEventListener('pointerup', terminarRedimension);
            modal.addEventListener('pointercancel', terminarRedimension);
            window.addEventListener('pointerup', terminarRedimension);
            window.addEventListener('pointercancel', terminarRedimension);
            modal.addEventListener('pointerleave', (evento) => {
                if (!redimensionando) modal.style.cursor = '';
            });

            window.addEventListener('blur', () => {
                redimensionando = false;
                borde = '';
                modal.style.cursor = '';
            });
        };

        return { preparar, prepararRedimension };
    };

    return { crear };
})();
