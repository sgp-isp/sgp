/**
 * SGP Mobile Dock — Lógica de navegación iOS-style
 * Inspirado en control_asis/dashboard-nexio mobile dock
 *
 * Funcionalidades:
 *  - Apertura/cierre del sheet "Más"
 *  - Indicador activo dinámico
 *  - Animación spring en los tabs
 *  - Safe area automática y compatibilidad total con PJAX
 */
(function () {
    'use strict';

    /* ── Abrir Sheet "Más" ────────────────────────────────── */
    function openSheet() {
        const currentSheet = document.getElementById('sgpDockSheet');
        const currentOverlay = document.getElementById('sgpDockOverlay');
        if (!currentSheet || !currentOverlay) return;
        currentSheet.classList.add('active');
        currentOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /* ── Cerrar Sheet ─────────────────────────────────────── */
    function closeSheet() {
        const currentSheet = document.getElementById('sgpDockSheet');
        const currentOverlay = document.getElementById('sgpDockOverlay');
        if (currentSheet) currentSheet.classList.remove('active');
        if (currentOverlay) currentOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* ── Exponer funciones globalmente de inmediato ───────── */
    window.openSgpDockSheet = openSheet;
    window.closeSgpDockSheet = closeSheet;
    window.toggleSgpDockSheet = function () {
        const currentSheet = document.getElementById('sgpDockSheet');
        if (currentSheet && currentSheet.classList.contains('active')) {
            closeSheet();
        } else {
            openSheet();
        }
    };

    /* ── Marcar ítem activo (por URL) y mover Slide ── */
    function markActive() {
        const dock = document.getElementById('sgpMobileDock');
        const sheet = document.getElementById('sgpDockSheet');
        if (!dock) return;

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

    /* ── Eventos Delegados (Funcionan con o sin recarga PJAX) ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('#dockMoreBtn, .dock-more-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            window.toggleSgpDockSheet();
            return;
        }
        if (e.target.closest('#sgpDockOverlay')) {
            closeSheet();
        }
    });

    // Soporte táctil inmediato
    document.addEventListener('touchend', function (e) {
        const btn = e.target.closest('#dockMoreBtn, .dock-more-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            window.toggleSgpDockSheet();
        }
    }, { passive: false });

    // Swipe down para cerrar el sheet — SOLO cuando se arrastra desde la manija o cabecera
    document.addEventListener('DOMContentLoaded', function() {
        const sheet = document.getElementById('sgpDockSheet');
        if (!sheet) return;

        let startY = 0;
        let startX = 0;
        let isHandleDrag = false;

        sheet.addEventListener('touchstart', function (e) {
            if (!e.touches || e.touches.length === 0) return;
            const touch = e.touches[0];
            startY = touch.clientY;
            startX = touch.clientX;

            // Solo permitir gesto de arrastrar para cerrar si el toque comenzó en:
            // 1. La manija (.dock-sheet-handle)
            // 2. La cabecera (.dock-sheet-header)
            // 3. O en la franja superior inmediata (< 60px) con el scroll en la cima (scrollTop <= 0)
            const target = e.target;
            const isHeaderArea = Boolean(target.closest('.dock-sheet-handle, .dock-sheet-header'));
            const isAtTop = sheet.scrollTop <= 0;
            const sheetRect = sheet.getBoundingClientRect();
            const isNearTopEdge = (startY - sheetRect.top) < 60;

            isHandleDrag = Boolean(isHeaderArea || (isAtTop && isNearTopEdge));
        }, { passive: true });

        sheet.addEventListener('touchend', function (e) {
            if (!isHandleDrag) return;
            isHandleDrag = false;

            if (!e.changedTouches || e.changedTouches.length === 0) return;
            const endY = e.changedTouches[0].clientY;
            const endX = e.changedTouches[0].clientX;
            const diffY = endY - startY;
            const diffX = Math.abs(endX - startX);

            // Deslizamiento hacia abajo intencional: al menos 85px y predominantemente vertical
            if (diffY > 85 && diffY > diffX && sheet.scrollTop <= 0) {
                closeSheet();
            }
        }, { passive: true });
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSheet();
        }
    });

    /* ── Init ─────────────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', markActive);
    } else {
        markActive();
    }

    // Re-marcar al navegar (PJAX compatibility)
    document.addEventListener('pjax:complete', function() {
        closeSheet();
        markActive();
    });
    document.addEventListener('sgp:navigate', markActive);

}());
