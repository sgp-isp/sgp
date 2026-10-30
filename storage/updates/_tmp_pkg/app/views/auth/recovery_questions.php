<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Preguntas de Seguridad</title>
    <!-- 🛡️ No-Caché: Impide restaurar esta página con el botón Atrás -->
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notifications.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">
    <script>const URLROOT = <?php echo json_encode(URLROOT, JSON_UNESCAPED_SLASHES) ?>;</script> <!-- [FIX-C3] -->
</head>

<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>
    <div class="auth-card" style="position: relative;">
        <!-- Back Button -->
        <a href="<?= URLROOT ?>/auth/login" class="btn-back" title="Cancelar y volver">
            <i class="ti ti-arrow-left"></i>
        </a>

        <div class="auth-header">
            <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" class="auth-logo">
            <h1 class="auth-title">Verificación de Seguridad</h1>
            <p class="auth-subtitle">Paso 2: Responde tus preguntas de seguridad</p>
        </div>
        
        <form action="<?= URLROOT ?>/auth/recovery" method="POST">
            <input type="hidden" name="step" value="2">
            <?php foreach ($questions as $q): ?>
                <div class="form-group">
                    <input type="text" name="answers[<?= $q['question_id'] ?>]" id="answer_<?= $q['question_id'] ?>" class="input-modern" placeholder=" " required>
                    <label for="answer_<?= $q['question_id'] ?>" class="label-floating">
                        <?= htmlspecialchars($q['question']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn-primary" style="margin-top: 1rem;">
                Verificar <i class="ti ti-check" style="margin-left: 8px;"></i>
            </button>
        </form>

        <script>
            document.querySelector('form').addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (typeof setLoading === 'function') {
                    setLoading(btn, true, 'Verificando...');
                } else {
                    // Fallback si validation.js no está cargado (aunque debería por footer)
                    btn.innerHTML = '<i class="ti ti-loader animate-spin"></i> Verificando...';
                    btn.disabled = true;
                }
            });
        </script>
        
        <!-- SECCIÓN: SOLICITUD DE AYUDA -->
        <div class="text-center" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
            <p style="font-size: 13px; color: #6B7280; margin-bottom: 0.75rem;">
                ¿No recuerdas tus respuestas?
            </p>
            <a href="#" 
               id="btnSolicitarAyuda"
               class="btn-link" 
               style="color: #EF4444; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; transition: all 0.3s ease;">
                <i class="ti ti-help-circle" style="margin-right: 6px; font-size: 18px;"></i>
                Solicitar ayuda al administrador
            </a>
            <p style="font-size: 12px; color: #9CA3AF; margin-top: 0.5rem;">
                El administrador revisará tu solicitud y te contactará pronto.
            </p>
        </div>
        
        <div class="auth-footer">
             <a href="<?= URLROOT ?>/auth/login" class="auth-link">Cancelar</a>
        </div>
    </div>
    
    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    <script>
        // ============================================
        // SWEETALERT: CONFIRMACIÓN DE SOLICITUD DE AYUDA
        // ============================================
        document.getElementById('btnSolicitarAyuda')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                icon: 'question',
                title: '¿Solicitar ayuda?',
                text: 'Se notificará al administrador para resetear tu cuenta. ¿Deseas continuar?',
                showCancelButton: true,
                confirmButtonText: 'Sí, solicitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#162660',
                cancelButtonColor: '#6B7280',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // ✨ MEJORADO: Usar AJAX en lugar de redirect
                    sendHelpRequest();
                }
            });
        });
        
        /**
         * Enviar solicitud de ayuda al administrador vía AJAX
         */
        function sendHelpRequest() {
            // Obtener email del usuario (de sesión PHP)
            const email = '<?= htmlspecialchars($_SESSION['rec_email'] ?? '', ENT_QUOTES) ?>';
            
            if (!email) {
                NotificationService.error('No se pudo identificar tu correo. Inicia el proceso de recuperación de nuevo.');
                return;
            }

            // Leer CSRF token del meta tag inyectado globalmente
            const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            
            // ── Modal de carga ────────────────────────────────────────────────
            Swal.fire({
                title: 'Enviando solicitud...',
                html:  '<div style="color:#475569; font-size:0.95rem; margin-top:6px;">Notificando al administrador</div>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            // ── Fetch con CSRF token ──────────────────────────────────────────
            fetch('<?= URLROOT ?>/auth/requestHelp', {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // ── Modal de éxito — Bento UI Premium ────────────────────
                    Swal.fire({
                        icon:  'success',
                        title: 'Solicitud Enviada',
                        html: [
                            '<div style="',
                                'font-size: 0.97rem;',
                                'color: #475569;',
                                'line-height: 1.65;',
                                'margin-top: 10px;',
                                'padding: 0 4px;',
                            '">',
                                '<span style="',
                                    'display: inline-flex;',
                                    'align-items: center;',
                                    'gap: 6px;',
                                    'background: rgba(16, 185, 129, 0.08);',
                                    'border: 1px solid rgba(16, 185, 129, 0.28);',
                                    'border-radius: 10px;',
                                    'padding: 7px 14px;',
                                    'margin-bottom: 12px;',
                                    'font-size: 0.85rem;',
                                    'color: #059669;',
                                    'font-weight: 600;',
                                '">',
                                    '✓ Administrador notificado',
                                '</span>',
                                '<br>',
                                'Tu solicitud ha sido registrada exitosamente.',
                                '<br>',
                                '<span style="font-size:0.85rem; color:#94a3b8; margin-top:6px; display:block;">',
                                    'Un administrador evaluará tu caso y restablecerá tu acceso a la brevedad.',
                                '</span>',
                            '</div>'
                        ].join(''),

                        confirmButtonText:  'Entendido',
                        confirmButtonColor: '#1d4ed8',
                        showCancelButton:   false,
                        allowOutsideClick:  false,

                        didOpen: function () {
                            var popup = Swal.getPopup();
                            if (!popup) return;
                            popup.style.borderRadius   = '24px';
                            popup.style.padding        = '34px 28px 28px';
                            popup.style.boxShadow      = '0 25px 60px rgba(0,0,0,0.16), 0 8px 24px rgba(0,0,0,0.10)';
                            popup.style.border         = '1px solid rgba(255,255,255,0.14)';
                        }

                    }).then(() => {
                        // Redirigir al login al confirmar o cerrar
                        window.location.href = '<?= URLROOT ?>/auth/login';
                    });

                } else {
                    // ── Error del servidor (rate-limit, usuario no encontrado, etc.) ──
                    Swal.fire({
                        icon:               'warning',
                        title:              'No se pudo enviar',
                        text:               data.message || 'Error al procesar la solicitud.',
                        confirmButtonText:  'Entendido',
                        confirmButtonColor: '#1d4ed8',
                        didOpen: function () {
                            var popup = Swal.getPopup();
                            if (popup) {
                                popup.style.borderRadius = '20px';
                                popup.style.padding      = '32px 28px 28px';
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('[SGP] Error en sendHelpRequest:', error);
                Swal.fire({
                    icon:               'error',
                    title:              'Error de conexión',
                    text:               'No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.',
                    confirmButtonText:  'Cerrar',
                    confirmButtonColor: '#1d4ed8',
                    didOpen: function () {
                        var popup = Swal.getPopup();
                        if (popup) { popup.style.borderRadius = '20px'; }
                    }
                });
            });
        }
    </script>

    <?php if (!empty($error)): ?>
    <script>
    // [SGP-FIX] Mismo mecanismo de alerta que el login (NotificationService.error)
    // para coherencia visual en todo el flujo de autenticación.
    document.addEventListener('DOMContentLoaded', function () {
        // Restaurar botón de envío (no queda bloqueado tras el error del servidor)
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled  = false;
            submitBtn.innerHTML = 'Verificar <i class="ti ti-check" style="margin-left:8px;"></i>';
        }
        // Idéntico al error de credenciales del login
        NotificationService.error('<?= htmlspecialchars($error, ENT_QUOTES) ?>');
    });
    </script>
    <?php endif; ?>
</body>
</html>
