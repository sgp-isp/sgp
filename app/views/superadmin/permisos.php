<?php
/**
 * SuperAdmin — Gestión y Matriz de Permisos Granulares (RBAC Hub)
 * Arquitectura Bento Master-Detail de Nivel Senior / Ejecutivo
 */
?>

<style>
/* ── Variables y Contenedor Principal ── */
.sgp-perm-container {
    width: 100%;
    max-width: 100%;
    padding: 0;
    margin: 0;
    font-family: inherit;
}

/* ── Hero Banner Bento ── */
.sgp-perm-hero {
    background: linear-gradient(135deg, #09153a 0%, #162660 50%, #1e40af 100%);
    border-radius: 20px;
    padding: 26px 32px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
}
.sgp-perm-hero::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.sgp-perm-hero-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    z-index: 1;
}
.sgp-perm-hero-brand {
    display: flex;
    align-items: center;
    gap: 16px;
}
.sgp-perm-hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(8px);
}
.sgp-perm-hero-title {
    color: #ffffff;
    font-size: 1.55rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;
}
.sgp-perm-hero-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.88rem;
    margin: 4px 0 0 0;
    font-weight: 400;
}
.sgp-perm-hero-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.sgp-perm-badge-mode {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 50px;
    background: rgba(16, 185, 129, 0.18);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.sgp-perm-badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
    animation: sgpPulse 2s infinite;
}
@keyframes sgpPulse {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.8; }
}
.sgp-perm-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 12px;
    color: #ffffff;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 700;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
}
.sgp-perm-btn-back:hover {
    background: rgba(255, 255, 255, 0.24);
    color: #ffffff;
    transform: translateY(-1px);
}

/* ── Bento KPI Grid ── */
.sgp-perm-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}
.sgp-perm-kpi-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    display: flex;
    align-items: center;
    gap: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sgp-perm-kpi-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}
.sgp-perm-kpi-card.active-role {
    border-color: var(--kpi-color, #2563eb);
    background: var(--kpi-bg, #eff6ff);
    box-shadow: 0 4px 14px var(--kpi-shadow, rgba(37, 99, 235, 0.12));
}
.sgp-perm-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.sgp-perm-kpi-info {
    flex: 1;
    min-width: 0;
}
.sgp-perm-kpi-val {
    font-size: 1.45rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.1;
}
.sgp-perm-kpi-label {
    font-size: 0.76rem;
    font-weight: 700;
    color: #64748b;
    margin-top: 3px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* ── Bento Workspace Layout (Master - Detail) ── */
.sgp-perm-workspace {
    display: grid;
    grid-template-columns: 370px 1fr;
    gap: 22px;
    align-items: start;
}

/* ── Panel Maestro (Directorio de Usuarios) ── */
.sgp-perm-master {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.sgp-perm-master-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid #f1f5f9;
}
.sgp-perm-master-title {
    font-size: 0.98rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.sgp-perm-master-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 10px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 50px;
}

/* Tabs de Rol Segmentadas */
.sgp-perm-role-tabs {
    display: flex;
    background: #f1f5f9;
    border-radius: 12px;
    padding: 4px;
    gap: 4px;
    margin-bottom: 14px;
}
.sgp-perm-role-tab {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 9px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sgp-perm-role-tab:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}
.sgp-perm-role-tab.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}
.sgp-perm-role-tab .count {
    font-size: 0.68rem;
    font-weight: 800;
    padding: 1px 6px;
    border-radius: 50px;
    background: #e2e8f0;
    color: #475569;
}
.sgp-perm-role-tab.active .count {
    background: #eff6ff;
    color: #2563eb;
}

/* Buscador */
.sgp-perm-search-box {
    position: relative;
}
.sgp-perm-search-box i.search-ico {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    pointer-events: none;
}
.sgp-perm-search-input {
    width: 100%;
    padding: 10px 36px 10px 38px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #1e293b;
    outline: none;
    transition: all 0.2s;
    background: #f8fafc;
    box-sizing: border-box;
}
.sgp-perm-search-input:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.sgp-perm-search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 0.9rem;
    cursor: pointer;
    display: none;
    padding: 4px;
}
.sgp-perm-search-clear:hover {
    color: #ef4444;
}

/* Lista de Usuarios */
.sgp-perm-users-list {
    max-height: 580px;
    overflow-y: auto;
    padding: 8px 10px;
}
.sgp-perm-users-list::-webkit-scrollbar {
    width: 6px;
}
.sgp-perm-users-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.sgp-perm-user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid transparent;
    margin-bottom: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #ffffff;
}
.sgp-perm-user-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
}
.sgp-perm-user-item.selected {
    background: #eff6ff;
    border-color: #bfdbfe;
    box-shadow: 0 2px 10px rgba(37, 99, 235, 0.08);
}
.sgp-perm-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    color: #ffffff;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}
.sgp-perm-uinfo {
    flex: 1;
    min-width: 0;
}
.sgp-perm-uname {
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sgp-perm-umail {
    font-size: 0.74rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}
.sgp-perm-utags {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 4px;
    flex-wrap: wrap;
}
.sgp-perm-tag-custom {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 7px;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 800;
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
}
.sgp-perm-tag-dept {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.64rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}
.sgp-perm-uarrow {
    color: #cbd5e1;
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: transform 0.2s;
}
.sgp-perm-user-item.selected .sgp-perm-uarrow {
    color: #2563eb;
    transform: translateX(3px);
}

/* ── Panel Detalle (Inspector de Permisos) ── */
.sgp-perm-detail {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Empty State cuando no hay selección */
.sgp-perm-empty-state {
    background: #ffffff;
    border-radius: 18px;
    border: 1px dashed #cbd5e1;
    padding: 60px 30px;
    text-align: center;
    color: #64748b;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
}
.sgp-perm-empty-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin-bottom: 4px;
}
.sgp-perm-empty-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}
.sgp-perm-empty-desc {
    font-size: 0.88rem;
    color: #64748b;
    max-width: 420px;
    line-height: 1.5;
    margin: 0;
}

/* Tarjeta Perfil del Usuario Inspeccionado */
.sgp-perm-profile-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    padding: 22px 26px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 18px;
}
.sgp-perm-profile-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
    min-width: 240px;
}
.sgp-perm-profile-avatar {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    font-weight: 900;
    color: #ffffff;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
}
.sgp-perm-profile-name {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.sgp-perm-profile-role-badge {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 3px 10px;
    border-radius: 50px;
}
.sgp-perm-profile-meta {
    font-size: 0.82rem;
    color: #64748b;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.sgp-perm-profile-meta span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.sgp-perm-profile-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.sgp-perm-stats-box {
    text-align: right;
    padding-right: 14px;
    border-right: 1px solid #e2e8f0;
}
.sgp-perm-stats-num {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
}
.sgp-perm-stats-lbl {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
}
.sgp-perm-progress-bar-wrap {
    width: 100px;
    height: 6px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 5px;
}
.sgp-perm-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #3b82f6);
    border-radius: 4px;
    transition: width 0.4s ease;
}

.sgp-perm-btn-reset {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    border-radius: 11px;
    color: #dc2626;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sgp-perm-btn-reset:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    transform: translateY(-1px);
}

/* ── Filtro de Categorías Bento ── */
.sgp-perm-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 10px 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}
.sgp-perm-cat-pills {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.sgp-perm-cat-pill {
    padding: 6px 14px;
    border-radius: 50px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sgp-perm-cat-pill:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    color: #0f172a;
}
.sgp-perm-cat-pill.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
}
.sgp-perm-live-audit-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.74rem;
    font-weight: 600;
    color: #059669;
    background: #ecfdf5;
    padding: 4px 10px;
    border-radius: 50px;
    border: 1px solid #a7f3d0;
}

/* ── Grid Bento de Módulos ── */
.sgp-perm-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}

.sgp-perm-mod-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
}
.sgp-perm-mod-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
    border-color: #cbd5e1;
    transform: translateY(-2px);
}
.sgp-perm-mod-card.elevated {
    border-color: #fed7aa;
}
.sgp-perm-mod-card.elevated .sgp-perm-mod-header {
    background: #fffaf5;
}

.sgp-perm-mod-header {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
}
.sgp-perm-mod-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.sgp-perm-mod-info {
    flex: 1;
    min-width: 0;
}
.sgp-perm-mod-name {
    font-size: 0.92rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.sgp-perm-mod-grp {
    display: inline-block;
    margin-top: 3px;
    padding: 1px 7px;
    border-radius: 50px;
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
}
.sgp-perm-mod-elevated-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-top: 3px;
    padding: 1px 7px;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 800;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}

/* Master Toggle */
.sgp-perm-master-toggle-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
}
.sgp-perm-master-toggle-lbl {
    font-size: 0.58rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Cuerpo del Módulo */
.sgp-perm-mod-body {
    padding: 12px 18px 16px;
    background: #ffffff;
    flex: 1;
}
.sgp-perm-actions-caption {
    font-size: 0.65rem;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}
.sgp-perm-action-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f8fafc;
    transition: opacity 0.2s;
}
.sgp-perm-action-row:last-child {
    border-bottom: none;
}
.sgp-perm-action-row.dimmed {
    opacity: 0.38;
    pointer-events: none;
}
.sgp-perm-action-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
}
.sgp-perm-action-icon {
    font-size: 0.95rem;
    flex-shrink: 0;
}
.sgp-perm-action-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sgp-perm-override-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #f59e0b;
    box-shadow: 0 0 0 2px #ffffff, 0 0 6px #f59e0b;
    display: inline-block;
    flex-shrink: 0;
    margin-left: 4px;
}

/* ── Toggle Switch Bento (Estilo iOS Premium) ── */
.sgp-ios-switch {
    position: relative;
    display: inline-block;
    width: 42px;
    height: 24px;
    cursor: pointer;
    flex-shrink: 0;
}
.sgp-ios-switch input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}
.sgp-ios-slider {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #cbd5e1;
    border-radius: 24px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.sgp-ios-slider::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    bottom: 3px;
    background: #ffffff;
    border-radius: 50%;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
.sgp-ios-switch input:checked + .sgp-ios-slider {
    background: var(--switch-color, #2563eb);
}
.sgp-ios-switch input:checked + .sgp-ios-slider::before {
    transform: translateX(18px);
}

.sgp-ios-switch.sm {
    width: 34px;
    height: 20px;
}
.sgp-ios-switch.sm .sgp-ios-slider::before {
    width: 14px;
    height: 14px;
    left: 3px;
    bottom: 3px;
}
.sgp-ios-switch.sm input:checked + .sgp-ios-slider::before {
    transform: translateX(14px);
}

/* ── Botón Mobile para regresar al maestro ── */
.sgp-perm-mobile-back-btn {
    display: none;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    color: #334155;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    margin-bottom: 12px;
}

/* ── Responsive Media Queries ── */
@media (max-width: 1024px) {
    .sgp-perm-workspace {
        grid-template-columns: 320px 1fr;
        gap: 16px;
    }
}
@media (max-width: 860px) {
    .sgp-perm-workspace {
        grid-template-columns: 1fr;
    }
    .sgp-perm-mobile-back-btn {
        display: inline-flex;
    }
    .sgp-perm-detail.is-viewing {
        display: flex;
    }
    .sgp-perm-master.is-hidden-mobile {
        display: none;
    }
}
</style>

<div class="sgp-perm-container">

    <!-- 1. Hero Banner Bento -->
    <div class="sgp-perm-hero">
        <div class="sgp-perm-hero-content">
            <div class="sgp-perm-hero-brand">
                <div class="sgp-perm-hero-icon">
                    <i class="ti ti-shield-lock"></i>
                </div>
                <div>
                    <h1 class="sgp-perm-hero-title">Gestión de Permisos & Accesos Granulares</h1>
                    <p class="sgp-perm-hero-subtitle">Control de privilegios por usuario con anulación individual (RBAC) y auditoría forense</p>
                </div>
            </div>
            <div class="sgp-perm-hero-actions">
                <div class="sgp-perm-badge-mode">
                    <span class="sgp-perm-badge-dot"></span>
                    <span>Modo SuperAdmin</span>
                </div>
                <a href="<?= URLROOT ?>/superadmin" class="sgp-perm-btn-back">
                    <i class="ti ti-layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 2. KPIs Bento -->
    <div class="sgp-perm-kpis">
        <div class="sgp-perm-kpi-card active-role" id="kpi-role-1" 
             style="--kpi-color:#7c3aed;--kpi-bg:#f5f3ff;--kpi-shadow:rgba(124,58,237,0.15);" 
             onclick="sgpSelectRole(1)">
            <div class="sgp-perm-kpi-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="ti ti-user-shield"></i>
            </div>
            <div class="sgp-perm-kpi-info">
                <div class="sgp-perm-kpi-val" id="kpiAdminsCount"><?= $data['kpis']['total_admins'] ?? 0 ?></div>
                <div class="sgp-perm-kpi-label">Administradores</div>
            </div>
        </div>

        <div class="sgp-perm-kpi-card" id="kpi-role-2" 
             style="--kpi-color:#059669;--kpi-bg:#ecfdf5;--kpi-shadow:rgba(5,150,105,0.15);" 
             onclick="sgpSelectRole(2)">
            <div class="sgp-perm-kpi-icon" style="background:#d1fae5;color:#059669;">
                <i class="ti ti-user-check"></i>
            </div>
            <div class="sgp-perm-kpi-info">
                <div class="sgp-perm-kpi-val" id="kpiTutoresCount"><?= $data['kpis']['total_tutores'] ?? 0 ?></div>
                <div class="sgp-perm-kpi-label">Tutores</div>
            </div>
        </div>

        <div class="sgp-perm-kpi-card" id="kpi-role-3" 
             style="--kpi-color:#d97706;--kpi-bg:#fffbeb;--kpi-shadow:rgba(217,119,6,0.15);" 
             onclick="sgpSelectRole(3)">
            <div class="sgp-perm-kpi-icon" style="background:#fef3c7;color:#d97706;">
                <i class="ti ti-users-group"></i>
            </div>
            <div class="sgp-perm-kpi-info">
                <div class="sgp-perm-kpi-val" id="kpiPasantesCount"><?= $data['kpis']['total_pasantes'] ?? 0 ?></div>
                <div class="sgp-perm-kpi-label">Pasantes</div>
            </div>
        </div>

        <div class="sgp-perm-kpi-card" style="--kpi-color:#ea580c;--kpi-bg:#fff7ed;--kpi-shadow:rgba(234,88,12,0.15);">
            <div class="sgp-perm-kpi-icon" style="background:#ffedd5;color:#ea580c;">
                <i class="ti ti-adjustments-alt"></i>
            </div>
            <div class="sgp-perm-kpi-info">
                <div class="sgp-perm-kpi-val" id="kpiOverridesCount"><?= $data['kpis']['con_override'] ?? 0 ?></div>
                <div class="sgp-perm-kpi-label">Personalizados</div>
            </div>
        </div>

        <div class="sgp-perm-kpi-card" style="--kpi-color:#2563eb;--kpi-bg:#eff6ff;--kpi-shadow:rgba(37,99,235,0.15);">
            <div class="sgp-perm-kpi-icon" style="background:#dbeafe;color:#2563eb;">
                <i class="ti ti-apps"></i>
            </div>
            <div class="sgp-perm-kpi-info">
                <div class="sgp-perm-kpi-val"><?= $data['kpis']['total_modulos'] ?? 0 ?></div>
                <div class="sgp-perm-kpi-label">Módulos Activos</div>
            </div>
        </div>
    </div>

    <!-- 3. Workspace Principal: Master - Detail -->
    <div class="sgp-perm-workspace">

        <!-- PANEL MAESTRO: Directorio de Usuarios -->
        <aside class="sgp-perm-master" id="sgpPermMaster">
            <div class="sgp-perm-master-header">
                <div class="sgp-perm-master-title">
                    <span>Directorio de Usuarios</span>
                    <span class="sgp-perm-master-badge" id="sgpUsersCountBadge">0 usuarios</span>
                </div>

                <!-- Tabs de Selección de Rol -->
                <div class="sgp-perm-role-tabs">
                    <button class="sgp-perm-role-tab active" id="tabRole1" onclick="sgpSelectRole(1)">
                        <i class="ti ti-user-shield"></i>
                        <span>Admins</span>
                        <span class="count"><?= $data['kpis']['total_admins'] ?? 0 ?></span>
                    </button>
                    <button class="sgp-perm-role-tab" id="tabRole2" onclick="sgpSelectRole(2)">
                        <i class="ti ti-user-check"></i>
                        <span>Tutores</span>
                        <span class="count"><?= $data['kpis']['total_tutores'] ?? 0 ?></span>
                    </button>
                    <button class="sgp-perm-role-tab" id="tabRole3" onclick="sgpSelectRole(3)">
                        <i class="ti ti-users-group"></i>
                        <span>Pasantes</span>
                        <span class="count"><?= $data['kpis']['total_pasantes'] ?? 0 ?></span>
                    </button>
                </div>

                <!-- Buscador en tiempo real -->
                <div class="sgp-perm-search-box">
                    <i class="ti ti-search search-ico"></i>
                    <input type="text" id="sgpPermSearch" class="sgp-perm-search-input" 
                           placeholder="Buscar por nombre o correo..." 
                           oninput="sgpFilterUsers(this.value)">
                    <button type="button" id="sgpPermSearchClear" class="sgp-perm-search-clear" onclick="sgpClearSearch()">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>

            <!-- Listado con scroll -->
            <div class="sgp-perm-users-list" id="sgpPermUsersList">
                <div style="padding:40px 20px;text-align:center;color:#94a3b8;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <div style="font-size:0.85rem;margin-top:8px;">Cargando usuarios...</div>
                </div>
            </div>
        </aside>

        <!-- PANEL DETALLE: Inspector y Editor de Permisos -->
        <main class="sgp-perm-detail" id="sgpPermDetail">

            <!-- Botón mobile para volver al directorio -->
            <button type="button" class="sgp-perm-mobile-back-btn" onclick="sgpBackToMasterMobile()">
                <i class="ti ti-arrow-left"></i>
                <span>Ver lista de usuarios</span>
            </button>

            <!-- Estado Vacío Inicial (cuando ningún usuario está seleccionado) -->
            <div class="sgp-perm-empty-state" id="sgpPermEmptyState">
                <div class="sgp-perm-empty-icon">
                    <i class="ti ti-shield-check"></i>
                </div>
                <h3 class="sgp-perm-empty-title">Selecciona un Usuario del Directorio</h3>
                <p class="sgp-perm-empty-desc">
                    Elige un usuario del panel lateral izquierdo para inspeccionar sus privilegios asignados por rol, 
                    habilitar o revocar módulos específicos y auditar cambios en tiempo real.
                </p>
            </div>

            <!-- Contenido cuando un usuario está seleccionado (oculto por defecto) -->
            <div id="sgpPermActiveWorkspace" style="display:none;flex-direction:column;gap:18px;">

                <!-- Tarjeta Perfil del Usuario Activo -->
                <div class="sgp-perm-profile-card">
                    <div class="sgp-perm-profile-left">
                        <div class="sgp-perm-profile-avatar" id="sgpProfileAvatar">?</div>
                        <div>
                            <div class="sgp-perm-profile-name">
                                <span id="sgpProfileName">—</span>
                                <span class="sgp-perm-profile-role-badge" id="sgpProfileRoleBadge">ROL</span>
                            </div>
                            <div class="sgp-perm-profile-meta">
                                <span><i class="ti ti-mail"></i> <span id="sgpProfileEmail">—</span></span>
                                <span id="sgpProfileDeptWrap" style="display:none;">
                                    <i class="ti ti-building"></i> <span id="sgpProfileDept">—</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="sgp-perm-profile-right">
                        <div class="sgp-perm-stats-box">
                            <div class="sgp-perm-stats-num" id="sgpProfileStatsCount">0 / 0</div>
                            <div class="sgp-perm-stats-lbl">Permisos Habilitados</div>
                            <div class="sgp-perm-progress-bar-wrap">
                                <div class="sgp-perm-progress-bar" id="sgpProfileProgressBar" style="width:0%;"></div>
                            </div>
                        </div>

                        <button type="button" class="sgp-perm-btn-reset" onclick="sgpResetCurrentUser()" 
                                title="Restablece todos los permisos a la configuración estándar del rol">
                            <i class="ti ti-rotate-clockwise"></i>
                            <span>Restablecer al Rol</span>
                        </button>
                    </div>
                </div>

                <!-- Barra de Filtros de Categorías & Auditoría -->
                <div class="sgp-perm-filter-bar">
                    <div class="sgp-perm-cat-pills" id="sgpCatPills">
                        <button class="sgp-perm-cat-pill active" onclick="sgpFilterCategory('ALL', this)">Todos</button>
                        <button class="sgp-perm-cat-pill" onclick="sgpFilterCategory('Administración', this)">Administración</button>
                        <button class="sgp-perm-cat-pill" onclick="sgpFilterCategory('Pasantías', this)">Pasantías</button>
                        <button class="sgp-perm-cat-pill" onclick="sgpFilterCategory('Informes', this)">Informes</button>
                        <button class="sgp-perm-cat-pill" onclick="sgpFilterCategory('Sistema', this)">Sistema</button>
                        <button class="sgp-perm-cat-pill" onclick="sgpFilterCategory('Académico', this)">Académico</button>
                        <button class="sgp-perm-cat-pill" onclick="sgpFilterCategory('Tutor', this)">Tutor</button>
                    </div>

                    <div class="sgp-perm-live-audit-badge">
                        <i class="ti ti-activity"></i>
                        <span>Cambios auditados en Bitácora</span>
                    </div>
                </div>

                <!-- Bento Grid de Módulos & Acciones -->
                <div class="sgp-perm-modules-grid" id="sgpModulesGrid">
                    <!-- Se inyecta dinámicamente -->
                </div>

            </div><!-- /#sgpPermActiveWorkspace -->

        </main>
    </div>

</div>

<script>
(function () {
    'use strict';

    const URLROOT = '<?= URLROOT ?>';

    // Diccionario de etiquetas legibles para acciones
    const accionLabels = {
        'ver_usuarios': 'Ver Módulo Usuarios',
        'crear_usuario': 'Crear Usuarios',
        'editar_usuario': 'Editar Usuarios',
        'desactivar_usuario': 'Desactivar Usuarios',
        'ver_pasantes': 'Ver Módulo Pasantes',
        'editar_pasante': 'Editar Ficha Pasante',
        'exportar_pasantes': 'Exportar Lista Pasantes',
        'ver_asistencias': 'Ver Módulo Asistencias',
        'modificar_asistencia': 'Modificar Asistencias',
        'exportar_asistencias': 'Exportar Asistencias',
        'ver_asignaciones': 'Ver Módulo Asignaciones',
        'crear_asignacion': 'Crear Asignación de Tutor',
        'editar_asignacion': 'Editar Asignación',
        'ver_evaluaciones': 'Ver Módulo Evaluaciones',
        'exportar_evaluacion': 'Exportar PDF Evaluación',
        'ver_reportes': 'Ver Módulo Reportes',
        'exportar_reporte': 'Exportar Reportes',
        'ver_analiticas': 'Ver Módulo Analíticas',
        'ver_backup': 'Ver Módulo Respaldos BD',
        'crear_backup': 'Generar Respaldos BD',
        'descargar_backup': 'Descargar Respaldos BD',
        'ver_bitacora': 'Ver Módulo Bitácora',
        'exportar_bitacora': 'Exportar Bitácora',
        'ver_configuracion': 'Ver Módulo Configuración',
        'editar_configuracion': 'Modificar Configuración',
        'ver_periodos': 'Ver Módulo Períodos',
        'crear_periodo': 'Crear / Cerrar Períodos',
        'ver_actividades': 'Ver Actividades Extras',
        'crear_actividad': 'Crear Actividad Extra',
        'ver_mis_pasantes': 'Ver Mis Pasantes Asignados',
        'ver_mis_evaluaciones': 'Ver Mis Evaluaciones',
        'registrar_evaluacion': 'Calificar y Evaluar Pasante',
        'ver_perfil_pasante': 'Ver Mi Perfil de Pasante',
        'descargar_constancia': 'Descargar Constancia Oficial',
    };

    const accionIcons = {
        'ver': 'ti-eye',
        'crear': 'ti-circle-plus',
        'editar': 'ti-edit',
        'eliminar': 'ti-trash',
        'exportar': 'ti-download',
    };

    const colorRules = {
        'Administración': { bg: '#eff6ff', color: '#2563eb', border: '#bfdbfe' },
        'Pasantías':      { bg: '#ecfdf5', color: '#059669', border: '#a7f3d0' },
        'Informes':       { bg: '#eef2ff', color: '#4f46e5', border: '#c7d2fe' },
        'Sistema':        { bg: '#fef2f2', color: '#dc2626', border: '#fecaca' },
        'Académico':      { bg: '#fffbeb', color: '#d97706', border: '#fde68a' },
        'Tutor':          { bg: '#ecfeff', color: '#0891b2', border: '#a5f3fc' },
        'Portal Pasante': { bg: '#fdf4ff', color: '#a855f7', border: '#f5d0fe' },
    };

    const roleMeta = {
        1: { name: 'Administrador', color: '#7c3aed', bg: '#f5f3ff', border: '#ddd6fe', grad: 'linear-gradient(135deg, #6d28d9, #8b5cf6)' },
        2: { name: 'Tutor',         color: '#059669', bg: '#ecfdf5', border: '#a7f3d0', grad: 'linear-gradient(135deg, #047857, #10b981)' },
        3: { name: 'Pasante',       color: '#d97706', bg: '#fffbeb', border: '#fde68a', grad: 'linear-gradient(135deg, #b45309, #f59e0b)' },
    };

    // Estado local
    let currentRoleId     = 1;
    let currentUserId     = null;
    let currentCategory   = 'ALL';
    let currentModulos    = null;
    let usersData         = [];
    let usersCache        = {};

    // ── 1. Cambio de Rol ──────────────────────────────────
    window.sgpSelectRole = function (roleId) {
        currentRoleId = roleId;
        currentUserId = null;

        // Actualizar pestañas
        document.querySelectorAll('.sgp-perm-role-tab').forEach(function (tab) {
            tab.classList.remove('active');
        });
        const activeTab = document.getElementById('tabRole' + roleId);
        if (activeTab) activeTab.classList.add('active');

        // Actualizar KPIs de rol
        [1, 2, 3].forEach(function (id) {
            const card = document.getElementById('kpi-role-' + id);
            if (card) {
                card.classList.toggle('active-role', id === roleId);
            }
        });

        // Limpiar buscador
        sgpClearSearch();

        // Ocultar workspace activo y mostrar empty state
        document.getElementById('sgpPermActiveWorkspace').style.display = 'none';
        document.getElementById('sgpPermEmptyState').style.display = 'flex';

        // Cargar usuarios del rol
        loadUsers(roleId);
    };

    // ── 2. Cargar Usuarios ────────────────────────────────
    function loadUsers(roleId) {
        const list = document.getElementById('sgpPermUsersList');
        list.innerHTML = '<div style="padding:40px 20px;text-align:center;color:#94a3b8;">' +
                         '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
                         '<div style="font-size:0.85rem;margin-top:8px;">Cargando usuarios...</div></div>';

        fetch(URLROOT + '/superadmin/getUsersByRol?rol_id=' + roleId)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    list.innerHTML = '<div style="padding:30px 20px;text-align:center;color:#ef4444;"><i class="ti ti-alert-triangle" style="font-size:1.5rem;"></i><p style="margin:6px 0 0;font-size:0.85rem;">Error al cargar usuarios.</p></div>';
                    return;
                }
                currentModulos = res.modulos;
                usersData = res.usuarios || [];
                usersCache = {};
                usersData.forEach(function (u) { usersCache[u.id] = u; });

                document.getElementById('sgpUsersCountBadge').textContent = usersData.length + ' usuario' + (usersData.length !== 1 ? 's' : '');
                renderUserList(usersData);

                // Si hay usuarios y estamos en desktop, auto-seleccionar el primero para conveniencia
                if (usersData.length > 0 && window.innerWidth > 860 && !currentUserId) {
                    sgpSelectUser(usersData[0].id);
                }
            })
            .catch(function (err) {
                console.error(err);
                list.innerHTML = '<div style="padding:30px 20px;text-align:center;color:#ef4444;"><i class="ti ti-wifi-off" style="font-size:1.5rem;"></i><p style="margin:6px 0 0;font-size:0.85rem;">Error de conexión.</p></div>';
            });
    }

    // ── 3. Renderizar Lista de Usuarios ───────────────────
    function renderUserList(usuarios) {
        const list = document.getElementById('sgpPermUsersList');
        if (!usuarios.length) {
            list.innerHTML = '<div style="padding:40px 20px;text-align:center;color:#94a3b8;">' +
                             '<i class="ti ti-users-off" style="font-size:2rem;opacity:0.6;"></i>' +
                             '<p style="margin:8px 0 0;font-size:0.85rem;">No se encontraron usuarios</p></div>';
            return;
        }

        const roleCfg = roleMeta[currentRoleId] || roleMeta[1];

        list.innerHTML = usuarios.map(function (u) {
            const nombre  = (u.nombre_completo || '').trim() || u.correo;
            const ini     = nombre.charAt(0).toUpperCase();
            const hasOvr  = u.permisos && Object.values(u.permisos).some(function (p) { return p.es_override; });
            const isSel   = currentUserId == u.id;
            const dept    = u.departamento_nombre ? escH(u.departamento_nombre) : '';

            return '<div class="sgp-perm-user-item' + (isSel ? ' selected' : '') + '" data-uid="' + u.id + '" onclick="sgpSelectUser(' + u.id + ')">' +
                   '    <div class="sgp-perm-avatar" style="background:' + roleCfg.grad + ';">' + ini + '</div>' +
                   '    <div class="sgp-perm-uinfo">' +
                   '        <div class="sgp-perm-uname">' + escH(nombre) + '</div>' +
                   '        <div class="sgp-perm-umail">' + escH(u.correo) + '</div>' +
                   '        <div class="sgp-perm-utags">' +
                   (hasOvr ? '<span class="sgp-perm-tag-custom"><i class="ti ti-bolt"></i> Personalizado</span>' : '') +
                   (dept ? '<span class="sgp-perm-tag-dept"><i class="ti ti-building"></i> ' + dept + '</span>' : '') +
                   '        </div>' +
                   '    </div>' +
                   '    <i class="ti ti-chevron-right sgp-perm-uarrow"></i>' +
                   '</div>';
        }).join('');
    }

    // ── 4. Búsqueda y Filtrado de Usuarios ────────────────
    window.sgpFilterUsers = function (query) {
        const q = (query || '').toLowerCase().trim();
        const clearBtn = document.getElementById('sgpPermSearchClear');
        if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

        if (!q) {
            renderUserList(usersData);
            return;
        }

        const filtered = usersData.filter(function (u) {
            const nom = (u.nombre_completo || '').toLowerCase();
            const cor = (u.correo || '').toLowerCase();
            const dep = (u.departamento_nombre || '').toLowerCase();
            return nom.includes(q) || cor.includes(q) || dep.includes(q);
        });

        renderUserList(filtered);
    };

    window.sgpClearSearch = function () {
        const input = document.getElementById('sgpPermSearch');
        if (input) input.value = '';
        const clearBtn = document.getElementById('sgpPermSearchClear');
        if (clearBtn) clearBtn.style.display = 'none';
        renderUserList(usersData);
    };

    // ── 5. Seleccionar Usuario e Inspeccionar ──────────────
    window.sgpSelectUser = function (uid) {
        currentUserId = uid;
        const u = usersCache[uid];
        if (!u) return;

        // Resaltar en lista
        document.querySelectorAll('.sgp-perm-user-item').forEach(function (el) {
            el.classList.toggle('selected', parseInt(el.dataset.uid) === uid);
        });

        // Configuración de Rol
        const roleCfg = roleMeta[currentRoleId] || roleMeta[1];
        const nombre  = (u.nombre_completo || '').trim() || u.correo;
        const ini     = nombre.charAt(0).toUpperCase();

        // Llenar Banner Perfil
        const avatar = document.getElementById('sgpProfileAvatar');
        avatar.textContent = ini;
        avatar.style.background = roleCfg.grad;

        document.getElementById('sgpProfileName').textContent = nombre;
        document.getElementById('sgpProfileEmail').textContent = u.correo;

        const roleBadge = document.getElementById('sgpProfileRoleBadge');
        roleBadge.textContent = roleCfg.name;
        roleBadge.style.background = roleCfg.bg;
        roleBadge.style.color = roleCfg.color;
        roleBadge.style.border = '1px solid ' + roleCfg.border;

        const deptWrap = document.getElementById('sgpProfileDeptWrap');
        if (u.departamento_nombre) {
            document.getElementById('sgpProfileDept').textContent = u.departamento_nombre;
            deptWrap.style.display = 'inline-flex';
        } else {
            deptWrap.style.display = 'none';
        }

        // Mostrar panel activo y ocultar empty state
        document.getElementById('sgpPermEmptyState').style.display = 'none';
        document.getElementById('sgpPermActiveWorkspace').style.display = 'flex';

        // En pantallas pequeñas, enfocar el detalle
        if (window.innerWidth <= 860) {
            document.getElementById('sgpPermMaster').classList.add('is-hidden-mobile');
            document.getElementById('sgpPermDetail').classList.add('is-viewing');
        }

        // Renderizar Módulos y Switches
        renderModulesGrid(currentModulos, u.permisos || {});
    };

    // Regresar a la lista en mobile
    window.sgpBackToMasterMobile = function () {
        document.getElementById('sgpPermMaster').classList.remove('is-hidden-mobile');
        document.getElementById('sgpPermDetail').classList.remove('is-viewing');
    };

    // ── 6. Renderizar Grid de Módulos ──────────────────────
    function renderModulesGrid(modulos, permisos) {
        const grid = document.getElementById('sgpModulesGrid');
        if (!modulos || !Object.keys(modulos).length) {
            grid.innerHTML = '<div style="grid-column:1/-1;padding:40px;text-align:center;color:#94a3b8;"><i class="ti ti-folder-off" style="font-size:2rem;"></i><p>Sin módulos configurados para este rol.</p></div>';
            return;
        }

        let totalPerms = 0;
        let activePerms = 0;
        let html = '';

        for (const grupo in modulos) {
            if (!Object.prototype.hasOwnProperty.call(modulos, grupo)) continue;
            const modulosGrupo = modulos[grupo];

            for (const modId in modulosGrupo) {
                if (!Object.prototype.hasOwnProperty.call(modulosGrupo, modId)) continue;
                const acciones = modulosGrupo[modId];
                if (!acciones.length) continue;

                const rule       = colorRules[grupo] || { bg: '#f8fafc', color: '#475569', border: '#e2e8f0' };
                const modName    = acciones[0].modulo_nombre || 'Módulo';
                const modIcon    = acciones[0].icono || 'ti-folder';
                const rolBase    = parseInt(acciones[0].rol_base) || 1;
                const isElevated = currentRoleId !== null && rolBase < currentRoleId;

                let accionVer = null;
                const funciones = [];

                acciones.forEach(function (a) {
                    if (a.tipo === 'ver') {
                        accionVer = a;
                    } else {
                        funciones.push(a);
                    }
                });

                // Conteo de permisos del módulo
                acciones.forEach(function (a) {
                    totalPerms++;
                    const p = permisos[a.clave] || {};
                    if (p.habilitado) activePerms++;
                });

                // Toggle Maestro de Módulo (menú lateral / acceso principal)
                let masterToggleHtml = '';
                let isMasterChecked  = true;

                if (accionVer) {
                    const vp   = permisos[accionVer.clave] || {};
                    isMasterChecked = !!vp.habilitado;
                    const hasOvr    = !!vp.es_override;

                    masterToggleHtml = 
                        '<div class="sgp-perm-master-toggle-box">' +
                        '    <label class="sgp-ios-switch" title="Habilitar o Deshabilitar acceso a este módulo">' +
                        '        <input type="checkbox" data-clave="' + accionVer.clave + '" ' + (isMasterChecked ? 'checked' : '') + 
                        '               onchange="sgpTogglePermiso(this, \'' + modId + '\')">' +
                        '        <span class="sgp-ios-slider" style="--switch-color:' + rule.color + ';"></span>' +
                        '    </label>' +
                        '    <span class="sgp-perm-master-toggle-lbl" style="color:' + rule.color + ';">' + (isMasterChecked ? 'Activo' : 'Inactivo') + '</span>' +
                        '</div>';
                }

                // Generar filas de funciones
                let fnRowsHtml = '';
                funciones.forEach(function (accion) {
                    const p       = permisos[accion.clave] || {};
                    const isChk   = !!p.habilitado;
                    const isOvr   = !!p.es_override;
                    const label   = accionLabels[accion.clave] || accion.accion_nombre;
                    const iconKey = accionIcons[accion.tipo] || 'ti-circle-dot';

                    fnRowsHtml += 
                        '<div class="sgp-perm-action-row' + (!isMasterChecked ? ' dimmed' : '') + '">' +
                        '    <div class="sgp-perm-action-left">' +
                        '        <i class="ti ' + iconKey + ' sgp-perm-action-icon" style="color:' + rule.color + ';"></i>' +
                        '        <span class="sgp-perm-action-name" title="' + escH(label) + '">' + escH(label) + '</span>' +
                        (isOvr ? '<span class="sgp-perm-override-dot" title="Permiso personalizado para este usuario"></span>' : '') +
                        '    </div>' +
                        '    <label class="sgp-ios-switch sm">' +
                        '        <input type="checkbox" data-clave="' + accion.clave + '" ' + (isChk ? 'checked' : '') + 
                        '               onchange="sgpTogglePermiso(this)">' +
                        '        <span class="sgp-ios-slider" style="--switch-color:' + rule.color + ';"></span>' +
                        '    </label>' +
                        '</div>';
                });

                // Tarjeta del módulo
                html += 
                    '<div class="sgp-perm-mod-card' + (isElevated ? ' elevated' : '') + '" data-mod-id="' + modId + '" data-category="' + grupo + '">' +
                    '    <div class="sgp-perm-mod-header">' +
                    '        <div class="sgp-perm-mod-icon" style="background:' + rule.bg + ';color:' + rule.color + ';border:1px solid ' + rule.border + ';">' +
                    '            <i class="ti ' + modIcon + '"></i>' +
                    '        </div>' +
                    '        <div class="sgp-perm-mod-info">' +
                    '            <div class="sgp-perm-mod-name">' + escH(modName) + '</div>' +
                    '            <span class="sgp-perm-mod-grp" style="background:' + rule.bg + ';color:' + rule.color + ';">' + escH(grupo) + '</span>' +
                    (isElevated ? '<br><span class="sgp-perm-mod-elevated-pill"><i class="ti ti-shield-half-filled"></i> Privilegio Elevado</span>' : '') +
                    '        </div>' +
                    masterToggleHtml +
                    '    </div>' +
                    '    <div class="sgp-perm-mod-body">' +
                    (funciones.length ? '        <div class="sgp-perm-actions-caption">Acciones Granulares</div>' + fnRowsHtml : '<div style="font-size:0.75rem;color:#94a3b8;font-style:italic;">Acceso único de visualización general</div>') +
                    '    </div>' +
                    '</div>';
            }
        }

        grid.innerHTML = html;

        // Actualizar barra de progreso del perfil
        updateStatsSummary(activePerms, totalPerms);

        // Aplicar filtro de categoría actual si existe
        if (currentCategory !== 'ALL') {
            applyCategoryFilter(currentCategory);
        }
    }

    function updateStatsSummary(active, total) {
        document.getElementById('sgpProfileStatsCount').textContent = active + ' / ' + total;
        const pct = total > 0 ? Math.round((active / total) * 100) : 0;
        document.getElementById('sgpProfileProgressBar').style.width = pct + '%';
    }

    // ── 7. Filtrar por Categoría de Módulo ────────────────
    window.sgpFilterCategory = function (category, btn) {
        currentCategory = category;
        document.querySelectorAll('.sgp-perm-cat-pill').forEach(function (p) {
            p.classList.remove('active');
        });
        if (btn) btn.classList.add('active');

        applyCategoryFilter(category);
    };

    function applyCategoryFilter(cat) {
        document.querySelectorAll('.sgp-perm-mod-card').forEach(function (card) {
            if (cat === 'ALL' || card.dataset.category === cat) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // ── 8. Guardar Permiso (Toggle en Tiempo Real + Audit) ──
    window.sgpTogglePermiso = function (input, modId) {
        if (!currentUserId) return;
        const clave = input.dataset.clave;
        const nuevo = input.checked;
        const prev  = !nuevo;

        // Si es el toggle maestro del módulo
        if (modId !== undefined) {
            const card = document.querySelector('.sgp-perm-mod-card[data-mod-id="' + modId + '"]');
            if (card) {
                const lbl = card.querySelector('.sgp-perm-master-toggle-lbl');
                if (lbl) lbl.textContent = nuevo ? 'Activo' : 'Inactivo';
                card.querySelectorAll('.sgp-perm-action-row').forEach(function (row) {
                    nuevo ? row.classList.remove('dimmed') : row.classList.add('dimmed');
                });
            }
        }

        fetch(URLROOT + '/superadmin/savePermiso', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario_id: parseInt(currentUserId),
                clave: clave,
                habilitado: nuevo
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                if (window.NotificationService) {
                    window.NotificationService.success(res.message);
                }

                // Actualizar caché del usuario
                const u = usersCache[currentUserId];
                if (u) {
                    if (!u.permisos) u.permisos = {};
                    u.permisos[clave] = { habilitado: nuevo, es_override: true };

                    // Recalcular conteo en barra de progreso
                    let activeCount = 0;
                    let totalCount = 0;
                    Object.values(u.permisos).forEach(function (p) {
                        totalCount++;
                        if (p.habilitado) activeCount++;
                    });
                    updateStatsSummary(activeCount, totalCount);

                    // Marcar fila de usuario como personalizada si no lo estaba
                    const row = document.querySelector('.sgp-perm-user-item[data-uid="' + currentUserId + '"]');
                    if (row && !row.querySelector('.sgp-perm-tag-custom')) {
                        const tags = row.querySelector('.sgp-perm-utags');
                        if (tags) {
                            tags.insertAdjacentHTML('afterbegin', '<span class="sgp-perm-tag-custom"><i class="ti ti-bolt"></i> Personalizado</span>');
                        }
                    }
                }
            } else {
                if (window.NotificationService) {
                    window.NotificationService.error(res.message || 'Error al guardar permiso');
                }
                // Rollback visual
                input.checked = prev;
                if (modId !== undefined) {
                    const card2 = document.querySelector('.sgp-perm-mod-card[data-mod-id="' + modId + '"]');
                    if (card2) {
                        const lbl2 = card2.querySelector('.sgp-perm-master-toggle-lbl');
                        if (lbl2) lbl2.textContent = prev ? 'Activo' : 'Inactivo';
                        card2.querySelectorAll('.sgp-perm-action-row').forEach(function (row) {
                            prev ? row.classList.remove('dimmed') : row.classList.add('dimmed');
                        });
                    }
                }
            }
        })
        .catch(function (err) {
            console.error(err);
            input.checked = prev;
            if (window.NotificationService) {
                window.NotificationService.error('Error de red al actualizar permiso');
            }
        });
    };

    // ── 9. Restablecer Permisos al Rol por Defecto ─────────
    window.sgpResetCurrentUser = function () {
        if (!currentUserId) return;
        const u = usersCache[currentUserId];
        const nombre = u ? ((u.nombre_completo || '').trim() || u.correo) : 'este usuario';

        const confirmAction = function () {
            fetch(URLROOT + '/superadmin/resetUsuario', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: parseInt(currentUserId) })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    if (window.NotificationService) {
                        window.NotificationService.success(res.message);
                    }
                    // Recargar usuarios del rol y volver a seleccionar al usuario con permisos limpios
                    loadUsers(currentRoleId);
                    setTimeout(function () {
                        sgpSelectUser(currentUserId);
                    }, 400);
                } else {
                    if (window.NotificationService) {
                        window.NotificationService.error(res.message || 'Error al restablecer permisos');
                    }
                }
            })
            .catch(function (err) {
                console.error(err);
                if (window.NotificationService) {
                    window.NotificationService.error('Error de red al restablecer permisos');
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Restablecer permisos?',
                html: '<p style="font-size:0.92rem;color:#64748b;margin:0;">Todos los permisos personalizados de <strong>' + escH(nombre) + '</strong> volverán a los valores estándar de su rol.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-check"></i> Sí, restablecer',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#94a3b8',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    confirmAction();
                }
            });
        } else {
            if (confirm('¿Restablecer todos los permisos de ' + nombre + ' a los valores por defecto del rol?')) {
                confirmAction();
            }
        }
    };

    function escH(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    // Inicialización al cargar la vista
    loadUsers(currentRoleId);

})();
</script>
