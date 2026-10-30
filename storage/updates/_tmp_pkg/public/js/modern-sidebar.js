/**
 * MODERN SIDEBAR CONTROLLER (Toggle-Only)
 * Maneja el colapso/expansión del sidebar SOLO con botón toggle
 * NO usa hover para expandir
 */

document.addEventListener('DOMContentLoaded', function () {
    const appLayout = document.getElementById('appLayout');
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // IMPORTANTE: Sidebar inicia SIEMPRE CERRADO por defecto
    // Solo se abre si el usuario lo dejó abierto en la sesión anterior
    const savedState = localStorage.getItem('sidebarExpanded');
    if (savedState === 'true') {
        appLayout.classList.add('sidebar-expanded');
    }
    // Si no hay estado guardado o es 'false', el sidebar permanece cerrado (80px)

    /**
     * Toggle Sidebar (Desktop: Expand/Collapse | Mobile: Show/Hide)
     */
    function toggleSidebar() {
        const isMobile = window.innerWidth < 992;

        if (isMobile) {
            // Mobile: Show/Hide con overlay
            appLayout.classList.toggle('sidebar-open');
        } else {
            // Desktop: Toggle expand/collapse
            appLayout.classList.toggle('sidebar-expanded');

            // Guardar estado en localStorage
            const isExpanded = appLayout.classList.contains('sidebar-expanded');
            localStorage.setItem('sidebarExpanded', isExpanded);

            // Trigger resize para ApexCharts
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 300);
        }
    }

    /**
     * Cerrar sidebar en mobile al hacer clic en overlay
     */
    function closeSidebarMobile() {
        appLayout.classList.remove('sidebar-open');
    }

    // Event Listeners
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebarMobile);
    }

    /**
     * Ajustar comportamiento al cambiar tamaño de ventana
     */
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            const isMobile = window.innerWidth < 992;

            // Si pasamos de mobile a desktop, limpiar clases mobile
            if (!isMobile) {
                appLayout.classList.remove('sidebar-open');
            }
        }, 250);
    });

    /**
     * Cerrar sidebar mobile al hacer clic en un enlace de navegación
     */
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                closeSidebarMobile();
            }
        });
    });
});
