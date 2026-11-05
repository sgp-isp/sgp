<?php
/**
 * Vista de Pasantes: Mesa de Ayuda, Reporte de Casos y Chat con Soporte Técnico
 */
$tickets        = $data['tickets'] ?? [];
$selectedTicket = $data['selectedTicket'] ?? null;
$mensajes       = $data['mensajes'] ?? [];
?>

<div class="pasante-soporte-wrap">
    <!-- ══ HEADER BANNER (Bento Style) ══ -->
    <div class="ps-banner">
        <div class="ps-banner-info">
            <div class="ps-icon">
                <i class="ti ti-headset"></i>
            </div>
            <div>
                <h1 class="ps-title">Mesa de Ayuda &bull; Mis Casos</h1>
                <p class="ps-subtitle">Comunícate con la División de Soporte Técnico y Telemática del ISPEB para resolver incidencias o dudas</p>
            </div>
        </div>
        <div class="ps-banner-actions">
            <button type="button" class="btn-nuevo-reporte" onclick="abrirModalReportePasante()">
                <i class="ti ti-plus"></i>
                <span>Crear Reporte</span>
            </button>
        </div>
    </div>

    <!-- ══ WORKSPACE: MIS TICKETS + CHAT DEL CASO ══ -->
    <div class="ps-workspace">
        <!-- ── COLUMNA IZQUIERDA: LISTADO DE REPORTES ── -->
        <div class="ps-list-col">
            <div class="ps-list-header">
                <span class="plh-title"><i class="ti ti-list-check"></i> Historial de Reportes</span>
                <span class="plh-count"><?= count($tickets) ?> caso(s)</span>
            </div>

            <div class="ps-tickets-scroll">
                <?php if (empty($tickets)): ?>
                <div class="ps-empty-list">
                    <div class="pel-icon"><i class="ti ti-ticket-off"></i></div>
                    <h4>No tienes casos registrados</h4>
                    <p>Si experimentas problemas con tu marcaje de asistencia, evaluaciones o acceso al sistema, puedes reportarlo de inmediato.</p>
                    <button type="button" class="btn-crear-primer-caso" onclick="abrirModalReportePasante()">
                        <i class="ti ti-plus"></i> Crear mi primer reporte
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($tickets as $t): 
                        $isSelected = ($selectedTicket && (int)$selectedTicket->id === (int)$t->id);
                        $badgeColor = match($t->estado) {
                            'Abierto'    => '#ef4444',
                            'En Proceso' => '#f59e0b',
                            'Resuelto'   => '#10b981',
                            'Cerrado'    => '#64748b',
                            default      => '#2563eb'
                        };
                    ?>
                    <div class="ps-ticket-item <?= $isSelected ? 'selected' : '' ?>" onclick="seleccionarTicketPasante(<?= (int)$t->id ?>)">
                        <div class="pti-top">
                            <span class="pti-code"><?= htmlspecialchars($t->codigo) ?></span>
                            <span class="pti-badge" style="background: <?= $badgeColor ?>; color: #fff;">
                                <?= htmlspecialchars($t->estado) ?>
                            </span>
                        </div>
                        <h4 class="pti-subject"><?= htmlspecialchars($t->asunto) ?></h4>
                        <div class="pti-bottom">
                            <span class="pti-cat"><i class="ti ti-tag"></i> <?= htmlspecialchars($t->categoria) ?></span>
                            <span class="pti-date"><i class="ti ti-clock"></i> Hace <?= $t->horas_transcurridas ?>h</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── COLUMNA DERECHA: CHAT CON SOPORTE ── -->
        <div class="ps-chat-col">
            <?php if (!$selectedTicket): ?>
            <div class="ps-no-chat-state">
                <div class="pncs-icon"><i class="ti ti-messages"></i></div>
                <h3>Ningún caso seleccionado</h3>
                <p>Selecciona un reporte de tu lista para ver la conversación y la respuesta técnica de la División de Telemática.</p>
            </div>
            <?php else: 
                $estadoColor = match($selectedTicket->estado) {
                    'Abierto'    => '#ef4444',
                    'En Proceso' => '#f59e0b',
                    'Resuelto'   => '#10b981',
                    'Cerrado'    => '#64748b',
                    default      => '#2563eb'
                };
            ?>
            <!-- Encabezado del caso -->
            <div class="ps-chat-header">
                <div>
                    <div class="pch-tags">
                        <span class="pch-code"><?= htmlspecialchars($selectedTicket->codigo) ?></span>
                        <span class="pch-badge" style="background: <?= $estadoColor ?>; color: #fff;"><?= htmlspecialchars($selectedTicket->estado) ?></span>
                        <span class="pch-cat"><?= htmlspecialchars($selectedTicket->categoria) ?></span>
                    </div>
                    <h2 class="pch-title"><?= htmlspecialchars($selectedTicket->asunto) ?></h2>
                </div>
                <div class="pch-date">
                    <span>Creado: <?= date('d/m/Y H:i', strtotime($selectedTicket->created_at)) ?></span>
                </div>
            </div>

            <!-- Contenedor del chat -->
            <div class="ps-chat-body" id="chatPasanteContainer">
                <?php if (empty($mensajes)): ?>
                    <div class="empty-chat-hint">
                        <span>No hay mensajes registrados en este caso aún.</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($mensajes as $m): 
                        $isSoporte = ($m->remitente_tipo === 'soporte');
                    ?>
                    <div class="chat-bubble-row <?= $isSoporte ? 'row-soporte' : 'row-usuario' ?>">
                        <div class="chat-bubble <?= $isSoporte ? 'bubble-soporte' : 'bubble-usuario' ?>">
                            <div class="bubble-header">
                                <span class="bubble-author">
                                    <i class="<?= $isSoporte ? 'ti ti-shield-check' : 'ti ti-user' ?>"></i>
                                    <?= htmlspecialchars($m->remitente_nombre) ?>
                                    <?php if ($isSoporte): ?>
                                    <span class="tag-tecnico">Soporte Telemática</span>
                                    <?php endif; ?>
                                </span>
                                <span class="bubble-time"><?= date('d/m H:i', strtotime($m->created_at)) ?></span>
                            </div>
                            <div class="bubble-body">
                                <?= nl2br(htmlspecialchars($m->mensaje)) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input del chat para el pasante -->
            <form class="chat-input-bar" id="formChatPasante" onsubmit="enviarMensajePasante(event, <?= (int)$selectedTicket->id ?>)">
                <textarea id="chatInputPasanteTexto" placeholder="Escribe tu mensaje o respuesta para el equipo de soporte..." rows="2" required onkeydown="handlePasanteChatKeyDown(event, <?= (int)$selectedTicket->id ?>)"></textarea>
                <div class="cib-actions">
                    <button type="submit" id="btnEnviarChatPasante" class="btn-send-chat">
                        <i class="ti ti-send"></i>
                        <span>Enviar</span>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Crear Reporte Pasante -->
<div id="modalReportePasante" class="sgp-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.65);z-index:999999;align-items:center;justify-content:center;backdrop-filter:blur(6px);padding:16px;" onclick="if(event.target===this)cerrarModalReportePasante()">
    <div class="sgp-modal-bento" style="background:#ffffff;border-radius:24px;width:100%;max-width:540px;box-shadow:0 25px 60px rgba(0,0,0,0.25);border:1px solid #e2e8f0;overflow:hidden;animation:bentoModalIn 0.3s cubic-bezier(0.16,1,0.3,1);">
        <div style="background:linear-gradient(135deg, #162660 0%, #1e3a8a 100%);padding:20px 24px;color:#ffffff;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                    <i class="ti ti-headset"></i>
                </div>
                <div>
                    <h3 style="margin:0;font-size:1.1rem;font-weight:800;color:#ffffff;">Reportar Incidencia o Caso</h3>
                    <p style="margin:2px 0 0;font-size:0.75rem;color:rgba(255,255,255,0.8);">Mesa de Ayuda &bull; Dirección de Telemática</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalReportePasante()" style="background:none;border:none;color:#ffffff;font-size:1.2rem;cursor:pointer;opacity:0.8;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <form onsubmit="guardarReportePasante(event)" style="padding:22px 24px;">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Asunto de tu Consulta o Problema</label>
                <input type="text" id="rpAsunto" required placeholder="Ej: Error al escanear QR / Problema con mis horas" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.9rem;outline:none;font-weight:600;color:#1e293b;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Categoría</label>
                    <select id="rpCategoria" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.88rem;outline:none;font-weight:600;color:#1e293b;background:#fff;">
                        <option value="Error del Sistema">Error del Sistema</option>
                        <option value="Problema de Acceso">Problema de Acceso / PIN</option>
                        <option value="Duda Operativa">Duda Operativa</option>
                        <option value="Sugerencia">Sugerencia</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Prioridad</label>
                    <select id="rpPrioridad" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.88rem;outline:none;font-weight:600;color:#1e293b;background:#fff;">
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media (Normal)</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente (Bloqueo)</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Explica lo que sucede</label>
                <textarea id="rpMensaje" required rows="4" placeholder="Describe claramente qué ocurrió para que el equipo pueda ayudarte con mayor rapidez..." style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.88rem;outline:none;font-weight:500;color:#1e293b;resize:vertical;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" onclick="cerrarModalReportePasante()" style="padding:10px 16px;border:1.5px solid #cbd5e1;border-radius:10px;background:#fff;color:#475569;font-weight:700;font-size:0.86rem;cursor:pointer;">Cancelar</button>
                <button type="submit" id="btnGuardarReportePasante" style="padding:10px 22px;border:none;border-radius:10px;background:linear-gradient(135deg,#162660,#2563eb);color:#fff;font-weight:700;font-size:0.86rem;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <i class="ti ti-send"></i> <span>Enviar Reporte</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.pasante-soporte-wrap {
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 4px 30px;
}
.ps-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #162660 0%, #1e3a8a 50%, #2563eb 100%);
    border-radius: 20px;
    padding: 24px 30px;
    color: #ffffff;
    box-shadow: 0 10px 30px rgba(22, 38, 96, 0.2);
    margin-bottom: 22px;
}
.ps-banner-info {
    display: flex;
    align-items: center;
    gap: 18px;
}
.ps-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #ffffff;
    flex-shrink: 0;
}
.ps-title {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.3px;
    color: #ffffff;
}
.ps-subtitle {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 4px 0 0;
}
.btn-nuevo-reporte {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    border-radius: 12px;
    border: none;
    background: #ffffff;
    color: #162660;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
    transition: transform 0.2s;
}
.btn-nuevo-reporte:hover { transform: translateY(-2px); }

/* Workspace */
.ps-workspace {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 20px;
    min-height: 620px;
}
@media (max-width: 900px) {
    .ps-workspace { grid-template-columns: 1fr; }
}

/* Columna izquierda: Lista */
.ps-list-col {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    padding: 18px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    height: 700px;
}
.ps-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1.5px solid #f1f5f9;
    margin-bottom: 12px;
}
.plh-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.plh-count {
    font-size: 0.75rem;
    font-weight: 700;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 8px;
    border-radius: 10px;
}
.ps-tickets-scroll {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-right: 4px;
}
.ps-tickets-scroll::-webkit-scrollbar { width: 5px; }
.ps-tickets-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }

.ps-empty-list {
    text-align: center;
    padding: 40px 16px;
    color: #64748b;
}
.pel-icon {
    font-size: 2.2rem;
    color: #94a3b8;
    margin-bottom: 10px;
}
.ps-empty-list h4 { margin: 0 0 6px; color: #1e293b; font-weight: 700; font-size: 0.95rem; }
.ps-empty-list p { font-size: 0.8rem; line-height: 1.45; margin: 0 0 16px; }
.btn-crear-primer-caso {
    padding: 8px 16px;
    border-radius: 10px;
    border: none;
    background: #162660;
    color: #fff;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
}

.ps-ticket-item {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.2s;
}
.ps-ticket-item:hover {
    border-color: #93c5fd;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.ps-ticket-item.selected {
    border-color: #2563eb;
    background: #f8fafc;
    box-shadow: 0 4px 14px rgba(37,99,235,0.12);
}
.pti-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.pti-code { font-size: 0.78rem; font-weight: 800; color: #2563eb; }
.pti-badge { font-size: 0.68rem; font-weight: 800; padding: 2px 7px; border-radius: 8px; }
.pti-subject { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0 0 8px; line-height: 1.3; }
.pti-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.72rem;
    color: #64748b;
    border-top: 1px dashed #e2e8f0;
    padding-top: 8px;
}
.pti-bottom span { display: inline-flex; align-items: center; gap: 4px; }

/* Columna derecha: Chat */
.ps-chat-col {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    height: 700px;
    overflow: hidden;
}
.ps-no-chat-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
    color: #64748b;
}
.pncs-icon {
    width: 68px;
    height: 68px;
    border-radius: 20px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
}
.ps-chat-header {
    padding: 18px 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.pch-tags {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}
.pch-code { font-weight: 800; font-size: 0.95rem; color: #162660; }
.pch-badge { font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 8px; }
.pch-cat { font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 8px; background: #e2e8f0; color: #334155; }
.pch-title { font-size: 1.15rem; font-weight: 800; color: #1e293b; margin: 0; }
.pch-date { font-size: 0.75rem; color: #94a3b8; font-weight: 600; }

.ps-chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.ps-chat-body::-webkit-scrollbar { width: 6px; }
.ps-chat-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }
</style>

<script>
function scrollChatPasanteToBottom() {
    const el = document.getElementById('chatPasanteContainer');
    if (el) el.scrollTop = el.scrollHeight;
}
document.addEventListener('DOMContentLoaded', scrollChatPasanteToBottom);

function seleccionarTicketPasante(id) {
    window.location.href = URLROOT + '/soporte?id=' + id;
}

function handlePasanteChatKeyDown(e, ticketId) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        enviarMensajePasante(e, ticketId);
    }
}

async function enviarMensajePasante(e, ticketId) {
    if (e) e.preventDefault();
    const txtEl = document.getElementById('chatInputPasanteTexto');
    const msg = txtEl.value.trim();
    if (!msg) return;

    const btn = document.getElementById('btnEnviarChatPasante');
    btn.disabled = true;

    try {
        const resp = await fetch(URLROOT + '/soporte/mensaje', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: ticketId, mensaje: msg })
        });
        const res = await resp.json();
        if (res.success) {
            txtEl.value = '';
            const container = document.getElementById('chatPasanteContainer');
            const row = document.createElement('div');
            row.className = 'chat-bubble-row row-usuario';
            row.innerHTML = `
                <div class="chat-bubble bubble-usuario">
                    <div class="bubble-header">
                        <span class="bubble-author"><i class="ti ti-user"></i> Tú</span>
                        <span class="bubble-time">${res.created_at || 'Ahora'}</span>
                    </div>
                    <div class="bubble-body">${msg.replace(/\n/g, '<br>')}</div>
                </div>
            `;
            container.appendChild(row);
            scrollChatPasanteToBottom();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo enviar el mensaje.' });
    } finally {
        btn.disabled = false;
    }
}

function abrirModalReportePasante() {
    document.getElementById('modalReportePasante').style.display = 'flex';
}
function cerrarModalReportePasante() {
    document.getElementById('modalReportePasante').style.display = 'none';
}

async function guardarReportePasante(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarReportePasante');
    btn.disabled = true;

    const data = {
        asunto: document.getElementById('rpAsunto').value.trim(),
        categoria: document.getElementById('rpCategoria').value,
        prioridad: document.getElementById('rpPrioridad').value,
        mensaje: document.getElementById('rpMensaje').value.trim()
    };

    try {
        const resp = await fetch(URLROOT + '/soporte/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await resp.json();
        if (res.success) {
            cerrarModalReportePasante();
            Swal.fire({
                icon: 'success',
                title: 'Reporte Creado',
                text: res.message,
                confirmButtonColor: '#162660'
            }).then(() => {
                window.location.href = URLROOT + '/soporte?id=' + res.ticket_id;
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo conectar con el servidor.' });
    } finally {
        btn.disabled = false;
    }
}
</script>
