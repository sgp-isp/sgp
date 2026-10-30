<?php
/**
 * Vista: Nuevo Examen — Quiz Builder
 * URL: /examenes/nuevo
 * Variables: $periodos[]
 */
$periodos = $data['periodos'] ?? [];
?>

<style>
@keyframes qb-fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }

.qb-wrap { padding-bottom:56px; animation:qb-fadeUp .4s ease both; }

/* ── Hero ────────────────────────────────────────────────── */
.qb-hero {
    background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 45%,#2563eb 100%);
    border-radius:20px; padding:30px 36px; margin-bottom:28px; color:#fff;
    position:relative; overflow:hidden; box-shadow:0 8px 32px rgba(15,23,42,.35);
    display:flex; align-items:center; gap:20px; flex-wrap:wrap;
}
.qb-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:260px; height:260px;
    background:radial-gradient(circle,rgba(255,255,255,.1) 0%,rgba(255,255,255,0) 70%);
    border-radius:50%; pointer-events:none;
}
.qb-hero-icon {
    width:56px; height:56px; border-radius:16px;
    background:rgba(255,255,255,.18); backdrop-filter:blur(10px);
    display:flex; align-items:center; justify-content:center;
    font-size:1.8rem; flex-shrink:0; border:1px solid rgba(255,255,255,.25);
    position:relative; z-index:1;
}
.qb-hero-text { position:relative; z-index:1; }
.qb-hero-title { font-size:1.75rem; font-weight:800; margin:0 0 4px; letter-spacing:-.4px; color:#fff; }
.qb-hero-sub   { font-size:.92rem; opacity:.82; margin:0; color:#fff; }

/* ── Card base ───────────────────────────────────────────── */
.qb-card {
    background:#fff; border-radius:18px; padding:26px 28px;
    box-shadow:0 2px 14px rgba(0,0,0,.06); border:1px solid rgba(0,0,0,.04);
    margin-bottom:20px;
}
.qb-card-title {
    font-size:1rem; font-weight:700; color:#1e293b;
    margin:0 0 18px; display:flex; align-items:center; gap:8px;
}
.qb-card-title i { color:#7c3aed; font-size:1.1rem; }

/* ── Form fields ─────────────────────────────────────────── */
.qb-label { font-size:.82rem; font-weight:700; color:#475569; margin-bottom:6px; display:block; }
.qb-input, .qb-select, .qb-textarea {
    width:100%; padding:11px 14px; border:2px solid #e2e8f0;
    border-radius:12px; font-size:.9rem; color:#1e293b;
    background:#f8fafc; transition:all .2s; box-sizing:border-box;
    font-family:inherit;
}
.qb-input:focus, .qb-select:focus, .qb-textarea:focus {
    outline:none; border-color:#7c3aed; background:#fff;
    box-shadow:0 0 0 4px rgba(124,58,237,.1);
}
.qb-textarea { resize:vertical; min-height:80px; }
.qb-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.qb-row.qb-row-3 { grid-template-columns:1fr 1fr 1fr; }
.qb-field { margin-bottom:16px; }

@media(max-width:640px) {
    .qb-row, .qb-row.qb-row-3 { grid-template-columns:1fr; }
    .qb-hero { padding:22px 18px; }
    .qb-hero-title { font-size:1.35rem; }
    .qb-card { padding:18px 16px; }
    .qb-preg-card { padding:16px 14px; }
    .qb-preg-meta { grid-template-columns:1fr; }
    .qb-footer { flex-direction:column; }
    .qb-btn-save { width:100%; justify-content:center; }
    .qb-opcion-row { flex-wrap:wrap; gap:6px; }
    .qb-lbl-correcta { display:none; }
}

/* ── Preguntas ───────────────────────────────────────────── */
.qb-preg-list { display:flex; flex-direction:column; gap:16px; margin-bottom:18px; }

.qb-preg-card {
    background:#faf5ff; border:2px solid #e9d5ff; border-radius:16px; padding:20px 22px;
    position:relative;
}
.qb-preg-header {
    display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;
}
.qb-preg-num {
    width:28px; height:28px; border-radius:8px;
    background:linear-gradient(135deg,#7c3aed,#a78bfa);
    color:#fff; font-weight:800; font-size:.8rem;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.qb-preg-reorder { display:flex; gap:4px; }
.qb-btn-sm {
    padding:5px 9px; border-radius:8px; border:none; cursor:pointer;
    font-size:.75rem; font-weight:700; transition:all .15s; display:inline-flex; align-items:center; gap:4px;
}
.qb-btn-reorder { background:#e9d5ff; color:#6d28d9; }
.qb-btn-reorder:hover { background:#ddd6fe; }
.qb-btn-del-preg { background:#fee2e2; color:#ef4444; margin-left:auto; }
.qb-btn-del-preg:hover { background:#fecaca; }

.qb-preg-enunc { width:100%; margin-bottom:12px; }
.qb-preg-meta { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }

/* ── Opciones ────────────────────────────────────────────── */
.qb-opciones-list { display:flex; flex-direction:column; gap:8px; margin-bottom:10px; }
.qb-opcion-row {
    display:flex; align-items:center; gap:10px;
    background:#fff; border:1.5px solid #e2e8f0; border-radius:10px; padding:8px 12px;
}
.qb-opcion-row.is-correcta { border-color:#10b981; background:#f0fdf4; }
.qb-opcion-text { flex:1; border:none; background:transparent; font-size:.88rem; color:#1e293b; outline:none; }
.qb-radio-correcta { width:17px; height:17px; accent-color:#10b981; cursor:pointer; flex-shrink:0; }
.qb-lbl-correcta { font-size:.72rem; font-weight:700; color:#059669; white-space:nowrap; }
.qb-btn-del-opc { background:none; border:none; cursor:pointer; color:#cbd5e1; font-size:1rem; padding:0 2px; transition:color .15s; flex-shrink:0; }
.qb-btn-del-opc:hover { color:#ef4444; }

.qb-btn-add-opc {
    background:none; border:2px dashed #c4b5fd; color:#7c3aed; border-radius:10px;
    padding:8px 14px; font-size:.82rem; font-weight:700; cursor:pointer; width:100%;
    transition:all .2s; display:flex; align-items:center; justify-content:center; gap:6px;
}
.qb-btn-add-opc:hover { background:#f5f3ff; border-color:#7c3aed; }

/* ── Btn Add Pregunta ────────────────────────────────────── */
.qb-btn-add-preg {
    width:100%; padding:14px; border:2px dashed #c4b5fd; border-radius:14px;
    background:none; color:#7c3aed; font-size:.9rem; font-weight:700;
    cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px;
}
.qb-btn-add-preg:hover { background:#f5f3ff; border-color:#7c3aed; }

/* ── Footer buttons ──────────────────────────────────────── */
.qb-footer {
    display:flex; justify-content:flex-end; gap:12px; flex-wrap:wrap; margin-top:8px;
}
.qb-btn-save {
    padding:13px 26px; border-radius:13px; font-size:.9rem; font-weight:700;
    border:none; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all .2s;
}
.qb-btn-draft {
    background:#f1f5f9; color:#64748b;
}
.qb-btn-draft:hover { background:#e2e8f0; }
.qb-btn-publish {
    background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff;
    box-shadow:0 4px 14px rgba(109,40,217,.3);
}
.qb-btn-publish:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(109,40,217,.4); }

/* ── Validation error ────────────────────────────────────── */
.qb-err {
    background:#fef2f2; border:1.5px solid #fca5a5; color:#991b1b;
    border-radius:12px; padding:12px 16px; font-size:.88rem; font-weight:600;
    margin-bottom:16px; display:none; align-items:center; gap:8px;
}
</style>

<div class="qb-wrap">

    <!-- Hero -->
    <div class="qb-hero">
        <div class="qb-hero-icon"><i class="ti ti-notebook"></i></div>
        <div class="qb-hero-text">
            <h1 class="qb-hero-title">Nuevo Examen</h1>
            <p class="qb-hero-sub">Crea un quiz con preguntas de opción múltiple o verdadero/falso</p>
        </div>
    </div>

    <!-- Error global -->
    <div class="qb-err" id="qb-err-global" style="display:none;">
        <i class="ti ti-alert-circle"></i>
        <span id="qb-err-msg"></span>
    </div>

    <!-- Información básica -->
    <div class="qb-card">
        <div class="qb-card-title"><i class="ti ti-info-circle"></i> Información del Examen</div>

        <div class="qb-field">
            <label class="qb-label" for="qb-titulo">Título <span style="color:#ef4444;">*</span></label>
            <input type="text" id="qb-titulo" class="qb-input" placeholder="Ej: Evaluación de Conocimientos Generales" maxlength="200">
        </div>

        <div class="qb-field">
            <label class="qb-label" for="qb-desc">Descripción (opcional)</label>
            <textarea id="qb-desc" class="qb-textarea" placeholder="Instrucciones o descripción del examen..."></textarea>
        </div>

        <div class="qb-row qb-row-3">
            <div>
                <label class="qb-label" for="qb-periodo">Período Académico</label>
                <select id="qb-periodo" class="qb-select">
                    <option value="">— Sin período —</option>
                    <?php foreach ($periodos as $p):
                        $tipo = $p->tipo ?? 'Regular';
                        $tipoLabel = $tipo === 'Corto' ? ' · Corto' : ' · Regular';
                    ?>
                    <option value="<?= (int)$p->id ?>"><?= htmlspecialchars($p->nombre ?? '') ?><?= $tipoLabel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="qb-label" for="qb-fecha-ini">Fecha inicio (opcional)</label>
                <input type="date" id="qb-fecha-ini" class="qb-input">
            </div>
            <div>
                <label class="qb-label" for="qb-fecha-fin">Fecha fin (opcional)</label>
                <input type="date" id="qb-fecha-fin" class="qb-input">
            </div>
        </div>

    </div>

    <!-- Preguntas -->
    <div class="qb-card">
        <div class="qb-card-title"><i class="ti ti-help-circle"></i> Preguntas</div>

        <div class="qb-preg-list" id="qb-preg-list">
            <!-- Preguntas se renderizan aquí por JS -->
        </div>

        <button type="button" class="qb-btn-add-preg" onclick="agregarPregunta()">
            <i class="ti ti-plus"></i> Agregar pregunta
        </button>
    </div>

    <!-- Footer -->
    <div class="qb-footer">
        <button type="button" class="qb-btn-save qb-btn-draft" onclick="guardar(false)">
            <i class="ti ti-device-floppy"></i> Guardar como borrador
        </button>
        <button type="button" class="qb-btn-save qb-btn-publish" onclick="guardar(true)">
            <i class="ti ti-rocket"></i> Publicar examen
        </button>
    </div>

</div>

<script>
// URLROOT ya disponible desde main_layout.php

// Estado del quiz builder — [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta sin reload completo.
var preguntas = [];

// ── Agregar pregunta ────────────────────────────────────────────────
function agregarPregunta() {
    preguntas.push({
        enunciado: '',
        tipo: 'opcion_multiple',
        puntos: 1,
        opciones: [
            {texto: '', es_correcta: false},
            {texto: '', es_correcta: false},
        ]
    });
    renderPreguntas();
    // Scroll al final
    setTimeout(() => {
        const list = document.getElementById('qb-preg-list');
        list.lastElementChild?.scrollIntoView({behavior:'smooth', block:'center'});
    }, 80);
}

// ── Agregar opción a una pregunta ───────────────────────────────────
function agregarOpcion(pi) {
    preguntas[pi].opciones.push({texto: '', es_correcta: false});
    renderPreguntas();
    // Focus en el nuevo input
    setTimeout(() => {
        const inputs = document.querySelectorAll(`.qb-opc-text-${pi}`);
        inputs[inputs.length - 1]?.focus();
    }, 60);
}

// ── Eliminar opción ─────────────────────────────────────────────────
function eliminarOpcion(pi, oi) {
    if (preguntas[pi].opciones.length <= 2) {
        alert('Cada pregunta debe tener al menos 2 opciones.');
        return;
    }
    // Si era la correcta, resetear
    if (preguntas[pi].opciones[oi].es_correcta) {
        preguntas[pi].opciones.forEach(o => o.es_correcta = false);
    }
    preguntas[pi].opciones.splice(oi, 1);
    renderPreguntas();
}

// ── Eliminar pregunta ───────────────────────────────────────────────
function eliminarPregunta(pi) {
    preguntas.splice(pi, 1);
    renderPreguntas();
}

// ── Reordenar ───────────────────────────────────────────────────────
function reorderUp(pi) {
    if (pi === 0) return;
    [preguntas[pi-1], preguntas[pi]] = [preguntas[pi], preguntas[pi-1]];
    renderPreguntas();
}
function reorderDown(pi) {
    if (pi >= preguntas.length - 1) return;
    [preguntas[pi], preguntas[pi+1]] = [preguntas[pi+1], preguntas[pi]];
    renderPreguntas();
}

// ── Cambiar tipo (opcion_multiple / verdadero_falso) ────────────────
function cambiarTipo(pi) {
    const sel = document.getElementById(`qb-tipo-${pi}`);
    preguntas[pi].tipo = sel.value;

    if (sel.value === 'verdadero_falso') {
        preguntas[pi].opciones = [
            {texto: 'Verdadero', es_correcta: false},
            {texto: 'Falso',     es_correcta: false},
        ];
    } else if (preguntas[pi].opciones.length < 2) {
        preguntas[pi].opciones = [
            {texto: '', es_correcta: false},
            {texto: '', es_correcta: false},
        ];
    }
    renderPreguntas();
}

// ── Seleccionar opción correcta ─────────────────────────────────────
function selCorrect(pi, oi) {
    preguntas[pi].opciones.forEach((o, i) => o.es_correcta = (i === oi));
    // Actualizar clases sin re-render completo (UX más suave)
    preguntas[pi].opciones.forEach((o, i) => {
        const row = document.getElementById(`qb-opc-row-${pi}-${i}`);
        if (row) row.className = 'qb-opcion-row' + (o.es_correcta ? ' is-correcta' : '');
    });
}

// ── Sincronizar texto de opción ─────────────────────────────────────
function syncOpcion(pi, oi, val) {
    preguntas[pi].opciones[oi].texto = val;
}

// ── Sincronizar campo de pregunta ───────────────────────────────────
function syncPregunta(pi, field, val) {
    preguntas[pi][field] = val;
}

// ── Render ──────────────────────────────────────────────────────────
function renderPreguntas() {
    const list = document.getElementById('qb-preg-list');
    if (!list) return;

    list.innerHTML = preguntas.map((preg, pi) => {
        const esVF = preg.tipo === 'verdadero_falso';

        const opcionesHtml = preg.opciones.map((op, oi) => {
            const correctaClass = op.es_correcta ? ' is-correcta' : '';
            const canDelete = !esVF && preg.opciones.length > 2;
            return `
            <div class="qb-opcion-row${correctaClass}" id="qb-opc-row-${pi}-${oi}">
                <input type="radio"
                    class="qb-radio-correcta"
                    name="qb-correct-${pi}"
                    ${op.es_correcta ? 'checked' : ''}
                    title="Marcar como correcta"
                    onchange="selCorrect(${pi},${oi})"
                >
                <input type="text"
                    class="qb-opcion-text qb-opc-text-${pi}"
                    placeholder="Opción ${oi+1}..."
                    value="${_esc(op.texto)}"
                    ${esVF ? 'readonly' : ''}
                    oninput="syncOpcion(${pi},${oi},this.value)"
                    style="${esVF ? 'color:#64748b;cursor:default;' : ''}"
                >
                <span class="qb-lbl-correcta" style="${op.es_correcta ? '' : 'visibility:hidden;'}">Correcta</span>
                <button type="button" class="qb-btn-del-opc" title="Eliminar opción"
                    onclick="eliminarOpcion(${pi},${oi})"
                    style="${!canDelete ? 'visibility:hidden;' : ''}">
                    <i class="ti ti-x"></i>
                </button>
            </div>`;
        }).join('');

        return `
        <div class="qb-preg-card" id="qb-preg-card-${pi}">
            <div class="qb-preg-header">
                <div class="qb-preg-num">${pi+1}</div>
                <div class="qb-preg-reorder">
                    <button type="button" class="qb-btn-sm qb-btn-reorder" onclick="reorderUp(${pi})" title="Subir" ${pi===0?'disabled':''}>
                        <i class="ti ti-arrow-up"></i>
                    </button>
                    <button type="button" class="qb-btn-sm qb-btn-reorder" onclick="reorderDown(${pi})" title="Bajar" ${pi===preguntas.length-1?'disabled':''}>
                        <i class="ti ti-arrow-down"></i>
                    </button>
                </div>
                <button type="button" class="qb-btn-sm qb-btn-del-preg" onclick="eliminarPregunta(${pi})">
                    <i class="ti ti-trash"></i> Eliminar
                </button>
            </div>

            <div class="qb-field qb-preg-enunc">
                <label class="qb-label">Enunciado <span style="color:#ef4444;">*</span></label>
                <textarea class="qb-textarea" rows="2" placeholder="Escribe la pregunta aquí..."
                    oninput="syncPregunta(${pi},'enunciado',this.value)">${_esc(preg.enunciado)}</textarea>
            </div>

            <div class="qb-preg-meta">
                <div>
                    <label class="qb-label">Tipo</label>
                    <select class="qb-select" id="qb-tipo-${pi}" onchange="cambiarTipo(${pi})">
                        <option value="opcion_multiple" ${preg.tipo==='opcion_multiple'?'selected':''}>Opción múltiple</option>
                        <option value="verdadero_falso" ${preg.tipo==='verdadero_falso'?'selected':''}>Verdadero / Falso</option>
                    </select>
                </div>
                <div>
                    <label class="qb-label">Puntos</label>
                    <input type="number" class="qb-input" value="${preg.puntos}" min="1" max="100"
                        onchange="syncPregunta(${pi},'puntos',parseInt(this.value)||1)">
                </div>
            </div>

            <div>
                <label class="qb-label">Opciones de respuesta</label>
                <div class="qb-opciones-list">${opcionesHtml}</div>
                ${esVF ? '' : `
                <button type="button" class="qb-btn-add-opc" onclick="agregarOpcion(${pi})">
                    <i class="ti ti-plus"></i> Agregar opción
                </button>`}
            </div>
        </div>`;
    }).join('');
}

// ── Escape HTML ─────────────────────────────────────────────────────
function _esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Construir payload ───────────────────────────────────────────────
function buildPayload(publicar) {
    return {
        titulo:              document.getElementById('qb-titulo').value.trim(),
        descripcion:         document.getElementById('qb-desc').value.trim(),
        periodo_id:          document.getElementById('qb-periodo').value || null,
        fecha_inicio:        document.getElementById('qb-fecha-ini').value || null,
        fecha_fin:           document.getElementById('qb-fecha-fin').value || null,
        intentos_permitidos: 1, // [FIX-N-11] cada examen admite un único intento (examen_intentos.UNIQUE(examen_id, pasante_id))
        publicar:            !!publicar,
        preguntas:           preguntas.map(p => ({
            enunciado: p.enunciado,
            tipo:      p.tipo,
            puntos:    parseInt(p.puntos) || 1,
            opciones:  p.opciones.map(o => ({texto: o.texto, es_correcta: o.es_correcta}))
        }))
    };
}

// ── Mostrar error ───────────────────────────────────────────────────
function showErr(msg) {
    const el = document.getElementById('qb-err-global');
    const em = document.getElementById('qb-err-msg');
    em.textContent = msg;
    el.style.display = 'flex';
    el.scrollIntoView({behavior:'smooth', block:'center'});
}
function hideErr() { document.getElementById('qb-err-global').style.display = 'none'; }

// ── Validar cliente ─────────────────────────────────────────────────
function validarPayload(payload) {
    if (!payload.titulo) return 'El título del examen es obligatorio.';
    if (payload.preguntas.length === 0) return 'Agrega al menos una pregunta.';
    for (let i = 0; i < payload.preguntas.length; i++) {
        const p = payload.preguntas[i];
        const n = i + 1;
        if (!p.enunciado.trim()) return `La pregunta #${n} no tiene enunciado.`;
        if (p.opciones.length < 2) return `La pregunta #${n} debe tener al menos 2 opciones.`;
        const correctas = p.opciones.filter(o => o.es_correcta);
        if (correctas.length !== 1) return `La pregunta #${n} debe tener exactamente 1 opción correcta marcada.`;
        for (let oi = 0; oi < p.opciones.length; oi++) {
            if (!p.opciones[oi].texto.trim()) return `La opción #${oi+1} de la pregunta #${n} está vacía.`;
        }
    }
    return null;
}

// ── Guardar ─────────────────────────────────────────────────────────
async function guardar(publicar) {
    hideErr();

    // Sincronizar texto de preguntas desde DOM
    preguntas.forEach((preg, pi) => {
        const ta = document.querySelector(`#qb-preg-card-${pi} .qb-textarea`);
        if (ta) preg.enunciado = ta.value.trim();
        const pts = document.querySelector(`#qb-preg-card-${pi} input[type="number"]`);
        if (pts) preg.puntos = parseInt(pts.value) || 1;
    });

    const payload = buildPayload(publicar);
    const err = validarPayload(payload);
    if (err) { showErr(err); return; }

    // Deshabilitar botones
    document.querySelectorAll('.qb-btn-save').forEach(b => { b.disabled = true; b.style.opacity = '.6'; });

    try {
        const csrfMetaGuardar = document.querySelector('meta[name="csrf-token"]');
        const res  = await fetch(URLROOT + '/examenes/guardar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaGuardar ? csrfMetaGuardar.getAttribute('content') : ''},
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        } else {
            showErr(data.message || 'Error desconocido al guardar.');
            document.querySelectorAll('.qb-btn-save').forEach(b => { b.disabled = false; b.style.opacity = '1'; });
        }
    } catch(e) {
        showErr('Error de conexión. Por favor, inténtalo de nuevo.');
        document.querySelectorAll('.qb-btn-save').forEach(b => { b.disabled = false; b.style.opacity = '1'; });
    }
}

// Init: una pregunta por defecto
agregarPregunta();
</script>
