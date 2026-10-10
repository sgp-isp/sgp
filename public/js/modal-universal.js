/**
 * SGP Modal Universal — Search & View
 * Provides: SGPModal.buscar() and SGPModal.verUsuario(id)
 */
(function () {
    'use strict';

    // ─── State ───
    let activeRoleFilter = 0; // 0 = all, 3 = pasantes only

    // ─── Cache (60 seconds) ───
    const cache = new Map();
    const CACHE_TTL = 60000;

    function getCached(key) {
        const entry = cache.get(key);
        if (!entry) return null;
        if (Date.now() - entry.ts > CACHE_TTL) { cache.delete(key); return null; }
        return entry.data;
    }
    function setCache(key, data) {
        cache.set(key, { data, ts: Date.now() });
    }

    // ─── Avatar colors by role ───
    const AVATAR_COLORS = {
        1: 'linear-gradient(135deg, #f59e0b, #d97706)', // Admin
        2: 'linear-gradient(135deg, #2563eb, #1d4ed8)', // Tutor
        3: 'linear-gradient(135deg, #059669, #047857)'  // Pasante
    };

    const BADGE_CLASSES = { 1: 'sgp-badge-admin', 2: 'sgp-badge-tutor', 3: 'sgp-badge-pasante' };

    // ─── Debounce ───
    function debounce(fn, ms) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    // ─── Create modal HTML (once) ───
    let initialized = false;

    function init() {
        if (initialized) return;
        initialized = true;

        const html = `
        <!-- MODAL BÚSQUEDA -->
        <div id="sgpModalBuscar" class="sgp-modal-overlay" onclick="if(event.target===this)SGPModal.cerrar('buscar')">
            <div class="sgp-modal">
                <div class="sgp-modal-header">
                    <h3><i class="ti ti-search" style="margin-right:8px"></i>Búsqueda Rápida</h3>
                    <p>Buscar por nombre o número de cédula</p>
                    <button class="sgp-modal-close" onclick="SGPModal.cerrar('buscar')"><i class="ti ti-x"></i></button>
                </div>
                <div class="sgp-modal-body">
                    <div class="sgp-search-wrapper">
                        <i class="ti ti-search sgp-search-icon"></i>
                        <input type="text" id="sgpSearchInput" class="sgp-search-input" placeholder="Escriba nombre o cédula..." autocomplete="off">
                        <div id="sgpSearchLoading" class="sgp-search-loading"></div>
                    </div>
                    <div id="sgpSearchResults" class="sgp-results-list">
                        <div class="sgp-empty-state">
                            <i class="ti ti-users-group"></i>
                            <p>Escriba al menos 2 caracteres para buscar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL VER USUARIO (BENTO) -->
        <div id="sgpModalVer" class="sgp-modal-overlay" onclick="if(event.target===this)SGPModal.cerrar('ver')">
            <div class="sgp-modal sgp-modal-view">
                <div class="sgp-modal-header">
                    <h3 id="sgpVerTitle"><i class="ti ti-user" style="margin-right:8px"></i>Perfil de Usuario</h3>
                    <p id="sgpVerSubtitle">Información completa</p>
                    <button class="sgp-modal-close" onclick="SGPModal.cerrar('ver')"><i class="ti ti-x"></i></button>
                </div>
                <div class="sgp-modal-body" id="sgpVerBody">
                    <!-- Populated by JS -->
                </div>
                <div class="sgp-modal-actions" id="sgpVerActions"></div>
            </div>
        </div>`;

        const container = document.createElement('div');
        container.innerHTML = html;
        document.body.appendChild(container);

        // Bind search with debounce
        const input = document.getElementById('sgpSearchInput');
        if (input) {
            input.addEventListener('input', debounce(function () {
                performSearch(this.value.trim());
            }, 300));
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') SGPModal.cerrar('buscar');
            });
        }
    }

    // ─── Search ───
    async function performSearch(query) {
        const resultsEl = document.getElementById('sgpSearchResults');
        const loadingEl = document.getElementById('sgpSearchLoading');

        if (query.length < 2) {
            resultsEl.innerHTML = '<div class="sgp-empty-state"><i class="ti ti-users-group"></i><p>Escriba al menos 2 caracteres para buscar</p></div>';
            return;
        }

        loadingEl.classList.add('active');
        try {
            let url = URLROOT + '/users/buscar?q=' + encodeURIComponent(query);
            if (activeRoleFilter > 0) url += '&rol=' + activeRoleFilter;
            const resp = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await resp.json();
            loadingEl.classList.remove('active');

            if (!json.success || !json.data.length) {
                resultsEl.innerHTML = '<div class="sgp-empty-state"><i class="ti ti-user-off"></i><p>No se encontraron resultados</p></div>';
                return;
            }

            resultsEl.innerHTML = json.data.map((u, i) => {
                const clickAction = activeRoleFilter > 0 && window.SGPModal._onSelect 
                    ? `SGPModal._handleSelect(${u.id}, '${escHtml(u.nombres)}', '${escHtml(u.apellidos)}', '${u.iniciales}', '${u.rol_id}', '${escHtml(u.cedula)}', '${escHtml(u.departamento)}')`
                    : `SGPModal.verUsuario(${u.id})`;
                
                // Construir línea de meta contextual según rol
                const isPasante = parseInt(u.rol_id) === 3;
                let metaParts = [];
                if (u.cedula) metaParts.push(`<i class="ti ti-id" style="font-size:0.7rem;opacity:0.7;"></i> ${escHtml(u.cedula)}`);
                if (u.departamento && u.departamento !== 'Sin asignar') {
                    metaParts.push(`<i class="ti ti-building" style="font-size:0.7rem;opacity:0.7;"></i> ${escHtml(u.departamento)}`);
                }
                if (isPasante && u.tutor_nombre) {
                    metaParts.push(`<i class="ti ti-user-check" style="font-size:0.7rem;color:#10b981;"></i> <span style="color:#059669;font-weight:600;">${escHtml(u.tutor_nombre)}</span>`);
                } else if (isPasante && u.institucion) {
                    metaParts.push(`<i class="ti ti-school" style="font-size:0.7rem;opacity:0.7;"></i> ${escHtml(u.institucion)}`);
                } else if (isPasante && !u.tutor_nombre) {
                    metaParts.push(`<span style="color:#f59e0b;font-size:0.7rem;font-weight:600;"><i class="ti ti-alert-triangle" style="font-size:0.65rem;"></i> Sin tutor asignado</span>`);
                }

                return `
                    <div class="sgp-result-item sgp-anim-item" style="animation-delay:${i * 50}ms" onclick="${clickAction}">
                        <div class="sgp-result-avatar" style="background:${AVATAR_COLORS[u.rol_id] || AVATAR_COLORS[3]}">${u.iniciales}</div>
                        <div class="sgp-result-info" style="flex:1;min-width:0;">
                            <div class="sgp-result-name">${escHtml(u.nombres)} ${escHtml(u.apellidos)}</div>
                            <div class="sgp-result-meta" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">${metaParts.join(' <span style="opacity:0.3">·</span> ')}</div>
                        </div>
                        <span class="sgp-result-badge ${BADGE_CLASSES[u.rol_id] || ''}">${escHtml(u.rol_nombre)}</span>
                    </div>
                `;
            }).join('');
        } catch (e) {
            loadingEl.classList.remove('active');
            resultsEl.innerHTML = '<div class="sgp-empty-state"><i class="ti ti-alert-circle"></i><p>Error en la búsqueda</p></div>';
        }
    }

    // ─── View User Modal ───
    async function verUsuario(userId) {
        init();

        // Close search modal if open
        const buscarEl = document.getElementById('sgpModalBuscar');
        if (buscarEl) buscarEl.classList.remove('active');

        const verEl = document.getElementById('sgpModalVer');
        const bodyEl = document.getElementById('sgpVerBody');
        const actionsEl = document.getElementById('sgpVerActions');

        // Show with skeleton
        verEl.classList.add('active');
        bodyEl.innerHTML = buildSkeleton();
        actionsEl.innerHTML = '';

        // Check cache
        let data = getCached('user_' + userId);
        if (!data) {
            try {
                const resp = await fetch(URLROOT + '/users/verUniversal/' + userId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await resp.json();
                if (!json.success) {
                    bodyEl.innerHTML = `<div class="sgp-empty-state"><i class="ti ti-alert-circle"></i><p>${escHtml(json.message)}</p></div>`;
                    return;
                }
                data = json.data;
                setCache('user_' + userId, data);
            } catch (e) {
                bodyEl.innerHTML = '<div class="sgp-empty-state"><i class="ti ti-wifi-off"></i><p>Error de conexión</p></div>';
                return;
            }
        }

        // Render
        renderProfile(data, bodyEl, actionsEl);
    }

    // ─── Render Profile ───
    function renderProfile(d, bodyEl, actionsEl) {
        const avatarGradient = AVATAR_COLORS[d.rol_id] || AVATAR_COLORS[3];
        const badgeClass = BADGE_CLASSES[d.rol_id] || '';
        const rolLabels = { 1: 'Administrador', 2: 'Tutor Empresarial', 3: 'Pasante' };
        const estadoColor = d.estado === 'activo' ? '#059669' : '#dc2626';

        let hoursHtml = '';
        if (d.es_pasante) {
            const circumference = 2 * Math.PI * 26;
            const offset = circumference - (d.porcentaje_horas / 100) * circumference;

            // Calcular días hábiles restantes en el calendario
            let diasRestCal = 0;
            if (d.fecha_fin) {
                const parts = d.fecha_fin.split('/');
                if (parts.length === 3) {
                    const finDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
                    const cur = new Date(); cur.setHours(0,0,0,0);
                    while (cur <= finDate) {
                        const dow = cur.getDay();
                        if (dow !== 0 && dow !== 6) diasRestCal++;
                        cur.setDate(cur.getDate() + 1);
                    }
                }
            }
            const horasRest   = Math.max(0, d.horas_requeridas - d.horas_acumuladas);
            const puedeComplet = (diasRestCal * 8) >= horasRest;
            const periodoActivo = d.fecha_fin && diasRestCal > 0;

            // Color del anillo según riesgo
            const ringColor = (!puedeComplet && periodoActivo) ? '#ef4444'
                : d.porcentaje_horas >= 80 ? '#2563eb'
                : d.porcentaje_horas >= 50 ? '#f59e0b' : '#ef4444';

            // Banner de alerta
            let alertBanner = '';
            if (periodoActivo && !puedeComplet) {
                const diasLabel = diasRestCal === 1 ? '1 día hábil' : `${diasRestCal} días hábiles`;
                alertBanner = `
                <div style="margin-top:8px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;
                             padding:6px 10px;font-size:.72rem;color:#b91c1c;font-weight:700;
                             display:flex;align-items:center;gap:6px;">
                    <i class="ti ti-alert-triangle" style="font-size:.9rem;flex-shrink:0;"></i>
                    <span>En riesgo — faltan ${horasRest}h pero solo quedan ${diasLabel} (${diasRestCal * 8}h posibles)</span>
                </div>`;
            } else if (!periodoActivo && horasRest > 0) {
                alertBanner = `
                <div style="margin-top:8px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;
                             padding:6px 10px;font-size:.72rem;color:#a16207;font-weight:700;
                             display:flex;align-items:center;gap:6px;">
                    <i class="ti ti-clock-exclamation" style="font-size:.9rem;flex-shrink:0;"></i>
                    <span>Período finalizado — ${horasRest}h sin completar</span>
                </div>`;
            }

            hoursHtml = `
            <div class="sgp-hours-ring sgp-anim-item">
                <svg class="sgp-hours-svg" viewBox="0 0 64 64">
                    <circle cx="32" cy="32" r="26" fill="none" stroke="#e2e8f0" stroke-width="5"/>
                    <circle cx="32" cy="32" r="26" fill="none" stroke="${ringColor}" stroke-width="5"
                        stroke-linecap="round" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"
                        transform="rotate(-90 32 32)" style="transition:stroke-dashoffset 1s"/>
                    <text x="32" y="36" text-anchor="middle" font-size="11" font-weight="800" fill="#162660">${d.porcentaje_horas}%</text>
                </svg>
                <div class="sgp-hours-info" style="flex:1;min-width:0;">
                    <div class="sgp-hours-percent">${d.horas_acumuladas} / ${d.horas_requeridas} hrs</div>
                    <div class="sgp-hours-detail">Horas de pasantía acumuladas</div>
                    ${alertBanner}
                </div>
            </div>`;
        }

        let bentoItems = [
            { label: 'Cédula', value: escHtml(d.cedula), icon: 'id-badge' },
            { label: 'Estado', value: `<span style="color:${estadoColor};font-weight:700">${escHtml(d.estado)}</span>`, icon: 'circle-check' },
            { label: 'Departamento', value: escHtml(d.departamento), icon: 'building' },
            { label: 'Teléfono', value: escHtml(d.telefono || 'No registrado'), icon: 'phone' },
        ];

        if (d.es_pasante) {
            let instHtml = escHtml(d.institucion || 'No registrada');
            if (d.inst_rep_nombre) {
                instHtml += `<br><span style="font-size:0.7rem; color:#64748b; font-weight:500;"><i class="ti ti-user" style="margin-right:2px;"></i>Rep: ${escHtml(d.inst_rep_nombre)}</span>`;
            }
            
            bentoItems.push(
                { label: 'Tutor Asignado', value: escHtml(d.tutor_nombre || 'Sin asignar'), icon: 'user-star' },
                { label: 'Institución', value: instHtml, icon: 'school' },
                { label: 'Inicio Pasantía', value: d.fecha_inicio || 'Sin definir', icon: 'calendar' },
                { label: 'Fin Pasantía', value: d.fecha_fin || 'Sin definir', icon: 'calendar-due' }
            );
        } else {
            bentoItems.push(
                { label: 'Correo', value: escHtml(d.correo || 'No registrado'), icon: 'mail' },
                // [FIX-XS-2] d.cargo es texto libre editado por el propio Tutor en su perfil,
                // sin restricción de caracteres server-side — antes se insertaba sin escapar.
                { label: 'Cargo', value: escHtml(d.cargo || 'No registrado'), icon: 'briefcase' }
            );
        }

        bodyEl.innerHTML = `
            <div class="sgp-view-profile sgp-anim-item">
                <div class="sgp-view-avatar" style="background:${avatarGradient}">${d.iniciales}</div>
                <div class="sgp-view-name">${escHtml(d.nombres)} ${escHtml(d.apellidos)}</div>
                <span class="sgp-view-role ${badgeClass}">${rolLabels[d.rol_id] || d.rol_nombre}</span>
            </div>
            ${hoursHtml}
            <div class="sgp-bento-grid">
                ${bentoItems.map((item, i) => `
                    <div class="sgp-bento-item sgp-anim-item" style="animation-delay:${(i + 2) * 50}ms">
                        <div class="sgp-bento-label"><i class="ti ti-${item.icon}" style="margin-right:4px"></i>${item.label}</div>
                        <div class="sgp-bento-value">${item.value}</div>
                    </div>
                `).join('')}
            </div>`;

        // Actions
        let actionsHtml = '';
        if (d.tiene_pin) {
            actionsHtml += `<button class="sgp-btn-action sgp-btn-reset-pin" onclick="SGPModal.resetPin(${d.id}, '${escHtml(d.nombres)} ${escHtml(d.apellidos)}')">
                <i class="ti ti-refresh"></i> Restablecer PIN
            </button>`;
        }
        if (d.es_pasante) {
            actionsHtml += `<a href="${URLROOT}/users/exportPdf/${d.id}" target="_blank" class="sgp-btn-action btn btn-outline-danger fw-bold d-flex align-items-center justify-content-center gap-2" style="text-decoration:none; border: 1.5px solid currentColor !important;">
                <i class="ti ti-file-type-pdf fs-5"></i> Descargar PDF
            </a>`;
            if (typeof window.abrirModalEditarPasante === 'function') {
                actionsHtml += `<button class="sgp-btn-action" style="background:#eff6ff;color:#2563eb;border:1.5px solid #bfdbfe;" onclick="SGPModal.cerrar('ver'); window.abrirModalEditarPasante(${d.id})">
                    <i class="ti ti-edit"></i> Editar Datos
                </button>`;
            }
        }
        actionsEl.innerHTML = actionsHtml;
    }

    // ─── Skeleton ───
    function buildSkeleton() {
        return `
            <div style="text-align:center;margin-bottom:16px">
                <div class="sgp-skeleton sgp-skeleton-avatar"></div>
                <div class="sgp-skeleton sgp-skeleton-line w60"></div>
                <div class="sgp-skeleton sgp-skeleton-line w40"></div>
            </div>
            <div class="sgp-bento-grid">
                ${Array(4).fill('<div class="sgp-bento-item"><div class="sgp-skeleton sgp-skeleton-block"></div></div>').join('')}
            </div>`;
    }

    // ─── Reset PIN ───
    async function resetPin(userId, nombre) {
        const result = await Swal.fire({
            title: '<i class="ti ti-lock"></i> Restablecer PIN',
            html: `¿Restablecer el PIN de asistencia de <strong>${nombre}</strong>?<br><small>Se generará un nuevo PIN aleatorio de 4 dígitos.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-check"></i> Sí, restablecer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#f59e0b'
        });

        if (!result.isConfirmed) return;

        try {
            const formData = new FormData();
            formData.append('pasante_id', userId);

            const resp = await fetch(URLROOT + '/pasantes/resetPin', { method: 'POST', body: formData });
            const json = await resp.json();

            if (json.success) {
                // Invalidate cache
                cache.delete('user_' + userId);

                Swal.fire({
                    title: '<i class="ti ti-circle-check"></i> PIN Restablecido',
                    html: `El nuevo PIN para <strong>${nombre}</strong> es:<br><br>
                           <div style="font-size:2.5rem;font-weight:900;letter-spacing:8px;color:#162660;
                                       background:#f1f5f9;border-radius:12px;padding:16px;display:inline-block">
                                ${json.nuevo_pin}
                           </div><br><br>
                           <small style="color:#64748b">Comunique este PIN al pasante de forma segura.</small>`,
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#059669'
                });
            } else {
                NotificationService.error(json.message || 'Error al restablecer PIN');
            }
        } catch (e) {
            NotificationService.error('Error de conexión');
        }
    }

    // ─── Utility ───
    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ─── Public API ───
    window.SGPModal = {
        _onSelect: null,
        buscar: function (options) {
            init();
            activeRoleFilter = (options && options.rol) ? options.rol : 0;
            this._onSelect = (options && options.onSelect) ? options.onSelect : null;

            const el = document.getElementById('sgpModalBuscar');
            el.classList.add('active');
            // Update title based on filter
            const titleEl = el.querySelector('.sgp-modal-header h3');
            if (titleEl) {
                const label = this._onSelect ? 'Seleccionar Usuario' : (activeRoleFilter === 3 ? 'Buscar Pasante' : 'Búsqueda Rápida');
                const icon = this._onSelect ? 'ti-pointer' : (activeRoleFilter === 3 ? 'ti-user-search' : 'ti-search');
                titleEl.innerHTML = `<i class="ti ${icon}" style="margin-right:8px"></i>${label}`;
            }
            const subtitleEl = el.querySelector('.sgp-modal-header p');
            if (subtitleEl) {
                subtitleEl.textContent = this._onSelect 
                    ? 'Haz clic en el resultado para seleccionar'
                    : (activeRoleFilter === 3
                        ? 'Buscar por nombre o cédula del pasante'
                        : 'Buscar por nombre o número de cédula');
            }
            setTimeout(() => {
                const input = document.getElementById('sgpSearchInput');
                if (input) { input.value = ''; input.focus(); }
                document.getElementById('sgpSearchResults').innerHTML =
                    '<div class="sgp-empty-state"><i class="ti ti-users-group"></i><p>Escriba al menos 2 caracteres para buscar</p></div>';
            }, 100);
        },
        _handleSelect: function(id, nombres, apellidos, iniciales, rol_id, cedula, departamento) {
            if (this._onSelect) {
                this._onSelect({ id, nombres, apellidos, iniciales, rol_id, cedula, departamento });
                this.cerrar('buscar');
            }
        },
        verUsuario: verUsuario,
        resetPin: resetPin,
        cerrar: function (tipo) {
            const id = tipo === 'buscar' ? 'sgpModalBuscar' : 'sgpModalVer';
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
            if (tipo === 'buscar') this._onSelect = null;
        }
    };
})();
