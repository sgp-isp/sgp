<!-- =====================================================
     TOPBAR (Header-First con Cintillo Institucional)
     ===================================================== -->
<nav class="main-header">
    <!-- Zona Izquierda: Toggle + Logo SGP -->
    <div class="header-left">
        <!-- Toggle Button -->
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <?php
        $_rolIdLogo = (int)(Session::get('role_id') ?? 99);
        $_logoHomeUrl = URLROOT . '/dashboard';
        if ($_rolIdLogo === 0) $_logoHomeUrl = URLROOT . '/superadmin';
        elseif ($_rolIdLogo === 1) $_logoHomeUrl = URLROOT . '/admin';
        elseif ($_rolIdLogo === 2) $_logoHomeUrl = URLROOT . '/tutor';
        elseif ($_rolIdLogo === 3) $_logoHomeUrl = URLROOT . '/pasante/dashboard';
        ?>
        <!-- Logo SGP (Imagen) -->
        <a href="<?= $_logoHomeUrl ?>" class="brand-link" style="padding: 0; margin-left: 8px;" title="Ir al Inicio">
            <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" 
                 class="brand-image" 
                 style="opacity: .8; max-height: 35px; width: auto; margin: 0;">
        </a>
    </div>
    
    <!-- Zona Central: Logo Institucional (oculto en móvil) -->
    <div class="header-center d-none d-md-flex" style="position: absolute; left: 50%; transform: translateX(-50%); height: 100%; align-items: center;">
        <img src="<?= URLROOT ?>/img/gobe.png" 
             alt="Gobernación de Bolívar - Salud" 
             style="max-height: 52px; width: auto; max-width: 320px; object-fit: contain;">
    </div>
    
    <!-- Zona Derecha: Período Activo + Notificaciones + Perfil -->
    <div class="header-right topbar-actions">

        <!-- ========== [OPT-02] BADGE PERÍODO ACTIVO (Admin/Tutor) ========== -->
        <?php
        $_topbarRol = (int)(Session::get('role_id') ?? 99);
        if (in_array($_topbarRol, [0, 1, 2])):
            // Consulta ligera: solo una fila, usa índice de estado
            $_dbTopbar = Database::getInstance();
            $_dbTopbar->query("
                SELECT nombre, estado, fecha_fin
                FROM periodos_academicos
                WHERE estado IN ('Activo','Planificado')
                ORDER BY FIELD(estado,'Activo','Planificado'), fecha_inicio DESC
                LIMIT 1
            ");
            $_periodoTopbar = $_dbTopbar->single();
        ?>
        <?php if ($_periodoTopbar): ?>
        <a href="<?= URLROOT ?>/periodos" class="topbar-periodo-badge d-none d-lg-flex" title="Período académico actual — Click para gestionar">
            <span class="periodo-dot <?= $_periodoTopbar->estado === 'Activo' ? 'dot-activo' : 'dot-planificado' ?>"></span>
            <span class="periodo-nombre"><?= htmlspecialchars($_periodoTopbar->nombre) ?></span>
            <span class="periodo-estado-tag"><?= htmlspecialchars($_periodoTopbar->estado) ?></span>
        </a>
        <?php else: ?>
        <a href="<?= URLROOT ?>/periodos" class="topbar-periodo-badge topbar-periodo-sin d-none d-lg-flex" title="Sin período activo — Click para crear uno">
            <span class="periodo-dot dot-sin"></span>
            <span class="periodo-nombre">Sin período activo</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- ========== NOTIFICACIONES ========== -->
        <div class="notifications-wrapper">
            <button class="notification-btn" id="notificationBtn" aria-label="Notificaciones">
                <i class="ti ti-bell"></i>
                <span class="notification-badge" id="notificationBadge"></span>
            </button>
            
            <!-- Dropdown de Notificaciones -->
            <div class="notifications-dropdown" id="notificationsDropdown">
                <!-- Header -->
                <div class="notifications-header">
                    <span class="notifications-title">Notificaciones</span>
                    <button class="mark-all-read-btn" id="markAllReadBtn" title="Marcar todas como leídas">
                        <i class="ti ti-checks"></i>
                    </button>
                </div>
                
                <!-- Body (Lista de notificaciones) -->
                <div class="notifications-body" id="notificationList">
                    <!-- Se inyecta dinámicamente vía JS -->
                </div>
                
                <!-- Footer -->
                <div class="notifications-footer">
                    <a href="<?= URLROOT ?>/notificaciones">Ver todas las notificaciones</a>
                </div>
            </div>
        </div>
        
        <!-- ========== PERFIL ========== -->
        <div class="profile-dropdown" onclick="toggleProfileMenu()">
            <div class="profile-avatar">
                <?php 
                $userName = Session::get('user_name') ?? 'Usuario';
                echo strtoupper(substr($userName, 0, 1));
                ?>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?= $userName ?></div>
                <div class="profile-role">
                    <?php
                    $role_id = Session::get('role_id');
                    echo $role_id == 1 ? 'Administrador' : ($role_id == 2 ? 'Tutor' : 'Pasante');
                    ?>
                </div>
            </div>
            <i class="ti ti-chevron-down"></i>
        </div>
        
        <!-- Menú Desplegable del Perfil -->
        <div class="profile-menu" id="profileMenu">
            <a href="<?= URLROOT ?>/perfil/ver" class="profile-menu-item">
                <i class="ti ti-user"></i>
                <span>Mi Perfil</span>
            </a>
            <div class="profile-menu-divider"></div>
            <a href="<?= URLROOT ?>/auth/logout" class="profile-menu-item" onclick="event.preventDefault(); confirmLogout();">
                <i class="ti ti-logout"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</nav>

<style>
/* ── [OPT-02] Badge Período Activo en Topbar ─────────────────────────── */
.topbar-periodo-badge {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 6px 14px;
    border-radius: 20px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    text-decoration: none;
    color: inherit;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    transition: background 0.2s, border-color 0.2s;
    cursor: pointer;
    max-width: 260px;
    overflow: hidden;
}
.topbar-periodo-badge:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.35);
    color: inherit;
}
.periodo-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    animation: pulseDot 2s infinite;
}
.dot-activo     { background: #22c55e; box-shadow: 0 0 0 0 rgba(34,197,94,.4); }
.dot-planificado{ background: #f59e0b; animation: none; }
.dot-sin        { background: #ef4444; animation: none; }
@keyframes pulseDot {
    0%  { box-shadow: 0 0 0 0 rgba(34,197,94,.4); }
    70% { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
    100%{ box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}
.periodo-nombre {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 150px;
}
.periodo-estado-tag {
    font-size: 0.68rem;
    padding: 2px 7px;
    border-radius: 10px;
    background: rgba(255,255,255,0.18);
    font-weight: 700;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}
.topbar-periodo-sin .periodo-nombre { color: #fca5a5; }
</style>



<script>
// Toggle del menú de perfil
function toggleProfileMenu() {
    const menu = document.getElementById('profileMenu');
    menu.classList.toggle('active');
}

// Cerrar menú al hacer click fuera
document.addEventListener('click', function(event) {
    const menu = document.getElementById('profileMenu');
    const profileDropdown = document.querySelector('.profile-dropdown');
    
    if (menu && profileDropdown && !profileDropdown.contains(event.target)) {
        menu.classList.remove('active');
    }
});

// Confirmación de cierre de sesión con SweetAlert
function confirmLogout() {
    Swal.fire({
        title: '¿Estás seguro de que deseas cerrar sesión?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#162660',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            try {
                localStorage.setItem('sgp_theme', 'light');
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.classList.remove('dark');
            } catch(e) {}
            window.location.href = '<?= URLROOT ?>/auth/logout';
        }
    });
}
</script>
