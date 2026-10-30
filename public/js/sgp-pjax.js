/**
 * SGP-PJAX — Navegación sin recarga completa de página
 *
 * Intercepta todos los links del sidebar (.nav-link) y los carga por AJAX,
 * reemplazando solo el #sgp-content sin tocar el sidebar, topbar ni las librerías.
 *
 * Características:
 *  - Push History (URL real en la barra del navegador)
 *  - Spinner de transición (usa .loading-overlay existente)
 *  - Re-inicializa librerías JS (ApexCharts, DataTables, Flatpickr, etc.)
 *  - Fallback graceful: si falla AJAX, hace navegación normal
 *  - Soporte para el botón Atrás/Adelante del navegador (popstate)
 *
 * @version 1.0 — Optimización de Rendimiento SGP
 */

(function () {
    'use strict';

    // [SGP-FIX-LOGIN-LOADER-NULL] Guard: sgp-pjax.js también puede fallar en login si no hay content
    var initContent = document.getElementById('sgp-content');
    if (!initContent) {
        console.info('[SGP-PJAX] Elemento #sgp-content no encontrado — PJAX no inicializado (Vista pública/Login).');
        return; // Salir del script sin lanzar errores
    }

    // ─── Configuración ─────────────────────────────────────────────────────────
    const CONTENT_ID = 'sgp-content';
    const NAV_SELECTOR = '.nav-link, .dock-item, .dock-sheet-item, .pjax-link';
    const PJAX_HEADER = 'X-PJAX';
    const ACTIVE_CLASS = 'active';
    const TRANSITION_MS = 180; // ms de fade entre vistas

    // ─── Estado interno ────────────────────────────────────────────────────────
    let isNavigating = false;

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /** Muestra el spinner de carga (reutiliza el existente del layout). */
    function showSpinner() {
        // Obsoleto: Usamos SlimProgress para no bloquear UI (Sensación rápida SPA)
        startSlimProgress();
    }

    /** Oculta el spinner. */
    function hideSpinner() {
        // Obsoleto: Usamos SlimProgress 
        finishSlimProgress();
    }

    /* ─── Novedad Premium UX: Top Slim Progress Bar (Estilo Github / YouTube) ─── */
    function startSlimProgress() {
        let bar = document.getElementById('sgp-slim-progress');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'sgp-slim-progress';
            bar.style.position = 'fixed';
            bar.style.top = '0';
            bar.style.left = '0';
            bar.style.height = '3px';
            bar.style.background = 'linear-gradient(90deg, #3b82f6, #10b981)'; // Gradiente neón dinámico
            bar.style.zIndex = '9999999';
            bar.style.width = '0%';
            bar.style.boxShadow = '0 0 10px rgba(16, 185, 129, 0.5)';
            bar.style.transition = 'width 1.5s cubic-bezier(0.1, 0.8, 0.2, 1), opacity 0.3s ease';
            document.body.appendChild(bar);
        }
        bar.style.opacity = '1';
        bar.style.width = '10%';
        void bar.offsetWidth; // Force repaint
        bar.style.width = '75%';
    }

    function finishSlimProgress() {
        let bar = document.getElementById('sgp-slim-progress');
        if (bar) {
            bar.style.width = '100%';
            setTimeout(function() { 
                bar.style.opacity = '0'; 
                setTimeout(function() { bar.style.width = '0%'; }, 300); // Reset after hidden
            }, 250);
        }
    }

    /** Obtiene el contenedor principal de contenido. */
    function getContent() {
        return document.getElementById(CONTENT_ID);
    }

    /**
     * Actualiza el estado "active" del sidebar sin tocar el DOM del sidebar.
     * Selecciona con precisión matemática el enlace más específico que coincida con la URL,
     * evitando activar simultáneamente el enlace raíz de inicio (dashboard) y la sección actual.
     * @param {string} url - URL de destino
     */
    function updateSidebarActive(url) {
        try {
            const currentPath = new URL(url, window.location.origin).pathname.replace(/\/+$/, '').toLowerCase();
            const links = Array.from(document.querySelectorAll(NAV_SELECTOR));

            links.forEach(function (l) { l.classList.remove(ACTIVE_CLASS); });

            let bestMatch = null;
            let bestScore = -1;

            links.forEach(function (link) {
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.includes('logout')) return;

                const linkPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, '').toLowerCase();

                // Match exacto (máxima prioridad)
                if (currentPath === linkPath) {
                    const score = 1000 + linkPath.length;
                    if (score > bestScore) {
                        bestScore = score;
                        bestMatch = link;
                    }
                } 
                // Prefijo de sub-ruta (ej: /users/crear activa /users)
                else if (currentPath.startsWith(linkPath + '/')) {
                    const score = linkPath.length;
                    if (score > bestScore) {
                        bestScore = score;
                        bestMatch = link;
                    }
                }
            });

            if (bestMatch) {
                bestMatch.classList.add(ACTIVE_CLASS);
            }
        } catch (e) {
            console.warn('[SGP-PJAX] Error al actualizar estado activo del sidebar:', e);
        }
    }

    /**
     * Actualiza el <title> de la página con el que venga en la respuesta PJAX.
     * @param {string} html - HTML completo de la respuesta PJAX (solo el contenido)
     */
    function updateTitle(html) {
        // El servidor puede inyectar el title en un meta tag especial
        const match = html.match(/data-pjax-title="([^"]+)"/);
        if (match) {
            document.title = decodeURIComponent(match[1]) + ' — SGP';
        }
    }

    /**
     * Re-ejecuta los scripts inline que vienen dentro del HTML cargado por PJAX.
     * Los scripts normales dentro de innerHTML no se ejecutan automáticamente.
     * NOTA: Los scripts externos (src) que YA están cargados desde el layout inicial
     * (jQuery, Choices, SGPModal, DataTables, etc.) se omiten a propósito — re-crearlos
     * causaría recargas asíncronas que dejan esos objetos undefined momentáneamente.
     * [SGP-FIX-PJAX-EXTERNAL-SCRIPTS] PERO algunas vistas (evaluaciones/index.php,
     * perfil/ver.php, wizard/index.php, etc.) incluyen su PROPIO <script src="..."> de
     * página específica DENTRO del contenido, no en el layout — esos nunca se ejecutan
     * al llegar por PJAX (el navegador ignora <script src> insertado vía innerHTML),
     * dejando indefinidos objetos globales como EvalApp. Por eso ahora también se
     * recrean los scripts externos, pero SOLO si su archivo no está ya cargado en el
     * shell persistente (fuera de este container) — así no se duplica ninguna librería
     * compartida, solo se ejecutan los scripts propios de la vista actual.
     * @param {HTMLElement} container
     */
    function execInlineScripts(container) {
        const scripts = container.querySelectorAll('script:not([src])');
        scripts.forEach(function (oldScript) {
            try {
                const newScript = document.createElement('script');
                // Copiar atributos (type, src, etc.)
                Array.from(oldScript.attributes).forEach(function (attr) {
                    newScript.setAttribute(attr.name, attr.value);
                });
                newScript.textContent = oldScript.textContent;
                if (oldScript.parentNode) {
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                }
            } catch (err) {
                console.warn('[SGP-PJAX] Advertencia ejecutando script inline:', err);
            }
        });

        const externalScripts = container.querySelectorAll('script[src]');
        externalScripts.forEach(function (oldScript) {
            const src = oldScript.getAttribute('src');
            if (!src) return;
            const baseSrc = src.split('?')[0]; // ignorar cache-buster (?v=2, ?v=<timestamp>)
            const yaCargadoFuera = Array.from(document.scripts).some(function (s) {
                if (s === oldScript) return false;
                const otroSrc = s.getAttribute('src');
                return otroSrc && otroSrc.split('?')[0] === baseSrc && !container.contains(s);
            });
            if (yaCargadoFuera) return; // ya vive en el layout persistente, no recargar

            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    /**
     * After PJAX load: re-inicializa las librerías que se attachen al DOM.
     */
    function reinitLibraries() {

        // SGP-FIX-v2 [3.2] DESTROY instancias previas antes de reinicializar
        // Previene 'Cannot reinitialise DataTable' y charts duplicados.


        // Destruir ApexCharts
        if (typeof ApexCharts !== 'undefined') {
            document.querySelectorAll('[id]').forEach(function (el) {
                var chart = ApexCharts.getChartByID(el.id);
                if (chart) { chart.destroy(); }
            });
        }

        // Limpiar instancias Flatpickr
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.flatpickr-input').forEach(function (el) {
                if (el._flatpickr) { el._flatpickr.destroy(); }
            });
        }
        // --- FIN DESTROY --- continuar con el reinit existente

        // 1. Re-inicializa KPI counters
        const kpiEls = document.querySelectorAll('[data-kpi-value]');
        kpiEls.forEach(function (el) {
            const target = parseInt(el.dataset.kpiValue, 10) || 0;
            el.textContent = '0';
            // Animar de 0 al valor:
            const duration = 900;
            const start = performance.now();
            function step(now) {
                const p = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.floor(eased * target);
                if (p < 1) requestAnimationFrame(step);
                else el.textContent = target;
            }
            requestAnimationFrame(step);
        });

        // 2. Re-inicializa Flatpickr
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('[data-flatpickr]').forEach(function (el) {
                if (!el._flatpickr) flatpickr(el, { locale: 'es' });
            });
        }

        // 3. Re-inicializa Choices.js utilizando la API general
        if (typeof window.SGPChoices !== 'undefined') {
            window.SGPChoices.initAll();
        }


    }

    // ─── Núcleo: Navegar con PJAX ──────────────────────────────────────────────

    /**
     * Navega a una URL usando PJAX.
     * @param {string} url - URL destino
     * @param {boolean} pushState - Si añadir al historial del navegador
     */
    function navigate(url, pushState) {
        if (isNavigating) return;
        isNavigating = true;

        const content = getContent();
        if (!content) {
            window.location.href = url;
            return;
        }

        showSpinner();
        content.style.opacity = '0.5'; // No bajar a 0 para evitar salto blanco (percibido como lento)
        content.style.transition = 'opacity ' + TRANSITION_MS + 'ms ease';

        // SGP-FIX-v2 [3.1] Timeout de seguridad: desbloquea la UI si fetch falla sin
        // pasar por .finally() (error de red, XAMPP caído, timeout de servidor).
        // NOTA: Este timeout (10s) es DIFERENTE al de sesión por inactividad (10min).
        var navigationTimeout = setTimeout(function () {
            if (isNavigating) {
                isNavigating = false;
                hideSpinner();
                content.style.opacity = '1';
                console.warn('[SGP-PJAX] Navegación desbloqueada por timeout (10s). Posible error de red o servidor caído.');
            }
        }, 10000);

        fetch(url, {
            headers: {
                [PJAX_HEADER]: '1',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('[SGP-PJAX] HTTP ' + response.status + ': ' + response.statusText);
                return response.text();
            })
            .then(function (html) {
                // SGP-FIX DATATABLES TIMING — destruir ANTES de mutar el DOM
                // Si se destruye despues de innerHTML, los nodos ya son referencias muertas
                if (typeof $ !== 'undefined' &&
                    typeof $.fn !== 'undefined' &&
                    typeof $.fn.DataTable !== 'undefined') {
                    try {
                        var tablas = $.fn.dataTable.tables({ api: true });
                        if (tablas && tablas.context && tablas.context.length > 0) {
                            tablas.destroy(true);
                        }
                    } catch (e) {
                        // Silenciar — puede fallar si ya fueron destruidas
                    }
                }
                // Destruir Choices.js antes de borrar DOM
                if (typeof window.SGPChoices !== 'undefined') {
                    window.SGPChoices.destroyAll();
                }

                content.innerHTML = html;

                if (pushState) {
                    history.pushState({ pjax: true, url: url }, '', url);
                }
                updateTitle(html);
                updateSidebarActive(url);

                execInlineScripts(content);
                reinitLibraries();

                // SGP-FIX PJAX: Notificar al sistema que la vista cargó sin recarga
                document.dispatchEvent(new CustomEvent('pjax:complete', { detail: { url: url } }));

                content.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(function (err) {
                console.warn('[SGP-PJAX] Error al cargar ' + url + ':', err);
                window.location.href = url;
            })
            .finally(function () {
                clearTimeout(navigationTimeout); // SGP-FIX-v2 [3.1] cancelar safety timeout
                isNavigating = false;
                hideSpinner();
                content.style.opacity = '1';
            });
    }

    // ─── Binding de eventos ────────────────────────────────────────────────────

    /**
     * Interceptar clics en links del sidebar (.nav-link).
     * Excluimos: logout, links con data-no-pjax, links externos, links con target.
     */
    function bindNavLinks() {
        document.addEventListener('click', function (e) {
            // Buscar el link más cercano al elemento clickeado
            const link = e.target.closest(NAV_SELECTOR);
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href) return;

            // Excluir casos especiales
            if (
                link.dataset.noPjax !== undefined ||       // data-no-pjax explícito
                link.getAttribute('target') === '_blank' || // nueva pestaña
                href.startsWith('#') ||                    // anchor
                href.includes('/auth/logout') ||           // logout (debe recargar)
                href.includes('/kiosco') ||                // kiosco tiene su propio flujo
                e.ctrlKey || e.metaKey || e.shiftKey       // modificadores del teclado
            ) {
                return; // Dejar comportamiento normal
            }

            // Verificar que es una URL del mismo origen
            try {
                const destUrl = new URL(href, window.location.origin);
                if (destUrl.origin !== window.location.origin) return;
            } catch (_) {
                return;
            }

            e.preventDefault();
            navigate(href, true);
        });
    }

    /**
     * Manejar Botón Atrás / Adelante del navegador.
     */
    function bindPopState() {
        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.pjax) {
                navigate(e.state.url || window.location.href, false);
            } else {
                // Estado sin PJAX → recargar normal
                window.location.reload();
            }
        });
    }

    // ─── Inicialización ────────────────────────────────────────────────────────

    function init() {
        // Registrar el estado inicial en el historial
        history.replaceState(
            { pjax: true, url: window.location.href },
            '',
            window.location.href
        );

        bindNavLinks();
        bindPopState();

        console.info('[SGP-PJAX] ✅ Navegación fluida activada.');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // API pública: permite que otros scripts disparen navegación PJAX
    window.SGPPjax = { navigate: navigate };

})();
