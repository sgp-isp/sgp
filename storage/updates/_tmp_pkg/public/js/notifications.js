/**
 * Notifications UI Handler
 * Sistema de Gestión de Pasantes (SGP)
 * 
 * Maneja la carga, visualización e interacción con notificaciones en tiempo real
 */

(function () {
    'use strict';

    // Contador previo — usa sessionStorage para sobrevivir recargas de página.
    // null = primera vez en la sesión (no sonar). Se resetea al cerrar el navegador.
    const _STORAGE_KEY = 'sgp_notif_last_count';
    let _lastKnownCount = sessionStorage.getItem(_STORAGE_KEY) !== null
        ? parseInt(sessionStorage.getItem(_STORAGE_KEY), 10)
        : null;

    // ==================== CONFIGURACIÓN ====================
    const CONFIG = {
        REFRESH_INTERVAL: 20000, // 20 segundos — near real-time para exámenes
        ENDPOINTS: {
            GET_UNREAD: URLROOT + '/notifications/getUnread',
            MARK_AS_READ: URLROOT + '/notifications/markAsRead/',
            MARK_ALL_READ: URLROOT + '/notifications/markAllAsRead'
        }
    };

    // ==================== ELEMENTOS DOM ====================
    const elements = {
        btn: null,
        badge: null,
        dropdown: null,
        list: null,
        markAllBtn: null
    };

    // ==================== ICONOS POR TIPO ====================
    const NOTIFICATION_ICONS = {
        'usuario_creado': 'ti-user-plus',
        'perfil_actualizado': 'ti-user-check',
        'password_reset': 'ti-key',
        'sistema': 'ti-bell',
        'alerta_sistema': 'ti-alert-triangle',
        'alerta_urgente': 'ti-urgent',
        'alerta_exito': 'ti-circle-check',
        'info': 'ti-info-circle',
        'solicitud_pin': 'ti-lock-question',
        'solicitud_recovery': 'ti-help',
        'evaluacion_nueva': 'ti-star',
        'asignacion_nueva': 'ti-link',
        'cambio_estado': 'ti-switch-horizontal',
        'warning': 'ti-alert-triangle',
        'success': 'ti-circle-check',
        'actividad_nueva': 'ti-notebook',
        'actividad_corregida': 'ti-edit',
        'asistencia_registrada': 'ti-clock-check',
        'feriado_proximo': 'ti-calendar-event',
        'examen_publicado': 'ti-notebook',
        'examen_enviado': 'ti-school',
        'examen_revisado': 'ti-circle-check',
        'default': 'ti-bell'
    };

    // ==================== INICIALIZACIÓN ====================
    function init() {
        // Cachear elementos DOM — priorizar ID para compatibilidad con PJAX
        elements.btn      = document.getElementById('bell-btn')        || document.getElementById('notificationBtn');
        elements.badge    = document.getElementById('notif-badge-el')  || document.querySelector('.unread-count-badge');
        elements.dropdown = document.getElementById('notificationsDropdown');
        elements.list     = document.getElementById('notificationList');
        elements.markAllBtn = document.getElementById('markAllReadBtn');

        // Validación silenciosa: páginas sin topbar (login, kiosco, etc.)
        if (!elements.btn || !elements.dropdown) return;

        // ── Pintar el badge INMEDIATAMENTE con el valor cacheado ──────────────
        // Esto evita el flash de "0" durante el fetch inicial (PJAX o recarga)
        var cached = sessionStorage.getItem(_STORAGE_KEY);
        if (cached !== null) {
            _paintBadge(parseInt(cached, 10));
        }

        // Luego carga desde el servidor (actualiza/reconcilia)
        loadNotifications();
        setupEventListeners();

        // Recargar badge inmediatamente tras cada navegación PJAX
        document.addEventListener('pjax:complete', function () {
            loadNotifications();
        });

        // Auto-refresh periódico (clearInterval seguro si ya existía)
        if (window._sgpNotifInterval) clearInterval(window._sgpNotifInterval);
        window._sgpNotifInterval = setInterval(loadNotifications, CONFIG.REFRESH_INTERVAL);
    }

    // ==================== EVENT LISTENERS ====================
    function setupEventListeners() {
        // Toggle dropdown al hacer clic en el botón
        elements.btn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleDropdown();

            // ✨ Pausar animación de badge cuando dropdown está abierto
            if (elements.dropdown.classList.contains('show')) {
                elements.badge.style.animation = 'none';
            } else {
                elements.badge.style.animation = '';
            }
        });

        // Marcar todas como leídas
        if (elements.markAllBtn) {
            elements.markAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                markAllAsRead();
            });
        }

        // Event delegation para notificaciones (en lugar de onclick inline)
        elements.list.addEventListener('click', function (e) {
            const link = e.target.closest('.notification-link');
            if (link) {
                e.preventDefault();
                const item = link.closest('.notification-item');
                const id = item.dataset.id;
                const targetUrl = link.getAttribute('href');

                // Dynamic system notifications (sys_*) → navigate directly
                if (id && id.toString().startsWith('sys_')) {
                    if (targetUrl && targetUrl !== '#') {
                        window.location.href = targetUrl;
                    }
                    return;
                }

                markNotificationAsRead(id, targetUrl);
            }
        });

        // Cerrar dropdown al hacer clic fuera (con early return para performance)
        document.addEventListener('click', function (e) {
            // Early return si el dropdown no está visible
            if (!elements.dropdown.classList.contains('show')) return;

            if (!elements.dropdown.contains(e.target) && !elements.btn.contains(e.target)) {
                closeDropdown();
            }
        });
    }

    // ==================== CARGAR NOTIFICACIONES ====================
    function loadNotifications() {
        fetch(CONFIG.ENDPOINTS.GET_UNREAD, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                // Validar status HTTP
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('⚠️ Expected JSON but received:', contentType);
                    throw new Error('Respuesta del servidor no es válida (No JSON)');
                }
                return response.json();
            })
            .then(data => {
                // Si el middleware detecta sesión expirada y envía redirección
                if (data && data.session_expired && data.redirect) {
                    console.warn('🔄 Sesión expirada detectada vía API');
                    window.location.href = data.redirect;
                    return;
                }

                if (data.success) {
                    updateBadge(data.count);
                    renderNotifications(data.notifications);
                } else {
                    showEmptyState();
                }
            })
            .catch(error => {
                // Ignorar errores si la página se está descargando (navigating away)
                if (error.name === 'AbortError') return;

                console.error('❌ Error al cargar notificaciones:', error);
                showErrorState();
            });
    }

    // ==================== PINTAR BADGE (helper interno) ====================
    /**
     * Aplica visualmente el conteo al badge SIN tocar sessionStorage.
     * Llamado desde init() (caché) y desde updateBadge() (servidor).
     */
    function _paintBadge(count) {
        if (!elements.badge) return;
        if (count > 0) {
            elements.badge.textContent = count > 99 ? '99+' : count;
            elements.badge.classList.add('badge-visible');
            if (elements.btn) elements.btn.classList.add('bell-ringing');
        } else {
            elements.badge.textContent = '';
            elements.badge.classList.remove('badge-visible');
            if (elements.btn) elements.btn.classList.remove('bell-ringing');
        }
    }

    // ==================== ACTUALIZAR BADGE ====================
    function updateBadge(count) {
        if (!elements.badge) return;

        // Detectar si llegó una notificación nueva
        const isNew = (_lastKnownCount !== null && count > _lastKnownCount);

        // Pintar visualmente
        _paintBadge(count);

        if (count > 0 && isNew) {
            // Sonar solo si es una notificación nueva en un poll posterior
            try {
                const audio = new Audio(URLROOT + '/sounds/pop.wav');
                audio.volume = 0.5;
                audio.play().catch(() => {});
            } catch(e) {}
        }

        _lastKnownCount = count;
        sessionStorage.setItem(_STORAGE_KEY, count);
    }



    // ==================== RENDERIZAR NOTIFICACIONES ====================
    function renderNotifications(notifications) {
        if (!elements.list) return;

        if (!notifications || notifications.length === 0) {
            showEmptyState();
            return;
        }

        let html = '';

        notifications.forEach(notification => {
            let url = sanitizeUrl(notification.url); // ✅ Sanitizar URL para prevenir XSS

            let isRead = (notification.leida == 1);
            let containerClasses = '';
            let iconClasses = '';
            
            let tipoAlerta = notification.tipo || 'info';
            let icono = 'ti-bell'; // Tabler Icon default

            if (isRead) {
                containerClasses = 'notif-read';
                iconClasses = 'notif-icon-read';
                icono = 'ti-check';
            } else if (tipoAlerta === 'critical' || tipoAlerta === 'danger' || tipoAlerta === 'error') {
                containerClasses = 'notif-critical';
                iconClasses = 'notif-icon-critical';
                icono = 'ti-alert-circle';
            } else if (tipoAlerta === 'warning') {
                containerClasses = 'notif-warning';
                iconClasses = 'notif-icon-warning';
                icono = 'ti-shield-lock';
            } else if (tipoAlerta === 'success') {
                containerClasses = 'notif-info';
                iconClasses = 'notif-icon-info';
                icono = 'ti-circle-check';
            } else {
                containerClasses = 'notif-info';
                iconClasses = 'notif-icon-info';
                icono = NOTIFICATION_ICONS[tipoAlerta] || NOTIFICATION_ICONS['default'];
            }

            html += `
                <a href="${url}" class="notif-item ${containerClasses} notification-item notification-link" data-id="${notification.id}">
                    <i class="ti ${icono} ${iconClasses}" style="font-size: 22px; margin-top: 2px;"></i>
                    <div class="notif-content">
                        <div class="notif-title" style="font-weight: 700; color: #334155; font-size: 0.9rem; margin-bottom: 4px;">${escapeHtml(notification.titulo || 'Notificación')}</div>
                        <span class="notif-message" style="font-size: 0.8rem; color: #64748b; margin-bottom: 6px; line-height: 1.4; display: block;">${escapeHtml(notification.mensaje || '')}</span>
                        <span class="notif-time" style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 4px;"><i class="ti ti-clock"></i>${notification.time_ago}</span>
                    </div>
                </a>
            `;
        });
        elements.list.innerHTML = html;
    }

    // ==================== ESTADOS VACÍOS Y ERROR ====================
    function showEmptyState() {
        if (!elements.list) return;

        elements.list.innerHTML = `
            <div class="notifications-empty">
                <i class="ti ti-bell-off"></i>
                <p>Estás al día</p>
                <span>No tienes notificaciones nuevas</span>
            </div>
        `;
    }

    function showErrorState() {
        if (!elements.list) return;

        elements.list.innerHTML = `
            <div class="notifications-empty">
                <i class="ti ti-alert-circle"></i>
                <p>Error al cargar</p>
                <span>No se pudieron cargar las notificaciones</span>
            </div>
        `;
    }

    // ==================== MARCAR COMO LEÍDA ====================
    /**
     * Marcar notificación como leída (función privada, no global)
     * @param {number} id - ID de la notificación
     * @param {string} targetUrl - URL de destino
     */
    function markNotificationAsRead(id, targetUrl) {
        var csrfMetaNotif = document.querySelector('meta[name="csrf-token"]');
        fetch(CONFIG.ENDPOINTS.MARK_AS_READ + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfMetaNotif ? csrfMetaNotif.getAttribute('content') : ''
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // ✅ Optimistic update: actualizar UI inmediatamente
                    const item = document.querySelector(`[data-id="${id}"]`);
                    if (item) {
                        const indicator = item.querySelector('.unread-indicator');
                        if (indicator) indicator.remove();
                    }

                    // Recargar para sincronizar con servidor
                    loadNotifications();

                    // Si hay URL válida, navegar (PJAX si está disponible)
                    if (targetUrl && targetUrl !== '#') {
                        setTimeout(() => {
                            if (window.SGPPjax && typeof window.SGPPjax.navigate === 'function') {
                                window.SGPPjax.navigate(targetUrl, true);
                            } else {
                                window.location.href = targetUrl;
                            }
                        }, 300);
                    }
                } else {
                    // [FIX-UX] Antes esto quedaba en silencio total (ej. token CSRF
                    // vencido tras una pestaña abierta mucho tiempo -> 403 con JSON
                    // válido, nunca entraba al .catch de abajo). El mensaje del
                    // backend ya es accionable ("Recarga la página...").
                    console.error('❌ Error al marcar notificación:', data.message);
                    if (typeof NotificationService !== 'undefined') {
                        NotificationService.error(data.message || 'Error al marcar notificación');
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error al marcar notificación:', error);
                if (typeof NotificationService !== 'undefined') {
                    NotificationService.error('Error al marcar notificación');
                }
            });
    }

    // ==================== MARCAR TODAS COMO LEÍDAS ====================
    function markAllAsRead() {
        var csrfMetaNotifAll = document.querySelector('meta[name="csrf-token"]');
        fetch(CONFIG.ENDPOINTS.MARK_ALL_READ, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfMetaNotifAll ? csrfMetaNotifAll.getAttribute('content') : ''
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    if (typeof NotificationService !== 'undefined') {
                        NotificationService.success('Todas las notificaciones marcadas como leídas');
                    }
                } else {
                    // [FIX-UX] Mismo caso que markNotificationAsRead: un 403 por token
                    // CSRF vencido traía JSON válido (success:false) y quedaba en
                    // silencio total — el usuario no se enteraba de nada.
                    console.error('❌ Error al marcar todas:', data.message);
                    if (typeof NotificationService !== 'undefined') {
                        NotificationService.error(data.message || 'Error al marcar notificaciones');
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error al marcar todas:', error);
                if (typeof NotificationService !== 'undefined') {
                    NotificationService.error('Error al marcar notificaciones');
                }
            });
    }

    // ==================== TOGGLE DROPDOWN ====================
    function toggleDropdown() {
        elements.dropdown.classList.toggle('show');
        elements.btn.classList.toggle('active');
    }

    function closeDropdown() {
        elements.dropdown.classList.remove('show');
        elements.btn.classList.remove('active');
    }

    // ==================== UTILIDADES ====================
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Sanitizar URL para prevenir XSS
     * Bloquea URLs con javascript:, data:, vbscript:
     */
    function sanitizeUrl(url) {
        if (!url || url === '#') return '#';

        // Rechazar URLs peligrosas
        const dangerous = /^(javascript|data|vbscript):/i;
        if (dangerous.test(url)) {
            console.warn('⚠️ URL peligrosa bloqueada:', url);
            return '#';
        }

        return url;
    }

    // ==================== AUTO-INIT ====================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
