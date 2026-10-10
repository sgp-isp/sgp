// ====================================================
// SISTEMA DE VALIDACIÓN EN TIEMPO REAL - SGP
// ====================================================

/**
 * Validar Email en tiempo real
 * @param {HTMLInputElement} input - Input de email
 * @returns {boolean|null} true si válido, false si inválido, null si vacío
 */
function validateEmail(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const value = input.value.trim();

    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        return null;
    }

    if (emailRegex.test(value)) {
        input.classList.remove('invalid');
        input.classList.add('valid');
        return true;
    } else {
        input.classList.remove('valid');
        input.classList.add('invalid');
        return false;
    }
}

/**
 * Calcular fortaleza de contraseña
 * @param {string} password - Contraseña a evaluar
 * @returns {string} 'weak', 'medium' o 'strong'
 */
function calculatePasswordStrength(password) {
    let score = 0;

    // Requisitos obligatorios
    const length = password.length >= 8;
    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[^a-zA-Z0-9]/.test(password);

    if (length) score++;
    if (hasLower) score++;
    if (hasUpper) score++;
    if (hasNumber) score++;
    if (hasSpecial) score++;

    // Retornar directamente el string (más simple y limpio)
    if (score <= 2) return 'weak';
    if (score <= 4) return 'medium';
    return 'strong';
}

/**
 * Gestionar estado de carga de botones
 * @param {HTMLButtonElement} btn - El botón a modificar
 * @param {boolean} isLoading - true para activar carga, false para restaurar
 * @param {string} [loadingText='Guardando...'] - Texto a mostrar durante carga
 */
function setLoading(btn, isLoading, loadingText = 'Guardando...') {
    if (!btn) return;

    if (isLoading) {
        // Guardar estado original
        btn.dataset.originalText = btn.innerHTML;
        // Cambiar a estado loading
        btn.disabled = true;
        btn.classList.add('btn-loading');
        btn.innerHTML = `<i class="ti ti-loader animate-spin"></i> ${loadingText}`;
    } else {
        // Restaurar estado original
        btn.disabled = false;
        btn.classList.remove('btn-loading');
        if (btn.dataset.originalText) {
            btn.innerHTML = btn.dataset.originalText;
        }
    }
}

/**
 * Valida que la contraseña cumpla requisitos mínimos
 * REQUISITOS: Mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número, 1 carácter especial
 * @param {string} password - Contraseña a validar
 * @returns {Object} - {isValid: boolean, message: string}
 */
// FUNCIÓN ELIMINADA: Duplicada en línea 226 (versión más completa con UI)

/**
 * Actualizar indicador de fortaleza de contraseña
 * @param {HTMLInputElement} input - Input de contraseña
 * @param {HTMLElement} strengthBar - Barra de fortaleza
 * @param {HTMLElement} strengthText - Texto de fortaleza
 */
function updatePasswordStrength(input, strengthBar, strengthText) {
    const password = input.value;

    if (password.length === 0) {
        strengthBar.className = 'password-strength-bar';
        strengthText.textContent = '';
        input.classList.remove('valid', 'invalid');
        return;
    }

    // 1. ANÁLISIS DE REQUISITOS (Hard Constraints - Gatekeeper)
    const allRequirementsMet = validatePasswordRequirements(password);

    // 2. ANÁLISIS DE ENTROPÍA (Soft Constraints)
    let strength = calculatePasswordStrength(password);

    // 🚨 PATRÓN GATEKEEPER: Si no cumple requisitos, CAP visual en 'medium'
    // La barra NUNCA puede mostrar "Fuerte" si falta algún requisito
    if (!allRequirementsMet && strength === 'strong') {
        strength = 'medium'; // Degradar visualmente
    }

    // 3. ACTUALIZAR BARRA Y TEXTO
    strengthBar.className = `password-strength-bar ${strength}`;

    const messages = {
        weak: 'Débil',
        medium: 'Media',
        strong: 'Fuerte'
    };

    strengthText.textContent = messages[strength];
    strengthText.className = `password-strength-text ${strength}`;

    // 4. FEEDBACK VISUAL DEL INPUT (Sincronizado con requisitos)
    if (allRequirementsMet && strength === 'strong') {
        // ✅ Contraseña fuerte Y todos los requisitos → Check verde
        input.classList.remove('invalid');
        input.classList.add('valid');
    } else if (password.length > 0 && strength === 'weak') {
        // ❌ Contraseña débil → Check rojo
        input.classList.remove('valid');
        input.classList.add('invalid');
    } else {
        // ⚪ Estado neutral (escribiendo o media) → Sin check
        input.classList.remove('valid', 'invalid');
    }
}

/**
 * Validar que dos contraseñas coincidan
 * PATRÓN GATEKEEPER: Solo puede ser válido si la contraseña principal es válida
 * @param {HTMLInputElement} password - Input de contraseña original
 * @param {HTMLInputElement} confirmation - Input de confirmación
 * @returns {boolean|null} true si coinciden, false si no, null si vacío
 */
function validatePasswordMatch(password, confirmation) {
    const value = confirmation.value;

    if (value.length === 0) {
        confirmation.classList.remove('valid', 'invalid');
        return null;
    }

    // CIRCUIT BREAKER: Verificar si la contraseña principal es válida
    const passwordIsValid = password.classList.contains('valid');
    const passwordsMatch = value === password.value;

    if (passwordsMatch && passwordIsValid) {
        // ✅ Coinciden Y la contraseña principal es válida
        confirmation.classList.remove('invalid');
        confirmation.classList.add('valid');
        return true;
    } else if (passwordsMatch && !passwordIsValid) {
        // ⚪ Coinciden pero la contraseña principal no es válida → Neutral
        confirmation.classList.remove('valid', 'invalid');
        return null;
    } else {
        // ❌ No coinciden → Error
        confirmation.classList.remove('valid');
        confirmation.classList.add('invalid');
        return false;
    }
}

/**
 * Toggle de visibilidad de contraseña
 * @param {string} inputId - ID del input de contraseña
 * @param {HTMLElement} iconElement - Elemento del icono
 */
function togglePasswordVisibility(inputId, iconElement) {
    const input = document.getElementById(inputId);

    if (input.type === 'password') {
        input.type = 'text';
        iconElement.classList.remove('ti-eye');
        iconElement.classList.add('ti-eye-off');
    } else {
        input.type = 'password';
        iconElement.classList.remove('ti-eye-off');
        iconElement.classList.add('ti-eye');
    }
}

/**
 * Validar longitud mínima de contraseña
 * @param {HTMLInputElement} input - Input de contraseña
 * @param {number} minLength - Longitud mínima (default: 6)
 */
function validatePasswordLength(input, minLength = 6) {
    const value = input.value;

    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        return null;
    }

    if (value.length >= minLength) {
        input.classList.remove('invalid');
        input.classList.add('valid');
        return true;
    } else {
        input.classList.remove('valid');
        input.classList.add('invalid');
        return false;
    }
}

// ====================================================
// VALIDACIÓN DE REQUISITOS DE CONTRASEÑA
// ====================================================

/**
 * Validar requisitos de contraseña y actualizar checklist visual
 * @param {string} password - Contraseña a validar
 * @returns {boolean} true si cumple todos los requisitos
 */
function validatePasswordRequirements(password) {
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/.test(password)
    };

    // Actualizar UI si existen los elementos
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');

    if (reqLength) reqLength.classList.toggle('met', requirements.length);
    if (reqUppercase) reqUppercase.classList.toggle('met', requirements.uppercase);
    if (reqLowercase) reqLowercase.classList.toggle('met', requirements.lowercase);
    if (reqNumber) reqNumber.classList.toggle('met', requirements.number);
    if (reqSpecial) reqSpecial.classList.toggle('met', requirements.special);

    // Retornar si todos los requisitos se cumplen
    return Object.values(requirements).every(req => req === true);
}

/**
 * Actualizar fortaleza con requisitos estrictos
 * @param {HTMLInputElement} input - Input de contraseña
 * @param {HTMLElement} strengthBar - Barra de fortaleza
 * @param {HTMLElement} strengthText - Texto de fortaleza
 */
// FUNCIÓN ELIMINADA: Duplicada en línea 629 (versión más completa con validación)

// ====================================================
// MULTI-STEP FORM NAVIGATION
// ====================================================

if (typeof currentStep === 'undefined') { var currentStep = 1; }

/**
 * Mostrar un paso específico del formulario
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

    currentStep = step;
}

/**
 * Validar el paso actual antes de avanzar
 * @returns {boolean} true si el paso es válido
 */
function validateCurrentStep() {
    if (currentStep === 1) {
        // ✅ CORREGIDO: IDs sincronizados con register.php
        const name = document.getElementById('nombre');      // Cambiado de 'name' a 'nombre'
        const cedula = document.getElementById('cedula');
        const email = document.getElementById('email');
        const password = document.getElementById('password'); // Cambiado de 'reg_password' a 'password'
        const confirm = document.getElementById('password_confirm');

        // ✅ VALIDACIÓN NULL-SAFE: Verificar que los elementos existan
        // IMPORTANTE: Solo mostrar error si estamos en el formulario de registro
        // (el wizard no tiene estos campos, así que no debe mostrar error)
        if (!name || !email || !password || !confirm) {
            // Verificar si al menos uno de los campos existe (estamos en registro)
            if (name || email || password || confirm) {
                console.error('❌ Error: Faltan elementos en el HTML. Verifica los IDs: nombre, cedula, email, password, password_confirm');
                Swal.fire({
                    icon: 'error',
                    title: 'Error del sistema',
                    text: 'No se pudieron encontrar todos los campos del formulario',
                    confirmButtonColor: '#162660'
                });
            }
            // Si ninguno existe, simplemente retornar true (no estamos en registro)
            return false;
        }

        // Validar que todos los campos estén llenos (cedula es opcional)
        if (!name.value || !email.value || !password.value || !confirm.value || (cedula && !cedula.value)) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor completa todos los campos obligatorios',
                confirmButtonColor: '#162660'
            });
            return false;
        }

        // Validar email
        if (!validateEmail(email)) {
            Swal.fire({
                icon: 'error',
                title: 'Email inválido',
                text: 'Por favor ingresa un correo electrónico válido',
                confirmButtonColor: '#162660'
            });
            return false;
        }

        // Validar requisitos de contraseña
        if (!validatePasswordRequirements(password.value)) {
            Swal.fire({
                icon: 'error',
                title: 'Contraseña débil',
                text: 'La contraseña debe cumplir todos los requisitos de seguridad',
                confirmButtonColor: '#162660'
            });
            return false;
        }

        // Validar que las contraseñas coincidan
        if (password.value !== confirm.value) {
            Swal.fire({
                icon: 'error',
                title: 'Contraseñas no coinciden',
                text: 'Las contraseñas deben ser idénticas',
                confirmButtonColor: '#162660'
            });
            return false;
        }

        return true;
    }

    return true;
}

/**
 * Avanzar al siguiente paso
 * NOTA: Solo se define si no existe ya una versión personalizada en window
 * (register.php define window.nextStep en el <head> con lógica propia)
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
        showStep(currentStep - 1);
    };
}

// ====================================================
// VALIDACIÓN DE CÉDULA VENEZOLANA
// ====================================================

/**
 * Valida y formatea input de cédula venezolana en tiempo real
 * Formato: Solo números, entre 6 y 8 dígitos
 * 
 * @param {Event} event - Evento del input
 */
var _cedulaTimer = null; // [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta <script src> sin reload completo.
function validateCedulaInput(event) {
    const input = event.target;
    let value = input.value.replace(/[^0-9]/g, '');
    if (value.length > 8) value = value.substring(0, 8);
    input.value = value;

    // Verificar unicidad tras 500ms de inactividad
    clearTimeout(_cedulaTimer);
    const hint = input.parentElement.querySelector('.cedula-feedback');
    if (hint) hint.innerHTML = '';
    if (value.length < 6) return;

    _cedulaTimer = setTimeout(() => {
        const urlRoot  = (typeof URLROOT !== 'undefined') ? URLROOT : '';
        const checkUrl = input.dataset.checkUrl || `${urlRoot}/users/checkUnique`;
        fetch(`${checkUrl}?campo=cedula&valor=${encodeURIComponent(value)}`)
        .then(r => r.json())
        .then(data => {
            if (!hint) return;
            if (data.available === false) {
                hint.innerHTML = '<i class="ti ti-x"></i> Esta cédula ya está registrada';
                hint.style.color = '#EF4444';
                input.classList.add('invalid'); input.classList.remove('valid');
            } else {
                hint.innerHTML = '<i class="ti ti-check"></i> Cédula disponible';
                hint.style.color = '#10B981';
                input.classList.add('valid'); input.classList.remove('invalid');
            }
        }).catch(() => {});
    }, 500);
}

/**
 * Valida formato completo de cédula venezolana
 * @param {string} cedula - Cédula a validar
 * @returns {boolean} - true si es válida (solo números)
 */
function isValidCedula(cedula) {
    // Solo números, entre 6 y 8 dígitos
    const regex = /^[0-9]{6,8}$/;
    return regex.test(cedula);
}

// ====================================================
// VALIDACIÓN NUMÉRICA
// ====================================================

/**
 * Permite solo números en un input
 * @param {Event} event - Evento del input
 */
function validateNumericInput(event) {
    const input = event.target;
    input.value = input.value.replace(/[^0-9]/g, '');
}

/**
 * Permite solo números y un punto decimal
 * @param {Event} event - Evento del input
 */
function validateDecimalInput(event) {
    const input = event.target;
    let value = input.value;

    // Permitir solo números y un punto
    value = value.replace(/[^0-9.]/g, '');

    // Permitir solo un punto decimal
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }

    input.value = value;
}

/**
 * Validar input de nombres y apellidos (solo letras, acentos, ñ y espacios)
 * @param {Event} event - Evento del input
 * 
 * PROPÓSITO:
 * Prevenir que se ingresen números o caracteres especiales en campos de nombres.
 * Permite letras con acentos (á, é, í, ó, ú), la letra ñ y espacios.
 * 
 * EJEMPLOS VÁLIDOS:
 * - "José María"
 * - "María Fernández"
 * - "Ángel Núñez"
 * 
 * EJEMPLOS INVÁLIDOS (serán removidos automáticamente):
 * - "José123" → "José"
 * - "María@" → "María"
 * - "Pedro-Luis" → "PedroLuis"
 */
function validateNameInput(event) {
    const input = event.target;
    // Permitir solo letras (incluyendo acentos), ñ y espacios
    // Regex: [^a-zA-ZáéíóúÁÉÍÓÚñÑ\s] = "todo lo que NO sea letra o espacio"
    input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
}

// ====================================================
// SANITIZACIÓN DE INPUTS
// ====================================================

/**
 * Sanitiza un string eliminando espacios y escapando HTML
 * @param {string} value - Valor a sanitizar
 * @returns {string} - Valor sanitizado
 */
function sanitizeInput(value) {
    if (typeof value !== 'string') {
        return '';
    }

    // Eliminar espacios al inicio y final
    value = value.trim();

    // Escapar caracteres HTML básicos
    const map = {
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#x27;',
        '/': '&#x2F;'
    };

    return value.replace(/[<>"'/]/g, (char) => map[char] || char);
}

/**
 * Sanitiza todos los inputs de un formulario antes de enviar
 * @param {HTMLFormElement} form - Formulario a sanitizar
 * @returns {FormData} - FormData sanitizado
 */
function sanitizeForm(form) {
    const formData = new FormData(form);
    const sanitized = new FormData();

    for (let [key, value] of formData.entries()) {
        if (typeof value === 'string') {
            sanitized.append(key, sanitizeInput(value));
        } else {
            sanitized.append(key, value);
        }
    }

    return sanitized;
}

// ====================================================
// VALIDACIÓN DE TELÉFONO
// ====================================================

/**
 * Valida y formatea teléfono venezolano
 * Formato: 0414-1234567 (11 dígitos)
 * @param {Event} event - Evento del input
 */
function validatePhoneInput(event) {
    const input = event.target;
    let value = input.value.replace(/[^0-9]/g, '');

    // Limitar a 11 dígitos
    if (value.length > 11) {
        value = value.substring(0, 11);
    }

    // Formatear con guión
    if (value.length > 4) {
        value = value.substring(0, 4) + '-' + value.substring(4);
    }

    input.value = value;
}

// ====================================================
// MEJORAR VALIDACIÓN DE EMAIL EXISTENTE
// ====================================================

/**
 * Valida formato de email mejorado (RFC 5322 simplificado)
 * @param {string} email - Email a validar
 * @returns {boolean} - true si es válido
 */
function isValidEmail(email) {
    const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (!regex.test(email)) {
        return false;
    }

    // Verificar caracteres peligrosos
    const dangerousChars = /<|>|"|'|`|;|\\/g;
    if (dangerousChars.test(email)) {
        return false;
    }

    return true;
}

/**
 * Valida email con feedback visual mejorado
 * @param {HTMLInputElement} input - Input de email
 * @returns {boolean} - true si es válido
 */
function validateEmailWithFeedback(input) {
    const email = input.value.trim();
    const feedbackEl = input.parentElement.querySelector('.email-feedback');

    if (email === '') {
        input.classList.remove('valid', 'invalid');
        if (feedbackEl) feedbackEl.innerHTML = '';
        return true;
    }

    const isValid = isValidEmail(email);
    if (!isValid) {
        input.classList.remove('valid');
        input.classList.add('invalid');
        if (feedbackEl) {
            feedbackEl.innerHTML = '<i class="ti ti-x"></i> Formato de correo inválido';
            feedbackEl.style.color = '#EF4444';
        }
        return false;
    }

    // Formato válido — verificar unicidad vía AJAX
    if (feedbackEl) {
        feedbackEl.innerHTML = '<i class="ti ti-loader"></i> Verificando...';
        feedbackEl.style.color = '#94a3b8';
    }
    const urlRoot  = (typeof URLROOT !== 'undefined') ? URLROOT : '';
    const checkUrl = input.dataset.checkUrl || `${urlRoot}/users/checkUnique`;
    const excludeId = input.dataset.excludeId || '';
    fetch(`${checkUrl}?campo=correo&valor=${encodeURIComponent(email)}${excludeId ? '&exclude=' + excludeId : ''}`)
    .then(r => r.json())
    .then(data => {
        if (data.available === false) {
            input.classList.remove('valid'); input.classList.add('invalid');
            if (feedbackEl) {
                feedbackEl.innerHTML = '<i class="ti ti-x"></i> Este correo ya está registrado';
                feedbackEl.style.color = '#EF4444';
            }
        } else {
            input.classList.remove('invalid'); input.classList.add('valid');
            if (feedbackEl) {
                feedbackEl.innerHTML = '<i class="ti ti-check"></i> Correo disponible';
                feedbackEl.style.color = '#10B981';
            }
        }
    }).catch(() => {
        // Si falla el AJAX, al menos el formato es válido
        input.classList.remove('invalid'); input.classList.add('valid');
        if (feedbackEl) {
            feedbackEl.innerHTML = '<i class="ti ti-check"></i> Formato válido';
            feedbackEl.style.color = '#10B981';
        }
    });

    return true; // formato OK, unicidad se verifica async
}

/**
 * Actualiza indicador de fortaleza y valida requisitos estrictos
 * @param {HTMLInputElement} input - Input password
 * @param {HTMLElement} strengthBar - Barra visual
 * @param {HTMLElement} strengthText - Texto descriptivo
 */
function updatePasswordStrengthWithRequirements(input, strengthBar, strengthText) {
    const password = input.value;

    // ✅ CORREGIDO: calculatePasswordStrength ahora retorna string directo
    const strength = calculatePasswordStrength(password);
    const validation = validatePasswordRequirements(password);

    // Actualizar clases de la barra
    if (strengthBar) {
        strengthBar.className = 'password-strength-bar';
        strengthBar.classList.add(strength); // ✅ Ahora strength es un string ('weak', 'medium', 'strong')
    }

    // Actualizar texto
    const messages = {
        'weak': 'Débil',
        'medium': 'Media',
        'strong': 'Fuerte'
    };

    if (strengthText) {
        strengthText.textContent = messages[strength];
        strengthText.className = 'password-strength-text ' + strength;

        // Color dinámico basado en validación completa
        const allRequirementsMet = validatePasswordRequirements(password);
        if (allRequirementsMet && strength === 'strong') {
            strengthText.style.color = '#10B981'; // Verde solo si TODO es válido
        } else if (strength === 'weak') {
            strengthText.style.color = '#EF4444'; // Rojo para débil
        } else {
            strengthText.style.color = '#F59E0B'; // Naranja para media
        }
    }

    // ✅ CORREGIDO: Feedback visual sincronizado con requisitos
    // Solo marcar válido si cumple TODOS los requisitos
    const allRequirementsMet = validatePasswordRequirements(password);

    if (allRequirementsMet && strength === 'strong') {
        input.classList.remove('invalid');
        input.classList.add('valid');
    } else if (password.length > 0 && strength === 'weak') {
        input.classList.remove('valid');
        input.classList.add('invalid');
    } else {
        // Estado neutral (escribiendo o media)
        input.classList.remove('valid', 'invalid');
    }

    // 🔄 TRIGGER: Actualizar confirmación si existe y tiene contenido
    const confirmInput = document.getElementById('password_confirm');
    if (confirmInput && confirmInput.value.length > 0) {
        validatePasswordMatch(input, confirmInput);
    }
}

// ====================================================
// ESTILOS CSS DINÁMICOS
// ====================================================

if (!document.getElementById('validation-styles-extended')) {
    const style = document.createElement('style');
    style.id = 'validation-styles-extended';
    style.textContent = `
        .email-feedback {
            min-height: 20px;
            font-size: 0.85rem;
            margin-top: 4px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
    `;
    document.head.appendChild(style);
}

console.log('✅ validation.js - Funciones extendidas cargadas');
// ====================================================
// VALIDACIÓN DE PREGUNTAS DE SEGURIDAD
// ====================================================

/**
 * Validar que las 3 preguntas de seguridad sean diferentes
 */
function validateUniqueQuestions(q1Id, q2Id, q3Id) {
    const q1 = document.getElementById(q1Id)?.value;
    const q2 = document.getElementById(q2Id)?.value;
    const q3 = document.getElementById(q3Id)?.value;

    if (!q1 || !q2 || !q3) return true;

    if (q1 === q2 || q1 === q3 || q2 === q3) {
        if (typeof NotificationService !== 'undefined') {
            NotificationService.error('Debes seleccionar 3 preguntas diferentes');
        }
        return false;
    }
    return true;
}

/**
 * Validar que las 3 respuestas sean diferentes (case-insensitive)
 */
function validateUniqueAnswers(a1Id, a2Id, a3Id) {
    const a1 = document.getElementById(a1Id)?.value.trim().toLowerCase();
    const a2 = document.getElementById(a2Id)?.value.trim().toLowerCase();
    const a3 = document.getElementById(a3Id)?.value.trim().toLowerCase();

    if (!a1 || !a2 || !a3) return true;

    if (a1 === a2 || a1 === a3 || a2 === a3) {
        if (typeof NotificationService !== 'undefined') {
            NotificationService.error('Las respuestas de seguridad deben ser diferentes');
        }
        return false;
    }
    return true;
}

/**
 * Verificar duplicados de preguntas en tiempo real
 */
function checkDuplicateQuestions(q1Id, q2Id, q3Id) {
    const q1 = document.getElementById(q1Id);
    const q2 = document.getElementById(q2Id);
    const q3 = document.getElementById(q3Id);

    if (!q1 || !q2 || !q3) return true;

    const values = [q1.value, q2.value, q3.value].filter(v => v);
    const unique = new Set(values);

    if (values.length === 3 && unique.size !== 3) {
        [q1, q2, q3].forEach(select => {
            select.classList.add('invalid');
            select.classList.remove('valid');
        });
        return false;
    } else if (values.length === 3) {
        [q1, q2, q3].forEach(select => {
            select.classList.add('valid');
            select.classList.remove('invalid');
        });
        return true;
    }
    return true;
}

/**
 * Verificar duplicados de respuestas en tiempo real
 */
function checkDuplicateAnswers(a1Id, a2Id, a3Id) {
    const a1 = document.getElementById(a1Id);
    const a2 = document.getElementById(a2Id);
    const a3 = document.getElementById(a3Id);

    if (!a1 || !a2 || !a3) return true;

    const values = [
        a1.value.trim().toLowerCase(),
        a2.value.trim().toLowerCase(),
        a3.value.trim().toLowerCase()
    ].filter(v => v);

    const unique = new Set(values);

    if (values.length === 3 && unique.size !== 3) {
        [a1, a2, a3].forEach(input => {
            input.classList.add('invalid');
            input.classList.remove('valid');
        });
        return false;
    } else if (values.length === 3) {
        [a1, a2, a3].forEach(input => {
            input.classList.add('valid');
            input.classList.remove('invalid');
        });
        return true;
    }
    return true;
}

// ====================================================
// SPANISH WRAPPER FUNCTIONS (BACKWARD COMPATIBILITY)
// ====================================================

/**
 * Wrapper functions para compatibilidad con nombres en español
 * Mantiene una única fuente de verdad (funciones en inglés arriba)
 * mientras permite que el HTML use nombres en español
 */

// Toggle de visibilidad de contraseña (español)
window.togglePass = function (fieldId, icon) {
    togglePasswordVisibility(fieldId, icon);
};

// Actualizar fortaleza de contraseña (español)
window.actualizarFortaleza = function (input) {
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    if (strengthBar && strengthText) {
        updatePasswordStrengthWithRequirements(input, strengthBar, strengthText);
    } else {
        // Fallback si no existen los elementos
        updatePasswordStrength(input, strengthBar, strengthText);
    }
};

// Validar email (español)
window.validarEmail = function (input) {
    return validateEmailWithFeedback(input);
};

// Validar confirmación de contraseña (español)
window.validarConfirmacion = function (passwordInput, confirmInput) {
    return validatePasswordMatch(passwordInput, confirmInput);
};

// Validar preguntas de seguridad únicas (español)
window.validarPreguntasUnicas = function (q1Id, q2Id, q3Id) {
    return validateUniqueQuestions(q1Id, q2Id, q3Id);
};

// Validar respuestas de seguridad únicas (español)
window.validarRespuestasUnicas = function (a1Id, a2Id, a3Id) {
    return validateUniqueAnswers(a1Id, a2Id, a3Id);
};

console.log('✅ validation.js - Spanish wrapper functions loaded');
