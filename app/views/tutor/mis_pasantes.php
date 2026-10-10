<?php
/**
 * Tutor — Lista de Mis Pasantes
 */
?>
<style>
/* =========================================================
   DISEÑO PREMIUM - VISTA DE PASANTES (TUTOR)
   ========================================================= */
.tutor-pasantes {
    width: 100%;
}

/* --- BANNER PRINCIPAL (TUTOR) --- */
.mp-banner {
    background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
    border-radius: 20px;
    padding: 28px 36px;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.mp-banner::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
.mp-banner-left  { display:flex; align-items:center; gap:14px; z-index:1; }
.mp-banner-icon  { background:rgba(255,255,255,0.15); border-radius:14px; padding:13px; }
.mp-banner-right { display:flex; z-index:1; }
@media(max-width:640px) { .mp-banner { flex-direction:column; align-items:flex-start; } }

/* --- KPI CARDS --- */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 1.5rem;
}
.kpi-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
}
.kpi-card:hover { transform: translateY(-3px); }
.kpi-card.b-indigo  { border-left: 4px solid #6366f1; }
.kpi-card.b-emerald { border-left: 4px solid #10b981; }
.kpi-card.b-amber   { border-left: 4px solid #f59e0b; }
.kpi-card.b-violet  { border-left: 4px solid #7c3aed; }
.kpi-card:hover.b-indigo  { box-shadow: 0 10px 22px rgba(99,102,241,.2); }
.kpi-card:hover.b-emerald { box-shadow: 0 10px 22px rgba(16,185,129,.2); }
.kpi-card:hover.b-amber   { box-shadow: 0 10px 22px rgba(245,158,11,.2); }
.kpi-card:hover.b-violet  { box-shadow: 0 10px 22px rgba(124,58,237,.2); }
.kpi-details h3 {
    font-size: 0.78rem; font-weight: 600; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 6px;
}
.kpi-details .kpi-value {
    font-size: 2.2rem; font-weight: 800; line-height: 1; margin: 0;
}
.kpi-card.b-indigo  .kpi-value { color: #6366f1; }
.kpi-card.b-emerald .kpi-value { color: #10b981; }
.kpi-card.b-amber   .kpi-value { color: #f59e0b; }
.kpi-card.b-violet  .kpi-value { color: #7c3aed; }
.kpi-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.bg-indigo-light  { background: #ede9fe; color: #6366f1; }
.bg-emerald-light { background: #d1fae5; color: #10b981; }
.bg-amber-light   { background: #fef3c7; color: #f59e0b; }
.bg-violet-light  { background: #ede9fe; color: #7c3aed; }

/* --- ACCIONES Y BARRA DE BÚSQUEDA --- */
.action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.search-wrapper {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-wrapper i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.search-input {
    width: 100%;
    padding: 0.75rem 2.5rem 0.75rem 2.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
    color: #1e293b;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.btn-primary-indigo {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary-indigo:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    color: white;
}

/* --- BENTO UI GRID DE PASANTES --- */
.bento-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.bento-card-wrapper {
    background: #ffffff;
    border-radius: 24px;
    padding: 1.25rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.bento-card-wrapper:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(79, 70, 229, 0.12);
    border-color: rgba(79, 70, 229, 0.2);
}

.bento-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(to right, #f8fafc, #ffffff);
    padding: 1rem;
    border-radius: 18px;
    border: 1px solid rgba(0,0,0,0.03);
    margin-bottom: 0.75rem;
}

.b-avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 800;
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    flex-shrink: 0;
}

.b-info {
    flex: 1;
    min-width: 0;
}

.b-name {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 0.2rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.b-depto {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.b-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.b-badge-activo { background: rgba(16,185,129,0.1); color: #10b981; }
.b-badge-pendiente { background: rgba(245,158,11,0.1); color: #f59e0b; }
.b-badge-finalizado { background: rgba(99,102,241,0.1); color: #6366f1; }
.b-badge-retirado { background: rgba(239,68,68,0.1); color: #ef4444; }
.b-badge-default { background: rgba(100,116,139,0.1); color: #64748b; }

/* Grid 2x2 interno (Bento blocks) */
.bento-blocks-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.bento-box {
    background: #f1f5f9;
    border-radius: 16px;
    padding: 1.15rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: background 0.2s;
    border: 1px solid rgba(0,0,0,0.02);
}

.bento-box:hover {
    background: #e2e8f0;
}

.b-val {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 0.35rem;
}

.b-val.highlight-val {
    color: #4f46e5;
}

.b-lbl {
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.b-sub {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 0.5rem;
    font-weight: 500;
}

/* Progress bar en bento box */
.b-progress-wrap {
    margin-top: 0.8rem;
    width: 100%;
}
.b-progress-bg {
    height: 6px;
    background: #cbd5e1;
    border-radius: 999px;
    overflow: hidden;
}
.b-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4f46e5, #6366f1);
    border-radius: 999px;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Actions */
.bento-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: auto;
}

.btn-bento {
    flex: 1;
    padding: 0.8rem;
    border-radius: 14px;
    font-weight: 700;
    font-size: 0.85rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

.btn-bento-primary {
    background: #4f46e5;
    color: white;
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
}
.btn-bento-primary:hover {
    background: #4338ca;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
}

.btn-bento-secondary {
    background: #f1f5f9;
    color: #475569;
}
.btn-bento-secondary:hover {
    background: #e2e8f0;
    color: #1e293b;
    transform: translateY(-2px);
}

/* Empty State Premium */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #f8fafc;
    border-radius: 24px;
    border: 2px dashed #e2e8f0;
}

.empty-icon {
    font-size: 4rem;
    color: #64748b;
    opacity: 0.5;
    margin-bottom: 1rem;
}

.empty-text {
    font-size: 1.25rem;
    color: #1e293b;
    font-weight: 800;
}

/* ── tp-btn (acciones de tarjeta y modal) ── */
.tp-btn { padding:.45rem .9rem; border-radius:8px; font-size:.83rem; font-weight:600; border:none; cursor:pointer; transition:background .15s; text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; }
.tp-btn-primary   { background:#6366f1; color:#fff; }
.tp-btn-primary:hover   { background:#4f46e5; color:#fff; }
.tp-btn-secondary { background:#e2e8f0; color:#475569; }
.tp-btn-secondary:hover { background:#cbd5e1; }

/* ── PIN Modal ── */
.pin-modal-backdrop {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
    z-index:1080; align-items:center; justify-content:center;
}
.pin-modal-backdrop.open { display:flex; }
.pin-modal {
    background:#fff; border-radius:20px; padding:2rem;
    width:100%; max-width:400px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    animation:pinSlideUp .25s cubic-bezier(.34,1.56,.64,1);
}
@keyframes pinSlideUp {
    from { opacity:0; transform:translateY(20px) scale(.97); }
    to   { opacity:1; transform:translateY(0)    scale(1);   }
}
.pin-modal-title { font-size:1.15rem; font-weight:800; color:#162660; margin:0 0 .25rem; }
.pin-modal-sub   { font-size:.85rem; color:#64748b; margin:0 0 1.25rem; }
.pin-field { margin-bottom:.9rem; }
.pin-field label { display:block; font-size:.78rem; font-weight:600; color:#64748b; margin-bottom:.35rem; }
.pin-field input {
    width:100%; padding:.65rem 1rem; border:1px solid #e2e8f0; border-radius:10px;
    background:#fff; color:#1e293b; font-size:1.1rem; letter-spacing:4px;
    text-align:center; font-weight:700;
}
.pin-field input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.pin-modal-actions { display:flex; gap:.75rem; margin-top:1.25rem; }
.pin-modal-actions .tp-btn { flex:1; justify-content:center; padding:.65rem; }
</style>

<div class="tutor-pasantes">
    <!-- BANNER PRINCIPAL -->
    <div class="mp-banner">
        <div class="mp-banner-left">
            <div class="mp-banner-icon">
                <i class="ti ti-users-group" style="font-size:28px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:#fff;font-size:1.6rem;font-weight:800;margin:0;">Mis Pasantes</h1>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:.88rem;">
                    <i class="ti ti-school"></i> Supervisión y Seguimiento en Tiempo Real
                </p>
            </div>
        </div>
        <div class="mp-banner-right">
            <a href="<?= URLROOT ?>/evaluaciones" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);border-radius:10px;padding:8px 16px;color:#fff;font-size:.83rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
                <i class="ti ti-list"></i> Ver Evaluaciones
            </a>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="kpi-grid">
        <div class="kpi-card b-indigo">
            <div class="kpi-details">
                <h3>Total Pasantes</h3>
                <p class="kpi-value" data-kpi-value="<?= $total ?>"><?= $total ?></p>
            </div>
            <a href="<?= URLROOT ?>/tutor/pasantes" class="kpi-icon bg-indigo-light" title="Ver pasantes" style="text-decoration:none;">
                <i class="ti ti-users-group"></i>
            </a>
        </div>
        <div class="kpi-card b-emerald">
            <div class="kpi-details">
                <h3>Activos</h3>
                <p class="kpi-value" data-kpi-value="<?= $activos ?>"><?= $activos ?></p>
            </div>
            <a href="<?= URLROOT ?>/tutor/asistencias" class="kpi-icon bg-emerald-light" title="Ver asistencias" style="text-decoration:none;">
                <i class="ti ti-user-check"></i>
            </a>
        </div>
        <div class="kpi-card b-amber">
            <div class="kpi-details">
                <h3>Sin Evaluar</h3>
                <p class="kpi-value" data-kpi-value="<?= $pendientesEval ?? 0 ?>"><?= $pendientesEval ?? 0 ?></p>
            </div>
            <a href="<?= URLROOT ?>/evaluaciones" class="kpi-icon bg-amber-light" title="Ir a evaluaciones" style="text-decoration:none;">
                <i class="ti ti-star"></i>
            </a>
        </div>
        <div class="kpi-card b-violet">
            <div class="kpi-details">
                <h3>Puntualidad</h3>
                <p class="kpi-value" data-kpi-value="<?= $pctPuntualidad ?? 0 ?>"><?= $pctPuntualidad ?? 0 ?>%</p>
            </div>
            <a href="<?= URLROOT ?>/tutor/puntualidad" class="kpi-icon bg-violet-light" title="Dashboard puntualidad" style="text-decoration:none;">
                <i class="ti ti-clock-check"></i>
            </a>
        </div>
    </div>

    <!-- ACTION BAR -->
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:1.5rem; flex-wrap:wrap;">
        <!-- Filtros de estado -->
        <div style="display:flex; gap:6px; background:#f1f5f9; border-radius:12px; padding:5px;">
            <button class="tp-filtro activo" data-filtro="todos"
                    onclick="filtrarPasantes(this,'todos')"
                    style="padding:7px 16px; border:none; border-radius:9px; font-size:.82rem; font-weight:600; cursor:pointer; transition:all .2s; background:#6366f1; color:#fff;">
                <i class="ti ti-users"></i> Todos
            </button>
            <button class="tp-filtro" data-filtro="activo"
                    onclick="filtrarPasantes(this,'activo')"
                    style="padding:7px 16px; border:none; border-radius:9px; font-size:.82rem; font-weight:600; cursor:pointer; transition:all .2s; background:transparent; color:#64748b;">
                <i class="ti ti-user-check"></i> Activos
            </button>
            <button class="tp-filtro" data-filtro="finalizado"
                    onclick="filtrarPasantes(this,'finalizado')"
                    style="padding:7px 16px; border:none; border-radius:9px; font-size:.82rem; font-weight:600; cursor:pointer; transition:all .2s; background:transparent; color:#64748b;">
                <i class="ti ti-user-off"></i> Finalizados
            </button>
        </div>
        <!-- Buscador -->
        <div style="position:relative; flex:1; min-width:200px;">
            <i class="ti ti-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
            <input type="text" id="buscarPasante"
                   autocomplete="new-password"
                   readonly
                   placeholder="Buscar por nombre o cédula..."
                   style="width:100%; padding:10px 12px 10px 36px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.9rem; color:#1e293b; background:#fff; outline:none; transition:border-color .2s;"
                   onfocus="this.removeAttribute('readonly'); this.style.borderColor='#6366f1';"
                   onblur="this.style.borderColor='#e2e8f0'">
        </div>
    </div>

    <!-- BENTO GRID DE PASANTES -->
    <?php if (empty($pasantes)): ?>
        <div class="empty-state">
            <i class="ti ti-users empty-icon"></i>
            <h3 class="empty-text">No tienes pasantes asignados aún</h3>
            <p style="color: #64748b; margin-top: 0.5rem;">Cuando se te asigne un pasante, aparecerá en tu panel bento.</p>
        </div>
    <?php else: ?>
    <div class="bento-grid" id="pasantesGrid">
        <?php foreach ($pasantes as $p):
            $nombre  = trim(($p->nombres ?? '') . ' ' . ($p->apellidos ?? ''));
            $iniciales = strtoupper(substr($p->nombres ?? 'P', 0, 1) . substr($p->apellidos ?? 'A', 0, 1));
            $pct     = (float)($p->progreso_pct ?? 0);
            $estado  = strtolower($p->estado_pasantia ?? 'sin asignar');
            
            $badgeClass = match($p->estado_pasantia ?? '') {
                'Activo'     => 'b-badge-activo',
                'Pendiente'  => 'b-badge-pendiente',
                'Finalizado' => 'b-badge-finalizado',
                'Retirado'   => 'b-badge-retirado',
                default      => 'b-badge-default',
            };
            
            $puntaje = $p->promedio_eval ? number_format($p->promedio_eval, 1) : '—';
        ?>
        <div class="bento-card-wrapper tp-card"
             data-search="<?= htmlspecialchars(strtolower($nombre . ' ' . ($p->cedula ?? '') . ' ' . ($p->departamento ?? ''))) ?>"
             data-estado="<?= htmlspecialchars(strtolower($p->estado_pasantia ?? '')) ?>">
            
            <!-- Bento Box: Profile Header -->
            <div class="bento-header">
                <div class="b-avatar"><?= $iniciales ?></div>
                <div class="b-info">
                    <h3 class="b-name" title="<?= htmlspecialchars($nombre) ?>"><?= htmlspecialchars($nombre) ?></h3>
                    <p class="b-depto"><i class="ti ti-id me-1"></i>V-<?= htmlspecialchars($p->cedula ?? '—') ?></p>
                </div>
                <span class="b-badge <?= $badgeClass ?>"><?= htmlspecialchars($p->estado_pasantia ?? 'N/A') ?></span>
            </div>

            <!-- Bento Boxes: Stats Grid -->
            <div class="bento-blocks-grid">
                
                <!-- Box 1: Progreso Pro-Rata -->
                <div class="bento-box pb-3">
                    <div class="b-val highlight-val"><?= $pct ?>%</div>
                    <div class="b-lbl">Progreso</div>
                    <div class="b-progress-wrap">
                        <div class="b-progress-bg">
                            <div class="b-progress-fill" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </div>
                    <div class="b-sub"><?= $p->dias_acumulados ?? 0 ?> días acumulados</div>
                </div>

                <!-- Box 2: Evaluaciones & Asistencia -->
                <div class="bento-box pb-3">
                    <div class="b-val"><?= $puntaje ?></div>
                    <div class="b-lbl">Promedio Eval.</div>
                    <div class="b-sub" style="margin-top: 0.8rem;"><i class="ti ti-file-analytics me-1"></i><?= $p->total_evaluaciones ?? 0 ?> eval(s)</div>
                    <div class="b-sub mt-1"><i class="ti ti-calendar me-1"></i>Últ: <?= $p->ultima_asistencia !== '—' ? date('d/m', strtotime($p->ultima_asistencia)) : 'N/A' ?></div>
                </div>

            </div>

            <!-- Bento Box: Actions Container -->
            <div class="bento-actions">
                <a href="<?= URLROOT ?>/tutor/perfil/<?= $p->pasante_id ?>" class="btn-bento btn-bento-primary">
                    <i class="ti ti-user"></i> Ver Perfil
                </a>
                <a href="<?= URLROOT ?>/asistencias/almanaque/<?= $p->pasante_id ?>" class="btn-bento btn-bento-secondary" style="color:#0ea5e9;background:#e0f2fe;border-color:#bae6fd;" title="Ver almanaque de asistencia">
                    <i class="ti ti-calendar-stats"></i> Almanaque
                </a>
                <?php if ($p->estado_pasantia === 'Activo'): ?>
                <button class="btn-bento btn-bento-secondary" onclick="abrirResetPin(<?= $p->pasante_id ?>, '<?= htmlspecialchars($nombre) ?>')" title="Restablecer PIN">
                    <i class="ti ti-key"></i> PIN
                </button>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal: Resetear PIN -->

<div class="pin-modal-backdrop" id="pinModalBackdrop">
    <div class="pin-modal">
        <h3 class="pin-modal-title">Resetear PIN</h3>
        <p class="pin-modal-sub" id="pinModalNombre"></p>
        <div class="pin-field">
            <label style="display:flex;align-items:center;gap:6px;">Nuevo PIN (4 dígitos) <i class="ti ti-help-circle" style="color:#2563eb;font-size:1.1em;cursor:help;" data-tooltip="Ingrese un código numérico de 4 dígitos"></i></label>
            <input type="password" id="nuevoPinInput" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="• • • •">
        </div>
        <div class="pin-field">
            <label style="display:flex;align-items:center;gap:6px;">Confirmar PIN <i class="ti ti-help-circle" style="color:#2563eb;font-size:1.1em;cursor:help;" data-tooltip="Vuelva a escribir el PIN para confirmarlo"></i></label>
            <input type="password" id="confirmarPinInput" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="• • • •">
        </div>
        <div class="pin-modal-actions">
            <button class="tp-btn tp-btn-secondary" onclick="cerrarPinModal()">Cancelar</button>
            <button class="tp-btn tp-btn-primary" id="btnGuardarPin" onclick="guardarPin()">
                <i class="ti ti-check"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
// Filtro combinado: texto + estado — [SGP-FIX-PJAX-REDECL]
var _filtroEstado = 'todos';

function aplicarFiltros() {
    const q = (document.getElementById('buscarPasante')?.value || '').toLowerCase().trim();
    document.querySelectorAll('#pasantesGrid .tp-card').forEach(card => {
        const matchTexto = (q === '' || card.dataset.search.includes(q));
        const matchEstado = (_filtroEstado === 'todos' || card.dataset.estado === _filtroEstado);
        card.style.display = (matchTexto && matchEstado) ? '' : 'none';
    });
}

function filtrarPasantes(btn, estado) {
    _filtroEstado = estado;
    document.querySelectorAll('.tp-filtro').forEach(b => {
        b.style.background = 'transparent';
        b.style.color = '#64748b';
    });
    btn.style.background = '#6366f1';
    btn.style.color = '#fff';
    aplicarFiltros();
}

var _buscador = document.getElementById('buscarPasante'); // [SGP-FIX-PJAX-REDECL]
if (_buscador) {
    // Limpiar cualquier valor inyectado por autocompletar del navegador
    _buscador.value = '';
    _buscador.addEventListener('input', aplicarFiltros);
}

// PIN Modal — [SGP-FIX-PJAX-REDECL]
var _pinPasanteId = null;
function abrirResetPin(id, nombre) {
    _pinPasanteId = id;
    document.getElementById('pinModalNombre').textContent = nombre;
    document.getElementById('nuevoPinInput').value = '';
    document.getElementById('confirmarPinInput').value = '';
    document.getElementById('pinModalBackdrop').classList.add('open');
}
function cerrarPinModal() {
    document.getElementById('pinModalBackdrop').classList.remove('open');
    _pinPasanteId = null;
}
async function guardarPin() {
    const pin1 = document.getElementById('nuevoPinInput').value.trim();
    const pin2 = document.getElementById('confirmarPinInput').value.trim();
    if (!/^\d{4}$/.test(pin1)) return Swal.fire('Error', 'El PIN debe tener exactamente 4 dígitos numéricos.', 'error');
    if (pin1 !== pin2) return Swal.fire('Error', 'Los PINs no coinciden.', 'error');

    const btn = document.getElementById('btnGuardarPin');
    btn.disabled = true;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const fd = new FormData();
    fd.append('pasante_id', _pinPasanteId);
    fd.append('nuevo_pin', pin1);
    fd.append('csrf_token', csrf);

    try {
        const res = await fetch(`${URLROOT}/tutor/resetPin`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf}, body:fd });
        const data = await res.json();
        if (data.success) {
            Swal.fire('<i class="ti ti-circle-check"></i> Éxito', data.message, 'success');
            cerrarPinModal();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
    } finally {
        btn.disabled = false;
    }
}
document.getElementById('pinModalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) cerrarPinModal();
});
</script>
