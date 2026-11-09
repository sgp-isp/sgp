<?php
/**
 * Vista de Pasantes: Mesa de Ayuda, Reporte de Casos y Chat con Soporte Técnico
 */
$tickets        = $data['tickets'] ?? [];
$selectedTicket = $data['selectedTicket'] ?? null;
$mensajes       = $data['mensajes'] ?? [];

// KPIs del pasante
$pasTotal     = count($tickets);
$pasAbiertos  = count(array_filter($tickets, fn($t) => $t->estado === 'Abierto'));
$pasEnProceso = count(array_filter($tickets, fn($t) => $t->estado === 'En Proceso'));
$pasResueltos = count(array_filter($tickets, fn($t) => in_array($t->estado, ['Resuelto', 'Cerrado'])));
?>

<div class="pasante-soporte-wrap">
    <!-- ══ HEADER BANNER (Estandarizado SGP) ══ -->
    <div class="pasantes-banner soporte-banner" style="position:relative;overflow:hidden;background:linear-gradient(135deg, #162660 0%, #1e3a8a 50%, #2563eb 100%);border-radius:20px;padding:26px 32px;color:#ffffff;box-shadow:0 10px 30px rgba(22, 38, 96, 0.2);margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;box-sizing:border-box;">
        <div style="position:absolute;top:-30px;right:-30px;width:180px;height:180px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
        <div class="banner-main-group" style="display:flex;align-items:center;gap:18px;z-index:1;min-width:0;">
            <div class="banner-icon-badge" style="width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,0.16);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#ffffff;flex-shrink:0;">
                <i class="ti ti-headset"></i>
            </div>
            <div class="banner-text-block" style="min-width:0;">
                <h1 class="banner-title" style="font-size:1.6rem;font-weight:800;margin:0;letter-spacing:-0.3px;color:#ffffff;line-height:1.2;">Mesa de Ayuda &bull; Mis Casos</h1>
                <p class="banner-subtitle" style="font-size:0.86rem;color:rgba(255,255,255,0.85);margin:5px 0 0;display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
                    <span>Atención técnica y soporte directo con la Dirección de Telemática</span>
                    <span class="banner-chip-badge" style="display:inline-block;background:rgba(255,255,255,0.18);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.25);border-radius:50px;padding:2px 12px;color:white;font-weight:700;font-size:0.78rem;white-space:nowrap;">
                        <?= $pasTotal ?> <?= $pasTotal === 1 ? 'reporte' : 'reportes' ?>
                    </span>
                </p>
            </div>
        </div>
        <div class="pasantes-banner-actions" style="z-index:1;flex-shrink:0;">
            <button type="button" class="btn-nuevo-reporte" onclick="abrirModalReportePasante()">
                <i class="ti ti-plus"></i>
                <span>Crear Reporte</span>
            </button>
        </div>
    </div>

    <!-- ══ KPI CARDS ESTANDARIZADAS ══ -->
    <div class="sgp-kpi-grid soporte-kpis" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px;">
        <div class="asist-kpi-card c-blue">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">Mis Reportes</div>
                    <div class="asist-kpi-val" style="color:#2563eb;"><?= $pasTotal ?></div>
                    <div class="asist-kpi-sub">total de solicitudes enviadas</div>
                </div>
                <div class="asist-kpi-icon" style="background:#eff6ff;">
                    <i class="ti ti-ticket" style="color:#2563eb;"></i>
                </div>
            </div>
        </div>

        <div class="asist-kpi-card c-red">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">Abiertos</div>
                    <div class="asist-kpi-val" style="color:#dc2626;"><?= $pasAbiertos ?></div>
                    <div class="asist-kpi-sub">esperando atención técnica</div>
                </div>
                <div class="asist-kpi-icon" style="background:#fef2f2;">
                    <i class="ti ti-clock" style="color:#dc2626;"></i>
                </div>
            </div>
        </div>

        <div class="asist-kpi-card c-amber">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">En Atención</div>
                    <div class="asist-kpi-val" style="color:#d97706;"><?= $pasEnProceso ?></div>
                    <div class="asist-kpi-sub">en proceso de diagnóstico</div>
                </div>
                <div class="asist-kpi-icon" style="background:#fffbeb;">
                    <i class="ti ti-loader" style="color:#d97706;"></i>
                </div>
            </div>
        </div>

        <div class="asist-kpi-card c-green">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">Resueltos</div>
                    <div class="asist-kpi-val" style="color:#059669;"><?= $pasResueltos ?></div>
                    <div class="asist-kpi-sub">atendidos satisfactoriamente</div>
                </div>
                <div class="asist-kpi-icon" style="background:#ecfdf5;">
                    <i class="ti ti-circle-check" style="color:#059669;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ WORKSPACE: MIS TICKETS + CHAT DEL CASO ══ -->
    <div class="ps-workspace <?= (!empty($selectedTicket) && (isset($_GET['id']) || isset($_GET['ticket_id']))) ? 'has-selected-ticket' : 'no-selected-ticket' ?>" id="pasanteWorkspace">
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
                        <div class="pti-bottom" style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                            <span class="pti-cat" style="font-size:0.72rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><i class="ti ti-tag"></i> <?= htmlspecialchars($t->categoria) ?></span>
                            <button type="button" class="btn-ver-chat-mini" onclick="event.stopPropagation(); seleccionarTicketPasante(<?= (int)$t->id ?>)" style="padding:4px 10px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:0.72rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;cursor:pointer;flex-shrink:0;">
                                <i class="ti ti-messages"></i> Ver Chat
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── COLUMNA DERECHA: CHAT CON SOPORTE (STEAM / MESSENGER UI) ── -->
        <div class="ps-chat-col steam-chat-panel" id="chatDetailPasanteSection">
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
            <!-- Encabezado del Chat estilo Steam / Messenger -->
            <div class="messenger-header">
                <div class="mh-left">
                    <button type="button" class="btn-back-mobile" onclick="volverAListaPasante()" title="Volver a mis reportes">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                    <div class="mh-avatar-wrap">
                        <div class="mh-avatar mh-avatar-tech">
                            <i class="ti ti-headset"></i>
                        </div>
                        <span class="online-status-dot" title="Equipo de soporte en línea"></span>
                    </div>
                    <div class="mh-info">
                        <div class="mh-name-row">
                            <h3 class="mh-name">Soporte Técnico &bull; División de Telemática</h3>
                            <span class="mh-badge-code"><?= htmlspecialchars($selectedTicket->codigo) ?></span>
                            <span class="mh-status-pill status-pill-<?= strtolower(str_replace(' ', '-', $selectedTicket->estado)) ?>" id="headerPasanteEstadoPill">
                                <?= htmlspecialchars($selectedTicket->estado) ?>
                            </span>
                        </div>
                        <div class="mh-meta-line">
                            <span class="mh-cat-pill"><?= htmlspecialchars($selectedTicket->categoria) ?></span>
                            <span class="mh-prio-pill prio-<?= strtolower($selectedTicket->prioridad) ?>">
                                Prioridad: <?= htmlspecialchars($selectedTicket->prioridad) ?>
                            </span>
                            <span class="mh-dot">&bull;</span>
                            <span>Atención oficial ISPEB</span>
                        </div>
                    </div>
                </div>

                <div class="mh-right">
                    <span class="badge-status-live" title="Chat sincronizado en vivo">
                        <span class="pulse-dot"></span> En Vivo
                    </span>
                </div>
            </div>

            <!-- Asunto del caso -->
            <div class="chat-subject-bar">
                <i class="ti ti-info-circle"></i>
                <div class="csb-content">
                    <span class="csb-label">Tu reporte:</span>
                    <strong class="csb-subject"><?= htmlspecialchars($selectedTicket->asunto) ?></strong>
                    <span class="csb-time">&bull; Creado <?= date('d/m/Y h:i A', strtotime($selectedTicket->created_at)) ?></span>
                </div>
            </div>

            <!-- Contenedor del chat / Stream de Mensajes -->
            <div class="chat-messages-container" id="chatPasanteContainer">
                <div class="chat-date-separator">
                    <span>Inicio de la conversación con Soporte</span>
                </div>

                <?php if (empty($mensajes)): ?>
                    <div class="empty-chat-hint" id="emptyChatHint">
                        <i class="ti ti-message-dots"></i>
                        <span>No hay mensajes registrados en este caso aún. Escribe tu mensaje abajo.</span>
                    </div>
                <?php else: ?>
                    <?php 
                    $lastId = 0;
                    foreach ($mensajes as $m): 
                        $isSoporte = ($m->remitente_tipo === 'soporte');
                        $isMe = !$isSoporte; // Para el pasante, "usuario" es ME
                        $lastId = (int)$m->id;
                    ?>
                    <div class="chat-msg-row <?= $isMe ? 'msg-me' : 'msg-other' ?>" data-msg-id="<?= (int)$m->id ?>">
                        <?php if (!$isMe): ?>
                        <div class="msg-avatar-col" title="Soporte Técnico">
                            <div class="msg-avatar msg-avatar-tech">
                                <i class="ti ti-headset"></i>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="msg-bubble-wrap">
                            <div class="msg-meta-line">
                                <span class="msg-author-name">
                                    <?= htmlspecialchars($m->remitente_nombre) ?>
                                    <?php if ($isSoporte): ?>
                                    <span class="badge-role-tech"><i class="ti ti-shield-check"></i> Soporte Oficial</span>
                                    <?php else: ?>
                                    <span class="badge-role-user"><i class="ti ti-school"></i> Tú (Pasante)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="msg-time"><?= date('d/m H:i', strtotime($m->created_at)) ?></span>
                            </div>
                            <div class="msg-bubble <?= $isMe ? 'bubble-me' : 'bubble-other' ?>">
                                <div class="msg-text"><?= nl2br(htmlspecialchars($m->mensaje)) ?></div>
                                <div class="msg-check-status">
                                    <span class="msg-clock"><?= date('h:i A', strtotime($m->created_at)) ?></span>
                                    <?php if ($isMe): ?>
                                    <i class="ti ti-checks msg-checks" title="Enviado"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($isMe): ?>
                        <div class="msg-avatar-col msg-avatar-right" title="Tú">
                            <div class="msg-avatar msg-avatar-user">
                                <?= strtoupper(substr(Session::get('user_name') ?? 'P', 0, 1)) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Respuestas Rápidas estilo Steam / Facebook -->
            <div class="chat-quick-replies">
                <span class="cqr-label"><i class="ti ti-sparkles"></i> Respuestas Rápidas:</span>
                <div class="cqr-chips">
                    <button type="button" class="cqr-btn" onclick="insertarPlantillaPasante('Ya verifiqué en el sistema y me funcionó correctamente, muchas gracias.')">
                        👍 Ya verifiqué y funciona
                    </button>
                    <button type="button" class="cqr-btn" onclick="insertarPlantillaPasante('Intenté realizar la acción pero aún me sigue arrojando el mismo error.')">
                        ⚠️ Aún persiste el problema
                    </button>
                    <button type="button" class="cqr-btn" onclick="insertarPlantillaPasante('Muchas gracias por la atención y por solventar el caso.')">
                        🙏 Muchas gracias
                    </button>
                </div>
            </div>

            <!-- Input del chat para el pasante -->
            <form class="chat-input-bar messenger-input-bar" id="formChatPasante" onsubmit="enviarMensajePasante(event, <?= (int)$selectedTicket->id ?>)">
                <div class="cib-input-wrap">
                    <textarea id="chatInputPasanteTexto" 
                              placeholder="Escribe tu mensaje para el equipo de soporte... (Enter para enviar, Shift+Enter para salto de línea)" 
                              rows="1" 
                              required 
                              onkeydown="handlePasanteChatKeyDown(event, <?= (int)$selectedTicket->id ?>)"
                              oninput="autoExpandChatInputPasante(this)"></textarea>
                </div>
                <div class="cib-actions">
                    <button type="submit" id="btnEnviarChatPasante" class="btn-send-chat" title="Enviar mensaje">
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
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0 4px 30px;
    box-sizing: border-box;
}
.soporte-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #162660 0%, #1e3a8a 50%, #2563eb 100%);
    border-radius: 20px;
    padding: 26px 32px;
    color: #ffffff;
    box-shadow: 0 10px 30px rgba(22, 38, 96, 0.2);
    margin-bottom: 22px;
}
.btn-nuevo-reporte {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 22px;
    border-radius: 12px;
    border: none;
    background: #ffffff;
    color: #162660;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-nuevo-reporte:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.2);
}

/* ── KPIs Estandarizadas (Estilo Asistencias/Inicio SGP) ─────────────── */
.soporte-kpis {
    margin-bottom: 22px;
}
.asist-kpi-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 20px 22px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, .04);
    border: 1px solid #e2e8f0;
    position: relative;
    overflow: hidden;
    transition: all .25s ease;
}
.asist-kpi-card::after {
    content: '';
    position: absolute;
    bottom: -20px;
    right: -20px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    opacity: .07;
    pointer-events: none;
}
.asist-kpi-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.asist-kpi-label { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
.asist-kpi-val { font-size: 2.2rem; font-weight: 900; line-height: 1.1; margin-top: 4px; }
.asist-kpi-sub { font-size: .74rem; color: #94a3b8; font-weight: 500; margin-top: 3px; }
.asist-kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }

.asist-kpi-card.c-blue   { border-top: 3.5px solid #2563eb; } .asist-kpi-card.c-blue::after   { background: #2563eb; }
.asist-kpi-card.c-green  { border-top: 3.5px solid #059669; } .asist-kpi-card.c-green::after  { background: #059669; }
.asist-kpi-card.c-amber  { border-top: 3.5px solid #d97706; } .asist-kpi-card.c-amber::after  { background: #d97706; }
.asist-kpi-card.c-red    { border-top: 3.5px solid #dc2626; } .asist-kpi-card.c-red::after    { background: #dc2626; }

.asist-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, .08);
}

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

/* Columna derecha: Chat (Estilo Steam / Facebook Messenger) */
.ps-chat-col {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    height: 720px;
    overflow: hidden;
    position: relative;
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

/* ── Messenger / Steam Header ───────────────────────────────────── */
.messenger-header {
    padding: 14px 20px;
    background: #ffffff;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
}
.mh-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.btn-back-mobile {
    display: none;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    align-items: center;
    justify-content: center;
    color: #1e293b;
    font-size: 1.1rem;
    cursor: pointer;
    flex-shrink: 0;
}
.mh-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
.mh-avatar {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #162660 0%, #2563eb 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 800;
    box-shadow: 0 4px 10px rgba(37,99,235,0.2);
}
.mh-avatar-tech {
    background: linear-gradient(135deg, #162660, #2563eb);
    font-size: 1.3rem;
}
.online-status-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #10b981;
    border: 2px solid #ffffff;
    box-shadow: 0 0 6px rgba(16,185,129,0.8);
    animation: statusPulse 2s infinite ease-in-out;
}
@keyframes statusPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.15); opacity: 0.85; }
}
.mh-info {
    min-width: 0;
}
.mh-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.mh-name {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    line-height: 1.2;
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mh-badge-code {
    font-size: 0.72rem;
    font-weight: 800;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 7px;
    border-radius: 6px;
    border: 1px solid #bfdbfe;
}
.mh-status-pill {
    font-size: 0.68rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.status-pill-abierto     { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
.status-pill-en-proceso  { background: #fef3c7; color: #d97706; border: 1px solid #fcd34d; }
.status-pill-resuelto    { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.status-pill-cerrado     { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

.mh-meta-line {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 0.74rem;
    color: #64748b;
    margin-top: 3px;
}
.mh-cat-pill {
    font-weight: 700;
    color: #334155;
    background: #f1f5f9;
    padding: 1px 6px;
    border-radius: 6px;
}
.mh-prio-pill {
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 6px;
}
.prio-urgente { background: #fee2e2; color: #b91c1c; }
.prio-alta    { background: #ffedd5; color: #c2410c; }
.prio-media   { background: #fef3c7; color: #b45309; }
.prio-baja    { background: #f1f5f9; color: #475569; }
.mh-dot { color: #cbd5e1; }

.badge-status-live {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.74rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #2563eb;
    animation: livePulse 1.5s infinite;
}
@keyframes livePulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37,99,235,0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(37,99,235,0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37,99,235,0); }
}

/* Chat Subject Bar */
.chat-subject-bar {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 9px 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    color: #475569;
    flex-shrink: 0;
}
.chat-subject-bar i {
    color: #2563eb;
    font-size: 1rem;
    flex-shrink: 0;
}
.csb-content {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    min-width: 0;
}
.csb-label { font-weight: 600; color: #64748b; }
.csb-subject { color: #0f172a; font-weight: 700; }
.csb-time { font-size: 0.72rem; color: #94a3b8; }

/* ── Stream de Mensajes (Steam / Facebook Messenger) ──────────────── */
.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px 22px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.chat-messages-container::-webkit-scrollbar { width: 6px; }
.chat-messages-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }

.chat-date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 4px 0 10px;
}
.chat-date-separator span {
    background: #e2e8f0;
    color: #475569;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 3px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.chat-msg-row {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    width: 100%;
    animation: msgFadeIn 0.25s ease-out;
}
@keyframes msgFadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}
.chat-msg-row.msg-me {
    justify-content: flex-end;
}
.chat-msg-row.msg-other {
    justify-content: flex-start;
}

.msg-avatar-col {
    flex-shrink: 0;
    margin-bottom: 2px;
}
.msg-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 800;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.msg-avatar-user {
    background: linear-gradient(135deg, #162660, #2563eb);
    color: #ffffff;
}
.msg-avatar-tech {
    background: linear-gradient(135deg, #0f172a, #334155);
    color: #ffffff;
    font-size: 1rem;
}

.msg-bubble-wrap {
    display: flex;
    flex-direction: column;
    max-width: 72%;
}
.msg-me .msg-bubble-wrap {
    align-items: flex-end;
}
.msg-other .msg-bubble-wrap {
    align-items: flex-start;
}

.msg-meta-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    font-size: 0.72rem;
}
.msg-author-name {
    font-weight: 800;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.badge-role-tech {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 1px 6px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 800;
}
.badge-role-user {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 1px 6px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
}
.msg-time {
    color: #94a3b8;
    font-size: 0.68rem;
    font-weight: 600;
}

/* Bubbles */
.msg-bubble {
    padding: 12px 16px;
    word-break: break-word;
    font-size: 0.88rem;
    line-height: 1.45;
    position: relative;
}
/* Bubble Propia (Pasante = Azul Messenger) */
.bubble-me {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
    color: #ffffff;
    border-radius: 18px 18px 4px 18px;
    box-shadow: 0 4px 14px rgba(37,99,235,0.22);
}
.bubble-me .msg-text {
    color: #ffffff;
}
/* Bubble Interlocutor (Soporte = Blanco nítido con borde) */
.bubble-other {
    background: #ffffff;
    color: #1e293b;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px 18px 18px 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.msg-check-status {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    font-size: 0.68rem;
    margin-top: 5px;
}
.bubble-me .msg-check-status {
    color: rgba(255,255,255,0.85);
}
.bubble-other .msg-check-status {
    color: #94a3b8;
}
.msg-checks {
    font-size: 0.85rem;
    color: #93c5fd;
}

/* ── Canned Quick Replies (Respuestas Rápidas Steam) ───────────────── */
.chat-quick-replies {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    flex-shrink: 0;
}
.chat-quick-replies::-webkit-scrollbar { height: 4px; }
.chat-quick-replies::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.cqr-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748b;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.cqr-chips {
    display: flex;
    align-items: center;
    gap: 6px;
}
.cqr-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 20px;
    padding: 4px 11px;
    font-size: 0.74rem;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.cqr-btn:hover {
    background: #eff6ff;
    border-color: #2563eb;
    color: #1d4ed8;
    transform: translateY(-1px);
}

/* ── Barra de Input estilo Messenger / Steam ──────────────────────── */
.messenger-input-bar {
    padding: 12px 18px;
    background: #ffffff;
    border-top: 1.5px solid #f1f5f9;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-shrink: 0;
}
.cib-input-wrap {
    flex: 1;
}
.messenger-input-bar textarea {
    width: 100%;
    border: 1.5px solid #cbd5e1;
    border-radius: 20px;
    padding: 10px 16px;
    font-size: 0.88rem;
    font-weight: 500;
    outline: none;
    color: #1e293b;
    resize: none;
    line-height: 1.4;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #f8fafc;
    box-sizing: border-box;
}
.messenger-input-bar textarea:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}
.btn-send-chat {
    padding: 10px 18px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #162660, #2563eb);
    color: #ffffff;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(37,99,235,0.25);
    transition: transform 0.2s;
    flex-shrink: 0;
    height: 42px;
}
.btn-send-chat:hover {
    transform: translateY(-1px);
}

/* ── Overrides Responsive Globales SGP ────────────────────────────── */
@media (max-width: 1366px) and (min-width: 1025px) {
    .soporte-kpis {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (min-width: 769px) and (max-width: 1024px) {
    /* TABLET: Mantener siempre dos columnas lado a lado sin colapsar */
    .soporte-kpis {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px !important;
    }
    .ps-workspace {
        grid-template-columns: 310px 1fr !important;
        gap: 14px !important;
    }
    .ps-list-col {
        height: 680px !important;
        padding: 14px !important;
    }
    .ps-chat-col {
        height: 680px !important;
    }
    .banner-title {
        font-size: 1.4rem !important;
    }
}
@media (max-width: 768px) {
    .pasante-soporte-wrap {
        padding: 0 10px 24px !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .soporte-banner {
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 20px 18px !important;
        gap: 16px !important;
        border-radius: 16px !important;
    }
    .banner-main-group {
        width: 100% !important;
        flex-direction: row !important;
        align-items: flex-start !important;
        gap: 14px !important;
    }
    .banner-icon-badge {
        width: 44px !important;
        height: 44px !important;
        font-size: 1.4rem !important;
        border-radius: 12px !important;
    }
    .banner-title {
        font-size: 1.3rem !important;
        line-height: 1.25 !important;
    }
    .banner-subtitle {
        font-size: 0.8rem !important;
        line-height: 1.4 !important;
    }
    .pasantes-banner-actions {
        width: 100% !important;
    }
    .pasantes-banner-actions button {
        width: 100% !important;
        justify-content: center !important;
    }

    /* MÓVIL: Modo WhatsApp / Steam Chat — Transición fluida lista vs chat */
    .ps-workspace {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        gap: 0 !important;
    }
    .ps-workspace.no-selected-ticket .ps-list-col {
        display: flex !important;
        width: 100% !important;
        max-height: none !important;
        height: auto !important;
        min-height: 480px;
        padding: 14px 12px !important;
        box-sizing: border-box !important;
    }
    .ps-workspace.no-selected-ticket .ps-chat-col {
        display: none !important;
    }

    .ps-workspace.has-selected-ticket .ps-list-col {
        display: none !important;
    }
    .ps-workspace.has-selected-ticket .ps-chat-col {
        display: flex !important;
        width: 100% !important;
        height: calc(100vh - 180px) !important;
        min-height: 520px !important;
    }

    .ps-ticket-item {
        width: 100% !important;
        box-sizing: border-box !important;
        margin: 0 0 10px 0 !important;
    }

    .btn-back-mobile {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
    }
    .msg-bubble-wrap {
        max-width: 88% !important;
    }
    .soporte-kpis {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    .asist-kpi-card {
        padding: 14px 12px !important;
        border-radius: 16px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .asist-kpi-val  { font-size: 1.7rem !important; }
    .asist-kpi-icon { width: 38px !important; height: 38px !important; font-size: 1.15rem !important; }
}
@media (max-width: 480px) {
    .soporte-kpis {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
    }
    .asist-kpi-card { padding: 12px 10px !important; }
    .asist-kpi-val  { font-size: 1.5rem !important; }
    .asist-kpi-label { font-size: 0.68rem !important; }
    .asist-kpi-sub { display: none; }
    .banner-title { font-size: 1.18rem !important; }
    .messenger-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
}
</style>

<script>
// Estado del chat en cliente (Pasante)
const PASANTE_TICKET_ID = <?= $selectedTicket ? (int)$selectedTicket->id : 0 ?>;
let lastPasanteMsgId = <?= !empty($mensajes) ? (int)end($mensajes)->id : 0 ?>;
let chatPasantePollingTimer = null;

// Sonido sutil de notificación estilo Messenger/Steam (Web Audio API)
function playChatChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.10); // A5
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.22);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.22);
    } catch (e) {}
}

function autoExpandChatInputPasante(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 110) + 'px';
}

function insertarPlantillaPasante(texto) {
    const txt = document.getElementById('chatInputPasanteTexto');
    if (!txt) return;
    txt.value = texto;
    autoExpandChatInputPasante(txt);
    txt.focus();
}

function scrollChatPasanteToBottom(smooth = false) {
    const el = document.getElementById('chatPasanteContainer');
    if (el) {
        if (smooth) {
            el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
        } else {
            el.scrollTop = el.scrollHeight;
        }
    }
}

function volverAListaPasante() {
    window.location.href = URLROOT + '/soporte';
}

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

    // ── 0ms Optimistic UI: vaciar campo inmediatamente y resetear altura ──
    txtEl.value = '';
    txtEl.style.height = 'auto';

    // Ocultar hint de chat vacío si existe
    const hint = document.getElementById('emptyChatHint');
    if (hint) hint.style.display = 'none';

    // Inyectar burbuja optimista inmediatamente en el contenedor
    const container = document.getElementById('chatPasanteContainer');
    const tempRowId = 'temp_pas_' + Date.now();
    const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const row = document.createElement('div');
    row.className = 'chat-msg-row msg-me';
    row.id = tempRowId;
    row.innerHTML = `
        <div class="msg-bubble-wrap">
            <div class="msg-meta-line">
                <span class="msg-author-name">
                    Tú <span class="badge-role-user"><i class="ti ti-school"></i> Tú (Pasante)</span>
                </span>
                <span class="msg-time">Ahora</span>
            </div>
            <div class="msg-bubble bubble-me">
                <div class="msg-text">${escapeHtml(msg).replace(/\n/g, '<br>')}</div>
                <div class="msg-check-status">
                    <span class="msg-clock">${nowTime}</span>
                    <i class="ti ti-clock msg-clock-pending" title="Enviando..." style="display:inline-block;animation:spin 1.2s linear infinite;color:rgba(255,255,255,0.7);"></i>
                </div>
            </div>
        </div>
        <div class="msg-avatar-col msg-avatar-right" title="Tú">
            <div class="msg-avatar msg-avatar-user">
                P
            </div>
        </div>
    `;
    container.appendChild(row);
    scrollChatPasanteToBottom(true);
    txtEl.focus();

    // ── Petición en segundo plano ──
    try {
        const resp = await fetch(URLROOT + '/soporte/mensaje', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: ticketId, mensaje: msg })
        });
        const res = await resp.json();
        if (res.success) {
            if (res.msg_id) {
                row.setAttribute('data-msg-id', res.msg_id);
                lastPasanteMsgId = Math.max(lastPasanteMsgId, parseInt(res.msg_id, 10));
            }
            const statusBox = row.querySelector('.msg-check-status');
            if (statusBox) {
                statusBox.innerHTML = `
                    <span class="msg-clock">${res.hora || nowTime}</span>
                    <i class="ti ti-checks msg-checks" title="Enviado"></i>
                `;
            }
            if (hint) hint.remove();
        } else {
            row.remove();
            txtEl.value = msg;
            if (hint) hint.style.display = 'block';
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo enviar el mensaje.' });
        }
    } catch (err) {
        row.remove();
        txtEl.value = msg;
        if (hint) hint.style.display = 'block';
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo enviar el mensaje. Intenta de nuevo.' });
    }
}

// Polling en tiempo real para el pasante (Escucha respuestas de soporte)
function iniciarPollingChatPasante() {
    if (!PASANTE_TICKET_ID) return;

    chatPasantePollingTimer = setInterval(async () => {
        try {
            const resp = await fetch(URLROOT + '/soporte/mensajesNuevos?ticket_id=' + PASANTE_TICKET_ID + '&after_id=' + lastPasanteMsgId);
            if (!resp.ok) return;
            const data = await resp.json();
            if (!data.success) return;

            // Actualizar estado si el soporte lo cambió
            if (data.estado) {
                const headerPill = document.getElementById('headerPasanteEstadoPill');
                if (headerPill && headerPill.textContent.trim() !== data.estado) {
                    headerPill.textContent = data.estado;
                    headerPill.className = 'mh-status-pill status-pill-' + data.estado.toLowerCase().replace(/\\s+/g, '-');
                }
            }

            // Si hay mensajes nuevos
            if (data.mensajes && data.mensajes.length > 0) {
                const container = document.getElementById('chatPasanteContainer');
                const hint = document.getElementById('emptyChatHint');
                if (hint) hint.remove();

                data.mensajes.forEach(m => {
                    const msgId = parseInt(m.id, 10);
                    if (msgId <= lastPasanteMsgId) return;
                    lastPasanteMsgId = Math.max(lastPasanteMsgId, msgId);

                    const isSoporte = (m.remitente_tipo === 'soporte');
                    const isMe = !isSoporte;
                    const row = document.createElement('div');
                    row.className = 'chat-msg-row ' + (isMe ? 'msg-me' : 'msg-other');
                    row.setAttribute('data-msg-id', msgId);

                    if (!isMe) {
                        row.innerHTML = `
                            <div class="msg-avatar-col" title="Soporte Técnico">
                                <div class="msg-avatar msg-avatar-tech">
                                    <i class="ti ti-headset"></i>
                                </div>
                            </div>
                            <div class="msg-bubble-wrap">
                                <div class="msg-meta-line">
                                    <span class="msg-author-name">
                                        ${escapeHtml(m.remitente_nombre)}
                                        <span class="badge-role-tech"><i class="ti ti-shield-check"></i> Soporte Oficial</span>
                                    </span>
                                    <span class="msg-time">${formatearFechaHora(m.created_at)}</span>
                                </div>
                                <div class="msg-bubble bubble-other">
                                    <div class="msg-text">${escapeHtml(m.mensaje).replace(/\\n/g, '<br>')}</div>
                                    <div class="msg-check-status">
                                        <span class="msg-clock">${formatearHora(m.created_at)}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        row.innerHTML = `
                            <div class="msg-bubble-wrap">
                                <div class="msg-meta-line">
                                    <span class="msg-author-name">
                                        ${escapeHtml(m.remitente_nombre)}
                                        <span class="badge-role-user"><i class="ti ti-school"></i> Tú (Pasante)</span>
                                    </span>
                                    <span class="msg-time">${formatearFechaHora(m.created_at)}</span>
                                </div>
                                <div class="msg-bubble bubble-me">
                                    <div class="msg-text">${escapeHtml(m.mensaje).replace(/\\n/g, '<br>')}</div>
                                    <div class="msg-check-status">
                                        <span class="msg-clock">${formatearHora(m.created_at)}</span>
                                        <i class="ti ti-checks msg-checks" title="Enviado"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="msg-avatar-col msg-avatar-right" title="Tú">
                                <div class="msg-avatar msg-avatar-user">
                                    ${(m.remitente_nombre || 'P').charAt(0).toUpperCase()}
                                </div>
                            </div>
                        `;
                    }
                    container.appendChild(row);
                });

                playChatChime();
                scrollChatPasanteToBottom(true);
            }
        } catch (e) {
            // Error silencioso en polling
        }
    }, 3500);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function formatearFechaHora(str) {
    try {
        const d = new Date(str);
        return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth()+1)).slice(-2) + ' ' + ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
    } catch(e) { return 'Reciente'; }
}
function formatearHora(str) {
    try {
        const d = new Date(str);
        let h = d.getHours();
        const m = ('0' + d.getMinutes()).slice(-2);
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    } catch(e) { return ''; }
}

document.addEventListener('DOMContentLoaded', () => {
    scrollChatPasanteToBottom(false);
    iniciarPollingChatPasante();

    if (window.innerWidth <= 900 && new URLSearchParams(window.location.search).has('id')) {
        const chatSection = document.getElementById('chatDetailPasanteSection');
        if (chatSection) {
            setTimeout(() => {
                chatSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 180);
        }
    }
});

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

