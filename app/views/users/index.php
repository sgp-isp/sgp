

<style>
    /* =====================================================
       MODERN USERS MANAGEMENT STYLES
       ===================================================== */
    
    /* Page Header - Modern with gradient */
    .page-header-modern {
        background: linear-gradient(135deg, 
            #172554 0%, 
            #1e3a8a 50%,
            #2563eb 100%
        );
        border-radius: 20px;
        padding: 32px 40px;
        margin-bottom: 32px;
        box-shadow: 0 10px 30px rgba(22, 38, 96, 0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .page-header-modern::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        pointer-events: none;
    }
    
    .page-header-content {
        display: flex;
        align-items: center;
        gap: 20px;
        z-index: 1;
    }
    
    .page-header-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }
    
    .page-header-text h1 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: white;
    }
    
    .page-header-text p {
        font-size: 1.05rem;
        opacity: 0.9;
        margin: 0;
    }
    
    /* Primary Button - Enhanced */
    .btn-primary {
        background: linear-gradient(135deg, var(--color-accent) 0%, #e8d4b8 100%);
        color: var(--color-primary);
        padding: 11px 20px;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all var(--transition-fast);
        box-shadow: 0 4px 12px rgba(241, 228, 209, 0.3);
        z-index: 1;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(241, 228, 209, 0.4);
    }
    
    .btn-primary i {
        font-size: 16px;
    }
    
    /* Table Container - Enhanced */
    .table-card-modern {
        background: var(--color-card);
        border-radius: 20px;
        padding: 0;
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    
    /* Filtros Rápidos (Cápsulas) movidos a la vista principal */
    
    /* =======================================
       Modal Styles (Estandarizado Pasantes)
       ======================================= */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(15,23,42,0.7); backdrop-filter: blur(6px);
        z-index: 9999; align-items: center; justify-content: center;
        animation: fadeIn 0.2s ease;
    }
    .modal-overlay.active { display: flex; }
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
    @keyframes slideUp { from { transform:translateY(24px);opacity:0; } to { transform:translateY(0);opacity:1; } }

    .modal-box {
        background: white;
        border-radius: 24px;
        width: 90%;
        max-width: 580px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden; 
        box-shadow: 0 32px 80px rgba(15,23,42,0.3);
        animation: slideUp 0.3s ease;
    }
    .modal-head {
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
        padding: 28px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0; 
        color: white;
    }
    .modal-head h2 { font-size:1.3rem; font-weight:700; margin:0; color:white !important; }
    .modal-head p  { font-size:0.85rem; margin:4px 0 0; color:rgba(255,255,255,0.8) !important; }
    .modal-head * { color: white !important; }
    
    .btn-close-modal {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white !important;
        width: 36px; height: 36px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.1rem;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s;
        flex-shrink: 0;
    }
    .btn-close-modal:hover { background: rgba(255,255,255,0.35); }
    .btn-close-modal i { color: white !important; }
    
    .modal-body {
        padding: 28px 32px;
        overflow-y: auto; 
        flex: 1;
    }

    .form-group { margin-bottom: 20px; }
    .form-label {
        display:block; font-size:0.82rem; font-weight:700; color:#374151;
        margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;
    }
    .form-input {
        width:100%; padding:12px 16px; border:2px solid #e5e7eb; border-radius:12px;
        font-size:0.95rem; color:#1e293b; transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing:border-box; background:#fafafa;
    }
    .form-input:focus {
        outline:none; border-color:#2563eb;
        box-shadow:0 0 0 4px rgba(79,70,229,0.1); background:white;
    }
    
    /* Vento Pills (Filtros) */
    .vento-pill.active {
        box-shadow: 0 4px 12px rgba(37,99,235,0.25);
    }

    /* Modal Styles - Consistente con modal de Pasantes */
    .modal {
        display: none;
        position: fixed;
        z-index: 1100; /* Mayor que sidebar-mobile (1050) para cubrirlo */
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(6px);
        animation: fadeIn 0.3s;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    /* Modal box: flexbox para header fijo + body scrollable */
    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 520px;
        width: 90%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden; /* Clip esquinas redondeadas */
        animation: slideUp 0.3s;
        box-shadow: 0 32px 80px rgba(15, 23, 42, 0.3);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(24px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    /* Header con gradiente azul institucional */
    .modal-header {
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        color: white;
    }
    
    .modal-header-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .modal-header-icon {
        background: rgba(255,255,255,0.15);
        border-radius: 12px;
        width: 44px; height: 44px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    
    .modal-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: white !important;
        margin: 0;
    }
    
    .modal-subtitle {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.75);
        margin: 3px 0 0;
    }
    
    /* Cuerpo scrolleable */
    .modal-body-scroll {
        padding: 28px;
        overflow-y: auto;
        flex: 1;
    }
    
    /* Botón cerrar modal */
    .modal-close {
        background: rgba(255,255,255,0.15);
        border: none;
        color: white;
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background 0.2s;
        flex-shrink: 0;
    }
    
    .modal-close:hover {
        background: rgba(255,255,255,0.3);
        transform: none;
    }
    
    .modal-close i {
        color: white !important;
    }

    /* 3. BOTÓN PREMIUM (Blanco/Azul - Alto Contraste) */
    .btn-stitch-gold {
        background-color: #ffffff !important;
        color: var(--color-primary) !important;
        font-weight: 700;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }
    .btn-stitch-gold:hover {
        background-color: #f8fafc !important;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    }
    
    /* Form Inputs - Modern */
    .input-modern {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all var(--transition-fast);
        background: white;
        color: var(--color-primary);
        font-weight: 500;
    }
    
    .input-modern:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 4px rgba(22, 38, 96, 0.1);
        transform: translateY(-1px);
    }
    
    .input-modern::placeholder {
        color: #9ca3af;
    }
    
    /* Form Labels */
    label {
        display: block;
        font-weight: 600;
        color: var(--color-primary);
        margin-bottom: 8px;
        font-size: 0.9rem;
    }
    
    /* Selectors and Wrappers - Core DT Overrides movidos a global */
    
    /* === ANIMACIONES ESTADO === */
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    @keyframes dot-pulse-green {
        0%   { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); }
        70%  { box-shadow: 0 0 0 5px rgba(22, 163, 74, 0); }
        100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }
    
    @keyframes dot-pulse-red {
        0%   { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
        70%  { box-shadow: 0 0 0 5px rgba(220, 38, 38, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
    }

    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
    }
    .status-dot.active {
        background-color: #16a34a;
        animation: dot-pulse-green 1.5s infinite;
    }
    .status-dot.inactive {
        background-color: #dc2626;
        /* No pulse for inactive, or a red pulse if preferred. We'll add red pulse. */
        animation: dot-pulse-red 2s infinite;
    }

    .welcome-icon {
        animation: pulse 2s ease-in-out infinite;
    }
    
    /* Filtros con hover */
    .filter-select {
        transition: all 0.2s ease;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 8px 12px;
        font-size: 0.9rem;
        cursor: pointer;
    }
    
    .filter-select:hover {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(22, 38, 96, 0.1);
    }
    
    .filter-select:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(22, 38, 96, 0.15);
        outline: none;
    }
    
    /* Botón limpiar */
    .btn-clear-filters {
        transition: all 0.2s ease;
    }
    
    .btn-clear-filters:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    /* Input de búsqueda mejorado */
    .search-input-wrapper {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 4px 12px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
    }
    
    .search-input-wrapper:focus-within {
        background-color: #fff;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(22, 38, 96, 0.1);
    }
    
    .search-input-wrapper input {
        border: none;
        background: transparent;
        width: 100%;
        padding: 8px;
        color: #374151;
        outline: none;
    }
    
    .search-input-wrapper input::placeholder {
        color: #9ca3af;
    }

    /* Ajuste del contenedor de DataTables */
    .top {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }
    
    .dataTables_filter {
        margin: 0 !important;
    }

    /* === MÓVIL (SGP Bento Mobile) === */
    @media (max-width: 991px) {
        .users-banner {
            flex-direction: column !important;
            align-items: flex-start !important;
            padding: 24px 20px !important;
            gap: 20px !important;
            margin-bottom: 20px !important;
        }
        .users-banner > div:first-child { /* Círculo decorativo */
            display: none !important;
        }
        .users-banner-actions {
            flex-direction: column !important;
            width: 100% !important;
            align-items: stretch !important;
            gap: 12px !important;
        }
        .users-banner-actions button {
            width: 100% !important;
            justify-content: center !important;
        }
        
        .kpi-users-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 12px !important;
        }
        
        .users-filter-bar {
            flex-direction: column !important;
            align-items: stretch !important;
            padding: 14px 16px !important;
            gap: 10px !important;
        }
        /* Buscador: ancho completo sin límite */
        .users-filter-bar > div:first-child {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        /* Separador vertical → ocultar en móvil */
        .users-filter-bar > div:nth-child(2) {
            display: none !important;
        }
        /* Etiqueta "Rol:" */
        .users-filter-bar > p {
            width: 100%;
            margin: 0 !important;
        }
        /* Pills: fila scrollable horizontal sin clipping */
        .users-filter-bar {
            overflow: visible !important; /* evita que border-radius corte el scroll */
        }
        #ventoFilterPills {
            /* Rompe del padding del padre para usar el ancho completo */
            width: calc(100% + 32px);
            margin-left: -16px;
            padding-left: 16px;
            padding-right: 20px; /* margen derecho para último pill */
            flex-wrap: nowrap !important;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 4px;
            scrollbar-width: none;
            scroll-snap-type: x proximity;
        }
        #ventoFilterPills::-webkit-scrollbar { display: none; }
        .vento-pill {
            flex-shrink: 0;
            white-space: nowrap;
            scroll-snap-align: start;
        }
    }
    
    @media (max-width: 575px) {
        .kpi-users-grid {
            grid-template-columns: 1fr !important;
        }
        #totalUsersBadge {
            margin-left: 0 !important;
            margin-top: 8px !important;
        }
        .users-banner p {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
    }
</style>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">

    <!-- BANNER ESTANDARIZADO SGP -->
    <div class="users-banner" style="border-radius:20px;padding:32px 40px;margin-bottom:28px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;">
        <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
        <div style="display:flex;align-items:center;gap:16px;z-index:1;">
            <div style="background:rgba(255,255,255,0.15);border-radius:14px;padding:14px;">
                <i class="ti ti-users" style="font-size:32px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.8rem;font-weight:700;margin:0;">Gestión de Usuarios</h1>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:0.9rem;display:flex;align-items:center;">
                    <i class="ti ti-shield-check" style="margin-right: 6px;"></i>
                    <span>Control de Accesos · Administración</span>
                    <span id="totalUsersBadge" style="display:inline-block; background:rgba(255,255,255,0.15); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.1); border-radius:50px; padding:4px 14px; margin-left:12px; color:white; font-weight:700; font-size:0.8rem; box-shadow:0 4px 6px rgba(0,0,0,0.05); white-space:nowrap;">
                        <?= count($users) ?> usuarios
                    </span>
                </p>
            </div>
        </div>
        <div class="users-banner-actions" style="display:flex; gap:16px; z-index:1; align-items:center;">
            <!-- Botón Secundario: Consulta Rápida -->
            <button onclick="openSearchModal()" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i class="ti ti-search" style="font-size: 1.1rem;"></i> Consulta Rápida
            </button>

            <!-- Botón Principal: Nuevo Usuario (Destacado en Blanco) -->
            <button onclick="openCreateModal()" class="btn-create-user" style="background: white; color: #1e3a8a; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'">
                <i class="ti ti-user-plus" style="font-size: 1.1rem;"></i> Nuevo Usuario
            </button>
        </div>
    </div>


    <?php
    // ==========================================================
    // OPTIMIZACIÓN: KPIs precalculados en SQL (UserModel->getUsersKPIs)
    // ==========================================================
    $activos   = $kpis['activos'] ?? 0;
    $pasantes  = $kpis['pasantes'] ?? 0;
    $tutores   = count(array_filter($users, fn($u) => strtolower($u['role_name']) === 'tutor'));
    $inactivos = $kpis['inactivos'] ?? 0;
    // ==========================================================
    ?>

    <!-- KPI CARDS ESTANDARIZADAS E INTERACTIVAS -->
    <div class="kpi-users-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;">
        <?php
        $kpis = [
            ['label' => 'Total Activos',      'val' => $activos,   'color' => '#10b981', 'boxShadow' => 'rgba(16,185,129,0.15)', 'icon' => 'ti-user-check',   'sub' => 'operando en sistema', 'filterVal' => 'activo'],
            ['label' => 'Pasantes',           'val' => $pasantes,  'color' => '#2563eb', 'boxShadow' => 'rgba(37,99,235,0.15)',  'icon' => 'ti-school',       'sub' => 'métrica principal',   'filterVal' => 'Pasante'],
            ['label' => 'Tutores Disponibles','val' => $tutores,   'color' => '#f59e0b', 'boxShadow' => 'rgba(245,158,11,0.15)', 'icon' => 'ti-briefcase',    'sub' => 'capacitadores',       'filterVal' => 'Tutor'],
            ['label' => 'Inactivos',          'val' => $inactivos, 'color' => '#dc2626', 'boxShadow' => 'rgba(220,38,38,0.15)',  'icon' => 'ti-user-off',     'sub' => 'requiere atención',   'filterVal' => 'inactivo'],
        ];
        foreach ($kpis as $k): 
        ?>
        <div class="kpi-card kpi-card-user" style="border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid <?= $k['color'] ?>;transition:all 0.3s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 25px <?= $k['boxShadow'] ?>'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <p style="color:#64748b;font-size:0.82rem;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?= $k['label'] ?></p>
                <i class="ti <?= $k['icon'] ?>" style="color:<?= $k['color'] ?>;font-size:1.4rem;opacity:0.7;"></i>
            </div>
            <h2 style="font-size:2.4rem;font-weight:800;color:<?= $k['color'] ?>;margin:0;" data-kpi-value="<?= $k['val'] ?>"><?= $k['val'] ?></h2>
            <p style="color:#94a3b8;font-size:0.8rem;margin:4px 0 0;"><?= $k['sub'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Filtros Rápidos (Cápsulas) + Buscador -->
    <div class="users-filter-bar" style="margin-bottom: 20px; display: flex; align-items: center; gap: 16px; background: white; padding: 16px 24px; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); flex-wrap: wrap;">
        <!-- Buscador -->
        <div style="position: relative; flex: 1; min-width: 220px; max-width: 320px;">
            <i class="ti ti-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1rem; pointer-events: none;"></i>
            <input
                type="text"
                id="globalSearch"
                placeholder="Buscar por nombre, cédula o correo…"
                autocomplete="off"
                style="width: 100%; height: 40px; padding: 0 36px 0 40px; border: 1.5px solid #e2e8f0; border-radius: 50px; background: #f8fafc; color: #0d1424; font-size: 0.85rem; font-weight: 500; outline: none; transition: border-color .18s, box-shadow .18s;"
                onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,.1)'; this.style.background='#fff';"
                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'; this.style.background='#f8fafc';"
            >
            <button id="clearSearch" onclick="document.getElementById('globalSearch').value=''; document.getElementById('globalSearch').dispatchEvent(new Event('input'));" style="display:none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: #e2e8f0; border: none; width: 22px; height: 22px; border-radius: 50%; color: #64748b; cursor: pointer; align-items: center; justify-content: center; font-size: 0.75rem; padding: 0;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Separador vertical -->
        <div style="width: 1px; height: 28px; background: #e2e8f0; flex-shrink: 0;"></div>

        <!-- Pills de Rol -->
        <p style="font-size: 0.82rem; font-weight: 700; color: #94a3b8; margin: 0; white-space: nowrap;">
            <i class="ti ti-filter" style="font-size:1rem; color:#2563eb;"></i> Rol:
        </p>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;" id="ventoFilterPills">
            <button class="vento-pill active" data-role="" onclick="filtrarPorRol('Todos')" style="background: #2563eb; color: white; border: none; padding: 7px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                Todos
            </button>
            <button class="vento-pill" data-role="Administrador" onclick="filtrarPorRol('Administrador')" style="background: #f1f5f9; color: #475569; border: none; padding: 7px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="if(!this.classList.contains('active')) {this.style.background='#e2e8f0'}" onmouseout="if(!this.classList.contains('active')) {this.style.background='#f1f5f9'}">
                Administradores
            </button>
            <button class="vento-pill" data-role="Tutor" onclick="filtrarPorRol('Tutor')" style="background: #f1f5f9; color: #475569; border: none; padding: 7px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="if(!this.classList.contains('active')) {this.style.background='#e2e8f0'}" onmouseout="if(!this.classList.contains('active')) {this.style.background='#f1f5f9'}">
                Tutores
            </button>
            <button class="vento-pill" data-role="Pasante" onclick="filtrarPorRol('Pasante')" style="background: #f1f5f9; color: #475569; border: none; padding: 7px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="if(!this.classList.contains('active')) {this.style.background='#e2e8f0'}" onmouseout="if(!this.classList.contains('active')) {this.style.background='#f1f5f9'}">
                Pasantes
            </button>
        </div>
    </div>

    <!-- BLOQUE 3: TABLA DE DATOS — solo en desktop (≥ 992px) -->
    <div class="card border-0 shadow-soft rounded-xl overflow-hidden sgp-solo-desktop">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle mb-0" style="width:100%; opacity: 0; transition: opacity 0.4s ease-in-out;">
                <thead class="bg-light text-uppercase text-muted small fw-bold">
                    <tr>
                        <th class="px-4 py-3 border-0">Nombre</th>
                        <th class="px-4 py-3 border-0">Rol</th>
                        <th class="px-4 py-3 border-0">Departamento / Institución</th>
                        <th class="px-4 py-3 border-0">Estado</th>
                        <th class="px-4 py-3 border-0 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php foreach ($users as $user):
                        $inicial = strtoupper(mb_substr($user['name'] ?? 'U', 0, 1));
                    ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="dt-name-cell">
                                <div class="dt-avatar"><?= $inicial ?></div>
                                <div>
                                    <div class="dt-cell-primary"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="dt-cell-secondary"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $rolMap = [
                                    'Administrador' => ['bg' => '#eff6ff', 'color' => '#2563eb', 'icon' => 'ti-shield-check'],
                                    'Tutor'         => ['bg' => '#fef9c3', 'color' => '#ca8a04', 'icon' => 'ti-briefcase'],
                                    'Pasante'       => ['bg' => '#e0f2fe', 'color' => '#0284c7', 'icon' => 'ti-school'],
                                ];
                                $rCfg = $rolMap[$user['role_name']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'icon' => 'ti-user'];
                            ?>
                            <span class="badge" style="background:<?= $rCfg['bg'] ?>; color:<?= $rCfg['color'] ?>; padding: 6px 12px; font-weight: 700; border-radius: 8px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="ti <?= $rCfg['icon'] ?>" style="font-size: 1rem;"></i> <?= htmlspecialchars($user['role_name']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $infoContextual = '';
                                $icon = 'ti-building';
                                if (in_array($user['role_id'], [1, 2])) {
                                    $infoContextual = $user['departamento_nombre'] ?? '';
                                    $icon = 'ti-hierarchy-2';
                                } else {
                                    $infoContextual = $user['institucion_nombre'] ?? '';
                                    $icon = 'ti-school';
                                }
                            ?>
                            <?php if (!empty($infoContextual)): ?>
                                <span class="dt-cell-truncate text-dark fw-medium" title="<?= htmlspecialchars($infoContextual) ?>">
                                    <i class="ti <?= $icon ?> text-muted me-1"></i>
                                    <?= htmlspecialchars($infoContextual) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small italic">
                                    <i class="ti ti-alert-circle me-1"></i> Sin asignar
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $estadoMap = [
                                    'activo'   => ['bg' => '#eff6ff', 'color' => '#2563eb', 'text' => 'Activo',   'dot' => 'active'],
                                    'inactivo' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'text' => 'Inactivo', 'dot' => 'inactive'],
                                ];
                                $eStr = strtolower($user['estado']);
                                $eCfg = $estadoMap[$eStr] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'text' => ucfirst($eStr), 'dot' => 'inactive'];
                            ?>
                            <span class="badge" style="background:<?= $eCfg['bg'] ?>; color:<?= $eCfg['color'] ?>; padding: 6px 12px; font-weight: 700; border-radius: 8px; font-size: 0.85rem; display: inline-flex; align-items: center;">
                                <span class="status-dot <?= $eCfg['dot'] ?>"></span> <?= $eCfg['text'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center align-middle">
                            <div class="d-flex justify-content-center" style="gap: 12px;">
                                <button class="btn btn-sm border-0 shadow-sm transition-all" data-bs-toggle="tooltip" title="Ver Perfil" onclick="SGPModal.verUsuario(<?= $user['id'] ?>)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #2563eb; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-eye fs-5 text-white"></i>
                                </button>
                                <button class="btn btn-sm border-0 shadow-sm transition-all" data-bs-toggle="tooltip" title="Editar Usuario" onclick="editUser('<?= UrlSecurity::encrypt($user['id']) ?>')" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #f59e0b; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-pencil fs-5 text-white"></i>
                                </button>
                                <button class="btn btn-sm border-0 shadow-sm transition-all" data-bs-toggle="tooltip" title="Cambiar Clave" onclick="resetUser('<?= UrlSecurity::encrypt($user['id']) ?>')" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #64748b; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-key fs-5 text-white"></i>
                                </button>
                                <?php if($user['estado'] == 'activo'): ?>
                                    <button class="btn btn-sm border-0 shadow-sm transition-all" data-bs-toggle="tooltip" title="Desactivar" onclick="toggleUser('<?= UrlSecurity::encrypt($user['id']) ?>', 'desactivar')" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #dc2626; color: #ffffff; border-radius: 6px !important;">
                                        <i class="ti ti-user-off fs-5 text-white"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm border-0 shadow-sm transition-all" data-bs-toggle="tooltip" title="Activar" onclick="toggleUser('<?= UrlSecurity::encrypt($user['id']) ?>', 'activar')" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #16a34a; color: #ffffff; border-radius: 6px !important;">
                                        <i class="ti ti-user-check fs-5 text-white"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm border-0 shadow-sm transition-all" data-bs-toggle="tooltip" title="Eliminar Permanente" onclick="confirmarEliminarUsuario('<?= UrlSecurity::encrypt($user['id']) ?>', '<?= addslashes(htmlspecialchars(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? $user['correo'] ?? ''))) ?>', <?= intval($user['role_id']) ?>)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px !important;">
                                    <i class="ti ti-trash fs-5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top border-light p-3">
            <!-- DataTables pagination will be injected here -->
        </div>
    </div>

    <!-- CARD VIEW — solo en móvil (< 992px) -->
    <div class="sgp-solo-mobile gap-3 pb-3" id="cardsUsers">
    <?php
    $rolMap = [
        'Administrador' => ['bg' => '#eff6ff', 'color' => '#2563eb', 'grad' => 'linear-gradient(135deg,#162660,#2563eb)'],
        'Tutor'         => ['bg' => '#fef3c7', 'color' => '#d97706', 'grad' => 'linear-gradient(135deg,#78350f,#f59e0b)'],
        'Pasante'       => ['bg' => '#e0f2fe', 'color' => '#0284c7', 'grad' => 'linear-gradient(135deg,#0c4a6e,#0ea5e9)'],
    ];
    foreach ($users as $user):
        $nombre    = htmlspecialchars($user['name'] ?? '');
        $email     = htmlspecialchars($user['email'] ?? '');
        $rol       = $user['role_name'] ?? 'Usuario';
        $estado    = $user['estado'] ?? 'inactivo';
        $rcfg      = $rolMap[$rol] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'grad' => 'linear-gradient(135deg,#475569,#94a3b8)'];
        $estadoBg  = $estado === 'activo' ? '#eff6ff' : '#fef2f2';
        $estadoClr = $estado === 'activo' ? '#2563eb' : '#dc2626';
        $ini       = strtoupper(mb_substr($nombre, 0, 1));
        if (in_array($user['role_id'], [1, 2])) {
            $infoCtx  = htmlspecialchars($user['departamento_nombre'] ?? '');
            $infoIcon = 'ti-hierarchy-2';
            $infoLbl  = 'Departamento';
        } else {
            $infoCtx  = htmlspecialchars($user['institucion_nombre'] ?? '');
            $infoIcon = 'ti-school';
            $infoLbl  = 'Institución';
        }
    ?>
    <div class="bcu-card" data-rol="<?= htmlspecialchars($rol) ?>" data-estado="<?= htmlspecialchars($estado) ?>">

        <!-- Encabezado: avatar + nombre + badge rol -->
        <div class="bcu-header">
            <div class="bcu-avatar" style="background:<?= $rcfg['grad'] ?>;"><?= $ini ?></div>
            <div class="bcu-info">
                <span class="bcu-nombre"><?= $nombre ?></span>
                <span class="bcu-email"><?= $email ?></span>
            </div>
            <span class="bcu-badge"
                  style="background:<?= $rcfg['bg'] ?>; color:<?= $rcfg['color'] ?>;">
                <?= htmlspecialchars($rol) ?>
            </span>
        </div>

        <!-- Cuerpo -->
        <div class="bcu-body">
            <?php if (!empty($infoCtx)): ?>
            <div class="bcu-row">
                <span class="bcu-label"><?= $infoLbl ?></span>
                <span class="bcu-value">
                    <i class="ti <?= $infoIcon ?>" style="font-size:.75rem; color:#94a3b8; margin-right:3px;"></i>
                    <?= $infoCtx ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="bcu-row">
                <span class="bcu-label">Estado</span>
                <?php
                    $mobileDot = $estado === 'activo' ? 'active' : 'inactive';
                ?>
                <span class="bcu-badge"
                      style="background:<?= $estadoBg ?>; color:<?= $estadoClr ?>; display: inline-flex; align-items: center;">
                    <span class="status-dot <?= $mobileDot ?>"></span> <?= ucfirst($estado) ?>
                </span>
            </div>
        </div>

        <!-- Acciones — mismas funciones JS que la tabla -->
        <div class="bcu-actions">
            <button class="bcu-btn bcu-btn-view"
                    onclick="SGPModal.verUsuario(<?= $user['id'] ?>)"
                    title="Ver perfil">
                <i class="ti ti-eye"></i> Ver
            </button>
            <button class="bcu-btn bcu-btn-edit"
                    onclick="editUser('<?= UrlSecurity::encrypt($user['id']) ?>')"
                    title="Editar">
                <i class="ti ti-pencil"></i> Editar
            </button>
            <button class="bcu-btn bcu-btn-key bcu-btn-icon"
                    onclick="resetUser('<?= UrlSecurity::encrypt($user['id']) ?>')"
                    title="Cambiar clave">
                <i class="ti ti-key"></i>
            </button>
            <?php if ($estado === 'activo'): ?>
                <button class="bcu-btn bcu-btn-toggle-off bcu-btn-icon"
                        onclick="toggleUser('<?= UrlSecurity::encrypt($user['id']) ?>', 'desactivar')"
                        title="Desactivar">
                    <i class="ti ti-user-off"></i>
                </button>
            <?php else: ?>
                <button class="bcu-btn bcu-btn-toggle-on bcu-btn-icon"
                        onclick="toggleUser('<?= UrlSecurity::encrypt($user['id']) ?>', 'activar')"
                        title="Activar">
                    <i class="ti ti-user-check"></i>
                </button>
            <?php endif; ?>
            <button class="bcu-btn bcu-btn-icon" style="color:#dc2626;border-color:#fecaca;background:#fef2f2;"
                    onclick="confirmarEliminarUsuario('<?= UrlSecurity::encrypt($user['id']) ?>', '<?= addslashes(htmlspecialchars(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? $user['correo'] ?? ''))) ?>', <?= intval($user['role_id']) ?>)"
                    title="Eliminar permanente">
                <i class="ti ti-trash"></i>
            </button>
        </div>

    </div>
    <?php endforeach; ?>
    </div>

</div>

<!-- Create User Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon">
                    <i class="ti ti-user-plus"></i>
                </div>
                <div>
                    <h2 class="modal-title">Crear Usuario</h2>
                    <p class="modal-subtitle">Complete los datos del nuevo usuario</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeCreateModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <div class="modal-body-scroll">
        <form id="createUserForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    Nombre *
                </label>
                <input type="text" name="nombre" id="nombreCreate" required class="input-modern" 
                       placeholder="Juan" 
                       pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" 
                       maxlength="100"
                       title="Solo letras y espacios">
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    Apellido *
                </label>
                <input type="text" name="apellido" id="apellidoCreate" required class="input-modern" 
                       placeholder="Pérez" 
                       pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" 
                       maxlength="100"
                       title="Solo letras y espacios">
            </div>
            
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    Cédula * <small>(Base para contraseña temporal)</small>
                </label>
                <input type="text" name="cedula" id="cedulaCreate" required
                       inputmode="numeric"
                       pattern="[0-9]{7,8}"
                       maxlength="8"
                       class="input-modern"
                       placeholder="12345678"
                       title="Entre 7 y 8 dígitos numéricos"
                       autocomplete="off">
                <div id="cedulaFeedback" style="min-height: 20px; font-size: 0.82rem; margin-top: 5px; font-weight: 600;"></div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    Correo *
                </label>
                <input type="email" name="correo" id="correoCreate" required class="input-modern" placeholder="usuario@ejemplo.com" autocomplete="off">
                <div id="correoFeedback" style="min-height: 20px; font-size: 0.82rem; margin-top: 5px; font-weight: 600;"></div>
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    Rol *
                </label>
                <select name="rol_id" id="create_rol_id" required class="input-modern">
                    <option value="">Seleccione...</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role->id ?>"><?= htmlspecialchars($role->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Departamento (solo para Tutores) -->
            <div class="form-group" id="departamento-group" style="margin-bottom: 24px; display: none;">
                <label style="display: block; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    Departamento *
                </label>
                <select name="departamento_id" id="create_departamento_id" class="input-modern">
                    <option value="">Seleccione...</option>
                    <?php foreach ($departamentos as $depto): ?>
                        <option value="<?= $depto->id ?>"><?= htmlspecialchars($depto->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Institución (solo para Pasantes) -->
            <div class="form-group" id="institucion-group" style="margin-bottom: 24px; display: none;">
                <label style="display: block; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    <i class="ti ti-building-community" style="margin-right:5px;color:#7c3aed;"></i>Institución de procedencia
                </label>
                <select name="institucion_id" id="create_institucion_id" class="input-modern">
                    <option value="">Sin especificar (el pasante la completará)</option>
                    <?php foreach ($instituciones ?? [] as $inst): ?>
                        <option value="<?= $inst->id ?>"><?= htmlspecialchars($inst->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <p style="margin:6px 0 0;font-size:.75rem;color:#7c3aed;display:flex;align-items:center;gap:4px;">
                    <i class="ti ti-info-circle"></i>
                    Si la seleccionas ahora, el pasante ya aparecerá con institución en Asignaciones.
                </p>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;background:linear-gradient(135deg,#172554 0%,#1e3a8a 100%);color:white;padding:14px;font-size:1rem;">
                <i class="ti ti-check"></i> Crear Usuario
            </button>
        </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon">
                    <i class="ti ti-edit"></i>
                </div>
                <div>
                    <h2 class="modal-title">Editar Usuario</h2>
                    <p class="modal-subtitle">Modifique los datos del usuario seleccionado</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <div class="modal-body-scroll">
            <form id="editUserForm">
                <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="edit_nombres" class="input-modern" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Apellido</label>
                    <input type="text" name="apellido" id="edit_apellidos" class="input-modern" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display:flex;align-items:center;gap:6px;">
                        Cédula
                        <?php $puedeEditarCedula = in_array((int)Session::get('role_id'), [0, 1]); ?>
                        <?php if ($puedeEditarCedula): ?>
                        <span style="background:#fef3c7;color:#b45309;font-size:.65rem;font-weight:700;padding:1px 7px;border-radius:10px;letter-spacing:.3px;">
                            <i class="ti ti-alert-triangle" style="font-size:.7rem;"></i> CAMPO SENSIBLE
                        </span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="cedula" id="edit_cedula" class="input-modern" required pattern="[0-9]{7,8}"
                           <?= !$puedeEditarCedula ? 'readonly title="Solo Administradores pueden editar la cédula"' : '' ?>
                           data-cedula-original="">
                    <?php if ($puedeEditarCedula): ?>
                    <div id="cedula_change_warn" style="display:none;margin-top:5px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:7px 10px;font-size:.75rem;color:#92400e;font-weight:600;">
                        <i class="ti ti-alert-triangle"></i> Cambiar la cédula modifica el identificador principal del usuario. Requiere confirmación.
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" id="edit_correo" class="input-modern" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Rol</label>
                    <select name="rol_id" id="edit_rol_id" class="input-modern" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role->id ?>"><?= htmlspecialchars($role->nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Departamento (Solo para Tutores) -->
                <div id="edit-departamento-group" class="form-group" style="margin-bottom: 24px; display: none;">
                    <label>Departamento</label>
                    <select name="departamento_id" id="edit_departamento_id" class="input-modern">
                        <option value="">Seleccione...</option>
                        <?php foreach ($departamentos as $depto): ?>
                            <option value="<?= $depto->id ?>"><?= htmlspecialchars($depto->nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary" style="width:100%;justify-content:center;background:linear-gradient(135deg,#172554 0%,#1e3a8a 100%);color:white;padding:14px;font-size:1rem;">
                    <i class="ti ti-device-floppy"></i> Guardar Cambios
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Toast: usando NotificationService global (main_layout.php)
    
    // Initialize DataTable
    $(document).ready(function() {
        var dtOptions = {
            language: {
                url: '<?= URLROOT ?>/assets/libs/datatables/es-ES.json'
            },
            dom: '<"top"f>rt<"bottom"ip><"clear">',
            order: [[0, 'asc']], // Ordenar por Nombre por defecto
            responsive: true,
            pageLength: 10,
            columnDefs: [
                { responsivePriority: 1, targets: 1 },   // Nombre — siempre visible
                { responsivePriority: 2, targets: -1 },  // Acciones — siempre visible
                { responsivePriority: 3, targets: 4 },   // Estado
                { responsivePriority: 4, targets: 2 },   // Rol
                { responsivePriority: 8, targets: 3 },   // Institución
                { orderable: false, targets: -1 }
            ],
            drawCallback: function() {
                var api = this.api();
                if(api.rows({filter: 'applied'}).count) {
                    var count = api.rows({filter: 'applied'}).count();
                    $('#resultCount').text(count);
                }
                
                // Initialize Bootstrap Tooltips for the newly drawn rows
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            },
            initComplete: function(settings, json) {
                $(this.api().table().node()).css('opacity', '1');
            }
        };

        // Se inyecta solo la 'f' (filtro) en la parte superior
        dtOptions.dom = '<"top"f>rt<"bottom"ip><"clear">';

        // SGP-FIX DATATABLES CELLINDEX
        var $tablaUsers = $('#usersTable');
        if ($tablaUsers.length && !$.fn.DataTable.isDataTable($tablaUsers)) {
            window.table = $tablaUsers.DataTable(dtOptions);
        } else if ($tablaUsers.length && $.fn.DataTable.isDataTable($tablaUsers)) {
            window.table = $tablaUsers.DataTable();
            window.table.draw(false);
        }

        // SGP-FIX: recalcular columnas al pasar de móvil a desktop
        // (DataTable inicializa con display:none en móvil → columnas en ancho 0)
        var _dtAdjusted = false;
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992 && !_dtAdjusted && window.table) {
                window.table.columns.adjust().draw(false);
                _dtAdjusted = true;
            }
            if (window.innerWidth < 992) { _dtAdjusted = false; }
        });
        
        // Función para actualizar chips de filtros activos
        function updateChips() {
            var chips = '';
            var role = $('#filterRole').val();
            var status = $('#filterStatus').val();
            var search = $('#globalSearch').val();

            if (role) chips += `<span style="background:#eff6ff;color:#2563eb;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;display:flex;align-items:center;gap:5px;">
                <i class="ti ti-user-circle"></i>${role}
                <i class="ti ti-x" style="cursor:pointer;font-size:0.75rem;" onclick="$('#filterRole').val('').trigger('change')"></i></span>`;
            if (status) chips += `<span style="background:#f0fdf4;color:#16a34a;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;display:flex;align-items:center;gap:5px;">
                <i class="ti ti-circle-check"></i>${status.charAt(0).toUpperCase()+status.slice(1)}
                <i class="ti ti-x" style="cursor:pointer;font-size:0.75rem;" onclick="$('#filterStatus').val('').trigger('change')"></i></span>`;
            if (search) chips += `<span style="background:#faf5ff;color:#7c3aed;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;display:flex;align-items:center;gap:5px;">
                <i class="ti ti-search"></i>"${search}"
                <i class="ti ti-x" style="cursor:pointer;font-size:0.75rem;" onclick="$('#clearSearch').click()"></i></span>`;
            $('#activeFilterChips').html(chips);
        }

        // Inicializar contador
        $('#resultCount').text(table.rows().count());

        // 1. Global Search — mostrar/ocultar botón X + actualizar chips
        $('#globalSearch').on('input keyup change', function() {
            table.search(this.value).draw();
            document.getElementById('clearSearch').style.display = this.value ? 'block' : 'none';
            updateChips();
        });

        // 2. Role Filter (Column 2)
        $('#filterRole').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            table.column(2).search(val ? val : '', true, false).draw();
            updateChips();
        });

        // 3. Status Filter (Column 4)
        $('#filterStatus').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            table.column(4).search(val ? val : '', true, false).draw();
            updateChips();
        });

        // 4. Clear Filters Button
        $('#clearFilters').on('click', function() {
            $('#globalSearch').val('');
            $('#filterRole').val('');
            $('#filterStatus').val('');
            document.getElementById('clearSearch').style.display = 'none';
            table.search('').columns().search('').draw();
            updateChips();
        });

        // Prevent form submission on enter in search
        $('#globalSearch').on('keypress', function(e) {
            if(e.which == 13) { e.preventDefault(); return false; }
        });
    });
    
    // Create Modal Functions
    function openCreateModal() {
        document.getElementById('createModal').classList.add('active');
    }
    
    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('active');
        document.getElementById('createUserForm').reset();
        setFieldFeedback('cedulaCreate', 'cedulaFeedback', '', 'clear');
        setFieldFeedback('correoCreate', 'correoFeedback', '', 'clear');
        // Reset campos condicionales
        const ig = document.getElementById('institucion-group');
        const dg = document.getElementById('departamento-group');
        if (ig) ig.style.display = 'none';
        if (dg) dg.style.display = 'none';
    }

    // ── Validación en tiempo real: cédula y correo ─────────────────
    // Función utilitaria para mostrar/ocultar el feedback visual bajo el input
    function setFieldFeedback(inputId, feedbackId, message, type) {
        const input    = document.getElementById(inputId);
        const feedback = document.getElementById(feedbackId);
        if (!input || !feedback) return;

        feedback.textContent = message;

        // Colores según estado
        const styles = {
            error   : { color: '#dc2626', border: '#dc2626', bg: '#fff1f2' },
            success : { color: '#16a34a', border: '#16a34a', bg: '#f0fdf4' },
            loading : { color: '#2563eb', border: '#2563eb', bg: '#eff6ff' },
            hint    : { color: '#64748b', border: '#cbd5e1', bg: '#fff'    },
            clear   : { color: '',        border: '',        bg: ''         },
        };
        const s = styles[type] || styles.clear;
        feedback.style.color        = s.color;
        input.style.borderColor     = s.border;
        input.style.backgroundColor = s.bg || '#fff';
    }

    // Debounce: espera N ms tras dejar de escribir antes de ejecutar fn
    function debounce(fn, delay) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // Verificación AJAX de unicidad
    function checkUnique(campo, valor, inputId, feedbackId, emptyMsg) {
        if (!valor) { setFieldFeedback(inputId, feedbackId, '', 'clear'); return; }

        setFieldFeedback(inputId, feedbackId, 'Verificando...', 'loading');

        fetch(`<?= URLROOT ?>/users/checkUnique?campo=${campo}&valor=${encodeURIComponent(valor)}`)
            .then(r => r.json())
            .then(data => {
                if (data.skip) { setFieldFeedback(inputId, feedbackId, '', 'clear'); return; }
                if (data.available) {
                    setFieldFeedback(inputId, feedbackId, '✓ Disponible', 'success');
                } else {
                    setFieldFeedback(inputId, feedbackId, `✗ ${emptyMsg}`, 'error');
                }
            })
            .catch(() => setFieldFeedback(inputId, feedbackId, '', 'clear'));
    }

    // Listener: Cédula — solo acepta dígitos y valida longitud antes de consultar
    document.getElementById('cedulaCreate').addEventListener('input', function() {
        // Eliminar cualquier carácter no numérico mientras escribe
        this.value = this.value.replace(/\D/g, '');

        const val = this.value;
        if (val.length === 0) {
            setFieldFeedback('cedulaCreate', 'cedulaFeedback', '', 'clear');
            return;
        }
        if (val.length < 7) {
            // Hint neutro mientras sigue escribiendo — aún no es un error confirmado
            setFieldFeedback('cedulaCreate', 'cedulaFeedback', 'Entre 7 y 8 dígitos', 'hint');
            return;
        }
        debouncedCedula(val);
    });

    // Al salir del campo: si sigue corto, ahora sí marcar como error
    document.getElementById('cedulaCreate').addEventListener('blur', function() {
        const val = this.value;
        if (val.length > 0 && val.length < 7) {
            setFieldFeedback('cedulaCreate', 'cedulaFeedback', 'Máximo 8 dígitos', 'error');
        }
    });

    // Listener: Correo — hint neutro mientras escribe, error al salir con formato inválido
    document.getElementById('correoCreate').addEventListener('input', debounce(function() {
        const val = this.value.trim();
        if (!val) { setFieldFeedback('correoCreate', 'correoFeedback', '', 'clear'); return; }
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        if (!emailOk) {
            setFieldFeedback('correoCreate', 'correoFeedback', 'Ej: usuario@dominio.com', 'hint');
            return;
        }
        checkUnique('correo', val, 'correoCreate', 'correoFeedback', 'Este correo ya está registrado');
    }, 500));

    document.getElementById('correoCreate').addEventListener('blur', function() {
        const val = this.value.trim();
        if (val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            setFieldFeedback('correoCreate', 'correoFeedback', 'Formato de correo inválido', 'error');
        }
    });

    var debouncedCedula = debounce(function(val) { // [SGP-FIX-PJAX-REDECL]
        checkUnique('cedula', val, 'cedulaCreate', 'cedulaFeedback', 'Esta cédula ya está registrada');
    }, 500);
    
    // Create User Form Submit
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Bloquear envío si hay errores de unicidad pendientes
        const cedulaFb = document.getElementById('cedulaFeedback');
        const correoFb = document.getElementById('correoFeedback');
        const hayError = (el) => el && el.style.color === 'rgb(220, 38, 38)'; // #dc2626
        if (hayError(cedulaFb) || hayError(correoFb)) {
            NotificationService.error('Corrige los errores indicados antes de continuar.');
            return;
        }

        const formData = new FormData(this);
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-loader animate-spin"></i> Creando...';
        btn.disabled = true;
        
        fetch('<?= URLROOT ?>/users/create', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Extraer la contraseña temporal del mensaje
                // El mensaje tiene formato: "Usuario creado exitosamente. Contraseña temporal: Sgp.XXXXXXXX"
                const match = data.message.match(/Contraseña temporal:\s*(\S+)/);
                const tempPass = match ? match[1] : '';

                closeCreateModal();

                Swal.fire({
                    icon: 'success',
                    title: 'Usuario Creado',
                    html: `
                        <p style="margin-bottom:12px;">El usuario fue creado correctamente. La credencial inicial es:</p>
                        <div class="swal-token-row">
                            <div class="swal-bento-token-long" id="swal-token-create">${tempPass}</div>
                            <button class="swal-copy-btn" id="swal-copy-create" title="Copiar credencial" onclick="(function(btn){navigator.clipboard.writeText('${tempPass}').then(()=>{btn.innerHTML='<i class=\\'ti ti-check\\'></i>';btn.classList.add('copied');setTimeout(()=>{btn.innerHTML='<i class=\\'ti ti-copy\\'></i>';btn.classList.remove('copied');},2000);})})(this)">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                        <p style="font-size:0.8rem; margin-top:12px;">
                            <i>Deberá cambiarla en su primer inicio de sesión.</i>
                        </p>
                    `,
                    confirmButtonText: 'Entendido',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    location.reload();
                });
            } else {
                NotificationService.error(data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            NotificationService.error('Error al crear usuario');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
    
    // ── Protección de cambio de rol: Admin/Tutor ↔ Pasante es incompatible ──
    // Guarda el rol original al abrir el modal
    var _editRolOriginal = null; // [SGP-FIX-PJAX-REDECL]

    function rolFamilia(rolId) {
        // Familia A: Admin(1) y Tutor(2)  |  Familia B: Pasante(3)
        return parseInt(rolId) === 3 ? 'B' : 'A';
    }

    document.getElementById('edit_rol_id').addEventListener('change', function() {
        if (_editRolOriginal === null) return;
        const nuevoRol = this.value;
        if (nuevoRol && rolFamilia(nuevoRol) !== rolFamilia(_editRolOriginal)) {
            const nombres = {1:'Administrador', 2:'Tutor', 3:'Pasante'};
            Swal.fire({
                icon: 'error',
                title: 'Cambio de rol no permitido',
                html: `No es posible cambiar de <strong>${nombres[_editRolOriginal]}</strong>
                       a <strong>${nombres[nuevoRol]}</strong>.<br><br>
                       Cada perfil requiere datos distintos (PIN, institución, cargo, departamento).
                       Si necesitas hacer este cambio, elimina el usuario y créalo de nuevo con el rol correcto.`,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#172554'
            });
            // Revertir al rol original
            this.value = _editRolOriginal;

            // Restaurar visibilidad del departamento según rol revertido
            syncDeptoGroup(_editRolOriginal);
            return;
        }
        syncDeptoGroup(nuevoRol);
    });

    function syncDeptoGroup(rolId) {
        const editDeptoGroup  = document.getElementById('edit-departamento-group');
        const editDeptoSelect = document.getElementById('edit_departamento_id');
        if (rolId == '2') {
            editDeptoGroup.style.display = 'block';
            editDeptoSelect.required = true;
        } else {
            editDeptoGroup.style.display = 'none';
            editDeptoSelect.required = false;
            editDeptoSelect.value = '';
        }
    }

    // Edit User Functions
    function editUser(encryptedId) {
        fetch('<?= URLROOT ?>/users/edit/' + encryptedId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Guardar rol original para la protección de cambio
                    _editRolOriginal = data.data.rol_id;

                    // Fill all fields
                    document.getElementById('edit_id').value = data.data.id;
                    document.getElementById('edit_nombres').value = data.data.nombres || '';
                    document.getElementById('edit_apellidos').value = data.data.apellidos || '';
                    const cedulaInput = document.getElementById('edit_cedula');
                    cedulaInput.value = data.data.cedula || '';
                    cedulaInput.dataset.cedulaOriginal = data.data.cedula || '';
                    // Ocultar advertencia de cambio al abrir
                    const cedulaWarn = document.getElementById('cedula_change_warn');
                    if (cedulaWarn) cedulaWarn.style.display = 'none';
                    document.getElementById('edit_correo').value = data.data.correo;
                    document.getElementById('edit_rol_id').value = data.data.rol_id;

                    // Handle department visibility and value
                    const editDeptoGroup = document.getElementById('edit-departamento-group');
                    const editDeptoSelect = document.getElementById('edit_departamento_id');

                    if (data.data.rol_id == '2') { // Tutor
                        editDeptoGroup.style.display = 'block';
                        editDeptoSelect.required = true;
                        editDeptoSelect.value = data.data.departamento_id || '';
                    } else {
                        editDeptoGroup.style.display = 'none';
                        editDeptoSelect.required = false;
                        editDeptoSelect.value = '';
                    }

                    document.getElementById('editModal').classList.add('active');
                } else {
                    NotificationService.error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                NotificationService.error('Error al cargar datos del usuario');
            });
    }



    function closeViewModal() {
        document.getElementById('modalVerUsuario').classList.remove('active');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }
    
    // Detectar cambio de cédula y mostrar advertencia en tiempo real
    document.getElementById('edit_cedula')?.addEventListener('input', function() {
        const warn = document.getElementById('cedula_change_warn');
        if (!warn) return;
        warn.style.display = this.value !== this.dataset.cedulaOriginal ? 'block' : 'none';
    });

    // Edit User Form Submit — con confirmación si cambia la cédula
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;

        const cedulaInput    = document.getElementById('edit_cedula');
        const cedulaActual   = cedulaInput?.dataset.cedulaOriginal || '';
        const cedulaNueva    = cedulaInput?.value.trim() || '';
        const cambioCedula   = cedulaActual && cedulaNueva && cedulaNueva !== cedulaActual;

        const ejecutarUpdate = () => {
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-loader animate-spin"></i> Actualizando...';
            btn.disabled = true;
            fetch('<?= URLROOT ?>/users/update', { method:'POST', body:formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    NotificationService.success(data.message);
                    closeEditModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    NotificationService.error(data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        };

        if (!cambioCedula) {
            ejecutarUpdate();
            return;
        }

        // Confirmación antes de cambiar cédula
        Swal.fire({
            title: '¿Cambiar cédula del usuario?',
            html: `<div style="font-size:.9rem;color:#475569;text-align:left;line-height:1.7;">
                       <p style="margin:0 0 12px;">Estás a punto de cambiar la <strong>cédula de identidad</strong>, que es el identificador principal del usuario en el sistema:</p>
                       <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;margin-bottom:12px;">
                           <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;">
                               <div><div style="font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Cédula actual</div>
                               <div style="font-size:1.1rem;font-weight:800;color:#dc2626;">${cedulaActual}</div></div>
                               <i class="ti ti-arrow-right" style="color:#94a3b8;font-size:1.2rem;"></i>
                               <div><div style="font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Cédula nueva</div>
                               <div style="font-size:1.1rem;font-weight:800;color:#059669;">${cedulaNueva}</div></div>
                           </div>
                       </div>
                       <p style="margin:0;font-size:.82rem;color:#b45309;background:#fef3c7;padding:8px 12px;border-radius:8px;">
                           <i class="ti ti-alert-triangle"></i> La contraseña del usuario podría verse afectada si está basada en la cédula. Notifícale el cambio.
                       </p>
                   </div>`,
            icon: 'warning',
            iconColor: '#d97706',
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-check"></i> Sí, cambiar cédula',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
        }).then(result => {
            if (result.isConfirmed) ejecutarUpdate();
        });
    });
    
    // Reset User Password
    function resetUser(encryptedId) {
        Swal.fire({
            title: '<i class="ti ti-lock"></i> Restablecer credenciales',
            html: '¿Estás seguro de blanquear el acceso de esta cuenta?<br>La contraseña volverá a ser:<br><br>' +
                  '<div style="font-size: 1.4rem; font-weight: 800; color: #db2777; letter-spacing: 0.1em; background-color: #fdf2f8; padding: 1rem 1.5rem; border-radius: 16px; border: 2px dashed #fbcfe8; display: inline-block; margin: 1.5rem 0 1rem 0;">Sgp.[Cédula]</div><br>' +
                  '<span style="font-size: 0.85rem; color: #64748b;">El usuario deberá cambiarla en su próximo inicio de sesión.</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, restablecer',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                fetch('<?= URLROOT ?>/users/reset/' + encryptedId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const match = data.message.match(/restablecida a:\s*(\S+)/);
                        const tempPass = match ? match[1] : 'Sgp.[Cédula]';

                        Swal.fire({
                            icon: 'success',
                            title: '<i class="ti ti-circle-check"></i> ¡Restablecido!',
                            html: `
                                <p style="color:#64748b;margin-bottom:12px">Contraseña temporal asignada:</p>
                                <div class="swal-token-row">
                                    <div class="swal-bento-token-long">${tempPass}</div>
                                    <button class="swal-copy-btn" title="Copiar contraseña" onclick="(function(btn){navigator.clipboard.writeText('${tempPass}').then(()=>{btn.innerHTML='<i class=\\'ti ti-check\\'></i>';btn.classList.add('copied');setTimeout(()=>{btn.innerHTML='<i class=\\'ti ti-copy\\'></i>';btn.classList.remove('copied');},2000);})})(this)">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </div>
                                <span style="font-size: 0.95rem; font-weight: 500; color: #475569; display: block; margin-top: 12px; font-style: normal;">El usuario deberá cambiarla en su próximo inicio de sesión.</span>
                            `
                        });
                    } else {
                        NotificationService.error(data.message);
                    }
                });
            }
        });
    }
    
    // Toggle Activar / Desactivar Usuario
    function toggleUser(encryptedId, accion) {
        const esActivar = accion === 'activar';
        Swal.fire({
            title: esActivar ? '¿Activar usuario?' : '¿Desactivar usuario?',
            text: esActivar
                ? 'El usuario podrá iniciar sesión en el sistema nuevamente.'
                : 'El usuario no podrá iniciar sesión. Puedes reactivarlo cuando quieras.',
            icon: esActivar ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonColor: esActivar ? '#16a34a' : '#ef4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: esActivar ? '<i class="ti ti-check"></i> Sí, activar' : '<i class="ti ti-ban"></i> Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                fetch('<?= URLROOT ?>/users/toggleStatus/' + encryptedId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        NotificationService.success(data.message);
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        NotificationService.error(data.message);
                    }
                })
                .catch(() => NotificationService.error('Error de conexión'));
            }
        });
    }
    
    // Close modals on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
    
    // Department field visibility control (Create Modal)
    var createRolSelect = document.getElementById('create_rol_id'); // [SGP-FIX-PJAX-REDECL]
    var departamentoGroup = document.getElementById('departamento-group');
    var departamentoSelect = document.getElementById('create_departamento_id');

    var institucionGroup   = document.getElementById('institucion-group');
    var institucionSelect  = document.getElementById('create_institucion_id');

    if (createRolSelect) {
        createRolSelect.addEventListener('change', function() {
            const rol = this.value;

            // Tutor → mostrar departamento
            if (rol == '2') {
                departamentoGroup.style.display = 'block';
                departamentoSelect.required = true;
            } else {
                departamentoGroup.style.display = 'none';
                departamentoSelect.required = false;
                departamentoSelect.value = '';
            }

            // Pasante → mostrar institución
            if (rol == '3') {
                if (institucionGroup) institucionGroup.style.display = 'block';
            } else {
                if (institucionGroup) {
                    institucionGroup.style.display = 'none';
                    if (institucionSelect) institucionSelect.value = '';
                }
            }
        });
    }
    
    // Department field visibility control (Edit Modal)
    var editRolSelect = document.getElementById('edit_rol_id'); // [SGP-FIX-PJAX-REDECL]
    var editDepartamentoGroup = document.getElementById('edit-departamento-group');
    var editDepartamentoSelect = document.getElementById('edit_departamento_id');

    if (editRolSelect) {
        editRolSelect.addEventListener('change', function() {
            if (this.value == '2') { // Tutor
                editDepartamentoGroup.style.display = 'block';
                editDepartamentoSelect.required = true;
            } else {
                editDepartamentoGroup.style.display = 'none';
                editDepartamentoSelect.required = false;
                editDepartamentoSelect.value = '';
            }
        });
    }
</script>

<!-- Search Modal (Clonado de Pasantes) -->
<div id="searchModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <h2><i class="ti ti-search" style="margin-right:8px;"></i>Consulta Rápida</h2>
                <p>Encuentra usuarios en el sistema por nombre, correo o rol.</p>
            </div>
            <button class="btn-close-modal" onclick="closeSearchModal()"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body">
            
            <div class="form-group">
                <label class="form-label" style="display:flex; align-items:center; gap:6px;"><i class="ti ti-users" style="font-size:1.1rem;"></i> Búsqueda General</label>
                <div style="position: relative;">
                    <i class="ti ti-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; color: #94a3b8; pointer-events: none;"></i>
                    <input type="text" class="form-input" id="ventoSearchInput" placeholder="Buscar por nombre o correo..." style="padding-left: 44px; padding-right: 44px;">
                    <button id="ventoClearSearch" onclick="document.getElementById('ventoSearchInput').value=''; document.getElementById('ventoSearchInput').dispatchEvent(new Event('input'))" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); display: none; background: #e2e8f0; border: none; width: 24px; height: 24px; border-radius: 50%; color: #64748b; cursor: pointer; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#cbd5e1';this.style.color='#334155'" onmouseout="this.style.background='#e2e8f0';this.style.color='#64748b'">
                        <i class="ti ti-x" style="font-size: 0.8rem;"></i>
                    </button>
                </div>
            </div>

            <!-- Contenedor de Resultados (Búsqueda Interna) -->
            <div id="ventoSearchResults" style="margin-top: 16px; max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <div style="text-align:center; padding: 20px; color:#94a3b8; font-size:0.9rem;">
                    Escribe para buscar...
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
// 1. Helper para obtener la tabla
function getSGPDataTable() {
    return typeof table !== 'undefined' ? table : (typeof miTabla !== 'undefined' ? miTabla : null);
}

function obtenerIndiceColumna(dt, nombreColumna) {
    let indice = -1;
    dt.columns().every(function() {
        let titulo = this.header().textContent.trim().toUpperCase();
        if (titulo === nombreColumna.toUpperCase()) {
            indice = this.index();
        }
    });
    return indice;
}

window.filtrarPorRol = function(rolSeleccionado) {
    // ── 1. DataTable (desktop) — índice fijo: col 1 = ROL ──────────────
    let dt = getSGPDataTable();
    if (dt) {
        // [SGP-FIX-USUARIOS-FILTRO-ROL] El buscador global (#globalSearch) y este filtro de
        // rol se combinaban con Y lógico: si quedaba texto escrito en el buscador de una
        // búsqueda anterior, cambiar de pill (ej. de "Pasante" a "Administradores") podía
        // dar 0 resultados aunque sí existan administradores, porque ninguno contenía ese
        // texto. En móvil las tarjetas ignoran el buscador al filtrar por rol, por eso allí
        // "sí funcionaba". Aquí igualamos el comportamiento: cambiar de rol limpia el
        // buscador global, igual que en móvil.
        dt.search('');
        const globalSearchInput = document.getElementById('globalSearch');
        if (globalSearchInput) globalSearchInput.value = '';
        const clearSearchBtn = document.getElementById('clearSearch');
        if (clearSearchBtn) clearSearchBtn.style.display = 'none';

        if (!rolSeleccionado || rolSeleccionado === 'Todos') {
            dt.column(1).search('').draw();
        } else {
            let rolBuscar = 'Pasante';
            const rolStr = String(rolSeleccionado).toLowerCase();
            if (rolStr.includes('admin')) rolBuscar = 'Administrador';
            else if (rolStr.includes('tutor')) rolBuscar = 'Tutor';
            dt.column(1).search('^\\s*' + rolBuscar + '\\s*$', true, false, true).draw();
        }
    }

    // ── 2. Tarjetas móvil (sgp-solo-mobile) — filtrar por data-rol ────
    const cards = document.querySelectorAll('#cardsUsers .bcu-card');
    cards.forEach(function(card) {
        if (!rolSeleccionado || rolSeleccionado === 'Todos') {
            card.style.display = '';
        } else {
            const cardRol = (card.dataset.rol || '').toLowerCase();
            const filtro  = String(rolSeleccionado).toLowerCase();
            let coincide  = false;
            if (filtro.includes('admin') && cardRol.includes('admin')) coincide = true;
            else if (filtro.includes('tutor') && cardRol.includes('tutor')) coincide = true;
            else if (filtro.includes('pasante') && cardRol.includes('pasante')) coincide = true;
            else if (filtro === 'todos') coincide = true;
            card.style.display = coincide ? '' : 'none';
        }
    });

    // ── 3. Visual: actualizar pill activo ────────────────────────────────
    document.querySelectorAll('.vento-pill').forEach(function(p) {
        const pRol = (p.dataset.role || '').toLowerCase();
        const sel  = String(rolSeleccionado || 'todos').toLowerCase();
        const isActive = (!sel || sel === 'todos')
            ? (!pRol)  // pill "Todos" activo
            : sel.includes(pRol) && pRol !== '';
        if (isActive) {
            p.classList.add('active');
            p.style.background = '#2563eb';
            p.style.color = 'white';
        } else {
            p.classList.remove('active');
            p.style.background = '#f1f5f9';
            p.style.color = '#475569';
        }
    });
};

    function openSearchModal() {
        document.getElementById('searchModal').classList.add('active');
        setTimeout(() => {
            const searchInput = document.getElementById('ventoSearchInput');
            if(searchInput) searchInput.focus();
        }, 100);
    }
    
    function closeSearchModal() {
        document.getElementById('searchModal').classList.remove('active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // LÓGICA DEL BUSCADOR "CONSULTA RÁPIDA" Y PILLS
        // ==========================================
        
        // Pasamos la data de PHP a JS para el buscador interno
        const allUsersData = <?= json_encode($users) ?>;
        console.log("Datos de usuarios cargados:", allUsersData);
        
        // Damos un pequeño respiro para que DataTables termine de inicializarse en el DOM
        setTimeout(() => {
            const ventoInput = document.getElementById('ventoSearchInput');
            const ventoClear = document.getElementById('ventoClearSearch');
            const ventoResults = document.getElementById('ventoSearchResults');
            const ventoPills = document.querySelectorAll('.vento-pill');
            
            // Usamos la instancia existente de la tabla (inicializada arriba)
            const dtInstance = $('#usersTable').DataTable();
            
            // 1. Lógica de Consulta Rápida (Modal Independiente)
            if (ventoInput && ventoResults && allUsersData) {
                ventoInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    ventoResults.innerHTML = ''; // Limpiamos resultados anteriores

                    if (query.length === 0) {
                        ventoResults.innerHTML = '<p style="text-align:center; color:#94a3b8; padding: 20px;">Escribe para buscar...</p>';
                        if (ventoClear) ventoClear.style.display = 'none';
                        return;
                    } else {
                        if (ventoClear) ventoClear.style.display = 'flex';
                    }

                    // Filtramos el arreglo de manera segura (soportando varios nombres de columnas)
                    const filtered = allUsersData.filter(u => {
                        const nombre = String(u.nombre || u.name || '').toLowerCase();
                        const correo = String(u.correo || u.email || '').toLowerCase();
                        const cedula = String(u.cedula || u.ci || u.documento || '').toLowerCase();
                        
                        return nombre.includes(query) || correo.includes(query) || cedula.includes(query);
                    });

                    if (filtered.length === 0) {
                        ventoResults.innerHTML = '<p style="text-align:center; color:#94a3b8; padding: 20px;">No se encontraron resultados para "'+query+'".</p>';
                        return;
                    }

                    // Renderizamos las tarjetas HTML
                    filtered.forEach(u => {
                        // Obtenemos los valores de forma segura
                        const uNombre = u.nombre || u.name || 'Usuario';
                        const uCorreo = u.correo || u.email || 'Sin correo';
                        const uCedula = u.cedula || u.ci || '-';
                        const uRol = u.rol || u.role_name || 'Desconocido';
                        const inicial = uNombre.charAt(0).toUpperCase();

                        const card = document.createElement('div');
                        card.style = "display:flex; align-items:center; justify-content:space-between; padding:12px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; transition: all 0.2s; margin-bottom: 8px; cursor:pointer;";
                        card.onmouseover = function() {this.style.background='#eff6ff';this.style.borderColor='#bfdbfe'};
                        card.onmouseout = function() {this.style.background='#f8fafc';this.style.borderColor='#e2e8f0'};
                        card.onclick = function() { closeSearchModal(); SGPModal.verUsuario(u.id); };
                        
                        card.innerHTML = `
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:white; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:1.1rem; box-shadow: 0 4px 6px rgba(37,99,235,0.2);">
                                    ${inicial}
                                </div>
                                <div>
                                    <h4 style="margin:0; font-size:0.95rem; font-weight:700; color:#0f172a;">${uNombre}</h4>
                                    <p style="margin:0; font-size:0.8rem; color:#64748b;">
                                        <i class="ti ti-id"></i> ${uCedula} &nbsp;·&nbsp; ${uCorreo}
                                    </p>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span style="font-size:0.75rem; background:#e2e8f0; color:#475569; padding:4px 10px; border-radius:20px; font-weight:700; text-transform:uppercase;">${uRol}</span>
                            </div>
                        `;
                        ventoResults.appendChild(card);
                    });
                });
            }

            // 2. Filtros Rápidos (Pills) reubicados en la vista principal
            if (dtInstance && ventoPills.length > 0) {
                ventoPills.forEach(pill => {
                    pill.addEventListener('click', function() {
                        // A. Gestionar visual
                        ventoPills.forEach(p => {
                            p.classList.remove('active');
                            p.style.background = '#f1f5f9';
                            p.style.color = '#475569';
                        });
                        
                        this.classList.add('active');
                        this.style.background = '#2563eb';
                        this.style.color = 'white';
                        
                        // La llamada a filtrarPorRol ya se hace mediante el onclick en el HTML.
                        // Aquí solo mantenemos la lógica visual si es necesario.
                    });
                });
            }
        }, 500);
    });

// ── Eliminación permanente de usuario ──────────────────────────
function _ejecutarEliminarUsuario(encryptedId) {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    fetch(URLROOT + '/users/eliminarPermanente/' + encryptedId, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Eliminado', text: data.message, timer: 2500, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    });
}

// roleId: 1=Admin, 2=Tutor → confirmación simple | 3=Pasante → doble confirmación
function confirmarEliminarUsuario(encryptedId, nombre, roleId) {
    const esPasante = (parseInt(roleId) === 3);

    const htmlInfo = `<p style="color:#475569;font-size:.95rem;line-height:1.6;">
                 Vas a eliminar permanentemente a <strong>${nombre}</strong>.
               </p>
               <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:.83rem;color:#b91c1c;margin-top:12px;text-align:left;">
                 <strong>Se eliminarán:</strong> datos personales y cuenta de usuario.
                 ${esPasante ? '<br><span style="font-size:.8rem;color:#dc2626;">También se borrarán sus asistencias, evaluaciones y registros de pasantía.</span>' : ''}
               </div>`;

    if (!esPasante) {
        // Admin / Tutor: confirmación simple
        Swal.fire({
            title: 'Eliminar usuario',
            html: htmlInfo,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        }).then(r => {
            if (r.isConfirmed) _ejecutarEliminarUsuario(encryptedId);
        });
        return;
    }

    // Pasante: primera confirmación + segunda con tipeo "ELIMINAR"
    Swal.fire({
        title: 'Eliminar pasante',
        html: htmlInfo,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({
            title: 'Confirmación final',
            html: '<p style="color:#475569;font-size:.9rem;">Escribe <strong style="color:#dc2626;">ELIMINAR</strong> para confirmar la acción irreversible:</p>',
            input: 'text',
            inputPlaceholder: 'ELIMINAR',
            showCancelButton: true,
            confirmButtonText: 'Eliminar definitivamente',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            inputValidator: v => v !== 'ELIMINAR' ? 'Escribe exactamente: ELIMINAR' : null
        }).then(r2 => {
            if (r2.isConfirmed) _ejecutarEliminarUsuario(encryptedId);
        });
    });
}
</script>
