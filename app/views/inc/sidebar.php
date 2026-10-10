<?php
/**
 * Sidebar - Navegación Lateral del Sistema
 * 
 * MIDDLEWARE "LA JAULA":
 * Si el usuario tiene requiere_cambio_clave = 1, NO se renderiza el sidebar.
 * Esto obliga al usuario a completar el wizard antes de acceder al sistema.
 * 
 * RAZÓN TÉCNICA:
 * Un usuario "enjaulado" solo debe ver el wizard, sin acceso a otras secciones.
 * Al suprimir sidebar y navbar, garantizamos que no pueda navegar a otras páginas.
 */

// ============================================
// MIDDLEWARE: Verificar Estado del Usuario
// ============================================
if (Session::get('requiere_cambio_clave') == 1) {
    // Usuario está "enjaulado" → NO renderizar sidebar
    return;
}

// ============================================
// ADAPTADOR DE COMPATIBILIDAD
// ============================================
$user_name = Session::get('user_name') ?? Session::get('nombres') ?? Session::get('nombre') ?? 'Usuario';
$rol_id = Session::get('role_id') ?? Session::get('rol_id') ?? 0;
$role = 'Invitado';

switch ($rol_id) {
    case 0: $role = 'SuperAdministrador'; break;
    case 1: $role = 'Administrador'; break;
    case 2: $role = 'Tutor'; break;
    case 3: $role = 'Pasante'; break;
    default: 
        $role = Session::get('rol_nombre') ?? 'Invitado';
        break;
}

// Determinar URL del dashboard según rol
$dashboardUrl = URLROOT . '/dashboard';
if ($rol_id == 0) $dashboardUrl = URLROOT . '/superadmin';
if ($rol_id == 1) $dashboardUrl = URLROOT . '/admin';
if ($rol_id == 2) $dashboardUrl = URLROOT . '/tutor';
if ($rol_id == 3) $dashboardUrl = URLROOT . '/pasante/dashboard';

/**
 * Helper: Determinar si un link está activo
 * 
 * @param string $url URL a verificar
 * @return string Clase CSS 'active' si coincide, '' si no
 */
function isActive($url, $exact = false) {
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($exact) {
        $target = parse_url(URLROOT . $url, PHP_URL_PATH);
        return rtrim($current, '/') === rtrim($target, '/') ? 'active' : '';
    }
    return strpos($_SERVER['REQUEST_URI'], $url) !== false ? 'active' : '';
}
?>

<aside class="main-sidebar">
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <!-- Dashboard -->
            <li>
                <a href="<?= $dashboardUrl ?>" 
                   class="nav-link <?= isActive($rol_id == 1 ? '/admin' : ($rol_id == 2 ? '/tutor' : ($rol_id == 0 ? '/superadmin' : '/pasante/dashboard')), true) ?>"
                   data-tooltip="Inicio">
                    <i class="ti ti-home"></i>
                    <span class="menu-text">Inicio</span>
                </a>
            </li>

            <?php if ($role == 'SuperAdministrador'): ?>
            <!-- Menú Exclusivo SuperAdministrador -->
            <li>
                <a href="<?= URLROOT ?>/superadmin/permisos"
                   class="nav-link <?= isActive('/superadmin/permisos') ?>"
                   data-tooltip="Gestión de Permisos">
                    <i class="ti ti-shield-lock"></i>
                    <span class="menu-text">Permisos</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($role, ['Administrador', 'SuperAdministrador'])): ?>
            <!-- Menú Administrador / SuperAdministrador — respeta permisos granulares -->
            <?php if (RoleMiddleware::hasPermission('ver_usuarios')): ?>
            <li>
                <a href="<?= URLROOT ?>/users"
                   class="nav-link <?= isActive('/users') ?>"
                   data-tooltip="Usuarios">
                    <i class="ti ti-users"></i>
                    <span class="menu-text">Usuarios</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_pasantes')): ?>
            <li>
                <a href="<?= URLROOT ?>/pasantes"
                   class="nav-link <?= isActive('/pasantes') ?>"
                   data-tooltip="Pasantes">
                    <i class="ti ti-user-check"></i>
                    <span class="menu-text">Pasantes</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_asistencias')): ?>
            <li>
                <a href="<?= URLROOT ?>/asistencias"
                   class="nav-link <?= isActive('/asistencias') ?>"
                   data-tooltip="Asistencias">
                    <i class="ti ti-calendar-stats"></i>
                    <span class="menu-text">Asistencias</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_asignaciones') && RoleMiddleware::hasAnyRole([0, 1])): ?>
            <li>
                <a href="<?= URLROOT ?>/asignaciones"
                   class="nav-link <?= isActive('/asignaciones') ?>"
                   data-tooltip="Asignaciones">
                    <i class="ti ti-user-cog"></i>
                    <span class="menu-text">Asignaciones</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_evaluaciones')): ?>
            <li>
                <a href="<?= URLROOT ?>/evaluaciones"
                   class="nav-link <?= isActive('/evaluaciones') ?>"
                   data-tooltip="Evaluaciones">
                    <i class="ti ti-star"></i>
                    <span class="menu-text">Evaluaciones</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_reportes')): ?>
            <li>
                <a href="<?= URLROOT ?>/reportes"
                   class="nav-link <?= isActive('/reportes') ?>"
                   data-tooltip="Centro de Reportes">
                    <i class="ti ti-file-analytics"></i>
                    <span class="menu-text">Reportes</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_analiticas')): ?>
            <li>
                <a href="<?= URLROOT ?>/analiticas"
                   class="nav-link <?= isActive('/analiticas') ?>"
                   data-tooltip="Analíticas">
                    <i class="ti ti-chart-dots"></i>
                    <span class="menu-text">Analíticas</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_backup')): ?>
            <li>
                <a href="<?= URLROOT ?>/backup"
                   class="nav-link <?= isActive('/backup') ?>"
                   data-tooltip="Respaldos">
                    <i class="ti ti-database"></i>
                    <span class="menu-text">Respaldos</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_bitacora')): ?>
            <li>
                <a href="<?= URLROOT ?>/bitacora"
                   class="nav-link <?= isActive('/bitacora') ?>"
                   data-tooltip="Bitácora">
                    <i class="ti ti-file-analytics"></i>
                    <span class="menu-text">Bitácora</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_configuracion')): ?>
            <li>
                <a href="<?= URLROOT ?>/configuracion"
                   class="nav-link <?= isActive('/configuracion') ?>"
                   data-tooltip="Configuración">
                    <i class="ti ti-settings"></i>
                    <span class="menu-text">Configuración</span>
                </a>
            </li>
            <?php endif; ?>
            <!-- Módulos Extras -->
            <?php if (RoleMiddleware::hasPermission('ver_periodos') && RoleMiddleware::hasAnyRole([0, 1])): ?>
            <li style="margin-top:6px;padding-top:6px;border-top:1px solid rgba(255,255,255,0.08);">
                <a href="<?= URLROOT ?>/periodos"
                   class="nav-link <?= isActive('/periodos') ?>"
                   data-tooltip="Períodos Académicos">
                    <i class="ti ti-calendar-month"></i>
                    <span class="menu-text">Períodos</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_actividades')): ?>
            <li>
                <a href="<?= URLROOT ?>/actividades"
                   class="nav-link <?= isActive('/actividades') ?>"
                   data-tooltip="Actividades Extras">
                    <i class="ti ti-briefcase"></i>
                    <span class="menu-text">Act. Extras</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="<?= URLROOT ?>/examenes"
                   class="nav-link <?= isActive('/examenes') ?>"
                   data-tooltip="Exámenes Rápidos">
                    <i class="ti ti-notebook"></i>
                    <span class="menu-text">Exámenes</span>
                </a>
            </li>
            <li style="margin-top:6px;padding-top:6px;border-top:1px solid rgba(255,255,255,0.08);">
                <a href="<?= URLROOT ?>/ayuda"
                   class="nav-link <?= isActive('/ayuda') ?>"
                   data-tooltip="Manual de Usuario">
                    <i class="ti ti-lifebuoy"></i>
                    <span class="menu-text">Ayuda</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role == 'Tutor'): ?>
            <!-- ========================================
                 MENÚ TUTOR — Base + permisos granulares
                 ======================================== -->
            <li>
                <a href="<?= URLROOT ?>/tutor/pasantes"
                   class="nav-link <?= isActive('/tutor/pasantes') ?>"
                   data-tooltip="Mis Pasantes">
                    <i class="ti ti-users-group"></i>
                    <span class="menu-text">Mis Pasantes</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/tutor/asistencias"
                   class="nav-link <?= isActive('/tutor/asistencias') ?>"
                   data-tooltip="Asistencias">
                    <i class="ti ti-clock-check"></i>
                    <span class="menu-text">Asistencias</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/tutor/puntualidad"
                   class="nav-link <?= isActive('/tutor/puntualidad') ?>"
                   data-tooltip="Puntualidad">
                    <i class="ti ti-clock-exclamation"></i>
                    <span class="menu-text">Puntualidad</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/evaluaciones"
                   class="nav-link <?= isActive('/evaluaciones') ?>"
                   data-tooltip="Evaluaciones">
                    <i class="ti ti-star"></i>
                    <span class="menu-text">Evaluaciones</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/reportes"
                   class="nav-link <?= isActive('/reportes') ?>"
                   data-tooltip="Reportes">
                    <i class="ti ti-file-analytics"></i>
                    <span class="menu-text">Reportes</span>
                </a>
            </li>
            <!-- Accesos extra concedidos por el SuperAdmin -->
            <?php if (RoleMiddleware::hasPermission('ver_pasantes') && !RoleMiddleware::hasPermission('ver_mis_pasantes')): ?>
            <li>
                <a href="<?= URLROOT ?>/pasantes"
                   class="nav-link <?= isActive('/pasantes') ?>"
                   data-tooltip="Pasantes">
                    <i class="ti ti-user-check"></i>
                    <span class="menu-text">Pasantes</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_asignaciones') && RoleMiddleware::hasAnyRole([0, 1])): ?>
            <li>
                <a href="<?= URLROOT ?>/asignaciones"
                   class="nav-link <?= isActive('/asignaciones') ?>"
                   data-tooltip="Asignaciones">
                    <i class="ti ti-user-cog"></i>
                    <span class="menu-text">Asignaciones</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_analiticas')): ?>
            <li>
                <a href="<?= URLROOT ?>/analiticas"
                   class="nav-link <?= isActive('/analiticas') ?>"
                   data-tooltip="Analíticas">
                    <i class="ti ti-chart-dots"></i>
                    <span class="menu-text">Analíticas</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (RoleMiddleware::hasPermission('ver_periodos') && RoleMiddleware::hasAnyRole([0, 1])): ?>
            <li>
                <a href="<?= URLROOT ?>/periodos"
                   class="nav-link <?= isActive('/periodos') ?>"
                   data-tooltip="Períodos">
                    <i class="ti ti-calendar-month"></i>
                    <span class="menu-text">Períodos</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="<?= URLROOT ?>/examenes"
                   class="nav-link <?= isActive('/examenes') ?>"
                   data-tooltip="Exámenes Rápidos">
                    <i class="ti ti-notebook"></i>
                    <span class="menu-text">Exámenes</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/actividades"
                   class="nav-link <?= isActive('/actividades') ?>"
                   data-tooltip="Act. Extras">
                    <i class="ti ti-briefcase"></i>
                    <span class="menu-text">Act. Extras</span>
                </a>
            </li>
            <li style="margin-top:6px;padding-top:6px;border-top:1px solid rgba(255,255,255,0.08);">
                <a href="<?= URLROOT ?>/ayuda"
                   class="nav-link <?= isActive('/ayuda') ?>"
                   data-tooltip="Manual de Usuario">
                    <i class="ti ti-lifebuoy"></i>
                    <span class="menu-text">Ayuda</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role == 'Pasante'): ?>
            <li>
                <a href="<?= URLROOT ?>/pasante/asistencia"
                   class="nav-link <?= isActive('/pasante/asistencia') ?>"
                   data-tooltip="Mi Asistencia">
                    <i class="ti ti-clock-check"></i>
                    <span class="menu-text">Mi Asistencia</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/pasante/misActividades"
                   class="nav-link <?= isActive('/pasante/misActividades') ?>"
                   data-tooltip="Mis Actividades">
                    <i class="ti ti-writing"></i>
                    <span class="menu-text">Mis Actividades</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/pasante/analiticas"
                   class="nav-link <?= isActive('/pasante/analiticas') ?>"
                   data-tooltip="Mis Analíticas">
                    <i class="ti ti-chart-dots"></i>
                    <span class="menu-text">Mis Analíticas</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/pasante/misEvaluaciones"
                   class="nav-link <?= isActive('/pasante/misEvaluaciones') ?>"
                   data-tooltip="Mis Evaluaciones">
                    <i class="ti ti-star"></i>
                    <span class="menu-text">Mis Evaluaciones</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/pasante/misExamenes"
                   class="nav-link <?= isActive('/pasante/misExamenes') ?>"
                   data-tooltip="Exámenes">
                    <i class="ti ti-notebook"></i>
                    <span class="menu-text">Exámenes</span>
                </a>
            </li>
            <li>
                <a href="<?= URLROOT ?>/pasante/constancia"
                   class="nav-link <?= isActive('/pasante/constancia') ?>"
                   data-tooltip="Mi Constancia">
                    <i class="ti ti-file-certificate"></i>
                    <span class="menu-text">Mi Constancia</span>
                </a>
            </li>
            <li style="margin-top:6px;padding-top:6px;border-top:1px solid rgba(255,255,255,0.08);">
                <a href="<?= URLROOT ?>/ayuda"
                   class="nav-link <?= isActive('/ayuda') ?>"
                   data-tooltip="Manual de Usuario">
                    <i class="ti ti-lifebuoy"></i>
                    <span class="menu-text">Ayuda</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= URLROOT ?>/auth/logout" class="btn-logout" onclick="event.preventDefault(); confirmLogout();" title="Cerrar Sesión">
            <i class="ti ti-power"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>

<?php /* ============================================================
   SGP MOBILE DOCK — Navegación iOS-Style (solo en móvil < 992px)
   Reemplaza el sidebar lateral en resoluciones pequeñas.
   ============================================================ */ ?>

<!-- Overlay del Sheet "Más" -->
<div id="sgpDockOverlay" class="sgp-dock-sheet-overlay" aria-hidden="true"></div>

<!-- Sheet inferior "Más" (Bottom Drawer) -->
<div id="sgpDockSheet" class="sgp-dock-sheet" role="dialog" aria-modal="true" aria-label="Más opciones de navegación">
    <div class="dock-sheet-handle" aria-hidden="true"></div>
    <div class="dock-sheet-header">
        <h6>Más secciones</h6>
    </div>
    <nav class="dock-sheet-nav" aria-label="Navegación adicional">

        <?php if ($role == 'SuperAdministrador'): ?>
        <a href="<?= URLROOT ?>/pasantes" class="dock-sheet-item" data-href="/pasantes">
            <span class="dock-sheet-icon"><i class="ti ti-users-group"></i></span>
            <span class="dock-sheet-label">Pasantes</span>
        </a>
        <a href="<?= URLROOT ?>/asistencias" class="dock-sheet-item" data-href="/asistencias">
            <span class="dock-sheet-icon"><i class="ti ti-calendar-stats"></i></span>
            <span class="dock-sheet-label">Asistencias</span>
        </a>
        <?php if (RoleMiddleware::hasAnyRole([0, 1])): ?>
        <a href="<?= URLROOT ?>/asignaciones" class="dock-sheet-item" data-href="/asignaciones">
            <span class="dock-sheet-icon"><i class="ti ti-user-cog"></i></span>
            <span class="dock-sheet-label">Asignaciones</span>
        </a>
        <?php endif; ?>
        <a href="<?= URLROOT ?>/evaluaciones" class="dock-sheet-item" data-href="/evaluaciones">
            <span class="dock-sheet-icon"><i class="ti ti-star"></i></span>
            <span class="dock-sheet-label">Evaluaciones</span>
        </a>
        <a href="<?= URLROOT ?>/reportes" class="dock-sheet-item" data-href="/reportes">
            <span class="dock-sheet-icon"><i class="ti ti-file-analytics"></i></span>
            <span class="dock-sheet-label">Reportes</span>
        </a>
        <a href="<?= URLROOT ?>/analiticas" class="dock-sheet-item" data-href="/analiticas">
            <span class="dock-sheet-icon"><i class="ti ti-chart-dots"></i></span>
            <span class="dock-sheet-label">Analíticas</span>
        </a>
        <a href="<?= URLROOT ?>/backup" class="dock-sheet-item" data-href="/backup">
            <span class="dock-sheet-icon"><i class="ti ti-database"></i></span>
            <span class="dock-sheet-label">Respaldos</span>
        </a>
        <a href="<?= URLROOT ?>/bitacora" class="dock-sheet-item" data-href="/bitacora">
            <span class="dock-sheet-icon"><i class="ti ti-clipboard-list"></i></span>
            <span class="dock-sheet-label">Bitácora</span>
        </a>
        <a href="<?= URLROOT ?>/configuracion" class="dock-sheet-item" data-href="/configuracion">
            <span class="dock-sheet-icon"><i class="ti ti-settings"></i></span>
            <span class="dock-sheet-label">Configuración</span>
        </a>
        <?php if (RoleMiddleware::hasAnyRole([0, 1])): ?>
        <a href="<?= URLROOT ?>/periodos" class="dock-sheet-item" data-href="/periodos">
            <span class="dock-sheet-icon"><i class="ti ti-calendar-month"></i></span>
            <span class="dock-sheet-label">Períodos</span>
        </a>
        <?php endif; ?>
        <a href="<?= URLROOT ?>/actividades" class="dock-sheet-item" data-href="/actividades">
            <span class="dock-sheet-icon"><i class="ti ti-briefcase"></i></span>
            <span class="dock-sheet-label">Act. Extras</span>
        </a>
        <a href="<?= URLROOT ?>/examenes" class="dock-sheet-item" data-href="/examenes">
            <span class="dock-sheet-icon"><i class="ti ti-notebook"></i></span>
            <span class="dock-sheet-label">Exámenes</span>
        </a>
        <a href="<?= URLROOT ?>/superadmin/permisos" class="dock-sheet-item" data-href="/superadmin/permisos">
            <span class="dock-sheet-icon"><i class="ti ti-shield-cog"></i></span>
            <span class="dock-sheet-label">Permisos</span>
        </a>
        <?php endif; ?>

        <?php if ($role == 'Administrador'): ?>
        <a href="<?= URLROOT ?>/asignaciones" class="dock-sheet-item" data-href="/asignaciones">
            <span class="dock-sheet-icon"><i class="ti ti-user-cog"></i></span>
            <span class="dock-sheet-label">Asignaciones</span>
        </a>
        <a href="<?= URLROOT ?>/evaluaciones" class="dock-sheet-item" data-href="/evaluaciones">
            <span class="dock-sheet-icon"><i class="ti ti-star"></i></span>
            <span class="dock-sheet-label">Evaluaciones</span>
        </a>
        <a href="<?= URLROOT ?>/reportes" class="dock-sheet-item" data-href="/reportes">
            <span class="dock-sheet-icon"><i class="ti ti-file-analytics"></i></span>
            <span class="dock-sheet-label">Reportes</span>
        </a>
        <a href="<?= URLROOT ?>/analiticas" class="dock-sheet-item" data-href="/analiticas">
            <span class="dock-sheet-icon"><i class="ti ti-chart-dots"></i></span>
            <span class="dock-sheet-label">Analíticas</span>
        </a>
        <a href="<?= URLROOT ?>/backup" class="dock-sheet-item" data-href="/backup">
            <span class="dock-sheet-icon"><i class="ti ti-database"></i></span>
            <span class="dock-sheet-label">Respaldos</span>
        </a>
        <a href="<?= URLROOT ?>/bitacora" class="dock-sheet-item" data-href="/bitacora">
            <span class="dock-sheet-icon"><i class="ti ti-clipboard-list"></i></span>
            <span class="dock-sheet-label">Bitácora</span>
        </a>
        <a href="<?= URLROOT ?>/configuracion" class="dock-sheet-item" data-href="/configuracion">
            <span class="dock-sheet-icon"><i class="ti ti-settings"></i></span>
            <span class="dock-sheet-label">Configuración</span>
        </a>
        <?php if (RoleMiddleware::hasAnyRole([0, 1])): ?>
        <a href="<?= URLROOT ?>/periodos" class="dock-sheet-item" data-href="/periodos">
            <span class="dock-sheet-icon"><i class="ti ti-calendar-month"></i></span>
            <span class="dock-sheet-label">Períodos</span>
        </a>
        <?php endif; ?>
        <a href="<?= URLROOT ?>/actividades" class="dock-sheet-item" data-href="/actividades">
            <span class="dock-sheet-icon"><i class="ti ti-briefcase"></i></span>
            <span class="dock-sheet-label">Act. Extras</span>
        </a>
        <a href="<?= URLROOT ?>/examenes" class="dock-sheet-item" data-href="/examenes">
            <span class="dock-sheet-icon"><i class="ti ti-notebook"></i></span>
            <span class="dock-sheet-label">Exámenes</span>
        </a>
        <?php endif; ?>

        <?php if ($role == 'Tutor'): ?>
        <a href="<?= URLROOT ?>/tutor/puntualidad" class="dock-sheet-item" data-href="/tutor/puntualidad">
            <span class="dock-sheet-icon"><i class="ti ti-clock-exclamation"></i></span>
            <span class="dock-sheet-label">Puntualidad</span>
        </a>
        <a href="<?= URLROOT ?>/reportes" class="dock-sheet-item" data-href="/reportes">
            <span class="dock-sheet-icon"><i class="ti ti-file-analytics"></i></span>
            <span class="dock-sheet-label">Reportes</span>
        </a>
        <a href="<?= URLROOT ?>/examenes" class="dock-sheet-item" data-href="/examenes">
            <span class="dock-sheet-icon"><i class="ti ti-notebook"></i></span>
            <span class="dock-sheet-label">Exámenes</span>
        </a>
        <?php endif; ?>

        <?php if ($role == 'Pasante'): ?>
        <a href="<?= URLROOT ?>/pasante/analiticas" class="dock-sheet-item" data-href="/pasante/analiticas">
            <span class="dock-sheet-icon"><i class="ti ti-chart-dots"></i></span>
            <span class="dock-sheet-label">Mis Analíticas</span>
        </a>
        <a href="<?= URLROOT ?>/pasante/misEvaluaciones" class="dock-sheet-item" data-href="/pasante/misEvaluaciones">
            <span class="dock-sheet-icon"><i class="ti ti-star"></i></span>
            <span class="dock-sheet-label">Mis Evaluaciones</span>
        </a>
        <a href="<?= URLROOT ?>/pasante/misExamenes" class="dock-sheet-item" data-href="/pasante/misExamenes">
            <span class="dock-sheet-icon"><i class="ti ti-notebook"></i></span>
            <span class="dock-sheet-label">Exámenes</span>
        </a>
        <a href="<?= URLROOT ?>/pasante/constancia" class="dock-sheet-item" data-href="/pasante/constancia">
            <span class="dock-sheet-icon"><i class="ti ti-file-certificate"></i></span>
            <span class="dock-sheet-label">Mi Constancia</span>
        </a>
        <a href="<?= URLROOT ?>/perfil/ver" class="dock-sheet-item" data-href="/perfil">
            <span class="dock-sheet-icon"><i class="ti ti-user-circle"></i></span>
            <span class="dock-sheet-label">Mi Perfil</span>
        </a>
        <?php endif; ?>

        <!-- Mi Perfil — visible para todos los roles en el sheet -->
        <?php if ($role !== 'Pasante'): // Para pasante ya está en el dock principal ?>
        <a href="<?= URLROOT ?>/perfil/ver" class="dock-sheet-item" data-href="/perfil/ver">
            <span class="dock-sheet-icon"><i class="ti ti-user-circle"></i></span>
            <span class="dock-sheet-label">Mi Perfil</span>
        </a>
        <?php endif; ?>

        <!-- Ayuda — visible para todos los roles -->
        <a href="<?= URLROOT ?>/ayuda" class="dock-sheet-item" data-href="/ayuda">
            <span class="dock-sheet-icon"><i class="ti ti-lifebuoy"></i></span>
            <span class="dock-sheet-label">Ayuda</span>
        </a>

    </nav>
    <div class="dock-sheet-divider"></div>
    <div style="padding: 4px 16px 8px;">
        <button class="dock-sheet-logout" onclick="confirmLogout()">
            <i class="ti ti-power"></i>
            Cerrar Sesión
        </button>
    </div>
</div>

<!-- DOCK INFERIOR PRINCIPAL -->
<nav id="sgpMobileDock" class="sgp-mobile-dock" role="navigation" aria-label="Navegación principal">
    <div class="dock-indicator"></div>

    <?php if ($role == 'SuperAdministrador'): ?>
        <!-- SUPERADMIN: Usuarios | Pasantes | [FAB: Dashboard] | Bitácora | Más -->
        <a href="<?= URLROOT ?>/users" class="dock-item" data-href="/users" title="Usuarios">
            <i class="ti ti-user-cog"></i>
            <span>Usuarios</span>
        </a>
        <a href="<?= URLROOT ?>/pasantes" class="dock-item" data-href="/pasantes" title="Pasantes">
            <i class="ti ti-users-group"></i>
            <span>Pasantes</span>
        </a>
        <a href="<?= URLROOT ?>/superadmin" class="dock-item dock-fab-item" data-href="/superadmin" title="Dashboard">
            <div class="dock-fab"><i class="ti ti-home"></i></div>
            <span>Inicio</span>
        </a>
        <a href="<?= URLROOT ?>/bitacora" class="dock-item" data-href="/bitacora" title="Bitácora">
            <i class="ti ti-clipboard-list"></i>
            <span>Bitácora</span>
        </a>

    <?php elseif ($role == 'Administrador'): ?>
        <!-- ADMIN: Asistencias | Pasantes | [FAB: Inicio] | Usuarios | Más -->
        <a href="<?= URLROOT ?>/asistencias" class="dock-item" data-href="/asistencias" title="Asistencias">
            <i class="ti ti-calendar-stats"></i>
            <span>Asistencia</span>
        </a>
        <a href="<?= URLROOT ?>/pasantes" class="dock-item" data-href="/pasantes" title="Pasantes">
            <i class="ti ti-users-group"></i>
            <span>Pasantes</span>
        </a>
        <a href="<?= URLROOT ?>/admin" class="dock-fab-item dock-item" data-href="/admin" title="Inicio">
            <div class="dock-fab"><i class="ti ti-home"></i></div>
            <span>Inicio</span>
        </a>
        <a href="<?= URLROOT ?>/users" class="dock-item" data-href="/users" title="Usuarios">
            <i class="ti ti-user-cog"></i>
            <span>Usuarios</span>
        </a>

    <?php elseif ($role == 'Tutor'): ?>
        <!-- TUTOR: Inicio | Mis Pasantes | [FAB: Asistencias] | Evaluaciones | Más -->
        <a href="<?= URLROOT ?>/tutor" class="dock-item" data-href="/tutor" title="Inicio">
            <i class="ti ti-home"></i>
            <span>Inicio</span>
        </a>
        <a href="<?= URLROOT ?>/tutor/pasantes" class="dock-item" data-href="/tutor/pasantes" title="Mis Pasantes">
            <i class="ti ti-users-group"></i>
            <span>Pasantes</span>
        </a>
        <a href="<?= URLROOT ?>/tutor/asistencias" class="dock-item dock-fab-item" data-href="/tutor/asistencias" title="Asistencias">
            <div class="dock-fab"><i class="ti ti-clock-check"></i></div>
            <span>Asistencia</span>
        </a>
        <a href="<?= URLROOT ?>/evaluaciones" class="dock-item" data-href="/evaluaciones" title="Evaluaciones">
            <i class="ti ti-star"></i>
            <span>Evaluar</span>
        </a>

    <?php elseif ($role == 'Pasante'): ?>
        <!-- PASANTE: Asistencia | Analíticas | [FAB: Inicio] | Evaluar | Más -->
        <a href="<?= URLROOT ?>/pasante/asistencia" class="dock-item" data-href="/pasante/asistencia" title="Mi Asistencia">
            <i class="ti ti-clock-check"></i>
            <span>Asistencia</span>
        </a>
        <a href="<?= URLROOT ?>/pasante/analiticas" class="dock-item" data-href="/pasante/analiticas" title="Mis Analíticas">
            <i class="ti ti-chart-dots"></i>
            <span>Analíticas</span>
        </a>
        <a href="<?= URLROOT ?>/pasante/dashboard" class="dock-item dock-fab-item" data-href="/pasante/dashboard" title="Inicio">
            <div class="dock-fab"><i class="ti ti-home"></i></div>
            <span>Inicio</span>
        </a>
        <a href="<?= URLROOT ?>/pasante/misEvaluaciones" class="dock-item" data-href="/pasante/misEvaluaciones" title="Mis Evaluaciones">
            <i class="ti ti-star"></i>
            <span>Evaluar</span>
        </a>
    <?php endif; ?>

    <?php if ($role !== 'Pasante'): ?>
    <!-- Botón "Más" para Admin y Tutor -->
    <button id="dockMoreBtn" class="dock-item dock-more-btn" aria-label="Más opciones" aria-expanded="false" aria-controls="sgpDockSheet">
        <i class="ti ti-dots-circle-horizontal"></i>
        <span>Más</span>
    </button>
    <?php else: ?>
    <!-- Botón "Más" para Pasante -->
    <button id="dockMoreBtn" class="dock-item dock-more-btn" aria-label="Más opciones" aria-expanded="false" aria-controls="sgpDockSheet">
        <i class="ti ti-dots-circle-horizontal"></i>
        <span>Más</span>
    </button>
    <?php endif; ?>

</nav>

