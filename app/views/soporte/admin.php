<?php
/**
 * Vista Administrativa: Mesa de Ayuda y Soporte Técnico (HelpDesk ISPEB)
 */
$kpis           = $data['kpis'] ?? (object)['total_tickets' => 0, 'abiertos' => 0, 'en_proceso' => 0, 'resueltos' => 0];
$tickets        = $data['tickets'] ?? [];
$selectedTicket = $data['selectedTicket'] ?? null;
$mensajes       = $data['mensajes'] ?? [];
$filtros        = $data['filtros'] ?? ['estado' => 'Todos', 'prioridad' => 'Todas', 'q' => ''];
?>

<div class="soporte-container">
    <!-- ══ HEADER BANNER (Bento Style) ══ -->
    <div class="soporte-banner">
        <div class="sb-info">
            <div class="sb-icon">
                <i class="ti ti-headset"></i>
            </div>
            <div>
                <h1 class="sb-title">Mesa de Ayuda &bull; Soporte Técnico</h1>
                <p class="sb-subtitle">Centro integral de atención de incidencias, solicitudes y requerimientos de usuarios del ISPEB</p>
            </div>
        </div>
        <div class="sb-actions">
            <button type="button" class="btn-nuevo-ticket" onclick="abrirModalCrearTicketAdmin()">
                <i class="ti ti-plus"></i>
                <span>Nuevo Caso</span>
            </button>
        </div>
    </div>

    <!-- ══ KPI CARDS ══ -->
    <div class="sgp-kpi-grid soporte-kpis">
        <div class="kpi-card-bento <?= $filtros['estado'] === 'Todos' ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('Todos')">
            <div class="kpi-icon-wrap" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                <i class="ti ti-ticket"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-num"><?= (int)$kpis->total_tickets ?></span>
                <span class="kpi-lbl">Total Casos</span>
            </div>
        </div>

        <div class="kpi-card-bento <?= $filtros['estado'] === 'Abierto' ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('Abierto')">
            <div class="kpi-icon-wrap" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="ti ti-alert-circle"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-num" style="color: #ef4444;"><?= (int)$kpis->abiertos ?></span>
                <span class="kpi-lbl">Abiertos (Sin Atender)</span>
            </div>
        </div>

        <div class="kpi-card-bento <?= $filtros['estado'] === 'En Proceso' ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('En Proceso')">
            <div class="kpi-icon-wrap" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="ti ti-loader"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-num" style="color: #f59e0b;"><?= (int)$kpis->en_proceso ?></span>
                <span class="kpi-lbl">En Proceso</span>
            </div>
        </div>

        <div class="kpi-card-bento <?= $filtros['estado'] === 'Resuelto' ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('Resuelto')">
            <div class="kpi-icon-wrap" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="ti ti-circle-check"></i>
            </div>
            <div class="kpi-content">
                <span class="kpi-num" style="color: #10b981;"><?= (int)$kpis->resueltos ?></span>
                <span class="kpi-lbl">Resueltos</span>
            </div>
        </div>
    </div>

    <!-- ══ MAIN WORKSPACE: LISTA DE TICKETS + PANEL DE CHAT ══ -->
    <div class="soporte-workspace">
        <!-- ── COLUMNA IZQUIERDA: LISTADO Y FILTROS ── -->
        <div class="soporte-sidebar-list">
            <div class="list-search-box">
                <i class="ti ti-search search-ico"></i>
                <input type="text" id="inputBuscarTicket" placeholder="Buscar por código, solicitante, cédula o asunto..." value="<?= htmlspecialchars($filtros['q']) ?>" onkeyup="ejecutarFiltroTickets(event)">
                <?php if (!empty($filtros['q'])): ?>
                <button type="button" class="btn-clear-search" onclick="limpiarBusquedaTickets()"><i class="ti ti-x"></i></button>
                <?php endif; ?>
            </div>

            <!-- Filtros rápidos por píldoras -->
            <div class="status-pills-bar">
                <?php 
                $estadosPills = ['Todos', 'Abierto', 'En Proceso', 'Resuelto', 'Cerrado'];
                foreach ($estadosPills as $ep):
                    $act = ($filtros['estado'] === $ep) ? 'active' : '';
                ?>
                <button type="button" class="pill-btn <?= $act ?>" onclick="filtrarPorEstado('<?= $ep ?>')"><?= $ep ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Contenedor con scroll para lista de tickets -->
            <div class="tickets-scroll-list">
                <?php if (empty($tickets)): ?>
                <div class="empty-tickets">
                    <i class="ti ti-inbox" style="font-size: 2.2rem; color: #94a3b8; margin-bottom: 8px;"></i>
                    <p style="margin: 0; font-weight: 700; color: #475569;">No hay tickets que coincidan</p>
                    <small style="color: #94a3b8;">Prueba cambiando el filtro o la búsqueda.</small>
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
                        $prioridadBg = match($t->prioridad) {
                            'Urgente' => '#fee2e2; color: #b91c1c;',
                            'Alta'    => '#ffedd5; color: #c2410c;',
                            'Media'   => '#fef3c7; color: #b45309;',
                            default   => '#f1f5f9; color: #475569;'
                        };
                    ?>
                    <div class="ticket-item-card <?= $isSelected ? 'selected' : '' ?>" onclick="seleccionarTicket(<?= (int)$t->id ?>)">
                        <div class="tic-header">
                            <span class="tic-code"><?= htmlspecialchars($t->codigo) ?></span>
                            <span class="tic-badge" style="background: <?= $badgeColor ?>; color: #fff;"><?= htmlspecialchars($t->estado) ?></span>
                        </div>
                        <h4 class="tic-asunto"><?= htmlspecialchars($t->asunto) ?></h4>
                        <p class="tic-preview"><?= htmlspecialchars(mb_substr($t->mensaje, 0, 85)) ?>...</p>
                        
                        <div class="tic-footer">
                            <div class="tic-user">
                                <span class="avatar-mini"><?= strtoupper(substr($t->solicitante_nombres ?? 'U', 0, 1)) ?></span>
                                <span class="tic-username"><?= htmlspecialchars($t->solicitante_nombres) ?></span>
                            </div>
                            <div class="tic-meta">
                                <span class="tic-prio" style="<?= $prioridadBg ?>"><?= htmlspecialchars($t->prioridad) ?></span>
                                <span class="tic-time"><?= $t->horas_transcurridas ?>h</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── COLUMNA DERECHA: DETALLE DEL TICKET & CHAT INTERACTIVO ── -->
        <div class="soporte-detail-chat">
            <?php if (!$selectedTicket): ?>
            <div class="no-selection-placeholder">
                <div class="nsp-icon">
                    <i class="ti ti-messages"></i>
                </div>
                <h3>Selecciona un caso para atender</h3>
                <p>Haz clic en cualquier ticket del panel izquierdo para visualizar su información, cambiar el estado y dialogar en tiempo real con el solicitante.</p>
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
            <!-- Encabezado del caso seleccionado -->
            <div class="detail-header">
                <div class="dh-main">
                    <div class="dh-tags">
                        <span class="dh-code"><?= htmlspecialchars($selectedTicket->codigo) ?></span>
                        <span class="dh-estado" style="background: <?= $estadoColor ?>; color: #fff;">
                            <?= htmlspecialchars($selectedTicket->estado) ?>
                        </span>
                        <span class="dh-cat"><?= htmlspecialchars($selectedTicket->categoria) ?></span>
                        <span class="dh-prio prio-<?= strtolower($selectedTicket->prioridad) ?>">
                            Prioridad: <?= htmlspecialchars($selectedTicket->prioridad) ?>
                        </span>
                    </div>
                    <h2 class="dh-title"><?= htmlspecialchars($selectedTicket->asunto) ?></h2>
                </div>

                <div class="dh-actions">
                    <!-- Selector rápido de estado -->
                    <div class="estado-selector-group">
                        <label for="cambioEstadoSelect">Estado:</label>
                        <select id="cambioEstadoSelect" onchange="cambiarEstadoTicket(<?= (int)$selectedTicket->id ?>, this.value)">
                            <option value="Abierto" <?= $selectedTicket->estado === 'Abierto' ? 'selected' : '' ?>>Abierto</option>
                            <option value="En Proceso" <?= $selectedTicket->estado === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="Resuelto" <?= $selectedTicket->estado === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                            <option value="Cerrado" <?= $selectedTicket->estado === 'Cerrado' ? 'selected' : '' ?>>Cerrado</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Ficha resumida del solicitante -->
            <div class="user-strip-card">
                <div class="usc-avatar">
                    <?= strtoupper(substr($selectedTicket->solicitante_nombres ?? 'U', 0, 1)) ?>
                </div>
                <div class="usc-details">
                    <div class="usc-name">
                        <?= htmlspecialchars(trim(($selectedTicket->solicitante_nombres ?? '') . ' ' . ($selectedTicket->solicitante_apellidos ?? ''))) ?>
                        <?php if (!empty($selectedTicket->solicitante_cedula)): ?>
                        <span class="usc-cedula">C.I. <?= htmlspecialchars($selectedTicket->solicitante_cedula) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="usc-contact">
                        <span><i class="ti ti-mail"></i> <?= htmlspecialchars($selectedTicket->correo) ?></span>
                        <?php if (!empty($selectedTicket->departamento_nombre)): ?>
                        <span><i class="ti ti-building"></i> <?= htmlspecialchars($selectedTicket->departamento_nombre) ?></span>
                        <?php endif; ?>
                        <span><i class="ti ti-clock"></i> Creado hace <?= $selectedTicket->horas_transcurridas ?>h (<?= date('d/m/Y H:i', strtotime($selectedTicket->created_at)) ?>)</span>
                    </div>
                </div>
            </div>

            <!-- Ventana de Mensajes / Chat -->
            <div class="chat-messages-container" id="chatMessagesContainer">
                <?php if (empty($mensajes)): ?>
                    <div class="empty-chat-hint">
                        <i class="ti ti-message-dots"></i>
                        <span>No hay mensajes en este caso aún. Escribe el primer mensaje de respuesta técnica abajo.</span>
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

            <!-- Formulario para enviar respuesta en el chat -->
            <form class="chat-input-bar" id="formRespuestaChat" onsubmit="enviarMensajeChat(event, <?= (int)$selectedTicket->id ?>)">
                <textarea id="chatInputTexto" placeholder="Escribe tu respuesta técnica o instrucción para el usuario..." rows="2" required onkeydown="handleChatKeyDown(event, <?= (int)$selectedTicket->id ?>)"></textarea>
                <div class="cib-actions">
                    <button type="submit" id="btnEnviarChat" class="btn-send-chat">
                        <i class="ti ti-send"></i>
                        <span>Responder</span>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Crear Ticket (Mesa de Ayuda) -->
<div id="modalCrearTicketAdmin" class="sgp-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.65);z-index:999999;align-items:center;justify-content:center;backdrop-filter:blur(6px);padding:16px;" onclick="if(event.target===this)cerrarModalCrearTicketAdmin()">
    <div class="sgp-modal-bento" style="background:#ffffff;border-radius:24px;width:100%;max-width:540px;box-shadow:0 25px 60px rgba(0,0,0,0.25);border:1px solid #e2e8f0;overflow:hidden;animation:bentoModalIn 0.3s cubic-bezier(0.16,1,0.3,1);">
        <div style="background:linear-gradient(135deg, #162660 0%, #1e3a8a 100%);padding:20px 24px;color:#ffffff;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                    <i class="ti ti-headset"></i>
                </div>
                <div>
                    <h3 style="margin:0;font-size:1.1rem;font-weight:800;color:#ffffff;">Nuevo Ticket de Soporte</h3>
                    <p style="margin:2px 0 0;font-size:0.75rem;color:rgba(255,255,255,0.8);">Mesa de Ayuda &bull; Dirección de Telemática</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalCrearTicketAdmin()" style="background:none;border:none;color:#ffffff;font-size:1.2rem;cursor:pointer;opacity:0.8;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <form onsubmit="guardarNuevoTicketAdmin(event)" style="padding:22px 24px;">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Asunto</label>
                <input type="text" id="ntAsunto" required placeholder="Ej: Inconsistencia en asistencia / Bloqueo de PIN" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.9rem;outline:none;font-weight:600;color:#1e293b;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Categoría</label>
                    <select id="ntCategoria" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.88rem;outline:none;font-weight:600;color:#1e293b;background:#fff;">
                        <option value="Error del Sistema">Error del Sistema</option>
                        <option value="Problema de Acceso">Problema de Acceso</option>
                        <option value="Duda Operativa">Duda Operativa</option>
                        <option value="Sugerencia">Sugerencia</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Prioridad</label>
                    <select id="ntPrioridad" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.88rem;outline:none;font-weight:600;color:#1e293b;background:#fff;">
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:6px;">Descripción Detallada</label>
                <textarea id="ntMensaje" required rows="4" placeholder="Explica detalladamente la solicitud o reporte..." style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.88rem;outline:none;font-weight:500;color:#1e293b;resize:vertical;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" onclick="cerrarModalCrearTicketAdmin()" style="padding:10px 16px;border:1.5px solid #cbd5e1;border-radius:10px;background:#fff;color:#475569;font-weight:700;font-size:0.86rem;cursor:pointer;">Cancelar</button>
                <button type="submit" id="btnGuardarTicketAdmin" style="padding:10px 22px;border:none;border-radius:10px;background:linear-gradient(135deg,#162660,#2563eb);color:#fff;font-weight:700;font-size:0.86rem;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <i class="ti ti-send"></i> <span>Crear Ticket</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ── Layout General de Soporte ───────────────────────────────────── */
.soporte-container {
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 4px 30px;
}
.soporte-banner {
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
.sb-info {
    display: flex;
    align-items: center;
    gap: 18px;
}
.sb-icon {
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
.sb-title {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.3px;
    color: #ffffff;
}
.sb-subtitle {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 4px 0 0;
}
.btn-nuevo-ticket {
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
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-nuevo-ticket:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.2);
}

/* ── KPIs Bento ─────────────────────────────────────────────────── */
.soporte-kpis {
    margin-bottom: 22px;
}
.kpi-card-bento {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
}
.kpi-card-bento:hover {
    transform: translateY(-2px);
    border-color: #cbd5e1;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}
.kpi-card-bento.active-kpi {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 6px 18px rgba(37,99,235,0.12);
}
.kpi-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.kpi-content {
    display: flex;
    flex-direction: column;
}
.kpi-num {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.1;
    color: #1e293b;
}
.kpi-lbl {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    margin-top: 2px;
}

/* ── Workspace Split Screen ──────────────────────────────────────── */
.soporte-workspace {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 20px;
    min-height: 620px;
}
@media (max-width: 1024px) {
    .soporte-workspace {
        grid-template-columns: 1fr;
    }
}

/* ── Panel Izquierdo: Lista ──────────────────────────────────────── */
.soporte-sidebar-list {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    padding: 18px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    height: 720px;
}
.list-search-box {
    position: relative;
    margin-bottom: 12px;
}
.list-search-box .search-ico {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.1rem;
}
.list-search-box input {
    width: 100%;
    padding: 9px 34px 9px 36px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    outline: none;
    color: #1e293b;
    background: #f8fafc;
}
.list-search-box input:focus {
    border-color: #2563eb;
    background: #ffffff;
}
.btn-clear-search {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
}
.status-pills-bar {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 8px;
    margin-bottom: 12px;
}
.status-pills-bar::-webkit-scrollbar { height: 4px; }
.status-pills-bar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.pill-btn {
    padding: 5px 12px;
    border-radius: 16px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}
.pill-btn.active {
    background: #162660;
    color: #ffffff;
    border-color: #162660;
}
.tickets-scroll-list {
    flex: 1;
    overflow-y: auto;
    padding-right: 4px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.tickets-scroll-list::-webkit-scrollbar { width: 5px; }
.tickets-scroll-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }

.ticket-item-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.2s;
}
.ticket-item-card:hover {
    border-color: #93c5fd;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.ticket-item-card.selected {
    border-color: #2563eb;
    background: #f8fafc;
    box-shadow: 0 4px 14px rgba(37,99,235,0.12);
}
.tic-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.tic-code {
    font-size: 0.78rem;
    font-weight: 800;
    color: #2563eb;
}
.tic-badge {
    font-size: 0.68rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 8px;
}
.tic-asunto {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px;
    line-height: 1.3;
}
.tic-preview {
    font-size: 0.76rem;
    color: #64748b;
    margin: 0 0 10px;
    line-height: 1.35;
}
.tic-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed #e2e8f0;
    padding-top: 8px;
}
.tic-user {
    display: flex;
    align-items: center;
    gap: 6px;
}
.avatar-mini {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #1e293b;
    font-size: 0.65rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.tic-username {
    font-size: 0.74rem;
    font-weight: 600;
    color: #334155;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.tic-meta {
    display: flex;
    align-items: center;
    gap: 6px;
}
.tic-prio {
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 6px;
}
.tic-time {
    font-size: 0.7rem;
    color: #94a3b8;
    font-weight: 600;
}

/* ── Panel Derecho: Detalle y Chat ───────────────────────────────── */
.soporte-detail-chat {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    height: 720px;
    overflow: hidden;
}
.no-selection-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
    color: #64748b;
}
.nsp-icon {
    width: 72px;
    height: 72px;
    border-radius: 24px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 2.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.no-selection-placeholder h3 {
    margin: 0 0 6px;
    font-weight: 800;
    color: #1e293b;
}
.no-selection-placeholder p {
    max-width: 420px;
    font-size: 0.85rem;
    line-height: 1.5;
    margin: 0;
}

.detail-header {
    padding: 18px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    background: #f8fafc;
}
.dh-tags {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 6px;
}
.dh-code {
    font-weight: 800;
    font-size: 0.95rem;
    color: #162660;
}
.dh-estado {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 8px;
}
.dh-cat {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 8px;
    background: #e2e8f0;
    color: #334155;
}
.dh-prio {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 8px;
}
.prio-urgente { background: #fee2e2; color: #b91c1c; }
.prio-alta    { background: #ffedd5; color: #c2410c; }
.prio-media   { background: #fef3c7; color: #b45309; }
.prio-baja    { background: #f1f5f9; color: #475569; }

.dh-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    line-height: 1.3;
}
.estado-selector-group {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    padding: 6px 12px;
    border-radius: 10px;
    border: 1.5px solid #cbd5e1;
}
.estado-selector-group label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    margin: 0;
}
.estado-selector-group select {
    border: none;
    font-size: 0.84rem;
    font-weight: 700;
    color: #1e293b;
    outline: none;
    cursor: pointer;
    background: transparent;
}

.user-strip-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}
.usc-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #162660, #2563eb);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.usc-name {
    font-weight: 800;
    font-size: 0.88rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.usc-cedula {
    font-size: 0.72rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    padding: 1px 6px;
    border-radius: 6px;
}
.usc-contact {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 2px;
}
.usc-contact span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* ── Chat Messages ───────────────────────────────────────────────── */
.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.chat-messages-container::-webkit-scrollbar { width: 6px; }
.chat-messages-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }

.chat-bubble-row {
    display: flex;
    width: 100%;
}
.row-usuario { justify-content: flex-start; }
.row-soporte { justify-content: flex-end; }

.chat-bubble {
    max-width: 78%;
    border-radius: 16px;
    padding: 14px 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}
.bubble-usuario {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}
.bubble-soporte {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #bfdbfe;
    border-bottom-right-radius: 4px;
}
.bubble-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 6px;
}
.bubble-author {
    font-size: 0.76rem;
    font-weight: 800;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.tag-tecnico {
    font-size: 0.65rem;
    font-weight: 800;
    background: #2563eb;
    color: #ffffff;
    padding: 1px 6px;
    border-radius: 6px;
    margin-left: 4px;
}
.bubble-time {
    font-size: 0.68rem;
    color: #94a3b8;
    font-weight: 600;
}
.bubble-body {
    font-size: 0.86rem;
    color: #334155;
    line-height: 1.45;
}

.chat-input-bar {
    padding: 14px 20px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: flex-end;
    gap: 12px;
}
.chat-input-bar textarea {
    flex: 1;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 0.86rem;
    font-weight: 500;
    outline: none;
    color: #1e293b;
    resize: none;
    line-height: 1.4;
    transition: border-color 0.2s;
}
.chat-input-bar textarea:focus {
    border-color: #2563eb;
}
.btn-send-chat {
    padding: 10px 20px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #162660, #2563eb);
    color: #ffffff;
    font-weight: 800;
    font-size: 0.86rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(37,99,235,0.25);
    transition: transform 0.2s;
}
.btn-send-chat:hover {
    transform: translateY(-1px);
}
</style>

<script>
// Scroll automático al fondo del chat
function scrollChatToBottom() {
    const el = document.getElementById('chatMessagesContainer');
    if (el) el.scrollTop = el.scrollHeight;
}
document.addEventListener('DOMContentLoaded', scrollChatToBottom);

// Seleccionar ticket en la lista
function seleccionarTicket(id) {
    const params = new URLSearchParams(window.location.search);
    params.set('id', id);
    window.location.href = URLROOT + '/soporte?' + params.toString();
}

// Filtrar por estado
function filtrarPorEstado(estado) {
    const params = new URLSearchParams(window.location.search);
    params.set('estado', estado);
    params.delete('id');
    window.location.href = URLROOT + '/soporte?' + params.toString();
}

// Búsqueda de tickets
function ejecutarFiltroTickets(e) {
    if (e.key === 'Enter') {
        const q = document.getElementById('inputBuscarTicket').value.trim();
        const params = new URLSearchParams(window.location.search);
        if (q) params.set('q', q); else params.delete('q');
        params.delete('id');
        window.location.href = URLROOT + '/soporte?' + params.toString();
    }
}
function limpiarBusquedaTickets() {
    const params = new URLSearchParams(window.location.search);
    params.delete('q');
    params.delete('id');
    window.location.href = URLROOT + '/soporte?' + params.toString();
}

// Enviar mensaje en el chat
function handleChatKeyDown(e, ticketId) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        enviarMensajeChat(e, ticketId);
    }
}

async function enviarMensajeChat(e, ticketId) {
    if (e) e.preventDefault();
    const txtEl = document.getElementById('chatInputTexto');
    const msg = txtEl.value.trim();
    if (!msg) return;

    const btn = document.getElementById('btnEnviarChat');
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
            // Agregar burbuja visualmente de inmediato
            const container = document.getElementById('chatMessagesContainer');
            const row = document.createElement('div');
            row.className = 'chat-bubble-row row-soporte';
            row.innerHTML = `
                <div class="chat-bubble bubble-soporte">
                    <div class="bubble-header">
                        <span class="bubble-author">
                            <i class="ti ti-shield-check"></i>
                            Tú
                            <span class="tag-tecnico">Soporte Telemática</span>
                        </span>
                        <span class="bubble-time">${res.created_at || 'Ahora'}</span>
                    </div>
                    <div class="bubble-body">${msg.replace(/\n/g, '<br>')}</div>
                </div>
            `;
            container.appendChild(row);
            scrollChatToBottom();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo enviar el mensaje.' });
    } finally {
        btn.disabled = false;
    }
}

// Cambiar estado del ticket
async function cambiarEstadoTicket(ticketId, nuevoEstado) {
    try {
        const resp = await fetch(URLROOT + '/soporte/actualizarEstado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: ticketId, estado: nuevoEstado })
        });
        const res = await resp.json();
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Estado Actualizado',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo actualizar el estado.' });
    }
}

// Modal nuevo ticket
function abrirModalCrearTicketAdmin() {
    document.getElementById('modalCrearTicketAdmin').style.display = 'flex';
}
function cerrarModalCrearTicketAdmin() {
    document.getElementById('modalCrearTicketAdmin').style.display = 'none';
}

async function guardarNuevoTicketAdmin(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarTicketAdmin');
    btn.disabled = true;

    const data = {
        asunto: document.getElementById('ntAsunto').value.trim(),
        categoria: document.getElementById('ntCategoria').value,
        prioridad: document.getElementById('ntPrioridad').value,
        mensaje: document.getElementById('ntMensaje').value.trim()
    };

    try {
        const resp = await fetch(URLROOT + '/soporte/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await resp.json();
        if (res.success) {
            cerrarModalCrearTicketAdmin();
            Swal.fire({
                icon: 'success',
                title: 'Ticket Creado',
                text: res.message,
                confirmButtonColor: '#162660'
            }).then(() => {
                seleccionarTicket(res.ticket_id);
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

