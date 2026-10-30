<?php
/**
 * Header - Barra Superior del Sistema
 * 
 * MIDDLEWARE "LA JAULA":
 * Si el usuario tiene requiere_cambio_clave = 1, NO se renderiza el header.
 * Esto complementa la supresión del sidebar para "enjaular" al usuario en el wizard.
 */

// ============================================
// MIDDLEWARE: Verificar Estado del Usuario
// ============================================
if (Session::get('requiere_cambio_clave') == 1) {
    // Usuario está "enjaulado" → NO renderizar header
    return;
}
?>

<?php
/**
 * NOTA: URLROOT ya está definido en main_layout.php
 * No es necesario redefinirlo aquí para evitar errores de duplicación
 */
?>

<style>
/* ========================================================
   NOTIFICACIONES DROPDOWN - BENTO UI PREMIUM
   ======================================================== */
.dropdown-menu-notifications {
    width: 380px !important;
    max-height: 450px;
    overflow-y: auto;
    overflow-x: hidden !important;
    padding: 0;
    border-radius: 24px !important;
    box-shadow: 0 20px 50px rgba(0,0,0,0.1) !important;
    background-color: #ffffff;
    border: 1px solid #f1f5f9;
    margin-top: 12px !important;
}

/* Scroll elegante */
.dropdown-menu-notifications::-webkit-scrollbar {
    width: 6px;
}
.dropdown-menu-notifications::-webkit-scrollbar-track {
    background: transparent;
}
.dropdown-menu-notifications::-webkit-scrollbar-thumb {
    background-color: #cbd5e1; 
    border-radius: 20px;
}

/* ── Botón campana ───────────────────────────────────────── */
.notif-btn {
    position: relative;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px 8px;
    border-radius: 10px;
    transition: background .15s;
    display: flex;
    align-items: center;
    color: #475569;
}
.notif-btn:hover { background: #f1f5f9; }
.notif-btn.active { background: #e2e8f0; }

/* Badge Contador — oculto por defecto, visible con .badge-visible */
.notif-badge {
    display: none;                  /* ← empieza oculto */
    position: absolute;
    top: 2px;
    right: 2px;
    min-width: 18px;
    height: 18px;
    background: #ef4444;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 800;
    border-radius: 20px;
    border: 2px solid #fff;
    line-height: 1;
    padding: 0 4px;
    align-items: center;
    justify-content: center;
    transform: translate(40%, -40%);
    pointer-events: none;
    animation: none;
}
.notif-badge.badge-visible {
    display: flex;                  /* ← aparece solo cuando hay notifs */
}

/* Animación campana — wiggle periódico (CSS-only, sin JS) */
/*
 * La campana se mueve 0.8s y luego descansa 4.2s — ciclo de 5s infinito.
 * Funciona con o sin badge. No requiere setInterval en JS.
 */
@keyframes bell-loop {
    /* Idle 0% → 0%  (el ciclo empieza quieto) */
    0%                       { transform: rotate(0deg);   }
    /* Wiggle rápido: ocupa el primer 16% del ciclo (≈ 0.8s de 5s) */
    2%                       { transform: rotate(18deg);  }
    5%                       { transform: rotate(-14deg); }
    8%                       { transform: rotate(10deg);  }
    11%                      { transform: rotate(-7deg);  }
    14%                      { transform: rotate(4deg);   }
    16%                      { transform: rotate(0deg);   }
    /* Idle del 16% al 100% (≈ 4.2s de descanso) */
    100%                     { transform: rotate(0deg);   }
}

/* Solo se activa la animación cuando hay notificaciones (.bell-ringing) */
.notif-btn.bell-ringing > i {
    display: inline-block;
    animation: bell-loop 5s ease-in-out infinite;
    transform-origin: top center;
}

/* Pausa la animación cuando el dropdown está abierto (UX: no distraer) */
.notif-btn.active > i {
    animation-play-state: paused;
}

/* Alias legacy */
.has-notifications i.ti-bell {
    display: inline-block;
    animation: bell-loop 5s ease-in-out infinite;
    transform-origin: top center;
}


/* Header del Dropdown */
.notif-header {
    background: #f8fafc;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 24px 24px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
}
.notif-header h6 { margin: 0; font-weight: 800; color: #1e293b; font-size: 1rem; }

/* Items Contextuales Bento */
.notif-item {
    position: relative;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
    transition: all 0.2s ease;
    display: flex;
    gap: 16px;
    align-items: flex-start;
    text-decoration: none !important;
}
.notif-item:hover { background-color: #f8fafc; }
.notif-item:last-child { border-bottom: none; border-radius: 0 0 24px 24px; }

/* Contenedor del ícono */
.notif-icon-box {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
}
.notif-icon-primary { background: #eff6ff; color: #3b82f6; }
.notif-icon-success { background: #ecfdf5; color: #10b981; }
.notif-icon-warning { background: #fffbeb; color: #f59e0b; }
.notif-icon-danger  { background: #fef2f2; color: #ef4444; }

.notif-content { flex-grow: 1; min-width: 0; }
.notif-title { font-weight: 700; color: #334155; font-size: 0.9rem; margin-bottom: 4px; }
.notif-message { font-size: 0.8rem; color: #64748b; margin-bottom: 6px; line-height: 1.4; display: block; }
.notif-time { font-size: 0.75rem; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 4px; }

/* ── Mobile Header: ocultar cintillo institucional, simplificar ── */
@media (max-width: 991px) {
    /* Ocultar el strip de logos institucionales */
    .institutional-strip-center { display: none !important; }

    /* Nombre de usuario oculto — solo avatar en mobile */
    .user-profile-link .d-block.ml-2 { display: none !important; }
    .user-profile-link .ti-chevron-down { display: none !important; }

    /* Header más compacto */
    .main-header {
        padding: 0 12px !important;
        min-height: var(--header-height-mobile) !important;
    }
}

/* ── Mobile Notifications Dropdown: panel full-width desde topbar ── */
@media (max-width: 767px) {
    .dropdown-menu-notifications {
        position: fixed !important;
        top: 60px !important;
        left: 0 !important;
        right: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        max-height: 70vh !important;
        border-radius: 0 0 20px 20px !important;
        margin-top: 0 !important;
        box-shadow: 0 12px 40px rgba(15,23,42,0.18) !important;
        border-left: none !important;
        border-right: none !important;
        border-top: none !important;
        overflow-y: auto !important;
    }
    .dropdown-menu-notifications .notif-header {
        border-radius: 0 !important;
    }
}
</style>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    
    <!-- ZONA IZQUIERDA: Hamburguesa + Logo SGP -->
    <ul class="navbar-nav align-items-center" style="gap: 12px;">
        <!-- Toggle Desktop - Colapsar/Expandir Sidebar (Desktop Only) -->
        <li class="nav-item d-none d-lg-block">
            <button id="sidebarCollapseToggle" class="btn-toggle" aria-label="Colapsar/Expandir sidebar" title="Colapsar sidebar">
                <i class="ti ti-menu-2"></i>
            </button>
        </li>
        
        <!-- Hamburguesa Mobile (Mobile Only) - REMOVIDO POR EXIGENCIA UX BENTO -->
        <li class="nav-item d-none">
            <button id="sidebarToggle" class="btn-toggle" aria-label="Toggle sidebar">
                <i class="ti ti-menu-2"></i>
            </button>
        </li>
        
        <!-- Logo SGP (Siempre visible) -->
        <li class="nav-item">
            <?php
            $user_role_id = (int)(Session::get('role_id') ?? 0);
            $dashboardUrl = URLROOT . '/dashboard';
            if ($user_role_id === 0) $dashboardUrl = URLROOT . '/superadmin';
            elseif ($user_role_id === 1) $dashboardUrl = URLROOT . '/admin';
            elseif ($user_role_id === 2) $dashboardUrl = URLROOT . '/tutor';
            elseif ($user_role_id === 3) $dashboardUrl = URLROOT . '/pasante/dashboard';
            ?>
            <a href="<?= $dashboardUrl ?>" class="brand-link d-flex align-items-center text-decoration-none" title="Ir al Inicio">
                <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" 
                     style="height: 40px; width: auto; object-fit: contain;">
            </a>
        </li>
    </ul>
    
    <!-- ZONA CENTRAL: Cintillo Institucional Centrado (Ambos logos juntos) -->
    <div class="institutional-strip-center">
        <!-- Logo Instituto de Salud -->
        <img src="<?= URLROOT ?>/img/cintillo-salud.png" 
             alt="Instituto de Salud Pública" 
             class="institutional-logo"
             style="height: 50px; width: auto; max-width: 350px; object-fit: contain;">
        
        <!-- Separador visual -->
        <div class="institutional-separator"></div>
        
        <!-- Logo Gobernación -->
        <img src="<?= URLROOT ?>/img/gobe.png" 
             alt="Gobernación de Bolívar" 
             class="institutional-logo"
             style="height: 50px; width: auto; max-width: 350px; object-fit: contain;">
    </div>

    <!-- ZONA DERECHA: Notificaciones + Perfil -->
    <ul class="navbar-nav ml-auto align-items-center" style="gap: 12px;">
        
        <!-- Control de Escala / Zoom (80% por defecto en desktop) -->
        <li class="nav-item d-none d-md-flex align-items-center">
            <div class="sgp-zoom-pill" title="Escala de visualización: Haz clic en el porcentaje para alternar entre 80% (recomendado) y 100%">
                <button type="button" class="btn-zoom-adjust" onclick="changeSgpZoom(-0.05)" title="Reducir escala (Zoom -)">
                    <i class="ti ti-minus"></i>
                </button>
                <button type="button" id="sgpZoomIndicator" class="btn-zoom-label" onclick="toggleSgpDefaultZoom()" title="Haz clic para alternar entre 80% y 100%">
                    80%
                </button>
                <button type="button" class="btn-zoom-adjust" onclick="changeSgpZoom(0.05)" title="Aumentar escala (Zoom +)">
                    <i class="ti ti-plus"></i>
                </button>
            </div>
        </li>

        <!-- Toggle Modo Oscuro (Fase 5) -->
        <li class="nav-item">
            <button id="themeToggleBtn" class="theme-toggle-btn notif-btn" aria-label="Cambiar tema claro / oscuro" title="Cambiar tema claro / oscuro" onclick="toggleSgpTheme()">
                <i class="ti ti-moon" id="themeToggleIcon" style="font-size: 21px;"></i>
            </button>
        </li>

        <script>
        function applySgpZoom(val) {
            var num = parseFloat(val);
            if (isNaN(num)) num = 0.90;
            num = Math.round(num * 100) / 100;
            num = Math.max(0.60, Math.min(1.20, num));
            document.documentElement.style.zoom = num.toString();
            try { localStorage.setItem('sgp_zoom', num.toString()); } catch(e){}
            var ind = document.getElementById('sgpZoomIndicator');
            if (ind) {
                ind.textContent = Math.round(num * 100) + '%';
            }
        }
        function toggleSgpDefaultZoom() {
            var cur = parseFloat(localStorage.getItem('sgp_zoom') || (document.documentElement.style.zoom || '0.90'));
            var next = (Math.round(cur * 100) === 90) ? 1.00 : 0.90;
            applySgpZoom(next);
        }
        function changeSgpZoom(delta) {
            var cur = parseFloat(localStorage.getItem('sgp_zoom') || (document.documentElement.style.zoom || '0.90'));
            applySgpZoom(cur + delta);
        }
        (function() {
            var isDesktop = window.innerWidth >= 768;
            var saved = localStorage.getItem('sgp_zoom');
            if (!saved || saved === '0.80' || saved === '0.8' || saved === '1') {
                saved = isDesktop ? '0.90' : '1';
            }
            if (isDesktop || saved !== '1') {
                applySgpZoom(saved);
            }
        })();

        function applySgpTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.classList.toggle('dark', theme === 'dark');
            if (document.body) document.body.classList.toggle('dark-mode', theme === 'dark');
            try { localStorage.setItem('sgp_theme', theme); } catch(e){}
            var icon = document.getElementById('themeToggleIcon');
            if (icon) {
                icon.className = theme === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
                icon.style.color = theme === 'dark' ? '#fbbf24' : '#475569';
            }
        }
        function toggleSgpTheme() {
            var cur = localStorage.getItem('sgp_theme') || (document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
            var next = cur === 'dark' ? 'light' : 'dark';
            applySgpTheme(next);
        }
        (function() {
            var saved = localStorage.getItem('sgp_theme') || 'light';
            applySgpTheme(saved);
        })();

        function applySgpPalette(primary, accent, name) {
            if (!primary || !accent) return;
            document.documentElement.style.setProperty('--color-primary', primary);
            document.documentElement.style.setProperty('--gradient-mid', primary);
            document.documentElement.style.setProperty('--gradient-end', primary);
            document.documentElement.style.setProperty('--color-accent-brand', accent);
            document.documentElement.style.setProperty('--color-info', accent);
            document.documentElement.style.setProperty('--icon-active', accent);
            document.documentElement.style.setProperty('--text-active', accent);
            document.documentElement.style.setProperty('--sidebar-accent', accent);
            document.documentElement.style.setProperty('--gradient-start', accent);

            var styleEl = document.getElementById('sgp-palette-dynamic-override');
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = 'sgp-palette-dynamic-override';
                document.head.appendChild(styleEl);
            }
            styleEl.textContent = `
                :root {
                    --color-primary: ${primary} !important;
                    --color-accent-brand: ${accent} !important;
                    --color-mid: ${primary} !important;
                    --gradient-start: ${accent} !important;
                    --gradient-mid: ${primary} !important;
                    --gradient-end: ${primary} !important;
                }
                .main-sidebar {
                    background: linear-gradient(180deg, ${accent} 0%, ${primary} 45%, ${primary} 100%) !important;
                }
                html:not([data-theme="dark"]) .admin-banner,
                html:not([data-theme="dark"]) .pasantes-banner,
                html:not([data-theme="dark"]) .superadmin-banner,
                html:not([data-theme="dark"]) .tutor-banner,
                html:not([data-theme="dark"]) .users-banner,
                html:not([data-theme="dark"]) .dashboard-banner,
                html:not([data-theme="dark"]) .module-banner,
                html:not([data-theme="dark"]) .asig-banner,
                html:not([data-theme="dark"]) .ev-banner,
                html:not([data-theme="dark"]) .ev-hero,
                html:not([data-theme="dark"]) .man-banner,
                html:not([data-theme="dark"]) .pb-hero,
                html:not([data-theme="dark"]) .as-hero,
                html:not([data-theme="dark"]) .act-hero,
                html:not([data-theme="dark"]) .con-hero,
                html:not([data-theme="dark"]) .an-hero,
                html:not([data-theme="dark"]) .ex-hero,
                html:not([data-theme="dark"]) .td-hero,
                html:not([data-theme="dark"]) .ta-hero,
                html:not([data-theme="dark"]) .cfg-hero,
                html:not([data-theme="dark"]) .mp-banner,
                html:not([data-theme="dark"]) .punt-banner,
                html:not([data-theme="dark"]) .tpp-banner,
                html:not([data-theme="dark"]) .perm-banner,
                html:not([data-theme="dark"]) .eg-banner,
                html:not([data-theme="dark"]) .inst-banner,
                html:not([data-theme="dark"]) .pc-banner,
                html:not([data-theme="dark"]) .sc-banner,
                html:not([data-theme="dark"]) .backup-banner,
                html:not([data-theme="dark"]) .welcome-banner,
                html:not([data-theme="dark"]) .p-banner,
                html:not([data-theme="dark"]) .periodos-banner,
                html:not([data-theme="dark"]) .modal-head,
                html:not([data-theme="dark"]) .act-modal-header,
                html:not([data-theme="dark"]) .con-doc-header,
                .pb-hero, .cfg-hero, .act-hero, .ex-hero, .con-hero, .ev-hero, .as-hero, .an-hero, .td-hero, .ta-hero, .mp-banner, .punt-banner, .tpp-banner, .perm-banner, .eg-banner, .inst-banner, .pc-banner, .sc-banner {
                    background: linear-gradient(135deg, ${primary} 0%, ${primary} 45%, ${accent} 100%) !important;
                }
                .btn-primary, .btn-brand, a.btn-primary, button.btn-primary,
                .act-btn-save, .con-btn-primary, .btn-cfg-save, .cfg-btn-save,
                .bca-btn-primary, .btn-aplicar-paleta, .pb-btn-constancia,
                .btn-submit, .btn-ver-full, .prev-more, #puntScrollTop {
                    background: linear-gradient(135deg, ${primary}, ${accent}) !important;
                    border-color: ${accent} !important;
                    color: #ffffff !important;
                }
                .btn-primary:hover, .btn-brand:hover, a.btn-primary:hover, button.btn-primary:hover,
                .act-btn-save:hover, .con-btn-primary:hover, .btn-cfg-save:hover, .pb-btn-constancia:hover,
                .btn-submit:hover, .btn-ver-full:hover, .prev-more:hover {
                    filter: brightness(1.1) !important;
                    box-shadow: 0 4px 14px ${accent}55 !important;
                }
                .pb-link-asistencia {
                    color: ${accent} !important;
                }
                .pb-btn-actividad {
                    color: ${accent} !important;
                    border-color: ${accent}55 !important;
                }
                .pb-btn-actividad:hover {
                    background: ${accent}15 !important;
                }
                .pb-card-title i, .pb-info-pill i {
                    color: ${accent} !important;
                }
                .pb-kpi.c-blue {
                    border-top-color: ${accent} !important;
                }
                .pb-kpi.c-blue .pb-kpi-val {
                    color: ${accent} !important;
                }
                .pb-kpi.c-blue .pb-kpi-icon {
                    background: ${accent}18 !important;
                    color: ${accent} !important;
                }
                .profile-avatar:not(img) {
                    background: linear-gradient(135deg, ${primary} 0%, ${accent} 100%) !important;
                    box-shadow: 0 2px 6px ${primary}44 !important;
                }
                .dropdown-header {
                    background: linear-gradient(135deg, ${primary} 0%, ${accent} 100%) !important;
                }
                .dock-fab {
                    background: linear-gradient(135deg, ${primary} 0%, ${primary} 45%, ${accent} 100%) !important;
                    box-shadow: 0 6px 18px ${primary}66 !important;
                }
                .dock-fab i {
                    color: #ffffff !important;
                }
                .dock-item.active {
                    color: ${accent} !important;
                }
                .dock-item.active i {
                    color: ${accent} !important;
                    background: ${accent}22 !important;
                }
                .dock-item.active span {
                    color: ${accent} !important;
                }
                .dock-item.dock-fab-item span {
                    color: ${accent} !important;
                }
                .dock-sheet-item.active .dock-sheet-icon,
                .dock-sheet-item:hover .dock-sheet-icon {
                    background: ${accent}22 !important;
                    color: ${accent} !important;
                }
                .dock-indicator {
                    background: linear-gradient(135deg, ${primary}, ${accent}) !important;
                    box-shadow: 0 0 8px ${accent}bb !important;
                }
                .main-sidebar .nav-item>a.active, .main-sidebar .nav-link.active, .main-sidebar .nav-item.active>a {
                    background: rgba(255, 255, 255, 0.16) !important;
                }
                .main-sidebar .nav-item>a.active i, .main-sidebar .nav-link.active i {
                    color: #ffffff !important;
                    filter: drop-shadow(0 0 6px ${accent}) !important;
                }
                .palette-card.active {
                    border-color: ${accent} !important;
                    box-shadow: 0 0 0 3px ${accent}33 !important;
                }
            `;

            try {
                localStorage.setItem('sgp_user_palette', JSON.stringify({ primary: primary, accent: accent, name: name || 'Custom' }));
            } catch(e){}
        }

        function restoreSgpPalette() {
            try {
                var p = JSON.parse(localStorage.getItem('sgp_user_palette'));
                if (p && p.primary && p.accent) {
                    applySgpPalette(p.primary, p.accent, p.name);
                } else {
                    var el = document.getElementById('sgp-palette-dynamic-override');
                    if (el) el.remove();
                }
            } catch(e){}
        }
        restoreSgpPalette();

        document.addEventListener('pjax:complete', function() {
            var saved = localStorage.getItem('sgp_theme') || 'light';
            applySgpTheme(saved);
            restoreSgpPalette();
        });
        </script>

        <!-- Notificaciones -->
        <li class="nav-item dropdown">
            <div class="notif-wrapper" id="notification-wrapper">
                <button id="bell-btn" class="notif-btn">
                    <i class="ti ti-bell" style="font-size: 22px;"></i>
                    <span class="notif-badge unread-count-badge" id="notif-badge-el"></span>
                </button>
                <!-- Pintado instantáneo desde caché para evitar el flash del "0" en PJAX -->
                <script>
                (function(){
                    var cached = sessionStorage.getItem('sgp_notif_last_count');
                    if (cached !== null && parseInt(cached, 10) > 0) {
                        var el = document.getElementById('notif-badge-el');
                        if (el) {
                            el.textContent = parseInt(cached, 10) > 99 ? '99+' : cached;
                            el.classList.add('badge-visible');
                            var btn = document.getElementById('bell-btn');
                            if (btn) btn.classList.add('bell-ringing');
                        }
                    }
                })();
                </script>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right dropdown-menu-notifications custom-scrollbar" id="notificationsDropdown">
                <!-- Cabecera Estática -->
                <div class="notif-header">
                    <h6>Notificaciones</h6>
                    <a href="#" id="markAllReadBtn" class="text-primary text-decoration-none" title="Marcar todas como leídas" style="font-size: 0.85rem; font-weight: 700;">
                        <i class="ti ti-checks"></i> Leídas
                    </a>
                </div>
                <!-- Lista Scrolleable -->
                <div id="notificationList">
                    <div class="notif-item flex-column text-center align-items-center justify-content-center p-4">
                        <i class="ti ti-bell-off" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 8px;"></i>
                        <span class="text-muted font-weight-bold">Estás al día</span>
                        <small class="text-muted">No tienes notificaciones nuevas</small>
                    </div>
                </div>
            </div>
        </li>

        <!-- Soporte Técnico / Mesa de Ayuda -->
        <li class="nav-item">
            <button type="button" class="notif-btn" onclick="abrirModalSoporteGlobal()" title="Mesa de Ayuda / Soporte Técnico ISPEB" style="color: #64748b;">
                <i class="ti ti-headset" style="font-size: 21px;"></i>
            </button>
        </li>

        <!-- Perfil Usuario -->
        <li class="nav-item dropdown">
            <a class="nav-link d-flex align-items-center user-profile-link" data-toggle="dropdown" href="#" aria-label="Perfil de usuario">
                <?php
                $user_name   = Session::get('user_name') ?? Session::get('nombres') ?? 'Usuario';
                $_avatarFile = Session::get('user_avatar') ?? 'default.png';
                $_avatarPath = APPROOT . '/../public/img/avatars/' . $_avatarFile;
                $_hasAvatar  = ($_avatarFile !== 'default.png' && file_exists($_avatarPath));
                ?>
                <?php if ($_hasAvatar): ?>
                <img src="<?= URLROOT ?>/img/avatars/<?= htmlspecialchars($_avatarFile) ?>" alt="Avatar"
                     class="profile-avatar" style="object-fit:cover;padding:0;">
                <?php else: ?>
                <div class="profile-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                <?php endif; ?>
                <div class="d-block ml-2 text-left">
                    <span class="d-block font-weight-bold line-height-1" style="font-size: 0.9rem; color: #1e293b;">
                        <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>
                    </span>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        <?php
                        $rol_id = Session::get('role_id') ?? 0;
                        if ($rol_id == 0) {
                            echo 'Super Administrador';
                        } else {
                            echo ($rol_id == 1) ? 'Administrador' : (($rol_id == 2) ? 'Tutor' : 'Pasante');
                        }
                        ?>
                    </small>
                </div>
                <i class="ti ti-chevron-down ml-2" style="font-size: 0.8rem; color: #64748b;"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right profile-dropdown" style="min-width: 230px; padding: 6px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); border: 1px solid rgba(0,0,0,0.06);">
                <!-- Header del Dropdown -->
                <div class="dropdown-header d-flex align-items-center p-3 mb-2" style="border-radius: 12px; background: linear-gradient(135deg, var(--color-primary, #1e3a8a) 0%, var(--color-accent-brand, #2563eb) 100%) !important; color: #ffffff !important;">
                    <?php if ($_hasAvatar): ?>
                        <img src="<?= URLROOT ?>/img/avatars/<?= htmlspecialchars($_avatarFile) ?>" alt="Avatar"
                             class="profile-avatar mr-2" style="width: 40px; height: 40px; min-width: 40px; object-fit: cover; border: 2px solid rgba(255,255,255,0.85); border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                    <?php else: ?>
                        <div class="profile-avatar mr-2" style="width: 40px; height: 40px; min-width: 40px; background: rgba(255,255,255,0.25); border: 2px solid rgba(255,255,255,0.4); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ffffff;">
                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="text-left overflow-hidden" style="color: #ffffff;">
                        <div class="font-weight-bold text-truncate" style="color: #ffffff; font-size: 0.92rem;"><?= htmlspecialchars($user_name) ?></div>
                        <div class="small" style="color: rgba(255,255,255,0.85); font-size: 0.75rem; display: flex; align-items: center; gap: 4px;">
                            <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background-color: #22c55e;"></span> En línea
                        </div>
                    </div>
                </div>

                <a href="<?= URLROOT ?>/perfil/ver" class="dropdown-item">
                    <i class="ti ti-user mr-2 text-primary"></i> Mi Perfil
                </a>
                
                <div class="dropdown-divider"></div>
                
                <a href="javascript:void(0)" onclick="confirmLogout()" class="dropdown-item text-danger">
                    <i class="ti ti-logout mr-2"></i> Cerrar Sesión
                </a>
            </div>
        </li>
    </ul>
</nav>

<script>
/**
 * Confirmar Logout con SweetAlert
 * 
 * PROPÓSITO:
 * Mostrar confirmación antes de cerrar sesión para evitar cierres accidentales.
 */
function confirmLogout() {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: '¿Estás seguro que deseas salir?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#162660',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            try { localStorage.setItem('sgp_theme', 'light'); } catch(e){}
            window.location.href = '<?= URLROOT ?>/auth/logout';
        }
    });
}

function abrirModalSoporteGlobal() {
    var modal = document.getElementById('modalSoporteGlobal');
    if (!modal) return;
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    modal.classList.add('active');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    var inputAsunto = document.getElementById('soporteAsunto');
    if (inputAsunto) setTimeout(function(){ inputAsunto.focus(); }, 100);
}

function cerrarModalSoporteGlobal() {
    var modal = document.getElementById('modalSoporteGlobal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var modal = document.getElementById('modalSoporteGlobal');
        if (modal && (modal.classList.contains('active') || modal.style.display === 'flex')) {
            cerrarModalSoporteGlobal();
        }
    }
});

function enviarTicketSoporteGlobal(e) {
    e.preventDefault();
    var btn = document.getElementById('btnEnviarSoporte');
    var asunto = document.getElementById('soporteAsunto').value.trim();
    var prioridad = document.getElementById('soportePrioridad').value;
    var descripcion = document.getElementById('soporteDescripcion').value.trim();

    if (!asunto || !descripcion) {
        Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Por favor complete el asunto y la descripción de la incidencia.' });
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;"></i> Enviando...';

    fetch('<?= URLROOT ?>/superAdmin/crearTicketSoporte', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ asunto: asunto, prioridad: prioridad, descripcion: descripcion })
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-send"></i> <span>Enviar Ticket</span>';
        if (res.success) {
            cerrarModalSoporteGlobal();
            document.getElementById('formSoporteGlobal').reset();
            Swal.fire({
                icon: 'success',
                title: 'Ticket Generado #' + (res.codigo || ''),
                html: '<p style="color:#475569;margin-top:8px;">' + res.message + '</p><div style="margin-top:12px;padding:10px;background:#f0fdf4;border-radius:10px;color:#16a34a;font-size:0.85rem;font-weight:600;"><i class="ti ti-mail-check"></i> Notificación enviada a Dirección de Telemática</div>',
                confirmButtonColor: '#162660'
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo generar el ticket.' });
        }
    })
    .catch(function(err){
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-send"></i> <span>Enviar Ticket</span>';
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo contactar al servidor.' });
    });
}
</script>

<!-- Modal Global de Soporte Técnico / HelpDesk -->
<div id="modalSoporteGlobal" class="sgp-modal-overlay modal" style="display:none;position:fixed;top:-20px;left:-20px;right:-20px;bottom:-20px;inset:-20px;width:calc(100% + 40px);height:calc(100% + 40px);background:rgba(15,23,42,0.65);z-index:99999999;align-items:center;justify-content:center;backdrop-filter:blur(6px);padding:16px;" onclick="if(event.target===this)cerrarModalSoporteGlobal()">
    <div class="sgp-modal-bento" style="background:#ffffff;border-radius:24px;width:100%;max-width:540px;box-shadow:0 25px 60px rgba(0,0,0,0.25);border:1px solid #e2e8f0;overflow:hidden;animation:bentoModalIn 0.3s cubic-bezier(0.16,1,0.3,1);">
        <div style="background:linear-gradient(135deg, #162660 0%, #1e3a8a 100%);padding:22px 26px;color:#ffffff;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                    <i class="ti ti-headset"></i>
                </div>
                <div>
                    <h3 style="margin:0;font-size:1.1rem;font-weight:800;color:#ffffff;">Mesa de Ayuda &bull; Soporte ISPEB</h3>
                    <p style="margin:2px 0 0;font-size:0.78rem;color:rgba(255,255,255,0.8);">Envío de incidencia técnica a Dirección de Telemática</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalSoporteGlobal()" style="background:none;border:none;color:#ffffff;font-size:1.2rem;cursor:pointer;opacity:0.8;transition:opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.8">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <form id="formSoporteGlobal" onsubmit="enviarTicketSoporteGlobal(event)" style="padding:24px 26px;">
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                    <i class="ti ti-message-2"></i> Asunto de la Incidencia
                </label>
                <input type="text" id="soporteAsunto" required placeholder="Ej: Inconsistencia en marcaje de asistencia / Error en evaluación" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.9rem;outline:none;font-weight:600;color:#1e293b;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="ti ti-flag"></i> Prioridad
                    </label>
                    <select id="soportePrioridad" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.9rem;outline:none;font-weight:600;color:#1e293b;background:#ffffff;">
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media (Normal)</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente (Bloqueante)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="ti ti-mail"></i> Destinatario
                    </label>
                    <input type="text" readonly value="sgp.ispeb@gmail.com" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:10px;font-size:0.85rem;color:#64748b;font-weight:600;">
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                    <i class="ti ti-file-text"></i> Descripción Detallada
                </label>
                <textarea id="soporteDescripcion" required rows="4" placeholder="Explique con el mayor detalle posible lo que sucede..." style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.9rem;outline:none;font-weight:500;color:#1e293b;resize:vertical;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" onclick="cerrarModalSoporteGlobal()" style="padding:10px 18px;border:1.5px solid #cbd5e1;border-radius:10px;background:#ffffff;color:#475569;font-weight:700;font-size:0.88rem;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" id="btnEnviarSoporte" style="padding:10px 22px;border:none;border-radius:10px;background:linear-gradient(135deg, #162660, #2563eb);color:#ffffff;font-weight:700;font-size:0.88rem;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 12px rgba(37,99,235,0.25);">
                    <i class="ti ti-send"></i> <span>Enviar Ticket</span>
                </button>
            </div>
        </form>
    </div>
</div>

