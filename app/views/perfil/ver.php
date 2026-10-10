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
        <div class="col-md-4" style="flex: 1; min-width: 300px; max-width: 400px;">
            <div class="smart-card" style="text-align: center;">
                <!-- Avatar con opción de cambio -->
                <?php
                $avatarFile = $user['avatar'] ?? 'default.png';
                $avatarUrl  = ($avatarFile !== 'default.png')
                    ? URLROOT . '/img/avatars/' . htmlspecialchars($avatarFile)
                    : null;
                ?>
                <div style="position:relative;width:120px;height:120px;margin:0 auto 20px;">
                    <?php if ($avatarUrl): ?>
                    <img id="profileAvatarImg" src="<?= $avatarUrl ?>" alt="Foto de perfil"
                         style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
                    <?php else: ?>
                    <div id="profileAvatarInitials" style="width:120px;height:120px;background:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:48px;color:white;font-weight:700;">
                        <?= strtoupper(substr($user['nombres'] ?? $user['correo'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <!-- Botón cambiar foto -->
                    <label for="inputFotoPerfil" title="Cambiar foto — JPG, PNG o WEBP, máx. 2 MB"
                           style="position:absolute;bottom:0;right:0;width:32px;height:32px;background:#162660;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid white;transition:background .2s;"
                           onmouseover="this.style.background='#6366f1'" onmouseout="this.style.background='#162660'">
                        <i class="ti ti-camera" style="color:white;font-size:0.9rem;"></i>
                    </label>
                    <input type="file" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="subirFotoPerfil(this)">
                </div>
                
                <!-- Name -->
                <h3 style="font-size: 1.25rem; color: var(--color-primary); font-weight: 700; margin-bottom: 8px;">
                    <?= htmlspecialchars(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')) ?: 'Usuario' ?>
                </h3>
                
                <!-- Role Badge -->
                <span style="display: inline-block; background: rgba(22, 38, 96, 0.1); color: var(--color-primary); padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 16px;">
                    <?= htmlspecialchars($user['rol_nombre'] ?? 'Sin rol') ?>
                </span>
                
                <!-- Status -->
                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 24px;">
                    <i class="ti ti-circle-check" style="color: #10B981; font-size: 16px;"></i>
                    <span style="color: #059669; font-size: 0.9rem; font-weight: 600;">
                        <?= ucfirst($user['estado'] ?? 'activo') ?>
                    </span>
                </div>
                
                <!-- Divider -->
                <div style="border-top: 1px solid #E5E7EB; padding-top: 20px; text-align: left;">
                    <!-- Email -->
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; margin-bottom: 4px;">
                            <i class="ti ti-mail" style="color: var(--color-primary); font-size: 14px; margin-right: 8px;"></i>
                            <span style="font-size: 0.7rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Correo</span>
                        </div>
                        <div style="color: var(--color-primary); font-weight: 500; font-size: 0.9rem; word-break: break-word;">
                            <?= htmlspecialchars($user['correo']) ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($user['departamento_nombre'])): ?>
                    <!-- Department -->
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; margin-bottom: 4px;">
                            <i class="ti ti-building" style="color: var(--color-primary); font-size: 14px; margin-right: 8px;"></i>
                            <span style="font-size: 0.7rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Departamento</span>
                        </div>
                        <div style="color: var(--color-primary); font-weight: 500; font-size: 0.9rem;">
                            <?= htmlspecialchars($user['departamento_nombre']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Member Since -->
                    <div>
                        <div style="display: flex; align-items: center; margin-bottom: 4px;">
                            <i class="ti ti-calendar" style="color: var(--color-primary); font-size: 14px; margin-right: 8px;"></i>
                            <span style="font-size: 0.7rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Miembro desde</span>
                        </div>
                        <div style="color: var(--color-primary); font-weight: 500; font-size: 0.9rem;">
                            <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Detailed Info -->
        <div class="col-md-8" style="flex: 2; min-width: 300px;">
            <div class="smart-card">
                <?php 
                // ALERTA: Perfil Incompleto (solo para pasantes)
                if (Session::get('role_id') == 3) {
                    $incompleto = empty($user['telefono']) || empty($user['institucion_procedencia']);
                    if ($incompleto):
                ?>
                <div class="alert alert-warning" style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <i class="ti ti-alert-circle" style="color: #F59E0B; font-size: 24px;"></i>
                    <div>
                        <strong style="color: #92400E; font-size: 14px; display: block; margin-bottom: 4px;">⚠️ Perfil Incompleto</strong>
                        <span style="color: #78350F; font-size: 13px;">Actualiza tus datos de contacto e institución de procedencia para completar tu perfil.</span>
                    </div>
                </div>
                <?php endif; } ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-top: 1px solid #E5E7EB;">
                    <h3 style="font-size: 1.2rem; color: var(--color-primary); font-weight: 700; margin: 0;">
                        <i class="ti ti-user" style="margin-right: 8px;"></i> Datos Personales
                    </h3>
                    <button class="btn-primary" style="width: auto; padding: 8px 16px; font-size: 0.9rem;" onclick="openEditModal()">
                        <i class="ti ti-edit"></i> Editar
                    </button>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                    <!-- Cédula -->
                    <div>
                        <div style="display: flex; align-items: center; margin-bottom: 6px;">
                            <i class="ti ti-id-badge" style="color: var(--color-primary); margin-right: 8px;"></i>
                            <span style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Cédula de Identidad</span>
                        </div>
                        <h5 style="font-weight: 600; margin: 0; color: #1F2937; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['cedula'] ?? 'No especificado') ?>
                        </h5>
                    </div>
                    
                    <!-- Teléfono -->
                    <div>
                        <div style="display: flex; align-items: center; margin-bottom: 6px;">
                            <i class="ti ti-phone" style="color: var(--color-primary); margin-right: 8px;"></i>
                            <span style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Teléfono</span>
                        </div>
                        <h5 style="font-weight: 600; margin: 0; color: #1F2937; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['telefono'] ?? 'No especificado') ?>
                        </h5>
                    </div>
                    
                    <!-- Cargo (SOLO para Admin/Tutor, NO para Pasantes) -->
                    <?php if (Session::get('role_id') != 3): ?>
                    <div>
                        <div style="display: flex; align-items: center; margin-bottom: 6px;">
                            <i class="ti ti-briefcase" style="color: var(--color-primary); margin-right: 8px;"></i>
                            <span style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Cargo</span>
                        </div>
                        <h5 style="font-weight: 600; margin: 0; color: #1F2937; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['cargo'] ?? 'No especificado') ?>
                        </h5>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Teléfono -->
                    <div>
                        <div style="display: flex; align-items: center; margin-bottom: 6px;">
                            <i class="ti ti-gender-bigender" style="color: var(--color-primary); margin-right: 8px;"></i>
                            <span style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Género</span>
                        </div>
                        <h5 style="font-weight: 600; margin: 0; color: #1F2937; font-size: 1.05rem;">
                            <?= isset($user['genero']) ? ($user['genero'] == 'M' ? 'Masculino' : 'Femenino') : 'No especificado' ?>
                        </h5>
                    </div>
                    
                    <!-- Fecha de Nacimiento -->
                    <div>
                        <div style="display: flex; align-items: center; margin-bottom: 6px;">
                            <i class="ti ti-cake" style="color: var(--color-primary); margin-right: 8px;"></i>
                            <span style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Fecha de Nacimiento</span>
                        </div>
                        <h5 style="font-weight: 600; margin: 0; color: #1F2937; font-size: 1.05rem;">
                            <?= isset($user['fecha_nacimiento']) ? date('d/m/Y', strtotime($user['fecha_nacimiento'])) : 'No especificado' ?>
                        </h5>
                    </div>
                    
                    <?php if (Session::get('role_id') == 3 && !empty($user['institucion_procedencia'])): ?>
                    <div style="grid-column: 1 / -1;">
                        <div style="display: flex; align-items: center; margin-bottom: 6px;">
                            <i class="ti ti-building-bank" style="color: var(--color-primary); margin-right: 8px;"></i>
                            <span style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Institución de Procedencia</span>
                        </div>
                        <h5 style="font-weight: 600; margin: 0; color: #1F2937; font-size: 1.05rem;">
                            <?= htmlspecialchars($user['institucion_nombre'] ?? $user['institucion_procedencia'] ?? 'No especificada') ?>
                        </h5>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tarjeta Institución + Representante (solo pasantes) -->
            <?php if (Session::get('role_id') == 3 && !empty($user['inst_rep_nombre'])): ?>
            <div class="smart-card" style="margin-top: 24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #E5E7EB;">
                    <h3 style="font-size:1.1rem;color:var(--color-primary);font-weight:700;margin:0;">
                        <i class="ti ti-school" style="margin-right:8px;"></i> Representante de Institución
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

            <!-- Tarjeta de Seguridad -->
            <div class="smart-card" style="margin-top: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #E5E7EB;">
                    <h3 style="font-size: 1.2rem; color: var(--color-primary); font-weight: 700; margin: 0;">
                        <i class="ti ti-shield" style="margin-right: 8px;"></i> Seguridad
                    </h3>
                </div>

                <div class="profile-security-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <!-- Cambiar Contraseña -->
                    <div style="background: #F9FAFB; padding: 20px; border-radius: 12px; border: 1px solid #E5E7EB;">
                        <div style="display: flex; align-items: center; margin-bottom: 12px;">
                            <i class="ti ti-key" style="color: var(--color-primary); font-size: 24px; margin-right: 12px;"></i>
                            <div>
                                <h5 style="margin: 0; font-weight: 600; color: var(--color-primary);">Contraseña</h5>
                                <p style="margin: 0; font-size: 0.85rem; color: #6B7280;">Actualiza tu contraseña periódicamente</p>
                            </div>
                        </div>
                        <button class="btn-primary" style="width: 100%; margin-top: 12px;" onclick="openPasswordModal()">
                            <i class="ti ti-lock"></i> Cambiar Contraseña
                        </button>
                    </div>

                    <!-- Preguntas de Seguridad -->
                    <div style="background: #F9FAFB; padding: 20px; border-radius: 12px; border: 1px solid #E5E7EB;">
                        <div style="display: flex; align-items: center; margin-bottom: 12px;">
                            <i class="ti ti-help-circle" style="color: var(--color-primary); font-size: 24px; margin-right: 12px;"></i>
                            <div>
                                <h5 style="margin: 0; font-weight: 600; color: var(--color-primary);">Preguntas de Seguridad</h5>
                                <p style="margin: 0; font-size: 0.85rem; color: #6B7280;">Para recuperación de cuenta</p>
                            </div>
                        </div>
                        <button class="btn-primary" style="width: 100%; margin-top: 12px;" onclick="openPreguntasModal()">
                            <i class="ti ti-settings"></i> Gestionar Preguntas
                        </button>
                    </div>
                </div>
            </div>
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

<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1100;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(6px);
        animation: fadeIn 0.3s;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 520px;
        width: 90%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s;
        box-shadow: 0 32px 80px rgba(15, 23, 42, 0.3);
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
    
    .password-requirement.met .requirement-icon {
        color: #10B981;
        transform: scale(1.1);
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

    // Edit Modal Functions
    function openEditModal() {
        document.getElementById('editModal').classList.add('active');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    // Password Modal Functions
    function openPasswordModal() {
        document.getElementById('passwordModal').classList.add('active');
        
        // Inicializar validación de contraseña
        const passwordInput = document.getElementById('password_nueva');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        // Limpiar campos
        passwordInput.value = '';
        document.getElementById('password_confirmar').value = '';
        
        // Resetear barra y checks
        strengthBar.className = 'password-strength-bar';
        strengthText.textContent = '';
        
        // Resetear todos los requisitos
        ['req-length', 'req-uppercase', 'req-lowercase', 'req-number', 'req-special'].forEach(id => {
            const elem = document.getElementById(id);
            if (elem) elem.classList.remove('met');
        });
    }
    
    function closePasswordModal() {
        document.getElementById('passwordModal').classList.remove('active');
        document.getElementById('changePasswordForm').reset();
    }
    
    function openPreguntasModal() {
        document.getElementById('preguntasModal').classList.add('active');
    }
    
    function closePreguntasModal() {
        document.getElementById('preguntasModal').classList.remove('active');
        document.getElementById('formSecurityQuestions').reset();
    }
    
    // Close modal on outside click
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const passwordModal = document.getElementById('passwordModal');
        const preguntasModal = document.getElementById('preguntasModal');
        
        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === passwordModal) {
            closePasswordModal();
        }
        if (event.target === preguntasModal) {
            closePreguntasModal();
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
</script>
