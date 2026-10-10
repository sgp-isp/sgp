/**
 * ============================================
 * FLATPICKR AUTO-INIT — SGP
 * ============================================
 * 
 * Auto-detecta todos los input[type="date"] del DOM
 * y los inicializa con Flatpickr + tema SGP.
 * 
 * Features:
 *  - Locale español automático
 *  - Formato visual dd/mm/aaaa, envía Y-m-d al backend
 *  - Respeta atributos min, max, value, required
 *  - Soporte para reinicializar en modales dinámicos
 */

(function () {
    'use strict';

    // Configuración por defecto para todos los date pickers
    var defaultConfig = {
        dateFormat: 'Y-m-d',       // formato que recibe el backend
        altInput: true,             // muestra un input visual amigable
        altFormat: 'd/m/Y',         // formato visual para el usuario
        allowInput: true,           // permite escribir la fecha
        disableMobile: true,        // siempre usar Flatpickr (nunca el nativo móvil)
        locale: 'es',               // español
        animate: true,
        monthSelectorType: 'dropdown', // selector de mes como dropdown
        showMonths: 1,
        // Callback para mantener las clases CSS del input original
        onReady: function (selectedDates, dateStr, instance) {
            if (instance.altInput) {
                // Copiar clases del input original al altInput visible
                var originalClasses = instance.element.className;
                instance.altInput.className = originalClasses + ' flatpickr-alt-input';
                instance.altInput.removeAttribute('readonly');
                instance.altInput.setAttribute('readonly', 'readonly');
                instance.altInput.style.cursor = 'pointer';
                instance.altInput.style.backgroundColor = 'white';
            }
        }
    };

    /**
     * Inicializa Flatpickr en un input de tipo date
     * @param {HTMLInputElement} input 
     */
    function initDateInput(input) {
        // Evitar doble inicialización
        if (input._flatpickr) return;

        var config = Object.assign({}, defaultConfig);

        // Respetar atributos min/max del HTML
        if (input.getAttribute('max')) {
            config.maxDate = input.getAttribute('max');
        }
        if (input.getAttribute('min')) {
            config.minDate = input.getAttribute('min');
        }

        // Respetar valor por defecto
        if (input.value) {
            config.defaultDate = input.value;
        }

        // Convertir type="date" a type="text" para que Flatpickr funcione
        input.setAttribute('type', 'text');

        // Inicializar Flatpickr
        flatpickr(input, config);
    }

    /**
     * Escanea el DOM y convierte todos los input[type="date"]
     */
    function initAllDateInputs() {
        var dateInputs = document.querySelectorAll('input[type="date"]');
        for (var i = 0; i < dateInputs.length; i++) {
            // Saltar inputs marcados con data-no-flatpickr
            if (dateInputs[i].hasAttribute('data-no-flatpickr')) continue;
            initDateInput(dateInputs[i]);
        }
    }

    /**
     * API pública para reinicializar desde modales dinámicos
     * Uso: window.SGPFlatpickr.init(containerElement)
     */
    window.SGPFlatpickr = {
        /**
         * Inicializa Flatpickr en todos los date inputs dentro de un contenedor
         * @param {HTMLElement} [container=document] - Contenedor donde buscar inputs
         */
        init: function (container) {
            container = container || document;
            var dateInputs = container.querySelectorAll('input[type="date"]');
            for (var i = 0; i < dateInputs.length; i++) {
                initDateInput(dateInputs[i]);
            }
        },

        /**
         * Destruye la instancia Flatpickr de un input específico
         * @param {HTMLInputElement|string} input - Input element o selector
         */
        destroy: function (input) {
            if (typeof input === 'string') {
                input = document.querySelector(input);
            }
            if (input && input._flatpickr) {
                input._flatpickr.destroy();
            }
        },

        /**
         * Reinicializa un input específico (destroy + init)
         * @param {HTMLInputElement|string} input - Input element o selector
         * @param {Object} [extraConfig] - Configuración adicional
         */
        reinit: function (input, extraConfig) {
            if (typeof input === 'string') {
                input = document.querySelector(input);
            }
            if (!input) return;

            // Destruir instancia previa si existe
            if (input._flatpickr) {
                input._flatpickr.destroy();
            }

            var config = Object.assign({}, defaultConfig, extraConfig || {});

            if (input.getAttribute('max')) {
                config.maxDate = input.getAttribute('max');
            }
            if (input.getAttribute('min')) {
                config.minDate = input.getAttribute('min');
            }

            input.setAttribute('type', 'text');
            flatpickr(input, config);
        }
    };

    // Auto-inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllDateInputs);
    } else {
        // DOM ya está listo
        initAllDateInputs();
    }
})();
