/**
 * Sidebar Professional - JavaScript
 * Sistema de Gestión de Pasantes (SGP)
 * 
 * Características:
 * - Desktop: Collapsed/Expanded con persistencia LocalStorage
 * - Mobile: Overlay con backdrop blur
 * - Transiciones suaves y sin bugs
 * - Fix para ApexCharts resize
 */

document.addEventListener('DOMContentLoaded', function () {
    // Modificación: Seleccionar ambos botones (Desktop y Mobile)
    const toggleBtns = document.querySelectorAll('#sidebarToggle, #sidebarCollapseToggle');
    const sidebar = document.querySelector('.main-sidebar');
    const content = document.querySelector('.content-wrapper');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtns.length === 0 || !sidebar || !content) {
        console.warn('⚠️ Sidebar: Elementos no encontrados');
        return;
    }

    // ============================================
    // PERSISTENCIA DE ESTADO (LocalStorage)
    // ============================================
    const STORAGE_KEY = 'sgp_sidebar_collapsed';

    function getSavedState() {
        return localStorage.getItem(STORAGE_KEY) === 'true';
    }

    function saveState(isCollapsed) {
        localStorage.setItem(STORAGE_KEY, isCollapsed);
    }

    // ============================================
    // INICIALIZACIÓN: Restaurar estado guardado
    // ============================================
    function initializeSidebar() {
        if (window.innerWidth >= 992 && getSavedState()) {
            document.body.classList.add('sidebar-collapsed');
            sidebar.classList.add('collapsed');
        }
    }

    // Ejecutar inicialización
    initializeSidebar();

    // Flag para ignorar el resize sintético que dispara el toggle
    let _internalResize = false;

    // ============================================
    // TOGGLE PRINCIPAL (Unified Handler)
    // ============================================
    function handleSidebarToggle(e) {
        e.stopPropagation();

        if (window.innerWidth >= 992) {
            const isCollapsed = sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed', isCollapsed);
            saveState(isCollapsed);

            // Resize solo para charts — ignorado por el handler de responsive
            setTimeout(() => {
                _internalResize = true;
                window.dispatchEvent(new Event('resize'));
                setTimeout(() => { _internalResize = false; }, 50);
            }, 350);

        } else {
            const isOpen = sidebar.classList.toggle('show');
            document.body.classList.toggle('sidebar-open', isOpen);
            if (overlay) overlay.classList.toggle('active', isOpen);
        }
    }

    // Attach listener to all toggle buttons
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', handleSidebarToggle);
    });

    // ============================================
    // CERRAR CON OVERLAY (Móvil)
    // ============================================
    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            console.log('📱 Sidebar cerrado por overlay');
        });
    }

    // ============================================
    // RESPONSIVE: Limpiar estado móvil al cambiar a desktop
    // ============================================
    let resizeTimer;
    let _lastBreakpoint = window.innerWidth >= 992 ? 'desktop' : 'mobile';

    window.addEventListener('resize', function () {
        // Ignorar resize sintético del toggle (solo para charts)
        if (_internalResize) return;

        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const isDesktop = window.innerWidth >= 992;
            const newBreakpoint = isDesktop ? 'desktop' : 'mobile';

            // Solo actuar si realmente cambió de breakpoint
            if (newBreakpoint === _lastBreakpoint) return;
            _lastBreakpoint = newBreakpoint;

            if (isDesktop) {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
                if (overlay) overlay.classList.remove('active');

                if (getSavedState()) {
                    sidebar.classList.add('collapsed');
                    document.body.classList.add('sidebar-collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    document.body.classList.remove('sidebar-collapsed');
                }
            } else {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('sidebar-collapsed');
            }
        }, 250);
    });

    // ============================================
    // [FIX-PERF] "PREVENIR CIERRE AL CLICK INTERNO" — este listener llamaba
    // e.stopPropagation() para CUALQUIER click dentro del sidebar, pero no
    // existe (ni existió) ningún listener en `document` que cierre el sidebar
    // al hacer click afuera — solo el overlay (línea ~86) lo hace, y un click
    // en el overlay nunca pasa por aquí. El único efecto real de este bloque
    // era impedir que el click llegara al listener delegado de sgp-pjax.js en
    // `document`, forzando una recarga completa de página en cada navegación
    // del menú lateral en vez del swap parcial de PJAX. Eliminado.
    // ============================================

    // ============================================
    // CERRAR SIDEBAR EN MOBILE AL HACER CLICK EN LINK
    // ============================================
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // [FIX-PERF] Ya no se llama e.stopPropagation() aquí — bloqueaba
            // la interceptación de sgp-pjax.js (ver nota arriba). Cerrar el
            // sidebar en mobile no depende de detener la propagación.

            if (window.innerWidth < 992) {
                // Cerrar sidebar en mobile después de click
                setTimeout(() => {
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                    if (overlay) {
                        overlay.classList.remove('active');
                    }
                }, 150); // Pequeño delay para feedback visual
            }
        });
    });

    // ============================================
    // FIX: PREVENIR EXPANSIÓN AL CLICKEAR ITEMS
    // ============================================
    const sidebarItems = document.querySelectorAll('.sidebar-link');
    sidebarItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });
});
