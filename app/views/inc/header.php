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
            $user_role_id = Session::get('role_id') ?? 0;
            $dashboardUrl = URLROOT . '/dashboard';
            if ($user_role_id == 1) $dashboardUrl = URLROOT . '/admin';
            if ($user_role_id == 2) $dashboardUrl = URLROOT . '/tutor';
            if ($user_role_id == 3) $dashboardUrl = URLROOT . '/pasante';
            ?>
            <a href="<?= $dashboardUrl ?>" class="brand-link d-flex align-items-center text-decoration-none">
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
        
        <!-- Toggle Modo Oscuro (Fase 5) -->
        <li class="nav-item">
            <button id="themeToggleBtn" class="theme-toggle-btn notif-btn" aria-label="Cambiar tema claro / oscuro" title="Cambiar tema claro / oscuro" onclick="toggleSgpTheme()">
                <i class="ti ti-moon" id="themeToggleIcon" style="font-size: 21px;"></i>
            </button>
        </li>

        <script>
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
        document.addEventListener('pjax:complete', function() {
            var saved = localStorage.getItem('sgp_theme') || 'light';
            applySgpTheme(saved);
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
            </div>
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
            <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                <!-- Header del Dropdown -->
                <div class="dropdown-header d-flex align-items-center p-3 mb-2" style="background: linear-gradient(135deg, #162660 0%, #0d1a3d 100%); border-radius: 8px;">
                    <div class="profile-avatar mr-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3);">
                        <?= strtoupper(substr($user_name, 0, 1)) ?>
                    </div>
                    <div class="text-white text-left overflow-hidden">
                        <div class="font-weight-bold text-truncate"><?= htmlspecialchars($user_name) ?></div>
                        <div class="small opacity-75">Conectado</div>
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
            window.location.href = '<?= URLROOT ?>/auth/logout';
        }
    });
}
</script>
