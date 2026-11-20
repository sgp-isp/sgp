<?php
/**
 * Vista Administrativa: Mesa de Ayuda y Soporte Técnico (HelpDesk ISPEB)
 */
$kpis           = $data['kpis'] ?? (object)['total_tickets' => 0, 'abiertos' => 0, 'en_proceso' => 0, 'resueltos' => 0];
$tickets        = $data['tickets'] ?? [];
$selectedTicket = $data['selectedTicket'] ?? null;
$mensajes       = $data['mensajes'] ?? [];
$filtros        = $data['filtros'] ?? ['estado' => 'Todos', 'prioridad' => 'Todas', 'q' => ''];
$hasExplicitTicket = (!empty($selectedTicket) && (isset($_GET['id']) || isset($_GET['ticket_id'])));
?>

<!-- Sincronización inmediata de modo móvil antes del primer renderizado -->
<script>
(function() {
    var hasExplicit = <?= $hasExplicitTicket ? 'true' : 'false' ?>;
    if (window.innerWidth <= 768) {
        if (hasExplicit) {
            document.body.classList.add('sgp-mobile-chat-active');
        } else {
            document.body.classList.remove('sgp-mobile-chat-active');
        }
    }
})();
</script>

<div class="soporte-container <?= $hasExplicitTicket ? 'has-ticket-open' : '' ?>">
    <!-- ══ HEADER BANNER (Estandarizado SGP) ══ -->
    <div class="admin-banner soporte-banner" style="position:relative;overflow:hidden;background:linear-gradient(135deg, #162660 0%, #1e3a8a 50%, #2563eb 100%);border-radius:20px;padding:26px 32px;color:#ffffff;box-shadow:0 10px 30px rgba(22, 38, 96, 0.2);margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;box-sizing:border-box;">
        <div style="position:absolute;top:-30px;right:-30px;width:180px;height:180px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
        <div class="banner-main-group" style="display:flex;align-items:center;gap:18px;z-index:1;min-width:0;">
            <div class="banner-icon-badge" style="width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,0.16);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#ffffff;flex-shrink:0;">
                <i class="ti ti-headset"></i>
            </div>
            <div class="banner-text-block" style="min-width:0;">
                <h1 class="banner-title" style="font-size:1.6rem;font-weight:800;margin:0;letter-spacing:-0.3px;color:#ffffff;line-height:1.2;">Mesa de Ayuda &bull; Soporte Técnico</h1>
                <p class="banner-subtitle" style="font-size:0.86rem;color:rgba(255,255,255,0.85);margin:5px 0 0;display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
                    <span>Centro integral de incidencias, solicitudes y requerimientos del ISPEB</span>
                    <span class="banner-chip-badge" style="display:inline-block;background:rgba(255,255,255,0.18);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.25);border-radius:50px;padding:2px 12px;color:white;font-weight:700;font-size:0.78rem;white-space:nowrap;">
                        <?= (int)$kpis->total_tickets ?> <?= (int)$kpis->total_tickets === 1 ? 'caso' : 'casos' ?>
                    </span>
                </p>
            </div>
        </div>
        <div class="admin-banner-actions" style="z-index:1;flex-shrink:0;">
            <button type="button" class="btn-nuevo-ticket" onclick="abrirModalCrearTicketAdmin()">
                <i class="ti ti-plus"></i>
                <span>Nuevo Caso</span>
            </button>
        </div>
    </div>

    <!-- ══ KPI CARDS ESTANDARIZADAS (Estilo Asistencias/Inicio SGP) ══ -->
    <div class="sgp-kpi-grid soporte-kpis" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px;">
        <!-- Casos Activos (Azul) -->
        <div class="asist-kpi-card c-blue <?= in_array($filtros['estado'], ['Activos', 'Todos']) ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('Activos')" style="cursor:pointer;" title="Ver casos activos (Abiertos y En Proceso)">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">Casos Activos</div>
                    <div class="asist-kpi-val" id="kpiActivosVal" style="color:#2563eb;"><?= (int)($kpis->abiertos + $kpis->en_proceso) ?></div>
                    <div class="asist-kpi-sub">requieren seguimiento técnico</div>
                </div>
                <div class="asist-kpi-icon" style="background:#eff6ff;">
                    <i class="ti ti-ticket" style="color:#2563eb;"></i>
                </div>
            </div>
        </div>

        <!-- Abiertos (Rojo) -->
        <div class="asist-kpi-card c-red <?= $filtros['estado'] === 'Abierto' ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('Abierto')" style="cursor:pointer;" title="Casos pendientes por atender">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">Abiertos (Sin Atender)</div>
                    <div class="asist-kpi-val" id="kpiAbiertosVal" style="color:#dc2626;"><?= (int)$kpis->abiertos ?></div>
                    <div class="asist-kpi-sub">requieren atención técnica</div>
                </div>
                <div class="asist-kpi-icon" style="background:#fef2f2;">
                    <i class="ti ti-alert-circle" style="color:#dc2626;"></i>
                </div>
            </div>
        </div>

        <!-- En Proceso (Ámbar) -->
        <div class="asist-kpi-card c-amber <?= $filtros['estado'] === 'En Proceso' ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('En Proceso')" style="cursor:pointer;" title="Casos en seguimiento">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">En Proceso</div>
                    <div class="asist-kpi-val" id="kpiEnProcesoVal" style="color:#d97706;"><?= (int)$kpis->en_proceso ?></div>
                    <div class="asist-kpi-sub">en diálogo o evaluación</div>
                </div>
                <div class="asist-kpi-icon" style="background:#fffbeb;">
                    <i class="ti ti-loader" style="color:#d97706;"></i>
                </div>
            </div>
        </div>

        <!-- Resueltos (Verde) -->
        <div class="asist-kpi-card c-green <?= $filtros['estado'] === 'Resuelto' ? 'active-kpi' : '' ?>" onclick="filtrarPorEstado('Resuelto')" style="cursor:pointer;" title="Casos resueltos">
            <div class="asist-kpi-top">
                <div>
                    <div class="asist-kpi-label">Resueltos / Cerrados</div>
                    <div class="asist-kpi-val" id="kpiResueltosVal" style="color:#059669;"><?= (int)$kpis->resueltos ?></div>
                    <div class="asist-kpi-sub">atención completada</div>
                </div>
                <div class="asist-kpi-icon" style="background:#ecfdf5;">
                    <i class="ti ti-circle-check" style="color:#059669;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MAIN WORKSPACE: LISTA DE TICKETS + PANEL DE CHAT ══ -->
    <div class="soporte-workspace <?= (!empty($selectedTicket) && (isset($_GET['id']) || isset($_GET['ticket_id']))) ? 'has-selected-ticket' : 'no-selected-ticket' ?>" id="soporteWorkspace">
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
                $estadosPills = ['Activos', 'Abierto', 'En Proceso', 'Resuelto', 'Cerrado', 'Todos'];
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
                    <div class="ticket-item-card <?= $isSelected ? 'selected' : '' ?>" data-ticket-id="<?= (int)$t->id ?>" data-estado="<?= htmlspecialchars($t->estado) ?>" onclick="seleccionarTicket(<?= (int)$t->id ?>)">
                        <div class="tic-header">
                            <span class="tic-code"><?= htmlspecialchars($t->codigo) ?></span>
                            <span class="tic-badge" style="background: <?= $badgeColor ?>; color: #fff;"><?= htmlspecialchars($t->estado) ?></span>
                        </div>
                        <h4 class="tic-asunto"><?= htmlspecialchars($t->asunto) ?></h4>
                        <p class="tic-preview"><?= htmlspecialchars(mb_substr($t->mensaje, 0, 85)) ?>...</p>
                        
                        <div class="tic-footer" style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <div class="tic-user" style="min-width:0;">
                                <span class="avatar-mini"><?= strtoupper(substr($t->solicitante_nombres ?? 'U', 0, 1)) ?></span>
                                <span class="tic-username" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($t->solicitante_nombres) ?></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                <span class="tic-prio" style="<?= $prioridadBg ?>"><?= htmlspecialchars($t->prioridad) ?></span>
                                <button type="button" class="btn-atender-ticket-card" onclick="event.stopPropagation(); seleccionarTicket(<?= (int)$t->id ?>)" style="padding:5px 12px;background:#2563eb;color:#ffffff;border:none;border-radius:8px;font-size:0.75rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                                    <i class="ti ti-headset"></i> Atender
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── COLUMNA DERECHA: DETALLE DEL TICKET & CHAT INTERACTIVO (STEAM / MESSENGER UI) ── -->
        <div class="soporte-detail-chat steam-chat-panel" id="chatDetailSection">
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
            <!-- Encabezado del Chat estilo Steam / Messenger -->
            <div class="messenger-header">
                <div class="mh-left">
                    <button type="button" class="btn-back-mobile" onclick="volverAListaAdmin()" title="Volver al listado">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                    <div class="mh-avatar-wrap">
                        <div class="mh-avatar">
                            <?= strtoupper(substr($selectedTicket->solicitante_nombres ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="online-status-dot" title="Usuario activo"></span>
                    </div>
                    <div class="mh-info">
                        <!-- Vista Desktop: nombre + pills completas -->
                        <div class="mh-desktop-info">
                            <div class="mh-name-row">
                                <h3 class="mh-name">
                                    <?= htmlspecialchars(trim(($selectedTicket->solicitante_nombres ?? '') . ' ' . ($selectedTicket->solicitante_apellidos ?? ''))) ?>
                                </h3>
                                <span class="mh-badge-code"><?= htmlspecialchars($selectedTicket->codigo) ?></span>
                                <span class="mh-status-pill status-pill-<?= strtolower(str_replace(' ', '-', $selectedTicket->estado)) ?>" id="headerEstadoPill">
                                    <?= htmlspecialchars($selectedTicket->estado) ?>
                                </span>
                            </div>
                            <div class="mh-meta-line">
                                <span class="mh-cat-pill"><?= htmlspecialchars($selectedTicket->categoria) ?></span>
                                <span class="mh-prio-pill prio-<?= strtolower($selectedTicket->prioridad) ?>">
                                    <?= htmlspecialchars($selectedTicket->prioridad) ?>
                                </span>
                                <?php if (!empty($selectedTicket->solicitante_cedula)): ?>
                                <span class="mh-dot">&bull;</span>
                                <span>C.I. <?= htmlspecialchars($selectedTicket->solicitante_cedula) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($selectedTicket->departamento_nombre)): ?>
                                <span class="mh-dot">&bull;</span>
                                <span><?= htmlspecialchars($selectedTicket->departamento_nombre) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Vista Móvil: Barra Compacta 56px (Línea 1 Nombre, Línea 2 Asunto • #Código) -->
                        <div class="mh-mobile-info">
                            <div class="mh-mobile-title">
                                <?= htmlspecialchars(trim(($selectedTicket->solicitante_nombres ?? '') . ' ' . ($selectedTicket->solicitante_apellidos ?? ''))) ?>
                            </div>
                            <div class="mh-mobile-sub">
                                <?= htmlspecialchars($selectedTicket->asunto) ?> &bull; #<?= htmlspecialchars($selectedTicket->codigo) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones rápidas de soporte: Cambiar Estado -->
                <div class="mh-right">
                    <!-- Selector Desktop: 4 píldoras horizontales -->
                    <div class="status-quick-switch desktop-status-switch" title="Cambiar estado del caso">
                        <span class="sqs-label">Estado:</span>
                        <div class="sqs-pills" id="sqsPillsContainer">
                            <?php 
                            $estadosPosibles = [
                                'Abierto'    => ['icon' => 'ti-alert-circle', 'bg' => '#ef4444'],
                                'En Proceso' => ['icon' => 'ti-loader',       'bg' => '#f59e0b'],
                                'Resuelto'   => ['icon' => 'ti-circle-check', 'bg' => '#10b981'],
                                'Cerrado'    => ['icon' => 'ti-lock',         'bg' => '#64748b']
                            ];
                            foreach ($estadosPosibles as $estNombre => $estMeta):
                                $isCur = ($selectedTicket->estado === $estNombre);
                            ?>
                            <button type="button" 
                                    class="sqs-btn <?= $isCur ? 'active' : '' ?> sqs-btn-<?= strtolower(str_replace(' ', '-', $estNombre)) ?>" 
                                    onclick="cambiarEstadoTicketDirecto(<?= (int)$selectedTicket->id ?>, '<?= $estNombre ?>')"
                                    data-estado="<?= $estNombre ?>">
                                <i class="ti <?= $estMeta['icon'] ?>"></i>
                                <span><?= $estNombre ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Selector Móvil: Dropdown compacto e interactivo (píldora actual con chevron) -->
                    <div class="mobile-status-dropdown-wrap" id="mobileStatusDropdownWrap">
                        <button type="button" 
                                class="mobile-status-pill-btn status-pill-<?= strtolower(str_replace(' ', '-', $selectedTicket->estado)) ?>" 
                                id="mobileStatusTriggerBtn" 
                                onclick="toggleMobileStatusDropdown(event)">
                            <span id="mobileStatusBtnText"><?= htmlspecialchars($selectedTicket->estado) ?></span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="mobile-status-menu" id="mobileStatusMenu" style="display: none;">
                            <?php foreach ($estadosPosibles as $estNombre => $estMeta): ?>
                            <button type="button" 
                                    class="msm-option <?= $selectedTicket->estado === $estNombre ? 'active' : '' ?>" 
                                    data-estado="<?= $estNombre ?>"
                                    onclick="seleccionarEstadoMobile(<?= (int)$selectedTicket->id ?>, '<?= $estNombre ?>')">
                                <i class="ti <?= $estMeta['icon'] ?>" style="color: <?= $estMeta['bg'] ?>;"></i>
                                <span><?= $estNombre ?></span>
                                <i class="ti ti-check msm-check-icon" style="<?= $selectedTicket->estado === $estNombre ? '' : 'display:none;' ?>"></i>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asunto del ticket en barra de contexto tipo chat room -->
            <div class="chat-subject-bar">
                <i class="ti ti-info-circle"></i>
                <div class="csb-content">
                    <span class="csb-label">Asunto reportado:</span>
                    <strong class="csb-subject"><?= htmlspecialchars($selectedTicket->asunto) ?></strong>
                    <span class="csb-time">&bull; Creado <?= date('d/m/Y h:i A', strtotime($selectedTicket->created_at)) ?></span>
                </div>
            </div>

            <!-- Ventana de Mensajes / Stream estilo Messenger / Steam -->
            <div class="chat-messages-container" id="chatMessagesContainer">
                <div class="chat-date-separator">
                    <span>Inicio del Hilo de Soporte Técnico</span>
                </div>

                <?php if (empty($mensajes)): ?>
                    <div class="empty-chat-hint" id="emptyChatHint">
                        <i class="ti ti-message-dots"></i>
                        <span>No hay mensajes en este caso aún. Escribe el primer mensaje o selecciona una respuesta rápida abajo.</span>
                    </div>
                <?php else: ?>
                    <?php 
                    $lastId = 0;
                    foreach ($mensajes as $m): 
                        $isSoporte = ($m->remitente_tipo === 'soporte');
                        $lastId = (int)$m->id;
                    ?>
                    <div class="chat-msg-row <?= $isSoporte ? 'msg-me' : 'msg-other' ?>" data-msg-id="<?= (int)$m->id ?>">
                        <?php if (!$isSoporte): ?>
                        <div class="msg-avatar-col" title="<?= htmlspecialchars($m->remitente_nombre) ?>">
                            <div class="msg-avatar msg-avatar-user">
                                <?= strtoupper(substr($m->remitente_nombre ?? 'U', 0, 1)) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="msg-bubble-wrap">
                            <div class="msg-meta-line">
                                <span class="msg-author-name">
                                    <?= htmlspecialchars($m->remitente_nombre) ?>
                                    <?php if ($isSoporte): ?>
                                    <span class="badge-role-tech"><i class="ti ti-shield-check"></i> Soporte Telemática</span>
                                    <?php else: ?>
                                    <span class="badge-role-user"><i class="ti ti-user"></i> Solicitante</span>
                                    <?php endif; ?>
                                </span>
                                <span class="msg-time"><?= date('d/m H:i', strtotime($m->created_at)) ?></span>
                            </div>
                            <div class="msg-bubble <?= $isSoporte ? 'bubble-me' : 'bubble-other' ?>">
                                <div class="msg-text"><?= nl2br(htmlspecialchars($m->mensaje)) ?></div>
                                <div class="msg-check-status">
                                    <span class="msg-clock"><?= date('h:i A', strtotime($m->created_at)) ?></span>
                                    <?php if ($isSoporte): ?>
                                    <i class="ti ti-checks msg-checks" title="Enviado"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($isSoporte): ?>
                        <div class="msg-avatar-col msg-avatar-right" title="Tú (Soporte)">
                            <div class="msg-avatar msg-avatar-tech">
                                <i class="ti ti-headset"></i>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Respuestas Rápidas estilo Steam / Facebook (Visible en Desktop) -->
            <div class="chat-quick-replies desktop-quick-replies">
                <span class="cqr-label"><i class="ti ti-sparkles"></i> Respuestas Rápidas:</span>
                <div class="cqr-chips">
                    <button type="button" class="cqr-btn" onclick="insertarPlantilla('Hola, tu caso ya está siendo atendido por la División de Telemática. Estamos verificando los detalles en el sistema.')">
                        💬 En revisión
                    </button>
                    <button type="button" class="cqr-btn" onclick="insertarPlantilla('Tu dispositivo anterior ha sido desvinculado con éxito. Por favor vuelve a escanear el cartel QR con tu teléfono para vincularlo.')">
                        🔄 Dispositivo desvinculado
                    </button>
                    <button type="button" class="cqr-btn" onclick="insertarPlantilla('Tu clave de acceso/PIN ha sido restablecida temporalmente a los últimos 4 dígitos de tu cédula. Por favor prueba ingresar.')">
                        🔑 PIN restablecido
                    </button>
                    <button type="button" class="cqr-btn" onclick="insertarPlantilla('El requerimiento ha sido resuelto y validado en la plataforma. Por favor confirma si pudiste acceder con normalidad.')">
                        ✅ Solución confirmada
                    </button>
                </div>
            </div>

            <!-- Drawer Flotante de Respuestas Rápidas para Móvil (Popover encima del input) -->
            <div class="mobile-quick-drawer" id="mobileQuickDrawer" style="display: none;">
                <div class="mqd-header">
                    <span class="mqd-title"><i class="ti ti-bolt" style="color: #f59e0b;"></i> Respuestas Rápidas</span>
                    <button type="button" class="mqd-close-btn" onclick="cerrarQuickDrawer()" aria-label="Cerrar"><i class="ti ti-x"></i></button>
                </div>
                <div class="mqd-list">
                    <button type="button" class="mqd-item" onclick="seleccionarPlantillaDrawer('Hola, tu caso ya está siendo atendido por la División de Telemática. Estamos verificando los detalles en el sistema.')">
                        <span class="mqd-emoji">💬</span>
                        <div class="mqd-info">
                            <strong class="mqd-label">En revisión</strong>
                            <small class="mqd-desc">Hola, tu caso ya está siendo atendido por la División de Telemática...</small>
                        </div>
                    </button>
                    <button type="button" class="mqd-item" onclick="seleccionarPlantillaDrawer('Tu dispositivo anterior ha sido desvinculado con éxito. Por favor vuelve a escanear el cartel QR con tu teléfono para vincularlo.')">
                        <span class="mqd-emoji">🔄</span>
                        <div class="mqd-info">
                            <strong class="mqd-label">Dispositivo desvinculado</strong>
                            <small class="mqd-desc">Tu dispositivo anterior ha sido desvinculado con éxito...</small>
                        </div>
                    </button>
                    <button type="button" class="mqd-item" onclick="seleccionarPlantillaDrawer('Tu clave de acceso/PIN ha sido restablecida temporalmente a los últimos 4 dígitos de tu cédula. Por favor prueba ingresar.')">
                        <span class="mqd-emoji">🔑</span>
                        <div class="mqd-info">
                            <strong class="mqd-label">PIN restablecido</strong>
                            <small class="mqd-desc">Tu clave de acceso/PIN ha sido restablecida temporalmente...</small>
                        </div>
                    </button>
                    <button type="button" class="mqd-item" onclick="seleccionarPlantillaDrawer('El requerimiento ha sido resuelto y validado en la plataforma. Por favor confirma si pudiste acceder con normalidad.')">
                        <span class="mqd-emoji">✅</span>
                        <div class="mqd-info">
                            <strong class="mqd-label">Solución confirmada</strong>
                            <small class="mqd-desc">El requerimiento ha sido resuelto y validado en la plataforma...</small>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Formulario de Envío de Mensaje (Steam / Messenger Bar) -->
            <form class="chat-input-bar messenger-input-bar" id="formRespuestaChat" onsubmit="enviarMensajeChat(event, <?= (int)$selectedTicket->id ?>)">
                <!-- Botón disparador de respuestas rápidas en móvil -->
                <button type="button" id="btnToggleQuickReplies" class="btn-quick-trigger" onclick="toggleQuickDrawer(event)" title="Respuestas rápidas">
                    <i class="ti ti-bolt"></i>
                </button>
                <div class="cib-input-wrap">
                    <textarea id="chatInputTexto" 
                              placeholder="Responder..." 
                              rows="1" 
                              required 
                              onkeydown="handleChatKeyDown(event, <?= (int)$selectedTicket->id ?>)"
                              oninput="autoExpandChatInput(this)"></textarea>
                </div>
                <div class="cib-actions">
                    <button type="submit" id="btnEnviarChat" class="btn-send-chat" title="Enviar respuesta (Enter)">
                        <i class="ti ti-send"></i>
                        <span>Enviar</span>
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
/* ── Layout General de Soporte (Aprovechamiento 100% de Pantalla) ── */
.soporte-container {
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
.btn-nuevo-ticket {
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
.btn-nuevo-ticket:hover {
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
    cursor: pointer;
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
.asist-kpi-card.active-kpi {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(37,99,235,0.18) !important;
    outline: 2px solid #2563eb;
    background: #f8faff;
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

/* ── Panel Derecho: Chat Interactivo (Estilo Steam / Facebook Messenger) ── */
.soporte-detail-chat {
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
    max-width: 280px;
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
.mh-dot { color: #cbd5e1; }

/* Status Quick Switch (Steam / Messenger Pills) */
.status-quick-switch {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    padding: 5px 8px;
    border-radius: 12px;
}
.sqs-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
}
.sqs-pills {
    display: flex;
    align-items: center;
    gap: 4px;
}
.sqs-btn {
    border: none;
    background: transparent;
    padding: 4px 9px;
    border-radius: 8px;
    font-size: 0.74rem;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
}
.sqs-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.sqs-btn.active.sqs-btn-abierto {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(239,68,68,0.35);
}
.sqs-btn.active.sqs-btn-en-proceso {
    background: #f59e0b;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(245,158,11,0.35);
}
.sqs-btn.active.sqs-btn-resuelto {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(16,185,129,0.35);
}
.sqs-btn.active.sqs-btn-cerrado {
    background: #64748b;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(100,116,139,0.35);
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
    background: linear-gradient(135deg, #475569, #1e293b);
    color: #ffffff;
}
.msg-avatar-tech {
    background: linear-gradient(135deg, #162660, #2563eb);
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
/* Bubble Propia (Soporte en admin = Azul Messenger) */
.bubble-me {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
    color: #ffffff;
    border-radius: 18px 18px 4px 18px;
    box-shadow: 0 4px 14px rgba(37,99,235,0.22);
}
.bubble-me .msg-text {
    color: #ffffff;
}
/* Bubble Interlocutor (Pasante = Blanco nítido con borde) */
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
    padding: 8px 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    flex-shrink: 0;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.chat-quick-replies::-webkit-scrollbar { display: none; }
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
    padding: 10px 14px;
    background: #ffffff;
    border-top: 1.5px solid #f1f5f9;
    display: flex;
    align-items: flex-end;
    gap: 8px;
    flex-shrink: 0;
}
.cib-input-wrap {
    flex: 1;
}
.messenger-input-bar textarea {
    width: 100%;
    border: 1.5px solid #cbd5e1;
    border-radius: 22px;
    padding: 9px 16px;
    font-size: 0.88rem;
    font-weight: 500;
    outline: none;
    color: #1e293b;
    resize: none;
    line-height: 1.4;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #f8fafc;
    box-sizing: border-box;
    overflow-y: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
    min-height: 40px;
    max-height: 110px;
}
.messenger-input-bar textarea::-webkit-scrollbar {
    display: none;
}
.messenger-input-bar textarea:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}
.btn-send-chat {
    padding: 9px 16px;
    border-radius: 20px;
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
    height: 40px;
}
.btn-send-chat:hover {
    transform: translateY(-1px);
}

/* ── Overrides Responsive Globales SGP ────────────────────────────── */
/* ── Reglas Base para componentes Desktop / Mobile Switch ── */
.mh-desktop-info { display: block; }
.mh-mobile-info { display: none; }
.desktop-status-switch { display: flex; }
.mobile-status-dropdown-wrap { display: none; }
.desktop-quick-replies { display: flex; }
.btn-quick-trigger { display: none; }
.mobile-quick-drawer { display: none; }

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
    .soporte-workspace {
        grid-template-columns: 310px 1fr !important;
        gap: 14px !important;
    }
    .soporte-sidebar-list {
        height: 680px !important;
        padding: 14px !important;
    }
    .soporte-detail-chat {
        height: 680px !important;
    }
    .banner-title {
        font-size: 1.4rem !important;
    }
}

/* ==========================================================================
   REFACTORIZACIÓN MOBILE-FIRST SOPORTE CHAT (≤ 768px) — ESTÁNDAR 2026
   ========================================================================== */
@media (max-width: 768px) {
    /* ── Estructura General en Móvil (Lista de Tickets) ── */
    .soporte-container {
        padding: 0 8px 24px !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .soporte-banner {
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 18px 16px !important;
        gap: 14px !important;
        border-radius: 16px !important;
    }
    .banner-main-group {
        width: 100% !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 12px !important;
    }
    .banner-icon-badge {
        width: 42px !important;
        height: 42px !important;
        font-size: 1.3rem !important;
        border-radius: 12px !important;
    }
    .banner-title {
        font-size: 1.22rem !important;
        line-height: 1.25 !important;
    }
    .banner-subtitle {
        font-size: 0.78rem !important;
    }
    .admin-banner-actions {
        width: 100% !important;
    }
    .admin-banner-actions button {
        width: 100% !important;
        justify-content: center !important;
    }
    .soporte-kpis {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    .asist-kpi-card {
        padding: 12px 10px !important;
        border-radius: 14px !important;
    }
    .asist-kpi-val { font-size: 1.6rem !important; }
    .asist-kpi-icon { width: 36px !important; height: 36px !important; font-size: 1.1rem !important; }

    .soporte-workspace {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        gap: 0 !important;
    }
    .soporte-workspace.no-selected-ticket .soporte-sidebar-list {
        display: flex !important;
        width: 100% !important;
        max-height: none !important;
        height: auto !important;
        min-height: 440px;
        padding: 14px 12px !important;
        box-sizing: border-box !important;
    }
    .soporte-workspace.no-selected-ticket .soporte-detail-chat {
        display: none !important;
    }
    .soporte-workspace.has-selected-ticket .soporte-sidebar-list {
        display: none !important;
    }
    .soporte-workspace.has-selected-ticket .soporte-detail-chat {
        display: flex !important;
    }

    /* ── 1. MODO FULLSCREEN CHAT ACTIVO (Oculta topbar, dock y libera 100% pantalla) ── */
    body.sgp-mobile-chat-active .main-header,
    body:has(.soporte-workspace.has-selected-ticket) .main-header,
    body.sgp-mobile-chat-active .sgp-mobile-dock,
    body:has(.soporte-workspace.has-selected-ticket) .sgp-mobile-dock,
    body.sgp-mobile-chat-active .sgp-dock-sheet,
    body:has(.soporte-workspace.has-selected-ticket) .sgp-dock-sheet,
    body.sgp-mobile-chat-active .sgp-dock-sheet-overlay,
    body:has(.soporte-workspace.has-selected-ticket) .sgp-dock-sheet-overlay,
    body.sgp-mobile-chat-active #mobileDock,
    body:has(.soporte-workspace.has-selected-ticket) #mobileDock,
    body.sgp-mobile-chat-active #sidebarToggle,
    body:has(.soporte-workspace.has-selected-ticket) #sidebarToggle,
    body.sgp-mobile-chat-active .sidebar-overlay,
    body:has(.soporte-workspace.has-selected-ticket) .sidebar-overlay,
    body.sgp-mobile-chat-active .soporte-banner,
    body:has(.soporte-workspace.has-selected-ticket) .soporte-banner,
    body.sgp-mobile-chat-active .soporte-kpis,
    body:has(.soporte-workspace.has-selected-ticket) .soporte-kpis {
        display: none !important;
    }
    body.sgp-mobile-chat-active .content-wrapper,
    body:has(.soporte-workspace.has-selected-ticket) .content-wrapper,
    body.sgp-mobile-chat-active .wrapper,
    body:has(.soporte-workspace.has-selected-ticket) .wrapper {
        padding: 0 !important;
        margin: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        overflow: hidden !important;
    }
    body.sgp-mobile-chat-active .soporte-container,
    body:has(.soporte-workspace.has-selected-ticket) .soporte-container,
    body.sgp-mobile-chat-active .soporte-workspace,
    body:has(.soporte-workspace.has-selected-ticket) .soporte-workspace {
        padding: 0 !important;
        margin: 0 !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
    }
    body.sgp-mobile-chat-active .soporte-sidebar-list,
    body:has(.soporte-workspace.has-selected-ticket) .soporte-sidebar-list {
        display: none !important;
    }
    body.sgp-mobile-chat-active #chatDetailSection,
    body:has(.soporte-workspace.has-selected-ticket) #chatDetailSection {
        position: fixed !important;
        inset: 0 !important;
        width: 100vw !important;
        height: 100dvh !important;
        z-index: 1050 !important;
        display: flex !important;
        flex-direction: column !important;
        background: var(--color-bg, #F0F5FA) !important;
        border-radius: 0 !important;
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* ── 2. APP BAR COMPACTA STICKY 56px ── */
    .messenger-header {
        height: 56px !important;
        min-height: 56px !important;
        max-height: 56px !important;
        padding: 0 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        background: #FFFFFF !important;
        border-bottom: 1px solid #E2E8F0 !important;
        z-index: 30 !important;
        flex-shrink: 0 !important;
        box-sizing: border-box !important;
        gap: 8px !important;
    }
    .mh-left {
        flex: 1 !important;
        min-width: 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
    .btn-back-mobile {
        width: 36px !important;
        height: 36px !important;
        border-radius: 10px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #f1f5f9 !important;
        border: 1px solid #cbd5e1 !important;
        color: #1e293b !important;
        cursor: pointer !important;
        flex-shrink: 0 !important;
        transition: background 0.15s !important;
    }
    .btn-back-mobile:active {
        background: #e2e8f0 !important;
    }
    .mh-avatar-wrap {
        position: relative !important;
        flex-shrink: 0 !important;
    }
    .mh-avatar {
        width: 34px !important;
        height: 34px !important;
        border-radius: 10px !important;
        font-size: 0.95rem !important;
    }
    .online-status-dot {
        width: 9px !important;
        height: 9px !important;
        bottom: -1px !important;
        right: -1px !important;
        border-width: 1.5px !important;
    }
    .mh-info {
        flex: 1 !important;
        min-width: 0 !important;
        margin: 0 4px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
    }
    .mh-desktop-info { display: none !important; }
    .mh-mobile-info {
        display: flex !important;
        flex-direction: column !important;
        min-width: 0 !important;
        justify-content: center !important;
    }
    .mh-mobile-title {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: var(--text-dark, #1E293B) !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        line-height: 1.25 !important;
    }
    .mh-mobile-sub {
        font-size: 12px !important;
        font-weight: 500 !important;
        color: var(--text-body, #64748B) !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        line-height: 1.25 !important;
        margin-top: 1px !important;
    }
    .chat-subject-bar {
        display: none !important;
    }

    /* ── Dropdown de Estado Compacto (Móvil) ── */
    .mh-right {
        flex-shrink: 0 !important;
    }
    .desktop-status-switch { display: none !important; }
    .mobile-status-dropdown-wrap {
        display: block !important;
        position: relative !important;
    }
    .mobile-status-pill-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        padding: 5px 9px !important;
        border-radius: 999px !important;
        font-size: 0.72rem !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        border: 1px solid transparent !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06) !important;
        transition: all 0.2s !important;
    }
    .mobile-status-pill-btn i {
        font-size: 0.75rem !important;
        margin-left: 1px !important;
    }
    .mobile-status-menu {
        position: absolute !important;
        top: calc(100% + 6px) !important;
        right: 0 !important;
        min-width: 155px !important;
        background: #ffffff !important;
        border-radius: 14px !important;
        box-shadow: 0 12px 30px rgba(15,23,42,0.18) !important;
        border: 1px solid #e2e8f0 !important;
        z-index: 100 !important;
        padding: 6px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 3px !important;
        animation: fadeInScale 0.18s ease-out !important;
    }
    .msm-option {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        width: 100% !important;
        padding: 7px 10px !important;
        border: none !important;
        background: transparent !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        cursor: pointer !important;
        text-align: left !important;
        transition: background 0.15s !important;
    }
    .msm-option:hover, .msm-option:active {
        background: #f1f5f9 !important;
    }
    .msm-option.active {
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        font-weight: 700 !important;
    }
    .msm-check-icon {
        margin-left: auto !important;
        font-size: 0.88rem !important;
        color: #2563eb !important;
    }

    /* ── 3. DRAWER DE RESPUESTAS RÁPIDAS (Reemplaza carrusel ruidoso) ── */
    .desktop-quick-replies { display: none !important; }
    .chat-quick-replies { display: none !important; }

    .btn-quick-trigger {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 38px !important;
        height: 38px !important;
        border-radius: 50% !important;
        background: #f8fafc !important;
        border: 1.5px solid #cbd5e1 !important;
        color: #d97706 !important;
        font-size: 1.15rem !important;
        cursor: pointer !important;
        flex-shrink: 0 !important;
        transition: all 0.2s !important;
        margin-bottom: 1px !important;
    }
    .btn-quick-trigger.active {
        background: #fef3c7 !important;
        border-color: #f59e0b !important;
        transform: scale(1.05) !important;
    }
    .mobile-quick-drawer {
        position: absolute !important;
        bottom: 60px !important;
        left: 10px !important;
        right: 10px !important;
        background: #ffffff !important;
        border-radius: 16px !important;
        box-shadow: 0 14px 40px rgba(15,23,42,0.2) !important;
        border: 1px solid #e2e8f0 !important;
        z-index: 50 !important;
        padding: 12px 14px !important;
        animation: drawerSlideUp 0.22s cubic-bezier(0.16,1,0.3,1) !important;
        max-height: 280px !important;
        overflow-y: auto !important;
        flex-direction: column !important;
    }
    .mqd-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 8px !important;
        padding-bottom: 6px !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .mqd-title {
        font-size: 0.82rem !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    .mqd-close-btn {
        background: none !important;
        border: none !important;
        color: #94a3b8 !important;
        font-size: 1.1rem !important;
        cursor: pointer !important;
        padding: 2px !important;
    }
    .mqd-list {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
    }
    .mqd-item {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        width: 100% !important;
        padding: 8px 10px !important;
        border: 1px solid #f1f5f9 !important;
        background: #f8fafc !important;
        border-radius: 10px !important;
        cursor: pointer !important;
        text-align: left !important;
        transition: all 0.15s !important;
    }
    .mqd-item:active {
        background: #eff6ff !important;
        border-color: #93c5fd !important;
    }
    .mqd-emoji {
        font-size: 1.1rem !important;
        flex-shrink: 0 !important;
    }
    .mqd-info {
        display: flex !important;
        flex-direction: column !important;
        min-width: 0 !important;
    }
    .mqd-label {
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        color: #1e293b !important;
    }
    .mqd-desc {
        font-size: 0.7rem !important;
        color: #64748b !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* ── 4. BURBUJAS Y FLUJO DE MENSAJES ESTILO WHATSAPP ── */
    .chat-messages-container {
        flex: 1 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        padding: 12px 14px !important;
        gap: 10px !important;
        background: var(--color-bg, #F0F5FA) !important;
        -webkit-overflow-scrolling: touch !important;
    }
    .msg-avatar-col {
        display: none !important;
    }
    .msg-bubble-wrap {
        max-width: 84% !important;
    }
    .msg-meta-line {
        font-size: 0.68rem !important;
        margin-bottom: 2px !important;
    }
    .msg-bubble {
        padding: 10px 14px !important;
        font-size: 0.86rem !important;
        border-radius: 16px !important;
        line-height: 1.4 !important;
    }
    .bubble-me {
        border-bottom-right-radius: 4px !important;
        border-bottom-left-radius: 16px !important;
    }
    .bubble-other {
        border-bottom-left-radius: 4px !important;
        border-bottom-right-radius: 16px !important;
    }

    /* ── BARRA DE ENTRADA COMPACTA FIJA ── */
    .messenger-input-bar {
        padding: 8px 12px !important;
        gap: 8px !important;
        background: #FFFFFF !important;
        border-top: 1px solid #E2E8F0 !important;
        align-items: flex-end !important;
        position: relative !important;
        flex-shrink: 0 !important;
    }
    .cib-input-wrap {
        flex: 1 !important;
    }
    .messenger-input-bar textarea {
        font-size: 0.88rem !important;
        padding: 9px 14px !important;
        border-radius: 20px !important;
        min-height: 38px !important;
        max-height: 90px !important;
    }
    .btn-send-chat {
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
        padding: 0 !important;
        border-radius: 50% !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
    }
    .btn-send-chat span {
        display: none !important;
    }
    .btn-send-chat i {
        font-size: 1.25rem !important;
        margin-left: 2px !important;
    }
}

@keyframes drawerSlideUp {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes fadeInScale {
    from { opacity: 0; transform: translateY(-6px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

<script>
// Estado del chat en cliente
const CURRENT_TICKET_ID = <?= $selectedTicket ? (int)$selectedTicket->id : 0 ?>;
const HAS_EXPLICIT_TICKET = <?= $hasExplicitTicket ? 'true' : 'false' ?>;
let lastMsgId = <?= !empty($mensajes) ? (int)end($mensajes)->id : 0 ?>;
let chatPollingTimer = null;

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

// Auto-expandir textarea de mensaje estilo WhatsApp (sin scrollbars internos)
function autoExpandChatInput(el) {
    el.style.height = 'auto';
    const maxH = 110;
    if (el.scrollHeight > maxH) {
        el.style.height = maxH + 'px';
        el.style.overflowY = 'auto';
    } else {
        el.style.height = Math.max(el.scrollHeight, 40) + 'px';
        el.style.overflowY = 'hidden';
    }
}

// Insertar plantilla de respuesta rápida
function insertarPlantilla(texto) {
    const txt = document.getElementById('chatInputTexto');
    if (!txt) return;
    txt.value = texto;
    autoExpandChatInput(txt);
    txt.focus();
}

// Scroll automático al fondo del chat
function scrollChatToBottom(smooth = false) {
    const el = document.getElementById('chatMessagesContainer');
    if (el) {
        if (smooth) {
            el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
        } else {
            el.scrollTop = el.scrollHeight;
        }
    }
}

// Volver al listado en móvil
function volverAListaAdmin() {
    document.body.classList.remove('sgp-mobile-chat-active');
    if (chatPollingTimer) {
        clearInterval(chatPollingTimer);
        chatPollingTimer = null;
    }
    if (window.chatPollingTimer) {
        clearInterval(window.chatPollingTimer);
        window.chatPollingTimer = null;
    }
    const params = new URLSearchParams(window.location.search);
    params.delete('id');
    const qs = params.toString();
    window.location.href = URLROOT + '/soporte' + (qs ? '?' + qs : '');
}

// Control de modo pantalla completa móvil (<= 768px)
function checkMobileChatActive() {
    if (window.innerWidth <= 768 && HAS_EXPLICIT_TICKET && CURRENT_TICKET_ID > 0) {
        document.body.classList.add('sgp-mobile-chat-active');
    } else {
        document.body.classList.remove('sgp-mobile-chat-active');
    }
}
// Ejecutar de inmediato al procesar el script y al completar navegaciones PJAX
checkMobileChatActive();
document.addEventListener('pjax:complete', checkMobileChatActive);

// Selector compacto de estado en móvil (Popover 4 estados)
function toggleMobileStatusDropdown(e) {
    if (e) {
        e.stopPropagation();
        e.preventDefault();
    }
    const menu = document.getElementById('mobileStatusMenu');
    if (!menu) return;
    const isHidden = (menu.style.display === 'none' || !menu.style.display);
    menu.style.display = isHidden ? 'flex' : 'none';
}

function cerrarMobileStatusDropdown() {
    const menu = document.getElementById('mobileStatusMenu');
    if (menu) menu.style.display = 'none';
}

function seleccionarEstadoMobile(ticketId, nuevoEstado) {
    cerrarMobileStatusDropdown();
    cambiarEstadoTicketDirecto(ticketId, nuevoEstado);
}

// Drawer de Respuestas Rápidas en móvil
function toggleQuickDrawer(e) {
    if (e) {
        e.stopPropagation();
        e.preventDefault();
    }
    const drawer = document.getElementById('mobileQuickDrawer');
    const trigger = document.getElementById('btnToggleQuickReplies');
    if (!drawer) return;
    const isHidden = (drawer.style.display === 'none' || !drawer.style.display);
    drawer.style.display = isHidden ? 'flex' : 'none';
    if (trigger) trigger.classList.toggle('active', isHidden);
}

function cerrarQuickDrawer() {
    const drawer = document.getElementById('mobileQuickDrawer');
    const trigger = document.getElementById('btnToggleQuickReplies');
    if (drawer) drawer.style.display = 'none';
    if (trigger) trigger.classList.remove('active');
}

function seleccionarPlantillaDrawer(texto) {
    cerrarQuickDrawer();
    insertarPlantilla(texto);
}

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

// Filtro activo actual para gestión dinámica de archivado
const CURRENT_ESTADO_FILTRO = '<?= htmlspecialchars($filtros['estado']) ?>';

async function enviarMensajeChat(e, ticketId) {
    if (e) e.preventDefault();
    const txtEl = document.getElementById('chatInputTexto');
    const msg = txtEl.value.trim();
    if (!msg) return;

    // ── 0ms Optimistic UI: vaciar campo inmediatamente y resetear altura ──
    txtEl.value = '';
    txtEl.style.height = 'auto';

    // Ocultar hint de chat vacío si existe
    const hint = document.getElementById('emptyChatHint');
    if (hint) hint.style.display = 'none';

    // Inyectar burbuja optimista inmediatamente en el contenedor (0 milisegundos de espera)
    const container = document.getElementById('chatMessagesContainer');
    const tempRowId = 'temp_msg_' + Date.now();
    const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const row = document.createElement('div');
    row.className = 'chat-msg-row msg-me';
    row.id = tempRowId;
    row.innerHTML = `
        <div class="msg-bubble-wrap">
            <div class="msg-meta-line">
                <span class="msg-author-name">
                    Tú
                    <span class="badge-role-tech"><i class="ti ti-shield-check"></i> Soporte Telemática</span>
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
        <div class="msg-avatar-col msg-avatar-right" title="Tú (Soporte)">
            <div class="msg-avatar msg-avatar-tech">
                <i class="ti ti-headset"></i>
            </div>
        </div>
    `;
    container.appendChild(row);
    scrollChatToBottom(true);
    txtEl.focus();

    // ── Petición en segundo plano (no bloquea al usuario) ──
    try {
        const resp = await fetch(URLROOT + '/soporte/mensaje', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: ticketId, mensaje: msg })
        });
        const res = await resp.json();
        if (res.success) {
            // Confirmación exitosa: actualizar id del mensaje y cambiar ícono a checks
            if (res.msg_id) {
                row.setAttribute('data-msg-id', res.msg_id);
                lastMsgId = Math.max(lastMsgId, parseInt(res.msg_id, 10));
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
            // Error devuelto por el servidor: retirar burbuja y restaurar texto en input
            row.remove();
            txtEl.value = msg;
            if (hint) hint.style.display = 'block';
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo enviar el mensaje.' });
        }
    } catch (err) {
        // Error de red: retirar burbuja y restaurar texto en input para que no se pierda
        row.remove();
        txtEl.value = msg;
        if (hint) hint.style.display = 'block';
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo conectar con el servidor. Tu mensaje no se envió.' });
    }
}

// Cambiar estado directo con Optimistic UI (0ms instantáneo + sincronización en segundo plano)
async function cambiarEstadoTicketDirecto(ticketId, nuevoEstado) {
    const estadoPrevio = document.getElementById('headerEstadoPill')?.textContent.trim() 
                      || document.getElementById('mobileStatusBtnText')?.textContent.trim() 
                      || '';

    if (estadoPrevio === nuevoEstado) return;

    // ── 1. ACTUALIZACIÓN VISUAL INMEDIATA (0 ms de espera) ──
    actualizarUIPorEstado(nuevoEstado);

    // Separador informativo inmediato en el chat
    const container = document.getElementById('chatMessagesContainer');
    let sysDiv = null;
    if (container) {
        sysDiv = document.createElement('div');
        sysDiv.className = 'chat-date-separator temp-status-sep';
        sysDiv.innerHTML = `<span>ℹ️ Has cambiado el estado del caso a: ${escapeHtml(nuevoEstado)}</span>`;
        container.appendChild(sysDiv);
        scrollChatToBottom(true);
    }

    // Alerta Toast instantánea (0 ms)
    const esCerrado = (nuevoEstado === 'Cerrado' || nuevoEstado === 'Resuelto');
    const toastTitulo = esCerrado ? `Caso archivado en Historial (${nuevoEstado})` : `Estado: ${nuevoEstado}`;

    if (window.Notyf) {
        if (!window._sgpNotyf) window._sgpNotyf = new Notyf({ duration: 2200, position: { x: 'right', y: 'top' } });
        window._sgpNotyf.success(toastTitulo);
    } else if (window.Swal) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: toastTitulo,
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        });
    }

    // ── 2. Petición en segundo plano (no bloquea al usuario) ──
    try {
        const resp = await fetch(URLROOT + '/soporte/actualizarEstado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: ticketId, estado: nuevoEstado })
        });
        const res = await resp.json();
        if (!res.success) {
            // Revertir ante error del backend
            if (estadoPrevio) actualizarUIPorEstado(estadoPrevio);
            if (sysDiv) sysDiv.remove();
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo actualizar el estado.' });
        }
    } catch (err) {
        // Revertir ante falla de red
        if (estadoPrevio) actualizarUIPorEstado(estadoPrevio);
        if (sysDiv) sysDiv.remove();
        Swal.fire({ icon: 'error', title: 'Error de Red', text: 'No se pudo conectar con el servidor. Se restauró el estado anterior.' });
    }
}

function actualizarUIPorEstado(nuevoEstado) {
    // 1. Actualizar botones de estado activos
    document.querySelectorAll('#sqsPillsContainer .sqs-btn').forEach(btn => {
        if (btn.getAttribute('data-estado') === nuevoEstado) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // 2. Actualizar píldora de cabecera
    const headerPill = document.getElementById('headerEstadoPill');
    if (headerPill) {
        headerPill.textContent = nuevoEstado;
        headerPill.className = 'mh-status-pill status-pill-' + nuevoEstado.toLowerCase().replace(/\s+/g, '-');
    }

    // 2b. Actualizar selector dropdown móvil compacto
    const mobileStatusBtnText = document.getElementById('mobileStatusBtnText');
    if (mobileStatusBtnText) {
        mobileStatusBtnText.textContent = nuevoEstado;
    }
    const mobileStatusTriggerBtn = document.getElementById('mobileStatusTriggerBtn');
    if (mobileStatusTriggerBtn) {
        mobileStatusTriggerBtn.className = 'mobile-status-pill-btn status-pill-' + nuevoEstado.toLowerCase().replace(/\s+/g, '-');
    }
    document.querySelectorAll('.msm-option').forEach(opt => {
        const isCur = (opt.getAttribute('data-estado') === nuevoEstado);
        opt.classList.toggle('active', isCur);
        const check = opt.querySelector('.msm-check-icon');
        if (check) check.style.display = isCur ? 'inline-block' : 'none';
    });

    // 3. Actualizar tarjeta en la lista izquierda y archivar si corresponde
    const card = document.querySelector(`.ticket-item-card[data-ticket-id="${CURRENT_TICKET_ID}"]`);
    if (card) {
        card.setAttribute('data-estado', nuevoEstado);
        const badge = card.querySelector('.tic-badge');
        if (badge) {
            badge.textContent = nuevoEstado;
            const badgeColors = {
                'Abierto': '#ef4444',
                'En Proceso': '#f59e0b',
                'Resuelto': '#10b981',
                'Cerrado': '#64748b'
            };
            badge.style.background = badgeColors[nuevoEstado] || '#2563eb';
        }

        // Si estamos viendo "Activos" (o "Abierto") y el caso pasó a "Cerrado" o "Resuelto",
        // animar su salida para mantener limpio el panel de trabajo activo
        const debeArchivar = (CURRENT_ESTADO_FILTRO === 'Activos' && (nuevoEstado === 'Cerrado' || nuevoEstado === 'Resuelto'))
                          || (CURRENT_ESTADO_FILTRO === 'Abierto' && nuevoEstado !== 'Abierto')
                          || (CURRENT_ESTADO_FILTRO === 'En Proceso' && nuevoEstado !== 'En Proceso');

        if (debeArchivar) {
            card.style.transition = 'all 0.4s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(-30px)';
            card.style.maxHeight = '0';
            card.style.paddingTop = '0';
            card.style.paddingBottom = '0';
            card.style.marginTop = '0';
            card.style.marginBottom = '0';
            card.style.overflow = 'hidden';
            setTimeout(() => {
                card.remove();
                const scrollList = document.querySelector('.tickets-scroll-list');
                if (scrollList && !scrollList.querySelector('.ticket-item-card')) {
                    scrollList.innerHTML = `
                        <div class="empty-tickets">
                            <i class="ti ti-circle-check" style="font-size: 2.2rem; color: #10b981; margin-bottom: 8px;"></i>
                            <p style="margin: 0; font-weight: 700; color: #1e293b;">¡Casos activos al día!</p>
                            <small style="color: #64748b;">No quedan más incidencias pendientes en esta vista.</small>
                        </div>
                    `;
                }
            }, 400);
        }
    }
}

// Polling en tiempo real (Estilo Steam / Messenger)
function iniciarPollingChat() {
    if (!CURRENT_TICKET_ID) return;

    if (chatPollingTimer) clearInterval(chatPollingTimer);

    chatPollingTimer = setInterval(async () => {
        try {
            const resp = await fetch(URLROOT + '/soporte/mensajesNuevos?ticket_id=' + CURRENT_TICKET_ID + '&after_id=' + lastMsgId);
            if (!resp.ok) return;
            const data = await resp.json();
            if (!data.success) return;

            // Actualizar estado si cambió
            if (data.estado) {
                const headerPill = document.getElementById('headerEstadoPill');
                if (headerPill && headerPill.textContent.trim() !== data.estado) {
                    actualizarUIPorEstado(data.estado);
                }
            }

            // Si hay mensajes nuevos
            if (data.mensajes && data.mensajes.length > 0) {
                const container = document.getElementById('chatMessagesContainer');
                const hint = document.getElementById('emptyChatHint');
                if (hint) hint.remove();

                data.mensajes.forEach(m => {
                    const msgId = parseInt(m.id, 10);
                    if (msgId <= lastMsgId) return;
                    lastMsgId = Math.max(lastMsgId, msgId);

                    const isSoporte = (m.remitente_tipo === 'soporte');
                    const row = document.createElement('div');
                    row.className = 'chat-msg-row ' + (isSoporte ? 'msg-me' : 'msg-other');
                    row.setAttribute('data-msg-id', msgId);

                    if (!isSoporte) {
                        row.innerHTML = `
                            <div class="msg-avatar-col" title="${escapeHtml(m.remitente_nombre)}">
                                <div class="msg-avatar msg-avatar-user">
                                    ${(m.remitente_nombre || 'U').charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div class="msg-bubble-wrap">
                                <div class="msg-meta-line">
                                    <span class="msg-author-name">
                                        ${escapeHtml(m.remitente_nombre)}
                                        <span class="badge-role-user"><i class="ti ti-user"></i> Solicitante</span>
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
                                        <span class="badge-role-tech"><i class="ti ti-shield-check"></i> Soporte Telemática</span>
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
                            <div class="msg-avatar-col msg-avatar-right" title="Tú (Soporte)">
                                <div class="msg-avatar msg-avatar-tech">
                                    <i class="ti ti-headset"></i>
                                </div>
                            </div>
                        `;
                    }
                    container.appendChild(row);
                });

                playChatChime();
                scrollChatToBottom(true);
            }
        } catch (e) {
            // Error silencioso en polling
        }
    }, 3500);

    window.chatPollingTimer = chatPollingTimer;
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

// Iniciar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    checkMobileChatActive();
    window.addEventListener('resize', checkMobileChatActive);

    // Cerrar dropdown de estado móvil y drawer al hacer click afuera
    document.addEventListener('click', (e) => {
        const statusWrap = document.getElementById('mobileStatusDropdownWrap');
        if (statusWrap && !statusWrap.contains(e.target)) {
            cerrarMobileStatusDropdown();
        }
        const drawer = document.getElementById('mobileQuickDrawer');
        const trigger = document.getElementById('btnToggleQuickReplies');
        if (drawer && trigger && !drawer.contains(e.target) && !trigger.contains(e.target)) {
            cerrarQuickDrawer();
        }
    });

    scrollChatToBottom(false);
    iniciarPollingChat();

    // Enfoque en móvil si hay ticket seleccionado
    if (window.innerWidth <= 900 && new URLSearchParams(window.location.search).has('id')) {
        const chatSection = document.getElementById('chatDetailSection');
        if (chatSection) {
            setTimeout(() => {
                chatSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 180);
        }
    }
});

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

