<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Registro</title>
    
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png?v=3">
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/assets/libs/choices/choices.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/choices-sgp.css">
    
    <!-- ============================================ -->
    <!-- DEFINIR URLROOT PARA JAVASCRIPT -->
    <!-- ============================================ -->
    <script>
        /**
         * Definir constante URLROOT para uso en JavaScript
         * Necesario para notifications.js y otros scripts del sistema
         */
        const URLROOT = <?php echo json_encode(URLROOT, JSON_UNESCAPED_SLASHES) ?>; // [FIX-C3]
    </script>
    
    <!-- JAVASCRIPT EN HEAD - FUNCIONES ESPECÍFICAS DEL REGISTRO -->
    <script>
        console.log("=== SISTEMA DE REGISTRO INICIADO ===");
        // Las funciones de validación básica se cargan desde validation.js
        // Aquí solo definimos las funciones específicas del flujo de registro
        
        // VALIDAR PASO 1 (con lógica específica de registro)
        window.validarPaso1 = function() {
            console.log("🔍 Validando paso 1...");
            
            var nombre = document.getElementById('nombre');
            var cedula = document.getElementById('cedula');
            var email = document.getElementById('email');
            var password = document.getElementById('password');
            var confirm = document.getElementById('password_confirm');
            
            if (!nombre || !nombre.value.trim()) {
                NotificationService.error('Por favor ingresa tu nombre completo');
                if (nombre) nombre.focus();
                return false;
            }
            
            if (!cedula || !cedula.value.trim()) {
                NotificationService.error('Por favor ingresa tu cédula');
                if (cedula) cedula.focus();
                return false;
            }
            
            if (!/^[0-9]{7,8}$/.test(cedula.value)) {
                NotificationService.error('La cédula debe contener solo números (7-8 dígitos)');
                cedula.focus();
                return false;
            }
            
            if (!email || !email.value.trim()) {
                NotificationService.error('Por favor ingresa tu correo electrónico');
                if (email) email.focus();
                return false;
            }
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                NotificationService.error('Por favor ingresa un correo electrónico válido');
                email.focus();
                return false;
            }
            
            var institucion = document.getElementById('institucion_id');
            if (!institucion || !institucion.value) {
                NotificationService.error('Por favor selecciona tu institución de procedencia');
                if (institucion) institucion.focus();
                return false;
            }
            
            if (!password || !password.value) {
                NotificationService.error('Por favor ingresa una contraseña');
                if (password) password.focus();
                return false;
            }
            
            if (password.value.length < 8) {
                NotificationService.error('La contraseña debe tener al menos 8 caracteres');
                password.focus();
                return false;
            }
            
            if (!confirm || !confirm.value) {
                NotificationService.error('Por favor confirma tu contraseña');
                if (confirm) confirm.focus();
                return false;
            }
            
            if (password.value !== confirm.value) {
                NotificationService.error('Las contraseñas no coinciden');
                confirm.focus();
                return false;
            }
            
            console.log("✅ Paso 1 validado correctamente");
            return true;
        };
        
        // AVANZAR AL PASO 2
        window.nextStep = function() {
            console.log("➡️ Intentando avanzar al paso 2...");
            
            if (!window.validarPaso1()) {
                console.log("❌ Validación fallida");
                return;
            }
            
            var step1 = document.getElementById('step1');
            var step2 = document.getElementById('step2');
            
            if (!step1 || !step2) {
                console.error("❌ No se encontraron los contenedores de pasos");
                return;
            }
            
            step1.style.display = 'none';
            step2.style.display = 'block';
            
            var steps = document.querySelectorAll('.steps-indicator .step');
            if (steps.length >= 2) {
                steps[0].classList.remove('active');
                steps[0].classList.add('completed');
                steps[1].classList.add('active');
                console.log("✅ Indicador visual actualizado");
            }
            
            console.log("✅ Avanzado a paso 2");
        };
        
        // VOLVER AL PASO 1
        window.prevStep = function() {
            console.log("⬅️ Volviendo al paso 1...");
            
            var step1 = document.getElementById('step1');
            var step2 = document.getElementById('step2');
            
            if (!step1 || !step2) {
                console.error("❌ No se encontraron los contenedores de pasos");
                return;
            }
            
            step2.style.display = 'none';
            step1.style.display = 'block';
            
            var steps = document.querySelectorAll('.steps-indicator .step');
            if (steps.length >= 2) {
                steps[0].classList.add('active');
                steps[0].classList.remove('completed');
                steps[1].classList.remove('active');
                console.log("✅ Indicador visual revertido");
            }
            
            console.log("✅ Vuelto a paso 1");
        };
        
        // VALIDAR PREGUNTAS DE SEGURIDAD
        window.validarPreguntasSeguridad = function() {
            console.log("🔍 Validando preguntas de seguridad...");
            
            var pregunta1 = document.getElementById('pregunta_1');
            var pregunta2 = document.getElementById('pregunta_2');
            var pregunta3 = document.getElementById('pregunta_3');
            
            var respuesta1 = document.getElementById('respuesta_1');
            var respuesta2 = document.getElementById('respuesta_2');
            var respuesta3 = document.getElementById('respuesta_3');
            
            if (!pregunta1 || !pregunta1.value || pregunta1.value === '') {
                NotificationService.error('Por favor selecciona la Pregunta de Seguridad 1');
                if (pregunta1) pregunta1.focus();
                return false;
            }
            
            if (!pregunta2 || !pregunta2.value || pregunta2.value === '') {
                NotificationService.error('Por favor selecciona la Pregunta de Seguridad 2');
                if (pregunta2) pregunta2.focus();
                return false;
            }
            
            if (!pregunta3 || !pregunta3.value || pregunta3.value === '') {
                NotificationService.error('Por favor selecciona la Pregunta de Seguridad 3');
                if (pregunta3) pregunta3.focus();
                return false;
            }
            
            if (pregunta1.value === pregunta2.value || pregunta1.value === pregunta3.value || pregunta2.value === pregunta3.value) {
                NotificationService.error('Debes seleccionar 3 preguntas diferentes');
                return false;
            }
            
            if (!respuesta1 || !respuesta1.value.trim()) {
                NotificationService.error('Por favor ingresa la Respuesta 1');
                if (respuesta1) respuesta1.focus();
                return false;
            }
            
            if (!respuesta2 || !respuesta2.value.trim()) {
                NotificationService.error('Por favor ingresa la Respuesta 2');
                if (respuesta2) respuesta2.focus();
                return false;
            }
            
            if (!respuesta3 || !respuesta3.value.trim()) {
                NotificationService.error('Por favor ingresa la Respuesta 3');
                if (respuesta3) respuesta3.focus();
                return false;
            }
            
            var r1 = respuesta1.value.trim().toLowerCase();
            var r2 = respuesta2.value.trim().toLowerCase();
            var r3 = respuesta3.value.trim().toLowerCase();
            
            if (r1 === r2 || r1 === r3 || r2 === r3) {
                NotificationService.error('Las respuestas de seguridad deben ser diferentes');
                return false;
            }
            
            console.log("✅ Preguntas de seguridad validadas correctamente");
            return true;
        };
        
        console.log("✅ Funciones de registro cargadas correctamente");
    </script>
</head>
<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>
    
    <div class="auth-card" style="max-width: 550px; position: relative;">
        <!-- Back Button -->
        <a href="<?= URLROOT ?>/auth/login" class="btn-back" title="Volver al Login">
            <i class="ti ti-arrow-left"></i>
        </a>

        <div class="auth-header">
            <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" class="auth-logo">
            <h1 class="auth-title">Crear Cuenta</h1>
            <p class="auth-subtitle">Regístrate como pasante en el sistema</p>
        </div>

        <!-- Steps Indicator -->
        <div class="steps-indicator">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-label">Datos Personales</div>
            </div>
            <div class="step-divider"></div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-label">Seguridad</div>
            </div>
        </div>

        <form action="<?= URLROOT ?>/auth/register" method="POST" id="registerForm">
            
            <!-- PASO 1: Datos Personales -->
            <div id="step1" style="display: block;">
                <!-- Nombre Completo -->
                <div class="form-group">
                    <input type="text" name="name" id="nombre" class="input-modern" placeholder=" " required accept-charset="UTF-8" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <label for="nombre" class="label-floating">
                        <i class="ti ti-user" style="margin-right: 8px; font-size: 18px;"></i>Nombre Completo
                    </label>
                </div>

                <!-- Cédula -->
                <div class="form-group">
                    <input type="text" name="cedula" id="cedula" class="input-modern" placeholder=" " required autocomplete="off" inputmode="numeric" maxlength="8" value="<?= htmlspecialchars($_POST['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?>" oninput="validateCedulaInput(event)" data-check-url="<?= URLROOT ?>/auth/checkAvailability">
                    <label for="cedula" class="label-floating">
                        <i class="ti ti-id-badge-2" style="margin-right: 8px; font-size: 18px;"></i>Cédula de Identidad
                    </label>
                    <div class="cedula-feedback" style="font-size:.75rem;margin-top:3px;min-height:16px;font-weight:600;"></div>
                    <small class="form-hint"><i class="ti ti-info-circle"></i> Solo números (máx. 8 dígitos)</small>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <input type="email" name="email" id="email" class="input-modern validate-email" placeholder=" " required autocomplete="off" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" onblur="validateEmailWithFeedback(this)" data-check-url="<?= URLROOT ?>/auth/checkAvailability">
                    <label for="email" class="label-floating">
                        <i class="ti ti-mail" style="margin-right: 8px; font-size: 18px;"></i>Correo Electrónico
                    </label>
                    <div class="email-feedback"></div>
                    <small class="form-hint">Usaremos este correo para recuperar tu cuenta</small>
                </div>

                <!-- Institución de Procedencia -->
                <div class="form-group" style="margin-top: 1.25rem;">
                    <label for="institucion_id" class="form-label" style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.85rem; color: #374151;">
                        <i class="ti ti-school" style="margin-right: 6px; color: #2563eb;"></i>Institución Educativa de Procedencia *
                    </label>
                    <select name="institucion_id" id="institucion_id" class="input-modern" required style="cursor: pointer;">
                        <option value="">Selecciona tu universidad o instituto...</option>
                        <?php 
                        $instList = $data['instituciones'] ?? $instituciones ?? [];
                        foreach ($instList as $instItem):
                            $iId = is_object($instItem) ? $instItem->id : $instItem['id'];
                            $iNom = is_object($instItem) ? $instItem->nombre : $instItem['nombre'];
                            $selected = (isset($_POST['institucion_id']) && (int)$_POST['institucion_id'] === (int)$iId) ? 'selected' : '';
                        ?>
                        <option value="<?= (int)$iId ?>" <?= $selected ?>><?= htmlspecialchars($iNom) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint" style="margin-top: 4px; display: block;">Casa de estudios de donde provienes</small>
                </div>

                <!-- Contraseña -->
                <div class="form-group has-toggle">
                    <input type="password" name="password" id="password" class="input-modern" placeholder=" " required oninput="actualizarFortaleza(this)">
                    <label for="password" class="label-floating">
                        <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Contraseña
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="window.togglePass('password', this)"></i>
                    <i class="ti ti-check input-feedback icon-check"></i>
                    <i class="ti ti-x input-feedback icon-error"></i>
                </div>
                
                <!-- Texto guía minimalista -->
                <span class="password-hint" style="display: block; margin-top: -16px; margin-bottom: 8px;">
                    Mín. 8 caracteres: mayúscula, minúscula, número y símbolo
                </span>
                
                <!-- Indicador de fortaleza -->
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-strength-text" id="strengthText"></div>
                
                <!-- Requisitos de contraseña -->
                <div class="password-requirements">
                    <div class="requirement" id="req-length">
                        <i class="ti ti-circle"></i>
                        <span>Mínimo 8 caracteres</span>
                    </div>
                    <div class="requirement" id="req-uppercase">
                        <i class="ti ti-circle"></i>
                        <span>Una letra mayúscula (A-Z)</span>
                    </div>
                    <div class="requirement" id="req-lowercase">
                        <i class="ti ti-circle"></i>
                        <span>Una letra minúscula (a-z)</span>
                    </div>
                    <div class="requirement" id="req-number">
                        <i class="ti ti-circle"></i>
                        <span>Un número (0-9)</span>
                    </div>
                    <div class="requirement" id="req-special">
                        <i class="ti ti-circle"></i>
                        <span>Un carácter especial (!@#$%^&*)</span>
                    </div>
                </div>
                
                <!-- Confirmación de Contraseña -->
                <div class="form-group has-toggle">
                    <input type="password" name="password_confirm" id="password_confirm" class="input-modern" placeholder=" " required oninput="window.validarConfirmacion(document.getElementById('password'), this)">
                    <label for="password_confirm" class="label-floating">
                        <i class="ti ti-lock-check" style="margin-right: 8px; font-size: 18px;"></i>Confirmar Contraseña
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="window.togglePass('password_confirm', this)"></i>
                    <i class="ti ti-check input-feedback icon-check"></i>
                    <i class="ti ti-x input-feedback icon-error"></i>
                    <span class="input-hint error" id="matchHint" style="display: none;">Las contraseñas no coinciden</span>
                </div>

                <!-- Botón Siguiente -->
                <button type="button" class="btn-primary" onclick="window.nextStep()" style="margin-top: 1rem;">
                    Siguiente <i class="ti ti-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </div>

            <!-- PASO 2: Preguntas de Seguridad -->
            <div id="step2" style="display: none;">
                <h3 style="font-size: 1rem; margin-bottom: 20px; color: var(--color-primary); font-weight: 600;">
                    <i class="ti ti-shield-check" style="margin-right: 8px; font-size: 18px;"></i>Preguntas de Seguridad
                </h3>
                
                
                
                <div style="background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.2); border-left: 4px solid #2563eb; border-radius: 8px; padding: 12px 16px; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 12px;">
                    <i class="ti ti-info-square-rounded" style="color: #2563eb; font-size: 1.25rem; margin-top: 2px;"></i>
                    <p style="color: #334155; font-size: 0.85rem; margin: 0; line-height: 1.5;">
                        <strong style="color: #1e3a8a; font-weight: 700; display: block; margin-bottom: 2px;">Importante para tu seguridad</strong> 
                        Selecciona 3 preguntas diferentes y proporciona tus respuestas. Estas serán la única forma de recuperar tu cuenta si olvidas la contraseña.
                    </p>
                </div>
                
                <?php 
                // DEBUG TEMPORAL: Ver qué variables están disponibles
                echo "<!-- DEBUG: ";
                echo "isset(\$questions) = " . (isset($questions) ? 'SI' : 'NO') . " | ";
                if (isset($questions)) {
                    echo "count(\$questions) = " . (is_array($questions) || is_object($questions) ? count($questions) : '0') . " | ";
                    echo "type = " . gettype($questions);
                }
                echo " -->";
                ?>
                
                <?php if (!empty($questions)): ?>
                    <!-- PREGUNTA 1 -->
                    <div class="form-group">
                        <label for="pregunta_1" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="ti ti-help-circle" style="margin-right: 6px;"></i>Pregunta de Seguridad 1 *
                        </label>
                        <select name="pregunta_id_1" id="pregunta_1" class="input-modern" required style="cursor: pointer;">
                            <option value="">Seleccione una pregunta...</option>
                            
                            <?php 
                            // LECTOR UNIVERSAL: Detecta automáticamente la variable y el tipo de dato
                            $lista = [];
                            if (isset($questions)) $lista = $questions;
                            elseif (isset($preguntas)) $lista = $preguntas;
                            elseif (isset($data['questions'])) $lista = $data['questions'];
                            elseif (isset($data['preguntas'])) $lista = $data['preguntas'];
                            
                            // Renderizar (soporta objetos y arrays)
                            if (!empty($lista)) {
                                foreach ($lista as $item) {
                                    // Normalizar ID y Texto
                                    $id = is_object($item) ? $item->id : $item['id'];
                                    $texto = is_object($item) ? $item->pregunta : $item['pregunta'];
                                    
                                    echo "<option value='{$id}'>" . htmlspecialchars($texto) . "</option>";
                                }
                            } else {
                                echo "<option disabled>⚠️ ERROR: No llegaron datos (Variable desconocida)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="respuesta_1" id="respuesta_1" class="input-modern" placeholder=" " required accept-charset="UTF-8">
                        <label for="respuesta_1" class="label-floating">
                            <i class="ti ti-message" style="margin-right: 8px; font-size: 18px;"></i>Tu Respuesta 1
                        </label>
                    </div>
                    
                    <!-- PREGUNTA 2 -->
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label for="pregunta_2" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="ti ti-help-circle" style="margin-right: 6px;"></i>Pregunta de Seguridad 2 *
                        </label>
                        <select name="pregunta_id_2" id="pregunta_2" class="input-modern" required style="cursor: pointer;">
                            <option value="">Seleccione una pregunta...</option>
                            
                            <?php 
                            // LECTOR UNIVERSAL: Detecta automáticamente la variable y el tipo de dato
                            $lista = [];
                            if (isset($questions)) $lista = $questions;
                            elseif (isset($preguntas)) $lista = $preguntas;
                            elseif (isset($data['questions'])) $lista = $data['questions'];
                            elseif (isset($data['preguntas'])) $lista = $data['preguntas'];
                            
                            // Renderizar (soporta objetos y arrays)
                            if (!empty($lista)) {
                                foreach ($lista as $item) {
                                    // Normalizar ID y Texto
                                    $id = is_object($item) ? $item->id : $item['id'];
                                    $texto = is_object($item) ? $item->pregunta : $item['pregunta'];
                                    
                                    echo "<option value='{$id}'>" . htmlspecialchars($texto) . "</option>";
                                }
                            } else {
                                echo "<option disabled>⚠️ ERROR: No llegaron datos (Variable desconocida)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="respuesta_2" id="respuesta_2" class="input-modern" placeholder=" " required accept-charset="UTF-8">
                        <label for="respuesta_2" class="label-floating">
                            <i class="ti ti-message" style="margin-right: 8px; font-size: 18px;"></i>Tu Respuesta 2
                        </label>
                    </div>
                    
                    <!-- PREGUNTA 3 -->
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label for="pregunta_3" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="ti ti-help-circle" style="margin-right: 6px;"></i>Pregunta de Seguridad 3 *
                        </label>
                        <select name="pregunta_id_3" id="pregunta_3" class="input-modern" required style="cursor: pointer;">
                            <option value="">Seleccione una pregunta...</option>
                            
                            <?php 
                            // LECTOR UNIVERSAL: Detecta automáticamente la variable y el tipo de dato
                            $lista = [];
                            if (isset($questions)) $lista = $questions;
                            elseif (isset($preguntas)) $lista = $preguntas;
                            elseif (isset($data['questions'])) $lista = $data['questions'];
                            elseif (isset($data['preguntas'])) $lista = $data['preguntas'];
                            
                            // Renderizar (soporta objetos y arrays)
                            if (!empty($lista)) {
                                foreach ($lista as $item) {
                                    // Normalizar ID y Texto
                                    $id = is_object($item) ? $item->id : $item['id'];
                                    $texto = is_object($item) ? $item->pregunta : $item['pregunta'];
                                    
                                    echo "<option value='{$id}'>" . htmlspecialchars($texto) . "</option>";
                                }
                            } else {
                                echo "<option disabled>⚠️ ERROR: No llegaron datos (Variable desconocida)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="respuesta_3" id="respuesta_3" class="input-modern" placeholder=" " required accept-charset="UTF-8">
                        <label for="respuesta_3" class="label-floating">
                            <i class="ti ti-message" style="margin-right: 8px; font-size: 18px;"></i>Tu Respuesta 3
                        </label>
                    </div>
                    
                <?php else: ?>
                    <div style="padding: 1rem; background: #fee2e2; color: #ef4444; border-radius: 12px; margin-bottom: 1.5rem;">
                        <i class="ti ti-alert-circle" style="margin-right: 8px;"></i>
                        Error: No hay preguntas de seguridad configuradas. Contacte soporte.
                    </div>
                <?php endif; ?>

                <!-- Aceptación de Términos y Privacidad -->
                <div style="margin: 1.2rem 0; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: flex-start; gap: 10px;">
                    <input type="checkbox" name="acepta_terminos" id="acepta_terminos" required style="margin-top: 3px; cursor: pointer; width: 18px; height: 18px; accent-color: #1e3a8a;">
                    <label for="acepta_terminos" style="font-size: 0.82rem; color: #475569; line-height: 1.4; cursor: pointer;">
                        He leído y acepto los <a href="<?= URLROOT ?>/legal/terminos" target="_blank" style="color: #1e3a8a; font-weight: 700; text-decoration: underline;">Términos de Servicio</a> y la <a href="<?= URLROOT ?>/legal/privacidad" target="_blank" style="color: #1e3a8a; font-weight: 700; text-decoration: underline;">Política de Privacidad</a> del ISPEB.
                    </label>
                </div>

                <!-- Botones de navegación -->
                <div class="step-buttons">
                    <button type="button" class="btn-secondary" onclick="window.prevStep()">
                        <i class="ti ti-arrow-left"></i> Atrás
                    </button>
                    <button type="button" class="btn-primary" style="flex: 1;" onclick="submitRegister(this)">
                        Registrarse <i class="ti ti-user-plus" style="margin-left: 8px;"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <div class="auth-footer">
            ¿Ya tienes cuenta? <a href="<?= URLROOT ?>/auth/login" class="auth-link">Inicia Sesión</a>
            <div style="margin-top: 12px; font-size: 0.74rem; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <a href="<?= URLROOT ?>/legal/terminos" target="_blank" style="color: #64748b; text-decoration: none; font-weight: 600;">Términos de Servicio</a>
                <span>•</span>
                <a href="<?= URLROOT ?>/legal/privacidad" target="_blank" style="color: #64748b; text-decoration: none; font-weight: 600;">Política de Privacidad</a>
            </div>
        </div>
    </div>

    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    
    <script src="<?= URLROOT ?>/js/validation.js"></script>
    <script src="<?= URLROOT ?>/assets/libs/choices/choices.min.js"></script>
    <script src="<?= URLROOT ?>/js/choices-init.js"></script>
    <script>
        console.log("🚀 Inicializando eventos del DOM...");
        
        // Mostrar errores PHP si existen
        <?php if (!empty($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error en el Registro',
                text: '<?= addslashes(htmlspecialchars($error)) ?>',
                confirmButtonColor: '#162660',
                confirmButtonText: 'Entendido',
                customClass: { popup: 'swal2-popup' }
            });
        <?php endif; ?>
        
        console.log("✅ Sistema de registro completamente inicializado");

        // Función para manejar el envío con loading state
        function submitRegister(btn) {
            if (window.validarPreguntasSeguridad()) {
                setLoading(btn, true, 'Registrando...');
                document.getElementById('registerForm').submit();
            }
        }

        // ============================================================
        // FILTRO DINÁMICO DE PREGUNTAS DE SEGURIDAD
        // Evita seleccionar la misma pregunta en dos selects distintos
        // ============================================================
        (function() {
            const selects = [
                document.getElementById('pregunta_1'),
                document.getElementById('pregunta_2'),
                document.getElementById('pregunta_3')
            ];

            function updateOptions() {
                // Obtener valores actualmente seleccionados por cada select
                const selected = selects.map(s => s ? s.value : '');

                selects.forEach(function(sel, idx) {
                    if (!sel) return;
                    const currentVal = sel.value;
                    Array.from(sel.options).forEach(function(opt) {
                        if (opt.value === '') return; // Dejar la opción vacía siempre habilitada
                        // Deshabilitar si otro select ya tiene este valor seleccionado
                        const usedByOther = selected.some(function(val, i) {
                            return i !== idx && val === opt.value;
                        });
                        opt.disabled = usedByOther;
                        // Marcar visualmente (color grisado)
                        opt.style.color = usedByOther ? '#9ca3af' : '';
                    });
                    
                    // Reconfigurar la instancia de Choices.js si está activa
                    if (window.SGPChoices) {
                        // Para no romper UX, solo reiniciamos los selects que NO dispararon el evento actual
                        // Si llamamos a todos, el que acaba de cambiar podría perder su evento o animación
                        // Pero la forma más segura de que todo haga match visual es re-inicializar el nodo
                        window.SGPChoices.reinit(sel);
                        
                        // Asegurarnos de rescatar el valor que tenía antes del reinit (a veces Choices resetea)
                        if (currentVal) sel.value = currentVal;
                    }
                });
            }

            selects.forEach(function(sel) {
                if (sel) sel.addEventListener('change', updateOptions);
            });

            // Ejecutar al cargar por si hay valores pre-seleccionados (error de vuelta)
            updateOptions();
        })();
    </script>
</body>
</html>
