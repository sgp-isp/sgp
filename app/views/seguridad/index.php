<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">

    <!-- ========================================================
         1. HERO BANNER INSTITUCIONAL SGP / ISPEB
         ======================================================== -->
    <div class="module-banner" style="
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
        border-radius: 20px; padding: 32px 40px; margin-bottom: 28px;
        position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 20px;
        box-shadow: 0 12px 35px -5px rgba(22, 38, 96, 0.35);">

        <!-- Círculos decorativos de fondo con difuminado -->
        <div style="position: absolute; top: -35px; right: -35px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
        <div style="position: absolute; bottom: -50px; left: 20%; width: 180px; height: 180px; background: radial-gradient(circle, rgba(56,189,248,0.15) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>

        <!-- Lado Izquierdo: Icono + Título + Subtítulo con Badges Institucionales -->
        <div style="display: flex; align-items: center; gap: 20px; z-index: 1; flex: 1; min-width: 300px;">
            <div style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); border-radius: 18px; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #ffffff; flex-shrink: 0; backdrop-filter: blur(8px); box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                <i class="ti ti-shield-lock"></i>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                    <span style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25); border-radius: 50px; padding: 4px 14px; color: #ffffff; font-weight: 800; font-size: 0.72rem; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ti ti-building-hospital"></i> ISPEB &bull; SALUD BOLÍVAR
                    </span>
                    <span style="background: rgba(16,185,129,0.25); border: 1px solid rgba(16,185,129,0.4); border-radius: 50px; padding: 4px 14px; color: #a7f3d0; font-weight: 700; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
                        Regla Activa: 1 Teléfono = 1 Pasante
                    </span>
                </div>
                <h1 style="color: #ffffff; font-size: 1.85rem; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                    Centro de Seguridad Institucional
                </h1>
                <p style="color: rgba(255,255,255,0.85); margin: 0; font-size: 0.92rem; line-height: 1.45; max-width: 760px;">
                    Control antifraude en quiosco, gestión de biometría móvil, trazabilidad de credenciales y respaldo automatizado en la nube.
                </p>
            </div>
        </div>

        <!-- Lado Derecho: Botón de Respaldo a Gmail Oficial -->
        <div style="display: flex; gap: 12px; align-items: center; z-index: 1; flex-shrink: 0;">
            <button type="button" id="btnRespaldoNube" onclick="ejecutarRespaldoNube()" 
                    style="background: #ffffff; color: #1e3a8a; border: none; padding: 12px 24px; border-radius: 14px; font-weight: 700; font-size: 0.92rem; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); transition: all 0.2s;" 
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.22)'" 
                    onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.15)'">
                <i class="ti ti-cloud-upload" style="font-size: 1.25rem; color: #2563eb;"></i>
                <span>Respaldar a Gmail Ahora</span>
            </button>
        </div>
    </div>

    <!-- ========================================================
         2. TARJETAS KPI INSTITUCIONALES (ESTILO SGP)
         ======================================================== -->
    <div class="sec-kpi-grid">
        
        <!-- KPI 1: Teléfonos Vinculados QR -->
        <div class="sec-kpi-card sec-kpi-blue">
            <div class="sec-kpi-info">
                <span class="sec-kpi-label">Teléfonos QR Vinculados</span>
                <div class="sec-kpi-value"><?= (int)($data['stats']['total_dispositivos_qr'] ?? 0) ?></div>
                <div class="sec-kpi-sub" style="color: #2563eb;">
                    <i class="ti ti-device-mobile"></i> 1 Teléfono = 1 Pasante (Activo)
                </div>
            </div>
            <div class="sec-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                <i class="ti ti-device-mobile"></i>
            </div>
        </div>

        <!-- KPI 2: Huellas / Biometría Passkeys -->
        <div class="sec-kpi-card sec-kpi-green">
            <div class="sec-kpi-info">
                <span class="sec-kpi-label">Huellas Dactilares / Passkeys</span>
                <div class="sec-kpi-value"><?= (int)($data['stats']['total_biometria'] ?? 0) ?></div>
                <div class="sec-kpi-sub" style="color: #10b981;">
                    <i class="ti ti-fingerprint"></i> Criptografía FIDO2 / WebAuthn
                </div>
            </div>
            <div class="sec-kpi-icon" style="background: #f0fdf4; color: #10b981;">
                <i class="ti ti-fingerprint"></i>
            </div>
        </div>

        <!-- KPI 3: Alertas Antifraude (30 días) -->
        <?php 
            $hayAlertas = ($data['stats']['alertas_fraude'] ?? 0) > 0;
            $alertaClass = $hayAlertas ? 'sec-kpi-red' : 'sec-kpi-slate';
            $alertaColor = $hayAlertas ? '#dc2626' : '#64748b';
            $alertaBg    = $hayAlertas ? '#fef2f2' : '#f8fafc';
        ?>
        <div class="sec-kpi-card <?= $alertaClass ?>">
            <div class="sec-kpi-info">
                <span class="sec-kpi-label">Alertas Antifraude (30D)</span>
                <div class="sec-kpi-value" style="color: <?= $alertaColor ?>;">
                    <?= (int)($data['stats']['alertas_fraude'] ?? 0) ?>
                </div>
                <div class="sec-kpi-sub" style="color: <?= $alertaColor ?>;">
                    <i class="ti ti-shield-alert"></i> Suplantaciones y bloqueos repelidos
                </div>
            </div>
            <div class="sec-kpi-icon" style="background: <?= $alertaBg ?>; color: <?= $alertaColor ?>;">
                <i class="ti ti-alert-triangle"></i>
            </div>
        </div>

        <!-- KPI 4: Copia en Gmail Oficial -->
        <div class="sec-kpi-card sec-kpi-indigo">
            <div class="sec-kpi-info">
                <span class="sec-kpi-label">Copia en Gmail Oficial</span>
                <div class="sec-kpi-value" style="font-size: 1.1rem; line-height: 1.3; font-weight: 800; word-break: break-all; margin-top: 4px;">
                    <?= htmlspecialchars($data['mailConfig']['user'] ?: 'sgp.ispeb@gmail.com') ?>
                </div>
                <div class="sec-kpi-sub" style="color: #64748b;">
                    <?php if (!empty($data['stats']['ultimo_respaldo_nube'])): ?>
                        Último: <strong style="color: #10b981;"><?= date('d/m/Y H:i', strtotime($data['stats']['ultimo_respaldo_nube']->created_at)) ?></strong>
                    <?php else: ?>
                        Sin despachos previos a la nube
                    <?php endif; ?>
                </div>
            </div>
            <div class="sec-kpi-icon" style="background: #e0e7ff; color: #4f46e5;">
                <i class="ti ti-mail"></i>
            </div>
        </div>

    </div>

    <!-- ========================================================
         3. PANEL PRINCIPAL CON PESTAÑAS Y TABLAS SGP
         ======================================================== -->
    <div class="sec-main-panel">
        
        <!-- Barra de Pestañas con Pills Modernas -->
        <div class="sec-tabs-bar">
            <button type="button" class="sec-tab-btn active" onclick="switchSecTab('tab-dispositivos-qr', this)">
                <i class="ti ti-device-mobile"></i> Teléfonos Vinculados Kiosco (<?= count($data['dispositivosQr']) ?>)
            </button>
            <button type="button" class="sec-tab-btn" onclick="switchSecTab('tab-biometria', this)">
                <i class="ti ti-fingerprint"></i> Credenciales Biométricas (<?= count($data['dispositivosBio']) ?>)
            </button>
            <button type="button" class="sec-tab-btn" onclick="switchSecTab('tab-alertas', this)">
                <i class="ti ti-shield-alert"></i> Bitácora de Alertas y Eventos Críticos (<?= count($data['alertas']) ?>)
            </button>
        </div>

        <!-- TAB 1: Dispositivos QR (pasante_dispositivos) -->
        <div id="tab-dispositivos-qr" class="sec-tab-pane" style="padding: 24px;">
            <div class="sec-info-banner">
                <i class="ti ti-info-circle"></i>
                <div>
                    <strong>Regla de Exclusividad Estricta:</strong> Cada pasante solo puede tener <strong>1 teléfono vinculado</strong> para marcar asistencia en el quiosco. Si un pasante cambia de equipo o lo extravía, puedes desvincularlo aquí.
                </div>
            </div>

            <?php if (empty($data['dispositivosQr'])): ?>
                <div class="sec-empty-state">
                    <div class="sec-empty-icon"><i class="ti ti-devices-off"></i></div>
                    <h3>No hay teléfonos móviles vinculados aún</h3>
                    <p>Se registrarán automáticamente cuando los pasantes escaneen su primer QR en el quiosco institucional.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="sec-table">
                        <thead>
                            <tr>
                                <th>Pasante</th>
                                <th>Cédula</th>
                                <th>Modelo de Teléfono</th>
                                <th>IP Registro</th>
                                <th>Primer Marcaje</th>
                                <th>Último Uso</th>
                                <th style="text-align: right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['dispositivosQr'] as $d): ?>
                            <tr class="sec-row">
                                <td style="font-weight: 700; color: #0f172a;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="sec-avatar-circle">
                                            <?= strtoupper(substr($d->nombre_usuario ?: 'P', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <?= htmlspecialchars($d->nombre_usuario ?: 'Pasante #' . $d->usuario_id) ?>
                                            <div style="font-size: 0.74rem; color: #94a3b8; font-weight: normal;"><?= htmlspecialchars($d->correo ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: #475569; font-family: monospace; font-size: 0.85rem; font-weight: 600;">
                                    <?= htmlspecialchars($d->cedula ?? 'N/D') ?>
                                </td>
                                <td>
                                    <span class="sec-badge-phone">
                                        <i class="ti ti-device-mobile"></i>
                                        <?= htmlspecialchars($d->nombre_dispositivo ?: 'Android Phone') ?>
                                    </span>
                                </td>
                                <td style="color: #64748b; font-family: monospace; font-size: 0.8rem;">
                                    <?= htmlspecialchars($d->ip_registro ?: '127.0.0.1') ?>
                                </td>
                                <td style="color: #64748b; font-size: 0.82rem;">
                                    <?= date('d/m/Y H:i', strtotime($d->creado_at)) ?>
                                </td>
                                <td style="color: #10b981; font-size: 0.82rem; font-weight: 600;">
                                    <?= !empty($d->ultimo_uso_at) ? date('d/m/Y H:i', strtotime($d->ultimo_uso_at)) : 'N/D' ?>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="sec-btn-unlink" 
                                            onclick="desvincularDispositivo('qr', <?= (int)$d->id ?>, '<?= htmlspecialchars(addslashes($d->nombre_usuario ?: $d->cedula)) ?>', '<?= htmlspecialchars(addslashes($d->nombre_dispositivo)) ?>')">
                                        <i class="ti ti-unlink"></i> Desvincular
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: Credenciales Biométricas (usuarios_biometria) -->
        <div id="tab-biometria" class="sec-tab-pane" style="display: none; padding: 24px;">
            <div class="sec-info-banner sec-info-green">
                <i class="ti ti-fingerprint"></i>
                <div>
                    <strong>Credenciales FIDO2 / Passkeys:</strong> Claves criptográficas registradas desde lectores biométricos en equipos de usuarios. Cada clave es estrictamente exclusiva de su respectiva cuenta.
                </div>
            </div>

            <?php if (empty($data['dispositivosBio'])): ?>
                <div class="sec-empty-state">
                    <div class="sec-empty-icon" style="background: #f0fdf4; color: #10b981;"><i class="ti ti-fingerprint-off"></i></div>
                    <h3>Aún no hay usuarios con credencial biométrica enrolada</h3>
                    <p>Los usuarios pueden enrolar su huella desde su perfil para iniciar sesión sin contraseña.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="sec-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Cédula / Correo</th>
                                <th>Dispositivo Enrolado</th>
                                <th>ID Credencial</th>
                                <th>Fecha Enrolamiento</th>
                                <th style="text-align: right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['dispositivosBio'] as $b): ?>
                            <tr class="sec-row">
                                <td style="font-weight: 700; color: #0f172a;">
                                    <?= htmlspecialchars($b->nombre_usuario ?: 'Usuario #' . $b->usuario_id) ?>
                                </td>
                                <td style="color: #64748b; font-family: monospace;">
                                    <?= htmlspecialchars($b->cedula ?: $b->correo) ?>
                                </td>
                                <td>
                                    <span class="sec-badge-bio">
                                        <i class="ti ti-fingerprint"></i>
                                        <?= htmlspecialchars($b->device_name ?: 'Lector Biométrico') ?>
                                    </span>
                                </td>
                                <td style="color: #94a3b8; font-family: monospace; font-size: 0.78rem;">
                                    <?= htmlspecialchars(substr($b->credential_id, 0, 16)) ?>...
                                </td>
                                <td style="color: #64748b; font-size: 0.82rem;">
                                    <?= date('d/m/Y H:i', strtotime($b->creado_at)) ?>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="sec-btn-unlink" 
                                            onclick="desvincularDispositivo('biometria', <?= (int)$b->id ?>, '<?= htmlspecialchars(addslashes($b->nombre_usuario ?: $b->cedula)) ?>', '<?= htmlspecialchars(addslashes($b->device_name)) ?>')">
                                        <i class="ti ti-trash"></i> Revocar Huella
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 3: Bitácora de Alertas y Eventos Críticos -->
        <div id="tab-alertas" class="sec-tab-pane" style="display: none; padding: 24px;">
            <div class="sec-info-banner sec-info-slate">
                <i class="ti ti-clipboard-list"></i>
                <div>
                    <strong>Auditoría Forense en Tiempo Real:</strong> Registro de bloqueos antifraude, reseteos de PIN de quiosco, cambios de contraseña y despachos automáticos a la nube.
                </div>
            </div>

            <?php if (empty($data['alertas'])): ?>
                <div class="sec-empty-state">
                    <div class="sec-empty-icon" style="background: #f0fdf4; color: #10b981;"><i class="ti ti-shield-check"></i></div>
                    <h3>No se registran incidentes sospechosos</h3>
                    <p>La actividad del sistema se encuentra en estado óptimo sin anomalías detectadas.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="sec-table">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Evento Crítico</th>
                                <th>Usuario Implicado</th>
                                <th>Dirección IP</th>
                                <th>Detalles del Evento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['alertas'] as $a): 
                                $isFraude = in_array($a->accion, ['BLOQUEO_SUPLANTACION_DISPOSITIVO', 'BLOQUEO_DISPOSITIVO_NO_AUTORIZADO', 'INTENTO_SUPLANTACION_BIOMETRICA', 'INTENTO_DUPLICAR_BIOMETRIA', 'LOGIN_FALLIDO_BLOQUEO']);
                                $badgeClass = $isFraude ? 'sec-badge-fraud' : 'sec-badge-event';
                                if ($a->accion === 'RESPALDO_NUBE_EXITOSO') {
                                    $badgeClass = 'sec-badge-success';
                                }
                            ?>
                            <tr class="sec-row <?= $isFraude ? 'sec-row-fraud' : '' ?>">
                                <td style="font-size: 0.82rem; color: #64748b; white-space: nowrap; font-family: monospace;">
                                    <?= date('d/m/Y H:i:s', strtotime($a->created_at)) ?>
                                </td>
                                <td>
                                    <span class="sec-badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($a->accion) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?= htmlspecialchars($a->nombre_usuario ?: ($a->cedula ? 'C.I. ' . $a->cedula : 'Sistema / Anónimo')) ?>
                                </td>
                                <td style="color: #64748b; font-family: monospace; font-size: 0.82rem;">
                                    <?= htmlspecialchars($a->ip_address ?: '127.0.0.1') ?>
                                </td>
                                <td style="color: #475569; font-size: 0.82rem; max-width: 440px; word-break: break-word;">
                                    <?= htmlspecialchars($a->detalles ?: 'Sin detalles adicionales') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- ========================================================
     ESTILOS CSS COHERENTES CON LA APP SGP (LIGHT + DARK)
     ======================================================== -->
<style>
/* ── Grid KPI Estandarizada ── */
.sec-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}
@media (max-width: 1200px) {
    .sec-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}
@media (max-width: 640px) {
    .sec-kpi-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }
}

/* ── Tarjetas KPI ── */
.sec-kpi-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.sec-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.08);
}
.sec-kpi-blue   { border-left: 4px solid #2563eb !important; }
.sec-kpi-green  { border-left: 4px solid #10b981 !important; }
.sec-kpi-red    { border-left: 4px solid #ef4444 !important; }
.sec-kpi-indigo { border-left: 4px solid #6366f1 !important; }
.sec-kpi-slate  { border-left: 4px solid #64748b !important; }

.sec-kpi-info {
    flex: 1;
}
.sec-kpi-label {
    font-size: 0.74rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: block;
    margin-bottom: 4px;
}
.sec-kpi-value {
    font-size: 2.2rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 4px;
}
.sec-kpi-sub {
    font-size: 0.78rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}
.sec-kpi-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    flex-shrink: 0;
    transition: transform 0.2s;
}
.sec-kpi-card:hover .sec-kpi-icon {
    transform: scale(1.08);
}

/* ── Panel Principal ── */
.sec-main-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}

/* ── Barra de Pestañas ── */
.sec-tabs-bar {
    display: flex;
    gap: 8px;
    padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    overflow-x: auto;
}
.sec-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    border: 1.5px solid transparent;
    background: transparent;
    color: #64748b;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
}
.sec-tab-btn:hover {
    background: #edf2f7;
    color: #1e293b;
}
.sec-tab-btn.active {
    background: #162660;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(22,38,96,0.3);
}
.sec-tab-btn.active i {
    color: #60a5fa;
}

/* ── Banner Informativo ── */
.sec-info-banner {
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #1e3a8a;
    font-size: 0.88rem;
}
.sec-info-banner i {
    font-size: 1.35rem;
    color: #2563eb;
    flex-shrink: 0;
}
.sec-info-green {
    background: #f0fdf4 !important;
    border-color: #bbf7d0 !important;
    color: #14532d !important;
}
.sec-info-green i {
    color: #16a34a !important;
}
.sec-info-slate {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    color: #475569 !important;
}
.sec-info-slate i {
    color: #2563eb !important;
}

/* ── Tablas ── */
.sec-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 0.88rem;
}
.sec-table thead tr {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}
.sec-table th {
    padding: 14px 16px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.74rem;
    letter-spacing: 0.5px;
    color: #475569;
}
.sec-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.sec-row:hover {
    background: #f8fafc;
}
.sec-row-fraud {
    background: #fffbfa;
}
.sec-row-fraud:hover {
    background: #fef2f2 !important;
}

/* ── Elementos UI Tabla ── */
.sec-avatar-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    font-weight: 800;
    flex-shrink: 0;
}
.sec-badge-phone {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #eff6ff;
    color: #1d4ed8;
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    border: 1px solid #dbeafe;
}
.sec-badge-bio {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #f0fdf4;
    color: #166534;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.8rem;
    border: 1px solid #bbf7d0;
    font-weight: 600;
}
.sec-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 800;
}
.sec-badge-fraud {
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecdd3;
}
.sec-badge-event {
    color: #2563eb;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
}
.sec-badge-success {
    color: #16a34a;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}
.sec-btn-unlink {
    background: transparent;
    color: #dc2626;
    border: 1px solid #fecdd3;
    padding: 6px 14px;
    font-size: 0.78rem;
    font-weight: 700;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.sec-btn-unlink:hover {
    background: #fee2e2;
    border-color: #ef4444;
}

/* ── Estado Vacío ── */
.sec-empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #94a3b8;
}
.sec-empty-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 2.2rem;
    color: #94a3b8;
}
.sec-empty-state h3 {
    font-size: 1.1rem;
    color: #475569;
    margin: 0 0 6px;
}
.sec-empty-state p {
    font-size: 0.88rem;
    margin: 0;
}

/* ── Modo Oscuro SGP [data-theme="dark"] ── */
[data-theme="dark"] .sec-kpi-card,
[data-theme="dark"] .sec-main-panel {
    background: #1e293b !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .sec-kpi-value {
    color: #f8fafc !important;
}
[data-theme="dark"] .sec-tabs-bar {
    background: #0f172a !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .sec-tab-btn {
    color: #94a3b8 !important;
}
[data-theme="dark"] .sec-tab-btn:hover {
    background: #1e293b !important;
    color: #f8fafc !important;
}
[data-theme="dark"] .sec-tab-btn.active {
    background: #2563eb !important;
    color: #ffffff !important;
}
[data-theme="dark"] .sec-table thead tr {
    background: #0f172a !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .sec-table th {
    color: #94a3b8 !important;
}
[data-theme="dark"] .sec-table td {
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .sec-row:hover {
    background: #0f172a !important;
}
[data-theme="dark"] .sec-info-banner {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #93c5fd !important;
}
</style>

<script>
const SGP_CSRF_TOKEN = '<?= $data['csrf_token'] ?>';
const SGP_URLROOT    = '<?= URLROOT ?>';

// Cambio de pestañas
function switchSecTab(tabId, btn) {
    document.querySelectorAll('.sec-tab-pane').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.sec-tab-btn').forEach(b => b.classList.remove('active'));
    
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';
    if (btn) btn.classList.add('active');
}

// Desvincular dispositivo
function desvincularDispositivo(tipo, id, nombreUsuario, nombreDisp) {
    const titulo = (tipo === 'qr') ? '¿Desvincular Teléfono del Pasante?' : '¿Revocar Huella Dactilar?';
    const texto = `Se desvinculará '${nombreDisp}' del usuario '${nombreUsuario}'. El usuario podrá vincular su nuevo equipo la próxima vez.`;

    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="ti ti-unlink"></i> Sí, desvincular',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Procesando...',
            text: 'Desvinculando dispositivo en el servidor',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            formData.append('csrf_token', SGP_CSRF_TOKEN);
            formData.append('tipo', tipo);
            formData.append('id', id);

            const res = await fetch(`${SGP_URLROOT}/seguridad/desvincular`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Operación Exitosa!',
                    text: data.message,
                    confirmButtonColor: '#162660'
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo completar la desvinculación',
                    confirmButtonColor: '#162660'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Red',
                text: 'Hubo una falla de comunicación con el servidor.',
                confirmButtonColor: '#162660'
            });
        }
    });
}

// Ejecutar Respaldo a Gmail Inmediato
function ejecutarRespaldoNube() {
    Swal.fire({
        title: '¿Respaldar Base de Datos a Gmail?',
        text: 'Se generará un volcado MySQL íntegro, comprimido en ZIP, y se transmitirá como adjunto a sgp.ispeb@gmail.com.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="ti ti-cloud-upload"></i> Sí, enviar respaldo',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Generando y Transmitiendo...',
            html: '<p style="color:#64748b;font-size:0.9rem;">Extrayendo base de datos, empaquetando en ZIP y conectando por socket SMTP a Gmail...</p>',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            formData.append('csrf_token', SGP_CSRF_TOKEN);

            const res = await fetch(`${SGP_URLROOT}/seguridad/respaldoNube`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Respaldo en la Nube Completado!',
                    text: data.message,
                    confirmButtonColor: '#162660'
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Fallo de Respaldo',
                    text: data.message || 'Ocurrió un error al enviar el respaldo.',
                    confirmButtonColor: '#162660'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'La petición tardó demasiado o el servidor se desconectó.',
                confirmButtonColor: '#162660'
            });
        }
    });
}
</script>
