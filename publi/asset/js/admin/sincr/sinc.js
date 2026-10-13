/* ============================================================
   Ruta: publi/asset/js/admin/sincr/sinc.js
   Función: Adaptar ADMIN al contrato de sincronización común.

   SINCRONIZACIÓN | FASE 2
============================================================ */

(() => {
    const TIPO = 'dato:camb';

    const emitir = ({modu, recu, acci, id = 0, dato = {}} = {}) => {
        const det = {
            area: 'admi',
            modu: String(modu || ''),
            recu: String(recu || ''),
            acci: String(acci || ''),
            id: Number(id || 0),
            dato: dato && typeof dato === 'object' ? dato : {},
            tiem: Date.now()
        };

        window.NURBAK_ESTA?.poner('camb:admi', det);
        return window.NURBAK_SINC?.emit(TIPO, det) === true;
    };

    const onCambio = (func, filt = {}, modal = null) => {
        if (typeof func !== 'function' || !window.NURBAK_SINC?.on) {
            return () => {};
        }

        const parar = window.NURBAK_SINC.on(TIPO, (det, evento) => {
            if (det.area !== 'admi') return;
            if (filt.modu && det.modu !== filt.modu) return;
            if (filt.recu && det.recu !== filt.recu) return;
            if (filt.acci && det.acci !== filt.acci) return;
            func(det, evento);
        });

        if (!modal) return parar;

        const ventana = modal.matches?.('[data-modal], [data-mini-ventana]')
            ? modal
            : modal.closest?.('[data-modal], [data-mini-ventana]') || modal;

        const cierres = [];
        const cerrar = det => {
            const objetivo = det?.modal || det?.mini;
            if (objetivo !== ventana) return;
            parar();
            cierres.forEach(quitar => quitar());
            cierres.length = 0;
        };

        cierres.push(window.NURBAK_SINC.on('modal:cerrando', cerrar));
        cierres.push(window.NURBAK_SINC.on('mini:cerrando', cerrar));

        return () => {
            parar();
            cierres.forEach(quitar => quitar());
            cierres.length = 0;
        };
    };

    window.NURBAK_ADMI_SINC = {
        listo: true,
        emitir,
        onCambio
    };
})();
