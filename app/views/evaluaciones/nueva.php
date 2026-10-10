<?php
/**
 * Vista: Nueva Evaluación — Vista Dedicada
 * Ruta: app/views/evaluaciones/nueva.php
 *
 * $data recibe:
 *   pasante      → objeto con id, nombres, apellidos, cedula, departamento
 *   tutores      → array de objetos tutor
 *   tutorActualId→ int|null
 */

$pasante       = $data['pasante']       ?? null;
$pasantes      = $data['pasantes']      ?? [];
$tutores       = $data['tutores']       ?? [];
$tutorActualId = $data['tutorActualId'] ?? null;

/* ══════════════════════════════════════════════════════════════
   PASO 1: Sin pasante → mostrar selector de pasante (página)
   ══════════════════════════════════════════════════════════════ */
if (!$pasante):
?>
<style>
.sel-wrap    { width: 100%; padding-bottom:40px; }
.sel-banner  {
    background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);
    border-radius:20px; padding:28px 36px; margin-bottom:28px;
    display:flex; align-items:center; justify-content:space-between;
    position:relative; overflow:hidden;
}
.sel-banner::before { content:''; position:absolute; top:-40px; right:-40px; width:200px; height:200px; background:rgba(255,255,255,0.05); border-radius:50%; }
.sel-search-box {
    background:#fff; border-radius:16px; padding:22px 24px;
    box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:22px;
}
.sel-search-input {
    width:100%; padding:13px 18px 13px 46px; border:2px solid #e2e8f0;
    border-radius:12px; font-size:1rem; color:#1e293b; background:#f8fafc;
    transition:all 0.2s; box-sizing:border-box;
}
.sel-search-input:focus { outline:none; border-color:#2563eb; background:#fff; box-shadow:0 0 0 4px rgba(37,99,235,0.1); }
.sel-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:14px;
}
.sel-card {
    background:#fff; border-radius:14px; padding:18px 20px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06); border:2px solid transparent;
    cursor:pointer; transition:all 0.2s; text-decoration:none;
    display:flex; align-items:center; gap:14px;
}
.sel-card:hover { border-color:#2563eb; transform:translateY(-2px); box-shadow:0 8px 20px rgba(37,99,235,0.15); }
.sel-avatar {
    width:46px; height:46px; border-radius:12px; flex-shrink:0;
    background:linear-gradient(135deg,#162660,#2563eb);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:1rem;
}
.sel-name   { font-weight:700; color:#1e293b; font-size:0.9rem; line-height:1.3; }
.sel-meta   { font-size:0.75rem; color:#94a3b8; margin-top:2px; }
.sel-empty  { grid-column:1/-1; text-align:center; padding:40px; color:#94a3b8; }
</style>

<div class="sel-wrap">
    <!-- Banner -->
    <div class="sel-banner">
        <div style="z-index:1;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:11px;">
                    <i class="ti ti-star" style="font-size:26px;color:white;"></i>
                </div>
                <div>
                    <h1 style="color:#fff;font-size:1.6rem;font-weight:700;margin:0;">Nueva Evaluación</h1>
                    <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:0.88rem;">
                        <i class="ti ti-users"></i> Selecciona al pasante a evaluar
                    </p>
                </div>
            </div>
        </div>
        <a href="<?= URLROOT ?>/evaluaciones"
           style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);border-radius:10px;padding:9px 18px;color:#fff;font-size:0.85rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:7px;z-index:1;"
           onmouseover="this.style.background='rgba(255,255,255,0.25)'"
           onmouseout="this.style.background='rgba(255,255,255,0.15)'">
            <i class="ti ti-arrow-left"></i> Cancelar
        </a>
    </div>

    <!-- Buscador -->
    <div class="sel-search-box">
        <div style="position:relative;">
            <i class="ti ti-search" style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:1.1rem;pointer-events:none;"></i>
            <input type="text" id="selBuscar" class="sel-search-input"
                   placeholder="Buscar por nombre o cédula..."
                   oninput="filtrarPasantes(this.value)"
                   autofocus>
        </div>
        <p style="color:#94a3b8;font-size:0.8rem;margin:10px 0 0;">
            <i class="ti ti-info-circle"></i>
            <?= count($pasantes) ?> pasante(s) activo(s) disponible(s) para evaluar
        </p>
    </div>

    <!-- Grid de pasantes -->
    <div class="sel-grid" id="selGrid">
        <?php if (empty($pasantes)): ?>
        <div class="sel-empty">
            <i class="ti ti-user-off" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.4;"></i>
            No tienes pasantes activos asignados.
        </div>
        <?php else: ?>
        <?php foreach ($pasantes as $p):
            $ini = strtoupper(substr($p->nombres ?? '?', 0, 1) . substr($p->apellidos ?? '', 0, 1));
            $nombre = htmlspecialchars(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''));
        ?>
        <a class="sel-card" href="<?= URLROOT ?>/evaluaciones/nueva/<?= (int)$p->id ?>"
           data-search="<?= strtolower(($p->nombres ?? '') . ' ' . ($p->apellidos ?? '') . ' ' . ($p->cedula ?? '')) ?>">
            <div class="sel-avatar"><?= $ini ?></div>
            <div>
                <div class="sel-name"><?= $nombre ?></div>
                <div class="sel-meta">
                    <i class="ti ti-id"></i> <?= htmlspecialchars($p->cedula ?? '—') ?>
                    <?php if (!empty($p->departamento)): ?>
                    &nbsp;·&nbsp;<i class="ti ti-building"></i> <?= htmlspecialchars($p->departamento) ?>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filtrarPasantes(q) {
    q = q.toLowerCase().trim();
    var cards = document.querySelectorAll('#selGrid .sel-card');
    var visible = 0;
    cards.forEach(function(c) {
        var match = !q || c.dataset.search.includes(q);
        c.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    var empty = document.getElementById('selEmpty');
    if (!empty) {
        empty = document.createElement('div');
        empty.id = 'selEmpty';
        empty.className = 'sel-empty';
        // [FIX-C1] Estructura estática del mensaje — el texto dinámico se asigna con textContent
        empty.innerHTML = '<i class="ti ti-search-off" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4;" aria-hidden="true"></i><span class="sel-empty-text"></span>';
        document.getElementById('selGrid').appendChild(empty);
    }
    // [FIX-C1] textContent en lugar de innerHTML — previene XSS con cualquier valor de q
    empty.querySelector('.sel-empty-text').textContent = 'Sin resultados para "' + q + '"';
    empty.style.display = (visible === 0 && cards.length > 0) ? '' : 'none';
}
</script>
<?php
    return; // No renderizar nada más
endif;

/* ══════════════════════════════════════════════════════════════
   PASO 2: Con pasante → formulario de evaluación
   ══════════════════════════════════════════════════════════════ */

$iniciales = strtoupper(
    substr($pasante->nombres   ?? '?', 0, 1) .
    substr($pasante->apellidos ?? '',  0, 1)
);

$categorias = [
    'Actitudes' => [
        'color' => '#f59e0b',
        'icon'  => 'ti-mood-happy',
        'items' => [
            ['campo' => 'criterio_iniciativa', 'label' => 'Iniciativa',   'icon' => 'ti-bulb',  'desc' => 'Capacidad para proponer ideas y actuar proactivamente'],
            ['campo' => 'criterio_interes',    'label' => 'Interés',      'icon' => 'ti-heart', 'desc' => 'Motivación y entusiasmo por las actividades asignadas'],
        ],
    ],
    'Competencias' => [
        'color' => '#2563eb',
        'icon'  => 'ti-brain',
        'items' => [
            ['campo' => 'criterio_conocimiento', 'label' => 'Conocimiento', 'icon' => 'ti-book',         'desc' => 'Dominio técnico y teórico en su área'],
            ['campo' => 'criterio_analisis',     'label' => 'Análisis',     'icon' => 'ti-brain',        'desc' => 'Capacidad para analizar problemas y situaciones'],
            ['campo' => 'criterio_comunicacion', 'label' => 'Comunicación', 'icon' => 'ti-message-dots', 'desc' => 'Habilidad para expresar ideas de forma clara'],
            ['campo' => 'criterio_aprendizaje',  'label' => 'Aprendizaje',  'icon' => 'ti-school',       'desc' => 'Velocidad y efectividad para adquirir nuevos conocimientos'],
        ],
    ],
    'Valores' => [
        'color' => '#10b981',
        'icon'  => 'ti-heart-handshake',
        'items' => [
            ['campo' => 'criterio_companerismo', 'label' => 'Compañerismo', 'icon' => 'ti-users',     'desc' => 'Relación y apoyo con los compañeros de trabajo'],
            ['campo' => 'criterio_cooperacion',  'label' => 'Cooperación',  'icon' => 'ti-hand-stop', 'desc' => 'Disposición para colaborar en actividades grupales'],
        ],
    ],
    'Disciplina' => [
        'color' => '#8b5cf6',
        'icon'  => 'ti-shield-check',
        'items' => [
            ['campo' => 'criterio_puntualidad',  'label' => 'Puntualidad',  'icon' => 'ti-clock', 'desc' => 'Cumplimiento de horarios y compromisos'],
            ['campo' => 'criterio_presentacion', 'label' => 'Presentación', 'icon' => 'ti-shirt', 'desc' => 'Aspecto personal y cuidado en la presentación'],
        ],
    ],
    'Desempeño' => [
        'color' => '#ef4444',
        'icon'  => 'ti-chart-bar',
        'items' => [
            ['campo' => 'criterio_desarrollo',    'label' => 'Desarrollo',             'icon' => 'ti-code',            'desc' => 'Calidad en el desarrollo de actividades asignadas'],
            ['campo' => 'criterio_analisis_res',  'label' => 'Análisis de Resultados', 'icon' => 'ti-chart-bar',       'desc' => 'Capacidad para interpretar y evaluar resultados'],
            ['campo' => 'criterio_conclusiones',  'label' => 'Conclusiones',           'icon' => 'ti-clipboard-check', 'desc' => 'Elaboración de conclusiones fundamentadas'],
            ['campo' => 'criterio_recomendacion', 'label' => 'Recomendaciones',        'icon' => 'ti-star',            'desc' => 'Aportes y sugerencias de mejora'],
        ],
    ],
];

$totalCriterios = array_sum(array_map(fn($c) => count($c['items']), $categorias));
?>

<style>
/* ── Variables ─────────────────────────────── */
:root {
    --eval-blue:   #172554;
    --eval-mid:    #1e3a8a;
    --eval-accent: #2563eb;
    --eval-radius: 16px;
    --eval-shadow: 0 2px 12px rgba(0,0,0,0.07);
}

/* ── Layout ────────────────────────────────── */
.eval-wrapper {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
    padding-bottom: 120px; /* espacio para el footer fijo */
}

/* ── Header sticky ─────────────────────────── */
.eval-sticky-header {
    position: sticky;
    top: 0;
    z-index: 200;
    background: linear-gradient(135deg, var(--eval-blue) 0%, var(--eval-mid) 50%, var(--eval-accent) 100%);
    border-radius: 20px;
    padding: 20px 28px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(23,37,84,0.35);
}

/* ── Tarjeta categoría ─────────────────────── */
.eval-cat-card {
    background: white;
    border-radius: var(--eval-radius);
    box-shadow: var(--eval-shadow);
    overflow: hidden;
    margin-bottom: 20px;
    transition: box-shadow 0.2s;
}
.eval-cat-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.1); }

.eval-cat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 22px;
    border-bottom: 1px solid #f1f5f9;
}
.eval-cat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

/* ── Criterio row ──────────────────────────── */
.eval-criterio-row {
    display: none; /* Se desactiva el estilo antiguo */
}
.eval-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    padding: 20px;
}
@media (max-width: 575px) {
    .eval-items-grid {
        grid-template-columns: 1fr;
    }
}

.eval-criterio-tile {
    background: #fcfdfe;
    border: 1.5px solid #f1f5f9;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: default;
    position: relative;
}
.eval-criterio-tile:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
}
.eval-criterio-tile.completado {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.eval-criterio-label {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.88rem;
    margin: 0;
}
.eval-criterio-desc {
    font-size: 0.72rem;
    color: #94a3b8;
    line-height: 1.3;
    height: 34px; /* Altura fija para uniformidad */
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* ── Stars ─────────────────────────────────── */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    gap: 2px;
    flex-shrink: 0;
}
.star-rating input { display: none; }
.star-rating label {
    cursor: pointer;
    font-size: 1.8rem;
    color: #e2e8f0;
    transition: all 0.12s;
    line-height: 1;
    user-select: none;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #f59e0b;
    transform: scale(1.2);
}
/* Badge de equivalencia ISP */
.star-equiv-badge {
    font-size:.67rem;font-weight:800;padding:2px 9px;border-radius:20px;
    white-space:nowrap;display:inline-block;margin-top:4px;
    transition:all .2s;
}
.star-equiv-wrap { display:flex;flex-direction:column;align-items:flex-end;gap:0; }

/* ── Panel lateral sticky ──────────────────── */
.eval-sidebar {
    position: sticky;
    top: 88px; /* altura del header sticky + gap */
}

/* ── Progreso ──────────────────────────────── */
.eval-progress-ring-track {
    fill: none;
    stroke: #e2e8f0;
    stroke-width: 8;
}
.eval-progress-ring-fill {
    fill: none;
    stroke: #2563eb;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dashoffset 0.4s ease;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

/* ── Footer fijo ───────────────────────────── */
.eval-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 300;
    background: white;
    border-top: 1px solid #e2e8f0;
    padding: 14px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
}

.eval-btn-borrador {
    padding: 11px 22px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    color: #64748b;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}
.eval-btn-borrador:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }

.eval-btn-guardar {
    padding: 11px 28px;
    background: linear-gradient(135deg, var(--eval-blue) 0%, var(--eval-accent) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    opacity: 0.5;
    pointer-events: none;
}
.eval-btn-guardar.listo {
    opacity: 1;
    pointer-events: all;
    box-shadow: 0 4px 14px rgba(37,99,235,0.35);
}
.eval-btn-guardar.listo:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,0.4); }

/* ── Input ─────────────────────────────────── */
.input-modern {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.2s;
    background: white;
    color: #1e293b;
    font-weight: 500;
    box-sizing: border-box;
}
.input-modern:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }

/* ── Pasante card ──────────────────────────── */
.eval-pasante-card {
    background: white;
    border-radius: var(--eval-radius);
    box-shadow: var(--eval-shadow);
    padding: 20px;
    margin-bottom: 20px;
}

/* ── Responsive ────────────────────────────── */
@media (max-width: 900px) {
    .eval-wrapper { grid-template-columns: 1fr; }
    .eval-sidebar { position: static; }
    .eval-footer { padding: 12px 16px; }
    .eval-sticky-header { border-radius: 14px; padding: 16px 20px; }
}
</style>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  HEADER STICKY                                           -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="eval-sticky-header">
    <div style="display:flex;align-items:center;gap:14px;min-width:0;">
        <!-- Avatar pasante -->
        <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:1.1rem;flex-shrink:0;letter-spacing:0.5px;">
            <?= $iniciales ?>
        </div>
        <div style="min-width:0;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span style="color:white;font-weight:800;font-size:1.1rem;white-space:nowrap;">
                    <?= htmlspecialchars($pasante->nombres . ' ' . $pasante->apellidos) ?>
                </span>
                <span style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);border-radius:20px;padding:2px 10px;color:white;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                    C.I. <?= htmlspecialchars($pasante->cedula ?? '—') ?>
                </span>
            </div>
            <p style="color:rgba(255,255,255,0.7);margin:3px 0 0;font-size:0.82rem;display:flex;align-items:center;gap:6px;">
                <i class="ti ti-star"></i>
                Nueva Evaluación — Planilla Digital · <?= $totalCriterios ?> criterios
            </p>
        </div>
    </div>
    <a href="<?= URLROOT ?>/evaluaciones"
       style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);border-radius:10px;padding:9px 18px;color:white;font-size:0.85rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:7px;flex-shrink:0;transition:background 0.2s;"
       onmouseover="this.style.background='rgba(255,255,255,0.25)'"
       onmouseout="this.style.background='rgba(255,255,255,0.15)'"
       onclick="return confirmarSalida()">
        <i class="ti ti-arrow-left"></i> Cancelar
    </a>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  BODY: dos columnas                                       -->
<!-- ══════════════════════════════════════════════════════════ -->
<form id="formEvaluacion" onsubmit="submitEvaluacion(event)">
<input type="hidden" name="pasante_id" value="<?= (int)$pasante->id ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken()) ?>">

<div class="eval-wrapper">

    <!-- ── COLUMNA PRINCIPAL: categorías ── -->
    <div>

        <!-- Leyenda de equivalencia estrellas → columna PDF -->
        <div style="background:white;border-radius:14px;padding:12px 18px;margin-bottom:18px;
                    border:1.5px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                <i class="ti ti-info-circle" style="color:#2563eb;margin-right:4px;"></i>
                Equivalencia de calificación → Columna en el reporte ISP
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:#dcfce7;color:#15803d;font-size:.73rem;font-weight:800;">★★★★★ Excelente</span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:#d1fae5;color:#059669;font-size:.73rem;font-weight:800;">★★★★☆ Sobre Promedio</span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:#fef3c7;color:#b45309;font-size:.73rem;font-weight:800;">★★★☆☆ Promedio</span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:#fee2e2;color:#dc2626;font-size:.73rem;font-weight:800;">★★☆☆☆ Bajo Promedio</span>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:#fecaca;color:#b91c1c;font-size:.73rem;font-weight:800;">★☆☆☆☆ Deficiente</span>
            </div>
        </div>

        <?php foreach ($categorias as $catNombre => $cat):
            $color = $cat['color'];
        ?>
        <div class="eval-cat-card" data-categoria="<?= htmlspecialchars($catNombre) ?>">
            <div class="eval-cat-header">
                <div class="eval-cat-icon" style="background:<?= $color ?>18;">
                    <i class="ti <?= $cat['icon'] ?>" style="color:<?= $color ?>;"></i>
                </div>
                <div style="flex:1;">
                    <h4 style="margin:0;color:<?= $color ?>;font-size:1rem;font-weight:700;"><?= $catNombre ?></h4>
                    <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin-top:2px;" title="Marcar todos como Excelente">
                        <span style="font-size:0.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;">EXCELENTE</span>
                        <div class="cat-switch-bg" style="position:relative;width:28px;height:14px;background:#e2e8f0;border-radius:50px;transition:background 0.3s;display:flex;align-items:center;">
                            <div class="cat-switch-knob" style="position:absolute;left:2px;width:10px;height:10px;background:white;border-radius:50%;transition:transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);box-shadow:0 1px 3px rgba(0,0,0,0.1);"></div>
                        </div>
                        <input type="checkbox" class="cat-switch-input" onchange="window.toggleCategoria(this)" style="display:none;">
                    </label>
                </div>
                <!-- Badge completados por categoría -->
                <span class="badge-cat-<?= strtolower($catNombre) ?>" style="background:<?= $color ?>18;color:<?= $color ?>;font-size:0.75rem;font-weight:700;padding:4px 10px;border-radius:20px;">
                    0/<?= count($cat['items']) ?>
                </span>
            </div>

            <div class="eval-items-grid">
                <?php foreach ($cat['items'] as $crit): ?>
                <div class="eval-criterio-tile" id="row_<?= $crit['campo'] ?>">
                    <div style="background:<?= $color ?>12;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="ti <?= $crit['icon'] ?>" style="color:<?= $color ?>;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="eval-criterio-label"><?= $crit['label'] ?></h5>
                        <p class="eval-criterio-desc" title="<?= htmlspecialchars($crit['desc']) ?>"><?= $crit['desc'] ?></p>
                    </div>
                    <div class="star-equiv-wrap">
                        <div class="star-rating" data-campo="<?= $crit['campo'] ?>"
                             onchange="actualizarEquiv('<?= $crit['campo'] ?>', this.querySelector(':checked')?.value)">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="<?= $crit['campo'] ?>" value="<?= $i ?>"
                                   id="<?= $crit['campo'] ?>_<?= $i ?>" required
                                   onchange="actualizarEquiv('<?= $crit['campo'] ?>', <?= $i ?>)">
                            <label for="<?= $crit['campo'] ?>_<?= $i ?>"
                                   title="<?= $i ?> — <?= ['','Deficiente','Bajo Promedio','Promedio','Sobre Promedio','Excelente'][$i] ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <span id="equiv_<?= $crit['campo'] ?>" class="star-equiv-badge"
                              style="background:#f1f5f9;color:#94a3b8;">Sin calificar</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Observaciones -->
        <div style="background:white;border-radius:var(--eval-radius);box-shadow:var(--eval-shadow);padding:22px;margin-bottom:20px;">
            <label style="display:block;font-weight:700;color:#374151;margin-bottom:10px;font-size:0.9rem;">
                <i class="ti ti-notes" style="margin-right:6px;color:#64748b;"></i>Observaciones Generales
            </label>
            <textarea name="observaciones" rows="4" class="input-modern"
                      placeholder="Comentarios adicionales sobre el desempeño del pasante..."
                      style="resize:vertical;"></textarea>
        </div>

    </div><!-- /columna principal -->

    <!-- ── COLUMNA LATERAL: resumen sticky ── -->
    <div class="eval-sidebar">

    <!-- Datos de la evaluación -->
        <div class="eval-pasante-card">
            <p style="font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 14px;">Datos de la evaluación</p>

            <?php
            $rolIdVista    = (int)(Session::get('role_id') ?? 0);
            $periodoNombre = $data['periodoNombre'] ?? ($periodoNombre ?? '');
            ?>

            <!-- Período académico (SOLO LECTURA — se toma de la asignación) -->
            <div style="margin-bottom:14px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:0.85rem;">
                    <i class="ti ti-calendar-event" style="margin-right:5px;color:#8b5cf6;"></i>Período Académico
                </label>
                <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f5f3ff;border:1.5px solid #e9d5ff;border-radius:10px;">
                    <i class="ti ti-lock" style="font-size:0.85rem;color:#8b5cf6;flex-shrink:0;"></i>
                    <span style="font-size:0.88rem;font-weight:600;color:#6d28d9;">
                        <?= htmlspecialchars($periodoNombre ?: 'Sin período asignado') ?>
                    </span>
                    <?php if ($periodoNombre): ?>
                    <span style="margin-left:auto;background:#8b5cf6;color:white;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:0.3px;">Auto</span>
                    <?php endif; ?>
                </div>
                <?php if (!$periodoNombre): ?>
                <p style="font-size:0.72rem;color:#ef4444;margin:4px 0 0;"><i class="ti ti-alert-circle"></i> El pasante no tiene período asignado. Asígnelo en el módulo de Asignaciones.</p>
                <?php endif; ?>
            </div>

            <!-- Tutor Evaluador -->
            <div style="margin-bottom:14px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:0.85rem;">
                    <i class="ti ti-school" style="margin-right:5px;color:#2563eb;"></i>Tutor Evaluador *
                </label>
                <?php
                // Sesión actual del evaluador
                $sessionUserId = (int)(Session::get('user_id') ?? 0);
                ?>
                <select name="tutor_id" class="input-modern" id="selectTutorEval">
                    <?php foreach ($tutores as $t):
                        // Predeterminado: el tutor original asignado al pasante
                        // Si no hay tutor asignado, se pre-selecciona el usuario actual
                        $defaultId  = $tutorActualId ?: $sessionUserId;
                        $isSelected = ((int)$t->id === (int)$defaultId);
                        $rolLabel   = ($t->rol_id == 1) ? 'Admin' : 'Tutor';
                        $nombre     = htmlspecialchars(trim(($t->nombres ?? '') . ' ' . ($t->apellidos ?? '')));
                    ?>
                    <option value="<?= (int)$t->id ?>" <?= $isSelected ? 'selected' : '' ?>>
                        <?= $nombre ?> (<?= $rolLabel ?>)<?= $isSelected ? ' — Predeterminado' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($rolIdVista !== 1): // Solo para tutores, mostrar aviso contextual ?>
                <p style="font-size:0.72rem;color:#64748b;margin:5px 0 0;display:flex;align-items:center;gap:4px;">
                    <i class="ti ti-info-circle" style="color:#2563eb;"></i>
                    Por defecto se asigna el tutor original del pasante.
                </p>
                <?php endif; ?>
            </div>

            <!-- Fecha (Auto) -->
            <div>
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:0.85rem;">
                    <i class="ti ti-calendar-time" style="margin-right:5px;color:#10b981;"></i>Fecha Registro
                </label>
                <div class="input-modern" style="background:#f0fbdf;color:#059669;display:flex;align-items:center;gap:8px;cursor:default;border:1.5px solid #d1fae5;">
                    <i class="ti ti-clock" style="font-size:0.9rem;"></i>
                    Automática (<?= date('d/m/Y') ?>)
                </div>
                <!-- Campo oculto para cumplir con el POST del backend, si aplica -->
                <input type="hidden" name="fecha_evaluacion" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Ring de progreso -->
        <div style="background:white;border-radius:var(--eval-radius);box-shadow:var(--eval-shadow);padding:24px;text-align:center;">

            <svg width="130" height="130" viewBox="0 0 130 130" style="display:block;margin:0 auto 16px;">
                <circle class="eval-progress-ring-track" cx="65" cy="65" r="54"/>
                <circle class="eval-progress-ring-fill" id="progressRing"
                        cx="65" cy="65" r="54"
                        stroke-dasharray="339.29"
                        stroke-dashoffset="339.29"/>
                <text x="65" y="58" text-anchor="middle" font-size="28" font-weight="800" fill="#1e293b" id="progressCount">0</text>
                <text x="65" y="76" text-anchor="middle" font-size="12" fill="#94a3b8">de <?= $totalCriterios ?></text>
            </svg>

            <p style="font-size:0.8rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Promedio actual</p>
            <div style="display:flex;align-items:baseline;justify-content:center;gap:4px;">
                <span id="promedioDisplay" style="font-size:2.8rem;font-weight:900;color:#162660;line-height:1;transition:color 0.3s;">0.00</span>
                <span style="font-size:1rem;color:#94a3b8;font-weight:600;">/5</span>
            </div>

            <!-- Desglose por categoría -->
            <div style="margin-top:18px;border-top:1px solid #f1f5f9;padding-top:16px;">
                <?php foreach ($categorias as $catNombre => $cat): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:8px;height:8px;border-radius:50%;background:<?= $cat['color'] ?>;flex-shrink:0;"></div>
                        <span style="font-size:0.78rem;color:#64748b;font-weight:500;"><?= $catNombre ?></span>
                    </div>
                    <span class="prom-cat-<?= strtolower($catNombre) ?>" style="font-size:0.78rem;font-weight:700;color:#94a3b8;">—</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /sidebar -->

</div><!-- /eval-wrapper -->
</form>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  FOOTER FIJO                                             -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="eval-footer">
    <div style="display:flex;align-items:center;gap:10px;">
        <div id="footerBadge" style="background:#f1f5f9;border-radius:20px;padding:6px 14px;font-size:0.82rem;color:#64748b;font-weight:600;">
            <i class="ti ti-clipboard" style="margin-right:4px;"></i>
            <span id="footerContador">0 de <?= $totalCriterios ?> criterios</span>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <button type="button" class="eval-btn-borrador" id="btnMarcarTodoGlobal" onclick="toggleMarcarTodo(this)" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);border:none;box-shadow: 0 4px 15px rgba(16,185,129,0.3);color:white;transition:all .3s ease;" 
                onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow=this.dataset.marcado==='1'?'0 6px 20px rgba(239,68,68,0.4)':'0 6px 20px rgba(16,185,129,0.4)';" 
                onmouseout="this.style.transform='none';this.style.boxShadow=this.dataset.marcado==='1'?'0 4px 15px rgba(239,68,68,0.3)':'0 4px 15px rgba(16,185,129,0.3)';">
            <i class="ti ti-stars" style="font-size:1.05rem;"></i> <span>Excelente a todo</span>
        </button>
        <button type="button" class="eval-btn-borrador" onclick="guardarBorrador()">
            <i class="ti ti-device-floppy"></i> Guardar borrador
        </button>
        <button type="submit" form="formEvaluacion" class="eval-btn-guardar" id="btnGuardarEval">
            <i class="ti ti-check"></i> Guardar Evaluación
        </button>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════ -->
<!--  JAVASCRIPT                                              -->
<!-- ══════════════════════════════════════════════════════════ -->
<script>
(function () {
    /* ── Constantes ─────────────────────────────── */
    var TOTAL      = <?= $totalCriterios ?>;
    var PASANTE_ID = <?= (int)$pasante->id ?>;
    var DRAFT_KEY  = 'sgp_eval_draft_' + PASANTE_ID;

    var categorias = <?= json_encode(
        array_map(fn($n, $c) => [
            'nombre' => $n,
            'color'  => $c['color'],
            'campos' => array_column($c['items'], 'campo'),
        ], array_keys($categorias), $categorias),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP
    ) ?>;

    /* ── Estado ─────────────────────────────────── */
    var valores = {};  // campo → valor 1-5

    /* ── Restaurar borrador ─────────────────────── */
    (function restaurarBorrador() {
        var raw = sessionStorage.getItem(DRAFT_KEY);
        if (!raw) return;
        try {
            var draft = JSON.parse(raw);
            Object.keys(draft).forEach(function (campo) {
                var val = draft[campo];
                if (!val) return;
                var input = document.getElementById(campo + '_' + val);
                if (input) {
                    input.checked = true;
                    valores[campo] = val;
                }
            });
            actualizarTodo();
            setTimeout(refrescarTodosLosBadges, 50);
        } catch (e) { /* borrador corrupto — ignorar */ }
    })();

    /* ── Badge de equivalencia ISP ──────────────── */
    const EQUIV_MAP = {
        5: { label:'Excelente',      bg:'#dcfce7', color:'#15803d' },
        4: { label:'Sobre Promedio', bg:'#d1fae5', color:'#059669' },
        3: { label:'Promedio',       bg:'#fef3c7', color:'#b45309' },
        2: { label:'Bajo Promedio',  bg:'#fee2e2', color:'#dc2626' },
        1: { label:'Deficiente',     bg:'#fecaca', color:'#b91c1c' },
    };
    window.actualizarEquiv = function(campo, val) {
        const badge = document.getElementById('equiv_' + campo);
        if (!badge) return;
        const v = parseInt(val);
        if (EQUIV_MAP[v]) {
            badge.textContent   = EQUIV_MAP[v].label;
            badge.style.background = EQUIV_MAP[v].bg;
            badge.style.color      = EQUIV_MAP[v].color;
        } else {
            badge.textContent      = 'Sin calificar';
            badge.style.background = '#f1f5f9';
            badge.style.color      = '#94a3b8';
        }
    };
    // Restaurar badges si hay borrador cargado
    function refrescarTodosLosBadges() {
        document.querySelectorAll('.star-rating input:checked').forEach(input => {
            actualizarEquiv(input.name, input.value);
        });
    }

    /* ── Escuchar cambios en estrellas ──────────── */
    document.getElementById('formEvaluacion').addEventListener('change', function (e) {
        if (e.target.type !== 'radio') return;
        var campo = e.target.name;
        var val   = parseInt(e.target.value);
        valores[campo] = val;

        /* marcar tile completado */
        var tile = document.getElementById('row_' + campo);
        if (tile) tile.classList.add('completado');

        actualizarTodo();
        autoGuardarBorrador();
    });

    /* ── Actualizar toda la UI ──────────────────── */
    function actualizarTodo() {
        var completados = Object.keys(valores).length;
        var suma        = Object.values(valores).reduce(function (a, b) { return a + b; }, 0);
        var promedio    = completados > 0 ? suma / TOTAL : 0;

        /* Promedio display */
        var display = document.getElementById('promedioDisplay');
        display.textContent = (completados > 0 ? (suma / completados).toFixed(2) : '0.00');

        /* Color promedio */
        var prom = parseFloat(display.textContent);
        if (prom >= 4)      display.style.color = '#10b981';
        else if (prom >= 3) display.style.color = '#f59e0b';
        else if (prom > 0)  display.style.color = '#ef4444';
        else                display.style.color = '#162660';

        /* Ring SVG */
        var circumference = 339.29;
        var offset = circumference - (completados / TOTAL) * circumference;
        var ring = document.getElementById('progressRing');
        ring.style.strokeDashoffset = offset;
        ring.style.stroke = completados === TOTAL ? '#10b981' : '#2563eb';

        /* Contador SVG y footer */
        document.getElementById('progressCount').textContent = completados;
        document.getElementById('footerContador').textContent = completados + ' de ' + TOTAL + ' criterios';

        /* Footer badge color */
        var badge = document.getElementById('footerBadge');
        if (completados === TOTAL) {
            badge.style.background = '#dcfce7';
            badge.style.color      = '#166534';
        } else {
            badge.style.background = '#f1f5f9';
            badge.style.color      = '#64748b';
        }

        /* Botón guardar */
        var btn = document.getElementById('btnGuardarEval');
        if (completados === TOTAL) btn.classList.add('listo');
        else                       btn.classList.remove('listo');

        /* Desglose por categoría */
        categorias.forEach(function (cat) {
            var catVals = cat.campos
                .filter(function (c) { return valores[c]; })
                .map(function (c)    { return valores[c]; });

            /* Badge X/N en header de categoría */
            var badge = document.querySelector(
                '.badge-cat-' + cat.nombre.toLowerCase().replace(/é/g, 'e').replace(/ñ/g, 'n')
            );
            if (badge) badge.textContent = catVals.length + '/' + cat.campos.length;

            /* Promedio en sidebar */
            var promEl = document.querySelector(
                '.prom-cat-' + cat.nombre.toLowerCase().replace(/é/g, 'e').replace(/ñ/g, 'n')
            );
            if (promEl) {
                if (catVals.length === 0) {
                    promEl.textContent = '—';
                    promEl.style.color = '#94a3b8';
                } else {
                    var avg = (catVals.reduce(function (a, b) { return a + b; }, 0) / catVals.length).toFixed(1);
                    promEl.textContent = avg + '/5';
                    promEl.style.color = parseFloat(avg) >= 4 ? '#10b981' : parseFloat(avg) >= 3 ? '#f59e0b' : '#ef4444';
                }
            }
        });
    }

    /* ── Borrador en sessionStorage ─────────────── */
    function autoGuardarBorrador() {
        try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(valores)); } catch (e) {}
    }

    window.toggleMarcarTodo = function(btn) {
        if (btn.dataset.marcado === '1') {
            document.querySelectorAll('.cat-switch-input').forEach(chk => {
                chk.checked = false;
                window.toggleCategoria(chk);
            });
            btn.dataset.marcado = '0';
            btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            btn.style.boxShadow = '0 4px 15px rgba(16,185,129,0.3)';
            if (btn.querySelector('span')) btn.querySelector('span').innerText = 'Excelente a todo';
            if (btn.querySelector('i')) btn.querySelector('i').className = 'ti ti-stars';
        } else {
            document.querySelectorAll('.cat-switch-input').forEach(chk => {
                chk.checked = true;
                window.toggleCategoria(chk);
            });
            btn.dataset.marcado = '1';
            btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            btn.style.boxShadow = '0 4px 15px rgba(239, 68, 68, 0.3)';
            if (btn.querySelector('span')) btn.querySelector('span').innerText = 'Desmarcar todo';
            if (btn.querySelector('i')) btn.querySelector('i').className = 'ti ti-eraser';
        }
    };

    window.toggleCategoria = function(checkbox) {
        let bg = checkbox.previousElementSibling;
        let knob = bg.querySelector('.cat-switch-knob');
        if (checkbox.checked) {
            bg.style.background = '#10b981';
            knob.style.transform = 'translateX(14px)';
        } else {
            bg.style.background = '#e2e8f0';
            knob.style.transform = 'none';
        }
        let container = checkbox.closest('.eval-cat-card');
        if (container) {
            container.querySelectorAll('.eval-criterio-tile').forEach(tile => {
                const inputs = tile.querySelectorAll('input[type="radio"]');
                if (checkbox.checked && inputs.length >= 5) {
                    inputs[0].checked = true; // Radio 5 es el primero en el DOM (flex-reverse)
                    let campo = inputs[0].name;
                    valores[campo] = 5;
                    tile.classList.add('completado');
                } else if (!checkbox.checked) {
                    inputs.forEach(i => i.checked = false);
                    tile.classList.remove('completado');
                    let campo = tile.id.replace('row_', '');
                    delete valores[campo];
                }
            });
            actualizarTodo();
            autoGuardarBorrador();
        }
    };

    window.guardarBorrador = function () {
        autoGuardarBorrador();
        /* Feedback visual */
        var btn = document.querySelector('.eval-btn-borrador');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check"></i> Borrador guardado';
        btn.style.borderColor = '#10b981';
        btn.style.color       = '#10b981';
        setTimeout(function () {
            btn.innerHTML    = orig;
            btn.style.borderColor = '';
            btn.style.color       = '';
        }, 2000);
    };

    /* ── Confirmar salida si hay datos ──────────── */
    window.confirmarSalida = function () {
        if (Object.keys(valores).length === 0) return true;
        return confirm('¿Salir sin guardar? Se perderán los criterios evaluados hasta ahora.\n\nUsa "Guardar borrador" para conservarlos.');
    };

    window.addEventListener('beforeunload', function (e) {
        if (Object.keys(valores).length > 0 && Object.keys(valores).length < TOTAL) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    /* ── Submit ─────────────────────────────────── */
    window.submitEvaluacion = async function (e) {
        e.preventDefault();

        if (Object.keys(valores).length < TOTAL) {
            Swal.fire({
                icon: 'warning',
                title: 'Criterios incompletos',
                text: 'Debes evaluar los ' + TOTAL + ' criterios antes de guardar.',
                confirmButtonColor: '#162660'
            });
            return;
        }

        var btn = document.getElementById('btnGuardarEval');
        btn.disabled   = true;
        btn.innerHTML  = '<i class="ti ti-loader"></i> Guardando...';

        var fd = new FormData(document.getElementById('formEvaluacion'));

        try {
            var resp = await fetch('<?= URLROOT ?>/evaluaciones/guardar', {
                method: 'POST', body: fd
            });
            var json = await resp.json();

            if (json.success) {
                /* Limpiar borrador */
                sessionStorage.removeItem(DRAFT_KEY);
                /* Quitar el beforeunload */
                window.onbeforeunload = null;

                await Swal.fire({
                    icon: 'success',
                    title: '¡Evaluación guardada!',
                    html: '<p>' + (json.message || 'La evaluación fue registrada exitosamente.') + '</p>',
                    confirmButtonColor: '#162660'
                });
                window.location.href = '<?= URLROOT ?>/evaluaciones';
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: json.message, confirmButtonColor: '#162660' });
                btn.disabled  = false;
                btn.innerHTML = '<i class="ti ti-check"></i> Guardar Evaluación';
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Intenta de nuevo.', confirmButtonColor: '#162660' });
            btn.disabled  = false;
            btn.innerHTML = '<i class="ti ti-check"></i> Guardar Evaluación';
        }
    };

})();
</script>
