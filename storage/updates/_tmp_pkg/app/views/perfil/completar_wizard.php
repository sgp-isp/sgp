<?php
/**
 * Vista: Wizard de Completar Perfil
 * 
 * PROPÓSITO EDUCATIVO:
 * Esta vista implementa un formulario de 3 pasos (wizard) para que usuarios nuevos
 * configuren su cuenta completamente.
 * 
 * LÓGICA DINÁMICA:
 * - Campos READONLY si el usuario tiene datos pre-poblados (creado por admin)
 * - Campos EDITABLES si el usuario NO tiene datos (registro externo)
 * - Preguntas de seguridad como SELECT (no inputs de texto)
 * - Campo "Cargo" solo para Admin/Tutor, "Departamento" solo para Pasante
 * 
 * FLUJO:
 * Step 1: Cambiar contraseña temporal → nueva contraseña
 * Step 2: Responder 3 preguntas de seguridad
 * Step 3: Completar datos personales
 * 
 * @var object $user Datos del usuario desde PerfilController
 * @var array $questions Preguntas de seguridad desde BD
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Perfil - SGP</title>
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css">
    
    <!-- CSS Moderno para Preguntas de Seguridad -->
    <style>
        .modern-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: white;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23374151' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding: 12px 40px 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .modern-select:hover { border-color: #D1D5DB; background-color: #F9FAFB; }
        .modern-select:focus { outline: none; border-color: var(--color-primary, #162660); box-shadow: 0 0 0 3px rgba(22, 38, 96, 0.1); background-color: white; }
        .input-icon-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 16px; color: var(--color-primary, #162660); font-size: 20px; pointer-events: none; z-index: 10; transition: all 0.3s ease; }
        .input-with-icon { padding-left: 48px !important; border: 2px solid #E5E7EB; border-radius: 10px; font-size: 14px; font-weight: 500; color: #374151; transition: all 0.3s ease; }
        .input-with-icon:hover { border-color: #D1D5DB; background-color: #F9FAFB; }
        .input-with-icon:focus { outline: none; border-color: var(--color-primary, #162660); box-shadow: 0 0 0 3px rgba(22, 38, 96, 0.1); background-color: white; }
        .modern-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; letter-spacing: 0.3px; }
        .modern-label i { margin-right: 6px; color: var(--color-primary, #162660); font-size: 16px; }
        .security-question-row { margin-bottom: 20px; padding: 16px; background: linear-gradient(135deg, #F9FAFB 0%, #FFFFFF 100%); border-radius: 12px; border: 1px solid #F3F4F6; transition: all 0.3s ease; }
        .security-question-row:hover { border-color: #E5E7EB; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04); }
    </style>
    
    <!-- ============================================ -->
    <!-- 🔧 PASSWORD TOGGLE - CARGA TEMPRANA         -->
    <!-- ============================================ -->
    <script>
        /**
         * Función Global: Toggle Password Visibility
         * 
         * UBICACIÓN CRÍTICA: En el <head> para garantizar carga ANTES de los onclick
         * 
         * @param {string} fieldId - ID del campo de contraseña
         * @param {HTMLElement} iconElement - Elemento del icono (this desde onclick)
         */
        window.togglePasswordVisibility = function(fieldId, iconElement) {
            console.log("🔍 Toggle llamado para:", fieldId);
            
            var input = document.getElementById(fieldId);
            
            if (!input) {
                console.error("❌ Input no encontrado:", fieldId);
                return;
            }
            
            if (!iconElement) {
                console.error("❌ Elemento de icono no proporcionado");
                return;
            }
            
            // Toggle tipo de input
            if (input.type === "password") {
                input.type = "text";
                iconElement.classList.remove('ti-eye');
                iconElement.classList.add('ti-eye-off');
                console.log("👁️ Contraseña visible");
            } else {
                input.type = "password";
                iconElement.classList.remove('ti-eye-off');
                iconElement.classList.add('ti-eye');
                console.log("🔒 Contraseña oculta");
            }
        };
        
        console.log("✅ Función togglePasswordVisibility cargada en window.");
    </script>
</head>
<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>
    
    <div class="auth-card" style="max-width: 750px;">
        <div class="auth-header">
            <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" class="auth-logo">
            <h1 class="auth-title">Completar Perfil</h1>
            <p class="auth-subtitle">Configura tu cuenta en 3 sencillos pasos</p>
        </div>

        <div class="wizard-progress" style="margin-bottom: 32px;">
            <div class="progress-steps">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <span class="step-label">Seguridad</span>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <span class="step-label">Preguntas</span>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <span class="step-label">Datos</span>
                </div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>

        <form id="wizardForm" method="POST" action="<?= URLROOT ?>/perfil/guardarWizard">
            
            <!-- ============================================ -->
            <!-- STEP 1: CAMBIO DE CONTRASEÑA -->
            <!-- ============================================ -->
            <div class="wizard-step active" data-step="1">
                <h3 style="font-size: 18px; font-weight: 600; color: var(--color-primary); margin-bottom: 24px;">
                    Cambio de Contraseña
                </h3>

                <div class="form-group has-toggle">
                    <input type="password" name="current_password" id="current_password" class="input-modern" placeholder=" " required style="padding-right: 48px;">
                    <label for="current_password" class="label-floating">
                        <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Contraseña Temporal
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('current_password', this)"></i>
                    <span class="input-hint">Formato: Sgp.TuCédula (Ej: Sgp.12345678)</span>
                </div>

                <div class="form-group has-toggle">
                    <input type="password" name="new_password" id="new_password" class="input-modern" placeholder=" " required style="padding-right: 48px;">
                    <label for="new_password" class="label-floating">
                        <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Nueva Contraseña
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('new_password', this)"></i>
                </div>
                <div class="password-strength-container">
                    <div class="password-strength-bar" id="strengthBar"></div>
                    <span class="password-strength-text" id="strengthText"></span>
                </div>

                <div class="form-group has-toggle">
                    <input type="password" name="confirm_password" id="confirm_password" class="input-modern" placeholder=" " required style="padding-right: 48px;">
                    <label for="confirm_password" class="label-floating">
                        <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Confirmar Contraseña
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('confirm_password', this)"></i>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- STEP 2: PREGUNTAS DE SEGURIDAD -->
            <!-- ============================================ -->
            <div class="wizard-step" data-step="2">
                <h3 style="font-size: 18px; font-weight: 600; color: var(--color-primary); margin-bottom: 8px;">
                    Preguntas de Seguridad
                </h3>
                <p style="font-size: 13px; color: #6B7280; margin-bottom: 20px;">
                    Selecciona 3 preguntas diferentes y proporciona respuestas que solo tú conozcas.
                </p>

                <?php 
                /**
                 * DISEÑO PREMIUM: Grid Layout + Custom Selects + Input Groups
                 */
                for ($i = 1; $i <= 3; $i++): 
                ?>
                <div class="security-question-row">
                    <div class="row">
                        <!-- COLUMNA IZQUIERDA: PREGUNTA CON SELECT PERSONALIZADO -->
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="question_<?= $i ?>" class="modern-label">
                                <i class="ti ti-help-circle"></i>
                                Pregunta <?= $i ?>
                            </label>
                            <select name="question_<?= $i ?>" 
                                    id="question_<?= $i ?>" 
                                    class="modern-select" 
                                    required
                                    onchange="validateDuplicateQuestions()">
                                <option value="" disabled selected>Selecciona una opción...</option>
                                <?php foreach ($questions as $q): ?>
                                <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['pregunta']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-danger" id="error_question_<?= $i ?>" style="display: none; font-size: 12px; margin-top: 6px; font-weight: 600;">
                                <i class="ti ti-alert-circle" style="font-size: 14px;"></i> Esta pregunta ya fue seleccionada
                            </small>
                        </div>

                        <!-- COLUMNA DERECHA: RESPUESTA CON ICONO INTEGRADO -->
                        <div class="col-md-6">
                            <label for="answer_<?= $i ?>" class="modern-label">
                                <i class="ti ti-key"></i>
                                Tu Respuesta
                            </label>
                            <div class="input-icon-wrapper">
                                <input type="text" 
                                       name="answer_<?= $i ?>" 
                                       id="answer_<?= $i ?>" 
                                       class="form-control input-with-icon" 
                                       placeholder="Escribe tu respuesta..." 
                                       required
                                       maxlength="100"
                                       oninput="sanitizeAnswer(this)"
                                       style="height: 48px;">
                                <i class="ti ti-pencil input-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>

                <div class="alert alert-info" style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; padding: 14px; margin-top: 8px;">
                    <i class="ti ti-info-circle" style="margin-right: 8px; color: #3B82F6; font-size: 18px;"></i>
                    <strong>Importante:</strong> Recuerda tus respuestas. Las necesitarás si olvidas tu contraseña.
                </div>
            </div>


            <!-- ============================================ -->
            <!-- STEP 3: DATOS PERSONALES -->
            <!-- ============================================ -->
            <div class="wizard-step" data-step="3">
                <h3 style="font-size: 18px; font-weight: 600; color: var(--color-primary); margin-bottom: 24px;">
                    Datos Personales
                </h3>

                <div class="form-row">
                    <?php
                    /**
                     * LÓGICA DINÁMICA: Campos Readonly vs Editables
                     * 
                     * ESCENARIO A (Usuario Externo - Registro Público):
                     * - $user->nombres es NULL o vacío
                     * - Campos son EDITABLES (sin readonly)
                     * 
                     * ESCENARIO B (Usuario Interno - Creado por Admin):
                     * - $user->nombres tiene valor
                     * - Campos son READONLY (pre-llenados)
                     * 
                     * RAZÓN TÉCNICA:
                     * Evitamos que usuarios creados por admin cambien datos críticos (cedula, nombres)
                     * que ya fueron validados por el administrador.
                     */
                    $esUsuarioExterno = empty($user->nombres) || empty($user->cedula);
                    ?>
                    
                    <div class="form-group">
                        <?php if ($esUsuarioExterno): ?>
                            <input type="text" name="nombres" class="input-modern" placeholder=" " required>
                        <?php else: ?>
                            <input type="text" name="nombres" value="<?= htmlspecialchars($user->nombres) ?>" class="input-modern" placeholder=" " readonly style="background: #F3F4F6;">
                        <?php endif; ?>
                        <label class="label-floating">
                            <i class="ti ti-user" style="margin-right: 8px; font-size: 18px;"></i>Nombres
                        </label>
                    </div>

                    <div class="form-group">
                        <?php if ($esUsuarioExterno): ?>
                            <input type="text" name="apellidos" class="input-modern" placeholder=" " required>
                        <?php else: ?>
                            <input type="text" name="apellidos" value="<?= htmlspecialchars($user->apellidos) ?>" class="input-modern" placeholder=" " readonly style="background: #F3F4F6;">
                        <?php endif; ?>
                        <label class="label-floating">
                            <i class="ti ti-user" style="margin-right: 8px; font-size: 18px;"></i>Apellidos
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <?php if ($esUsuarioExterno): ?>
                            <input type="text" name="cedula" class="input-modern" placeholder=" " required>
                        <?php else: ?>
                            <input type="text" name="cedula" value="<?= htmlspecialchars($user->cedula) ?>" class="input-modern" placeholder=" " readonly style="background: #F3F4F6;">
                        <?php endif; ?>
                        <label class="label-floating">
                            <i class="ti ti-id" style="margin-right: 8px; font-size: 18px;"></i>Cédula
                        </label>
                    </div>

                    <?php
                    /**
                     * LÓGICA BASADA EN ROL:
                     * 
                     * - Pasante (rol_id = 3): Mostrar campo "Departamento" (readonly)
                     * - Admin/Tutor (rol_id = 1 o 2): Mostrar campo "Cargo" (editable)
                     * 
                     * RAZÓN:
                     * Los pasantes son asignados a un departamento por el admin.
                     * Los admin/tutores definen su propio cargo.
                     */
                    ?>
                    <?php if ($user->rol_id == 3): ?>
                        <!-- Campo Departamento para Pasantes (Readonly) -->
                        <div class="form-group">
                            <input type="text" name="departamento" value="<?= htmlspecialchars($user->departamento_nombre ?? 'N/A') ?>" class="input-modern" placeholder=" " readonly style="background: #F3F4F6;">
                            <label class="label-floating">
                                <i class="ti ti-building" style="margin-right: 8px; font-size: 18px;"></i>Departamento
                            </label>
                        </div>
                    <?php else: ?>
                        <!-- Campo Cargo para Admin/Tutor (Editable) -->
                        <div class="form-group">
                            <input type="text" name="cargo" class="input-modern" placeholder="Ej: Analista de Soporte">
                            <label class="label-floating">
                                <i class="ti ti-briefcase" style="margin-right: 8px; font-size: 18px;"></i>Cargo <span class="text-muted" style="font-weight: 400;">(Opcional)</span>
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <input type="tel" name="telefono" class="input-modern" placeholder="0414-1231234" required>
                        <label class="label-floating">
                            <i class="ti ti-phone" style="margin-right: 8px; font-size: 18px;"></i>Teléfono
                        </label>
                    </div>

                    <div class="form-group">
                        <select name="genero" class="input-modern" required>
                            <option value="">Seleccione...</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                        <label class="label-floating">
                            <i class="ti ti-gender-bigender" style="margin-right: 8px; font-size: 18px;"></i>Género
                        </label>
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <div class="form-group">
                        <label class="form-label" style="display:block;margin-bottom:.5rem;font-weight:600;color:#374151;">
                            <i class="ti ti-calendar" style="margin-right:6px;"></i>Fecha de Nacimiento *
                        </label>
                        <input type="text" id="fecha_nacimiento_mask" class="input-modern" placeholder="DD / MM / AAAA" inputmode="numeric">
                        <input type="hidden" id="fecha_nacimiento" name="fecha_nacimiento" required>
                    </div>
                </div>
            </div>

            <!-- Botones de Navegación -->
            <div class="wizard-buttons">
                <button type="button" class="btn-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                    <i class="ti ti-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn-primary" id="nextBtn" onclick="changeStep(1)">
                    Siguiente <i class="ti ti-arrow-right"></i>
                </button>
                <button type="submit" class="btn-primary" id="submitBtn" style="display: none;">
                    <i class="ti ti-check"></i> Finalizar
                </button>
            </div>
        </form>

        <div class="auth-footer">
            <a href="<?= URLROOT ?>/auth/logout" class="auth-link">
                <i class="ti ti-logout"></i> Cerrar Sesión
            </a>
        </div>
    </div>

    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>

    <style>
        .wizard-progress { margin-bottom: 32px; }
        .progress-steps { display: flex; justify-content: space-between; margin-bottom: 16px; position: relative; }
        .step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
        .step-circle { width: 40px; height: 40px; border-radius: 50%; background: #E5E7EB; color: #6B7280; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 8px; transition: all 0.3s; z-index: 2; }
        .step.active .step-circle { background: var(--color-primary); color: white; }
        .step.completed .step-circle { background: #10B981; color: white; }
        .step-label { font-size: 12px; color: #6B7280; font-weight: 500; }
        .step.active .step-label { color: var(--color-primary); font-weight: 600; }
        .progress-bar { height: 4px; background: #E5E7EB; border-radius: 2px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--color-primary); width: 0%; transition: width 0.3s ease; }
        .wizard-step { display: none; }
        .wizard-step.active { display: block; animation: slideUp 0.4s ease-out; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .wizard-buttons { display: flex; gap: 12px; margin-top: 32px; }
        .wizard-buttons button { flex: 1; }
        .password-strength-container { margin-top: -12px; margin-bottom: 20px; }
        .password-strength-bar { height: 4px; background: #E5E7EB; border-radius: 2px; overflow: hidden; margin-bottom: 4px; }
        .password-strength-bar::after { content: ''; display: block; height: 100%; width: 0%; transition: all 0.3s; }
        .password-strength-bar.weak::after { width: 33%; background: #EF4444; }
        .password-strength-bar.medium::after { width: 66%; background: #F59E0B; }
        .password-strength-bar.strong::after { width: 100%; background: #10B981; }
        .password-strength-text { font-size: 12px; color: #6B7280; }
        .input-hint { display: block; font-size: 11px; color: #6B7280; margin-top: 4px; }
    </style>

    <script src="<?= URLROOT ?>/assets/libs/imask/imask.min.js"></script>
    <script src="<?= URLROOT ?>/js/validation.js"></script>
    <script>
        var currentStep = 1;
        var totalSteps = 3;

        // ── IMask: Fecha de Nacimiento ──────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            var maskEl    = document.getElementById('fecha_nacimiento_mask');
            var hiddenEl  = document.getElementById('fecha_nacimiento');

            if (!maskEl || !hiddenEl) return;

            var dateMask = IMask(maskEl, {
                mask: Date,
                pattern: 'd / `m / `Y',
                blocks: {
                    d: { mask: IMask.MaskedRange, from: 1, to: 31, maxLength: 2 },
                    m: { mask: IMask.MaskedRange, from: 1, to: 12, maxLength: 2 },
                    Y: { mask: IMask.MaskedRange, from: 1930, to: new Date().getFullYear() - 10, maxLength: 4 }
                },
                format: function (date) {
                    var d = date.getDate().toString().padStart(2, '0');
                    var m = (date.getMonth() + 1).toString().padStart(2, '0');
                    return [d, m, date.getFullYear()].join(' / ');
                },
                parse: function (str) {
                    var p = str.split(' / ');
                    return new Date(p[2], p[1] - 1, p[0]);
                },
                autofix: true,
                lazy: true
            });

            dateMask.on('accept', function () { hiddenEl.value = ''; });

            dateMask.on('complete', function () {
                var v = dateMask.unmaskedValue;
                if (v.length === 8) {
                    hiddenEl.value = v.substring(4) + '-' + v.substring(2, 4) + '-' + v.substring(0, 2);
                }
            });
        });

        // ── Navegación de pasos ─────────────────────────────────────
        function changeStep(direction) {
            if (direction === 1 && !validateStep(currentStep)) return;
            currentStep = Math.min(Math.max(currentStep + direction, 1), totalSteps);
            updateUI();
        }

        // ── Validación por paso ─────────────────────────────────────
        function validateStep(step) {
            if (step === 1) {
                const newPass     = document.getElementById('new_password').value;
                const confirmPass = document.getElementById('confirm_password').value;
                const currentPass = document.getElementById('current_password').value;

                if (!currentPass) {
                    NotificationService.error('Ingresa tu contraseña temporal');
                    document.getElementById('current_password').focus();
                    return false;
                }
                if (newPass.length < 8) {
                    NotificationService.error('La contraseña debe tener al menos 8 caracteres');
                    document.getElementById('new_password').focus();
                    return false;
                }
                const validation = validatePasswordRequirements(newPass);
                if (!validation.isValid) {
                    NotificationService.error(validation.message);
                    return false;
                }
                if (newPass !== confirmPass) {
                    NotificationService.error('Las contraseñas no coinciden');
                    document.getElementById('confirm_password').focus();
                    return false;
                }
                return true;
            }

            if (step === 2) {
                const q1 = document.getElementById('question_1').value;
                const q2 = document.getElementById('question_2').value;
                const q3 = document.getElementById('question_3').value;

                if (!q1 || !q2 || !q3) {
                    NotificationService.error('Debes seleccionar las 3 preguntas de seguridad');
                    return false;
                }

                const a1 = document.getElementById('answer_1').value.trim();
                const a2 = document.getElementById('answer_2').value.trim();
                const a3 = document.getElementById('answer_3').value.trim();

                if (!a1 || !a2 || !a3) {
                    NotificationService.error('Debes responder las 3 preguntas de seguridad');
                    return false;
                }
                if (a1.length < 3 || a2.length < 3 || a3.length < 3) {
                    NotificationService.error('Cada respuesta debe tener al menos 3 caracteres');
                    return false;
                }
                return true;
            }

            if (step === 3) {
                const telefono = document.querySelector('[name="telefono"]');
                const fnac     = document.getElementById('fecha_nacimiento');
                const genero   = document.querySelector('[name="genero"]');

                if (!telefono || !telefono.value.trim()) {
                    NotificationService.error('El campo Teléfono es obligatorio');
                    if (telefono) telefono.focus();
                    return false;
                }
                if (!fnac || !fnac.value) {
                    NotificationService.error('La Fecha de Nacimiento es obligatoria');
                    document.getElementById('fecha_nacimiento_mask').focus();
                    return false;
                }
                if (!genero || !genero.value) {
                    NotificationService.error('El campo Género es obligatorio');
                    if (genero) genero.focus();
                    return false;
                }
                return true;
            }

            return true;
        }

        // ── Actualizar UI del wizard ────────────────────────────────
        function updateUI() {
            document.querySelectorAll('.wizard-step').forEach(s => s.classList.remove('active'));
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.add('active');

            document.querySelectorAll('.step').forEach((s, i) => {
                s.classList.remove('active', 'completed');
                if (i + 1 < currentStep)      s.classList.add('completed');
                else if (i + 1 === currentStep) s.classList.add('active');
            });

            document.getElementById('progressFill').style.width =
                ((currentStep - 1) / (totalSteps - 1)) * 100 + '%';

            document.getElementById('prevBtn').style.display   = currentStep === 1          ? 'none' : 'flex';
            document.getElementById('nextBtn').style.display   = currentStep === totalSteps ? 'none' : 'flex';
            document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'flex' : 'none';
        }

        // ── Fortaleza de contraseña ─────────────────────────────────
        document.getElementById('new_password').addEventListener('input', function () {
            updatePasswordStrengthWithRequirements(
                this,
                document.getElementById('strengthBar'),
                document.getElementById('strengthText')
            );
        });

        // ── Submit ──────────────────────────────────────────────────
        document.getElementById('wizardForm').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateStep(currentStep)) return;

            const btn = document.getElementById('submitBtn');
            if (typeof setLoading === 'function') {
                setLoading(btn, true, 'Guardando...');
            } else {
                btn.innerHTML = '<i class="ti ti-loader"></i> Guardando...';
                btn.disabled  = true;
            }
            this.submit();
        });

        // ── Preguntas duplicadas ────────────────────────────────────
        function validateDuplicateQuestions() {
            const q1 = document.getElementById('question_1').value;
            const q2 = document.getElementById('question_2').value;
            const q3 = document.getElementById('question_3').value;

            ['error_question_1','error_question_2','error_question_3'].forEach(id => {
                document.getElementById(id).style.display = 'none';
            });

            let hasDuplicate = false;

            if (q1 && q2 && q1 === q2) { document.getElementById('error_question_2').style.display = 'block'; document.getElementById('question_2').value = ''; hasDuplicate = true; }
            if (q1 && q3 && q1 === q3) { document.getElementById('error_question_3').style.display = 'block'; document.getElementById('question_3').value = ''; hasDuplicate = true; }
            if (q2 && q3 && q2 === q3) { document.getElementById('error_question_3').style.display = 'block'; document.getElementById('question_3').value = ''; hasDuplicate = true; }

            if (hasDuplicate) NotificationService.warning('Debes seleccionar 3 preguntas diferentes');
        }

        // ── Sanitizar respuestas ────────────────────────────────────
        function sanitizeAnswer(input) {
            const sanitized = input.value.replace(/[^a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚüÜ]/g, '');
            if (input.value !== sanitized) {
                input.value = sanitized;
                input.style.borderColor = '#EF4444';
                setTimeout(() => { input.style.borderColor = '#D1D5DB'; }, 500);
            }
        }
    </script>

</body>
</html>
