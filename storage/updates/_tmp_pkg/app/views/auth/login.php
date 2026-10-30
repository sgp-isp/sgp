<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <script>
        try {
            localStorage.setItem('sgp_theme', 'light');
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.classList.remove('dark');
        } catch(e) {}
    </script>
    <title>SGP - Iniciar Sesión</title>
    <!-- PWA & Mobile Web App Meta Tags -->
    <link rel="manifest" href="<?= URLROOT ?>/manifest.json?v=2">
    <meta name="theme-color" content="#162660">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SGP ISPEB">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= URLROOT ?>/img/apple-touch-icon.png?v=2">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= URLROOT ?>/img/pwa-icon-192.png?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= URLROOT ?>/img/favicon-32.png?v=2">
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon-32.png?v=2">
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css?v=<?= filemtime(APPROOT.'/../public/css/swal-bento-navy.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notifications.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/captcha.css">
    
    <!-- ============================================ -->
    <!-- DEFINIR URLROOT PARA JAVASCRIPT -->
    <!-- ============================================ -->
    <script>
        /**
         * Definir constante URLROOT para uso en JavaScript
         * Necesario para notifications.js y otros scripts del sistema
         */
        window.URLROOT = <?php echo json_encode(URLROOT, JSON_UNESCAPED_SLASHES) ?>;
        const URLROOT = window.URLROOT;
    </script>
</head>
<body class="auth-wrapper">
    <!-- ===== ACCIONES RÁPIDAS: KIOSCO & QR (solo si está activo) ===== -->
    <?php
    $kioscoHabilitado = 1;
    try {
        $dbCfg = Database::getInstance();
        $dbCfg->query("SELECT valor FROM configuracion WHERE clave = 'kiosco_activo' LIMIT 1");
        $cfgRow = $dbCfg->single();
        if ($cfgRow) $kioscoHabilitado = (int)$cfgRow->valor;
    } catch (Exception $e) { /* asumir activo si no existe tabla */ }
    $urlTokenParam = !empty($_GET['token']) ? '?token=' . urlencode(trim($_GET['token'])) : '';
    ?>
    <?php if ($kioscoHabilitado): ?>
    <div class="kiosco-floating-actions">
        <a href="<?= URLROOT ?>/kiosco<?= $urlTokenParam ?>" class="kiosco-pill" target="_self" title="Acceso al Kiosco de Asistencia">
            <i class="ti ti-clock-check"></i>
            <span class="kiosco-text">
                <span>Marcar Asistencia</span>
                <span class="kiosco-label-sub">Acceso al Kiosco Público</span>
            </span>
        </a>
        <a href="<?= URLROOT ?>/configuracion/cartelQr" target="_blank" class="btn-qr-login-pill" title="Abrir Cartel Oficial QR para escanear e imprimir">
            <i class="ti ti-qrcode"></i>
            <span>Cartel QR</span>
        </a>
    </div>
    <?php endif; ?>

    <style>
    .kiosco-floating-actions {
        position: fixed;
        top: 380px;
        right: 40px;
        z-index: 9000;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }
    .kiosco-pill {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 24px;
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        color: white !important;
        text-decoration: none;
        border-radius: 12px; /* Rectángulo con esquinas redondeadas */
        font-weight: 700;
        font-size: 1.05rem;
        box-shadow: 0 8px 20px rgba(46, 204, 113, 0.4);
        transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
        letter-spacing: 0.3px;
    }
    .kiosco-pill i { font-size: 1.4rem; }
    .kiosco-pill:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 25px rgba(46, 204, 113, 0.6);
        color: white !important;
        text-decoration: none;
    }
    .btn-qr-login-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px; /* Altura y presencia un poco mayor */
        background: rgba(255, 255, 255, 0.95);
        color: #1e293b;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px; /* Rectángulo con bordes redondeados */
        font-weight: 700;
        font-size: 0.90rem;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        backdrop-filter: blur(8px);
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .btn-qr-login-pill i { font-size: 1.25rem; color: #27ae60; }
    .btn-qr-login-pill:hover {
        background: #ffffff;
        border-color: #27ae60;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(46, 204, 113, 0.25);
        color: #0f172a;
    }
    .kiosco-label-sub {
        display: none;
        font-size: 0.75rem;
        font-weight: 500;
        opacity: 0.85;
        margin-top: 1px;
    }
    .kiosco-text { display: flex; flex-direction: column; }

    @media (max-width: 1100px) {
        .kiosco-pill .kiosco-label-sub { display: block; }
    }
    @media (max-width: 768px) {
        .kiosco-floating-actions {
            position: relative !important;
            top: auto !important;
            right: auto !important;
            z-index: 100 !important;
            margin: 10px auto 14px !important;
            width: fit-content;
            max-width: 94%;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .kiosco-pill {
            padding: 10px 18px;
            font-size: 0.92rem;
            box-shadow: 0 4px 14px rgba(46, 204, 113, 0.35);
            border-radius: 10px !important;
        }
        .kiosco-pill i { font-size: 1.25rem; }
        .btn-qr-login-pill {
            padding: 10px 16px;
            font-size: 0.88rem;
            border-radius: 10px !important;
        }
        .btn-qr-login-pill i { font-size: 1.2rem; }
    }

    .btn-biometria-mobile {
        width: 100%;
        padding: 11px 18px;
        background: #f8fafc;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        color: #1e293b;
        font-weight: 700;
        font-size: 0.90rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    .btn-biometria-mobile:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37,99,235,0.12);
    }
    .btn-biometria-mobile i {
        font-size: 1.3rem;
        color: #2563eb;
    }
    </style>
    <script>
    (function positionKioscoActions() {
        function update() {
            var actions = document.querySelector('.kiosco-floating-actions');
            if (!actions) return;
            if (window.innerWidth <= 768) {
                actions.style.top = '';
                return;
            }
            var cintillo = document.querySelector('.cintillo-container');
            if (!cintillo) return;
            actions.style.top = (cintillo.offsetHeight + 80) + 'px';
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', update);
        } else {
            update();
        }
        window.addEventListener('resize', update);
        document.querySelectorAll('.cintillo-container img').forEach(function(img) {
            if (!img.complete) img.addEventListener('load', update);
        });
    })();
    </script>

    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>
    
    <!-- Tarjeta Premium Flotante -->
    <div class="auth-card">
        <div class="auth-header">
            <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" class="auth-logo">
            <h1 class="auth-title">Iniciar Sesión</h1>
            <p class="auth-subtitle">Bienvenido al Sistema de Gestión de Pasantes</p>
        </div>

        <!-- [FIX-UX] Banner de error persistente (credenciales, cuenta bloqueada/
             inactiva, rate limit) — reemplaza el toast para errores bloqueantes
             que requieren que el usuario los lea y corrija algo en el formulario. -->
        <div class="auth-error-banner" id="authErrorBanner" role="alert">
            <i class="ti ti-alert-circle"></i>
            <span id="authErrorBannerText"></span>
        </div>

        <form action="<?= URLROOT ?>/auth/login" method="POST">
            <!-- Email con Floating Label e Icono -->
            <div class="form-group">
                <input type="email" name="email" id="email" class="input-modern" placeholder=" " required>
                <label for="email" class="label-floating">
                    <i class="ti ti-mail" style="margin-right: 8px; font-size: 18px;"></i>Correo Electrónico
                </label>
                <div class="email-feedback"></div>
            </div>

            <!-- Password con Floating Label, Toggle e Icono -->
            <div class="form-group has-toggle">
                <input type="password" name="password" id="password" class="input-modern" placeholder=" " required style="padding-right: 48px;">
                <label for="password" class="label-floating">
                    <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Contraseña
                </label>
                <i class="ti ti-eye password-toggle" onclick="togglePass('password', this)"></i>
                <i class="ti ti-check input-feedback icon-check"></i>
                <i class="ti ti-x input-feedback icon-error"></i>
            </div>


            <!-- CAPTCHA Component -->
            <div class="captcha-wrapper">
                <label class="captcha-label">Código de Verificación</label>
                <div class="captcha-container">
                    <div class="captcha-display">
                        <img src="<?= URLROOT ?>/captcha/generate?v=<?= time() ?>"
                             alt="CAPTCHA"
                             class="captcha-image"
                             id="captchaImage">
                    </div>
                    <button type="button" 
                            class="captcha-refresh-btn" 
                            onclick="refreshCaptcha()"
                            title="Generar nuevo código">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
                <div class="form-group" style="margin-top: 0; margin-bottom: 0;">
                    <input type="text" 
                           name="captcha" 
                           id="captcha" 
                           class="input-modern" 
                           placeholder=" " 
                           required 
                           maxlength="5"
                           autocomplete="off"
                           style="text-transform: uppercase;"
                           oninput="this.value = this.value.toUpperCase()">
                    <label for="captcha" class="label-floating">
                        <i class="ti ti-shield-check" style="margin-right: 8px; font-size: 18px;"></i>Ingrese el código
                    </label>
                    <i class="ti ti-check input-feedback icon-check"></i>
                    <i class="ti ti-x input-feedback icon-error"></i>
                </div>
                <!-- [FIX-UX] Error de captcha inline, en vez de toast -->
                <div class="captcha-feedback" id="captchaFeedback"></div>
            </div>


            <!-- Botón Premium -->
            <button type="submit" class="btn-primary" style="margin-bottom: 10px;">
                Ingresar
                <i class="ti ti-arrow-right" style="font-size: 20px;"></i>
            </button>

            <!-- Acceso Biométrico Móvil (Oculto por defecto en desktop, visible solo si hay hardware en móvil) -->
            <div id="biometria-login-container" style="display: none; margin-bottom: 12px; text-align: center;">
                <div style="display: flex; align-items: center; margin: 8px 0 10px 0;">
                    <div style="flex: 1; height: 1px; background: rgba(0,0,0,0.08);"></div>
                    <span style="padding: 0 10px; font-size: 0.72rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">O ingresa con</span>
                    <div style="flex: 1; height: 1px; background: rgba(0,0,0,0.08);"></div>
                </div>
                <button type="button" id="btnBiometriaLogin" class="btn-biometria-mobile" style="display: none;">
                    <i class="ti ti-fingerprint"></i>
                    <span>Ingresar con Huella Dactilar</span>
                </button>
            </div>

            <!-- Enlaces: ¿Olvidaste tu contraseña? a la izquierda y Regístrate aquí a la derecha -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; font-size: 0.84rem;">
                <a href="<?= URLROOT ?>/auth/recovery" class="auth-link" style="font-size: 0.84rem;">
                    ¿Olvidaste tu contraseña?
                </a>
                <a href="<?= URLROOT ?>/auth/register" class="auth-link" style="font-size: 0.84rem; font-weight: 700;">
                    Regístrate aquí
                </a>
            </div>
        </form>
    </div>

    <!-- Loading Spinner: Usamos loading en el botón (setLoading) -->

    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    <script src="<?= URLROOT ?>/js/validation.js"></script>
    <script src="<?= URLROOT ?>/js/sgp-biometria.js?v=<?= @filemtime(APPROOT . '/../public/js/sgp-biometria.js') ?: time() ?>"></script>
    
    <script>
        // ============================================
        // FUNCIÓN: REFRESCAR CAPTCHA
        // ============================================
        /**
         * Refresca la imagen del CAPTCHA
         * @param {boolean} isError - true si es por error de validación, false si es refresco manual
         */
        function refreshCaptcha(isError = false) {
            const captchaImage = document.getElementById('captchaImage');
            const captchaInput = document.getElementById('captcha');
            
            // Agregar timestamp para evitar cache
            captchaImage.src = '<?= URLROOT ?>/captcha/generate?' + Date.now();
            
            // Limpiar input
            captchaInput.value = '';
            captchaInput.classList.remove('valid', 'invalid');
            
            // ✨ SOLO hacer shake si es un error de validación
            if (isError) {
                captchaInput.classList.add('shake-error');
                setTimeout(() => {
                    captchaInput.classList.remove('shake-error');
                    captchaInput.focus(); // Focus para mejor UX
                }, 500);
            } else {
                // Si es refresco manual, solo dar focus sin "regañar"
                captchaInput.focus();
            }
            
            // Animación de rotación del botón
            const refreshBtn = document.querySelector('.captcha-refresh-btn i');
            if (refreshBtn) {
                refreshBtn.style.transform = 'rotate(360deg)';
                setTimeout(() => {
                    refreshBtn.style.transform = 'rotate(0deg)';
                }, 500);
            }
        }
        
        // Auto-renovación del CAPTCHA cada 4 min (CAPTCHA expira en 5 min en sesión)
        setInterval(function() { refreshCaptcha(false); }, 240000);

        // [FIX-UX] Oculta el banner de credenciales y el error inline del captcha.
        // Se llama antes de cada intento nuevo y cuando el usuario vuelve a escribir,
        // para no dejar un mensaje de un intento anterior ya corregido.
        function hideAuthErrors() {
            document.getElementById('authErrorBanner').classList.remove('visible');
            const captchaFeedback = document.getElementById('captchaFeedback');
            captchaFeedback.classList.remove('visible');
            captchaFeedback.textContent = '';
            document.getElementById('captcha').classList.remove('invalid');
        }
        ['email', 'password'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', function () {
                document.getElementById('authErrorBanner').classList.remove('visible');
            });
        });
        document.getElementById('captcha').addEventListener('input', function () {
            const captchaFeedback = document.getElementById('captchaFeedback');
            captchaFeedback.classList.remove('visible');
            this.classList.remove('invalid');
        });

        // Validación en tiempo real de email
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('blur', function() {
            validateEmailWithFeedback(this);
        });
        
        // Validación de contraseña (solo visual)
        const passwordInput = document.getElementById('password');
        passwordInput.addEventListener('input', function() {
            // No mostramos check verde en login para no confundir con validación de requisitos
            if(this.value.length > 0) {
                this.classList.remove('invalid');
            }
        });
        
        // Validación de CAPTCHA (solo longitud)
        // Validación de CAPTCHA (solo visual)
        const captchaInput = document.getElementById('captcha');
        captchaInput.addEventListener('input', function() {
             if(this.value.length === 5) {
                this.classList.add('valid');
                this.classList.remove('invalid');
            } else {
                this.classList.remove('valid');
            }
        });
        
        // Submit del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const loader = document.getElementById('loginLoader');
            
            // Show loading overlay
            // loader.style.display = 'flex'; // Usamos spinner en botón mejor
            setLoading(btn, true, 'Iniciando...');

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                var contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }
                // El servidor devolvió HTML — probablemente un redirect exitoso
                // Verificar si fue un redirect a dashboard
                if (response.redirected || response.url.includes('dashboard')) {
                    window.location.href = response.url;
                    return null;
                }
                // Respuesta inesperada — leer como texto para debuggear
                return response.text().then(function(text) {
                    console.error('[SGP-LOGIN] Respuesta no-JSON del servidor:', text.substring(0, 200));
                    throw new Error('Respuesta inesperada del servidor');
                });
            })
            .then(data => {
                if (!data) return; // fue un redirect manejado arriba
                // loader.style.display = 'none';
                
                if (data.success) {
                    // ✨ Alerta centrada Premium de bienvenida única con tiempo adecuado (2s)
                    const userName = data.user_name || 'Usuario';
                    const firstName = userName.split(' ')[0];
                    
                    Swal.fire({
                        icon: 'success',
                        title: `¡Bienvenido, ${firstName}!`,
                        text: 'Iniciando sesión...',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            const popup = Swal.getPopup();
                            if (popup) {
                                popup.style.borderRadius = '20px';
                                popup.style.padding = '28px 24px';
                                popup.style.boxShadow = '0 25px 60px rgba(0,0,0,0.2)';
                            }
                        }
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    // [FIX-UX] Error de captcha -> inline junto al campo. Cualquier
                    // otro error bloqueante (credenciales, cuenta bloqueada/inactiva,
                    // rate limit) -> banner persistente arriba del formulario, en vez
                    // de un toast que se autodestruye antes de que el usuario alcance
                    // a leerlo. El banner no se ata a un campo específico a propósito
                    // (ver AuthController::login() — evita revelar si el correo existe).
                    hideAuthErrors();
                    if (data.field === 'captcha') {
                        const captchaFeedback = document.getElementById('captchaFeedback');
                        if (captchaFeedback) {
                            captchaFeedback.textContent = data.message;
                            captchaFeedback.classList.add('visible');
                        }
                        const captchaInput = document.getElementById('captcha');
                        if (captchaInput) {
                            captchaInput.classList.add('invalid');
                            captchaInput.focus();
                            captchaInput.select();
                        }
                        NotificationService.warning(data.message || 'Código de seguridad (CAPTCHA) incorrecto.');
                    } else {
                        const banner = document.getElementById('authErrorBanner');
                        if (banner) {
                            document.getElementById('authErrorBannerText').innerHTML = data.message;
                            banner.classList.add('visible');
                            banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                        NotificationService.error(data.message || 'Credenciales inválidas. Por favor verifica tus datos.');
                    }

                    // AUTO-REFRESH CAPTCHA on error (con shake)
                    refreshCaptcha(true);

                    setLoading(btn, false);
                }
            })
            .catch(error => {
                if (loader) loader.style.display = 'none';
                console.error('[SGP-LOGIN] Error:', error);
                
                const banner = document.getElementById('authErrorBanner');
                const netMsg = 'No se pudo establecer conexión con el servidor. Por favor verifica tu red e inténtalo de nuevo.';
                if (banner) {
                    document.getElementById('authErrorBannerText').innerHTML = '<strong>Error de comunicación:</strong> ' + netMsg;
                    banner.classList.add('visible');
                    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                NotificationService.error(netMsg);
                
                // AUTO-REFRESH CAPTCHA on error (con shake)
                refreshCaptcha(true);
                
                setLoading(btn, false);
            });
        });

        // ============================================
        // TOAST: REGISTRO EXITOSO
        // ============================================
        // Cuando el usuario se registra y es redirigido al login,
        // mostramos un toast de éxito
        <?php if (!empty($success)): ?>
            NotificationService.success('<?= htmlspecialchars($success) ?>');
        <?php endif; ?>
        
        // ============================================
        // TOAST: SOLICITUD DE AYUDA ENVIADA
        // ============================================
        // Cuando el usuario solicita ayuda y es redirigido al login
        <?php if (Session::hasFlash('success')): ?>
            NotificationService.success('<?= addslashes(Session::getFlash('success')) ?>');
        <?php endif; ?>
        
        // ============================================
        // TOAST: ERRORES
        // ============================================
        <?php if (Session::hasFlash('error')): ?>
            NotificationService.error('<?= addslashes(Session::getFlash('error')) ?>');
        <?php endif; ?>
        
        // ============================================
        // TOAST: ERRORES ESPECÍFICOS DE LOGIN
        // ============================================
        // Mostrar errores específicos (correo no existe, contraseña incorrecta)
        <?php if (Session::hasFlash('login_error')): ?>
            NotificationService.error('<?= addslashes(Session::getFlash('login_error')) ?>');
        <?php endif; ?>

        // ============================================
        // ALERTA: INACTIVIDAD (Con recarga para Captcha)
        // ============================================
        <?php
        // Detectar inactividad por flash consumiéndolo (evita bucles en recarga) O por URL param ?razon=inactividad
        $flashInactividad   = Session::getFlash('inactividad');
        $mostrarInactividad = ($flashInactividad !== null) || (($_GET['razon'] ?? '') === 'inactividad');
        ?>
        <?php if ($mostrarInactividad): ?>
            Swal.fire({
                icon: 'info',
                title: 'Sesión Finalizada',
                html: [
                    '<div style="text-align:center;padding:4px 0 8px;">',
                    '<div style="display:inline-flex;align-items:center;justify-content:center;',
                    'width:56px;height:56px;border-radius:50%;',
                    'background:linear-gradient(135deg,#dbeafe,#bfdbfe);margin-bottom:14px;">',
                    '<i class="ti ti-clock-off" style="font-size:1.7rem;color:#2563eb;"></i>',
                    '</div>',
                    '<p style="color:#64748b;font-size:0.9rem;line-height:1.6;margin:0;">',
                    'Tu sesión fue cerrada automáticamente<br>',
                    '<strong style="color:#1e293b;">por inactividad prolongada</strong><br>',
                    '<span style="font-size:0.8rem;color:#94a3b8;margin-top:6px;display:block;">',
                    'Tus datos están protegidos. Puedes volver a iniciar sesión.',
                    '</span>',
                    '</p>',
                    '</div>'
                ].join(''),
                confirmButtonColor: '#1d4ed8',
                confirmButtonText: '<i class="ti ti-login" style="margin-right:5px;"></i> Okay, entendido',
                customClass: {
                    popup: 'sgp-swal-inactividad',
                    confirmButton: 'sgp-swal-btn-confirm'
                },
                didOpen: () => {
                    const popup = Swal.getPopup();
                    if (popup) {
                        popup.style.borderRadius = '20px';
                        popup.style.padding = '32px 28px 28px';
                        popup.style.boxShadow = '0 25px 60px rgba(0,0,0,0.18)';
                    }
                }
            }).then(() => {
                // Limpiar el parámetro ?razon=inactividad de la URL sin recargar la página
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('razon');
                    const cleanPath = url.pathname + (url.search ? url.search : '');
                    window.history.replaceState({}, document.title, cleanPath);
                }
                // Enfocar el campo de correo directamente para iniciar sesión
                const emailInput = document.getElementById('email') || document.querySelector('input[type="email"]');
                if (emailInput) {
                    emailInput.focus();
                }
            });
        <?php endif; ?>

        // ============================================
        // TOAST: REGISTRO EXITOSO (PATRÓN PRG)
        // ============================================
        // Detectar si venimos de un registro exitoso (parámetro ?status=success)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Registro Exitoso! 🎉',
                html: 'Tu cuenta ha sido creada correctamente.<br><strong>Ya puedes iniciar sesión.</strong>',
                confirmButtonColor: '#162660',
                confirmButtonText: '<i class="ti ti-login"></i> Iniciar Sesión',
                timer: 10000,
                timerProgressBar: true,
                didOpen: () => {
                    // Permitir que el timer detenga al hacer clic en el modal
                    Swal.getPopup().addEventListener('mouseenter', () => Swal.stopTimer());
                    Swal.getPopup().addEventListener('mouseleave', () => Swal.resumeTimer());
                }
            }).then(() => {
                document.getElementById('email').focus();
            });
            // Limpiar la URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // ============================================
        // PWA: REGISTRO DE SERVICE WORKER
        // ============================================
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?= URLROOT ?>/sw.js')
                    .then(function(reg) {
                        console.log('[SGP-PWA] Service Worker registrado:', reg.scope);
                    })
                    .catch(function(err) {
                        console.warn('[SGP-PWA] Error al registrar Service Worker:', err);
                    });
            });
        }
    </script>
    
    <?php 
    // ✨ CRÍTICO: Incluir footer para cargar notification-service.js
    include_once APPROOT . '/views/layouts/footer.php'; 
    ?>
</body>
</html>
