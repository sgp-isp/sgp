<?php
/**
 * Vista: Gestión de Alertas y Notificaciones por Correo Electrónico
 * Sistema de Gestión de Pasantes (SGP) — ISPEB
 */
?>
<div class="dashboard-container" style="margin-top:20px;padding-bottom:50px;">

    <!-- ── CABECERA BENTO DEL MÓDULO ──────────────────────────────────────── -->
    <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);border-radius:18px;padding:26px 30px;color:#fff;border:1.5px solid #334155;box-shadow:0 12px 35px rgba(0,0,0,0.25);position:relative;overflow:hidden;margin-bottom:24px;">
        <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle, rgba(59,130,246,0.25) 0%, transparent 70%);border-radius:50%;pointer-events:none;"></div>
        
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;z-index:2;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg, #2563eb, #1d4ed8);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#ffffff;box-shadow:0 8px 20px rgba(37,99,235,0.4);">
                    <i class="ti ti-mail-cog"></i>
                </div>
                <div>
                    <h1 style="font-size:1.45rem;font-weight:800;color:#ffffff;margin:0 0 4px;letter-spacing:-0.3px;">
                        Gestión de Alertas y Notificaciones por Correo
                    </h1>
                    <p style="font-size:0.85rem;color:#94a3b8;margin:0;">
                        Control granular de eventos automáticos, filtros por rol y listas de exclusión institucional
                    </p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
                <a href="#smtp_card" class="btn" style="background:rgba(255,255,255,0.1);color:#ffffff;border:1px solid rgba(255,255,255,0.2);padding:9px 16px;border-radius:11px;font-size:0.84rem;font-weight:700;display:inline-flex;align-items:center;gap:7px;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='rgba(255,255,255,0.18)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <i class="ti ti-server-cog" style="color:#60a5fa;"></i> Servidor SMTP
                </a>
            </div>
        </div>
    </div>

    <!-- ── TARJETAS KPI RESUMEN ───────────────────────────────────────────── -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(230px, 1fr));gap:16px;margin-bottom:26px;">
        <!-- KPI 1: Eventos Configurados -->
        <div style="background:#ffffff;border-radius:14px;padding:18px 20px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.03);border-left:4px solid #3b82f6;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Tipos de Eventos</div>
                <div style="font-size:1.75rem;font-weight:800;color:#0f172a;margin-top:2px;"><?= $totalReglas ?></div>
                <div style="font-size:0.75rem;color:#3b82f6;font-weight:600;margin-top:2px;">Reglas del sistema</div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:1.35rem;">
                <i class="ti ti-bell-ringing"></i>
            </div>
        </div>

        <!-- KPI 2: Eventos Activos -->
        <div style="background:#ffffff;border-radius:14px;padding:18px 20px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.03);border-left:4px solid #10b981;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Notificaciones Activas</div>
                <div style="font-size:1.75rem;font-weight:800;color:#0f172a;margin-top:2px;"><?= $activasReglas ?> <span style="font-size:0.85rem;color:#64748b;font-weight:500;">/ <?= $totalReglas ?></span></div>
                <div style="font-size:0.75rem;color:#10b981;font-weight:600;margin-top:2px;">Habilitadas para despacho</div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#10b981;font-size:1.35rem;">
                <i class="ti ti-checkbox"></i>
            </div>
        </div>

        <!-- KPI 3: Despachos Hoy -->
        <div style="background:#ffffff;border-radius:14px;padding:18px 20px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.03);border-left:4px solid #8b5cf6;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Despachados Hoy</div>
                <div style="font-size:1.75rem;font-weight:800;color:#0f172a;margin-top:2px;"><?= $totalHoy ?></div>
                <div style="font-size:0.75rem;color:#8b5cf6;font-weight:600;margin-top:2px;">Notificaciones emitidas</div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:#f5f3ff;display:flex;align-items:center;justify-content:center;color:#8b5cf6;font-size:1.35rem;">
                <i class="ti ti-send"></i>
            </div>
        </div>

        <!-- KPI 4: Estado del Motor SMTP -->
        <div style="background:#ffffff;border-radius:14px;padding:18px 20px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.03);border-left:4px solid <?= !empty($smtpConfig['activo']) ? '#059669' : '#f59e0b' ?>;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Servicio SMTP</div>
                <div style="font-size:1.15rem;font-weight:800;color:<?= !empty($smtpConfig['activo']) ? '#059669' : '#d97706' ?>;margin-top:5px;">
                    <?= !empty($smtpConfig['activo']) ? 'En Línea (SMTP Activo)' : 'Modo Simulación' ?>
                </div>
                <div style="font-size:0.73rem;color:#64748b;margin-top:2px;">
                    <?= htmlspecialchars($smtpConfig['host'] ?? 'Servidor pendiente') ?>
                </div>
            </div>
            <div style="width:44px;height:44px;border-radius:10px;background:<?= !empty($smtpConfig['activo']) ? '#f0fdf4' : '#fffbeb' ?>;display:flex;align-items:center;justify-content:center;color:<?= !empty($smtpConfig['activo']) ? '#059669' : '#d97706' ?>;font-size:1.35rem;">
                <i class="ti <?= !empty($smtpConfig['activo']) ? 'ti-check' : 'ti-alert-triangle' ?>"></i>
            </div>
        </div>
    </div>

    <!-- ── SECCIÓN: CONFIGURACIÓN DEL SERVIDOR SMTP / API REST ── -->
    <?php 
    $userRoleId = (int)Session::get('role_id');
    $canConfigureSmtp = in_array($userRoleId, [0, 1], true); 
    ?>
    <div id="smtp_card" style="background:#ffffff;border-radius:16px;border:1.5px solid <?= $canConfigureSmtp ? '#2563eb' : '#e2e8f0' ?>;box-shadow:0 10px 30px rgba(0,0,0,0.04);margin-bottom:30px;overflow:hidden;">
        
        <!-- Cabecera de la Tarjeta SMTP -->
        <div style="background:<?= $canConfigureSmtp ? 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)' : '#f8fafc' ?>;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;border-bottom:1px solid #e2e8f0;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:<?= $canConfigureSmtp ? 'linear-gradient(135deg, #2563eb, #1d4ed8)' : '#e2e8f0' ?>;display:flex;align-items:center;justify-content:center;color:<?= $canConfigureSmtp ? '#ffffff' : '#64748b' ?>;font-size:1.3rem;">
                    <i class="ti <?= $canConfigureSmtp ? 'ti-server-cog' : 'ti-lock' ?>"></i>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <h2 style="font-size:1.1rem;font-weight:800;color:<?= $canConfigureSmtp ? '#ffffff' : '#0f172a' ?>;margin:0;">
                            Servidor de Correo Institucional (SMTP / API REST)
                        </h2>
                        <?php if ($canConfigureSmtp): ?>
                            <span style="background:rgba(59,130,246,0.2);color:#93c5fd;border:1px solid rgba(59,130,246,0.4);padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;">
                                <i class="ti ti-shield-check me-1"></i> Control de Administración
                            </span>
                        <?php else: ?>
                            <span style="background:#fef3c7;color:#b45309;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;">
                                <i class="ti ti-lock me-1"></i> Protegido
                            </span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:0.80rem;color:<?= $canConfigureSmtp ? '#94a3b8' : '#64748b' ?>;margin:3px 0 0;">
                        <?= $canConfigureSmtp ? 'Configura las credenciales de salida (Gmail, Brevo API, SMTP institucional) y valida el envío de plantillas' : 'Visualización restringida. Solo administradores pueden editar credenciales de salida.' ?>
                    </p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
                <?php if (!empty($smtpConfig['activo'])): ?>
                    <span style="background:#dcfce7;color:#15803d;padding:6px 14px;border-radius:30px;font-size:0.78rem;font-weight:800;display:inline-flex;align-items:center;gap:6px;">
                        <i class="ti ti-circle-check"></i> Servicio Habilitado y en Línea
                    </span>
                <?php else: ?>
                    <span style="background:#fef3c7;color:#b45309;padding:6px 14px;border-radius:30px;font-size:0.78rem;font-weight:800;display:inline-flex;align-items:center;gap:6px;">
                        <i class="ti ti-alert-triangle"></i> Modo Simulación (Logs)
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canConfigureSmtp): ?>
            <!-- Formulario para Administradores y Super Administradores -->
            <form method="POST" action="<?= URLROOT ?>/notificaciones/guardarSmtp" id="formSmtpModulo" style="padding:24px;">
                <input type="hidden" name="_csrf" value="<?= class_exists('CsrfHelper') ? CsrfHelper::generate() : '' ?>">

                <!-- Banner Informativo: Compatibilidad con Render Free y la Nube -->
                <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;">
                    <i class="ti ti-cloud-lock" style="color:#2563eb;font-size:1.45rem;flex-shrink:0;margin-top:2px;"></i>
                    <div style="font-size:0.83rem;color:#1e3a8a;line-height:1.55;">
                        <strong>🚀 ¿Despliegue en Render.com (Plan Gratuito) o Servidor en la Nube?</strong><br>
                        Render en su plan gratuito bloquea los puertos SMTP salientes (25, 465 y 587) por políticas de red. Para enviar correos en Render sin restricciones ni costos, el sistema cuenta con <strong>despacho directo vía HTTPS (Puerto 443)</strong>:
                        <br>&bull; Ingresa tu <strong>Clave de API de Brevo</strong> (inicia con <code style="background:#dbeafe;padding:1px 5px;border-radius:4px;font-weight:700;">xkeysib-...</code>) en el campo <em>Contraseña / API Key</em>.
                        <br>&bull; El sistema detectará la clave automáticamente y transmitirá mediante la API REST de Brevo sobre HTTPS, garantizando un 100% de entrega inmediata.
                    </div>
                </div>

                <!-- Toggle Habilitar Transmisión -->
                <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div>
                        <div style="font-weight:700;font-size:0.92rem;color:#0f172a;display:flex;align-items:center;gap:8px;">
                            <i class="ti ti-send" style="color:#2563eb;"></i> Habilitar Transmisión Real de Correos
                        </div>
                        <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">
                            Si está activo, el sistema enviará correos reales a través de <strong><?= htmlspecialchars($smtpConfig['user'] ?: 'Servidor Configurado') ?></strong>. Si se pausa, se guardarán en auditoría para pruebas sin consumir cuota.
                        </div>
                    </div>
                    <label class="cfg-toggle-wrap" style="position:relative;display:inline-block;width:48px;height:26px;cursor:pointer;margin:0;">
                        <input type="checkbox" name="smtp_activo" value="1" <?= !empty($smtpConfig['activo']) ? 'checked' : '' ?> style="cursor:pointer;">
                    </label>
                </div>

                <!-- Grid de Parámetros -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(230px, 1fr));gap:16px;margin-bottom:20px;">
                    <!-- Host -->
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Servidor / Host SMTP *</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars($smtpConfig['host'] ?? 'smtp-relay.brevo.com') ?>" required class="form-control" style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.88rem;" placeholder="smtp-relay.brevo.com o smtp.gmail.com">
                    </div>

                    <!-- Puerto -->
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Puerto SMTP *</label>
                        <input type="number" name="smtp_port" value="<?= (int)($smtpConfig['port'] ?? 587) ?>" required class="form-control" style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.88rem;" placeholder="587">
                    </div>

                    <!-- Seguridad -->
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Protocolo de Cifrado *</label>
                        <select name="smtp_secure" class="form-control" style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.88rem;cursor:pointer;">
                            <option value="tls" <?= strtolower($smtpConfig['secure'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (STARTTLS - Recomendado Puerto 587)</option>
                            <option value="ssl" <?= strtolower($smtpConfig['secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (SMTPS - Puerto 465)</option>
                        </select>
                    </div>

                    <!-- Usuario -->
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Usuario / Correo Autenticado *</label>
                        <input type="text" name="smtp_user" value="<?= htmlspecialchars($smtpConfig['user'] ?? '') ?>" class="form-control" style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.88rem;" placeholder="ej: luisgabrielyanez9@gmail.com">
                    </div>

                    <!-- Contraseña / API Key -->
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Contraseña / Clave de API Brevo (xkeysib-...) *</label>
                        <div style="position:relative;">
                            <input type="password" name="smtp_pass" id="modulo_smtp_pass" value="<?= htmlspecialchars($smtpConfig['pass'] ?? '') ?>" class="form-control" style="width:100%;padding:10px 42px 10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.88rem;" placeholder="Clave SMTP o Brevo API Key (xkeysib-...)">
                            <i class="ti ti-eye" id="toggleModuloPassIcon" onclick="toggleSmtpPass('modulo_smtp_pass', 'toggleModuloPassIcon')" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;font-size:1.15rem;"></i>
                        </div>
                    </div>

                    <!-- Nombre Remitente -->
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Nombre Remitente Oficial *</label>
                        <input type="text" name="mail_from_name" value="<?= htmlspecialchars($smtpConfig['from_name'] ?? 'SGP - Instituto de Salud Pública') ?>" class="form-control" style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.88rem;" placeholder="SGP - Instituto de Salud Pública">
                    </div>

                    <!-- Correo Remitente -->
                    <div style="grid-column:1 / -1;">
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Correo Electrónico Remitente Oficial *</label>
                        <input type="email" name="mail_from_email" value="<?= htmlspecialchars($smtpConfig['from_email'] ?? 'sgp.ispeb@gmail.com') ?>" class="form-control" style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.88rem;" placeholder="sgp.ispeb@gmail.com">
                    </div>
                </div>

                <!-- Barra de Acciones y Prueba en Vivo -->
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;padding-top:18px;border-top:1.5px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:300px;">
                        <input type="email" id="moduloEmailPruebaSmtp" placeholder="Ingresa correo de prueba..." class="form-control" style="flex:1;padding:10px 14px;border-radius:10px;border:1.5px solid #cbd5e1;font-size:0.86rem;" value="<?= htmlspecialchars(Session::get('user_email') ?? 'sgp.ispeb@gmail.com') ?>">
                        <button type="button" onclick="ejecutarPruebaSmtpModulo()" id="btnModuloProbarSmtp" style="padding:10px 18px;background:#f8fafc;border:1.5px solid #cbd5e1;color:#1e293b;border-radius:10px;font-size:0.84rem;font-weight:700;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:7px;transition:all .2s;">
                            <i class="ti ti-send" style="color:#2563eb;"></i> Enviar Correo Diagnóstico
                        </button>
                    </div>

                    <button type="submit" style="padding:11px 26px;background:linear-gradient(135deg, #1e3a8a, #2563eb);color:#ffffff;border:none;border-radius:10px;font-size:0.88rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 12px rgba(37,99,235,0.3);transition:all .2s;">
                        <i class="ti ti-device-floppy"></i> Guardar Parámetros de Correo
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- Vista Informativa para Administradores no-Superadmin -->
            <div style="padding:28px 24px;display:flex;align-items:center;gap:18px;">
                <div style="width:50px;height:50px;border-radius:14px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:1.6rem;flex-shrink:0;">
                    <i class="ti ti-shield-lock"></i>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;font-size:0.98rem;font-weight:800;color:#0f172a;">Configuración de Servidor Protegida</h3>
                    <p style="margin:0;font-size:0.84rem;color:#64748b;line-height:1.5;">
                        Por directrices de seguridad de la Dirección de Informática y Telemática, las credenciales, contraseñas de aplicación y puertos del servidor de correo institucional solo pueden ser manipuladas directamente por la cuenta del <strong>Super Administrador</strong>. Usted tiene acceso completo a la gestión de reglas, asignación de roles destinatarios y auditoría de envíos que se muestran abajo.
                    </p>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- ── CONTROL MAESTRO GLOBAL: SISTEMA DE EXCLUSIONES DE CORREO ── -->
    <div style="background:#ffffff;border-radius:16px;border:1.5px solid <?= !empty($globalExclusiones) ? '#fecdd3' : '#e2e8f0' ?>;padding:16px 22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:24px;box-shadow:0 3px 12px rgba(0,0,0,0.02);transition:all .2s;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:42px;height:42px;border-radius:12px;background:<?= !empty($globalExclusiones) ? '#fff1f2' : '#f1f5f9' ?>;color:<?= !empty($globalExclusiones) ? '#e11d48' : '#64748b' ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                <i class="ti ti-user-x"></i>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <h3 style="font-size:0.98rem;font-weight:800;color:#0f172a;margin:0;">Control Maestro de Correos Excluidos</h3>
                    <span id="badge_global_status" style="font-size:0.70rem;font-weight:800;padding:2px 8px;border-radius:999px;background:<?= !empty($globalExclusiones) ? '#dcfce7' : '#fee2e2' ?>;color:<?= !empty($globalExclusiones) ? '#15803d' : '#b91c1c' ?>;">
                        <?= !empty($globalExclusiones) ? 'ACTIVO' : 'PAUSADO (IGNORADO)' ?>
                    </span>
                </div>
                <p style="font-size:0.77rem;color:#64748b;margin:3px 0 0;">
                    Switch global: Activa o suspende el bloqueo hacia los usuarios marcados en listas de exclusión institucional.
                </p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <span id="label_global_exclusiones" style="font-size:0.80rem;font-weight:700;color:<?= !empty($globalExclusiones) ? '#059669' : '#dc2626' ?>;">
                <?= !empty($globalExclusiones) ? 'Exclusiones Habilitadas' : 'Exclusiones Deshabilitadas (Enviar a todos)' ?>
            </span>
            <label class="cfg-toggle-wrap cfg-toggle-danger" style="margin:0;" title="Activar/Desactivar el sistema de exclusiones de correos a nivel global">
                <input type="checkbox" id="toggle_global_exclusiones" <?= !empty($globalExclusiones) ? 'checked' : '' ?> onchange="cambiarEstadoExclusionesGlobal(this.checked)">
                <span class="cfg-toggle-track"></span>
                <span class="cfg-toggle-dot"></span>
            </label>
        </div>
    </div>

    <!-- ── GRID DE REGLAS DE CORREO ───────────────────────────────────────── -->
    <div style="margin-bottom:34px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div>
                <h2 style="font-size:1.15rem;font-weight:800;color:#0f172a;margin:0 0 2px;">Reglas de Notificación por Evento</h2>
                <p style="font-size:0.80rem;color:#64748b;margin:0;">Personaliza qué eventos generan correos, a qué roles van dirigidos y activa/pausa exclusiones puntuales</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(360px, 1fr));gap:18px;">
            <?php foreach ($reglas as $r): 
                $catIcon = 'ti-bell';
                $catColor = '#2563eb';
                $catBg = '#eff6ff';
                if ($r->categoria === 'academico') {
                    $catIcon = 'ti-notebook';
                    $catColor = '#16a34a';
                    $catBg = '#f0fdf4';
                } elseif ($r->categoria === 'seguridad') {
                    $catIcon = 'ti-shield-lock';
                    $catColor = '#ea580c';
                    $catBg = '#fff7ed';
                } elseif ($r->categoria === 'asistencia') {
                    $catIcon = 'ti-clock-check';
                    $catColor = '#4f46e5';
                    $catBg = '#eef2ff';
                }

                $cantExcluidos = count($r->usuarios_excluidos_arr);
                $cantIncluidos = count($r->usuarios_incluidos_arr);
                $excActivas = isset($r->exclusiones_activas) ? (int)$r->exclusiones_activas : 1;
            ?>
            <div class="mail-rule-card" id="card_rule_<?= (int)$r->id ?>" style="background:#ffffff;border-radius:16px;border:1.5px solid #e2e8f0;padding:20px;box-shadow:0 3px 12px rgba(0,0,0,0.03);display:flex;flex-direction:column;justify-content:space-between;transition:all .2s;">
                
                <div>
                    <!-- Header Card -->
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:<?= $catBg ?>;color:<?= $catColor ?>;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
                                <i class="ti <?= $catIcon ?>"></i>
                            </div>
                            <div>
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.68rem;font-weight:800;text-transform:uppercase;background:<?= $catBg ?>;color:<?= $catColor ?>;margin-bottom:3px;">
                                    <?= htmlspecialchars($r->categoria) ?>
                                </span>
                                <h3 style="font-size:0.95rem;font-weight:800;color:#0f172a;margin:0;line-height:1.3;">
                                    <?= htmlspecialchars($r->titulo) ?>
                                </h3>
                            </div>
                        </div>

                        <!-- Toggle Switch de la Regla General -->
                        <label class="cfg-toggle-wrap" style="margin:0;flex-shrink:0;" title="Activar/Pausar este tipo de correo">
                            <input type="checkbox" id="toggle_<?= (int)$r->id ?>" <?= !empty($r->activo) ? 'checked' : '' ?> onchange="cambiarEstadoRegla(<?= (int)$r->id ?>, this.checked)">
                            <span class="cfg-toggle-track"></span>
                            <span class="cfg-toggle-dot"></span>
                        </label>
                    </div>

                    <!-- Descripción -->
                    <p style="font-size:0.80rem;color:#64748b;line-height:1.45;margin:0 0 14px;min-height:36px;">
                        <?= htmlspecialchars($r->descripcion) ?>
                    </p>

                    <!-- Roles Destinatarios Badges -->
                    <div style="margin-bottom:12px;">
                        <span style="font-size:0.72rem;font-weight:700;color:#475569;display:block;margin-bottom:5px;">Roles que reciben este correo:</span>
                        <div style="display:flex;flex-wrap:wrap;gap:5px;">
                            <?php if (in_array('3', $r->roles_arr, true)): ?>
                                <span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="ti ti-user-check me-1"></i>Pasantes</span>
                            <?php endif; ?>
                            <?php if (in_array('2', $r->roles_arr, true)): ?>
                                <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="ti ti-user-star me-1"></i>Tutores</span>
                            <?php endif; ?>
                            <?php if (in_array('1', $r->roles_arr, true)): ?>
                                <span style="background:#f3e8ff;color:#6b21a8;padding:2px 8px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="ti ti-shield me-1"></i>Administradores</span>
                            <?php endif; ?>
                            <?php if (in_array('0', $r->roles_arr, true)): ?>
                                <span style="background:#fce7f3;color:#9d174d;padding:2px 8px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="ti ti-crown me-1"></i>SuperAdmin</span>
                            <?php endif; ?>
                            <?php if (empty($r->roles_arr)): ?>
                                <span style="background:#f1f5f9;color:#94a3b8;padding:2px 8px;border-radius:6px;font-size:0.72rem;font-weight:600;">Ningún rol seleccionado</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Fila con Switch de Exclusiones de la Regla -->
                    <div style="background:#f8fafc;border-radius:12px;padding:9px 12px;margin-bottom:14px;border:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="display:flex;align-items:center;gap:6px;min-width:0;flex-wrap:wrap;">
                            <i class="ti ti-user-x" style="color:<?= $excActivas === 1 ? '#e11d48' : '#94a3b8' ?>;font-size:0.95rem;"></i>
                            <span style="font-size:0.75rem;font-weight:700;color:#334155;white-space:nowrap;">
                                Excluidos (<?= $cantExcluidos ?>):
                            </span>
                            <span id="badge_exc_status_<?= (int)$r->id ?>" style="font-size:0.68rem;font-weight:800;padding:2px 7px;border-radius:6px;background:<?= $excActivas === 1 ? '#ffe4e6' : '#f1f5f9' ?>;color:<?= $excActivas === 1 ? '#be123c' : '#64748b' ?>;">
                                <?= $excActivas === 1 ? 'Activo' : 'Pausado' ?>
                            </span>
                            <?php if ($cantIncluidos > 0): ?>
                                <span style="font-size:0.68rem;font-weight:800;padding:2px 7px;border-radius:6px;background:#dcfce7;color:#15803d;" title="Inclusiones forzadas">
                                    +<?= $cantIncluidos ?> forzados
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Switch de Exclusión de la Regla -->
                        <div style="display:flex;align-items:center;gap:6px;">
                            <label class="cfg-toggle-wrap cfg-toggle-sm cfg-toggle-danger" style="margin:0;flex-shrink:0;" title="Activar o desactivar filtro de excluidos para este evento">
                                <input type="checkbox" id="toggle_exc_<?= (int)$r->id ?>" <?= $excActivas === 1 ? 'checked' : '' ?> onchange="cambiarEstadoExclusionesRegla(<?= (int)$r->id ?>, this.checked)">
                                <span class="cfg-toggle-track"></span>
                                <span class="cfg-toggle-dot"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer Botones -->
                <div style="display:flex;align-items:center;gap:8px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    <button type="button" onclick="abrirModalDestinatarios(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" style="flex:1;padding:8px 12px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:9px;color:#1e293b;font-size:0.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                        <i class="ti ti-users-cog" style="color:#2563eb;"></i> Destinatarios
                    </button>
                    <button type="button" onclick="probarEnvioRegla('<?= htmlspecialchars($r->codigo_evento) ?>', '<?= htmlspecialchars($r->titulo) ?>')" style="padding:8px 12px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:9px;color:#475569;font-size:0.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:all .2s;" title="Enviar correo de prueba a mi correo">
                        <i class="ti ti-send" style="color:#10b981;"></i> Probar
                    </button>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── TABLA DE HISTORIAL / AUDITORÍA DE ENVÍOS ──────────────────────── -->
    <div style="background:#ffffff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 4px 15px rgba(0,0,0,0.03);">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:9px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#1e293b;font-size:1.2rem;">
                    <i class="ti ti-history"></i>
                </div>
                <div>
                    <h3 style="font-size:1.05rem;font-weight:800;color:#0f172a;margin:0;">Bitácora de Despachos de Correo</h3>
                    <p style="font-size:0.78rem;color:#64748b;margin:0;">Últimas transmisiones procesadas por el despachador SMTP institucional</p>
                </div>
            </div>

            <button type="button" onclick="window.location.reload()" style="padding:7px 14px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:9px;font-size:0.78rem;font-weight:700;color:#334155;cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                <i class="ti ti-refresh"></i> Actualizar
            </button>
        </div>

        <div class="table-responsive" style="overflow-x:auto;">
            <table class="table" style="width:100%;border-collapse:collapse;font-size:0.83rem;">
                <thead>
                    <tr style="border-bottom:2px solid #e2e8f0;background:#f8fafc;text-align:left;color:#475569;font-weight:700;font-size:0.76rem;text-transform:uppercase;letter-spacing:0.5px;">
                        <th style="padding:10px 14px;">Fecha y Hora</th>
                        <th style="padding:10px 14px;">Evento</th>
                        <th style="padding:10px 14px;">Destinatario</th>
                        <th style="padding:10px 14px;">Asunto</th>
                        <th style="padding:10px 14px;">Estado</th>
                        <th style="padding:10px 14px;">Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;font-style:italic;">
                            No hay despachos de correo registrados en el historial todavía.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $h): 
                            $badgeClass = '#10b981';
                            $badgeBg = '#f0fdf4';
                            $estadoText = 'Enviado';
                            if ($h->estado === 'simulado') {
                                $badgeClass = '#3b82f6';
                                $badgeBg = '#eff6ff';
                                $estadoText = 'Simulado';
                            } elseif ($h->estado === 'excluido') {
                                $badgeClass = '#d97706';
                                $badgeBg = '#fffbeb';
                                $estadoText = 'Excluido';
                            } elseif ($h->estado === 'fallido') {
                                $badgeClass = '#ef4444';
                                $badgeBg = '#fef2f2';
                                $estadoText = 'Fallido';
                            }
                        ?>
                        <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding:10px 14px;color:#64748b;white-space:nowrap;">
                                <i class="ti ti-calendar me-1"></i><?= date('d/m/Y h:i A', strtotime($h->created_at)) ?>
                            </td>
                            <td style="padding:10px 14px;font-weight:700;color:#1e293b;">
                                <code><?= htmlspecialchars($h->codigo_evento) ?></code>
                            </td>
                            <td style="padding:10px 14px;color:#0f172a;">
                                <div style="font-weight:700;"><?= htmlspecialchars($h->destinatario_nombre ?: 'Sin nombre') ?></div>
                                <div style="font-size:0.74rem;color:#64748b;"><?= htmlspecialchars($h->destinatario_email) ?></div>
                            </td>
                            <td style="padding:10px 14px;color:#334155;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($h->asunto) ?>
                            </td>
                            <td style="padding:10px 14px;">
                                <span style="display:inline-block;padding:3px 9px;border-radius:999px;font-size:0.72rem;font-weight:800;background:<?= $badgeBg ?>;color:<?= $badgeClass ?>;border:1px solid <?= $badgeClass ?>33;">
                                    <?= $estadoText ?>
                                </span>
                            </td>
                            <td style="padding:10px 14px;font-size:0.75rem;color:#64748b;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($h->detalles ?: 'Sin detalles') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Gestión de Destinatarios y Exclusiones de la Regla
     ═══════════════════════════════════════════════════════════════════════ -->
<div id="modalDestinatarios" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,0.6);backdrop-filter:blur(5px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#ffffff;border-radius:20px;max-width:620px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,0.3);overflow:hidden;display:flex;flex-direction:column;max-height:90vh;animation:modalZoomIn .25s ease;">
        
        <!-- Header -->
        <div style="background:linear-gradient(135deg, #0f172a, #1e3a8a);padding:20px 24px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                    <i class="ti ti-users-cog"></i>
                </div>
                <div>
                    <h3 style="font-size:1.05rem;font-weight:800;color:#ffffff;margin:0;" id="modalTituloRegla">Gestión de Destinatarios</h3>
                    <p style="font-size:0.76rem;color:#93c5fd;margin:0;" id="modalCodigoRegla">Regla de notificación</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDestinatarios()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.1rem;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Body Scrollable -->
        <div style="padding:24px;overflow-y:auto;flex:1;">
            <input type="hidden" id="modalReglaId" value="">

            <!-- 1. Roles Destinatarios -->
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:0.83rem;font-weight:800;color:#0f172a;margin-bottom:8px;">
                    1. Roles que recibirán esta notificación automática:
                </label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <label style="display:flex;align-items:center;gap:9px;background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;cursor:pointer;font-size:0.82rem;font-weight:700;color:#1e293b;">
                        <input type="checkbox" name="rol_dest" value="3" id="rol_check_3"> Pasantes (Estudiantes)
                    </label>
                    <label style="display:flex;align-items:center;gap:9px;background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;cursor:pointer;font-size:0.82rem;font-weight:700;color:#1e293b;">
                        <input type="checkbox" name="rol_dest" value="2" id="rol_check_2"> Tutores Empresariales
                    </label>
                    <label style="display:flex;align-items:center;gap:9px;background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;cursor:pointer;font-size:0.82rem;font-weight:700;color:#1e293b;">
                        <input type="checkbox" name="rol_dest" value="1" id="rol_check_1"> Administradores
                    </label>
                    <label style="display:flex;align-items:center;gap:9px;background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;cursor:pointer;font-size:0.82rem;font-weight:700;color:#1e293b;">
                        <input type="checkbox" name="rol_dest" value="0" id="rol_check_0"> SuperAdministrador
                    </label>
                </div>
            </div>

            <!-- 2. Lista de Exclusiones (Silenciar a usuarios puntuales) -->
            <div id="modal_box_exclusiones" style="margin-bottom:20px;background:#fff1f2;border:1.5px solid #fecdd3;border-radius:14px;padding:16px;transition:all 0.2s;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="ti ti-user-x" style="color:#e11d48;font-size:1.15rem;"></i>
                        <div>
                            <label style="font-size:0.86rem;font-weight:800;color:#9f1239;margin:0;">
                                2. Lista de Exclusión (No enviar a estos usuarios):
                            </label>
                            <div style="font-size:0.72rem;color:#be123c;">
                                Silencia el despacho automático para los usuarios seleccionados
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span id="lbl_modal_exc_switch" style="font-size:0.74rem;font-weight:800;color:#9f1239;">Activo</span>
                        <label class="cfg-toggle-wrap cfg-toggle-sm cfg-toggle-danger" style="margin:0;" title="Activar/Desactivar el filtro de exclusiones para esta regla">
                            <input type="checkbox" id="modal_exclusiones_activas" checked onchange="toggleModalExclusiones(this.checked)">
                            <span class="cfg-toggle-track"></span>
                            <span class="cfg-toggle-dot"></span>
                        </label>
                    </div>
                </div>

                <!-- Buscador rápido dentro del modal -->
                <div style="position:relative;margin-bottom:8px;">
                    <i class="ti ti-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#f43f5e;font-size:0.85rem;"></i>
                    <input type="text" id="busqueda_excluidos" oninput="filtrarUsuariosExcluidos(this.value)" placeholder="Buscar usuario para excluir..." style="width:100%;padding:6px 10px 6px 30px;border:1px solid #fecdd3;border-radius:8px;font-size:0.78rem;outline:none;background:#ffffff;">
                </div>

                <div id="contenedor_lista_excluidos" style="max-height:150px;overflow-y:auto;background:#ffffff;border:1px solid #fecdd3;border-radius:9px;padding:6px 8px;transition:all 0.2s;">
                    <?php foreach ($usuarios as $u): ?>
                    <label class="item-usuario-excluido" data-busqueda="<?= strtolower(htmlspecialchars($u->nombre_completo . ' ' . $u->correo . ' ' . $u->rol_nombre)) ?>" style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;border-bottom:1px solid #fff1f2;font-size:0.80rem;cursor:pointer;border-radius:6px;transition:background .15s;">
                        <div style="min-width:0;flex:1;padding-right:8px;">
                            <span style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($u->nombre_completo ?: $u->correo) ?></span>
                            <span style="font-size:0.72rem;color:#64748b;margin-left:4px;">(<?= htmlspecialchars($u->rol_nombre) ?>)</span>
                            <div style="font-size:0.70rem;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($u->correo) ?></div>
                        </div>
                        <label class="cfg-toggle-wrap cfg-toggle-sm cfg-toggle-danger" style="margin:0;flex-shrink:0;">
                            <input type="checkbox" class="user-excluido-chk" value="<?= (int)$u->id ?>">
                            <span class="cfg-toggle-track"></span>
                            <span class="cfg-toggle-dot"></span>
                        </label>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Lista de Inclusiones Forzadas -->
            <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:14px;padding:16px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    <i class="ti ti-user-plus" style="color:#16a34a;font-size:1.15rem;"></i>
                    <label style="font-size:0.85rem;font-weight:800;color:#14532d;margin:0;">
                        3. Enviar SIEMPRE a estos usuarios específicos (Inclusión Forzada):
                    </label>
                </div>
                <p style="font-size:0.75rem;color:#15803d;margin:0 0 10px;line-height:1.4;">
                    Usuarios específicos que deben recibir esta notificación sin importar si su rol general está marcado:
                </p>

                <div style="max-height:140px;overflow-y:auto;background:#ffffff;border:1px solid #bbf7d0;border-radius:9px;padding:8px;">
                    <?php foreach ($usuarios as $u): ?>
                    <label style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;border-bottom:1px solid #f0fdf4;font-size:0.80rem;cursor:pointer;">
                        <div>
                            <span style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($u->nombre_completo ?: $u->correo) ?></span>
                            <span style="font-size:0.72rem;color:#64748b;margin-left:6px;">(<?= htmlspecialchars($u->rol_nombre) ?>)</span>
                        </div>
                        <input type="checkbox" class="user-incluido-chk" value="<?= (int)$u->id ?>">
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div style="background:#f8fafc;padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:flex-end;gap:12px;">
            <button type="button" onclick="cerrarModalDestinatarios()" style="padding:9px 18px;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:10px;font-size:0.84rem;font-weight:700;color:#475569;cursor:pointer;">
                Cancelar
            </button>
            <button type="button" onclick="guardarConfigDestinatarios()" id="btnGuardarDest" style="padding:9px 22px;background:linear-gradient(135deg, #1e3a8a, #2563eb);border:none;border-radius:10px;font-size:0.84rem;font-weight:800;color:#ffffff;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(37,99,235,0.3);">
                <i class="ti ti-device-floppy"></i> Guardar Cambios
            </button>
        </div>

    </div>
</div>

<style>
@keyframes modalZoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.cfg-toggle-wrap {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    cursor: pointer;
}
.cfg-toggle-wrap input {
    opacity: 0;
    width: 0;
    height: 0;
}
.cfg-toggle-track {
    position: absolute;
    inset: 0;
    background-color: #cbd5e1;
    border-radius: 24px;
    transition: 0.25s ease;
}
.cfg-toggle-dot {
    position: absolute;
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.25s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
}
.cfg-toggle-wrap input:checked + .cfg-toggle-track {
    background-color: #10b981;
}
.cfg-toggle-wrap input:checked + .cfg-toggle-track .cfg-toggle-dot,
.cfg-toggle-wrap input:checked ~ .cfg-toggle-dot {
    transform: translateX(20px);
}

/* Variante Switch Compacto */
.cfg-toggle-sm {
    width: 36px;
    height: 20px;
}
.cfg-toggle-sm .cfg-toggle-dot {
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
}
.cfg-toggle-sm input:checked ~ .cfg-toggle-dot {
    transform: translateX(16px);
}

/* Variante Switch Danger / Exclusiones */
.cfg-toggle-danger input:checked + .cfg-toggle-track {
    background-color: #e11d48;
}
</style>

<script>
// ── Cambiar Estado de la Regla (ON / OFF) ─────────────────────────
async function cambiarEstadoRegla(id, activo) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('activo', activo ? 1 : 0);

    try {
        const resp = await fetch('<?= URLROOT ?>/notificaciones/toggleRegla', {
            method: 'POST',
            body: fd
        });
        const res = await resp.json();

        if (res.success) {
            if (window.Notyf) {
                new Notyf().success(res.message);
            } else if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: res.message,
                    showConfirmButton: false,
                    timer: 2500
                });
            }
        } else {
            throw new Error(res.message || 'Error al cambiar estado');
        }
    } catch (err) {
        // Revertir checkbox
        const chk = document.getElementById('toggle_' + id);
        if (chk) chk.checked = !activo;

        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#2563eb' });
        } else {
            alert(err.message);
        }
    }
}

// ── Switch Exclusiones por Regla (ON / OFF) ──────────────────────
async function cambiarEstadoExclusionesRegla(id, activo) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('activo', activo ? 1 : 0);

    try {
        const resp = await fetch('<?= URLROOT ?>/notificaciones/toggleExclusionesRegla', {
            method: 'POST',
            body: fd
        });
        const res = await resp.json();

        if (res.success) {
            const badge = document.getElementById('badge_exc_status_' + id);
            if (badge) {
                badge.textContent = activo ? 'Activo' : 'Pausado';
                badge.style.background = activo ? '#ffe4e6' : '#f1f5f9';
                badge.style.color = activo ? '#be123c' : '#64748b';
            }
            if (window.Notyf) {
                new Notyf().success(res.message);
            } else if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: res.message,
                    showConfirmButton: false,
                    timer: 2500
                });
            }
        } else {
            throw new Error(res.message || 'Error al actualizar filtro de exclusiones');
        }
    } catch (err) {
        const chk = document.getElementById('toggle_exc_' + id);
        if (chk) chk.checked = !activo;

        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#2563eb' });
        } else {
            alert(err.message);
        }
    }
}

// ── Switch Maestro Global de Exclusiones (ON / OFF) ───────────────
async function cambiarEstadoExclusionesGlobal(activo) {
    const fd = new FormData();
    fd.append('activo', activo ? 1 : 0);

    try {
        const resp = await fetch('<?= URLROOT ?>/notificaciones/toggleExclusionesGlobal', {
            method: 'POST',
            body: fd
        });
        const res = await resp.json();

        if (res.success) {
            const lbl = document.getElementById('label_global_exclusiones');
            const badge = document.getElementById('badge_global_status');
            if (lbl) {
                lbl.textContent = activo ? 'Exclusiones Habilitadas' : 'Exclusiones Deshabilitadas (Enviar a todos)';
                lbl.style.color = activo ? '#059669' : '#dc2626';
            }
            if (badge) {
                badge.textContent = activo ? 'ACTIVO' : 'PAUSADO (IGNORADO)';
                badge.style.background = activo ? '#dcfce7' : '#fee2e2';
                badge.style.color = activo ? '#15803d' : '#b91c1c';
            }
            if (window.Notyf) {
                new Notyf().success(res.message);
            } else if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: res.message,
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        } else {
            throw new Error(res.message || 'Error al actualizar exclusiones globales');
        }
    } catch (err) {
        const chk = document.getElementById('toggle_global_exclusiones');
        if (chk) chk.checked = !activo;

        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#2563eb' });
        } else {
            alert(err.message);
        }
    }
}

// ── Toggle Visual de Exclusiones en Modal ─────────────────────────
function toggleModalExclusiones(activo) {
    const lbl = document.getElementById('lbl_modal_exc_switch');
    const container = document.getElementById('contenedor_lista_excluidos');
    const searchInput = document.getElementById('busqueda_excluidos');
    if (lbl) {
        lbl.textContent = activo ? 'Activo' : 'Pausado';
        lbl.style.color = activo ? '#9f1239' : '#64748b';
    }
    if (container) {
        container.style.opacity = activo ? '1' : '0.4';
        container.style.pointerEvents = activo ? 'auto' : 'none';
    }
    if (searchInput) {
        searchInput.disabled = !activo;
    }
}

// ── Filtro en Tiempo Real para Usuarios Excluidos en Modal ─────────
function filtrarUsuariosExcluidos(q) {
    const term = (q || '').trim().toLowerCase();
    document.querySelectorAll('.item-usuario-excluido').forEach(item => {
        const text = item.getAttribute('data-busqueda') || '';
        item.style.display = (!term || text.includes(term)) ? 'flex' : 'none';
    });
}

// ── Modal de Destinatarios y Exclusiones ─────────────────────────
function abrirModalDestinatarios(regla) {
    document.getElementById('modalReglaId').value = regla.id;
    document.getElementById('modalTituloRegla').textContent = regla.titulo;
    document.getElementById('modalCodigoRegla').textContent = 'Código de Evento: ' + regla.codigo_evento;

    // Reset buscador
    const searchInput = document.getElementById('busqueda_excluidos');
    if (searchInput) {
        searchInput.value = '';
        filtrarUsuariosExcluidos('');
    }

    // 1. Marcar roles
    const roles = regla.roles_arr || [];
    [0, 1, 2, 3].forEach(r => {
        const chk = document.getElementById('rol_check_' + r);
        if (chk) chk.checked = roles.includes(String(r));
    });

    // 2. Marcar switch de exclusiones y lista
    const excActivas = regla.exclusiones_activas !== undefined ? (parseInt(regla.exclusiones_activas, 10) === 1) : true;
    const modalExcSwitch = document.getElementById('modal_exclusiones_activas');
    if (modalExcSwitch) {
        modalExcSwitch.checked = excActivas;
        toggleModalExclusiones(excActivas);
    }

    const excluidos = regla.usuarios_excluidos_arr || [];
    document.querySelectorAll('.user-excluido-chk').forEach(c => {
        c.checked = excluidos.includes(parseInt(c.value, 10));
    });

    // 3. Marcar inclusiones
    const incluidos = regla.usuarios_incluidos_arr || [];
    document.querySelectorAll('.user-incluido-chk').forEach(c => {
        c.checked = incluidos.includes(parseInt(c.value, 10));
    });

    const modal = document.getElementById('modalDestinatarios');
    modal.style.display = 'flex';
}

function cerrarModalDestinatarios() {
    document.getElementById('modalDestinatarios').style.display = 'none';
}

async function guardarConfigDestinatarios() {
    const id = document.getElementById('modalReglaId').value;
    const btn = document.getElementById('btnGuardarDest');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2 ti-spin me-1"></i> Guardando...';

    const fd = new FormData();
    fd.append('id', id);

    // Roles seleccionados
    document.querySelectorAll('input[name="rol_dest"]:checked').forEach(c => {
        fd.append('roles[]', c.value);
    });

    // Exclusiones activas switch
    const modalExcSwitch = document.getElementById('modal_exclusiones_activas');
    fd.append('exclusiones_activas', (modalExcSwitch && modalExcSwitch.checked) ? 1 : 0);

    // Excluidos
    document.querySelectorAll('.user-excluido-chk:checked').forEach(c => {
        fd.append('excluidos[]', c.value);
    });

    // Incluidos
    document.querySelectorAll('.user-incluido-chk:checked').forEach(c => {
        fd.append('incluidos[]', c.value);
    });

    try {
        const resp = await fetch('<?= URLROOT ?>/notificaciones/guardarDestinatarios', {
            method: 'POST',
            body: fd
        });
        const res = await resp.json();

        if (res.success) {
            cerrarModalDestinatarios();
            if (window.Swal) {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            window.location.reload();
        } else {
            throw new Error(res.message || 'Error al guardar');
        }
    } catch (err) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#2563eb' });
        } else {
            alert(err.message);
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// ── Probar Envío de una Plantilla Específica ───────────────────────
async function probarEnvioRegla(codigoEvento, titulo) {
    if (!window.Swal) {
        const email = prompt('Ingresa el correo para recibir la prueba de [' + titulo + ']:');
        if (!email) return;
        enviarPruebaDirecta(codigoEvento, email);
        return;
    }

    const { value: email } = await Swal.fire({
        title: '✉️ Probar Plantilla de Correo',
        html: `<p style="font-size:13px;color:#64748b;margin:0 0 10px;">Se generará una muestra real de <strong>${titulo}</strong> con diseño Bento institucional y se enviará al correo que indiques:</p>`,
        input: 'email',
        inputValue: '<?= htmlspecialchars(Session::get('user_email') ?? '') ?>',
        inputPlaceholder: 'Ingresa correo de destino...',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-send me-1"></i>Enviar Correo de Prueba',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b'
    });

    if (email) {
        enviarPruebaDirecta(codigoEvento, email);
    }
}

async function enviarPruebaDirecta(codigoEvento, email) {
    if (window.Swal) {
        Swal.fire({
            title: 'Enviando prueba...',
            text: 'Conectando con el servidor SMTP institucional...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    const fd = new FormData();
    fd.append('codigo_evento', codigoEvento);
    fd.append('email_destino', email);

    try {
        const resp = await fetch('<?= URLROOT ?>/notificaciones/probarPlantilla', {
            method: 'POST',
            body: fd
        });
        const res = await resp.json();

        if (res.success) {
            if (window.Swal) {
                Swal.fire({
                    icon: res.simulated ? 'info' : 'success',
                    title: res.simulated ? 'Modo Simulación (Log Registrado)' : '¡Correo Enviado!',
                    html: `<p style="font-size:14px;color:#1e293b;">${res.message}</p>`,
                    confirmButtonColor: '#16a34a'
                });
            } else {
                alert(res.message);
            }
        } else {
            throw new Error(res.message || 'Error en transmisión SMTP');
        }
    } catch (err) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error al enviar prueba', text: err.message, confirmButtonColor: '#2563eb' });
        } else {
            alert(err.message);
        }
    }
}

// Cerrar modal al hacer clic afuera
window.addEventListener('click', function(e) {
    const modal = document.getElementById('modalDestinatarios');
    if (e.target === modal) {
        cerrarModalDestinatarios();
    }
});

function toggleSmtpPass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        input.type = 'password';
        icon.className = 'ti ti-eye';
    }
}

async function ejecutarPruebaSmtpModulo() {
    const emailInput = document.getElementById('moduloEmailPruebaSmtp');
    const email = emailInput ? emailInput.value.trim() : '';
    if (!email) {
        if (window.Swal) {
            Swal.fire({ icon: 'warning', title: 'Correo Requerido', text: 'Por favor ingresa un correo para la prueba.', confirmButtonColor: '#2563eb' });
        } else {
            alert('Por favor ingresa un correo para la prueba.');
        }
        return;
    }

    const btn = document.getElementById('btnModuloProbarSmtp');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2 ti-spin"></i> Enviando...';
    }

    try {
        const fd = new FormData();
        fd.append('email_destino', email);
        const resp = await fetch('<?= URLROOT ?>/notificaciones/probarSmtp', {
            method: 'POST',
            body: fd
        });
        const res = await resp.json();
        if (res.success) {
            if (window.Swal) {
                Swal.fire({
                    icon: res.simulated ? 'info' : 'success',
                    title: res.simulated ? 'Modo Simulación' : '¡Conexión Exitosa!',
                    text: res.message,
                    confirmButtonColor: '#16a34a'
                });
            } else {
                alert(res.message);
            }
        } else {
            throw new Error(res.message || 'Error en transmisión SMTP');
        }
    } catch (err) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Fallo al transmitir', text: err.message, confirmButtonColor: '#dc2626' });
        } else {
            alert('Error: ' + err.message);
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}
</script>
