/**
 * SGP Mobile Dock — Lógica de navegación iOS-style
 * Inspirado en control_asis/dashboard-nexio mobile dock
 *
 * Funcionalidades:
 *  - Apertura/cierre del sheet "Más"
 *  - Indicador activo dinámico
 *  - Animación spring en los tabs
 *  - Safe area automática
 */
(function () {
    'use strict';

    /* ── Referencias DOM ──────────────────────────────────── */
    const dock         = document.getElementById('sgpMobileDock');
    const sheet        = document.getElementById('sgpDockSheet');
    const overlay      = document.getElementById('sgpDockOverlay');
    const moreBtn      = document.getElementById('dockMoreBtn');

    if (!dock) return; // Guard: solo si el dock existe en el DOM

    /* ── Marcar ítem activo (por URL) y mover Slide ── */
    function markActive() {
        const currentPath = window.location.pathname;
        let activeElement = null;

        // Dock items
        dock.querySelectorAll('.dock-item[data-href]').forEach(function (item) {
            const href = item.getAttribute('data-href') || '';
            if (href && currentPath.includes(href)) {
                item.classList.add('active');
                item.setAttribute('aria-current', 'page');
                if (!activeElement || href.length > activeElement.getAttribute('data-href').length) {
                    activeElement = item;
                }
            } else {
                item.classList.remove('active');
                item.removeAttribute('aria-current');
            }
        });

        // Mover Sliding indicator (Dot / Punto premium)
        const indicator = dock.querySelector('.dock-indicator');
        if (indicator) {
            if (activeElement) {
                const dockRect = dock.getBoundingClientRect();
                const itemRect = activeElement.getBoundingClientRect();
                const relativeLeft = (itemRect.left - dockRect.left) + (itemRect.width / 2) - 3;
                indicator.style.transform = `translateX(${relativeLeft}px)`;
                requestAnimationFrame(() => indicator.classList.add('visible'));
            } else {
                indicator.classList.remove('visible'); 
            }
        }

        // Sheet items
        if (sheet) {
            sheet.querySelectorAll('.dock-sheet-item[data-href]').forEach(function (item) {
                const href = item.getAttribute('data-href') || '';
                if (href && currentPath.includes(href)) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }
    }

    /* ── Abrir Sheet "Más" ────────────────────────────────── */
    function openSheet() {
        if (!sheet || !overlay) return;
        sheet.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /* ── Cerrar Sheet ─────────────────────────────────────── */
    function closeSheet() {
        if (!sheet || !overlay) return;
        sheet.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* ── Eventos ─────────────────────────────────────────── */
    if (moreBtn) {
        moreBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (sheet && sheet.classList.contains('active')) {
                closeSheet();
            } else {
                openSheet();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSheet);
    }

    // Swipe down para cerrar el sheet
    if (sheet) {
        var startY = 0;
        sheet.addEventListener('touchstart', function (e) {
            startY = e.touches[0].clientY;
        }, { passive: true });

        sheet.addEventListener('touchend', function (e) {
            var endY = e.changedTouches[0].clientY;
            if (endY - startY > 60) { // Swipe down >= 60px → cerrar
                closeSheet();
            }
        }, { passive: true });
    }

    // Cerrar con tecla Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sheet && sheet.classList.contains('active')) {
            closeSheet();
        }
    });

    /* ── Animación spring en tap ──────────────────────────── */
    dock.querySelectorAll('.dock-item:not(.dock-more-btn)').forEach(function (item) {
        item.addEventListener('touchstart', function () {
            this.style.transform = 'scale(0.93)';
        }, { passive: true });
        item.addEventListener('touchend', function () {
            this.style.transform = '';
        }, { passive: true });
    });

    /* ── Init ─────────────────────────────────────────────── */
    markActive();

    // Re-marcar al navegar (PJAX compatibility)
    document.addEventListener('pjax:complete', function() {
        closeSheet();
        markActive();
    });
    document.addEventListener('sgp:navigate',  markActive);

}());
