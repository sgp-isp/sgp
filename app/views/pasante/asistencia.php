<?php
// Mi Asistencia — Pasante SGP v3 Premium (Tabs: Semanal / Mensual / Total)
$pasante     = $data['pasante']     ?? null;
$asistencias = $data['asistencias'] ?? [];
$proRata     = $data['proRata']     ?? null;

$estadoPasantia = $pasante->estado_pasantia ?? 'Pendiente';
$sinAsignar     = in_array($estadoPasantia, ['Sin Asignar', 'Pendiente', '']);

$diasValidos  = (int)($proRata->dias_presentes  ?? 0);
$horasAcum    = (int)($proRata->horas_mostradas ?? 0);
$horasMeta    = (int)($proRata->horas_meta      ?? 1440);
$pct          = $horasMeta > 0 ? min(100, round($horasAcum / $horasMeta * 100)) : 0;

$cPresentes   = 0; $cAusentes = 0; $cJustificados = 0;
foreach ($asistencias as $a) {
    $est = $a->estado ?? '';
    if ($est === 'Presente')      $cPresentes++;
    elseif ($est === 'Ausente')   $cAusentes++;
    elseif ($est === 'Justificado') $cJustificados++;
}
$totalReg  = count($asistencias);
$porcAsist = $totalReg > 0 ? round(($cPresentes + $cJustificados) / $totalReg * 100) : 0;

// Construir mapa fecha → estado para el calendario mensual
$actMap = [];
foreach ($asistencias as $a) {
    if (!empty($a->fecha)) {
        $actMap[$a->fecha] = [
            'estado' => $a->estado ?? '',
            'hora'   => $a->hora_registro ?? '',
            'metodo' => $a->metodo ?? '',
        ];
    }
}

// Semana actual (lun → dom)
$hoy         = new DateTime();
$diaSemana   = (int)$hoy->format('N'); // 1=lun … 7=dom
$inicioSem   = (clone $hoy)->modify('-' . ($diaSemana - 1) . ' days');
$diasSemana  = [];
for ($i = 0; $i < 7; $i++) {
    $d = (clone $inicioSem)->modify("+$i days");
    $diasSemana[] = $d;
}

// Mes actual — grilla de calendario
$anioMes = (int)date('Y'); $mesMes = (int)date('m');
$primero = new DateTime("$anioMes-$mesMes-01");
$ultimoDia = (int)$primero->format('t');
$primerDow = (int)$primero->format('N'); // 1=lun

$nombDias = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
$nombMeses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// ── Calendario Mon-Fri estilo almanaque ──────────────────────────────
$feriados  = $data['feriados'] ?? [];
$hoyStr    = date('Y-m-d');

// Inicio: lunes de la semana que contiene el día 1
$calStart = clone $primero;
if ($primerDow > 1) $calStart->modify('-' . ($primerDow - 1) . ' days');

// Fin: viernes de la semana que contiene el último día del mes
$calEnd = new DateTime("$anioMes-$mesMes-$ultimoDia");
$calEndDow = (int)$calEnd->format('N');
if ($calEndDow < 5)      $calEnd->modify('+' . (5 - $calEndDow) . ' days');
elseif ($calEndDow > 5)  $calEnd->modify('+' . (12 - $calEndDow) . ' days');

$semanasMes = []; $semanaCur = [];
$cur = clone $calStart;
while ($cur <= $calEnd) {
    $dow  = (int)$cur->format('N');
    $fStr = $cur->format('Y-m-d');
    $inMes = (int)$cur->format('m') === $mesMes && (int)$cur->format('Y') === $anioMes;
    if ($dow <= 5) {
        $reg       = $inMes ? ($actMap[$fStr] ?? null) : null;
        $esFeriado = $inMes && isset($feriados[$fStr]);
        $esFuturo  = $inMes && $fStr > $hoyStr;
        if (!$inMes)        $estado = 'fuera';
        elseif ($esFeriado) $estado = 'feriado';
        elseif ($esFuturo)  $estado = 'futuro';
        elseif ($reg)       $estado = $reg['estado'];
        else                $estado = 'sin_dato';
        $semanaCur[$dow] = [
            'day'           => $inMes ? (int)$cur->format('j') : '',
            'estado'        => $estado,
            'hora'          => $reg['hora'] ?? null,
            'feriadoNombre' => $esFeriado ? ($feriados[$fStr]) : null,
        ];
        if ($dow === 5) { $semanasMes[] = $semanaCur; $semanaCur = []; }
    }
    $cur->modify('+1 day');
}
if (!empty($semanaCur)) $semanasMes[] = $semanaCur;

// ── Resumen por mes para el acordeón ─────────────────────────────────
$resumenMeses = [];
foreach ($asistencias as $a) {
    $mesKey = substr($a->fecha ?? '', 0, 7);
    if (!$mesKey) continue;
    if (!isset($resumenMeses[$mesKey])) $resumenMeses[$mesKey] = ['P'=>0,'A'=>0,'J'=>0];
    $est = $a->estado ?? '';
    if ($est === 'Presente')      $resumenMeses[$mesKey]['P']++;
    elseif ($est === 'Ausente')   $resumenMeses[$mesKey]['A']++;
    elseif ($est === 'Justificado') $resumenMeses[$mesKey]['J']++;
}
krsort($resumenMeses);
$mesesAbrev = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun',
               '07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
?>
<style>
/* ── Base ── */
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.as-wrap { display:flex; flex-direction:column; gap:20px; animation:fadeUp .4s ease; }

/* ── Header ── */
.as-hero {
    background: linear-gradient(135deg,#0f172a 0%,#1e3a8a 50%,#0d9488 100%);
    border-radius: 22px; padding: 28px 36px;
    display: flex; align-items: center; gap: 18px;
    position: relative; overflow: hidden;
}
.as-hero::before { content:''; position:absolute; top:-50px; right:-50px; width:220px; height:220px; background:rgba(255,255,255,.04); border-radius:50%; }

/* ── KPI Grid ── */
.as-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
@media(max-width:900px){ .as-kpi-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){ .as-kpi-grid{grid-template-columns:1fr 1fr;} }

.as-kpi {
    background:#fff; border-radius:18px; padding:20px;
    box-shadow:0 2px 14px rgba(0,0,0,.07);
    display:flex; justify-content:space-between; align-items:flex-start;
    transition:transform .2s,box-shadow .2s;
    border-top: 3px solid transparent;
}
.as-kpi:hover { transform:translateY(-4px); }
.as-kpi.g { border-top-color:#10b981; } .as-kpi.g:hover { box-shadow:0 10px 24px rgba(16,185,129,.2); }
.as-kpi.r { border-top-color:#ef4444; } .as-kpi.r:hover { box-shadow:0 10px 24px rgba(239,68,68,.2); }
.as-kpi.y { border-top-color:#f59e0b; } .as-kpi.y:hover { box-shadow:0 10px 24px rgba(245,158,11,.2); }
.as-kpi.b { border-top-color:#6366f1; } .as-kpi.b:hover { box-shadow:0 10px 24px rgba(99,102,241,.2); }
.as-kpi-lbl { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:6px; }
.as-kpi-num { font-size:2.4rem; font-weight:900; line-height:1; }
.as-kpi-ico { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }

/* ── Card ── */
.as-card { background:#fff; border-radius:20px; padding:24px; box-shadow:0 2px 14px rgba(0,0,0,.07); }
.as-card-ttl { font-size:1rem; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px; margin-bottom:18px; }
.as-card-ttl i { color:#2563eb; font-size:1.1rem; }

/* ── Tabs ── */
.as-tabs { display:flex; gap:6px; background:#f1f5f9; border-radius:14px; padding:4px; }
.as-tab {
    flex:1; padding:9px 16px; border:none; border-radius:10px; cursor:pointer;
    font-size:.83rem; font-weight:700; color:#64748b; background:transparent;
    transition:all .2s; display:flex; align-items:center; justify-content:center; gap:6px;
}
.as-tab.active { background:#fff; color:#2563eb; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.as-tab:hover:not(.active) { background:rgba(255,255,255,.5); color:#2563eb; }

/* ── Tab Panels ── */
.as-panel { display:none; }
.as-panel.active { display:block; animation:fadeUp .3s ease; }

/* ── SEMANAL ── */
.as-week-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:10px; }
@media(max-width:700px){ .as-week-grid{grid-template-columns:repeat(4,1fr);} }
@media(max-width:480px){ .as-week-grid{grid-template-columns:repeat(2,1fr);} }

.as-week-day {
    border-radius:16px; padding:16px 10px; text-align:center;
    display:flex; flex-direction:column; align-items:center; gap:8px;
    border: 2px solid transparent;
    transition: transform .2s, box-shadow .2s;
}
.as-week-day:hover { transform:translateY(-3px); }
.as-week-day.hoy { border-color:#2563eb; }
.as-week-day .dow { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
.as-week-day .day-num { font-size:1.5rem; font-weight:900; line-height:1; }
.as-week-day .day-ico { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1rem; }
.as-week-day .day-lbl { font-size:.65rem; font-weight:700; border-radius:20px; padding:2px 8px; }

/* ── MENSUAL — Layout 2 columnas ── */
.as-mensual-layout { display:grid; grid-template-columns:260px 1fr; gap:16px; }
@media(max-width:768px) { .as-mensual-layout { grid-template-columns:1fr; } }

/* Mini-calendario Lun-Vie (estilo almanaque) */
.as-mf-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px 16px; }
.as-mf-title { display:flex; align-items:center; gap:7px; font-size:.85rem; font-weight:800; color:#1e293b; margin-bottom:14px; }
.as-mf-head { display:grid; grid-template-columns:repeat(5,1fr); gap:3px; margin-bottom:5px; }
.as-mf-head div { text-align:center; font-size:.63rem; font-weight:800; color:#94a3b8; text-transform:uppercase; padding:4px 0; }
.as-mf-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:3px; }
.as-mf-cell {
    aspect-ratio:1; border-radius:7px;
    display:flex; align-items:center; justify-content:center;
    font-size:.75rem; font-weight:700; cursor:default;
    transition:transform .12s;
}
.as-mf-cell:hover:not([data-e="fuera"]):not([data-e="futuro"]) { transform:scale(1.12); }
.as-mf-cell[data-e="fuera"]       { background:transparent; color:transparent; pointer-events:none; }
.as-mf-cell[data-e="futuro"]      { background:#fff; border:1px solid #e2e8f0; color:#cbd5e1; }
.as-mf-cell[data-e="sin_dato"]    { background:#f1f5f9; color:#94a3b8; }
.as-mf-cell[data-e="Presente"]    { background:#22c55e; color:#fff; }
.as-mf-cell[data-e="Ausente"]     { background:#ef4444; color:#fff; }
.as-mf-cell[data-e="Justificado"] { background:#3b82f6; color:#fff; }
.as-mf-cell[data-e="feriado"]     { background:#f59e0b; color:#fff; }
.as-mf-legend { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; font-size:.68rem; color:#64748b; }
.as-mf-legend span { display:flex; align-items:center; gap:4px; font-weight:600; }
.as-mf-dot { width:10px; height:10px; border-radius:3px; flex-shrink:0; }

/* Acordeón histórico por mes */
.as-acc-wrap { display:flex; flex-direction:column; gap:6px; }
.as-acc-title { font-size:.82rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.as-acc-item { border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; background:#fff; }
.as-acc-btn {
    width:100%; border:none; cursor:pointer; background:#fff;
    display:flex; align-items:center; justify-content:space-between;
    padding:11px 14px; transition:background .15s; gap:8px;
}
.as-acc-btn:hover { background:#f8fafc; }
.as-acc-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.as-acc-chevron { transition:transform .2s; font-size:.8rem; color:#94a3b8; flex-shrink:0; }
.as-acc-item.open .as-acc-chevron { transform:rotate(180deg); }
.as-acc-body { display:none; padding:10px 14px 12px; border-top:1px solid #f1f5f9; background:#f8fafc; }
.as-acc-item.open .as-acc-body { display:block; }
.as-acc-stats { display:flex; gap:0; }
.as-acc-stat { flex:1; display:flex; flex-direction:column; align-items:center; gap:2px; padding:6px 4px; }
.as-acc-stat b { font-size:1.3rem; font-weight:900; line-height:1; }
.as-acc-stat small { font-size:.62rem; color:#94a3b8; font-weight:600; text-transform:uppercase; }
.as-acc-bar { height:5px; border-radius:4px; overflow:hidden; background:#e2e8f0; margin-top:8px; display:flex; }
.as-acc-bar div { height:100%; }

/* ── TOTAL (Tabla) ── */
.as-tbl { width:100%; border-collapse:collapse; }
.as-tbl th { padding:10px 14px; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#64748b; background:#f8fafc; text-align:left; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
.as-tbl td { padding:12px 14px; font-size:.85rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.as-tbl tbody tr:hover { background:#f8fafc; }
.as-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:6px; font-size:.72rem; font-weight:700; }
.as-metodo { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:.72rem; font-weight:600; }

/* ── Progress bar ── */
.as-bar { height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin:.3rem 0; }
.as-bar-fill { height:100%; border-radius:999px; transition:width .8s cubic-bezier(.4,0,.2,1); }

/* ── Total — Paginación ── */
.as-pg { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-top:12px; }
.as-pg-info { font-size:.78rem; color:#94a3b8; }
.as-pg-btns { display:flex; gap:4px; flex-wrap:wrap; }
.as-pg-btn {
    min-width:30px; height:30px; padding:0 8px;
    border:1px solid #e2e8f0; border-radius:8px;
    background:#fff; color:#64748b; font-size:.8rem; font-weight:600;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition: all .15s;
}
.as-pg-btn:hover:not(:disabled):not(.active) { background:#f1f5f9; border-color:#cbd5e1; }
.as-pg-btn.active { background:#1e3a8a; border-color:#1e3a8a; color:#fff; }
.as-pg-btn:disabled { opacity:.35; cursor:not-allowed; }

/* ── Responsive ── */
@media(max-width:768px) {
    .as-hero { padding:20px 22px; }
    .as-tab span.tab-txt { display:none; }
}
</style>

<div class="as-wrap">

<!-- ── HERO ── -->
<div class="as-hero">
    <div style="background:rgba(255,255,255,.15);border-radius:14px;padding:14px;z-index:1;flex-shrink:0;">
        <i class="ti ti-calendar-check" style="font-size:28px;color:#fff;"></i>
    </div>
    <div style="z-index:1;">
        <h1 style="color:#fff;font-size:1.6rem;font-weight:800;margin:0 0 4px;">Mi Asistencia</h1>
        <p style="color:rgba(255,255,255,.7);margin:0;font-size:.88rem;">
            <i class="ti ti-list-check"></i> Registro completo de marcajes y justificaciones
        </p>
    </div>
    <div style="margin-left:auto;z-index:1;text-align:right;flex-shrink:0;">
        <div style="font-size:2rem;font-weight:900;color:#fff;line-height:1;"><?= $pct ?>%</div>
        <div style="font-size:.72rem;color:rgba(255,255,255,.7);font-weight:600;">progreso de pasantía</div>
        <div style="font-size:.68rem;color:rgba(255,255,255,.5);margin-top:2px;"><?= number_format($horasAcum) ?>h de <?= number_format($horasMeta) ?>h completadas</div>
    </div>
</div>

<?php if ($sinAsignar): ?>
<div style="background:#fff;border-radius:20px;padding:60px 30px;text-align:center;box-shadow:0 2px 14px rgba(0,0,0,.06);border:2px dashed #e2e8f0;">
    <i class="ti ti-calendar-off" style="font-size:3.5rem;color:#cbd5e1;display:block;margin-bottom:16px;"></i>
    <h3 style="color:#1e293b;margin:0 0 8px;font-weight:800;">Sin registros aún</h3>
    <p style="color:#94a3b8;max-width:400px;margin:0 auto;">Tu pasantía aún no ha sido activada. Contacta al equipo de Telemática.</p>
</div>
<?php else: ?>

<!-- ── KPI CARDS ── -->
<div class="as-kpi-grid">
    <?php $kpis = [
        ['lbl'=>'Presentes',   'num'=>$cPresentes,  'cls'=>'g', 'color'=>'#10b981', 'ibg'=>'#d1fae5', 'icon'=>'ti-check'],
        ['lbl'=>'Ausentes',    'num'=>$cAusentes,   'cls'=>'r', 'color'=>'#ef4444', 'ibg'=>'#fee2e2', 'icon'=>'ti-x'],
        ['lbl'=>'Justificados','num'=>$cJustificados,'cls'=>'y','color'=>'#f59e0b', 'ibg'=>'#fef3c7', 'icon'=>'ti-file-description'],
        ['lbl'=>'Tasa Asist.','num'=>$porcAsist.'%','cls'=>'b','color'=>'#6366f1', 'ibg'=>'#ede9fe', 'icon'=>'ti-percentage'],
    ];
    foreach ($kpis as $k): ?>
    <div class="as-kpi <?= $k['cls'] ?>">
        <div>
            <div class="as-kpi-lbl"><?= $k['lbl'] ?></div>
            <div class="as-kpi-num" style="color:<?= $k['color'] ?>;" <?= is_int($k['num']) ? "data-kpi-value=\"{$k['num']}\"" : '' ?>><?= $k['num'] ?></div>
        </div>
        <div class="as-kpi-ico" style="background:<?= $k['ibg'] ?>;color:<?= $k['color'] ?>;">
            <i class="ti <?= $k['icon'] ?>"></i>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── PROGRESO DE PASANTÍA ── -->
<div class="as-card">
    <div class="as-card-ttl"><i class="ti ti-trending-up"></i> Progreso de Pasantía
        <span style="margin-left:auto;font-size:.78rem;color:#94a3b8;font-weight:500;">Meta: <?= $horasMeta ?>h · <?= number_format($horasMeta/8) ?> días</span>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#64748b;margin-bottom:5px;">
        <span><?= $diasValidos ?> días válidos · <?= $horasAcum ?>h acumuladas</span>
        <strong style="color:#2563eb;"><?= $pct ?>%</strong>
    </div>
    <div class="as-bar"><div class="as-bar-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#2563eb,#0d9488);"></div></div>
    <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#94a3b8;margin-top:4px;">
        <span>0h</span><span><?= $horasMeta ?>h</span>
    </div>
</div>

<!-- ── TABS ── -->
<div class="as-card" style="padding:20px 24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
        <div class="as-card-ttl" style="margin-bottom:0;"><i class="ti ti-calendar"></i> Detalle de Registros</div>
        <div class="as-tabs">
            <button class="as-tab active" onclick="asTab('semanal',this)">
                <i class="ti ti-calendar-week"></i> <span class="tab-txt">Semanal</span>
            </button>
            <button class="as-tab" onclick="asTab('mensual',this)">
                <i class="ti ti-calendar-month"></i> <span class="tab-txt">Mensual</span>
            </button>
            <button class="as-tab" onclick="asTab('total',this)">
                <i class="ti ti-table"></i> <span class="tab-txt">Total</span>
            </button>
        </div>
    </div>

    <!-- ── PANEL SEMANAL ── -->
    <div id="panel-semanal" class="as-panel active">
        <div style="text-align:center;font-size:.78rem;color:#64748b;margin-bottom:16px;font-weight:600;">
            Semana del <?= $inicioSem->format('d/m') ?> al <?= (clone $inicioSem)->modify('+6 days')->format('d/m/Y') ?>
        </div>
        <div class="as-week-grid">
            <?php
            $dowNames = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
            $cfgEst = [
                'Presente'    => ['bg'=>'#dcfce7','txt'=>'#15803d','ibg'=>'#bbf7d0','icon'=>'ti-check','lbl_bg'=>'#f0fdf4'],
                'Ausente'     => ['bg'=>'#fee2e2','txt'=>'#dc2626','ibg'=>'#fecaca','icon'=>'ti-x','lbl_bg'=>'#fff1f2'],
                'Justificado' => ['bg'=>'#dbeafe','txt'=>'#1d4ed8','ibg'=>'#bfdbfe','icon'=>'ti-file-description','lbl_bg'=>'#eff6ff'],
            ];
            foreach ($diasSemana as $i => $d):
                $fStr  = $d->format('Y-m-d');
                $esHoy = $fStr === $hoy->format('Y-m-d');
                $reg   = $actMap[$fStr] ?? null;
                $est   = $reg['estado'] ?? null;
                $c     = $est ? ($cfgEst[$est] ?? null) : null;
                $esFin = $d->format('N') >= 6; // sáb/dom
            ?>
            <div class="as-week-day<?= $esHoy ? ' hoy' : '' ?>"
                 style="background:<?= $c ? $c['bg'] : ($esFin ? '#fafafa' : '#f8fafc') ?>;<?= $esHoy ? 'background:'.($c ? $c['bg'] : '#eff6ff').';' : '' ?>">
                <div class="dow" style="color:<?= $c ? $c['txt'] : ($esFin ? '#cbd5e1' : '#64748b') ?>;">
                    <?= $dowNames[$i] ?>
                </div>
                <div class="day-num" style="color:<?= $c ? $c['txt'] : ($esFin ? '#cbd5e1' : '#1e293b') ?>;">
                    <?= (int)$d->format('d') ?>
                </div>
                <?php if ($c): ?>
                <div class="day-ico" style="background:<?= $c['ibg'] ?>;color:<?= $c['txt'] ?>;">
                    <i class="ti <?= $c['icon'] ?>"></i>
                </div>
                <div class="day-lbl" style="background:<?= $c['lbl_bg'] ?>;color:<?= $c['txt'] ?>;">
                    <?= $est ?>
                </div>
                <?php elseif ($esHoy): ?>
                <div style="font-size:.65rem;font-weight:700;color:#2563eb;background:#eff6ff;padding:2px 8px;border-radius:20px;">Hoy</div>
                <?php else: ?>
                <div style="font-size:.65rem;color:#cbd5e1;font-weight:600;"><?= $esFin ? 'Libre' : '—' ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── PANEL MENSUAL ── -->
    <div id="panel-mensual" class="as-panel">
    <div class="as-mensual-layout">

        <!-- Columna izq: mini-calendario Mon-Vie -->
        <div class="as-mf-card">
            <div class="as-mf-title">
                <i class="ti ti-calendar-month" style="color:#2563eb;"></i>
                <?= $nombMeses[$mesMes] ?> <?= $anioMes ?>
            </div>
            <div class="as-mf-head">
                <div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div>
            </div>
            <div class="as-mf-grid">
            <?php foreach ($semanasMes as $semana): ?>
                <?php for ($d = 1; $d <= 5; $d++):
                    $cell    = $semana[$d] ?? ['day'=>'','estado'=>'fuera','hora'=>null,'feriadoNombre'=>null];
                    $tooltip = $cell['feriadoNombre'] ?: ($cell['hora'] ? 'Entrada: ' . substr($cell['hora'], 0, 5) : '');
                ?>
                <div class="as-mf-cell" data-e="<?= htmlspecialchars($cell['estado']) ?>"
                    <?= $tooltip ? ' title="' . htmlspecialchars($tooltip) . '"' : '' ?>>
                    <?= $cell['day'] ?>
                </div>
                <?php endfor; ?>
            <?php endforeach; ?>
            </div>
            <div class="as-mf-legend">
                <span><span class="as-mf-dot" style="background:#22c55e;"></span>Presente</span>
                <span><span class="as-mf-dot" style="background:#ef4444;"></span>Ausente</span>
                <span><span class="as-mf-dot" style="background:#3b82f6;"></span>Justif.</span>
                <span><span class="as-mf-dot" style="background:#f59e0b;"></span>Feriado</span>
                <span><span class="as-mf-dot" style="background:#f1f5f9;border:1px solid #e2e8f0;"></span>Sin dato</span>
            </div>
        </div>

        <!-- Columna der: acordeón histórico por mes -->
        <div class="as-acc-wrap">
            <div class="as-acc-title">
                <i class="ti ti-history"></i> Historial por mes
            </div>
            <?php if (empty($resumenMeses)): ?>
            <div style="text-align:center;padding:32px;color:#94a3b8;font-size:.85rem;">Sin registros aún.</div>
            <?php else: ?>
            <?php foreach ($resumenMeses as $mesKey => $stats):
                [$y, $m] = explode('-', $mesKey);
                $mesLabel = ($mesesAbrev[$m] ?? $m) . ' ' . $y;
                $total    = $stats['P'] + $stats['A'] + $stats['J'];
                $tasa     = $total > 0 ? round(($stats['P'] + $stats['J']) / $total * 100) : 0;
                $isCurrent = $mesKey === date('Y-m');
                $colorTasa = $tasa >= 85 ? '#16a34a' : ($tasa >= 70 ? '#d97706' : '#dc2626');
                $pW = $total > 0 ? round($stats['P'] / $total * 100) : 0;
                $jW = $total > 0 ? round($stats['J'] / $total * 100) : 0;
            ?>
            <div class="as-acc-item <?= $isCurrent ? 'open' : '' ?>">
                <button class="as-acc-btn" onclick="toggleAcc(this)" type="button">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <span class="as-acc-dot" style="background:<?= $colorTasa ?>;"></span>
                        <span style="font-weight:700;color:#1e293b;font-size:.88rem;"><?= $mesLabel ?></span>
                        <?php if ($isCurrent): ?>
                        <span style="background:#eff6ff;color:#2563eb;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:20px;white-space:nowrap;">Actual</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                        <span style="font-size:.82rem;font-weight:800;color:<?= $colorTasa ?>;"><?= $tasa ?>%</span>
                        <i class="ti ti-chevron-down as-acc-chevron"></i>
                    </div>
                </button>
                <div class="as-acc-body">
                    <div class="as-acc-stats">
                        <div class="as-acc-stat" style="color:#16a34a;">
                            <i class="ti ti-check-circle"></i>
                            <b><?= $stats['P'] ?></b>
                            <small>Presentes</small>
                        </div>
                        <div class="as-acc-stat" style="color:#dc2626;">
                            <i class="ti ti-x-circle"></i>
                            <b><?= $stats['A'] ?></b>
                            <small>Ausentes</small>
                        </div>
                        <div class="as-acc-stat" style="color:#2563eb;">
                            <i class="ti ti-file-check"></i>
                            <b><?= $stats['J'] ?></b>
                            <small>Justificados</small>
                        </div>
                    </div>
                    <div class="as-acc-bar">
                        <div style="width:<?= $pW ?>%;background:#22c55e;"></div>
                        <div style="width:<?= $jW ?>%;background:#3b82f6;"></div>
                        <div style="width:<?= 100 - $pW - $jW ?>%;background:#ef4444;"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
    </div>

    <!-- ── PANEL TOTAL ── -->
    <div id="panel-total" class="as-panel">
        <?php if (empty($asistencias)): ?>
        <div style="text-align:center;padding:48px;color:#94a3b8;">
            <i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.4;"></i>
            Sin registros de asistencia aún.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="as-tbl" id="tblTotal">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Método</th>
                        <th>Justificación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $cfgBadge = [
                    'Presente'    => ['bg'=>'#dcfce7','color'=>'#15803d','icon'=>'ti-check'],
                    'Ausente'     => ['bg'=>'#fee2e2','color'=>'#dc2626','icon'=>'ti-x'],
                    'Justificado' => ['bg'=>'#dbeafe','color'=>'#1d4ed8','icon'=>'ti-file-description'],
                ];
                $cfgMetodo = [
                    'Kiosco' => ['bg'=>'#f0f4ff','color'=>'#4338ca','icon'=>'ti-device-tablet'],
                    'Manual' => ['bg'=>'#fef3c7','color'=>'#d97706','icon'=>'ti-pencil'],
                    'AutoFill'=>['bg'=>'#f0fdf4','color'=>'#15803d','icon'=>'ti-refresh'],
                ];
                foreach (array_reverse($asistencias) as $a):
                    $est = $a->estado ?? 'Presente';
                    $bc  = $cfgBadge[$est]  ?? ['bg'=>'#f1f5f9','color'=>'#64748b','icon'=>'ti-minus'];
                    $met = $a->metodo ?? 'Kiosco';
                    $mc  = $cfgMetodo[$met] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','icon'=>'ti-help'];
                    $fecha = !empty($a->fecha) ? date('d/m/Y', strtotime($a->fecha)) : '—';
                    $hora  = !empty($a->hora_registro) ? date('g:i A', strtotime($a->hora_registro)) : '—';
                    $motivo = htmlspecialchars($a->motivo_justificacion ?? '');
                ?>
                <tr>
                    <td style="font-weight:700;color:#0f172a;white-space:nowrap;"><?= $fecha ?></td>
                    <td style="font-variant-numeric:tabular-nums;color:#475569;"><?= $hora ?></td>
                    <td>
                        <span class="as-metodo" style="background:<?= $mc['bg'] ?>;color:<?= $mc['color'] ?>;">
                            <i class="ti <?= $mc['icon'] ?>"></i> <?= $met ?>
                        </span>
                    </td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;font-size:.8rem;" title="<?= $motivo ?>">
                        <?php if ($motivo): ?>
                        <?= $motivo ?>
                        <?php if (!empty($a->ruta_evidencia)): ?>
                        &nbsp;<a href="<?= URLROOT ?>/asistencias/verEvidencia/<?= htmlspecialchars(basename($a->ruta_evidencia)) ?>" target="_blank" title="Ver evidencia"
                                 style="color:#2563eb;font-size:.75rem;"><i class="ti ti-paperclip"></i></a>
                        <?php endif; ?>
                        <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                    </td>
                    <td>
                        <span class="as-badge" style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;">
                            <i class="ti <?= $bc['icon'] ?>"></i> <?= $est ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="as-pg">
            <div class="as-pg-info" id="pgTotalInfo"><?= $totalReg ?> registros en total</div>
            <div class="as-pg-btns" id="pgTotalBtns"></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
</div><!-- /as-wrap -->

<script>
function asTab(name, btn) {
    document.querySelectorAll('.as-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.as-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + name).classList.add('active');
    btn.classList.add('active');
}
function toggleAcc(btn) {
    btn.closest('.as-acc-item').classList.toggle('open');
}

(function() {
    const PER_PAGE = 10;
    const tbl = document.getElementById('tblTotal');
    if (!tbl) return;
    const rows = Array.from(tbl.querySelectorAll('tbody tr'));
    const total = rows.length;
    if (total <= PER_PAGE) return;
    let cur = 1;
    const pages = Math.ceil(total / PER_PAGE);
    const infoEl = document.getElementById('pgTotalInfo');
    const btnsEl = document.getElementById('pgTotalBtns');

    function render() {
        rows.forEach((r, i) => {
            r.style.display = (i >= (cur - 1) * PER_PAGE && i < cur * PER_PAGE) ? '' : 'none';
        });
        const from = (cur - 1) * PER_PAGE + 1;
        const to   = Math.min(cur * PER_PAGE, total);
        infoEl.textContent = `Mostrando ${from}–${to} de ${total} registros`;

        btnsEl.innerHTML = '';
        const prev = document.createElement('button');
        prev.className = 'as-pg-btn';
        prev.innerHTML = '<i class="ti ti-chevron-left"></i>';
        prev.disabled = cur === 1;
        prev.onclick = () => { cur--; render(); };
        btnsEl.appendChild(prev);

        const start = Math.max(1, cur - 2);
        const end   = Math.min(pages, start + 4);
        for (let p = start; p <= end; p++) {
            const btn = document.createElement('button');
            btn.className = 'as-pg-btn' + (p === cur ? ' active' : '');
            btn.textContent = p;
            btn.onclick = ((pg) => () => { cur = pg; render(); })(p);
            btnsEl.appendChild(btn);
        }

        const next = document.createElement('button');
        next.className = 'as-pg-btn';
        next.innerHTML = '<i class="ti ti-chevron-right"></i>';
        next.disabled = cur === pages;
        next.onclick = () => { cur++; render(); };
        btnsEl.appendChild(next);
    }

    render();
})();
</script>
