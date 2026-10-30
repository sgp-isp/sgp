<?php
/**
 * Vista: Gestionar Preguntas de Seguridad
 * 
 * PROPÓSITO:
 * Permitir al usuario actualizar sus preguntas de seguridad con UX premium
 * consistente con el formulario de registro.
 */

?>

<!-- CSS Premium - Coherente con register.php -->
<style>
    /**
     * MODERN SELECT: Diseño Premium
     */
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

    .modern-select:hover {
        border-color: #D1D5DB;
        background-color: #F9FAFB;
    }

    .modern-select:focus {
        outline: none;
        border-color: var(--color-primary, #162660);
        box-shadow: 0 0 0 3px rgba(22, 38, 96, 0.1);
        background-color: white;
    }

    /**
     * INPUT WITH TOGGLE: Para respuestas con visibilidad
     */
    .input-with-toggle {
        position: relative;
    }

    .input-with-toggle input {
        padding-right: 45px !important;
    }

    .answer-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280;
        cursor: pointer;
        font-size: 20px;
        transition: all 0.3s ease;
        z-index: 10;
    }

    .answer-toggle:hover {
        color: var(--color-primary, #162660);
        transform: translateY(-50%) scale(1.1);
    }

    /**
     * SECURITY QUESTION ROW: Contenedor con animación
     */
    .security-question-row {
        margin-bottom: 24px;
        padding: 20px;
        background: linear-gradient(135deg, #F9FAFB 0%, #FFFFFF 100%);
        border-radius: 12px;
        border: 1px solid #F3F4F6;
        transition: all 0.3s ease;
        animation: slideIn 0.4s ease-out;
    }

    .security-question-row:hover {
        border-color: #E5E7EB;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        transform: translateY(-2px);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /**
     * LABELS: Estilo Moderno
     */
    .modern-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        letter-spacing: 0.3px;
    }

    .modern-label i {
        margin-right: 6px;
        color: var(--color-primary, #162660);
        font-size: 16px;
    }

    /**
     * INPUT MODERN: Inputs con animación
     */
    .input-modern {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #E5E7EB;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        transition: all 0.3s ease;
        background-color: white;
    }

    .input-modern:hover {
        border-color: #D1D5DB;
        background-color: #F9FAFB;
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--color-primary, #162660);
        box-shadow: 0 0 0 3px rgba(22, 38, 96, 0.1);
        background-color: white;
    }

    /**
     * ALERT INFO: Estilo premium
     */
    .alert-premium {
        background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        border: 1px solid #BFDBFE;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        animation: fadeIn 0.5s ease-out;
    }

    .alert-premium i {
        color: #1E40AF;
        font-size: 24px;
        margin-right: 12px;
        flex-shrink: 0;
    }

    .alert-premium-content {
        color: #1E3A8A;
        font-size: 14px;
        line-height: 1.6;
    }

    .alert-premium-content strong {
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /**
     * BUTTONS: Estilo premium
     */
    .btn-back-modern {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        background: white;
        border: 2px solid #E5E7EB;
        border-radius: 10px;
        color: #374151;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }

    .btn-back-modern:hover {
        background: #F9FAFB;
        border-color: var(--color-primary, #162660);
        color: var(--color-primary, #162660);
        transform: translateX(-4px);
        text-decoration: none;
    }

    .btn-back-modern i {
        margin-right: 8px;
        font-size: 18px;
    }

    /**
     * RESPONSIVE
     */
    @media (max-width: 767px) {
        .security-question-row {
            padding: 16px;
        }
        
        .modern-select,
        .input-modern {
            font-size: 16px; /* Evita zoom en iOS */
        }
    }
</style>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <!-- Back Button -->
            <a href="<?= URLROOT ?>/perfil/ver" class="btn-back-modern">
                <i class="ti ti-arrow-left"></i> Volver al Perfil
            </a>

            <!-- Header -->
            <div class="text-center mb-4">
                <h2 class="mb-2" style="color: var(--color-primary); font-weight: 700;">
                    <i class="ti ti-shield-lock" style="font-size: 32px; margin-right: 8px;"></i>
                    Preguntas de Seguridad
                </h2>
                <p class="text-muted" style="font-size: 15px;">
                    Actualiza tus preguntas para recuperación de cuenta
                </p>
            </div>

            <!-- Card Principal -->
            <div class="card shadow-sm" style="border-radius: 16px; border: none;">
                <div class="card-body p-4">
                    <!-- Alert Info -->
                    <div class="alert-premium">
                        <i class="ti ti-info-circle"></i>
                        <div class="alert-premium-content">
                            <strong>Importante:</strong>
                            Las preguntas de seguridad te permiten recuperar tu cuenta si olvidas tu contraseña. 
                            Asegúrate de elegir preguntas cuyas respuestas solo tú conozcas.
                        </div>
                    </div>

                    <form id="formSecurityQuestions" method="POST" action="<?= URLROOT ?>/perfil/actualizar_preguntas">
                        <?php if (empty($preguntas)): ?>
                            <!-- Mensaje cuando no hay preguntas configuradas en el sistema -->
                            <div class="alert-premium" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-color: #FCD34D;">
                                <i class="ti ti-alert-triangle" style="color: #92400E;"></i>
                                <div class="alert-premium-content" style="color: #78350F;">
                                    <strong>No hay preguntas disponibles</strong>
                                    No se encontraron preguntas de seguridad en el sistema. Por favor contacta al administrador.
                                </div>
                            </div>
                        <?php else: ?>
                        <!-- Pregunta 1 -->
                        <div class="security-question-row" style="animation-delay: 0.1s;">
                            <label class="modern-label">
                                <i class="ti ti-help"></i> Pregunta de Seguridad 1 *
                            </label>
                            <select name="pregunta_1" id="pregunta_1" class="modern-select mb-3" required>
                                <option value="">Seleccione una pregunta...</option>
                                <?php foreach ($preguntas as $pregunta): ?>
                                    <option value="<?= $pregunta->id ?>" 
                                            <?= (isset($respuestas[0]) && $respuestas[0]->pregunta_id == $pregunta->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pregunta->pregunta) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="modern-label">
                                <i class="ti ti-message-2"></i> Tu Respuesta *
                            </label>
                            <div class="input-with-toggle">
                                <input type="password" 
                                       name="respuesta_1" 
                                       id="respuesta_1" 
                                       class="input-modern" 
                                       placeholder="Escribe tu respuesta aquí"
                                       required>
                                <i class="ti ti-eye answer-toggle" onclick="toggleAnswerVisibility('respuesta_1', this)"></i>
                            </div>
                        </div>

                        <!-- Pregunta 2 -->
                        <div class="security-question-row" style="animation-delay: 0.2s;">
                            <label class="modern-label">
                                <i class="ti ti-help"></i> Pregunta de Seguridad 2 *
                            </label>
                            <select name="pregunta_2" id="pregunta_2" class="modern-select mb-3" required>
                                <option value="">Seleccione una pregunta...</option>
                                <?php foreach ($preguntas as $pregunta): ?>
                                    <option value="<?= $pregunta->id ?>"
                                            <?= (isset($respuestas[1]) && $respuestas[1]->pregunta_id == $pregunta->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pregunta->pregunta) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="modern-label">
                                <i class="ti ti-message-2"></i> Tu Respuesta *
                            </label>
                            <div class="input-with-toggle">
                                <input type="password" 
                                       name="respuesta_2" 
                                       id="respuesta_2" 
                                       class="input-modern" 
                                       placeholder="Escribe tu respuesta aquí"
                                       required>
                                <i class="ti ti-eye answer-toggle" onclick="toggleAnswerVisibility('respuesta_2', this)"></i>
                            </div>
                        </div>

                        <!-- Pregunta 3 -->
                        <div class="security-question-row" style="animation-delay: 0.3s;">
                            <label class="modern-label">
                                <i class="ti ti-help"></i> Pregunta de Seguridad 3 *
                            </label>
                            <select name="pregunta_3" id="pregunta_3" class="modern-select mb-3" required>
                                <option value="">Seleccione una pregunta...</option>
                                <?php foreach ($preguntas as $pregunta): ?>
                                    <option value="<?= $pregunta->id ?>"
                                            <?= (isset($respuestas[2]) && $respuestas[2]->pregunta_id == $pregunta->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pregunta->pregunta) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="modern-label">
                                <i class="ti ti-message-2"></i> Tu Respuesta *
                            </label>
                            <div class="input-with-toggle">
                                <input type="password" 
                                       name="respuesta_3" 
                                       id="respuesta_3" 
                                       class="input-modern" 
                                       placeholder="Escribe tu respuesta aquí"
                                       required>
                                <i class="ti ti-eye answer-toggle" onclick="toggleAnswerVisibility('respuesta_3', this)"></i>
                            </div>
                        </div>

                        <?php endif; ?>

                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary" style="padding: 12px 32px; border-radius: 10px; font-weight: 600;" <?= empty($preguntas) ? 'disabled' : '' ?>>
                                <i class="ti ti-check"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast: usando NotificationService global -->

<script>
    // Flash messages via SGP Toast
<?php if (Session::hasFlash('success')): ?>
    NotificationService.success('<?= addslashes(Session::getFlash('success')) ?>');
<?php endif; ?>

<?php if (Session::hasFlash('error')): ?>
    NotificationService.error('<?= addslashes(Session::getFlash('error')) ?>');
<?php endif; ?>

/**
 * Toggle Answer Visibility (como password toggle)
 * 
 * PROPÓSITO:
 * Mostrar/ocultar respuestas de seguridad con animación suave.
 */
function toggleAnswerVisibility(inputId, icon) {
    const input = document.getElementById(inputId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ti-eye');
        icon.classList.add('ti-eye-off');
    } else {
        input.type = 'password';
        icon.classList.remove('ti-eye-off');
        icon.classList.add('ti-eye');
    }
}

// Handle form submission
document.getElementById('formSecurityQuestions').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar que no se repitan preguntas
    const pregunta1 = document.getElementById('pregunta_1').value;
    const pregunta2 = document.getElementById('pregunta_2').value;
    const pregunta3 = document.getElementById('pregunta_3').value;
    
    if (pregunta1 === pregunta2 || pregunta1 === pregunta3 || pregunta2 === pregunta3) {
        NotificationService.error('No puedes seleccionar la misma pregunta dos veces');
        return;
    }
    
    // Validar que las respuestas no estén vacías
    const respuesta1 = document.getElementById('respuesta_1').value.trim();
    const respuesta2 = document.getElementById('respuesta_2').value.trim();
    const respuesta3 = document.getElementById('respuesta_3').value.trim();
    
    if (!respuesta1 || !respuesta2 || !respuesta3) {
        NotificationService.error('Todas las respuestas son obligatorias');
        return;
    }
    
    // Enviar formulario con animación de carga
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ti ti-loader" style="animation: spin 1s linear infinite;"></i> Guardando...';
    
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
            NotificationService.success(data.message || 'Preguntas actualizadas exitosamente');
            setTimeout(() => window.location.href = '<?= URLROOT ?>/perfil/ver', 1500);
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
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
