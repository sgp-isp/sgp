<!-- ═══════════ MODAL CALCULADORA DE HORAS — PREMIUM ═══════════ -->
<style>
@keyframes hc-fadeIn { from{opacity:0;transform:scale(.96) translateY(12px)} to{opacity:1;transform:scale(1) translateY(0)} }
@keyframes hc-slideUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes hc-spin { to{transform:rotate(360deg)} }

.hc-overlay {
    display: none;
    position: fixed !important;
    top: -20px !important;
    left: -20px !important;
    right: -20px !important;
    bottom: -20px !important;
    inset: -20px !important;
    width: calc(100% + 40px) !important;
    height: calc(100% + 40px) !important;
    min-width: calc(100% + 40px) !important;
    min-height: calc(100% + 40px) !important;
    max-width: none !important;
    max-height: none !important;
    background: rgba(2, 6, 23, 0.72) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 99999999 !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    padding: 24px !important;
    box-sizing: border-box !important;
}
.hc-box {
    background: #fff;
    border-radius: 24px;
    width: 95%;
    max-width: 780px;
    margin: auto !important;
    position: relative !important;
    box-shadow: 0 32px 100px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.1);
    overflow: hidden;
    max-height: 92vh;
    overflow-y: auto;
    animation: hc-fadeIn .3s ease;
}

/* Header */
.hc-head { background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 40%,#3b82f6 100%);padding:24px 28px;position:relative;overflow:hidden; }
.hc-head::before { content:'';position:absolute;inset:0;background-image:radial-gradient(circle at 20% 50%,rgba(99,102,241,.2) 0%,transparent 50%),radial-gradient(circle at 80% 20%,rgba(16,185,129,.12) 0%,transparent 40%); }
.hc-head::after { content:'';position:absolute;top:-60px;right:-60px;width:250px;height:250px;border-radius:50%;border:1px solid rgba(255,255,255,.06);box-shadow:0 0 0 50px rgba(255,255,255,.02),0 0 0 100px rgba(255,255,255,.01); }
.hc-head-inner { position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between; }
.hc-head-left { display:flex;align-items:center;gap:16px; }
.hc-head-icon { width:50px;height:50px;border-radius:16px;background:linear-gradient(135deg,rgba(255,255,255,.2),rgba(255,255,255,.08));border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(0,0,0,.15);backdrop-filter:blur(10px); }
.hc-head-tag { display:inline-flex;align-items:center;gap:4px;background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.35);color:#6ee7b7;font-size:.68rem;font-weight:700;letter-spacing:.03em;padding:3px 10px;border-radius:20px;margin-bottom:6px; }
.hc-head-title { color:#fff;font-size:1.4rem;font-weight:900;letter-spacing:-.02em;line-height:1.1; }
.hc-head-sub { color:rgba(255,255,255,.65);font-size:.78rem;margin-top:3px; }
.hc-close { width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);cursor:pointer;color:rgba(255,255,255,.8);display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:all .15s; }
.hc-close:hover { background:rgba(255,255,255,.2);color:#fff; }

/* Body */
.hc-body { padding:24px 28px; }

/* Input cards */
.hc-input-row { display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px; }
.hc-input-card { background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:14px 16px;transition:all .2s; }
.hc-input-card:focus-within { border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);background:#fff; }
.hc-input-label { display:flex;align-items:center;gap:6px;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px; }
.hc-input-label i { color:#3b82f6;font-size:.9rem; }
.hc-input { width:100%;padding:0;border:none;background:transparent;font-size:1.15rem;font-weight:800;color:#0f172a;outline:none;font-family:inherit;box-sizing:border-box; }
.hc-input::placeholder { color:#cbd5e1;font-weight:600; }

/* Presets */
.hc-presets { display:flex;align-items:center;gap:6px;margin-bottom:20px;flex-wrap:wrap; }
.hc-preset { padding:7px 14px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:5px; }
.hc-preset:hover { border-color:#93c5fd;background:#eff6ff;color:#2563eb; }
.hc-preset.active { background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border-color:#2563eb;box-shadow:0 4px 12px rgba(37,99,235,.3); }
.hc-preset i { font-size:.85rem; }

/* Divider */
.hc-divider { height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent);margin:4px 0 20px; }

/* Result hero */
.hc-result { animation:hc-slideUp .4s ease; }
.hc-hero { background:linear-gradient(135deg,#0f172a,#1e3a8a);border-radius:18px;padding:22px 26px;margin-bottom:18px;position:relative;overflow:hidden; }
.hc-hero::before { content:'';position:absolute;inset:0;background-image:radial-gradient(circle at 30% 40%,rgba(59,130,246,.2) 0%,transparent 50%),radial-gradient(circle at 80% 70%,rgba(16,185,129,.12) 0%,transparent 40%); }
.hc-hero-inner { position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px; }
.hc-hero-left { display:flex;align-items:center;gap:16px; }
.hc-hero-icon { width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center; }
.hc-hero-date { font-size:1.55rem;font-weight:900;color:#fff;line-height:1.15; }
.hc-hero-label { font-size:.7rem;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em; }
.hc-hero-dur { text-align:right; }
.hc-hero-dur-val { font-size:1.1rem;font-weight:800;color:#93c5fd; }
.hc-hero-dur-label { font-size:.7rem;color:rgba(255,255,255,.5);margin-top:2px; }

/* Donut + KPIs */
.hc-stats { display:grid;grid-template-columns:140px 1fr;gap:20px;margin-bottom:18px;align-items:center; }
.hc-donut-wrap { display:flex;align-items:center;justify-content:center; }
.hc-kpi-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
.hc-kpi { border-radius:14px;padding:12px 14px;display:flex;align-items:center;gap:12px;transition:transform .15s; }
.hc-kpi:hover { transform:translateY(-2px); }
.hc-kpi-icon { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0; }
.hc-kpi-val { font-size:1.35rem;font-weight:900;line-height:1; }
.hc-kpi-label { font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-top:2px; }

/* Feriados accordion */
.hc-fer-wrap { margin-bottom:18px;border:1px solid #fde68a;border-radius:14px;overflow:hidden; }
.hc-fer-head { display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fffbeb;cursor:pointer;user-select:none;transition:background .15s; }
.hc-fer-head:hover { background:#fef3c7; }
.hc-fer-body { max-height:180px;overflow-y:auto;padding:0 16px 12px; }
.hc-fer-item { display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #fef3c7; }
.hc-fer-item:last-child { border-bottom:none; }

/* Timeline */
.hc-tl-wrap { background:#f8fafc;border-radius:14px;padding:16px 18px;border:1px solid #e2e8f0; }
.hc-tl-title { font-size:.78rem;font-weight:700;color:#1e293b;margin-bottom:12px;display:flex;align-items:center;gap:6px; }
.hc-tl-row { display:flex;align-items:center;gap:12px;margin-bottom:8px; }
.hc-tl-row:last-child { margin-bottom:0; }
.hc-tl-month { min-width:52px;font-size:.75rem;font-weight:700;color:#475569;text-align:right; }
.hc-tl-bar-wrap { flex:1;height:26px;background:#e2e8f0;border-radius:8px;overflow:hidden;position:relative; }
.hc-tl-bar { height:100%;border-radius:8px;background:linear-gradient(90deg,#3b82f6,#6366f1);transition:width .6s ease;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;min-width:30px; }
.hc-tl-bar-text { font-size:.68rem;font-weight:800;color:#fff;white-space:nowrap; }
.hc-tl-info { min-width:74px;font-size:.72rem;font-weight:600;color:#64748b;text-align:right; }

/* Loading */
.hc-loading { text-align:center;padding:36px 20px;color:#94a3b8; }
.hc-loading i { font-size:2.2rem;display:block;margin-bottom:10px;animation:hc-spin .8s linear infinite; }

@media(max-width:680px){
    .hc-input-row { grid-template-columns:1fr; }
    .hc-stats { grid-template-columns:1fr; }
    .hc-kpi-grid { grid-template-columns:1fr 1fr; }
    .hc-hero-inner { flex-direction:column;text-align:center; }
    .hc-hero-left { flex-direction:column; }
    .hc-hero-dur { text-align:center; }
}
@media(max-width:400px){ .hc-kpi-grid { grid-template-columns:1fr; } }
</style>

<div class="hc-overlay" id="modalCalcHoras" onclick="if(event.target===this)cerrarCalculadoraHoras()">
<div class="hc-box">
    <div class="hc-head">
        <div class="hc-head-inner">
            <div class="hc-head-left">
                <div class="hc-head-icon"><i class="ti ti-calculator" style="font-size:1.6rem;color:#fff;"></i></div>
                <div>
                    <div class="hc-head-tag"><i class="ti ti-sparkles" style="font-size:.75rem;"></i> Herramienta Rápida</div>
                    <div class="hc-head-title">Calculadora de Horas</div>
                    <div class="hc-head-sub">Cálculo por horas meta o rango de fechas (solo excluye feriados no laborables)</div>
                </div>
            </div>
            <button type="button" class="hc-close" onclick="cerrarCalculadoraHoras()"><i class="ti ti-x"></i></button>
        </div>
    </div>

    <div class="hc-body">
        <!-- Inputs: Desde, Hasta, Horas Meta -->
        <div class="hc-input-row">
            <div class="hc-input-card" style="cursor: pointer;" onclick="const el=document.getElementById('calcFechaInicio'); if(el._flatpickr){el._flatpickr.open();}else{el.focus();}">
                <div class="hc-input-label" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="ti ti-calendar-event"></i> Desde (Inicio)</span>
                    <i class="ti ti-calendar" style="color: #2563eb; font-size: 1rem;" title="Abrir selector de fecha"></i>
                </div>
                <input type="date" class="hc-input" id="calcFechaInicio" value="<?= date('Y-m-d') ?>" onchange="calcularHoras('horas')">
            </div>

            <div class="hc-input-card" style="cursor: pointer;" onclick="const el=document.getElementById('calcFechaHasta'); if(el._flatpickr){el._flatpickr.open();}else{el.focus();}">
                <div class="hc-input-label" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="ti ti-calendar-check"></i> Hasta (Fin)</span>
                    <i class="ti ti-calendar" style="color: #2563eb; font-size: 1rem;" title="Abrir selector de fecha"></i>
                </div>
                <input type="date" class="hc-input" id="calcFechaHasta" value="" onchange="calcularHoras('rango')">
            </div>

            <div class="hc-input-card">
                <div class="hc-input-label"><i class="ti ti-hourglass"></i> Horas Meta</div>
                <input type="number" class="hc-input" id="calcHorasMeta" value="1440" min="8" step="8" onchange="calcularHoras('horas')" oninput="calcularHoras('horas')" placeholder="1440">
            </div>
        </div>

        <!-- Presets -->
        <div class="hc-presets">
            <button type="button" class="hc-preset" onclick="setPreset(this,240)"><i class="ti ti-bolt"></i> 240h</button>
            <button type="button" class="hc-preset" onclick="setPreset(this,480)"><i class="ti ti-clock-2"></i> 480h</button>
            <button type="button" class="hc-preset" onclick="setPreset(this,960)"><i class="ti ti-clock-hour-6"></i> 960h</button>
            <button type="button" class="hc-preset active" onclick="setPreset(this,1440)"><i class="ti ti-clock-hour-9"></i> 1440h</button>
            <button type="button" class="hc-preset" onclick="setPreset(this,1920)"><i class="ti ti-clock-hour-12"></i> 1920h</button>
        </div>

        <div class="hc-divider"></div>

        <!-- Loading -->
        <div class="hc-loading" id="calcLoading" style="display:none;"><i class="ti ti-loader-2"></i><div style="font-size:.88rem;font-weight:600;">Calculando distribución...</div></div>

        <!-- Resultado -->
        <div id="calcResultado" style="display:none;" class="hc-result">
            <!-- Hero fecha fin -->
            <div class="hc-hero">
                <div class="hc-hero-inner">
                    <div class="hc-hero-left">
                        <div class="hc-hero-icon"><i class="ti ti-calendar-check" style="font-size:1.6rem;color:#60a5fa;"></i></div>
                        <div>
                            <div class="hc-hero-label" id="calcHeroLabel">Fecha Fin Estimada</div>
                            <div class="hc-hero-date" id="calcFechaFin"></div>
                        </div>
                    </div>
                    <div class="hc-hero-dur">
                        <div class="hc-hero-dur-val" id="calcDuracion"></div>
                        <div class="hc-hero-dur-label" id="calcDuracionSub">duración total</div>
                    </div>
                </div>
            </div>

            <!-- Donut + KPIs -->
            <div class="hc-stats">
                <div class="hc-donut-wrap">
                    <svg id="calcDonut" width="130" height="130" viewBox="0 0 130 130"></svg>
                </div>
                <div class="hc-kpi-grid">
                    <div class="hc-kpi" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div class="hc-kpi-icon" style="background:#dcfce7;color:#15803d;"><i class="ti ti-briefcase"></i></div>
                        <div><div class="hc-kpi-val" style="color:#15803d;" id="calcDiasHabiles"></div><div class="hc-kpi-label" style="color:#15803d;">Días Hábiles</div></div>
                    </div>
                    <div class="hc-kpi" style="background:#f1f5f9;border:1px solid #e2e8f0;">
                        <div class="hc-kpi-icon" style="background:#e2e8f0;color:#475569;"><i class="ti ti-sun-off"></i></div>
                        <div><div class="hc-kpi-val" style="color:#475569;" id="calcFinSemana"></div><div class="hc-kpi-label" style="color:#64748b;">Fines de Semana</div></div>
                    </div>
                    <div class="hc-kpi" style="background:#fffbeb;border:1px solid #fde68a;">
                        <div class="hc-kpi-icon" style="background:#fef3c7;color:#b45309;"><i class="ti ti-calendar-off"></i></div>
                        <div><div class="hc-kpi-val" style="color:#b45309;" id="calcFeriados"></div><div class="hc-kpi-label" style="color:#b45309;">Feriados No Lab.</div></div>
                    </div>
                    <div class="hc-kpi" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <div class="hc-kpi-icon" style="background:#dbeafe;color:#2563eb;"><i class="ti ti-calendar-stats"></i></div>
                        <div><div class="hc-kpi-val" style="color:#2563eb;" id="calcTotalDias"></div><div class="hc-kpi-label" style="color:#2563eb;">Días Totales</div></div>
                    </div>
                </div>
            </div>

            <!-- Feriados accordion -->
            <div id="calcListaFeriados" style="display:none;" class="hc-fer-wrap">
                <div class="hc-fer-head" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none';this.querySelector('.hc-fer-chevron').style.transform=this.nextElementSibling.style.display==='none'?'rotate(-90deg)':''">
                    <span style="font-size:.82rem;font-weight:700;color:#92400e;display:flex;align-items:center;gap:6px;"><i class="ti ti-calendar-off"></i> Feriados no laborables en el período <span id="calcFeriadosBadge" style="background:#fde68a;color:#92400e;padding:1px 8px;border-radius:12px;font-size:.7rem;"></span></span>
                    <i class="ti ti-chevron-down hc-fer-chevron" style="color:#b45309;transition:transform .2s;"></i>
                </div>
                <div class="hc-fer-body" id="calcFeriadosLista"></div>
            </div>

            <!-- Timeline -->
            <div class="hc-tl-wrap">
                <div class="hc-tl-title"><i class="ti ti-chart-bar" style="color:#3b82f6;"></i> Distribución Mensual de Horas</div>
                <div id="calcTimeline"></div>
            </div>
        </div>

        <!-- Estado vacío -->
        <div id="calcEmpty" style="text-align:center;padding:36px 20px;">
            <div style="width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,#eff6ff,#dbeafe);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;"><i class="ti ti-calculator" style="font-size:2rem;color:#3b82f6;"></i></div>
            <div style="font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:4px;">Configura los parámetros</div>
            <div style="font-size:.85rem;color:#94a3b8;">Selecciona fecha de inicio y fecha hasta (o las horas meta) para calcular</div>
        </div>
    </div>
</div>
</div>

<script>
(function(){
    let feriadosCache = null;
    const MESES_FULL = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const DIAS  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    function fmtYmd(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function setInputFecha(id, ymd) {
        const el = document.getElementById(id);
        if (!el) return;
        if (el._flatpickr) {
            el._flatpickr.setDate(ymd, false); // false evita disparar onChange en bucle
        } else {
            el.value = ymd;
        }
    }

    window.setPreset = function(btn, val) {
        document.querySelectorAll('.hc-preset').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        document.getElementById('calcHorasMeta').value = val;
        calcularHoras('horas');
    };

    async function cargarFeriados() {
        if (feriadosCache) return feriadosCache;
        try {
            const r = await fetch(`${URLROOT}/asignaciones/feriadosJson`);
            feriadosCache = await r.json();
        } catch(e) {
            feriadosCache = [];
        }
        return feriadosCache;
    }

    function drawDonut(habiles, finde, feriados) {
        const svg = document.getElementById('calcDonut');
        const total = habiles + finde + feriados || 1;
        const r = 50, cx = 65, cy = 65, sw = 16;
        const circum = 2 * Math.PI * r;
        const segments = [
            { val: habiles,  color: '#22c55e', label: 'Hábiles' },
            { val: finde,    color: '#94a3b8', label: 'Fines sem.' },
            { val: feriados, color: '#f59e0b', label: 'Feriados' },
        ];
        let offset = 0;
        let html = '';
        segments.forEach(s => {
            if (s.val <= 0) return;
            const pct = s.val / total;
            const dash = pct * circum;
            const gap = circum - dash;
            html += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${s.color}" stroke-width="${sw}" stroke-dasharray="${dash} ${gap}" stroke-dashoffset="${-offset}" transform="rotate(-90 ${cx} ${cy})" style="transition:stroke-dasharray .6s,stroke-dashoffset .6s;"/>`;
            offset += dash;
        });
        const pctH = Math.round(habiles / total * 100);
        html += `<text x="${cx}" y="${cy-4}" text-anchor="middle" style="font-size:18px;font-weight:900;fill:#0f172a;">${pctH}%</text>`;
        html += `<text x="${cx}" y="${cy+12}" text-anchor="middle" style="font-size:8px;font-weight:600;fill:#94a3b8;">HÁBILES</text>`;
        svg.innerHTML = html;
    }

    window.calcularHoras = async function(modo = 'horas') {
        const inicio = document.getElementById('calcFechaInicio').value;
        const hasta  = document.getElementById('calcFechaHasta').value;
        let   horas  = parseInt(document.getElementById('calcHorasMeta').value) || 0;

        if (!inicio || (modo === 'horas' && horas < 8) || (modo === 'rango' && (!hasta || hasta < inicio))) {
            document.getElementById('calcEmpty').style.display = 'block';
            document.getElementById('calcResultado').style.display = 'none';
            return;
        }

        document.getElementById('calcEmpty').style.display = 'none';
        document.getElementById('calcResultado').style.display = 'none';
        document.getElementById('calcLoading').style.display = 'block';

        // feriadosJson ya filtra solo es_laborable = 0 (Navidad, Noche Vieja, etc.)
        const feriados = await cargarFeriados();
        const feriadoMap = {};
        feriados.forEach(f => { feriadoMap[f.fecha] = f.nombre; });

        const dInicio = new Date(inicio + 'T00:00:00');
        let diasHabiles = 0, finSemana = 0, feriadosEnRango = [], totalDias = 0, mesMapa = {};
        let cur = new Date(dInicio), fechaFin = new Date(dInicio);

        if (modo === 'rango') {
            const dHasta = new Date(hasta + 'T00:00:00');
            let guard = 0;
            while (cur <= dHasta && guard < 2000) {
                guard++;
                const dow = cur.getDay();
                const fStr = fmtYmd(cur);
                const mes = cur.getFullYear() + '-' + String(cur.getMonth() + 1).padStart(2, '0');
                if (!mesMapa[mes]) mesMapa[mes] = { habiles: 0, finde: 0, feriados: 0 };

                if (dow === 0 || dow === 6) {
                    finSemana++;
                    mesMapa[mes].finde++;
                } else if (feriadoMap[fStr]) {
                    // Feriado NO laborable: no suma día hábil, se cuenta como feriado
                    feriadosEnRango.push({ fecha: fStr, nombre: feriadoMap[fStr] });
                    mesMapa[mes].feriados++;
                } else {
                    diasHabiles++;
                    mesMapa[mes].habiles++;
                }
                totalDias++;
                fechaFin = new Date(cur);
                cur.setDate(cur.getDate() + 1);
            }
            horas = diasHabiles * 8;
            document.getElementById('calcHorasMeta').value = horas;
            document.querySelectorAll('.hc-preset').forEach(b => b.classList.remove('active'));
        } else {
            const diasNecesarios = Math.ceil(horas / 8);
            let guard = 0;
            while (diasHabiles < diasNecesarios && guard < 2000) {
                guard++;
                const dow = cur.getDay();
                const fStr = fmtYmd(cur);
                const mes = cur.getFullYear() + '-' + String(cur.getMonth() + 1).padStart(2, '0');
                if (!mesMapa[mes]) mesMapa[mes] = { habiles: 0, finde: 0, feriados: 0 };

                if (dow === 0 || dow === 6) {
                    finSemana++;
                    mesMapa[mes].finde++;
                } else if (feriadoMap[fStr]) {
                    // Feriado NO laborable: no suma día hábil, desplaza la fecha fin
                    feriadosEnRango.push({ fecha: fStr, nombre: feriadoMap[fStr] });
                    mesMapa[mes].feriados++;
                } else {
                    diasHabiles++;
                    mesMapa[mes].habiles++;
                }
                totalDias++;
                fechaFin = new Date(cur);
                cur.setDate(cur.getDate() + 1);
            }
            setInputFecha('calcFechaHasta', fmtYmd(fechaFin));
        }

        const [fy, fm, fd] = fmtYmd(fechaFin).split('-');
        document.getElementById('calcHeroLabel').textContent = modo === 'rango'
            ? `Total en Rango: ${horas} Horas (${diasHabiles} días hábiles)`
            : 'Fecha Fin Estimada';
        document.getElementById('calcFechaFin').textContent = `${fd} de ${MESES_FULL[parseInt(fm, 10) - 1]}, ${fy}`;
        document.getElementById('calcDuracion').textContent = `${totalDias} días · ~${Math.max(1, Math.round(totalDias / 30.44))} meses`;
        document.getElementById('calcDuracionSub').textContent = `${horas} hrs acumulables (8h/día)`;

        document.getElementById('calcDiasHabiles').textContent = diasHabiles;
        document.getElementById('calcFinSemana').textContent = finSemana;
        document.getElementById('calcFeriados').textContent = feriadosEnRango.length;
        document.getElementById('calcTotalDias').textContent = totalDias;

        drawDonut(diasHabiles, finSemana, feriadosEnRango.length);

        const listaEl = document.getElementById('calcFeriadosLista');
        const listaWrap = document.getElementById('calcListaFeriados');
        if (feriadosEnRango.length > 0) {
            document.getElementById('calcFeriadosBadge').textContent = feriadosEnRango.length;
            const esc = s => { const t = document.createElement('span'); t.textContent = s; return t.innerHTML; };
            listaEl.innerHTML = feriadosEnRango.map(f => {
                const d = new Date(f.fecha + 'T00:00:00');
                return `<div class="hc-fer-item"><i class="ti ti-flag-filled" style="color:#f59e0b;font-size:.85rem;flex-shrink:0;"></i><span style="font-size:.78rem;font-weight:700;color:#1e293b;min-width:90px;">${DIAS[d.getDay()]} ${esc(f.fecha)}</span><span style="font-size:.78rem;color:#92400e;flex:1;">${esc(f.nombre)}</span></div>`;
            }).join('');
            listaWrap.style.display = 'block';
        } else {
            listaWrap.style.display = 'none';
        }

        const timelineEl = document.getElementById('calcTimeline');
        const mKeys = Object.keys(mesMapa).sort();
        const maxH = Math.max(...mKeys.map(m => mesMapa[m].habiles), 1);
        timelineEl.innerHTML = mKeys.map(m => {
            const d = mesMapa[m];
            const [my, mm] = m.split('-');
            const pct = Math.round(d.habiles / maxH * 100);
            const h = d.habiles * 8;
            return `<div class="hc-tl-row"><span class="hc-tl-month">${MESES[parseInt(mm, 10) - 1]} ${my.slice(2)}</span><div class="hc-tl-bar-wrap"><div class="hc-tl-bar" style="width:${pct}%;"><span class="hc-tl-bar-text">${d.habiles}d</span></div></div><span class="hc-tl-info">${h}h${d.feriados ? ' · <span style=color:#f59e0b>' + d.feriados + '<svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" style="vertical-align:middle;margin-left:2px;"><path d="M4 5a1 1 0 0 1 .3-.714 8 8 0 0 1 3.7-.786c1.929 0 3.603.57 5.03 1.149A16 16 0 0 0 16 5.97c.34.06.6.2.77.394.17.195.23.439.23.636v8a1 1 0 0 1-.3.714 8 8 0 0 1-3.7.786c-1.929 0-3.603-.57-5.03-1.149A16 16 0 0 0 5 14.03V21a1 1 0 1 1-2 0V5z"/></svg></span>' : ''}</span></div>`;
        }).join('');

        document.getElementById('calcLoading').style.display = 'none';
        document.getElementById('calcResultado').style.display = 'block';
    };

    window.abrirCalculadoraHoras = function() {
        const m = document.getElementById('modalCalcHoras');
        if (!m) return;
        if (m.parentElement !== document.body) {
            document.body.appendChild(m);
        }
        m.style.display = 'flex';
        m.classList.add('active');
        document.body.style.overflow = 'hidden';

        const initFp = (id, modoChange) => {
            const inp = document.getElementById(id);
            if (inp && window.flatpickr && !inp._flatpickr) {
                window.flatpickr(inp, {
                    locale: 'es',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    altInputClass: 'hc-input',
                    defaultDate: inp.value || undefined,
                    allowInput: true,
                    clickOpens: true,
                    disableMobile: true,
                    onChange: function() { window.calcularHoras(modoChange); }
                });
            }
        };

        initFp('calcFechaInicio', 'horas');
        initFp('calcFechaHasta', 'rango');
        window.calcularHoras('horas');
    };

    window.cerrarCalculadoraHoras = function() {
        const m = document.getElementById('modalCalcHoras');
        if (m) {
            m.style.display = 'none';
            m.classList.remove('active');
        }
        document.body.style.overflow = '';
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            const cl = document.getElementById('calcLoading');
            if (cl) cl.style.display = 'none';
        });
    } else {
        const cl = document.getElementById('calcLoading');
        if (cl) cl.style.display = 'none';
    }
})();
</script>
