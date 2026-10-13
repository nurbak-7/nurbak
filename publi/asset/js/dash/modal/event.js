/* ============================================================
   Ruta: publi/asset/js/dash/modal/event.js
   Función: Conectar los eventos del navbar y coordinar la apertura
            de modals.

   Este archivo recibe las funciones ya separadas de Vent.
   No contiene SQL ni lógica de productos.

   AUTORIZACIÓN:
   - El submenú declara data-permiso.
   - LOGI / AUT.JS consulta la autorización al servidor.
   - EVEN.JS bloquea la apertura si AUT rechaza.
============================================================ */

window.NURBAK_MODAL_EVENT = (() => {

    const crear = (deps) => {
        const {
            areaModals,
            plantilla,
            rutas,
            modalsAbiertas,
            prepararArrastre,
            prepararRedimension,
            prepararControles,
            colocarModal,
            traerAlFrente,
            restaurarModal,
            cargarContenido,
            actualizarAltoNavbar,
            ajustarDentroDelArea,
            ordenarMinimizadas,
        } = deps;

        /* ====================================================
           NIVEL 1 — COMPROBAR AUTORIZACIÓN

           AUT.JS realiza la consulta al servidor.
           EVEN.JS solamente aplica la decisión antes de abrir
           o restaurar una modal.
        ==================================================== */

        const comprobarAutorizacion = async (enlace) => {

            const permCodi = (enlace.dataset.permiso || '').trim().toUpperCase();

            if (!permCodi) {
                
                return false;
            }

            if (
                !window.NURBAK_LOGI_AUT ||
                typeof window.NURBAK_LOGI_AUT.autorizar !== 'function'
            ) {
                
                return false;
            }

            const resultado = await window.NURBAK_LOGI_AUT.autorizar(permCodi);

            

            return resultado.ok === true && resultado.autorizado === true;
        };

        // SINCRONIZACIÓN | FASE 1
        // Evita dobles aperturas mientras una autorización o una carga está pendiente.
        const abriendo = new Set();

        const abrirModal = async (enlace) => {
            const area = enlace.closest('[data-modulo]')?.dataset.modulo || '';
            const submodulo = enlace.dataset.submodulo || '';
            const nombr = enlace.textContent.trim();
            const ruta = rutas[area]?.[submodulo];
            const rutaVista = ruta?.vista || '';
            const clave = `${area}/${submodulo}`;
            const modalExistente = modalsAbiertas.get(clave);

            if (!area || !submodulo || !rutaVista || !ruta?.recu) {
                return;
            }

            // Una ventana ya existente se reutiliza inmediatamente.
            // No se vuelve a consultar autorización para una simple navegación.
            if (modalExistente) {
                if (modalExistente.classList.contains('is-minimizada')) {
                    restaurarModal(modalExistente);
                } else {
                    traerAlFrente(modalExistente);
                }

                window.NURBAK_SINC?.emit('modal:activa', { modulo: submodulo, area, modal: modalExistente });
                return modalExistente;
            }

            if (abriendo.has(clave)) {
                return;
            }

            abriendo.add(clave);

            try {
                const autorizado = await comprobarAutorizacion(enlace);

                if (!autorizado) {
                    return;
                }

                const fragmento = plantilla.content.cloneNode(true);
                const modal = fragmento.querySelector('[data-modal]');
                const titulo = fragmento.querySelector('[data-modal-titulo]');

                if (!modal || !titulo) {
                    return;
                }

                modal.dataset.area = area;
                modal.dataset.modulo = submodulo;
                modal.dataset.clave = clave;
                modal.dataset.modalEstado = 'creado';
                titulo.textContent = nombr;
                areaModals.appendChild(fragmento);
                modalsAbiertas.set(clave, modal);

                prepararArrastre(modal);
                prepararRedimension(modal);
                prepararControles(modal);
                colocarModal(modal);
                traerAlFrente(modal);
                window.NURBAK_SINC?.emit('modal:abierta', { modulo: submodulo, area, modal });
                void cargarContenido(modal, rutaVista, ruta.recu, submodulo, area);

                modal.addEventListener('pointerdown', () => {
                    traerAlFrente(modal);
                });

                return modal;
            } finally {
                abriendo.delete(clave);
            }
        };

        const iniciar = () => {
            document.querySelectorAll('[data-submodulo]').forEach((enlace) => {
                enlace.addEventListener('click', (evento) => {
                    evento.preventDefault();
                    abrirModal(enlace);
                });
            });

            window.addEventListener('resize', () => {
                actualizarAltoNavbar();

                modalsAbiertas.forEach((modal) => {
                    ajustarDentroDelArea(modal);
                });

                ordenarMinimizadas();
            });
        };

        return { iniciar, abrirModal };
    };

    return { crear };
})();
