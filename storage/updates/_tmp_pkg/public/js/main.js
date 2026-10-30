/**
 * main.js - Lógica JavaScript Centralizada
 * Proyecto: SGP (Sistema de Gestión de Pasantes)
 * 
 * Este archivo contiene todas las funciones JavaScript comunes
 * utilizadas en múltiples vistas del sistema.
 */

// ============================================
// TOGGLE PASSWORD VISIBILITY
// ============================================

/**
 * Alternar visibilidad de contraseña
 * @param {string} fieldId - ID del input de contraseña
 * @param {HTMLElement} icon - Elemento del ícono (this)
 */
function togglePasswordVisibility(fieldId, icon) {
    const input = document.getElementById(fieldId);

    if (!input) {
        console.error(`Input con ID "${fieldId}" no encontrado`);
        return;
    }

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ti-eye');
        icon.classList.add('ti-eye-off');
    } else {
        input.type = 'password';
        icon.classList.remove('ti-eye-off');
        icon.classList.add('ti-eye');
    }
}

// ============================================
// WIZARD NAVIGATION (Para Registro Multi-Paso)
// ============================================

// ✅ REMOVED: Duplicate declaration - already exists in validation.js
// let currentStep = 1;

/**
 * Mostrar paso específico del wizard
 * @param {number} step - Número del paso a mostrar
 */
function showStep(step) {
    // Ocultar todos los pasos
    document.querySelectorAll('.form-step').forEach((el, index) => {
        if (index + 1 === step) {
            el.classList.add('active');
            el.style.display = 'block';
        } else {
            el.classList.remove('active');
            el.style.display = 'none';
        }
    });

    // Actualizar indicador de pasos
    document.querySelectorAll('.step').forEach((el, index) => {
        if (index + 1 < step) {
            el.classList.add('completed');
            el.classList.remove('active');
        } else if (index + 1 === step) {
            el.classList.add('active');
            el.classList.remove('completed');
        } else {
            el.classList.remove('active', 'completed');
        }
    });

    // ✅ NOTA: currentStep está declarado en validation.js
    if (typeof currentStep !== 'undefined') {
        currentStep = step;
    }
}

/**
 * Validar paso actual antes de avanzar
 * @returns {boolean} true si el paso es válido
 */
function validateCurrentStep() {
    if (currentStep === 1) {
        const name = document.getElementById('name');
        const cedula = document.getElementById('cedula');
        const email = document.getElementById('email');
        const password = document.getElementById('reg_password');
        const confirm = document.getElementById('password_confirm');

        // Validar que todos los campos estén llenos
        if (!name || !name.value || !cedula || !cedula.value || !email || !email.value || !password || !password.value || !confirm || !confirm.value) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor completa todos los campos',
                    confirmButtonColor: '#162660'
                });
            } else {
                alert('Por favor completa todos los campos');
            }
            return false;
        }

        // Validar email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Email inválido',
                    text: 'Por favor ingresa un correo electrónico válido',
                    confirmButtonColor: '#162660'
                });
            } else {
                alert('Por favor ingresa un correo electrónico válido');
            }
            return false;
        }

        // Validar que las contraseñas coincidan
        if (password.value !== confirm.value) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Contraseñas no coinciden',
                    text: 'Las contraseñas deben ser idénticas',
                    confirmButtonColor: '#162660'
                });
            } else {
                alert('Las contraseñas deben ser idénticas');
            }
            return false;
        }

        return true;
    }

    return true;
}

/**
 * Avanzar al siguiente paso
 * NOTA: Solo se define si no existe ya una versión personalizada en window
 */
if (!window.nextStep) {
    window.nextStep = function nextStep() {
        if (validateCurrentStep()) {
            showStep(currentStep + 1);
        }
    };
}

/**
 * Retroceder al paso anterior
 * NOTA: Solo se define si no existe ya una versión personalizada en window
 */
if (!window.prevStep) {
    window.prevStep = function prevStep() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    };
}

// ============================================
// FLOATING LABELS
// ============================================

/**
 * Inicializar floating labels
 * Detecta si un input tiene valor para mantener el label arriba
 */
function initFloatingLabels() {
    const inputs = document.querySelectorAll('.input-modern, .modern-select');

    inputs.forEach(input => {
        // Verificar valor inicial
        if (input.value && input.value.trim() !== '') {
            input.classList.add('has-value');
        }

        // Evento al escribir
        input.addEventListener('input', function () {
            if (this.value && this.value.trim() !== '') {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });

        // Evento al hacer blur
        input.addEventListener('blur', function () {
            if (!this.value || this.value.trim() === '') {
                this.classList.remove('has-value');
            }
        });
    });
}

// ============================================
// VALIDACIÓN DE EMAIL
// ============================================

/**
 * Validar formato de email
 * @param {HTMLInputElement} input - Input de email
 * @returns {boolean} true si el email es válido
 */
function validateEmail(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValid = emailRegex.test(input.value);

    if (input.classList.contains('validate-email')) {
        if (isValid) {
            input.classList.remove('invalid');
            input.classList.add('valid');
        } else {
            input.classList.remove('valid');
            input.classList.add('invalid');
        }
    }

    return isValid;
}

// ============================================
// INICIALIZACIÓN
// ============================================

/**
 * Inicializar funciones cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', function () {
    // Inicializar floating labels
    initFloatingLabels();

    // Si estamos en la página de registro, inicializar wizard
    if (document.querySelector('.form-step')) {
        showStep(1);
    }
});
