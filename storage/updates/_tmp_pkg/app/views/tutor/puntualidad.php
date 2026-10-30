<?php
/**
 * Vista: Dashboard de Puntualidad del Tutor
 * Ruta: GET /tutor/puntualidad?rango=hoy|semana|mes|todo
 */

$rango             = $data['rango']             ?? 'mes';
$rangoLabel        = $data['rangoLabel']         ?? 'Este mes';
$ranking           = $data['ranking']            ?? [];
$historialRetardos = $data['historialRetardos']  ?? [];
$kpis              = $data['kpis']               ?? [];

require_once APPROOT . '/helpers/RetardoHelper.php';

$pct      = (float)($kpis['pct_puntual'] ?? 0);
$pctColor = $pct >= 90 ? '#16a34a' : ($pct >= 75 ? '#d97706' : '#dc2626');
$pctBg    = $pct >= 90 ? '#dcfce7' : ($pct >= 75 ? '#fef3c7' : '#fee2e2');

$rangos = [
    'hoy'    => 'Hoy',
    'semana' => 'Esta semana',
    'mes'    => 'Este mes',
    'todo'   => 'Todo el período',
];

$esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>

<style>
:root { --p-radius:16px; --p-shadow:0 2px 12px rgba(0,0,0,0.07); }
.punt-wrap { width:100%; max-width:100%; padding-bottom:80px; }

/* ── Banner ── */
.punt-banner {
    background:linear-gradient(135deg,var(--color-primary, #172554) 0%,var(--gradient-mid, #1e3a8a) 50%,var(--color-accent-brand, #2563eb) 100%);
    border-radius:20px; padding:22px 32px; margin-bottom:24px;
    display:flex; align-items:center; justify-content:space-between;
    gap:16px; position:relative; overflow:hidden; flex-wrap:wrap;
}
@keyframes puntBokeh { 0%,100%{transform:translateY(0) scale(1);opacity:.5} 50%{transform:translateY(-16px) scale(1.08);opacity:.85} }
.punt-bokeh { position:absolute;border-radius:50%;pointer-events:none;animation:puntBokeh ease-in-out infinite; }

/* ── KPI Grid ── */
.punt-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(145px,1fr)); gap:14px; margin-bottom:24px; }
.punt-kpi  { background:#fff; border-radius:var(--p-radius); box-shadow:var(--p-shadow); padding:18px 20px; display:flex; flex-direction:column; gap:4px; border-left:4px solid transparent; transition:transform .2s,box-shadow .2s; }
.punt-kpi:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.1); }
@keyframes kpiIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
.punt-kpi-val { font-size:2rem; font-weight:900; line-height:1; animation:kpiIn .5s ease both; }
.punt-kpi-lbl { font-size:0.76rem; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.4px; }
.punt-kpi-bar { height:5px; border-radius:20px; background:#e2e8f0; overflow:hidden; margin-top:8px; }
.punt-kpi-bar-fill { height:100%; border-radius:20px; }

/* ── Cards ── */
.punt-card { background:#fff; border-radius:var(--p-radius); box-shadow:var(--p-shadow); padding:22px 26px; margin-bottom:20px; }
.punt-card-title { font-size:0.95rem; font-weight:700; color:#1e293b; margin-bottom:14px; display:flex; align-items:center; gap:8px; }

/* ── Pills rango ── */
.rango-pills { display:flex; gap:6px; flex-wrap:wrap; }
.rango-pill { padding:6px 14px; border-radius:20px; font-size:0.8rem; font-weight:600; text-decoration:none; transition:all .2s; border:2px solid transparent; }
.rango-pill-active  { background:#fff; color:#1e3a8a; }
.rango-pill-inactive { background:rgba(255,255,255,0.12); color:rgba(255,255,255,0.8); }
.rango-pill-inactive:hover { background:rgba(255,255,255,0.22); }

/* ── Gauge ring ── */
.gauge-ring-fill { transition:stroke-dashoffset 1.4s cubic-bezier(0.16,1,0.3,1); }

/* ── Ranking ── */
.rank-row { display:flex; align-items:center; gap:12px; padding:13px 10px; border-bottom:1px solid #f1f5f9; transition:background .15s; border-radius:10px; }
.rank-row:last-child { border-bottom:none; }
.rank-row:hover { background:#f8fafc; }
.rank-medal { font-size:1.15rem; min-width:22px; text-align:center; flex-shrink:0; }
.rank-avatar { width:40px; height:40px; border-radius:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:0.85rem; background:linear-gradient(135deg,#4f46e5,#7c3aed); }
.rank-bar-wrap { flex:1; min-width:0; }
.rank-name  { font-size:0.85rem; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rank-depto { font-size:0.71rem; color:#94a3b8; }
.rank-bar   { height:6px; border-radius:20px; background:#e2e8f0; margin-top:6px; overflow:hidden; }
.rank-bar-fill { height:100%; border-radius:20px; transition:width .8s cubic-bezier(0.16,1,0.3,1); }
/* El % siempre en oscuro — el color va en el badge, no en el número */
.rank-pct  { font-size:1rem; font-weight:900; color:#1e293b; flex-shrink:0; min-width:46px; text-align:right; }
.rank-badges { display:flex; gap:4px; flex-shrink:0; flex-wrap:wrap; justify-content:flex-end; align-items:center; }
.rank-badge { font-size:0.68rem; font-weight:700; padding:3px 7px; border-radius:20px; }
.rank-status-pill { font-size:0.7rem; font-weight:700; padding:3px 9px; border-radius:20px; white-space:nowrap; }
.rank-pos   { font-size:0.75rem; font-weight:800; color:#cbd5e1; min-width:18px; text-align:center; flex-shrink:0; }

/* ── Search ── */
.punt-search-wrap { position:relative; margin-bottom:14px; }
.punt-search-icon { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.85rem; pointer-events:none; }
.punt-search { width:100%; padding:7px 12px 7px 32px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:0.82rem; color:#475569; outline:none; transition:border-color .2s; box-sizing:border-box; }
.punt-search:focus { border-color:#3b82f6; }

/* ── Historial table ── */
.ret-table { width:100%; border-collapse:collapse; font-size:0.83rem; }
.ret-table th { padding:9px 10px; text-align:left; font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid #f1f5f9; background:#f8fafc; }
.ret-table td { padding:9px 10px; border-bottom:1px solid #f8fafc; vertical-align:middle; }
.ret-table tr:hover td { background:#f0f7ff; }
.ret-estado { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:0.73rem; font-weight:700; }

/* ── Paginador ── */
.punt-pager { display:flex; align-items:center; justify-content:space-between; padding:10px 0 0; flex-wrap:wrap; gap:8px; }
.punt-pager-info { font-size:0.77rem; color:#94a3b8; }
.punt-pager-controls { display:flex; align-items:center; gap:4px; }
.punt-pager-btn { background:#f1f5f9; border:none; border-radius:8px; width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; color:#475569; transition:all .2s; }
.punt-pager-btn:hover:not(:disabled) { background:#dbeafe; color:#2563eb; }
.punt-pager-btn:disabled { opacity:.35; cursor:default; }
.punt-pager-num { font-size:0.78rem; font-weight:700; color:#334155; padding:0 8px; }

/* ── Scroll top ── */
#puntScrollTop { position:fixed; bottom:24px; right:24px; z-index:9999; width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--color-primary, #172554),var(--color-accent-brand, #2563eb)); color:#fff; border:none; cursor:pointer; box-shadow:0 4px 16px rgba(37,99,235,.4); opacity:0; transform:translateY(10px); transition:all .3s; display:flex; align-items:center; justify-content:center; font-size:1rem; }
#puntScrollTop.visible { opacity:1; transform:none; }

/* ── Empty state ── */
.punt-empty { text-align:center; padding:52px 24px; color:#94a3b8; }
.punt-empty i { font-size:3rem; display:block; margin-bottom:12px; }

/* ── Filter Pills ── */
.filter-pills { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.filter-pill { padding:6px 14px; border-radius:20px; font-size:0.75rem; font-weight:600; cursor:pointer; border:1px solid #e2e8f0; background:#f8fafc; color:#64748b; transition:all 0.2s; }
.filter-pill:hover { background:#f1f5f9; color:#334155; }
.filter-pill-active { background:#2563eb; color:#fff; border-color:#2563eb; box-shadow:0 2px 6px rgba(37,99,235,0.3); }

@media(max-width:768px){
    .punt-banner{flex-direction:column;align-items:flex-start;}
    .punt-kpis{grid-template-columns:repeat(2,1fr);}
    .rank-badges,.rank-pct{display:none;}
    .ret-table th:nth-child(3),.ret-table td:nth-child(3),
    .ret-table th:nth-child(4),.ret-table td:nth-child(4){display:none;}
}
</style>

<div class="punt-wrap">

<!-- BANNER -->
<div class="punt-banner">
    <!-- Partículas Bokeh -->
    <div class="punt-bokeh" style="width:60px;height:60px;background:rgba(255,255,255,0.06);top:10%;left:30%;animation-duration:7s;"></div>
    <div class="punt-bokeh" style="width:40px;height:40px;background:rgba(255,255,255,0.08);top:50%;left:55%;animation-duration:5s;animation-delay:1s;"></div>
    <div class="punt-bokeh" style="width:50px;height:50px;background:rgba(255,255,255,0.05);top:15%;left:80%;animation-duration:8s;animation-delay:2s;"></div>

    <div style="z-index:1;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:11px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                <i class="ti ti-clock-check" style="font-size:26px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:#fff;font-size:1.55rem;font-weight:800;margin:0;letter-spacing:-0.5px;">Dashboard de Puntualidad</h1>
                <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:0.85rem;font-weight:500;">
                    <i class="ti ti-calendar" style="margin-right:4px;"></i> <?= $esc($rangoLabel) ?>
                </p>
            </div>
        </div>
    </div>
    <div style="z-index:1;display:flex;flex-direction:column;align-items:flex-end;gap:12px;">
        <!-- Filtros de rango -->
        <div class="rango-pills">
            <?php foreach ($rangos as $key => $label): ?>
            <a href="<?= URLROOT ?>/tutor/puntualidad?rango=<?= $key ?>"
               class="rango-pill <?= $rango === $key ? 'rango-pill-active' : 'rango-pill-inactive' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <a href="<?= URLROOT ?>/tutor"
           style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);border-radius:10px;
                  padding:8px 16px;color:#fff;font-size:0.82rem;font-weight:600;text-decoration:none;
                  display:flex;align-items:center;gap:6px;transition:all 0.2s;backdrop-filter:blur(10px);"
           onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
            <i class="ti ti-arrow-left"></i> Panel Tutor
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="punt-kpis">
    <div class="punt-kpi" style="border-left-color:<?= $pctColor ?>;">
        <div class="punt-kpi-val punt-counter" style="color:<?= $pctColor ?>;" data-val="<?= $pct ?>">0%</div>
        <div class="punt-kpi-lbl">Puntualidad global</div>
        <div class="punt-kpi-bar">
            <div class="punt-kpi-bar-fill" style="background:<?= $pctColor ?>;width:<?= $pct ?>%;"></div>
        </div>
    </div>
    <div class="punt-kpi" style="border-left-color:#16a34a;">
        <div class="punt-kpi-val punt-counter" style="color:#16a34a;" data-val="<?= (int)($kpis['a_tiempo'] ?? 0) ?>">0</div>
        <div class="punt-kpi-lbl">A tiempo</div>
    </div>
    <div class="punt-kpi" style="border-left-color:#f59e0b;">
        <div class="punt-kpi-val punt-counter" style="color:#f59e0b;" data-val="<?= (int)($kpis['leve'] ?? 0) ?>">0</div>
        <div class="punt-kpi-lbl">Retardo leve</div>
    </div>
    <div class="punt-kpi" style="border-left-color:#dc2626;">
        <div class="punt-kpi-val punt-counter" style="color:#dc2626;" data-val="<?= (int)($kpis['severo'] ?? 0) ?>">0</div>
        <div class="punt-kpi-lbl">Retardo severo</div>
    </div>
    <div class="punt-kpi" style="border-left-color:#64748b;">
        <div class="punt-kpi-val punt-counter" style="color:#64748b;" data-val="<?= (int)($kpis['ausente'] ?? 0) ?>">0</div>
        <div class="punt-kpi-lbl">Ausentes</div>
    </div>
    <div class="punt-kpi" style="border-left-color:#1e293b;">
        <div class="punt-kpi-val punt-counter" style="color:#1e293b;" data-val="<?= (int)($kpis['total'] ?? 0) ?>">0</div>
        <div class="punt-kpi-lbl">Registros totales</div>
    </div>
</div>

<?php if (empty($ranking)): ?>
<div class="punt-empty punt-card">
    <i class="ti ti-calendar-off" style="color:#cbd5e1;"></i>
    <h3 style="color:#334155;margin:0 0 8px;">Sin registros</h3>
    <p style="margin:0;font-size:0.9rem;">No hay registros de asistencia para el rango seleccionado.</p>
</div>
<?php else: ?>

<!-- RANKING DE PUNTUALIDAD -->
<div class="punt-card">
    <div class="punt-card-title" style="justify-content:space-between;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="ti ti-trophy" style="color:#f59e0b;font-size:1.2rem;"></i>
            Ranking de puntualidad
            <span style="font-size:0.75rem;color:#64748b;font-weight:600;background:#f1f5f9;padding:2px 8px;border-radius:20px;margin-left:4px;">
                <?= count($ranking) ?> pasantes
            </span>
        </div>
        <div class="punt-search-wrap" style="margin:0;width:220px;max-width:100%;">
            <i class="ti ti-search punt-search-icon"></i>
            <input type="text" id="puntSearchInput" class="punt-search" placeholder="Buscar pasante..." onkeyup="puntSearch()">
        </div>
    </div>
    
    <!-- Filtros de Ranking -->
    <div class="filter-pills">
        <button class="filter-pill filter-pill-active punt-filter-btn" data-filter="all" onclick="puntFilter('all', this)">Todos</button>
        <button class="filter-pill punt-filter-btn" data-filter="excelente" onclick="puntFilter('excelente', this)">Excelente (≥90%)</button>
        <button class="filter-pill punt-filter-btn" data-filter="regular" onclick="puntFilter('regular', this)">Regular (≥75%)</button>
        <button class="filter-pill punt-filter-btn" data-filter="crítico" onclick="puntFilter('crítico', this)">Crítico (<75%)</button>
    </div>

    <div id="puntRankList">
        <?php foreach ($ranking as $idx => $p):
            $pctP  = (float)$p['pct_puntual'];
            $colP  = $pctP >= 90 ? '#16a34a' : ($pctP >= 75 ? '#d97706' : '#dc2626');
            $bgP   = $pctP >= 90 ? '#dcfce7' : ($pctP >= 75 ? '#fef3c7' : '#fee2e2');
            $statusLabel = $pctP >= 90 ? 'Excelente' : ($pctP >= 75 ? 'Regular' : 'Crítico');
            $ini   = strtoupper(substr(explode(' ', $p['nombre'])[0] ?? '?', 0, 1) . substr(explode(' ', $p['nombre'])[1] ?? '?', 0, 1));
            $retrasoProm = (float)$p['retraso_prom'];
        ?>
        <?php
            // Color de barra según rango pero suavizado
            $barColor = $pctP >= 90 ? '#10b981' : ($pctP >= 75 ? '#f59e0b' : '#6366f1');
        ?>
        <div class="rank-row" data-name="<?= strtolower($esc($p['nombre'])) ?>" data-status="<?= strtolower($statusLabel) ?>">
            <!-- Posición -->
            <div class="rank-medal">
                <?php if($idx===0):?>🥇<?php elseif($idx===1):?>🥈<?php elseif($idx===2):?>🥉<?php else:?><span class="rank-pos"><?= $idx+1 ?></span><?php endif;?>
            </div>

            <!-- Avatar siempre índigo -->
            <div class="rank-avatar"><?= $esc($ini) ?></div>

            <!-- Nombre + barra -->
            <div class="rank-bar-wrap">
                <div class="rank-name"><?= $esc($p['nombre']) ?></div>
                <div class="rank-depto"><?= $esc($p['departamento']) ?></div>
                <div class="rank-bar">
                    <div class="rank-bar-fill" style="width:<?= $pctP ?>%;background:<?= $barColor ?>;"></div>
                </div>
            </div>

            <!-- % en color neutro oscuro — sin alarma visual -->
            <div class="rank-pct"><?= $pctP ?>%</div>

            <!-- Estado como pill pequeña -->
            <div style="flex-shrink:0;">
                <span class="rank-status-pill" style="background:<?= $bgP ?>;color:<?= $colP ?>;"><?= $statusLabel ?></span>
            </div>

            <!-- Conteo desglose -->
            <div class="rank-badges" style="min-width:110px;">
                <?php if ($p['a_tiempo'] > 0): ?>
                <span class="rank-badge" style="background:#dcfce7;color:#16a34a;" title="A tiempo">
                    <i class="ti ti-check" style="font-size:.65rem;"></i> <?= $p['a_tiempo'] ?>
                </span>
                <?php endif; ?>
                <?php if ($p['leve'] > 0): ?>
                <span class="rank-badge" style="background:#fef3c7;color:#d97706;" title="Retardo leve">
                    <i class="ti ti-clock-exclamation" style="font-size:.65rem;"></i> <?= $p['leve'] ?>
                </span>
                <?php endif; ?>
                <?php if ($p['severo'] > 0): ?>
                <span class="rank-badge" style="background:#fee2e2;color:#dc2626;" title="Retardo severo">
                    <i class="ti ti-clock-x" style="font-size:.65rem;"></i> <?= $p['severo'] ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Almanaque -->
            <div style="flex-shrink:0;text-align:right;min-width:90px;">
                <a href="<?= URLROOT ?>/asistencias/almanaque/<?= (int)$p['pasante_id'] ?>"
                   class="pjax-link"
                   style="font-size:0.75rem;color:#6366f1;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:8px;background:#f0f0ff;transition:all .2s;"
                   onmouseover="this.style.background='#e0e0ff'" onmouseout="this.style.background='#f0f0ff'">
                    <i class="ti ti-calendar-stats"></i> Ver
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Empty State Ranking -->
    <div id="puntEmptyFilter" style="display:none; text-align:center; padding:40px 20px; color:#94a3b8;">
        <i class="ti ti-search" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:8px; display:block;"></i>
        <h4 style="margin:0; color:#475569; font-size:1.1rem;">Sin resultados</h4>
        <p style="margin:4px 0 0; font-size:0.85rem;">No se encontraron pasantes con este filtro o búsqueda.</p>
    </div>

    <!-- Paginador Ranking -->
    <div class="punt-pager" id="puntPager">
        <div class="punt-pager-info">Mostrando <span id="puntPagerStart">0</span> - <span id="puntPagerEnd">0</span> de <span id="puntPagerTotal">0</span> pasantes</div>
        <div class="punt-pager-controls">
            <button class="punt-pager-btn" id="puntBtnPrev" onclick="puntChangePage(-1)" title="Anterior"><i class="ti ti-chevron-left"></i></button>
            <span class="punt-pager-num" id="puntPageNum">1</span>
            <button class="punt-pager-btn" id="puntBtnNext" onclick="puntChangePage(1)" title="Siguiente"><i class="ti ti-chevron-right"></i></button>
        </div>
    </div>
</div>

<?php if (!empty($historialRetardos)): ?>
<!-- HISTORIAL DETALLADO DE RETARDOS -->
<div class="punt-card" style="margin-bottom:0;">
    <div class="punt-card-title" style="justify-content:space-between;flex-wrap:wrap;margin-bottom:12px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="ti ti-list-details" style="color:#dc2626;font-size:1.2rem;"></i>
            Últimos retardos
            <span style="font-size:0.75rem;color:#64748b;font-weight:600;background:#f1f5f9;padding:2px 8px;border-radius:20px;margin-left:4px;">
                <?= count($historialRetardos) ?> registros
            </span>
        </div>
        <div class="punt-search-wrap" style="margin:0;width:220px;max-width:100%;">
            <i class="ti ti-search punt-search-icon"></i>
            <input type="text" id="retSearchInput" class="punt-search" placeholder="Buscar en historial..." onkeyup="retSearch()">
        </div>
    </div>
    
    <!-- Filtros de clasificación -->
    <div class="filter-pills">
        <button class="filter-pill filter-pill-active ret-filter-btn" data-filter="all" onclick="retFilter('all', this)">Todos</button>
        <button class="filter-pill ret-filter-btn" data-filter="a tiempo" onclick="retFilter('a tiempo', this)">A tiempo</button>
        <button class="filter-pill ret-filter-btn" data-filter="leve" onclick="retFilter('leve', this)">Leve</button>
        <button class="filter-pill ret-filter-btn" data-filter="severo" onclick="retFilter('severo', this)">Severo</button>
        <button class="filter-pill ret-filter-btn" data-filter="ausente" onclick="retFilter('ausente', this)">Ausente</button>
    </div>

    <div style="overflow-x:auto;">
    <table class="ret-table">
        <thead>
            <tr>
                <th>Pasante</th>
                <th>Fecha</th>
                <th>Llegada</th>
                <th>Asignada</th>
                <th>Retraso</th>
                <th>Clasificación</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($historialRetardos as $r):
            $horaReal = $r->hora_entrada ?? null;
            $horaAsig = $r->hora_asignacion ?? '08:00:00';
            $analisis = RetardoHelper::analizar($horaReal, $horaAsig, $r->estado ?? 'Presente');
            $nombre   = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));
            $horaFmt  = $horaReal ? substr($horaReal, 0, 5) : '—';
            $asigFmt  = substr($horaAsig, 0, 5);
            $retrMin  = $analisis['retraso_min'] ?? 0;
            $retrFmt  = $retrMin > 0
                ? ($retrMin >= 60 ? intdiv($retrMin, 60) . 'h ' . ($retrMin % 60) . 'm' : $retrMin . ' min')
                : '—';
        ?>
        <tr class="ret-row" data-name="<?= strtolower($esc($nombre)) ?>" data-status="<?= strtolower($esc($analisis['etiqueta'])) ?>">
            <td style="font-weight:700;color:#1e293b;"><?= $esc($nombre) ?></td>
            <td style="color:#475569;"><?= date('d/m/Y', strtotime($r->fecha)) ?></td>
            <td style="font-family:monospace;font-weight:800;color:#1e293b;font-size:0.85rem;"><?= $horaFmt ?></td>
            <td style="font-family:monospace;color:#94a3b8;font-size:0.85rem;"><?= $asigFmt ?></td>
            <td style="font-weight:800;color:<?= $analisis['color'] ?>;"><?= $retrFmt ?></td>
            <td>
                <span class="ret-estado" style="background:<?= $analisis['color'] ?>18;color:<?= $analisis['color'] ?>;">
                    <i class="ti <?= $esc($analisis['icono']) ?>" style="font-size:0.8rem;"></i>
                    <?= $esc($analisis['etiqueta']) ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Empty State Historial -->
    <div id="retEmptyFilter" style="display:none; text-align:center; padding:40px 20px; color:#94a3b8;">
        <i class="ti ti-search" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:8px; display:block;"></i>
        <h4 style="margin:0; color:#475569; font-size:1.1rem;">Sin resultados</h4>
        <p style="margin:4px 0 0; font-size:0.85rem;">No hay registros que coincidan con este filtro o búsqueda.</p>
    </div>

    <!-- Paginador Historial de Retardos -->
    <div class="punt-pager" id="retPager">
        <div class="punt-pager-info">Mostrando <span id="retPagerStart">0</span> - <span id="retPagerEnd">0</span> de <span id="retPagerTotal">0</span> registros</div>
        <div class="punt-pager-controls">
            <button class="punt-pager-btn" id="retBtnPrev" onclick="retChangePage(-1)" title="Anterior"><i class="ti ti-chevron-left"></i></button>
            <span class="punt-pager-num" id="retPageNum">1</span>
            <button class="punt-pager-btn" id="retBtnNext" onclick="retChangePage(1)" title="Siguiente"><i class="ti ti-chevron-right"></i></button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Botón Scroll Top -->
<button id="puntScrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Volver arriba">
    <i class="ti ti-arrow-up"></i>
</button>

</div><!-- /punt-wrap -->

<!-- JS PARA INTERACTIVIDAD -->
<script>
// --- Paginación del Ranking --- [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta sin reload completo.
var puntPage = 1;
var puntLimit = 10;
var puntRowsAll = [];
var puntRowsFiltered = [];
var currentPuntFilter = 'all';

function initPuntPager() {
    const list = document.getElementById('puntRankList');
    if(!list) return;
    puntRowsAll = Array.from(list.querySelectorAll('.rank-row'));
    puntRowsFiltered = [...puntRowsAll];
    renderPuntPage();
}

function applyPuntFilters() {
    const query = document.getElementById('puntSearchInput').value.toLowerCase();
    
    puntRowsFiltered = puntRowsAll.filter(row => {
        const name = row.getAttribute('data-name') || '';
        const status = row.getAttribute('data-status') || '';
        
        const matchesQuery = query === '' || name.includes(query);
        const matchesFilter = currentPuntFilter === 'all' || status.includes(currentPuntFilter);
        
        if (!matchesQuery || !matchesFilter) {
            row.style.display = 'none';
            return false;
        }
        return true;
    });
    
    puntPage = 1;
    renderPuntPage();
}

function puntSearch() {
    applyPuntFilters();
}

function puntFilter(status, btn) {
    currentPuntFilter = status;
    document.querySelectorAll('.punt-filter-btn').forEach(b => {
        b.classList.remove('filter-pill-active');
    });
    btn.classList.add('filter-pill-active');
    
    applyPuntFilters();
}

function renderPuntPage() {
    const total = puntRowsFiltered.length;
    const emptyMsg = document.getElementById('puntEmptyFilter');
    
    if(total === 0) {
        document.getElementById('puntPager').style.display = 'none';
        if(emptyMsg) emptyMsg.style.display = 'block';
        return;
    } else {
        if(emptyMsg) emptyMsg.style.display = 'none';
    }
    
    const start = (puntPage - 1) * puntLimit;
    const end = Math.min(start + puntLimit, total);
    
    puntRowsFiltered.forEach((row, i) => {
        row.style.display = (i >= start && i < end) ? 'flex' : 'none';
    });
    
    const totalPages = Math.ceil(total / puntLimit);
    document.getElementById('puntBtnPrev').disabled = puntPage === 1;
    document.getElementById('puntBtnNext').disabled = puntPage === totalPages || totalPages === 0;
    
    document.getElementById('puntPagerStart').innerText = total > 0 ? start + 1 : 0;
    document.getElementById('puntPagerEnd').innerText = end;
    document.getElementById('puntPagerTotal').innerText = total;
    document.getElementById('puntPageNum').innerText = puntPage;
    
    document.getElementById('puntPager').style.display = total <= puntLimit ? 'none' : 'flex';
}

function puntChangePage(dir) {
    puntPage += dir;
    renderPuntPage();
}

// --- Paginación del Historial de Retardos --- [SGP-FIX-PJAX-REDECL]
var retPage = 1;
var retLimit = 10;
var retRowsAll = [];
var retRowsFiltered = [];
var currentRetFilter = 'all';

function initRetPager() {
    const table = document.querySelector('.ret-table');
    if(!table) return;
    retRowsAll = Array.from(table.querySelectorAll('.ret-row'));
    retRowsFiltered = [...retRowsAll];
    renderRetPage();
}

function applyRetFilters() {
    const query = document.getElementById('retSearchInput').value.toLowerCase();
    
    retRowsFiltered = retRowsAll.filter(row => {
        const name = row.getAttribute('data-name') || '';
        const status = row.getAttribute('data-status') || '';
        
        const matchesQuery = query === '' || name.includes(query);
        const matchesFilter = currentRetFilter === 'all' || status.includes(currentRetFilter);
        
        if (!matchesQuery || !matchesFilter) {
            row.style.display = 'none';
            return false;
        }
        return true;
    });
    
    retPage = 1;
    renderRetPage();
}

function retSearch() {
    applyRetFilters();
}

function retFilter(status, btn) {
    currentRetFilter = status;
    document.querySelectorAll('.ret-filter-btn').forEach(b => {
        b.classList.remove('filter-pill-active');
    });
    btn.classList.add('filter-pill-active');
    
    applyRetFilters();
}

function renderRetPage() {
    const total = retRowsFiltered.length;
    const emptyMsg = document.getElementById('retEmptyFilter');
    const tableEl = document.querySelector('.ret-table');
    
    if(total === 0) {
        document.getElementById('retPager').style.display = 'none';
        if(emptyMsg) emptyMsg.style.display = 'block';
        if(tableEl) tableEl.style.display = 'none';
        return;
    } else {
        if(emptyMsg) emptyMsg.style.display = 'none';
        if(tableEl) tableEl.style.display = 'table';
    }
    
    const start = (retPage - 1) * retLimit;
    const end = Math.min(start + retLimit, total);
    
    retRowsFiltered.forEach((row, i) => {
        row.style.display = (i >= start && i < end) ? 'table-row' : 'none';
    });
    
    const totalPages = Math.ceil(total / retLimit);
    document.getElementById('retBtnPrev').disabled = retPage === 1;
    document.getElementById('retBtnNext').disabled = retPage === totalPages || totalPages === 0;
    
    document.getElementById('retPagerStart').innerText = total > 0 ? start + 1 : 0;
    document.getElementById('retPagerEnd').innerText = end;
    document.getElementById('retPagerTotal').innerText = total;
    document.getElementById('retPageNum').innerText = retPage;
    
    document.getElementById('retPager').style.display = total <= retLimit ? 'none' : 'flex';
}

function retChangePage(dir) {
    retPage += dir;
    renderRetPage();
}


// --- Scroll to Top ---
window.addEventListener('scroll', function() {
    const btn = document.getElementById('puntScrollTop');
    if(!btn) return;
    if(window.scrollY > 300) btn.classList.add('visible');
    else btn.classList.remove('visible');
});

// --- Contadores Animados ---
function animatePuntCounters() {
    const counters = document.querySelectorAll('.punt-counter');
    counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-val'));
        if(isNaN(target) || target === 0) return;
        
        const isPct = counter.innerText.includes('%');
        const duration = 1200;
        const startTime = performance.now();
        
        const update = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const ease = 1 - Math.pow(1 - progress, 4);
            let current = target * ease;
            
            if(isPct) {
                counter.innerText = current.toFixed(1).replace('.0','') + '%';
            } else {
                counter.innerText = Math.round(current);
            }
            
            if(progress < 1) requestAnimationFrame(update);
            else counter.innerText = isPct ? target + '%' : target;
        };
        requestAnimationFrame(update);
    });
}

// Inicializar todo cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    initPuntPager();
    initRetPager();
    setTimeout(animatePuntCounters, 100);
});
// Re-inicializar en navegación PJAX
document.addEventListener('pjax:complete', () => {
    initPuntPager();
    initRetPager();
    setTimeout(animatePuntCounters, 100);
});
</script>
