/**
 * Script de Auto-Inicialización Global para Choices.js (SGP Proyecto)
 * Encuentra y moderniza todos los selects del sistema automáticamente.
 */

// ============================================================================
// FIX GLOBAL: Monkey-Patch defensivo para prevenir crashes de eventos 
// huérfanos (Zombie Listeners) disparados durante la navegación por PJAX.
// ============================================================================
if (typeof Choices !== 'undefined' && typeof Choices.prototype._onKeyDown === 'function') {
    const originalOnKeyDown = Choices.prototype._onKeyDown;
    Choices.prototype._onKeyDown = function (e) {
        // Valida que los objetos internos de esta instancia sigan existiendo 
        // y no haya sido vaciada por el destructor o el Garbage Collector
        if (!this.input || !this._store || !this.passedElement) {
            return;
        }
        try {
            return originalOnKeyDown.call(this, e);
        } catch (err) {
            // Ignorar silenciosamente eventos huérfanos en DOM destruido por PJAX
            return;
        }
    };
}

document.addEventListener('DOMContentLoaded', function () {
    initAllSelects();
});

function initAllSelects() {
    // Buscar todos los select que NO tengan ya la clase choices__input (para no duplicar)
    // Opcionalmente podemos ignorar los select que tengan la clase 'no-choices'
    // También ignoramos explícitamente los selects internos de Flatpickr para que no se rompan
    const selects = document.querySelectorAll('select:not(.no-choices):not(.flatpickr-monthDropdown-months):not(.swal2-select)');

    selects.forEach(selectElement => {
        // No inicializar Choices en el selector de paginación de DataTables
        if (selectElement.closest('.dataTables_length')) return;
        
        initChoicesOnElement(selectElement);
    });
}

function initChoicesOnElement(element) {
    // Si el elemento ya fue inicializado por choices (por accidente lo llamamos dos veces), lo abortamos
    if (element.closest('.choices')) {
        return;
    }

    // Configuración base de Choices acorde al UX de SGP
    const config = {
        searchEnabled: true, // Habilitar barra de búsqueda (útil para muchos items)
        searchPlaceholderValue: 'Buscar opción...',
        itemSelectText: '', // Quitar el texto feo flotante "Press to select"
        noResultsText: 'No se encontraron resultados',
        noChoicesText: 'No hay opciones disponibles',
        shouldSort: false, // Deshabilitar orden alfabético por defecto (respetar el HTML)
        position: 'auto',
        renderChoiceLimit: -1, // Mostrar todas las opciones que encuentre la búsqueda
        allowHTML: false, // Por seguridad
        searchFields: ['label', 'value'], // Buscar tanto por texto visible como por value
    };

    // Optimización: Si el select tiene muy pocas opciones (< 5), ocultamos la barra de búsqueda
    // para no sobrecargar visualmente selects simples como (Género M/F, o un simple Si/No)
    if (element.options && element.options.length <= 5) {
        config.searchEnabled = false;
    }

    try {
        // Inicializar Choices y guardarlo en una propiedad del elemento para referencia futura
        const choiceInstance = new Choices(element, config);
        element.choicesInstance = choiceInstance;
        
        // Registrar globalmente para poder destruirlo en eventos PJAX
        if (!window.SGPChoicesInstances) window.SGPChoicesInstances = [];
        window.SGPChoicesInstances.push(choiceInstance);
        
    } catch (e) {
        console.warn('SGP: Falló al inicializar Choices en el elemento:', element, e);
    }
}

/**
 * ── API PÚBLICA DE SGPChoices ────────────────────────────────────────────────
 * Permite inicializar selectores creados dinámicamente o que están en modales 
 * que resetean su HTML (como innerHTML o reset de form).
 */
window.SGPChoices = {
    /**
     * Re-inicializa un único select. Destruye el anterior si existía.
     * @param {string|HTMLElement} target - Selector CSS (ej: '#miSelect') o el nodo en sí.
     */
    reinit: function (target) {
        const d = (typeof target === 'string') ? document.querySelector(target) : target;

        if (!d) return;

        // Si el DOM fue sobrescrito, la instancia vieja se perdió en JS, pero si sigue viva la destruimos
        if (d.choicesInstance) {
            d.choicesInstance.destroy();
            d.choicesInstance = null;
        } else if (d.closest('.choices')) {
            // Caso borde: está el wrapper de choices pero no tenemos la instancia JS. 
            // Buscamos el select original oculto dentro del wrapper.
            const wrapper = d.closest('.choices');
            const hiddenSelect = wrapper.querySelector('select');

            if (hiddenSelect) {
                // Clonamos el select limpio, borramos todo el wrapper viejo de choices
                // y reinsertamos el clon limpio para inicializar de 0.
                const cleanClone = hiddenSelect.cloneNode(true);
                cleanClone.className = cleanClone.className.replace(/choices__input/g, '').replace(/is-hidden/g, '').trim();
                wrapper.parentNode.insertBefore(cleanClone, wrapper);
                wrapper.parentNode.removeChild(wrapper);
                initChoicesOnElement(cleanClone);
                return;
            }
        }

        // Si es un simple HTML nativo <select>, lo inicializamos sin problemas
        initChoicesOnElement(d);
    },

    /**
     * Escanea e inicializa selects dentro de un contenedor específico (ej: un Modal)
     */
    initContainer: function (containerElement) {
        if (!containerElement) return;
        const selects = containerElement.querySelectorAll('select:not(.no-choices):not(.flatpickr-monthDropdown-months):not(.swal2-select)');
        selects.forEach(s => {
            if (s.closest('.dataTables_length')) return;
            window.SGPChoices.reinit(s);
        });
    },

    /**
     * Escanea todo el documento y configura los selects nuevos (llamado manual post-ajax)
     */
    initAll: function () {
        initAllSelects();
    },

    /**
     * Destruye todas las instancias activas para prevenir memory leaks en Pjax.
     */
    destroyAll: function () {
        if (window.SGPChoicesInstances) {
            window.SGPChoicesInstances.forEach(instance => {
                try {
                    // 1. Romper referencia cíclica en nuestro lado del DOM
                    if (instance.passedElement && instance.passedElement.element) {
                        instance.passedElement.element.choicesInstance = null;
                    }
                    // 2. Destruir limpiamente la instancia nativa
                    instance.destroy();
                } catch (e) {
                    console.warn('SGP: Advertencia al destruir Choices.js (probablemente ya GC)', e);
                }
            });
            // 3. Vaciar completamente el arreglo global de referencias
            window.SGPChoicesInstances = [];
        }
    }
};
