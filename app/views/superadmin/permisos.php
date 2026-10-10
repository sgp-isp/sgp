<?php
/**
 * SuperAdmin — Gestión de Permisos
 * UI: chips de rol + lista de usuarios estilo evaluaciones → slide → mod-cards originales
 */
?>

<style>
/* ── Slider ──────────────────────────────────────────── */
.perm-wrap   { overflow: hidden; border-radius: 20px; background: white; box-shadow: 0 2px 20px rgba(0,0,0,0.06); }
.perm-track  { display: flex; transition: transform .4s cubic-bezier(.4,0,.2,1); will-change: transform; }
.perm-panel  { min-width: 100%; box-sizing: border-box; }
.perm-inner  { padding: 24px; }

/* ── Breadcrumb ──────────────────────────────────────── */
.perm-crumbs { display:flex; align-items:center; gap:6px; margin-bottom:18px; flex-wrap:wrap; }
.perm-crumb  {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 14px; border:none; background:transparent;
    border-radius:50px; cursor:default;
    font-size:.82rem; font-weight:700; color:#94a3b8; transition:all .2s;
}
.perm-crumb.reach  { color:#475569; cursor:pointer; }
.perm-crumb.reach:hover { background:#f1f5f9; }
.perm-crumb.cur   { background:#eff6ff; color:#2563eb; }
.perm-crumb .dot  { width:8px; height:8px; border-radius:50%; background:#cbd5e1; flex-shrink:0; }
.perm-crumb.reach .dot { background:#94a3b8; }
.perm-crumb.cur   .dot { background:#2563eb; }
.perm-sep { color:#cbd5e1; font-size:.85rem; }

/* ── Role chips ──────────────────────────────────────── */
.perm-chips { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.perm-chip  {
    display:inline-flex; align-items:center; gap:8px;
    padding:9px 20px; border-radius:50px;
    border:2px solid #e2e8f0; background:#f8fafc;
    color:#64748b; font-weight:700; font-size:.85rem;
    cursor:pointer; transition:all .2s;
}
.perm-chip:hover { border-color:#94a3b8; background:white; }
.perm-chip.active {
    border-color: var(--cc); background: var(--cb);
    color: var(--cc); box-shadow: 0 4px 14px var(--cs);
}
.perm-chip-count {
    font-size:.72rem; font-weight:800;
    background:rgba(0,0,0,0.08); border-radius:50px;
    padding:2px 8px;
}

/* ── Búsqueda ────────────────────────────────────────── */
.perm-search { position:relative; margin-bottom:14px; }
.perm-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1rem; pointer-events:none; }
.perm-search input {
    width:100%; padding:11px 14px 11px 40px;
    border:1.5px solid #e2e8f0; border-radius:12px;
    font-size:.88rem; color:#1e293b; outline:none;
    transition:border .2s; box-sizing:border-box;
}
.perm-search input:focus { border-color:#2563eb; }

/* ── User list (estilo evaluaciones) ─────────────────── */
.perm-user-list { max-height: 480px; overflow-y: auto; border-radius: 14px; border: 1px solid #f1f5f9; }
.perm-user-row {
    display:flex; align-items:center; gap:14px;
    padding:13px 16px; border-bottom:1px solid #f8fafc;
    cursor:pointer; transition:background .15s; background:white;
}
.perm-user-row:last-child { border-bottom:none; }
.perm-user-row:hover  { background:#f8fafc; }
.perm-user-row.active { background:#eff6ff; border-left:3px solid #2563eb; }
.perm-uavatar {
    width:38px; height:38px; border-radius:50%;
    background:linear-gradient(135deg,#1e3a8a,#3b82f6);
    display:flex; align-items:center; justify-content:center;
    font-weight:800; color:white; font-size:.9rem; flex-shrink:0;
}
.perm-uname  { font-size:.88rem; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.perm-uemail { font-size:.72rem; color:#94a3b8; margin-top:1px; }
.perm-ubadge {
    margin-left:auto; padding:2px 8px;
    background:#fdf4ff; border:1px solid #f5d0fe;
    border-radius:50px; font-size:.65rem; font-weight:700;
    color:#a855f7; flex-shrink:0; white-space:nowrap;
}
.perm-uarrow { color:#cbd5e1; font-size:1rem; flex-shrink:0; }

/* ── Panel header (panel 1) ──────────────────────────── */
.perm-pheader {
    display:flex; align-items:center; gap:12px;
    margin-bottom:18px; padding-bottom:16px;
    border-bottom:1px solid #f1f5f9;
}
.perm-back {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; background:#f8fafc;
    border:1px solid #e2e8f0; border-radius:10px;
    cursor:pointer; font-size:.82rem; font-weight:700;
    color:#475569; transition:all .2s; flex-shrink:0;
}
.perm-back:hover { background:#e2e8f0; color:#1e293b; }
.perm-ptitle { flex:1; font-size:1rem; font-weight:700; color:#1e293b; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* ── User banner ─────────────────────────────────────── */
.perm-banner {
    display:flex; align-items:center; gap:16px;
    padding:16px 20px; margin-bottom:20px;
    background:linear-gradient(135deg,#f8fafc,#eff6ff);
    border:1px solid #e0e7ff; border-radius:14px;
}
.perm-banner-avatar {
    width:52px; height:52px; border-radius:50%;
    background:linear-gradient(135deg,#1e3a8a,#3b82f6);
    display:flex; align-items:center; justify-content:center;
    font-size:1.25rem; font-weight:900; color:white; flex-shrink:0;
}
.perm-banner-name  { font-size:1rem; font-weight:800; color:#1e293b; }
.perm-banner-sub   { font-size:.78rem; color:#64748b; margin-top:2px; }
.perm-banner-rol   {
    display:inline-block; margin-left:8px;
    padding:2px 10px; border-radius:50px;
    font-size:.65rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase;
}
.perm-reset-btn {
    margin-left:auto; display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; background:#fef2f2;
    border:1.5px solid #fecaca; border-radius:10px;
    cursor:pointer; font-size:.8rem; font-weight:700;
    color:#ef4444; transition:all .2s; flex-shrink:0;
}
.perm-reset-btn:hover { background:#fee2e2; }

/* ── Mod cards (diseño original restaurado) ──────────── */
.mod-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 12px;
}
.mod-card {
    background: white; border-radius: 14px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden; transition: box-shadow .2s, transform .2s;
}
.mod-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.07); transform: translateY(-2px); }
.mod-card-header {
    padding: 12px 14px; display: flex; align-items: center; gap: 10px; background: white;
}
.mod-card-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.mod-card-info { flex: 1; min-width: 0; }
.mod-card-name { font-size: .85rem; font-weight: 700; color: #1e293b; }
.mod-group-badge {
    display: inline-block; margin-top: 2px;
    padding: 1px 7px; border-radius: 50px;
    font-size: .6rem; font-weight: 700;
}
.mod-card-toggle-wrap { display:flex; flex-direction:column; align-items:center; gap:3px; flex-shrink:0; }
.mod-menu-lbl { font-size:.56rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; }
.mod-card-body { border-top:1px solid #f1f5f9; padding:8px 14px 10px; }
.mod-fn-header { font-size:.63rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
.mod-fn-row { display:flex; align-items:center; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f8fafc; }
.mod-fn-row:last-child { border-bottom:none; }
.mod-fn-label { display:flex; align-items:center; gap:6px; font-size:.78rem; font-weight:600; color:#475569; }
.mod-fn-row.dimmed .mod-fn-label { opacity:.45; }
.perm-override-dot { width:6px; height:6px; border-radius:50%; background:#f59e0b; box-shadow:0 0 0 2px white; margin-left:3px; display:inline-block; }
.mod-elevated-badge {
    display:inline-flex; align-items:center; gap:3px;
    margin-top:3px; padding:2px 7px; border-radius:50px;
    font-size:.57rem; font-weight:800; letter-spacing:.04em;
    background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;
}
.mod-card.elevated { border-color:#fed7aa; }
.mod-card.elevated .mod-card-header { background:#fffaf5; }

/* ── Toggle switch ───────────────────────────────────── */
.sgp-toggle { position:relative; display:inline-block; cursor:pointer; }
.sgp-toggle input { opacity:0; width:0; height:0; position:absolute; }
.sgp-toggle-slider { display:block; width:42px; height:24px; background:#cbd5e1; border-radius:12px; transition:background .2s; position:relative; }
.sgp-toggle-slider::before { content:''; position:absolute; width:20px; height:20px; left:2px; top:2px; background:white; border-radius:50%; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,0.2); }
.sgp-toggle input:checked + .sgp-toggle-slider { background:var(--toggle-color,#2563eb); }
.sgp-toggle input:checked + .sgp-toggle-slider::before { transform:translateX(18px); }
.sgp-toggle.sm .sgp-toggle-slider { width:32px; height:18px; border-radius:9px; }
.sgp-toggle.sm .sgp-toggle-slider::before { width:14px; height:14px; }
.sgp-toggle.sm input:checked + .sgp-toggle-slider::before { transform:translateX(14px); }

/* ── Loading / Empty ─────────────────────────────────── */
.perm-loading { display:flex; align-items:center; justify-content:center; padding:50px 20px; flex-direction:column; gap:10px; color:#94a3b8; }
.perm-spinner { width:28px; height:28px; border:3px solid #e2e8f0; border-top-color:#2563eb; border-radius:50%; animation:pspin .7s linear infinite; }
.perm-empty   { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:50px 20px; gap:10px; color:#94a3b8; text-align:center; }
.perm-empty i { font-size:2.2rem; }
.perm-empty p { font-size:.88rem; font-weight:500; margin:0; }
@keyframes pspin { to { transform:rotate(360deg); } }

@media (max-width: 640px) {
    .perm-inner { padding: 14px; }
    .mod-grid   { grid-template-columns: 1fr; }
    .perm-banner { flex-wrap: wrap; }
    .perm-reset-btn { margin-left: 0; width: 100%; justify-content: center; }
}
</style>

<div class="dashboard-container" style="width:100%;max-width:100%;padding:0;">

    <!-- BANNER -->
    <div style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:28px 36px;margin-bottom:22px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div style="position:absolute;top:-30px;right:-30px;width:180px;height:180px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
        <div style="display:flex;align-items:center;gap:14px;z-index:1;">
            <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:12px;flex-shrink:0;">
                <i class="ti ti-shield-cog" style="font-size:28px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.6rem;font-weight:700;margin:0;">Gestión de Permisos</h1>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:.88rem;">Control granular de accesos por usuario</p>
            </div>
        </div>
        <a href="<?= URLROOT ?>/superadmin" style="z-index:1;display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);border-radius:10px;color:white;text-decoration:none;font-size:.85rem;font-weight:600;">
            <i class="ti ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Breadcrumb -->
    <div class="perm-crumbs">
        <button class="perm-crumb cur" id="crumb0" onclick="goP(0)"><span class="dot"></span>Seleccionar usuario</button>
        <i class="ti ti-chevron-right perm-sep"></i>
        <button class="perm-crumb" id="crumb1" onclick="if(currentUid) goP(1)" disabled><span class="dot"></span><span id="crumb1Lbl">Permisos</span></button>
    </div>

    <!-- Slider -->
    <div class="perm-wrap">
        <div class="perm-track" id="pTrack">

            <!-- ── PANEL 0: lista de usuarios ── -->
            <div class="perm-panel">
                <div class="perm-inner">

                    <!-- Chips de rol -->
                    <div class="perm-chips">
                        <button class="perm-chip active" data-rol="1" id="chip-1"
                            style="--cc:#7c3aed;--cb:#f5f3ff;--cs:rgba(124,58,237,0.15);"
                            onclick="switchRol(1,this)">
                            <i class="ti ti-user-shield"></i>
                            Administradores
                            <span class="perm-chip-count"><?= $data['kpis']['total_admins'] ?></span>
                        </button>
                        <button class="perm-chip" data-rol="2" id="chip-2"
                            style="--cc:#059669;--cb:#ecfdf5;--cs:rgba(5,150,105,0.15);"
                            onclick="switchRol(2,this)">
                            <i class="ti ti-user-check"></i>
                            Tutores
                            <span class="perm-chip-count"><?= $data['kpis']['total_tutores'] ?? 0 ?></span>
                        </button>
                        <button class="perm-chip" data-rol="3" id="chip-3"
                            style="--cc:#d97706;--cb:#fffbeb;--cs:rgba(217,119,6,0.15);"
                            onclick="switchRol(3,this)">
                            <i class="ti ti-users-group"></i>
                            Pasantes
                            <span class="perm-chip-count"><?= $data['kpis']['total_pasantes'] ?? 0 ?></span>
                        </button>
                    </div>

                    <!-- Búsqueda -->
                    <div class="perm-search">
                        <i class="ti ti-search"></i>
                        <input type="text" id="pSearch" placeholder="Buscar por nombre o correo…" oninput="filterUsers(this.value)">
                    </div>

                    <!-- Lista -->
                    <div id="pUserList" class="perm-user-list">
                        <div class="perm-loading"><div class="perm-spinner"></div></div>
                    </div>

                </div>
            </div>

            <!-- ── PANEL 1: permisos del usuario ── -->
            <div class="perm-panel">
                <div class="perm-inner">

                    <div class="perm-pheader">
                        <button class="perm-back" onclick="goP(0)"><i class="ti ti-arrow-left"></i> Volver</button>
                        <div class="perm-ptitle" id="pTitle">Permisos</div>
                    </div>

                    <!-- Banner del usuario -->
                    <div class="perm-banner" id="pBanner" style="display:none;">
                        <div class="perm-banner-avatar" id="pBannerIni">?</div>
                        <div style="flex:1;min-width:0;">
                            <div class="perm-banner-name">
                                <span id="pBannerNombre">—</span>
                                <span class="perm-banner-rol" id="pBannerRolBadge"></span>
                            </div>
                            <div class="perm-banner-sub" id="pBannerEmail">—</div>
                        </div>
                        <button class="perm-reset-btn" onclick="resetCurrentUser()">
                            <i class="ti ti-refresh"></i> Restablecer al rol
                        </button>
                    </div>

                    <!-- Mod-card grid -->
                    <div id="permsBody">
                        <div class="perm-empty">
                            <i class="ti ti-arrow-left"></i>
                            <p>Selecciona un usuario para ver sus permisos</p>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /.perm-track -->
    </div><!-- /.perm-wrap -->

</div>

<script>
(function () {
    'use strict';
    const URLROOT = '<?= URLROOT ?>';

    const accionLabels = {
        'ver_usuarios':'Ver Usuarios','crear_usuario':'Crear Usuario','editar_usuario':'Editar Usuario',
        'desactivar_usuario':'Desactivar Usuario','ver_pasantes':'Ver Pasantes','editar_pasante':'Editar Pasante',
        'exportar_pasantes':'Exportar Lista','ver_asistencias':'Ver Asistencias','modificar_asistencia':'Modificar Asistencia',
        'exportar_asistencias':'Exportar Asistencias','ver_asignaciones':'Ver Asignaciones','crear_asignacion':'Crear Asignación',
        'editar_asignacion':'Editar Asignación','ver_evaluaciones':'Ver Evaluaciones','exportar_evaluacion':'Exportar PDF Evaluación',
        'ver_reportes':'Ver Reportes','exportar_reporte':'Exportar Reporte','ver_analiticas':'Ver Analíticas',
        'ver_backup':'Ver Backups','crear_backup':'Crear Backup','descargar_backup':'Descargar Backup',
        'ver_bitacora':'Ver Bitácora','exportar_bitacora':'Exportar Bitácora','ver_configuracion':'Ver Configuración',
        'editar_configuracion':'Editar Configuración','ver_periodos':'Ver Períodos','crear_periodo':'Crear Período',
        'ver_actividades':'Ver Actividades','crear_actividad':'Crear Actividad','ver_mis_pasantes':'Ver Mis Pasantes',
        'ver_mis_evaluaciones':'Ver Mis Evaluaciones','registrar_evaluacion':'Registrar Evaluación',
        'ver_perfil_pasante':'Ver Mi Perfil','descargar_constancia':'Descargar Constancia',
    };
    const tipoIconos = { 'ver':'ti-eye','crear':'ti-circle-plus','editar':'ti-pencil','eliminar':'ti-trash','exportar':'ti-download' };
    const colorRules = {
        'Administración': { bg:'#eff6ff', color:'#2563eb' },
        'Pasantías':      { bg:'#ecfdf5', color:'#059669' },
        'Informes':       { bg:'#eef2ff', color:'#4f46e5' },
        'Sistema':        { bg:'#fef2f2', color:'#dc2626' },
        'Académico':      { bg:'#fffbeb', color:'#d97706' },
        'Tutor':          { bg:'#ecfeff', color:'#0891b2' },
        'Portal Pasante': { bg:'#fdf4ff', color:'#a855f7' },
    };
    const rolNames  = { 1:'Administrador', 2:'Tutor', 3:'Pasante' };
    const rolColors = { 1:'#7c3aed', 2:'#059669', 3:'#d97706' };
    const rolBgs    = { 1:'#f5f3ff',   2:'#ecfdf5', 3:'#fffbeb' };
    const defaultRule = { bg:'#f1f5f9', color:'#64748b' };

    let currentPanel   = 0;
    let currentRolId   = 1;
    let currentUid     = null;
    let currentModulos = null;
    let currentPermisos = {};
    let usersData      = [];
    let usersCache     = {};

    // ── Navegación ──────────────────────────────────────
    window.goP = function (n) {
        if (n === 1 && !currentUid) return;
        currentPanel = n;
        document.getElementById('pTrack').style.transform = 'translateX(-' + (n * 100) + '%)';
        updateCrumbs();
    };

    function updateCrumbs() {
        var c0 = document.getElementById('crumb0');
        var c1 = document.getElementById('crumb1');
        c0.classList.toggle('cur',   currentPanel === 0);
        c0.classList.toggle('reach', currentPanel !== 0);
        c1.classList.toggle('cur',   currentPanel === 1);
        c1.classList.toggle('reach', currentPanel === 1 || !!currentUid);
        c1.disabled = !currentUid;
    }

    // ── Cambiar rol ──────────────────────────────────────
    window.switchRol = function (rolId, btn) {
        document.querySelectorAll('.perm-chip').forEach(function (c) { c.classList.remove('active'); });
        btn.classList.add('active');
        currentRolId = rolId;
        currentUid   = null;
        document.getElementById('pSearch').value = '';
        loadUsers(rolId);
    };

    // ── Cargar usuarios ──────────────────────────────────
    function loadUsers(rolId) {
        var list = document.getElementById('pUserList');
        list.innerHTML = '<div class="perm-loading"><div class="perm-spinner"></div></div>';

        fetch(URLROOT + '/superadmin/getUsersByRol?rol_id=' + rolId)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) { list.innerHTML = '<div class="perm-empty"><i class="ti ti-wifi-off"></i><p>Error al cargar.</p></div>'; return; }
                currentModulos = res.modulos;
                usersData = res.usuarios;
                usersCache = {};
                res.usuarios.forEach(function (u) { usersCache[u.id] = u; });
                renderUserList(res.usuarios);
            })
            .catch(function () { list.innerHTML = '<div class="perm-empty"><i class="ti ti-wifi-off"></i><p>Error de red.</p></div>'; });
    }

    function renderUserList(usuarios) {
        var list = document.getElementById('pUserList');
        if (!usuarios.length) {
            list.innerHTML = '<div class="perm-empty" style="padding:30px;"><i class="ti ti-users-off"></i><p>Sin usuarios en este grupo.</p></div>';
            return;
        }
        list.innerHTML = usuarios.map(function (u) {
            var nombre   = (u.nombre_completo || '').trim() || u.correo;
            var ini      = nombre.charAt(0).toUpperCase();
            var hasOvr   = u.permisos && Object.values(u.permisos).some(function (p) { return p.es_override; });
            var isAct    = currentUid == u.id;
            return '<div class="perm-user-row' + (isAct ? ' active' : '') + '" data-uid="' + u.id + '" onclick="selectUser(' + u.id + ')">' +
                '<div class="perm-uavatar">' + ini + '</div>' +
                '<div style="flex:1;min-width:0;">' +
                    '<div class="perm-uname">' + escH(nombre) + '</div>' +
                    '<div class="perm-uemail">' + escH(u.correo) + '</div>' +
                '</div>' +
                (hasOvr ? '<span class="perm-ubadge">Personalizado</span>' : '') +
                '<i class="ti ti-chevron-right perm-uarrow"></i>' +
            '</div>';
        }).join('');
    }

    window.filterUsers = function (q) {
        var t = (q || '').toLowerCase().trim();
        renderUserList(!t ? usersData : usersData.filter(function (u) {
            return ((u.nombre_completo || '').toLowerCase().includes(t)) ||
                   ((u.correo || '').toLowerCase().includes(t));
        }));
    };

    // ── Seleccionar usuario ──────────────────────────────
    window.selectUser = function (uid) {
        currentUid     = uid;
        var u          = usersCache[uid];
        if (!u) return;
        currentPermisos = u.permisos || {};

        var nombre = (u.nombre_completo || '').trim() || u.correo;
        var ini    = nombre.charAt(0).toUpperCase();
        var rol    = rolNames[currentRolId]  || 'Usuario';
        var rolC   = rolColors[currentRolId] || '#64748b';
        var rolB   = rolBgs[currentRolId]    || '#f1f5f9';

        // Breadcrumb
        document.getElementById('crumb1Lbl').textContent = nombre.split(' ')[0];
        document.getElementById('pTitle').textContent    = 'Permisos de ' + nombre;

        // Banner
        document.getElementById('pBannerIni').textContent    = ini;
        document.getElementById('pBannerNombre').textContent  = nombre;
        document.getElementById('pBannerEmail').textContent   = u.correo;
        var badge = document.getElementById('pBannerRolBadge');
        badge.textContent  = rol;
        badge.style.background = rolB;
        badge.style.color      = rolC;
        document.getElementById('pBanner').style.display = 'flex';

        // Highlight row
        document.querySelectorAll('.perm-user-row').forEach(function (el) {
            el.classList.toggle('active', parseInt(el.dataset.uid) === uid);
        });

        renderModCards(currentModulos, currentPermisos);
        goP(1);
    };

    // ── Render mod-cards (diseño original) ───────────────
    function renderModCards(modulos, permisos) {
        var body = document.getElementById('permsBody');
        if (!modulos || !Object.keys(modulos).length) {
            body.innerHTML = '<div class="perm-empty"><i class="ti ti-lock-question"></i><p>Sin módulos configurados.</p></div>';
            return;
        }

        var html = '<div class="mod-grid">';

        for (var grupo in modulos) {
            if (!Object.prototype.hasOwnProperty.call(modulos, grupo)) continue;
            var modulosGrupo = modulos[grupo];

            for (var modId in modulosGrupo) {
                if (!Object.prototype.hasOwnProperty.call(modulosGrupo, modId)) continue;
                var acciones = modulosGrupo[modId];
                if (!acciones.length) continue;

                var rule       = colorRules[grupo] || defaultRule;
                var modName    = acciones[0].modulo_nombre || 'Módulo';
                var modIcon    = acciones[0].icono || 'ti-folder';
                var rolBase    = parseInt(acciones[0].rol_base) || 1;
                var isElevated = currentRolId !== null && rolBase < currentRolId;

                var accionVer  = null;
                var funciones  = [];
                acciones.forEach(function (a) { if (a.tipo === 'ver') accionVer = a; else funciones.push(a); });

                // Toggle principal (menú)
                var mainToggle = '';
                if (accionVer) {
                    var vp  = permisos[accionVer.clave] || {};
                    var vH  = !!vp.habilitado;
                    var vOv = !!vp.es_override;
                    mainToggle = '<div class="mod-card-toggle-wrap">' +
                        '<label class="sgp-toggle" title="Habilitar en menú lateral">' +
                            '<input type="checkbox" data-clave="' + accionVer.clave + '" ' + (vH ? 'checked' : '') +
                                ' onchange="togglePermiso(this,\'' + modId + '\')">' +
                            '<span class="sgp-toggle-slider" style="--toggle-color:' + rule.color + ';"></span>' +
                        '</label>' +
                        '<span class="mod-menu-lbl" style="color:' + rule.color + ';">Menú</span>' +
                        (vOv ? '<span class="perm-override-dot" title="Permiso personalizado" style="margin-top:1px;"></span>' : '') +
                    '</div>';
                }

                // Filas de funciones
                var fnRows = '';
                var verHab = accionVer ? !!(permisos[accionVer.clave] || {}).habilitado : true;
                funciones.forEach(function (accion) {
                    var p   = permisos[accion.clave] || {};
                    var chk = !!p.habilitado;
                    var ovr = !!p.es_override;
                    var lbl = accionLabels[accion.clave] || accion.accion_nombre;
                    var ico = tipoIconos[accion.tipo] || 'ti-circle-dot';
                    fnRows += '<div class="mod-fn-row' + (!verHab ? ' dimmed' : '') + '">' +
                        '<span class="mod-fn-label">' +
                            '<i class="ti ' + ico + '" style="color:' + rule.color + ';font-size:.85rem;"></i>' +
                            lbl + (ovr ? '<span class="perm-override-dot"></span>' : '') +
                        '</span>' +
                        '<label class="sgp-toggle sm">' +
                            '<input type="checkbox" data-clave="' + accion.clave + '" ' + (chk ? 'checked' : '') +
                                ' onchange="togglePermiso(this)">' +
                            '<span class="sgp-toggle-slider" style="--toggle-color:' + rule.color + ';"></span>' +
                        '</label>' +
                    '</div>';
                });

                html += '<div class="mod-card' + (isElevated ? ' elevated' : '') + '" data-mod-id="' + modId + '">' +
                    '<div class="mod-card-header">' +
                        '<div class="mod-card-icon" style="background:' + rule.bg + ';color:' + rule.color + ';">' +
                            '<i class="ti ' + modIcon + '"></i>' +
                        '</div>' +
                        '<div class="mod-card-info">' +
                            '<div class="mod-card-name">' + modName + '</div>' +
                            '<span class="mod-group-badge" style="background:' + rule.bg + ';color:' + rule.color + ';">' + grupo + '</span>' +
                            (isElevated ? '<span class="mod-elevated-badge"><i class="ti ti-shield-half-filled"></i> Acceso elevado</span>' : '') +
                        '</div>' +
                        mainToggle +
                    '</div>' +
                    (funciones.length ? '<div class="mod-card-body"><div class="mod-fn-header">Funciones del módulo</div>' + fnRows + '</div>' : '') +
                '</div>';
            }
        }

        html += '</div>';
        body.innerHTML = html;
    }

    // ── Guardar permiso ──────────────────────────────────
    window.togglePermiso = function (input, modId) {
        if (!currentUid) return;
        var clave  = input.dataset.clave;
        var nuevo  = input.checked;
        var prev   = !nuevo;

        if (modId !== undefined) {
            var card = document.querySelector('.mod-card[data-mod-id="' + modId + '"]');
            if (card) card.querySelectorAll('.mod-fn-row').forEach(function (row) {
                nuevo ? row.classList.remove('dimmed') : row.classList.add('dimmed');
            });
        }

        fetch(URLROOT + '/superadmin/savePermiso', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: parseInt(currentUid), clave: clave, habilitado: nuevo })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                if (window.NotificationService) window.NotificationService.success(res.message);
                if (!currentPermisos[clave]) currentPermisos[clave] = {};
                currentPermisos[clave] = { habilitado: nuevo, es_override: true };
                if (usersCache[currentUid]) {
                    if (!usersCache[currentUid].permisos) usersCache[currentUid].permisos = {};
                    usersCache[currentUid].permisos[clave] = currentPermisos[clave];
                }
                // Marcar usuario como personalizado en la lista
                var row = document.querySelector('.perm-user-row[data-uid="' + currentUid + '"]');
                if (row && !row.querySelector('.perm-ubadge')) {
                    var arrow = row.querySelector('.perm-uarrow');
                    if (arrow) arrow.insertAdjacentHTML('beforebegin', '<span class="perm-ubadge">Personalizado</span>');
                }
            } else {
                if (window.NotificationService) window.NotificationService.error(res.message);
                input.checked = prev;
                if (modId !== undefined) {
                    var card2 = document.querySelector('.mod-card[data-mod-id="' + modId + '"]');
                    if (card2) card2.querySelectorAll('.mod-fn-row').forEach(function (row) {
                        prev ? row.classList.remove('dimmed') : row.classList.add('dimmed');
                    });
                }
            }
        })
        .catch(function () { input.checked = prev; });
    };

    // ── Restablecer ──────────────────────────────────────
    window.resetCurrentUser = function () {
        if (!currentUid) return;
        var u = usersCache[currentUid];
        var nombre = u ? ((u.nombre_completo || '').trim() || 'este usuario') : 'este usuario';

        Swal.fire({
            title: '¿Restablecer permisos?',
            html: '<p>Los permisos de <strong>' + nombre + '</strong> volverán a los valores por defecto de su rol.</p>',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, restablecer', cancelButtonText: 'Cancelar', confirmButtonColor: '#2563eb'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            fetch(URLROOT + '/superadmin/resetUsuario', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: parseInt(currentUid) })
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (window.NotificationService) window.NotificationService.success(res.message);
                    setTimeout(function () { loadUsers(currentRolId); goP(0); }, 600);
                } else {
                    if (window.NotificationService) window.NotificationService.error(res.message);
                }
            });
        });
    };

    function escH(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }

    // Carga inicial
    loadUsers(1);
    updateCrumbs();
})();
</script>

