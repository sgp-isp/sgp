/**
 * Bento OTP Morphing Component — Vanilla JS
 * Replicando la física SETTIGATION (Morphing: Casillas -> Colapso Central -> Spinner Orbital -> Checkmark SVG)
 * Sistema de Gestión de Pasantes (SGP) - ISPEB
 */

class BentoOtp {
    constructor(options = {}) {
        this.wrapper = typeof options.container === 'string' 
            ? document.querySelector(options.container) 
            : options.container;

        this.length = options.length || 6;
        this.onComplete = options.onComplete || null;
        this.onResend = options.onResend || null;
        this.resendTimerSeconds = options.resendCooldown || 60;
        this.autoSubmit = options.autoSubmit !== false;
        
        this.inputs = [];
        this.otpValue = '';
        this.timerInterval = null;

        if (this.wrapper) {
            this.buildHtml();
            this.bindEvents();
            this.startResendCooldown();
        }
    }

    buildHtml() {
        this.wrapper.innerHTML = `
            <div class="bento-otp-container" id="bentoOtpBoxContainer">
                ${Array.from({ length: this.length }, (_, i) => `
                    <input type="text" 
                           inputmode="numeric" 
                           pattern="[0-9]*" 
                           maxlength="1" 
                           class="bento-otp-box" 
                           data-index="${i}" 
                           autocomplete="one-time-code" 
                           aria-label="Dígito ${i + 1}">
                `).join('')}

                <!-- Spinner Orbital Morphing -->
                <div class="bento-otp-orbital" aria-hidden="true">
                    <div class="bento-orbital-ring">
                        <div class="bento-orbital-dot"></div>
                        <div class="bento-orbital-dot"></div>
                        <div class="bento-orbital-dot"></div>
                        <div class="bento-orbital-dot"></div>
                    </div>
                </div>

                <!-- Checkmark SVG Animado -->
                <div class="bento-otp-success" aria-hidden="true">
                    <svg class="bento-otp-checkmark-svg" viewBox="0 0 52 52">
                        <path class="bento-otp-check-path" d="M14 27 l8 8 l16 -16"/>
                    </svg>
                </div>
            </div>

            <div class="bento-otp-status" id="bentoOtpStatus">Introduce el código de 6 dígitos enviado a tu correo</div>

            <button type="button" class="bento-otp-resend" id="bentoOtpResendBtn" disabled>
                <i class="ti ti-refresh"></i> Reenviar código <span id="bentoOtpCountdown">(${this.resendTimerSeconds}s)</span>
            </button>
        `;

        this.containerEl = this.wrapper.querySelector('#bentoOtpBoxContainer');
        this.statusEl = this.wrapper.querySelector('#bentoOtpStatus');
        this.resendBtn = this.wrapper.querySelector('#bentoOtpResendBtn');
        this.countdownEl = this.wrapper.querySelector('#bentoOtpCountdown');
        this.inputs = Array.from(this.wrapper.querySelectorAll('.bento-otp-box'));
    }

    bindEvents() {
        this.inputs.forEach((input, idx) => {
            // Manejo de entrada
            input.addEventListener('input', (e) => this.handleInput(e, idx));

            // Manejo de Backspace / Flechas
            input.addEventListener('keydown', (e) => this.handleKeyDown(e, idx));

            // Pegar código completo
            input.addEventListener('paste', (e) => this.handlePaste(e));

            // Focus visual
            input.addEventListener('focus', () => input.select());
        });

        // Evento botón Reenviar
        if (this.resendBtn) {
            this.resendBtn.addEventListener('click', () => {
                if (!this.resendBtn.disabled && typeof this.onResend === 'function') {
                    this.onResend();
                    this.startResendCooldown();
                }
            });
        }
    }

    handleInput(e, idx) {
        const val = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = val ? val[0] : '';

        if (e.target.value) {
            e.target.classList.add('filled');
            if (idx < this.length - 1) {
                this.inputs[idx + 1].focus();
            }
        } else {
            e.target.classList.remove('filled');
        }

        this.updateValue();
    }

    handleKeyDown(e, idx) {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) {
            this.inputs[idx - 1].focus();
            this.inputs[idx - 1].value = '';
            this.inputs[idx - 1].classList.remove('filled');
            this.updateValue();
        } else if (e.key === 'ArrowLeft' && idx > 0) {
            this.inputs[idx - 1].focus();
        } else if (e.key === 'ArrowRight' && idx < this.length - 1) {
            this.inputs[idx + 1].focus();
        }
    }

    handlePaste(e) {
        e.preventDefault();
        const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        if (!pastedData) return;

        for (let i = 0; i < this.length; i++) {
            if (pastedData[i]) {
                this.inputs[i].value = pastedData[i];
                this.inputs[i].classList.add('filled');
            } else {
                this.inputs[i].value = '';
                this.inputs[i].classList.remove('filled');
            }
        }

        const nextFocus = Math.min(pastedData.length, this.length - 1);
        this.inputs[nextFocus].focus();
        this.updateValue();
    }

    updateValue() {
        this.otpValue = this.inputs.map(i => i.value).join('');
        this.containerEl.classList.remove('error');
        this.statusEl.className = 'bento-otp-status';
        this.statusEl.textContent = 'Introduce el código de 6 dígitos enviado a tu correo';

        if (this.otpValue.length === this.length) {
            if (this.autoSubmit && typeof this.onComplete === 'function') {
                this.startVerification();
                this.onComplete(this.otpValue);
            }
        }
    }

    /**
     * Fase 1 & 2: Inicia la animación de Morphing hacia el Spinner Orbital
     */
    startVerification(customStatus = 'Verificando código de seguridad...') {
        this.containerEl.classList.remove('error', 'success');
        this.containerEl.classList.add('morphing');
        this.statusEl.className = 'bento-otp-status';
        this.statusEl.innerHTML = `<i class="ti ti-shield-lock" style="animation:spin 1.5s linear infinite;display:inline-block;"></i> ${customStatus}`;
    }

    /**
     * Fase 3: Éxito — Morphing hacia el Checkmark SVG Trazado
     */
    setSuccess(message = '¡Código verificado con éxito!', callback = null) {
        this.containerEl.classList.remove('morphing', 'error');
        this.containerEl.classList.add('success');
        this.statusEl.className = 'bento-otp-status success';
        this.statusEl.innerHTML = `<i class="ti ti-circle-check"></i> ${message}`;

        if (typeof callback === 'function') {
            setTimeout(callback, 1000);
        }
    }

    /**
     * Error: Reversión de casillas con Shake animado
     */
    setError(errorMessage = 'Código incorrecto o expirado. Inténtalo nuevamente.') {
        this.containerEl.classList.remove('morphing', 'success');
        this.containerEl.classList.add('error');
        this.statusEl.className = 'bento-otp-status error';
        this.statusEl.innerHTML = `<i class="ti ti-alert-circle"></i> ${errorMessage}`;

        // Limpiar casillas tras breve pausa
        setTimeout(() => {
            this.inputs.forEach(input => {
                input.value = '';
                input.classList.remove('filled');
            });
            this.otpValue = '';
            if (this.inputs[0]) this.inputs[0].focus();
        }, 600);
    }

    /**
     * Reiniciar componente
     */
    reset() {
        this.containerEl.classList.remove('morphing', 'success', 'error');
        this.inputs.forEach(input => {
            input.value = '';
            input.classList.remove('filled');
        });
        this.otpValue = '';
        this.statusEl.className = 'bento-otp-status';
        this.statusEl.textContent = 'Introduce el código de 6 dígitos enviado a tu correo';
        if (this.inputs[0]) this.inputs[0].focus();
    }

    startResendCooldown() {
        if (!this.resendBtn || !this.countdownEl) return;
        
        let seconds = this.resendTimerSeconds;
        this.resendBtn.disabled = true;
        this.countdownEl.textContent = `(${seconds}s)`;

        clearInterval(this.timerInterval);
        this.timerInterval = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearInterval(this.timerInterval);
                this.resendBtn.disabled = false;
                this.countdownEl.textContent = '';
            } else {
                this.countdownEl.textContent = `(${seconds}s)`;
            }
        }, 1000);
    }

    getValue() {
        return this.otpValue;
    }
}

// Exportación global para navegadores y PJAX
window.BentoOtp = BentoOtp;
