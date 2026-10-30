/**
 * Real-Time Email Validation Script
 * Validates email availability via AJAX
 */

(function () {
    'use strict';

    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize email validation on all inputs with class 'validate-email'
    document.addEventListener('DOMContentLoaded', function () {
        const emailInputs = document.querySelectorAll('.validate-email');

        emailInputs.forEach(function (input) {
            const feedbackDiv = input.parentElement.querySelector('.email-feedback');

            if (!feedbackDiv) {
                console.warn('Email feedback div not found for input:', input);
                return;
            }

            // Debounced validation function
            const validateEmail = debounce(function () {
                const email = input.value.trim();

                // Clear feedback if empty
                if (!email) {
                    feedbackDiv.textContent = '';
                    input.classList.remove('is-valid', 'is-invalid');
                    return;
                }

                // Basic email format validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    feedbackDiv.textContent = '⚠️ Formato de correo inválido';
                    feedbackDiv.style.color = '#f59e0b'; // Orange
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    return;
                }

                // Show loading state
                feedbackDiv.textContent = '🔄 Verificando...';
                feedbackDiv.style.color = '#6b7280'; // Gray
                input.classList.remove('is-valid', 'is-invalid');

                // AJAX call to check email availability
                fetch(URLROOT + '/auth/apiCheckEmail', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ email: email })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            // Email already registered
                            feedbackDiv.textContent = '⛔ Este correo ya está registrado';
                            feedbackDiv.style.color = '#ef4444'; // Red
                            input.classList.remove('is-valid');
                            input.classList.add('is-invalid');
                        } else {
                            // Email available
                            feedbackDiv.textContent = '✅ Correo disponible';
                            feedbackDiv.style.color = '#10b981'; // Green
                            input.classList.remove('is-invalid');
                            input.classList.add('is-valid');
                        }
                    })
                    .catch(error => {
                        console.error('Error validating email:', error);
                        feedbackDiv.textContent = '⚠️ Error al verificar';
                        feedbackDiv.style.color = '#f59e0b'; // Orange
                        input.classList.remove('is-valid', 'is-invalid');
                    });
            }, 500); // 500ms debounce delay

            // Attach event listener
            input.addEventListener('input', validateEmail);
            input.addEventListener('blur', validateEmail);
        });
    });
})();
