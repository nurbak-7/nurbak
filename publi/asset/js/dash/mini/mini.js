/* ============================================================
   Ruta: publi/asset/js/dash/mini/mini.js
   Función: Coordinar la mini-ventana reutilizable de DASH.

   MINI es independiente de VENT y de los módulos que la utilizan.
   Este archivo coordina el ciclo de vida de MINI y expone su API.

   🔹 Nivel 2: Localizar la estructura de MINI.
   🔹 Nivel 3: Preparar el estad y la API.
   🔹 Nivel 4: Crear, cargar y ordenar una mini-ventana.
   🔹 Nivel 5: Mantener MINI dentro del área disponible.
   🔹 Nivel 6: Cerrar una mini-ventana.
   🔹 Nivel Final: Mantener MINI disponible para los módulos.
============================================================ */

const iniciarMini = async () => {

    window.NURBAK_MINI = window.NURBAK_MINI || {};

    if (window.NURBAK_MINI.iniciado || window.NURBAK_MINI.iniciando) return;
    window.NURBAK_MINI.iniciando = true;

    /* ========================================================
       ======================================================== */

    /* ========================================================
       NIVEL 2 — ESTRUCTURA DE MINI
       ======================================================== */
    const areaMini = document.querySelector('#dash-mini-area');
    const plantilla = document.querySelector('#dash-mini-plantilla');

    if (!areaMini || !plantilla) {
        window.NURBAK_MINI.iniciando = false;
        throw new Error('MINI: No se encontró la estructura de la mini-ventana.');
    }

    /* ========================================================
       NIVEL 3 — ESTADO Y API
       ======================================================== */
    const minisAbiertas = new Set();
    let ordenMini = 2000;

    const traerAlFrente = mini => {
        if (!mini) return;

        ordenMini += 1;
        mini.style.zIndex = String(ordenMini);
        mini.classList.add('is-activa');

        minisAbiertas.forEach(otra => {
            if (otra !== mini) otra.classList.remove('is-activa');
        });
    };

    const ajustarDentroDelArea = mini => {
        if (!mini) return;

        const margen = window.matchMedia?.('(max-width: 600px)').matches ? 30 : 48;
        const anchoMax = Math.max(1, areaMini.clientWidth - margen);
        const altoMax = Math.max(1, areaMini.clientHeight - margen);

        if (mini.offsetWidth > anchoMax) {
            mini.style.width = `${anchoMax}px`;
        }

        if (mini.offsetHeight > altoMax) {
            mini.style.height = `${altoMax}px`;
        }

        const maxX = Math.max(0, areaMini.clientWidth - mini.offsetWidth);
        const maxY = Math.max(0, areaMini.clientHeight - mini.offsetHeight);
        const izquierda = parseFloat(mini.style.left || '0');
        const arriba = parseFloat(mini.style.top || '0');

        mini.style.left = `${Math.min(Math.max(izquierda, 0), maxX)}px`;
        mini.style.top = `${Math.min(Math.max(arriba, 0), maxY)}px`;
    };

    const prepararRedimension = mini => {
        const zona = 8;
        const minimoAnchoBase = 560;
        const minimoAlto = 160;

        let redimensionando = false;
        let borde = '';
        let inicioX = 0;
        let inicioY = 0;
        let inicioLeft = 0;
        let inicioTop = 0;
        let inicioAncho = 0;
        let inicioAlto = 0;

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

        const margenArea = () => {
            return window.matchMedia?.('(max-width: 600px)').matches ? 30 : 48;
        };

        const obtenerMinimo = () => {
            const disponible = Math.max(0, areaMini.clientWidth - margenArea());
            return Math.min(minimoAnchoBase, disponible || minimoAnchoBase);
        };

        const obtenerMinimoAlto = () => {
            const disponible = Math.max(0, areaMini.clientHeight - margenArea());
            return Math.min(minimoAlto, disponible || minimoAlto);
        };

        const obtenerBorde = evento => {
            const rect = mini.getBoundingClientRect();
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

        const limpiarCursor = () => {
            if (!redimensionando) mini.style.cursor = '';
        };

        const terminarRedimension = evento => {
            if (!redimensionando) return;

            redimensionando = false;
            borde = '';
            mini.style.cursor = '';
            document.body.style.userSelect = '';

            if (evento && mini.hasPointerCapture?.(evento.pointerId)) {
                try {
                    mini.releasePointerCapture(evento.pointerId);
                } catch (_) {}
            }
        };

        const iniciarRedimension = evento => {
            if (evento.button !== 0 || !(evento.buttons & 1)) return;

            const bordeActual = obtenerBorde(evento);
            if (!bordeActual) return;

            const rectMini = mini.getBoundingClientRect();
            const rectArea = areaMini.getBoundingClientRect();

            redimensionando = true;
            borde = bordeActual;
            inicioX = evento.clientX;
            inicioY = evento.clientY;
            inicioLeft = rectMini.left - rectArea.left;
            inicioTop = rectMini.top - rectArea.top;
            inicioAncho = rectMini.width;
            inicioAlto = rectMini.height;

            mini.style.left = `${inicioLeft}px`;
            mini.style.top = `${inicioTop}px`;
            mini.style.right = 'auto';
            mini.style.width = `${inicioAncho}px`;
            mini.style.height = `${inicioAlto}px`;
            mini.style.cursor = cursores[bordeActual];

            mini.dataset.redim = '1';
            traerAlFrente(mini);
            document.body.style.userSelect = 'none';

            mini.setPointerCapture(evento.pointerId);
            evento.preventDefault();
            evento.stopPropagation();
        };

        const moverRedimension = evento => {
            if (!redimensionando) {
                mini.style.cursor = cursores[obtenerBorde(evento)] || '';
                return;
            }

            if (!(evento.buttons & 1)) {
                terminarRedimension(evento);
                return;
            }

            const deltaX = evento.clientX - inicioX;
            const deltaY = evento.clientY - inicioY;
            const anchoArea = areaMini.clientWidth;
            const altoArea = areaMini.clientHeight;
            const minimoW = obtenerMinimo();
            const minimoH = obtenerMinimoAlto();

            const derechaFija = inicioLeft + inicioAncho;
            const abajoFijo = inicioTop + inicioAlto;

            let left = inicioLeft;
            let top = inicioTop;
            let ancho = inicioAncho;
            let alto = inicioAlto;

            if (borde.includes('e')) {
                const maxAncho = Math.max(minimoW, anchoArea - inicioLeft);
                ancho = Math.min(
                    maxAncho,
                    Math.max(minimoW, inicioAncho + deltaX)
                );
            }

            if (borde.includes('w')) {
                const maxLeft = Math.max(0, derechaFija - minimoW);
                left = Math.min(
                    maxLeft,
                    Math.max(0, inicioLeft + deltaX)
                );
                ancho = derechaFija - left;

                if (ancho < minimoW) {
                    left = derechaFija - minimoW;
                    ancho = minimoW;
                }
            }

            if (borde.includes('s')) {
                const maxAlto = Math.max(minimoH, altoArea - inicioTop);
                alto = Math.min(
                    maxAlto,
                    Math.max(minimoH, inicioAlto + deltaY)
                );
            }

            if (borde.includes('n')) {
                const maxTop = Math.max(0, abajoFijo - minimoH);
                top = Math.min(
                    maxTop,
                    Math.max(0, inicioTop + deltaY)
                );
                alto = abajoFijo - top;

                if (alto < minimoH) {
                    top = abajoFijo - minimoH;
                    alto = minimoH;
                }
            }

            if (left + ancho > anchoArea) {
                if (borde.includes('w')) {
                    left = Math.max(0, anchoArea - ancho);
                } else {
                    ancho = Math.max(minimoW, anchoArea - left);
                }
            }

            if (top + alto > altoArea) {
                if (borde.includes('n')) {
                    top = Math.max(0, altoArea - alto);
                } else {
                    alto = Math.max(minimoH, altoArea - top);
                }
            }

            mini.style.left = `${Math.max(0, left)}px`;
            mini.style.top = `${Math.max(0, top)}px`;
            mini.style.width = `${Math.max(minimoW, ancho)}px`;
            mini.style.height = `${Math.max(minimoH, alto)}px`;
        };

        const onPointerLeave = () => limpiarCursor();
        const onBlur = () => {
            redimensionando = false;
            borde = '';
            mini.style.cursor = '';
            document.body.style.userSelect = '';
        };

        mini.addEventListener('pointerdown', iniciarRedimension, true);
        mini.addEventListener('pointermove', moverRedimension);
        mini.addEventListener('pointerup', terminarRedimension);
        mini.addEventListener('pointercancel', terminarRedimension);
        mini.addEventListener('pointerleave', onPointerLeave);
        window.addEventListener('pointerup', terminarRedimension);
        window.addEventListener('pointercancel', terminarRedimension);
        window.addEventListener('blur', onBlur);

        return () => {
            window.removeEventListener('pointerup', terminarRedimension);
            window.removeEventListener('pointercancel', terminarRedimension);
            window.removeEventListener('blur', onBlur);
            mini.removeEventListener('pointerdown', iniciarRedimension, true);
            mini.removeEventListener('pointermove', moverRedimension);
            mini.removeEventListener('pointerup', terminarRedimension);
            mini.removeEventListener('pointercancel', terminarRedimension);
            mini.removeEventListener('pointerleave', onPointerLeave);
            document.body.style.userSelect = '';
        };
    };

    const cargarVista = async (mini, rutaVista) => {
        const contenido = mini.querySelector('[data-mini-contenido]');

        if (!contenido || !rutaVista) return;

        

        contenido.innerHTML = '<p>Cargando contenido...</p>';

        const respuesta = await fetch(rutaVista, { method: 'GET' });

        if (!respuesta.ok) {
            throw new Error(`No se pudo cargar ${rutaVista}`);
        }

        contenido.innerHTML = await respuesta.text();

        
    };

    const cerrarInterno = mini => {
        if (!mini) return;

        const modulo = mini.dataset.miniModulo || '';

        mini.__miniRedim?.();
        if (mini.__miniVentana) {
            window.removeEventListener('resize', mini.__miniVentana);
        }
        // SINCRONIZACIÓN | FASE 1
        // Avisar antes y después de retirar la mini-ventana.
        const deta = { modulo, mini };
        window.NURBAK_SINC?.emit('mini:cerrando', deta);

        minisAbiertas.delete(mini);
        mini.remove();

        window.NURBAK_SINC?.emit('mini:cerrada', deta);
    };

    const abrir = async ({
        titulo = 'Mini-ventana',
        contenido = '',
        rutaVista = null,
        modulo = '',
        clase = '',
    } = {}) => {

        

        const copia = plantilla.content.cloneNode(true);
        const mini = copia.querySelector('[data-mini-ventana]');

        if (!mini) {
            
            throw new Error('La plantilla de MINI está incompleta.');
        }

        mini.dataset.miniModulo = modulo;

        const tituloElemento = mini.querySelector('[data-mini-titulo]');
        const contenidoElemento = mini.querySelector('[data-mini-contenido]');

        tituloElemento.textContent = titulo;
        contenidoElemento.innerHTML = contenido;

        /* --------------------------------------------------------
           NIVEL 2 — APLICAR CLASES DE LA MINI-VENTANA

           Permite recibir una o varias clases separadas por espacios.
           Cada clase se agrega individualmente para cumplir con la
           API DOMTokenList de classList.
        -------------------------------------------------------- */
        if (clase) {
            clase
                .split(/\s+/)
                .filter(Boolean)
                .forEach(nombreClase => {
                    mini.classList.add(nombreClase);
                });
        }

        areaMini.appendChild(mini);
        minisAbiertas.add(mini);

        const desplazamiento = Math.min(minisAbiertas.size - 1, 5) * 24;
        mini.style.right = 'auto';
        mini.style.left = `${Math.max(24, areaMini.clientWidth - mini.offsetWidth - 24 - desplazamiento)}px`;
        mini.style.top = `${24 + desplazamiento}px`;
        traerAlFrente(mini);

        mini.querySelector('[data-mini-accion="cerrar"]')
            ?.addEventListener('click', () => cerrarInterno(mini));

        mini.addEventListener('mousedown', () => traerAlFrente(mini));

        /* ====================================================
           NIVEL 4 — ARRASTRE
           ==================================================== */
        const cabecera = mini.querySelector('[data-mini-arrastre]');
        let moviendo = false;
        let inicioX = 0;
        let inicioY = 0;
        let inicioLeft = 0;
        let inicioTop = 0;

        cabecera?.addEventListener('pointerdown', evento => {
            if (evento.target.closest('button')) return;

            moviendo = true;
            cabecera.setPointerCapture(evento.pointerId);
            inicioX = evento.clientX;
            inicioY = evento.clientY;
            inicioLeft = mini.offsetLeft;
            inicioTop = mini.offsetTop;
            traerAlFrente(mini);
        });

        cabecera?.addEventListener('pointermove', evento => {
            if (!moviendo) return;

            mini.style.left = `${inicioLeft + (evento.clientX - inicioX)}px`;
            mini.style.top = `${inicioTop + (evento.clientY - inicioY)}px`;
            ajustarDentroDelArea(mini);
        });

        cabecera?.addEventListener('pointerup', evento => {
            moviendo = false;
            try { cabecera.releasePointerCapture(evento.pointerId); } catch (_) {}
        });

        cabecera?.addEventListener('pointercancel', () => {
            moviendo = false;
        });

        mini.__miniRedim = prepararRedimension(mini);
        ajustarDentroDelArea(mini);

        const ajustarVentana = () => {
            if (mini.isConnected) {
                ajustarDentroDelArea(mini);
            }
        };

        window.addEventListener('resize', ajustarVentana);
        mini.__miniVentana = ajustarVentana;

        if (rutaVista) {
            try {
                await cargarVista(mini, rutaVista);
            } catch (error) {
                contenidoElemento.innerHTML = '<p>No se pudo cargar el contenido.</p>';
                
                console.error('MINI:', error);
            }
        }

        /* El módulo ya define su tamaño mediante CSS. */
        requestAnimationFrame(() => ajustarDentroDelArea(mini));

        return mini;
    };

    const cerrar = mini => cerrarInterno(mini);

    const cerrarPorModulo = modulo => {
        if (!modulo) return;

        [...minisAbiertas].forEach(mini => {
            if (mini.dataset.miniModulo === modulo) {
                cerrarInterno(mini);
            }
        });
    };

    /* SESIÓN — cierre global antes de abandonar el panel. */
    const cerrarPorSesion = () => {
        [...minisAbiertas].forEach(cerrarInterno);
    };
    window.NURBAK_SINC?.on('sesion:cerrando', cerrarPorSesion);

    window.NURBAK_MINI.abrir = abrir;
    window.NURBAK_MINI.cerrar = cerrar;
    window.NURBAK_MINI.cerrarPorModulo = cerrarPorModulo;
    window.NURBAK_MINI.traerAlFrente = traerAlFrente;
    window.NURBAK_MINI.listo = true;
    window.NURBAK_MINI.iniciado = true;
    window.NURBAK_MINI.iniciando = false;

    
    // SINCRONIZACIÓN | FASE 1
    window.NURBAK_SINC?.emit('mini:listo', { listo: true });

    /* ========================================================
       NIVEL FINAL — SOLICITUDES DE LOS MÓDULOS
       ======================================================== */
    if (!window.NURBAK_MINI.eventoRegistrado) {
        document.addEventListener('mini:abrir', evento => {
            abrir(evento.detail || {});
        });

        window.NURBAK_MINI.eventoRegistrado = true;
    }
};

window.NURBAK_MINI_READY = new Promise((resolver, rechazar) => {
    const iniciar = async () => {
        try {
            await iniciarMini();
            resolver(true);
        } catch (error) {
            rechazar(error);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
});
