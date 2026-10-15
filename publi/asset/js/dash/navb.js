/* ============================================================
   Ruta: publi/asset/js/dash/navb.js
   Función: interacción responsive del navbar.
   No coordina módulos, modales ni sesiones.
============================================================ */
(() => {
    const head = document.querySelector('.dash-head');
    if (!head) return;

    const nav = head.querySelector('.dash-nav');
    const toggle = head.querySelector('.dash-toggle');
    const overlay = head.querySelector('.dash-overlay');
    const items = [...head.querySelectorAll('.dash-item')];
    const mobile = window.matchMedia('(max-width: 767px)');
    const tablet = window.matchMedia('(min-width: 768px) and (max-width: 1199px)');

    if (!nav || !toggle || !overlay) return;

    const closeItems = (except = null) => {
        items.forEach(item => {
            if (item === except) return;
            item.classList.remove('is-open');
            item.querySelector(':scope > .dash-link')?.setAttribute('aria-expanded', 'false');
        });
    };

    const setDrawer = open => {
        const active = mobile.matches && open;
        nav.classList.toggle('is-open', active);
        document.body.classList.toggle('dash-nav-open', active);
        toggle.setAttribute('aria-expanded', String(active));
        toggle.setAttribute('aria-label', active ? 'Cerrar menú' : 'Abrir menú');
        overlay.hidden = !active;
        if (!active) closeItems();
    };

    toggle.addEventListener('click', () => {
        if (!mobile.matches) return;
        setDrawer(!nav.classList.contains('is-open'));
    });

    overlay.addEventListener('click', () => setDrawer(false));

    items.forEach(item => {
        const button = item.querySelector(':scope > .dash-link');
        if (!button) return;

        button.addEventListener('click', () => {
            if (!mobile.matches && !tablet.matches) return;
            const open = !item.classList.contains('is-open');
            closeItems(item);
            item.classList.toggle('is-open', open);
            button.setAttribute('aria-expanded', String(open));
        });

        item.querySelectorAll('.dash-sub-link').forEach(link => {
            link.addEventListener('click', () => {
                if (mobile.matches) setDrawer(false);
            });
        });
    });

    document.addEventListener('click', event => {
        if (tablet.matches && !head.contains(event.target)) closeItems();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            setDrawer(false);
            closeItems();
        }
    });

    window.addEventListener('resize', () => {
        if (!mobile.matches) setDrawer(false);
        if (!tablet.matches) closeItems();
    });
})();
