/**
 * SGPToastService — Sistema de notificaciones toast del SGP
 *
 * Mejoras aplicadas:
 * [UX-1] Stacking limit: máximo MAX_TOASTS visibles simultáneamente (elimina el más antiguo)
 * [UX-2] Hover pause/resume correcto: pausa el timer Y la barra; lo reanuda al salir
 * [UX-3] XSS-safe: título y mensaje asignados con textContent (nunca innerHTML)
 * [UX-4] Accesibilidad: role="alert" + aria-live="polite" en el container
 * [UX-5] Íconos completos para los 4 tipos (success, error, warning, info)
 * [UX-6] Botón de cierre con aria-label descriptivo
 */
if (typeof window.NotificationService === 'undefined') {
    class SGPToastService {
        constructor() {
            this.container  = null;
            this.MAX_TOASTS = 4; // Límite de toasts apilados visibles
        }

        _ensureContainer() {
            if (this.container) return;
            this.container = document.getElementById('sgp-toast-container');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'sgp-toast-container';
                // [UX-4] Accesibilidad para lectores de pantalla
                this.container.setAttribute('role', 'region');
                this.container.setAttribute('aria-live', 'polite');
                this.container.setAttribute('aria-label', 'Notificaciones');
                document.body.appendChild(this.container);
            }
        }

        // [UX-1] Elimina el toast más antiguo si se supera el límite máximo
        _enforceStackLimit() {
            const toasts = this.container.querySelectorAll('.sgp-toast');
            if (toasts.length >= this.MAX_TOASTS) {
                const oldest = toasts[0];
                oldest.classList.remove('toast-show');
                oldest.classList.add('toast-hide');
                setTimeout(() => oldest.remove(), 400);
            }
        }

        show(type, title, message, duration = 5000) {
            this._ensureContainer();
            this._enforceStackLimit();

            // [UX-5] Mapa completo de iconos por tipo
            const iconMap = {
                success: 'ti-circle-check',
                error:   'ti-alert-circle',
                warning: 'ti-alert-triangle',
                info:    'ti-info-circle'
            };
            const iconClass = iconMap[type] || 'ti-info-circle';

            const toast = document.createElement('div');
            toast.className = `sgp-toast toast-${type}`;
            // [UX-4] Cada toast es un elemento de alerta individual
            toast.setAttribute('role', 'alert');

            // Estructura del toast — textos se asignan vía textContent (anti-XSS)
            toast.innerHTML = `
                <i class="ti ${iconClass} toast-icon" aria-hidden="true"></i>
                <div class="toast-body">
                    <h4 class="toast-title"></h4>
                    <p class="toast-msg"></p>
                </div>
                <button class="toast-close" aria-label="Cerrar notificación">
                    <i class="ti ti-x" aria-hidden="true"></i>
                </button>
                <div class="toast-progress">
                    <div class="toast-progress-bar" style="animation-duration: ${duration}ms"></div>
                </div>
            `;

            // [UX-3] Asignación segura: textContent nunca interpreta HTML (anti-XSS)
            toast.querySelector('.toast-title').textContent = title;
            toast.querySelector('.toast-msg').textContent   = message;

            this.container.appendChild(toast);

            // Entrada animada
            requestAnimationFrame(() => {
                requestAnimationFrame(() => toast.classList.add('toast-show'));
            });

            const progressBar = toast.querySelector('.toast-progress-bar');
            let timeoutId     = null;

            const removeToast = () => {
                clearTimeout(timeoutId);
                toast.classList.remove('toast-show');
                toast.classList.add('toast-hide');
                setTimeout(() => toast.remove(), 400);
            };

            const startTimer = (delay = duration) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(removeToast, delay);
            };

            // [UX-2] Pausar completamente al hacer hover (timer + barra de progreso)
            toast.addEventListener('mouseenter', () => {
                clearTimeout(timeoutId);
                progressBar.style.animationPlayState = 'paused';
            });

            // [UX-2] Reanudar al salir del hover (reinicia el timer completo)
            toast.addEventListener('mouseleave', () => {
                progressBar.style.animationPlayState = 'running';
                startTimer(); // Reinicia con duración completa para UX predecible
            });

            // [UX-6] Cierre manual y cierre automático por tiempo
            toast.querySelector('.toast-close').addEventListener('click', removeToast);
            startTimer();
        }

        success(msg, title = '¡Éxito!')    { this.show('success', title, msg); }
        error(msg, title = 'Error')         { this.show('error',   title, msg); }
        warning(msg, title = 'Atención')    { this.show('warning', title, msg); }
        info(msg, title = 'Información')    { this.show('info',    title, msg); }
    }

    // Instancia global disponible en todo el sistema
    window.NotificationService = new SGPToastService();
}
