<?php
// Ensure we have user data
if (!isset($user) || empty($user)) {
    header('Location: ' . URLROOT . '/dashboard');
    exit;
}
?>

<div class="dashboard-container" style="margin-top: 24px;">

<div class="container-fluid mt-4">
    <div class="row" style="display: flex; gap: 24px; flex-wrap: wrap;">
        <!-- Left Column: Profile Summary -->
        <!-- Left Column: Profile Summary -->
        <div class="col-md-4" style="flex: 1; min-width: 320px; max-width: 420px;">
            <div class="smart-card profile-main-card" style="text-align: center; border-top: 4px solid #2563eb; position: relative;">
                <!-- Avatar con opción de cambio -->
                <?php
                $avatarFile = $user['avatar'] ?? 'default.png';
                $avatarUrl  = ($avatarFile !== 'default.png')
                    ? URLROOT . '/img/avatars/' . htmlspecialchars($avatarFile)
                    : null;
                ?>
                <div style="position: relative; width: 128px; height: 128px; margin: 6px auto 18px;">
                    <?php if ($avatarUrl): ?>
                    <img id="profileAvatarImg" src="<?= $avatarUrl ?>" alt="Foto de perfil"
                         style="width: 128px; height: 128px; border-radius: 50%; object-fit: cover; border: 4px solid #ffffff; box-shadow: 0 8px 24px rgba(37,99,235,0.18);">
                    <?php else: ?>
                    <div id="profileAvatarInitials" style="width: 128px; height: 128px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 52px; color: white; font-weight: 800; border: 4px solid #ffffff; box-shadow: 0 8px 24px rgba(37,99,235,0.22);">
                        <?= strtoupper(substr($user['nombres'] ?? $user['correo'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <!-- Botón cambiar foto -->
                    <label for="inputFotoPerfil" title="Cambiar foto de perfil — JPG, PNG o WEBP, máx. 2 MB"
                           style="position: absolute; bottom: 4px; right: 4px; width: 36px; height: 36px; background: #1e3a8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.15); transition: all .25s ease;"
                           onmouseover="this.style.background='#2563eb'; this.style.transform='scale(1.1)';" 
                           onmouseout="this.style.background='#1e3a8a'; this.style.transform='scale(1)';">
                        <i class="ti ti-camera" style="color: white; font-size: 1rem;"></i>
                    </label>
                    <input type="file" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="subirFotoPerfil(this)">
                </div>
                
                <!-- Name -->
                <h3 style="font-size: 1.35rem; color: #0f172a; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.3px;">
                    <?= htmlspecialchars(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')) ?: 'Usuario' ?>
                </h3>
                
                <!-- Role Badge -->
                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 12px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(37, 99, 235, 0.08); color: #1d4ed8; border: 1px solid rgba(37, 99, 235, 0.2); padding: 5px 14px; border-radius: 999px; font-size: 0.82rem; font-weight: 700;">
                        <i class="ti ti-shield-check" style="font-size: 14px;"></i>
                        <?= htmlspecialchars($user['rol_nombre'] ?? 'Sin rol') ?>
                    </span>
                </div>
                
                <!-- Status -->
                <div style="display: inline-flex; align-items: center; justify-content: center; gap: 7px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 14px; border-radius: 999px; margin-bottom: 22px;">
                    <span style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);"></span>
                    <span style="color: #065f46; font-size: 0.82rem; font-weight: 700;">
                        <?= ucfirst($user['estado'] ?? 'activo') ?>
                    </span>
                </div>
                
                <!-- Divider & Contact info -->
                <div style="border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: left; display: flex; flex-direction: column; gap: 14px;">
                    <!-- Email con botón de cambio directo -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <i class="ti ti-mail" style="color: #2563eb; font-size: 15px;"></i>
                                <span style="font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Correo Institucional</span>
                            </div>
                            <button type="button" onclick="openEmailModal()" title="Cambiar correo electrónico" style="background: none; border: none; color: #2563eb; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 3px; padding: 2px 6px; border-radius: 6px; transition: background .15s;" onmouseover="this.style.background='rgba(37,99,235,0.1)'" onmouseout="this.style.background='none'">
                                <i class="ti ti-edit"></i> Cambiar
                            </button>
                        </div>
                        <div id="leftProfileEmailDisplay" style="color: #0f172a; font-weight: 600; font-size: 0.88rem; word-break: break-all;">
                            <?= htmlspecialchars($user['correo']) ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($user['departamento_nombre'])): ?>
                    <!-- Department -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="ti ti-building" style="color: #2563eb; font-size: 15px;"></i>
                            <span style="font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Departamento</span>
                        </div>
                        <div style="color: #0f172a; font-weight: 600; font-size: 0.88rem;">
                            <?= htmlspecialchars($user['departamento_nombre']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Member Since -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="ti ti-calendar" style="color: #2563eb; font-size: 15px;"></i>
                            <span style="font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Miembro desde</span>
                        </div>
                        <div style="color: #0f172a; font-weight: 600; font-size: 0.88rem;">
                            <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Detailed Info -->
        <div class="col-md-8" style="flex: 2; min-width: 320px;">
            <div class="smart-card">
                <?php 
                // ALERTA: Perfil Incompleto (solo para pasantes)
                if (Session::get('role_id') == 3) {
                    $incompleto = empty($user['telefono']) || empty($user['institucion_procedencia']);
                    if ($incompleto):
                ?>
                <div class="alert alert-warning" style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 14px;">
                    <i class="ti ti-alert-circle" style="color: #F59E0B; font-size: 26px; flex-shrink: 0;"></i>
                    <div>
                        <strong style="color: #92400E; font-size: 14px; display: block; margin-bottom: 2px;">⚠️ Perfil Incompleto</strong>
                        <span style="color: #78350F; font-size: 13px;">Por favor actualiza tus datos de contacto e institución de procedencia para completar tu expediente.</span>
                    </div>
                </div>
                <?php endif; } ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; padding-bottom: 16px; border-bottom: 1px solid #E5E7EB;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(37,99,235,0.08); display: flex; align-items: center; justify-content: center; color: #2563eb;">
                            <i class="ti ti-user" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0;">Datos Personales</h3>
                            <span style="font-size: 0.8rem; color: #64748b;">Información básica de tu perfil en el sistema</span>
                        </div>
                    </div>
                    <button class="btn-primary" style="width: auto; padding: 8px 18px; font-size: 0.88rem; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;" onclick="openEditModal()">
                        <i class="ti ti-edit"></i> Editar Datos
                    </button>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                    <!-- Cédula -->
                    <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="ti ti-id-badge" style="color: #475569; font-size: 16px;"></i>
                            <span style="font-size: 0.72rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Cédula de Identidad</span>
                        </div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['cedula'] ?? 'No especificado') ?>
                        </div>
                    </div>
                    
                    <!-- Teléfono -->
                    <div style="background: #eff6ff; border: 1.5px solid #93c5fd; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(37,99,235,0.04);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="ti ti-phone" style="color: #2563eb; font-size: 16px;"></i>
                            <span style="font-size: 0.72rem; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Teléfono</span>
                        </div>
                        <div style="font-weight: 700; color: #1e3a8a; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['telefono'] ?? 'No especificado') ?>
                        </div>
                    </div>

                    <!-- Departamento -->
                    <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(22,163,74,0.04);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="ti ti-building" style="color: #16a34a; font-size: 16px;"></i>
                            <span style="font-size: 0.72rem; color: #166534; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Departamento</span>
                        </div>
                        <div style="font-weight: 700; color: #14532d; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['departamento_nombre'] ?? 'Sin Asignar') ?>
                        </div>
                    </div>
                    
                    <!-- Cargo (SOLO para Admin/Tutor, NO para Pasantes) -->
                    <?php if (Session::get('role_id') != 3): ?>
                    <div style="background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(234,88,12,0.04);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="ti ti-briefcase" style="color: #ea580c; font-size: 16px;"></i>
                            <span style="font-size: 0.72rem; color: #9a3412; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Cargo / Función</span>
                        </div>
                        <div style="font-weight: 700; color: #7c2d12; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['cargo'] ?? 'No especificado') ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Género -->
                    <div style="background: #faf5ff; border: 1.5px solid #d8b4fe; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(124,58,237,0.04);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="ti ti-gender-bigender" style="color: #7c3aed; font-size: 16px;"></i>
                            <span style="font-size: 0.72rem; color: #6b21a8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Género</span>
                        </div>
                        <div style="font-weight: 700; color: #581c87; font-size: 1.05rem;">
                            <?= isset($user['genero']) ? ($user['genero'] == 'M' ? 'Masculino' : 'Femenino') : 'No especificado' ?>
                        </div>
                    </div>
                    
                    <!-- Fecha de Nacimiento -->
                    <div style="background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(225,29,72,0.04);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="ti ti-cake" style="color: #e11d48; font-size: 16px;"></i>
                            <span style="font-size: 0.72rem; color: #9f1239; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Fecha de Nacimiento</span>
                        </div>
                        <div style="font-weight: 700; color: #881337; font-size: 1.05rem;">
                            <?= isset($user['fecha_nacimiento']) ? date('d/m/Y', strtotime($user['fecha_nacimiento'])) : 'No especificado' ?>
                        </div>
                    </div>
                    
                    <?php if (Session::get('role_id') == 3 && !empty($user['institucion_procedencia'])): ?>
                    <div style="grid-column: 1 / -1; background: #ecfeff; border: 1.5px solid #a5f3fc; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(8,145,178,0.04);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="ti ti-building-bank" style="color: #0891b2; font-size: 16px;"></i>
                            <span style="font-size: 0.72rem; color: #155e75; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Institución Educativa de Procedencia</span>
                        </div>
                        <div style="font-weight: 700; color: #164e63; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['institucion_nombre'] ?? $user['institucion_procedencia'] ?? 'No especificada') ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tarjeta Institución + Representante (solo pasantes) -->
            <?php if (Session::get('role_id') == 3 && !empty($user['inst_rep_nombre'])): ?>
            <div class="smart-card" style="margin-top: 24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #E5E7EB;">
                    <h3 style="font-size:1.1rem;color:#0f172a;font-weight:700;margin:0;">
                        <i class="ti ti-school" style="margin-right:8px; color: #2563eb;"></i> Representante de Institución
                    </h3>
                </div>
                <div style="display:flex;gap:16px;align-items:flex-start;background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:18px;">
                    <div style="width:48px;height:48px;background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ti ti-user-check" style="color:white;font-size:1.3rem;"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:1rem;color:#1e3a8a;"><?= htmlspecialchars($user['inst_rep_nombre']) ?></div>
                        <?php if (!empty($user['inst_rep_cargo'])): ?>
                        <div style="font-size:0.82rem;color:#0369a1;margin-top:2px;"><?= htmlspecialchars($user['inst_rep_cargo']) ?></div>
                        <?php endif; ?>
                        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:10px;">
                            <?php if (!empty($user['inst_rep_correo'])): ?>
                            <a href="mailto:<?= htmlspecialchars($user['inst_rep_correo']) ?>" style="display:flex;align-items:center;gap:5px;font-size:0.83rem;color:#2563eb;text-decoration:none;">
                                <i class="ti ti-mail"></i><?= htmlspecialchars($user['inst_rep_correo']) ?>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($user['inst_rep_telefono'])): ?>
                            <span style="display:flex;align-items:center;gap:5px;font-size:0.83rem;color:#334155;">
                                <i class="ti ti-phone" style="color:#2563eb;"></i><?= htmlspecialchars($user['inst_rep_telefono']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tarjeta de Seguridad y Accesos -->
            <div class="smart-card" style="margin-top: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; padding-bottom: 16px; border-bottom: 1px solid #E5E7EB;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(37,99,235,0.08); display: flex; align-items: center; justify-content: center; color: #2563eb;">
                            <i class="ti ti-shield-lock" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0;">Seguridad & Control de Acceso</h3>
                            <span style="font-size: 0.8rem; color: #64748b;">Protección de identidad, credenciales y autenticación</span>
                        </div>
                    </div>
                </div>

                <div class="profile-security-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    
                    <!-- 1. NUEVO: Cambiar Correo Institucional -->
                    <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 20px; border-radius: 14px; border: 1.5px solid #93c5fd; display: flex; flex-direction: column; justify-content: space-between; transition: all .2s ease; box-shadow: 0 2px 6px rgba(14,165,233,0.04);">
                        <div>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #0284c7; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(2,132,199,0.25);">
                                        <i class="ti ti-mail" style="font-size: 22px;"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-weight: 700; color: #075985; font-size: 0.98rem;">Correo de Acceso</h5>
                                        <p style="margin: 0; font-size: 0.8rem; color: #0369a1;">Inicio de sesión y alertas</p>
                                    </div>
                                </div>
                                <span style="font-size: 0.72rem; font-weight: 800; background: #0284c7; color: white; padding: 2px 8px; border-radius: 999px;">
                                    Verificado
                                </span>
                            </div>
                            <div style="background: rgba(255,255,255,0.85); border: 1px solid #bae6fd; border-radius: 8px; padding: 8px 10px; margin-bottom: 14px; font-size: 0.82rem; color: #0369a1; font-weight: 700; word-break: break-all; display: flex; align-items: center; gap: 6px;">
                                <i class="ti ti-at" style="color: #0284c7;"></i>
                                <span id="securityProfileEmailDisplay"><?= htmlspecialchars($user['correo']) ?></span>
                            </div>
                        </div>
                        <button class="btn-primary" style="width: 100%; border-radius: 10px; padding: 10px; font-size: 0.88rem; font-weight: 700; background: linear-gradient(135deg, #0284c7, #0369a1); border: none; box-shadow: 0 4px 12px rgba(2,132,199,0.25); display: flex; align-items: center; justify-content: center; gap: 6px;" onclick="openEmailModal()">
                            <i class="ti ti-mail-cog"></i> Cambiar Correo
                        </button>
                    </div>

                    <!-- 2. Cambiar Contraseña -->
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); padding: 20px; border-radius: 14px; border: 1.5px solid #c7d2fe; display: flex; flex-direction: column; justify-content: space-between; transition: all .2s ease; box-shadow: 0 2px 6px rgba(79,70,229,0.04);">
                        <div>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #4f46e5; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(79,70,229,0.25);">
                                        <i class="ti ti-key" style="font-size: 22px;"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-weight: 700; color: #312e81; font-size: 0.98rem;">Contraseña</h5>
                                        <p style="margin: 0; font-size: 0.8rem; color: #4338ca;">Clave personal de acceso</p>
                                    </div>
                                </div>
                                <span style="font-size: 0.72rem; font-weight: 800; background: #4f46e5; color: white; padding: 2px 8px; border-radius: 999px;">
                                    Encriptada
                                </span>
                            </div>
                            <p style="margin: 0 0 14px; font-size: 0.82rem; color: #4338ca; line-height: 1.4;">
                                Actualiza tu contraseña regularmente para mantener blindada tu cuenta.
                            </p>
                        </div>
                        <button class="btn-primary" style="width: 100%; border-radius: 10px; padding: 10px; font-size: 0.88rem; font-weight: 700; background: linear-gradient(135deg, #4f46e5, #4338ca); border: none; box-shadow: 0 4px 12px rgba(79,70,229,0.25); display: flex; align-items: center; justify-content: center; gap: 6px;" onclick="openPasswordModal()">
                            <i class="ti ti-lock"></i> Cambiar Contraseña
                        </button>
                    </div>

                    <!-- 3. Preguntas de Seguridad -->
                    <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); padding: 20px; border-radius: 14px; border: 1.5px solid #fde68a; display: flex; flex-direction: column; justify-content: space-between; transition: all .2s ease; box-shadow: 0 2px 6px rgba(217,119,6,0.04);">
                        <div>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #d97706; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(217,119,6,0.25);">
                                        <i class="ti ti-help-circle" style="font-size: 22px;"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-weight: 700; color: #78350f; font-size: 0.98rem;">Preguntas Clave</h5>
                                        <p style="margin: 0; font-size: 0.8rem; color: #92400e;">Recuperación de cuenta</p>
                                    </div>
                                </div>
                                <span style="font-size: 0.72rem; font-weight: 800; background: #d97706; color: white; padding: 2px 8px; border-radius: 999px;">
                                    3 Activas
                                </span>
                            </div>
                            <p style="margin: 0 0 14px; font-size: 0.82rem; color: #92400e; line-height: 1.4;">
                                Permiten restablecer tu cuenta si olvidas tu contraseña institucional.
                            </p>
                        </div>
                        <button class="btn-primary" style="width: 100%; border-radius: 10px; padding: 10px; font-size: 0.88rem; font-weight: 700; background: linear-gradient(135deg, #d97706, #b45309); border: none; box-shadow: 0 4px 12px rgba(217,119,6,0.25); display: flex; align-items: center; justify-content: center; gap: 6px;" onclick="openPreguntasModal()">
                            <i class="ti ti-settings"></i> Gestionar Preguntas
                        </button>
                    </div>

                    <?php if (Session::get('role_id') == 3): ?>
                    <!-- 4. PIN de Asistencia Kiosco (Solo Pasantes) -->
                    <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); padding: 20px; border-radius: 14px; border: 1.5px solid #fed7aa; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 2px 6px rgba(234,88,12,0.04);">
                        <div>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #ea580c; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(234,88,12,0.25);">
                                        <i class="ti ti-dialpad" style="font-size: 22px;"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-weight: 700; color: #7c2d12; font-size: 0.98rem;">PIN de Asistencia</h5>
                                        <p style="margin: 0; font-size: 0.8rem; color: #9a3412;">Marcaje en Quiosco</p>
                                    </div>
                                </div>
                                <?php if (!empty($user['pin_asistencia'])): ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; background: #16a34a; color: white; padding: 2px 8px; border-radius: 999px;">
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; background: #f59e0b; color: white; padding: 2px 8px; border-radius: 999px;">
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p style="margin: 0 0 14px; font-size: 0.82rem; color: #9a3412; line-height: 1.4;">
                                Código numérico de 4 dígitos para registrar entrada y salida en quiosco.
                            </p>
                        </div>
                        <button class="btn-primary" style="width: 100%; border-radius: 10px; padding: 10px; font-size: 0.88rem; font-weight: 700; background: linear-gradient(135deg, #ea580c, #c2410c); border: none; box-shadow: 0 4px 12px rgba(234,88,12,0.25); display: flex; align-items: center; justify-content: center; gap: 6px;" onclick="openPinModal()">
                            <i class="ti ti-dialpad"></i> <?= !empty($user['pin_asistencia']) ? 'Cambiar PIN' : 'Crear PIN' ?>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- 5. Acceso Biométrico Móvil (Huella Dactilar / Face ID) -->
                    <?php 
                    $tieneBiometria = !empty($dispositivosBiometria);
                    $primerDispositivo = $tieneBiometria ? $dispositivosBiometria[0] : null;
                    ?>
                    <div style="background: <?= $tieneBiometria ? 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)' : '#F8FAFC' ?>; padding: 20px; border-radius: 14px; border: 1.5px solid <?= $tieneBiometria ? '#86efac' : '#E2E8F0' ?>; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: <?= $tieneBiometria ? '#16a34a' : 'rgba(37,99,235,0.1)' ?>; display: flex; align-items: center; justify-content: center; color: <?= $tieneBiometria ? '#ffffff' : '#2563eb' ?>; flex-shrink: 0;">
                                        <i class="ti ti-fingerprint" style="font-size: 22px;"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-weight: 700; color: <?= $tieneBiometria ? '#14532d' : '#0f172a' ?>; font-size: 0.98rem;">Huella / Biometría</h5>
                                        <p style="margin: 0; font-size: 0.8rem; color: <?= $tieneBiometria ? '#15803d' : '#64748b' ?>;">Acceso sin contraseña</p>
                                    </div>
                                </div>
                                <?php if ($tieneBiometria): ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; background: #22c55e; color: white; padding: 2px 8px; border-radius: 999px; text-transform: uppercase;">
                                        Vinculada
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.72rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 999px;">
                                        Opcional
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($tieneBiometria): ?>
                                <div style="background: rgba(255,255,255,0.9); border-radius: 8px; padding: 10px; font-size: 0.8rem; color: #166534; line-height: 1.4; border: 1px solid #bbf7d0; margin-bottom: 14px;">
                                    <i class="ti ti-circle-check" style="color: #16a34a; margin-right: 4px;"></i>
                                    Dispositivo: <strong><?= htmlspecialchars($primerDispositivo->device_name ?? 'Dispositivo') ?></strong>
                                </div>
                            <?php else: ?>
                                <p style="margin: 0 0 14px; font-size: 0.82rem; color: #64748b; line-height: 1.4;">
                                    Inicia sesión de forma rápida con el lector de huella o sensor biométrico de tu teléfono móvil.
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($tieneBiometria): ?>
                            <button type="button" 
                                    style="width: 100%; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 10px; padding: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all .2s;" 
                                    onmouseover="this.style.background='#fecaca'" 
                                    onmouseout="this.style.background='#fee2e2'"
                                    onclick="window.sgpEliminarBiometria(<?= (int)$primerDispositivo->id ?>, '<?= htmlspecialchars($primerDispositivo->device_name ?? 'Dispositivo Móvil') ?>')">
                                <i class="ti ti-trash"></i> Desafiliar Biometría
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    class="btn-primary" 
                                    style="width: 100%; border-radius: 10px; padding: 10px; font-size: 0.88rem; font-weight: 700; background: linear-gradient(135deg, #16a34a, #059669); border: none; box-shadow: 0 4px 12px rgba(22,163,74,0.25); display: flex; align-items: center; justify-content: center; gap: 6px;"
                                    onclick="sgpActivarHuellaConConfirmacion()">
                                <i class="ti ti-fingerprint"></i> Activar Huella Dactilar
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- 6. Patrón de Desbloqueo Gestual (Alternativa Universal para Equipos sin Huella) -->
                    <div style="background: <?= !empty($tienePatron) ? 'linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%)' : '#F8FAFC' ?>; padding: 20px; border-radius: 14px; border: 1.5px solid <?= !empty($tienePatron) ? '#c4b5fd' : '#E2E8F0' ?>; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: <?= !empty($tienePatron) ? '#7c3aed' : 'rgba(124,58,237,0.1)' ?>; display: flex; align-items: center; justify-content: center; color: <?= !empty($tienePatron) ? '#ffffff' : '#7c3aed' ?>; flex-shrink: 0;">
                                        <i class="ti ti-grid-dots" style="font-size: 22px;"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-weight: 700; color: <?= !empty($tienePatron) ? '#4c1d95' : '#0f172a' ?>; font-size: 0.98rem;">Patrón de Seguridad</h5>
                                        <p style="margin: 0; font-size: 0.8rem; color: <?= !empty($tienePatron) ? '#6d28d9' : '#64748b' ?>;">Acceso gestual (9 puntos)</p>
                                    </div>
                                </div>
                                <?php if (!empty($tienePatron)): ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; background: #7c3aed; color: white; padding: 2px 8px; border-radius: 999px; text-transform: uppercase;">
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.72rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 999px;">
                                        Opcional
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p style="margin: 0 0 14px; font-size: 0.82rem; color: #64748b; line-height: 1.4;">
                                <?= !empty($tienePatron) 
                                    ? 'Tienes un patrón gestual activo para acceder rápidamente sin contraseña desde tus dispositivos autorizados.' 
                                    : 'Ideal para computadoras o teléfonos sin lector de huellas. Configúralo con autorización por correo.' ?>
                            </p>
                        </div>

                        <button type="button" 
                                class="btn-primary" 
                                style="width: 100%; border-radius: 10px; padding: 10px; font-size: 0.88rem; font-weight: 700; background: linear-gradient(135deg, #7c3aed, #6366f1); border: none; box-shadow: 0 4px 12px rgba(124,58,237,0.25); display: flex; align-items: center; justify-content: center; gap: 6px;"
                                onclick="SgpDispositivos.iniciarVinculacionBancaria('patron')">
                            <i class="ti ti-grid-dots"></i> <?= !empty($tienePatron) ? 'Cambiar Patrón' : 'Configurar Patrón' ?>
                        </button>
                    </div>
                </div>

                <!-- 7. Panel de Dispositivos Frecuentes Vinculados (Estándar Bancario) -->
                <div style="margin-top: 24px; padding-top: 20px; border-top: 1.5px dashed #e2e8f0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h4 style="margin: 0; font-size: 0.98rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                <i class="ti ti-devices" style="color: #2563eb; font-size: 20px;"></i> Dispositivos Confiables Autorizados
                            </h4>
                            <p style="margin: 2px 0 0; font-size: 0.8rem; color: #64748b;">
                                Equipos autorizados mediante código OTP para acceso biométrico y gestual.
                            </p>
                        </div>
                        <button type="button" 
                                onclick="SgpDispositivos.iniciarVinculacionBancaria()" 
                                style="background: #eff6ff; color: #2563eb; border: 1.5px solid #bfdbfe; border-radius: 10px; padding: 7px 14px; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all .2s;"
                                onmouseover="this.style.background='#dbeafe'"
                                onmouseout="this.style.background='#eff6ff'">
                            <i class="ti ti-device-mobile-plus"></i> Vincular Este Dispositivo
                        </button>
                    </div>

                    <?php if (!empty($dispositivosFrecuentes)): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                            <?php foreach ($dispositivosFrecuentes as $disp): ?>
                                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #3b82f6; flex-shrink: 0;">
                                            <i class="<?= $disp->metodo_auth === 'patron' ? 'ti ti-grid-dots' : 'ti ti-device-mobile' ?>" style="font-size: 20px;"></i>
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 700; font-size: 0.86rem; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= htmlspecialchars($disp->nombre_dispositivo) ?>
                                            </div>
                                            <div style="font-size: 0.74rem; color: #64748b; margin-top: 2px;">
                                                Método: <strong style="color: #2563eb;"><?= ucfirst($disp->metodo_auth ?? 'huella') ?></strong>
                                                <?php if (!empty($disp->verificado_at)): ?>
                                                    · <?= date('d/m/y', strtotime($disp->verificado_at)) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            title="Desvincular Dispositivo"
                                            onclick="desvincularDispositivoSgp(<?= (int)$disp->id ?>, '<?= htmlspecialchars($disp->nombre_dispositivo) ?>')"
                                            style="background: transparent; border: none; color: #ef4444; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .2s;"
                                            onmouseover="this.style.background='#fee2e2'"
                                            onmouseout="this.style.background='transparent'">
                                        <i class="ti ti-trash" style="font-size: 17px;"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 18px; text-align: center; color: #64748b; font-size: 0.85rem;">
                            <i class="ti ti-shield-lock" style="font-size: 24px; color: #94a3b8; display: block; margin-bottom: 6px;"></i>
                            No tienes dispositivos vinculados como frecuentes. Pulsa <strong>"Vincular Este Dispositivo"</strong> para autorizarlo mediante código OTP.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Cambiar Correo Institucional con Verificación Adicional -->
<div id="emailModal" class="modal">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon" style="background: rgba(255,255,255,0.18); border-radius: 12px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    <i class="ti ti-mail-cog"></i>
                </div>
                <div>
                    <h2 class="modal-title">Cambiar Correo</h2>
                    <p class="modal-subtitle">Actualización segura con verificación de contraseña</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeEmailModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <div class="modal-body-scroll">
            <form id="formCambiarCorreo" onsubmit="ejecutarCambioCorreo(event)">
                <input type="hidden" name="_csrf" value="<?= Session::getCsrfToken() ?>">

                <!-- Banner explicativo de seguridad -->
                <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-start;">
                    <i class="ti ti-shield-lock" style="color: #2563eb; font-size: 24px; flex-shrink: 0; margin-top: 1px;"></i>
                    <div style="font-size: 0.82rem; color: #1e3a8a; line-height: 1.45;">
                        <strong>Verificación de Seguridad Obligatoria:</strong> Por protección de tu cuenta institucional, requerimos validar tu <strong>contraseña actual</strong>. Se enviará una alerta de seguridad al correo anterior, una confirmación al nuevo correo y una notificación a la administración.
                    </div>
                </div>

                <!-- Correo Actual (Solo Lectura) -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">
                        <i class="ti ti-mail" style="margin-right: 4px;"></i> Correo Actual (Asociado)
                    </label>
                    <input type="text" id="modal_correo_actual" readonly value="<?= htmlspecialchars($user['correo']) ?>" class="input-modern" style="background-color: #f8fafc; cursor: not-allowed; color: #64748b; font-weight: 600;">
                </div>

                <!-- Nuevo Correo -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">
                        <i class="ti ti-at" style="margin-right: 4px;"></i> Nuevo Correo Electrónico *
                    </label>
                    <input type="email" name="correo_nuevo" id="modal_correo_nuevo" required placeholder="ejemplo@ispeb.gob.ve" class="input-modern" autocomplete="email">
                </div>

                <!-- Confirmar Nuevo Correo -->
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">
                        <i class="ti ti-check" style="margin-right: 4px;"></i> Confirmar Nuevo Correo *
                    </label>
                    <input type="email" name="correo_confirmar" id="modal_correo_confirmar" required placeholder="Repite tu nuevo correo electrónico" class="input-modern" autocomplete="email">
                </div>

                <!-- Contraseña Actual (Verificación Adicional) -->
                <div class="form-group has-toggle" style="margin-bottom: 24px;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">
                        <i class="ti ti-lock" style="margin-right: 4px;"></i> Contraseña Actual (Verificación de Identidad) *
                    </label>
                    <div style="position: relative;">
                        <input type="password" name="password_actual" id="modal_password_actual" required placeholder="Ingresa tu contraseña actual" class="input-modern" style="padding-right: 46px;">
                        <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('modal_password_actual', this)" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; font-size: 1.15rem;"></i>
                    </div>
                    <small style="color: #64748b; font-size: 0.78rem; margin-top: 4px; display: block;">
                        Requerida para autorizar la actualización de tu cuenta en el sistema.
                    </small>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="closeEmailModal()" class="btn-cancel" style="flex: 1; padding: 12px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; border-radius: 12px; font-weight: 700; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarCorreo" class="btn-primary" style="flex: 2; padding: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="ti ti-shield-check"></i> Verificar y Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon">
                    <i class="ti ti-edit"></i>
                </div>
                <div>
                    <h2 class="modal-title">Editar Mi Perfil</h2>
                    <p class="modal-subtitle">Actualiza tu información personal de contacto</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <div class="modal-body-scroll">
            <form id="editProfileForm" action="<?= URLROOT ?>/perfil/actualizar" method="POST">
                <div class="profile-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label>Cédula * <small style="color: #6B7280;">(No editable)</small></label>
                        <input type="text" name="cedula" id="edit_cedula" readonly
                            placeholder="V-12234567"
                            value="<?= htmlspecialchars($user['cedula'] ?? '') ?>" 
                            class="input-modern" 
                            style="background-color: #F3F4F6; cursor: not-allowed; color: #6B7280;">
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="text" name="telefono" id="edit_telefono" required 
                            placeholder="0414-1231234"
                            maxlength="20"
                            oninput="validatePhoneInput(event)"
                            value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" class="input-modern">
                    </div>
                </div>
                
                <div class="profile-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label>Nombres *</label>
                        <input type="text" name="nombres" id="edit_nombres" required 
                            placeholder="Ingrese sus nombres"
                            maxlength="100"
                            oninput="validateNameInput(event)"
                            value="<?= htmlspecialchars($user['nombres'] ?? '') ?>" class="input-modern">
                    </div>
                    
                    <div class="form-group">
                        <label>Apellidos *</label>
                        <input type="text" name="apellidos" id="edit_apellidos" required 
                            placeholder="Ingrese sus apellidos"
                            maxlength="100"
                            oninput="validateNameInput(event)"
                            value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>" class="input-modern">
                    </div>
                </div>
                
                <div class="profile-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <?php if (Session::get('role_id') != 3): // Solo Admin/Tutor ?>
                    <div class="form-group">
                        <label>Cargo *</label>
                        <input type="text" name="cargo" id="edit_cargo" required 
                            placeholder="Ej: Analista de Soporte"
                            value="<?= htmlspecialchars($user['cargo'] ?? '') ?>" class="input-modern">
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Género *</label>
                        <select name="genero" id="edit_genero" required class="input-modern">
                            <option value="">Seleccione...</option>
                            <option value="M" <?= ($user['genero'] ?? '') == 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($user['genero'] ?? '') == 'F' ? 'selected' : '' ?>>Femenino</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Nacimiento *</label>
                        <?php
                        // Calcular fechas límite para validación
                        $maxDate = date('Y-m-d'); // Hoy
                        $minDate = date('Y-m-d', strtotime('-100 years')); // Hace 100 años
                        ?>
                        <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" required 
                            min="<?= $minDate ?>"
                            max="<?= $maxDate ?>"
                            value="<?= htmlspecialchars($user['fecha_nacimiento'] ?? '') ?>" class="input-modern">
                    </div>
                </div>
                
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label><i class="ti ti-building" style="color: #16a34a; margin-right: 4px;"></i> Departamento Adscrito *</label>
                    <select name="departamento_id" id="edit_departamento" required class="input-modern">
                        <option value="">Seleccione Departamento...</option>
                        <?php foreach (($departamentos ?? []) as $dep): ?>
                            <option value="<?= $dep->id ?>" <?= (($user['departamento_id'] ?? $user['departamento_efectivo_id'] ?? '') == $dep->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dep->nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (Session::get('role_id') == 3): // Pasante ?>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Institución de Procedencia * <small style="color: #6B7280;">(No editable)</small></label>
                    <input type="text" name="institucion_nombre" id="edit_institucion" readonly 
                        value="<?= htmlspecialchars($user['institucion_nombre'] ?? $user['institucion_procedencia'] ?? '') ?>" 
                        class="input-modern"
                        style="background-color: #F3F4F6; cursor: not-allowed; color: #6B7280;">
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class="ti ti-check"></i> Guardar Cambios
                </button>
            </form>
        </div>
    </div>
</div>
    </div>
</div>

<!-- Preguntas de Seguridad Modal -->
<div id="preguntasModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon">
                    <i class="ti ti-shield-lock"></i>
                </div>
                <div>
                    <h2 class="modal-title">Preguntas de Seguridad</h2>
                    <p class="modal-subtitle">Actualiza tus métodos de recuperación</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePreguntasModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <div class="modal-body-scroll">
            <form id="formSecurityQuestions" action="<?= URLROOT ?>/perfil/actualizar_preguntas" method="POST">
                <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); border: 1px solid #BFDBFE; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <p style="color: #1E3A8A; font-size: 0.9rem; margin: 0;">
                        <i class="ti ti-info-circle" style="margin-right: 6px;"></i>
                        <strong>Aviso:</strong> Selecciona tres preguntas diferentes y proporciona respuestas que solo tú conozcas.
                    </p>
                </div>

                <!-- Pregunta 1 -->
                <div style="background: #F8FAFC; padding: 20px; border-radius: 16px; border: 1px solid #E2E8F0; margin-bottom: 20px;">
                    <div class="form-group">
                        <label><i class="ti ti-help" style="margin-right: 6px;"></i> Pregunta 1 *</label>
                        <select name="pregunta_1" id="pregunta_1" class="input-modern" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($preguntas as $p): ?>
                                <option value="<?= $p->id ?>" <?= (isset($respuestas[0]) && $respuestas[0]->pregunta_id == $p->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p->pregunta) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group has-toggle" style="margin-bottom: 0;">
                        <input type="password" name="respuesta_1" id="respuesta_1" class="input-modern" placeholder="Tu respuesta a la pregunta 1" required style="padding-right: 48px;">
                        <label class="label-floating" style="opacity: 0;">Respuesta 1</label>
                        <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('respuesta_1', this)"></i>
                    </div>
                </div>

                <!-- Pregunta 2 -->
                <div style="background: #F8FAFC; padding: 20px; border-radius: 16px; border: 1px solid #E2E8F0; margin-bottom: 20px;">
                    <div class="form-group">
                        <label><i class="ti ti-help" style="margin-right: 6px;"></i> Pregunta 2 *</label>
                        <select name="pregunta_2" id="pregunta_2" class="input-modern" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($preguntas as $p): ?>
                                <option value="<?= $p->id ?>" <?= (isset($respuestas[1]) && $respuestas[1]->pregunta_id == $p->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p->pregunta) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group has-toggle" style="margin-bottom: 0;">
                        <input type="password" name="respuesta_2" id="respuesta_2" class="input-modern" placeholder="Tu respuesta a la pregunta 2" required style="padding-right: 48px;">
                        <label class="label-floating" style="opacity: 0;">Respuesta 2</label>
                        <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('respuesta_2', this)"></i>
                    </div>
                </div>

                <!-- Pregunta 3 -->
                <div style="background: #F8FAFC; padding: 20px; border-radius: 16px; border: 1px solid #E2E8F0; margin-bottom: 24px;">
                    <div class="form-group">
                        <label><i class="ti ti-help" style="margin-right: 6px;"></i> Pregunta 3 *</label>
                        <select name="pregunta_3" id="pregunta_3" class="input-modern" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($preguntas as $p): ?>
                                <option value="<?= $p->id ?>" <?= (isset($respuestas[2]) && $respuestas[2]->pregunta_id == $p->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p->pregunta) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group has-toggle" style="margin-bottom: 0;">
                        <input type="password" name="respuesta_3" id="respuesta_3" class="input-modern" placeholder="Tu respuesta a la pregunta 3" required style="padding-right: 48px;">
                        <label class="label-floating" style="opacity: 0;">Respuesta 3</label>
                        <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('respuesta_3', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class="ti ti-check"></i> Guardar Preguntas
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon">
                    <i class="ti ti-lock"></i>
                </div>
                <div>
                    <h2 class="modal-title">Seguridad</h2>
                    <p class="modal-subtitle">Actualiza tu contraseña de acceso</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePasswordModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <div class="modal-body-scroll">
            <form id="changePasswordForm" action="<?= URLROOT ?>/perfil/cambiar_password" method="POST">
                <!-- Contraseña Actual con Floating Label y Toggle -->
                <div class="form-group has-toggle" style="margin-bottom: 24px;">
                    <input type="password" 
                        name="password_actual" 
                        id="password_actual" 
                        class="input-modern" 
                        placeholder=" " 
                        required 
                        style="padding-right: 48px;">
                    <label for="password_actual" class="label-floating">
                        <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Contraseña Actual
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('password_actual', this)"></i>
                    <i class="ti ti-check input-feedback icon-check"></i>
                    <i class="ti ti-x input-feedback icon-error"></i>
                </div>

                <!-- Nueva Contraseña con Floating Label, Toggle y Barra de Fortaleza -->
                <div class="form-group has-toggle" style="margin-bottom: 12px;">
                    <input type="password" 
                        name="password_nueva" 
                        id="password_nueva" 
                        class="input-modern" 
                        placeholder=" " 
                        required 
                        style="padding-right: 48px;"
                        minlength="8"
                        oninput="updatePasswordStrengthWithRequirements(this, document.getElementById('strength-bar'), document.getElementById('strength-text'))">
                    <label for="password_nueva" class="label-floating">
                        <i class="ti ti-key" style="margin-right: 8px; font-size: 18px;"></i>Nueva Contraseña
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('password_nueva', this)"></i>
                    <i class="ti ti-check input-feedback icon-check"></i>
                    <i class="ti ti-x input-feedback icon-error"></i>
                </div>
                
                <!-- Barra de Fortaleza de Contraseña -->
                <div style="margin-bottom: 20px;">
                    <div class="password-strength-bar" id="strength-bar"></div>
                    <div id="strength-text" class="password-strength-text" style="text-align: right; font-size: 0.85rem; margin-top: 4px; font-weight: 600;"></div>
                </div>

                <!-- Confirmar Contraseña con Floating Label y Toggle -->
                <div class="form-group has-toggle" style="margin-bottom: 24px;">
                    <input type="password" 
                        name="password_confirmar" 
                        id="password_confirmar" 
                        class="input-modern" 
                        placeholder=" " 
                        required 
                        style="padding-right: 48px;"
                        minlength="8"
                        oninput="validatePasswordMatch(document.getElementById('password_nueva'), this)">
                    <label for="password_confirmar" class="label-floating">
                        <i class="ti ti-check" style="margin-right: 8px; font-size: 18px;"></i>Confirmar Nueva Contraseña
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('password_confirmar', this)"></i>
                    <i class="ti ti-check input-feedback icon-check"></i>
                    <i class="ti ti-x input-feedback icon-error"></i>
                </div>

                <!-- Checklist Visual de Requisitos -->
                <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); border: 1px solid #BFDBFE; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <h6 style="color: #1E40AF; margin: 0 0 12px 0; font-size: 0.9rem; font-weight: 600;">
                        <i class="ti ti-info-circle" style="margin-right: 6px;"></i> Requisitos de Seguridad
                    </h6>
                    <ul style="margin: 0; padding: 0; list-style: none; color: #1E3A8A; font-size: 0.85rem; line-height: 2;">
                        <li id="req-length" class="password-requirement">
                            <i class="ti ti-circle-check requirement-icon"></i>
                            <span>Mínimo 8 caracteres</span>
                        </li>
                        <li id="req-uppercase" class="password-requirement">
                            <i class="ti ti-circle-check requirement-icon"></i>
                            <span>Al menos una letra mayúscula</span>
                        </li>
                        <li id="req-lowercase" class="password-requirement">
                            <i class="ti ti-circle-check requirement-icon"></i>
                            <span>Al menos una letra minúscula</span>
                        </li>
                        <li id="req-number" class="password-requirement">
                            <i class="ti ti-circle-check requirement-icon"></i>
                            <span>Al menos un número</span>
                        </li>
                        <li id="req-special" class="password-requirement">
                            <i class="ti ti-circle-check requirement-icon"></i>
                            <span>Al menos un carácter especial (!@#$%^&*)</span>
                        </li>
                    </ul>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class="ti ti-check"></i> Cambiar Contraseña
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal PIN de Asistencia (4 dígitos para pasantes) -->
<div id="pinModal" class="modal">
    <div class="modal-content" style="max-width: 440px;">
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon" style="background:rgba(255,255,255,0.2);color:white;width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-dialpad" style="font-size:22px;"></i>
                </div>
                <div>
                    <h2 class="modal-title" style="margin:0;font-size:1.15rem;font-weight:800;color:white;">PIN de Asistencia</h2>
                    <p class="modal-subtitle" style="margin:2px 0 0;font-size:0.8rem;color:rgba(255,255,255,0.8);">Código de 4 dígitos para marcar en el quiosco</p>
                </div>
            </div>
            <button class="modal-close" onclick="closePinModal()" style="background:transparent;border:none;color:white;cursor:pointer;font-size:1.3rem;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        
        <div class="modal-body-scroll" style="padding: 24px;">
            <form id="formCambiarPin" onsubmit="guardarPinAsistencia(event)">
                <input type="hidden" name="_csrf" value="<?= Session::getCsrfToken() ?>">
                
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px;margin-bottom:18px;display:flex;gap:10px;align-items:center;">
                    <i class="ti ti-info-circle" style="color:#2563eb;font-size:20px;flex-shrink:0;"></i>
                    <span style="font-size:0.82rem;color:#1e40af;line-height:1.4;">
                        El PIN de 4 dígitos es tu clave personal para registrar entrada y salida rápidamente en el Quiosco de Asistencia.
                    </span>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">
                        Nuevo PIN (4 Dígitos) *
                    </label>
                    <div style="position:relative;">
                        <input type="password" 
                               name="pin_nuevo" 
                               id="pin_nuevo" 
                               maxlength="4" 
                               pattern="[0-9]{4}" 
                               inputmode="numeric" 
                               placeholder="••••" 
                               required 
                               class="input-modern" 
                               style="font-size:1.4rem;letter-spacing:10px;text-align:center;font-weight:800;padding-right:44px;"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)">
                        <i class="ti ti-eye" id="togglePinNuevoIcon" 
                           onclick="toggleInputPass('pin_nuevo', 'togglePinNuevoIcon')" 
                           style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;font-size:1.15rem;"></i>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:6px;">
                        Confirmar Nuevo PIN *
                    </label>
                    <div style="position:relative;">
                        <input type="password" 
                               name="pin_confirmacion" 
                               id="pin_confirmacion" 
                               maxlength="4" 
                               pattern="[0-9]{4}" 
                               inputmode="numeric" 
                               placeholder="••••" 
                               required 
                               class="input-modern" 
                               style="font-size:1.4rem;letter-spacing:10px;text-align:center;font-weight:800;padding-right:44px;"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)">
                        <i class="ti ti-eye" id="togglePinConfIcon" 
                           onclick="toggleInputPass('pin_confirmacion', 'togglePinConfIcon')" 
                           style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;font-size:1.15rem;"></i>
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="button" onclick="closePinModal()" style="flex:1;padding:11px;background:#f1f5f9;border:1px solid #cbd5e1;color:#475569;border-radius:10px;font-weight:700;cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarPin" class="btn-primary" style="flex:2;padding:11px;font-weight:700;">
                        <i class="ti ti-check"></i> Guardar PIN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<style>
    .modal {
        display: none;
        position: fixed !important;
        z-index: 99999999 !important;
        top: -20px !important;
        left: -20px !important;
        right: -20px !important;
        bottom: -20px !important;
        inset: -20px !important;
        width: calc(100% + 40px) !important;
        height: calc(100% + 40px) !important;
        min-width: calc(100% + 40px) !important;
        min-height: calc(100% + 40px) !important;
        background: rgba(15, 23, 42, 0.72) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        animation: fadeIn 0.25s ease-out;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 !important;
        padding: 24px !important;
        box-sizing: border-box !important;
    }
    
    .modal.active {
        display: flex !important;
    }
    
    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 540px !important;
        width: 95% !important;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 32px 80px rgba(15, 23, 42, 0.45);
        margin: auto !important;
        position: relative !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(24px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .modal-header {
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        color: white;
    }
    
    .modal-header-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .modal-header-icon {
        background: rgba(255,255,255,0.15);
        border-radius: 12px;
        width: 44px; height: 44px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    
    .modal-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: white !important;
        margin: 0;
    }
    
    .modal-subtitle {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.75);
        margin: 3px 0 0;
    }
    
    .modal-body-scroll {
        padding: 28px;
        overflow-y: auto;
        flex: 1;
    }
    
    .modal-close {
        background: rgba(255,255,255,0.15);
        border: none;
        color: white;
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background 0.2s;
        flex-shrink: 0;
    }
    
    .modal-close:hover {
        background: rgba(255,255,255,0.3);
    }
    
    .modal-close i {
        color: white !important;
        margin-right: 0 !important;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--color-primary);
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    /* Password Strength Bar */
    .password-strength-bar {
        height: 6px;
        background: #E5E7EB;
        border-radius: 3px;
        overflow: hidden;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .password-strength-bar::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 0;
        transition: all 0.4s ease;
        border-radius: 3px;
    }
    
    .password-strength-bar.weak::before {
        width: 33%;
        background: linear-gradient(90deg, #EF4444, #DC2626);
    }
    
    .password-strength-bar.medium::before {
        width: 66%;
        background: linear-gradient(90deg, #F59E0B, #D97706);
    }
    
    .password-strength-bar.strong::before {
        width: 100%;
        background: linear-gradient(90deg, #10B981, #059669);
    }
    
    .password-strength-text {
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 4px;
    }
    
    .password-strength-text.weak {
        color: #DC2626;
    }
    
    .password-strength-text.medium {
        color: #D97706;
    }
    
    .password-strength-text.strong {
        color: #059669;
    }
    
    /* Password Requirements Checklist */
    .password-requirement {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6B7280;
        transition: all 0.3s ease;
    }
    
    .password-requirement .requirement-icon {
        font-size: 18px;
        color: #D1D5DB;
        transition: all 0.3s ease;
    }
    
    .password-requirement.met {
        color: #059669;
    }
    
    /* Pulse dot y detalles de animación */
    @keyframes sgpPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    .pulse-dot {
        animation: sgpPulse 2s infinite;
    }
</style>

</div><!-- .dashboard-container -->

<!-- Toast: usando NotificationService global (main_layout.php) -->

<script>
    // Flash messages via SGP Toast
    <?php if (Session::hasFlash('success')): ?>
        NotificationService.success('<?= addslashes(Session::getFlash('success')) ?>');
    <?php endif; ?>

    <?php if (Session::hasFlash('error')): ?>
        NotificationService.error('<?= addslashes(Session::getFlash('error')) ?>');
    <?php endif; ?>

    // Helper universal para asegurar teletransporte al body
    function _sgpActivarModal(id) {
        const m = document.getElementById(id);
        if (!m) return null;
        if (m.parentElement !== document.body) {
            document.body.appendChild(m);
        }
        m.classList.add('active');
        document.body.style.overflow = 'hidden';
        return m;
    }
    function _sgpDesactivarModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Edit Modal Functions
    function openEditModal() {
        _sgpActivarModal('editModal');
    }
    
    function closeEditModal() {
        _sgpDesactivarModal('editModal');
    }

    // Email Modal Functions
    function openEmailModal() {
        _sgpActivarModal('emailModal');
    }
    function closeEmailModal() {
        _sgpDesactivarModal('emailModal');
        const form = document.getElementById('formCambiarCorreo');
        if (form) form.reset();
    }

    // Password Modal Functions
    function openPasswordModal() {
        _sgpActivarModal('passwordModal');
        
        // Inicializar validación de contraseña
        const passwordInput = document.getElementById('password_nueva');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        // Limpiar campos
        if (passwordInput) passwordInput.value = '';
        const conf = document.getElementById('password_confirmar');
        if (conf) conf.value = '';
        
        // Resetear barra y checks
        if (strengthBar) strengthBar.className = 'password-strength-bar';
        if (strengthText) strengthText.textContent = '';
        
        // Resetear todos los requisitos
        ['req-length', 'req-uppercase', 'req-lowercase', 'req-number', 'req-special'].forEach(id => {
            const elem = document.getElementById(id);
            if (elem) elem.classList.remove('met');
        });
    }
    
    function closePasswordModal() {
        _sgpDesactivarModal('passwordModal');
        const form = document.getElementById('changePasswordForm');
        if (form) form.reset();
    }
    
    function openPreguntasModal() {
        _sgpActivarModal('preguntasModal');
    }
    
    function closePreguntasModal() {
        _sgpDesactivarModal('preguntasModal');
        const form = document.getElementById('formSecurityQuestions');
        if (form) form.reset();
    }

    // PIN de Asistencia Modal Functions (Pasantes)
    function openPinModal() {
        const modal = _sgpActivarModal('pinModal');
        if (modal) {
            const pinInput = document.getElementById('pin_nuevo');
            if (pinInput) {
                pinInput.value = '';
                const conf = document.getElementById('pin_confirmacion');
                if (conf) conf.value = '';
                setTimeout(() => pinInput.focus(), 120);
            }
        }
    }

    function closePinModal() {
        _sgpDesactivarModal('pinModal');
        const form = document.getElementById('formCambiarPin');
        if (form) form.reset();
    }

    function toggleInputPass(inputId, iconId) {
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

    async function guardarPinAsistencia(e) {
        e.preventDefault();
        const pinNuevo = document.getElementById('pin_nuevo').value.trim();
        const pinConf  = document.getElementById('pin_confirmacion').value.trim();

        if (pinNuevo.length !== 4 || !/^\d{4}$/.test(pinNuevo)) {
            if (window.Swal) {
                Swal.fire({ icon: 'warning', title: 'PIN Inválido', text: 'El PIN debe constar exactamente de 4 dígitos numéricos.', confirmButtonColor: '#2563eb' });
            } else {
                alert('El PIN debe constar exactamente de 4 dígitos numéricos.');
            }
            return;
        }

        if (pinNuevo !== pinConf) {
            if (window.Swal) {
                Swal.fire({ icon: 'warning', title: 'No coincide', text: 'La confirmación del PIN no coincide con el nuevo PIN.', confirmButtonColor: '#2563eb' });
            } else {
                alert('La confirmación del PIN no coincide con el nuevo PIN.');
            }
            return;
        }

        const btn = document.getElementById('btnGuardarPin');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2 ti-spin me-1"></i> Guardando...';

        const formData = new FormData(document.getElementById('formCambiarPin'));

        try {
            const resp = await fetch('<?= URLROOT ?>/perfil/cambiarPin', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            if (res.success) {
                closePinModal();
                if (window.Swal) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡PIN Actualizado!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                window.location.reload();
            } else {
                throw new Error(res.message || 'Error al guardar el PIN');
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

    async function sgpActivarHuellaConConfirmacion() {
        if (!window.Swal) {
            window.sgpRegistrarBiometria();
            return;
        }
        const result = await Swal.fire({
            title: '🔐 Activar Huella Dactilar',
            html: `
                <div style="text-align:center;margin-bottom:14px;">
                    <div style="width:64px;height:64px;margin:0 auto 12px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="ti ti-fingerprint" style="font-size:36px;color:#2563eb;"></i>
                    </div>
                    <h4 style="font-size:16px;color:#0f172a;margin:0 0 6px;font-weight:800;">¿Deseas activar el acceso biométrico?</h4>
                    <p style="font-size:13px;color:#64748b;margin:0;">
                        Al confirmar, tu navegador o teléfono móvil solicitará tu <strong>huella dactilar o Face ID</strong> para vincularlo a tu cuenta institucional.
                    </p>
                </div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px;font-size:12px;color:#166534;text-align:left;">
                    <i class="ti ti-shield-check me-1"></i>Tus datos biométricos quedan guardados únicamente en tu teléfono. Recibirás un correo de confirmación de seguridad.
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-fingerprint me-1"></i>Sí, escanear mi huella',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            if (typeof window.sgpRegistrarBiometria === 'function') {
                await window.sgpRegistrarBiometria();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Módulo Biométrico',
                    text: 'El servicio biométrico no se ha cargado aún. Por favor recarga la página e inténtalo de nuevo.',
                    confirmButtonColor: '#2563eb'
                });
            }
        }
    }
    
    // Email Modal Functions
    function openEmailModal() {
        const modal = document.getElementById('emailModal');
        if (modal) {
            modal.classList.add('active');
            const nuevoInput = document.getElementById('modal_correo_nuevo');
            if (nuevoInput) {
                nuevoInput.value = '';
                document.getElementById('modal_correo_confirmar').value = '';
                document.getElementById('modal_password_actual').value = '';
                setTimeout(() => nuevoInput.focus(), 120);
            }
        }
    }

    function closeEmailModal() {
        const modal = document.getElementById('emailModal');
        if (modal) modal.classList.remove('active');
        const form = document.getElementById('formCambiarCorreo');
        if (form) form.reset();
    }

    async function ejecutarCambioCorreo(e) {
        e.preventDefault();
        const correoNuevo = document.getElementById('modal_correo_nuevo').value.trim();
        const correoConfirmar = document.getElementById('modal_correo_confirmar').value.trim();
        const passActual = document.getElementById('modal_password_actual').value;

        if (!correoNuevo || !correoConfirmar || !passActual) {
            NotificationService.error('Todos los campos son obligatorios');
            return;
        }

        if (correoNuevo.toLowerCase() === document.getElementById('modal_correo_actual').value.trim().toLowerCase()) {
            NotificationService.error('El nuevo correo no puede ser idéntico al actual');
            return;
        }

        if (correoNuevo !== correoConfirmar) {
            NotificationService.error('Los correos electrónicos ingresados no coinciden');
            return;
        }

        const btn = document.getElementById('btnGuardarCorreo');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2 ti-spin me-1"></i> Verificando...';

        const formData = new FormData(document.getElementById('formCambiarCorreo'));
        const csrfMeta = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfMeta && !formData.has('_csrf')) {
            formData.append('_csrf', csrfMeta);
        }

        try {
            const resp = await fetch('<?= URLROOT ?>/perfil/cambiarCorreo', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            if (res.success) {
                closeEmailModal();
                if (window.Swal) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Correo Actualizado!',
                        text: res.message,
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    NotificationService.success(res.message);
                }

                // Actualizar interfaz
                const leftDisplay = document.getElementById('leftProfileEmailDisplay');
                if (leftDisplay) leftDisplay.textContent = res.nuevo_correo || correoNuevo;
                const secDisplay = document.getElementById('securityProfileEmailDisplay');
                if (secDisplay) secDisplay.textContent = res.nuevo_correo || correoNuevo;
                const modalActual = document.getElementById('modal_correo_actual');
                if (modalActual) modalActual.value = res.nuevo_correo || correoNuevo;

                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(res.message || 'Error al actualizar el correo electrónico');
            }
        } catch (err) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Verificación fallida',
                    text: err.message,
                    confirmButtonColor: '#2563eb'
                });
            } else {
                NotificationService.error(err.message);
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }

    // Close modal on outside click
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const passwordModal = document.getElementById('passwordModal');
        const preguntasModal = document.getElementById('preguntasModal');
        const pinModal = document.getElementById('pinModal');
        const emailModal = document.getElementById('emailModal');
        
        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === passwordModal) {
            closePasswordModal();
        }
        if (event.target === preguntasModal) {
            closePreguntasModal();
        }
        if (event.target === pinModal) {
            closePinModal();
        }
        if (event.target === emailModal) {
            closeEmailModal();
        }
    }

    // Handle Edit Profile Form Submission
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        // [FIX-A3] Adjuntar token CSRF al FormData
        formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content || '');

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Verificar que la respuesta sea OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Obtener el texto de la respuesta primero
            return response.text();
        })
        .then(text => {
            // Intentar parsear como JSON
            try {
                const data = JSON.parse(text);
                
                if (data.success) {
                    NotificationService.success(data.message || 'Perfil actualizado exitosamente');
                    closeEditModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    NotificationService.error(data.message || 'Error al actualizar el perfil');
                }
            } catch (e) {
                // Si no es JSON válido, mostrar el error
                console.error('Respuesta del servidor:', text);
                console.error('Error al parsear JSON:', e);
                NotificationService.error('Error del servidor. Revisa la consola para más detalles.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            NotificationService.error('Error de conexión. Por favor intenta nuevamente.');
        });
    });

    // Handle Change Password Form Submission
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const passwordActual = document.getElementById('password_actual').value;
        const passwordNueva = document.getElementById('password_nueva').value;
        const passwordConfirmar = document.getElementById('password_confirmar').value;
        
        // Validar que la nueva contraseña no sea igual a la actual
        if (passwordNueva === passwordActual) {
            NotificationService.error('La nueva contraseña debe ser diferente a la actual');
            return;
        }
        
        // Validar que coincidan
        if (passwordNueva !== passwordConfirmar) {
            NotificationService.error('Las contraseñas no coinciden');
            return;
        }
        
        // Validar longitud
        if (passwordNueva.length < 8) {
            NotificationService.error('La contraseña debe tener al menos 8 caracteres');
            return;
        }
        
        // Validar requisitos completos
        if (!validatePasswordRequirementsInline(passwordNueva)) {
            NotificationService.error('La contraseña debe cumplir todos los requisitos de seguridad');
            return;
        }
        
        const formData = new FormData(this);
        // [FIX-A3] Adjuntar token CSRF al FormData
        formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content || '');

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                NotificationService.success(data.message || 'Contraseña cambiada exitosamente');
                closePasswordModal();
                this.reset();
            } else {
                NotificationService.error(data.message || 'Error al cambiar la contraseña');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            NotificationService.error('Error de conexión. Por favor intenta nuevamente.');
        });
    });
    
    // Función inline para validar requisitos de contraseña y actualizar checks
    function validatePasswordRequirementsInline(password) {
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^a-zA-Z0-9]/.test(password)
        };
        
        // Actualizar UI
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqLowercase = document.getElementById('req-lowercase');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');
        
        if (reqLength) reqLength.classList.toggle('met', requirements.length);
        if (reqUppercase) reqUppercase.classList.toggle('met', requirements.uppercase);
        if (reqLowercase) reqLowercase.classList.toggle('met', requirements.lowercase);
        if (reqNumber) reqNumber.classList.toggle('met', requirements.number);
        if (reqSpecial) reqSpecial.classList.toggle('met', requirements.special);
        
        return Object.values(requirements).every(req => req === true);
    }
    
    // Handle Security Questions Form Submission
    document.getElementById('formSecurityQuestions').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar que no se repitan preguntas
        const p1 = document.getElementById('pregunta_1').value;
        const p2 = document.getElementById('pregunta_2').value;
        const p3 = document.getElementById('pregunta_3').value;
        
        if (p1 === p2 || p1 === p3 || p2 === p3) {
            NotificationService.error('No puedes seleccionar la misma pregunta dos veces');
            return;
        }
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader" style="animation: spin 1s linear infinite;"></i> Guardando...';

        // [FIX-A3] Adjuntar token CSRF al FormData
        formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content || '');

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                NotificationService.success(data.message || 'Preguntas actualizadas exitosamente');
                closePreguntasModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                NotificationService.error(data.message || 'Error al actualizar las preguntas');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            NotificationService.error('Error de conexión. Por favor intenta nuevamente.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Sobrescribir la función updatePasswordStrengthWithRequirements si no existe
    if (typeof updatePasswordStrengthWithRequirements === 'undefined') {
        window.updatePasswordStrengthWithRequirements = function(input, strengthBar, strengthText) {
            const password = input.value;
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength-bar';
                strengthText.textContent = '';
                input.classList.remove('valid', 'invalid');
                
                // Resetear checks
                ['req-length', 'req-uppercase', 'req-lowercase', 'req-number', 'req-special'].forEach(id => {
                    const elem = document.getElementById(id);
                    if (elem) elem.classList.remove('met');
                });
                return;
            }
            
            // Validar requisitos
            const meetsRequirements = validatePasswordRequirementsInline(password);
            
            // Calcular fortaleza
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Ajustar niveles para retroalimentación visual
            let strengthLevel = 'weak';
            if (strength <= 2) strengthLevel = 'weak';
            else if (strength <= 4) strengthLevel = 'medium';
            else strengthLevel = 'strong';
            
            strengthBar.className = `password-strength-bar ${strengthLevel}`;
            
            const messages = {
                weak: 'Débil',
                medium: 'Media',
                strong: 'Fuerte'
            };
            
            strengthText.textContent = messages[strengthLevel];
            strengthText.className = `password-strength-text ${strengthLevel}`;
            
            // Validar que cumpla requisitos mínimos
            if (meetsRequirements) {
                input.classList.remove('invalid');
                input.classList.add('valid');
            } else {
                input.classList.remove('valid');
                input.classList.add('invalid');
            }
        };
    }
</script>

<!-- Validation.js para toggles de contraseña -->
<script src="<?= URLROOT ?>/js/validation.js"></script>

<script>
function subirFotoPerfil(input) {
    const file = input.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'La imagen no puede superar 2 MB.', confirmButtonColor: '#162660' });
        input.value = '';
        return;
    }

    // Preview inmediato
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('profileAvatarImg');
        const ini = document.getElementById('profileAvatarInitials');
        if (img) {
            img.src = e.target.result;
        } else if (ini) {
            const newImg = document.createElement('img');
            newImg.id = 'profileAvatarImg';
            newImg.src = e.target.result;
            newImg.alt = 'Foto de perfil';
            newImg.style.cssText = 'width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;';
            ini.replaceWith(newImg);
        }
        // También actualizar avatar en header
        document.querySelectorAll('.profile-avatar').forEach(el => {
            if (el.tagName !== 'IMG') el.style.backgroundImage = `url(${e.target.result})`;
        });
    };
    reader.readAsDataURL(file);

    // Upload al servidor
    const fd = new FormData();
    fd.append('foto', file);

    fetch('<?= URLROOT ?>/perfil/subirFoto', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (window.notyf) notyf.success(res.message);
                else Swal.fire({ icon: 'success', title: '¡Listo!', text: res.message, timer: 2000, showConfirmButton: false });
            } else {
                if (window.notyf) notyf.error(res.message);
                else Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#162660' });
            }
        })
        .catch(() => {
            if (window.notyf) notyf.error('Error de conexión al subir la foto');
        });

    input.value = '';
}

async function desvincularDispositivoSgp(id, nombre) {
    if (!window.Swal) return;
    const result = await Swal.fire({
        title: '¿Desvincular Dispositivo?',
        text: `¿Deseas revocar el acceso autorizado para "${nombre}"? Tendrás que volver a validar por código OTP si deseas usarlo nuevamente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="ti ti-trash me-1"></i>Sí, desvincular',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const resp = await fetch('<?= URLROOT ?>/dispositivos/desvincular', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ dispositivo_id: id })
            });
            const data = await resp.json();
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Desvinculado',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                location.reload();
            } else {
                throw new Error(data.message || 'Error al desvincular');
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error', text: e.message, confirmButtonColor: '#2563eb' });
        }
    }
}
</script>
