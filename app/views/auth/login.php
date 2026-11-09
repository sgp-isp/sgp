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
    <meta name="description" content="Sistema de Gestión de Pasantías (SGP) - Instituto de Salud Pública del Estado Bolívar (ISPEB). Control de asistencia, evaluaciones y gestión académica institucional.">
    <meta property="og:title" content="SGP — Sistema de Gestión de Pasantías ISPEB">
    <meta property="og:description" content="Portal oficial de control de asistencia, pasantías y evaluaciones del Instituto de Salud Pública del Estado Bolívar.">
    <meta property="og:type" content="website">
    
    <!-- Schema.org JSON-LD Datos Estructurados -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "GovernmentOrganization",
      "name": "Instituto de Salud Pública del Estado Bolívar",
      "alternateName": "ISPEB",
      "url": "<?= URLROOT ?>",
      "logo": "<?= URLROOT ?>/img/logo.png",
      "description": "Sistema de Gestión de Pasantías y Asistencias Institucionales"
    }
    </script>

    <!-- PWA & Mobile Web App Meta Tags -->
    <link rel="manifest" href="<?= URLROOT ?>/manifest.json?v=2">
    <meta name="theme-color" content="#162660">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SGP ISPEB">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= URLROOT ?>/img/apple-touch-icon.png?v=2">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= URLROOT ?>/img/pwa-icon-192.png?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= URLROOT ?>/img/favicon-32.png?v=<?= @filemtime(APPROOT . '/../public/img/favicon-32.png') ?: 3 ?>">
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png?v=<?= @filemtime(APPROOT . '/../public/img/favicon.png') ?: 3 ?>">
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css?v=<?= filemtime(APPROOT.'/../public/css/swal-bento-navy.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notifications.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/captcha.css?v=<?= filemtime(APPROOT.'/../public/css/captcha.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/bento-otp.css?v=<?= @filemtime(APPROOT.'/../public/css/bento-otp.css') ?: time() ?>">
    
    <!-- ============================================ -->
    <!-- FORZAR ESCALA 100% NATIVA EN LOGIN -->
    <!-- ============================================ -->
    <style>
        html, body.auth-wrapper {
            zoom: 1 !important;
            -webkit-text-size-adjust: 100%;
        }
    </style>

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

    <style>
    /* ── Botón Flotante Circular de Soporte Técnico (Esquina Inferior Izquierda) ── */
    .sgp-soporte-fab-dock {
        position: fixed;
        bottom: 24px;
        left: 24px;
        z-index: 9100;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sgp-soporte-fab-btn {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: #ffffff !important;
        border: 1.5px solid rgba(255, 255, 255, 0.35);
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        backdrop-filter: blur(8px);
        transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
        outline: none;
    }
    .sgp-soporte-fab-btn i {
        font-size: 1.6rem;
        color: #ffffff;
        transition: transform 0.28s ease;
    }
    .sgp-soporte-fab-btn:hover {
        background: linear-gradient(135deg, #172554 0%, #1d4ed8 100%);
        transform: translateY(-3px) scale(1.08);
        box-shadow: 0 12px 28px rgba(37, 99, 235, 0.6);
        border-color: rgba(255, 255, 255, 0.6);
    }
    .sgp-soporte-fab-btn:hover i {
        transform: rotate(-12deg) scale(1.1);
    }
    .sgp-soporte-fab-btn:active {
        transform: translateY(0) scale(0.96);
    }

    .kiosco-floating-actions {
        position: fixed;
        top: 36px;
        right: 36px;
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
        .sgp-soporte-fab-dock {
            bottom: 16px;
            left: 16px;
        }
        .sgp-soporte-fab-btn {
            width: 46px;
            height: 46px;
        }
        .sgp-soporte-fab-btn i {
            font-size: 1.4rem;
        }
        .kiosco-floating-actions {
            position: relative !important;
            top: auto !important;
            right: auto !important;
            z-index: 100 !important;
            margin: 10px auto 14px !important;
            width: fit-content;
            max-width: 95%;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
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
        padding: 12px 18px;
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        color: #0f172a;
        font-weight: 700;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    .btn-biometria-mobile:hover {
        background: #f0fdf4;
        border-color: #16a34a;
        color: #15803d;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(22, 163, 74, 0.16);
    }
    .btn-biometria-mobile i {
        font-size: 1.4rem;
        color: #16a34a;
        transition: transform 0.2s ease;
    }
    .btn-biometria-mobile:hover i {
        transform: scale(1.1);
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
    
    <?php if ($kioscoHabilitado): ?>
    <!-- Botón Principal de Marcar Asistencia (Mismas dimensiones y ancho que el cuadro de login) -->
    <div class="login-kiosco-banner-wrap">
        <a href="<?= URLROOT ?>/kiosco<?= $urlTokenParam ?>" class="login-kiosco-banner-btn" target="_self" title="Acceso al Kiosco de Asistencia">
            <div class="lkb-icon">
                <i class="ti ti-clock-check"></i>
            </div>
            <div class="lkb-text">
                <span class="lkb-title">Marcar Asistencia</span>
                <span class="lkb-sub">Acceso directo al Kiosco Público Oficial</span>
            </div>
            <div class="lkb-arrow">
                <i class="ti ti-chevron-right"></i>
            </div>
        </a>
    </div>
    <?php endif; ?>

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

            <!-- Acceso Rápido Seguro (Huella Dactilar / Patrón) — Activo solo en Dispositivos Vinculados -->
            <div id="biometria-login-container" style="display: none; margin-bottom: 12px; text-align: center;">
                <div style="display: flex; align-items: center; margin: 10px 0 12px 0;">
                    <div style="flex: 1; height: 1px; background: rgba(0,0,0,0.08);"></div>
                    <span style="padding: 0 10px; font-size: 0.72rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">O ingresa con tu dispositivo</span>
                    <div style="flex: 1; height: 1px; background: rgba(0,0,0,0.08);"></div>
                </div>
                <button type="button" id="btnBiometriaLogin" class="btn-biometria-mobile" style="display: none;">
                    <i class="ti ti-fingerprint"></i>
                    <span>Ingresar con Huella Dactilar</span>
                </button>
            </div>

            <!-- Enlaces de Acción Rápida: ¿Olvidaste tu contraseña? y Regístrate aquí (Chips interactivos con micro-animaciones) -->
            <div class="auth-actions-group">
                <a href="<?= URLROOT ?>/auth/recovery" class="auth-action-chip chip-recovery" title="Recuperar acceso si olvidaste tu clave">
                    <i class="ti ti-key"></i>
                    <span>¿Olvidaste tu contraseña?</span>
                </a>
                <a href="<?= URLROOT ?>/auth/register" class="auth-action-chip chip-register" title="Crear una cuenta nueva">
                    <i class="ti ti-user-plus"></i>
                    <span>Regístrate aquí</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Loading Spinner: Usamos loading en el botón (setLoading) -->

    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    <script src="<?= URLROOT ?>/js/validation.js"></script>
    <script src="<?= URLROOT ?>/js/sgp-biometria.js?v=<?= @filemtime(APPROOT . '/../public/js/sgp-biometria.js') ?: time() ?>"></script>
    <script src="<?= URLROOT ?>/js/bento-otp.js?v=<?= @filemtime(APPROOT . '/../public/js/bento-otp.js') ?: time() ?>"></script>
    <script src="<?= URLROOT ?>/js/sgp-dispositivos.js?v=<?= @filemtime(APPROOT . '/../public/js/sgp-dispositivos.js') ?: time() ?>"></script>
    
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

        // Validación en login de email (limpia, sin texto verde que choque con la contraseña)
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('blur', function() {
            const val = this.value.trim();
            const feedbackEl = this.parentElement.querySelector('.email-feedback');
            if (!val) {
                this.classList.remove('valid', 'invalid');
                if (feedbackEl) feedbackEl.innerHTML = '';
                return;
            }
            if (typeof isValidEmail === 'function' && isValidEmail(val)) {
                this.classList.remove('invalid');
                this.classList.add('valid');
                if (feedbackEl) feedbackEl.innerHTML = '';
            } else {
                this.classList.remove('valid');
                this.classList.add('invalid');
                if (feedbackEl) {
                    feedbackEl.innerHTML = '<i class="ti ti-alert-circle"></i> Formato de correo inválido';
                    feedbackEl.style.color = '#ef4444';
                }
            }
        });
        emailInput.addEventListener('input', function() {
            const feedbackEl = this.parentElement.querySelector('.email-feedback');
            if (feedbackEl) feedbackEl.innerHTML = '';
            this.classList.remove('invalid');
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
        // ALERTA: SESIÓN REVOCADA O INACTIVIDAD
        // ============================================
        <?php
        // Detectar inactividad o revocación forzada por flash o URL
        $flashInactividad   = Session::getFlash('inactividad');
        $flashError         = Session::getFlash('login_error');
        $mostrarInactividad = ($flashInactividad !== null) || (($_GET['razon'] ?? '') === 'inactividad');
        $mostrarRevocada    = (($_GET['razon'] ?? '') === 'revocada') || ($flashError !== null && stripos($flashError, 'revocada') !== false);
        ?>
        <?php if ($mostrarRevocada): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Acceso Revocado',
                html: [
                    '<div style="text-align:center;padding:4px 0 8px;">',
                    '<div style="display:inline-flex;align-items:center;justify-content:center;',
                    'width:56px;height:56px;border-radius:50%;',
                    'background:linear-gradient(135deg,#fee2e2,#fecaca);margin-bottom:14px;">',
                    '<i class="ti ti-shield-lock" style="font-size:1.7rem;color:#dc2626;"></i>',
                    '</div>',
                    '<p style="color:#64748b;font-size:0.9rem;line-height:1.6;margin:0;">',
                    'Tu sesión activa ha sido cerrada remotamente por el<br>',
                    '<strong style="color:#1e293b;">SuperAdministrador del sistema</strong>.<br>',
                    '<span style="font-size:0.8rem;color:#94a3b8;margin-top:6px;display:block;">',
                    'Si consideras que se trata de un error, contacta a la Dirección de Telemática.',
                    '</span>',
                    '</p>',
                    '</div>'
                ].join(''),
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#dc2626',
                customClass: {
                    popup: 'sgp-swal-revocada',
                    confirmButton: 'sgp-swal-btn-confirm'
                }
            }).then(() => {
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('razon');
                    const cleanPath = url.pathname + (url.search ? url.search : '');
                    window.history.replaceState({}, document.title, cleanPath);
                }
            });
        <?php elseif ($mostrarInactividad): ?>
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
            var isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            var isSelfSignedLocalHttps = location.protocol === 'https:' && isLocalhost;
            if (isSelfSignedLocalHttps) {
                navigator.serviceWorker.getRegistrations().then(function(regs) {
                    regs.forEach(function(r) { r.unregister(); });
                }).catch(function(){});
            } else {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('<?= URLROOT ?>/sw.js?v=<?= @filemtime(APPROOT . '/../public/sw.js') ?: time() ?>')
                        .then(function(reg) {
                            if (reg) reg.update();
                        })
                        .catch(function(err) {
                            console.warn('[SGP-PWA] Error al registrar Service Worker:', err);
                        });
                });
            }
        }
    </script>

    <!-- ========================================================
         MODAL BENTO: MESA DE AYUDA Y CHAT DE SOPORTE DESDE LOGIN
         ======================================================== -->
    <div id="modalSoporteGuest" class="sgp-guest-modal-backdrop" style="display: none;">
        <div class="sgp-guest-modal-card">
            <!-- Header del Modal -->
            <div class="sgp-guest-modal-header">
                <div class="sgp-guest-header-brand">
                    <div class="sgp-guest-icon-badge">
                        <i class="ti ti-headset"></i>
                    </div>
                    <div>
                        <h3 class="sgp-guest-title">Mesa de Ayuda &bull; Soporte Técnico</h3>
                        <p class="sgp-guest-subtitle">Atención en vivo para incidencias de acceso, biometría o dudas</p>
                    </div>
                </div>
                <button type="button" class="sgp-guest-close-btn" onclick="cerrarSoporteGuestModal()" title="Cerrar ventana">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <!-- Contenido Modal: Vista 1 - Identificación y Formulario -->
            <div id="sgpGuestViewIdent" class="sgp-guest-modal-body">
                <!-- Banner de sesión activa previa (si existe en localStorage) -->
                <div id="sgpGuestActiveBanner" class="sgp-guest-resume-banner" style="display: none;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="pulse-dot"></span>
                        <div style="flex:1;">
                            <strong style="color:#1e3a8a; font-size:0.86rem;" id="sgpGuestResumeCode">Caso Activo</strong>
                            <p style="margin:2px 0 0; font-size:0.80rem; color:#475569;" id="sgpGuestResumeAsunto">Tienes una conversación en curso con soporte técnico.</p>
                        </div>
                        <button type="button" onclick="reanudarChatGuardado()" class="btn-guest-resume">
                            <i class="ti ti-messages"></i> Continuar Chat
                        </button>
                    </div>
                </div>

                <!-- Paso 1: Identificación con Cédula o Correo -->
                <div class="sgp-guest-section">
                    <label class="sgp-guest-label">
                        <i class="ti ti-id"></i> Identifícate con tu Cédula o Correo Electrónico:
                    </label>
                    <div class="sgp-guest-input-group">
                        <input type="text" id="sgpGuestIdentInput" class="sgp-guest-input" placeholder="Ej. V-28123456 o pasante@correo.com" autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault();identificarGuestUsuario();}">
                        <button type="button" id="btnSgpGuestIdent" onclick="identificarGuestUsuario()" class="btn-sgp-guest-search">
                            <i class="ti ti-search"></i>
                            <span>Verificar</span>
                        </button>
                    </div>
                    <span class="sgp-guest-hint">Si eres pasante, tutor o personal del ISPEB, buscaremos tu ficha para atenderte de inmediato.</span>
                </div>

                <!-- Feedback de Identificación -->
                <div id="sgpGuestFeedback" style="display: none; margin-top: 14px;"></div>

                <!-- Bento: Selección Rápida de Temas Frecuentes -->
                <div id="sgpTopicChipsSection" class="sgp-topic-chips-wrapper" style="display: none; margin-top: 14px;">
                    <div class="sgp-topic-chips-title">
                        <i class="ti ti-sparkles" style="color: #2563eb;"></i> <strong>Selección rápida por motivo frecuente:</strong>
                    </div>
                    <div class="sgp-topic-chips">
                        <button type="button" class="sgp-chip" onclick="seleccionarChipTema('Problema de Acceso', 'Olvidé mi contraseña o credenciales de acceso')">
                            <span>🔑</span> Clave Olvidada
                        </button>
                        <button type="button" class="sgp-chip" onclick="seleccionarChipTema('Error del Sistema', 'Error al leer huella o credencial biométrica')">
                            <span>👆</span> Error Huella
                        </button>
                        <button type="button" class="sgp-chip" onclick="seleccionarChipTema('Duda Operativa', 'Problema al registrar asistencia en el Kiosco / PIN')">
                            <span>⏱️</span> Kiosco / PIN
                        </button>
                        <button type="button" class="sgp-chip" onclick="seleccionarChipTema('Sugerencia', 'Duda sobre registro de horas o tutor de pasantía')">
                            <span>🎓</span> Duda Pasantía
                        </button>
                        <button type="button" class="sgp-chip" onclick="seleccionarChipTema('Otro', 'Problema técnico general en la plataforma')">
                            <span>🛠️</span> Problema Técnico
                        </button>
                    </div>
                </div>

                <!-- Formulario Bento Estilo Ticket Stub / Vale Digital -->
                <form id="formSoporteGuest" onsubmit="crearCasoGuest(event)" class="sgp-ticket-form-card" style="display: none; margin-top: 16px;">
                    <input type="hidden" id="sgpGuestUsuarioId" value="">
                    
                    <!-- Cabecera de Ticket con Código de Barras Simulado -->
                    <div class="sgp-ticket-stub-top">
                        <div class="sgp-ticket-barcode-wrap" title="ISPEB HelpDesk Voucher">
                            <span class="barcode-line thin"></span>
                            <span class="barcode-line thick"></span>
                            <span class="barcode-line mid"></span>
                            <span class="barcode-line thin"></span>
                            <span class="barcode-line thick"></span>
                            <span class="barcode-line mid"></span>
                            <span class="barcode-line thin"></span>
                            <span class="barcode-line thick"></span>
                            <span class="barcode-line thin"></span>
                            <span class="barcode-line mid"></span>
                        </div>
                        <div class="sgp-ticket-stub-heading">
                            <span class="sgp-ticket-badge-tag"><i class="ti ti-ticket"></i> TICKET DIGITAL ISPEB</span>
                            <span class="sgp-ticket-subtag">Mesa de Guardia 24/7</span>
                        </div>
                    </div>

                    <div class="sgp-ticket-perforated-line">
                        <span class="ticket-notch notch-left"></span>
                        <span class="ticket-notch notch-right"></span>
                    </div>

                    <div class="sgp-ticket-body-content">
                        <div class="sgp-guest-grid-2">
                            <div class="sgp-guest-field">
                                <label class="sgp-guest-label"><i class="ti ti-user"></i> Nombre Completo *</label>
                                <input type="text" id="sgpGuestNombre" class="sgp-guest-input" required placeholder="Tu nombre y apellido">
                            </div>
                            <div class="sgp-guest-field">
                                <label class="sgp-guest-label"><i class="ti ti-mail"></i> Correo Electrónico *</label>
                                <input type="email" id="sgpGuestCorreo" class="sgp-guest-input" required placeholder="tu@correo.com">
                            </div>
                        </div>

                        <div class="sgp-guest-grid-2" style="margin-top: 12px;">
                            <div class="sgp-guest-field">
                                <label class="sgp-guest-label"><i class="ti ti-phone"></i> Teléfono Móvil</label>
                                <input type="text" id="sgpGuestTelefono" class="sgp-guest-input" placeholder="Ej. 0414-1234567 (opcional)">
                            </div>
                            <div class="sgp-guest-field">
                                <label class="sgp-guest-label"><i class="ti ti-category"></i> Categoría del Caso *</label>
                                <select id="sgpGuestCategoria" class="sgp-guest-input" required>
                                    <option value="Problema de Acceso">Problema de Acceso / Contraseña olvidada</option>
                                    <option value="Error del Sistema">Error en Acceso Biométrico o Huella</option>
                                    <option value="Duda Operativa">Inconveniente con Kiosco o Marcaje</option>
                                    <option value="Sugerencia">Duda sobre mi Pasantía / Tutor</option>
                                    <option value="Otro">Otro problema técnico</option>
                                </select>
                            </div>
                        </div>

                        <div class="sgp-guest-field" style="margin-top: 12px;">
                            <label class="sgp-guest-label"><i class="ti ti-tag"></i> Asunto Breve de la Consulta *</label>
                            <input type="text" id="sgpGuestAsunto" class="sgp-guest-input" required placeholder="Ej. Olvidé mi clave y no puedo ingresar al sistema">
                        </div>

                        <div class="sgp-guest-field" style="margin-top: 12px;">
                            <label class="sgp-guest-label"><i class="ti ti-message-2"></i> Detalle de la Incidencia *</label>
                            <textarea id="sgpGuestMensaje" class="sgp-guest-textarea" rows="3" required placeholder="Describe lo que ocurre para que nuestro equipo técnico te responda de inmediato en vivo..."></textarea>
                        </div>

                        <div class="sgp-ticket-footer-actions">
                            <div class="sgp-ticket-meta-hint">
                                <i class="ti ti-shield-check" style="color: #059669;"></i>
                                <span>Recibirás copia en tu correo y acceso inmediato al chat técnico.</span>
                            </div>
                            <button type="submit" id="btnSubmitGuestTicket" class="btn-sgp-guest-submit">
                                <i class="ti ti-send"></i> Generar Ticket & Entrar al Chat
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Contenido Modal: Vista de Éxito / Comprobante de Ticket Emitido -->
            <div id="sgpGuestViewSuccess" class="sgp-guest-modal-body" style="display: none; padding: 26px 22px;">
                <div class="sgp-success-voucher-card">
                    <div class="sgp-voucher-stamp">EMITIDO ✓</div>
                    <div class="sgp-voucher-barcode">|||| ||| || |||| | ||| ||||</div>
                    <div class="sgp-voucher-tag">COMPROBANTE OFICIAL DE ASISTENCIA TÉCNICA</div>
                    
                    <div class="sgp-voucher-code-box">
                        <div class="sgp-voucher-code-title">CÓDIGO DE SEGUIMIENTO:</div>
                        <div class="sgp-voucher-code-row">
                            <span id="sgpSuccessCode" class="sgp-voucher-code">#TCK-000000-0000</span>
                            <button type="button" class="btn-voucher-copy" onclick="copiarCodigoTicket()" title="Copiar código al portapapeles">
                                <i class="ti ti-copy" id="sgpCopyIcon"></i> <span id="sgpCopyText">Copiar</span>
                            </button>
                        </div>
                    </div>

                    <div class="sgp-voucher-info-grid">
                        <div class="sgp-vig-item">
                            <span class="sgp-vig-label">Solicitante</span>
                            <strong id="sgpSuccessNombre" class="sgp-vig-val">-</strong>
                        </div>
                        <div class="sgp-vig-item">
                            <span class="sgp-vig-label">Estado</span>
                            <span class="sgp-vig-badge"><i class="ti ti-clock"></i> ABIERTO</span>
                        </div>
                        <div class="sgp-vig-item full-width">
                            <span class="sgp-vig-label">Asunto</span>
                            <span id="sgpSuccessAsunto" class="sgp-vig-val">-</span>
                        </div>
                    </div>

                    <div class="sgp-voucher-email-notice">
                        <i class="ti ti-mail-check" style="color: #059669; font-size: 1.25rem;"></i>
                        <div>
                            <strong>Comprobante enviado por correo electrónico:</strong>
                            <p style="margin: 2px 0 0; font-size: 0.78rem; color: #475569;">
                                Hemos despachado una notificación tanto a tu buzón como a los técnicos administradores de guardia.
                            </p>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px; align-items: center;">
                    <button type="button" id="btnEnterLiveChat" onclick="entrarAlChatDirecto()" class="btn-sgp-guest-submit" style="width: 100%; justify-content: center; padding: 13px 20px; font-size: 0.98rem;">
                        <i class="ti ti-messages"></i> Abrir Chat en Vivo Ahora
                    </button>
                    <span id="sgpAutoTransitionCount" style="font-size: 0.80rem; color: #64748b; font-weight: 500;">
                        Ingresando automáticamente al chat en 3 segundos...
                    </span>
                </div>
            </div>

            <!-- Overlay de Animación: Imprimiendo Ticket Digital -->
            <div id="sgpTicketGeneratingOverlay" class="sgp-ticket-generating-overlay" style="display: none;">
                <div class="sgp-printer-animation-card">
                    <div class="sgp-printer-slot-machine">
                        <div class="sgp-printer-mouth"></div>
                        <div class="sgp-printer-sheet">
                            <div class="sgp-printer-paper-header">
                                <span class="ph-dot"></span>
                                <span class="ph-title">SGP ISPEB HELPDESK</span>
                                <span class="ph-dot"></span>
                            </div>
                            <div class="sgp-printer-paper-barcode">||| |||| || |||</div>
                            <div class="sgp-printer-paper-code" id="sgpPrintingCodePreview">#TCK-TICKET</div>
                        </div>
                    </div>
                    <div class="sgp-printer-step-info">
                        <div class="sgp-printer-step-icon">
                            <i class="ti ti-printer animate-bounce" id="sgpPrinterStepIcon"></i>
                        </div>
                        <h4 id="sgpPrinterStepTitle" class="sgp-printer-title">Generando Ticket Digital...</h4>
                        <p id="sgpPrinterStepSub" class="sgp-printer-desc">Registrando la incidencia y preparando el chat en vivo...</p>
                        <div class="sgp-printer-progress-bar">
                            <div class="sgp-printer-progress-fill"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido Modal: Vista 2 - Chat en Vivo -->
            <div id="sgpGuestViewChat" class="sgp-guest-modal-body" style="display: none; padding: 0;">
                <!-- Barra superior del Ticket en Chat -->
                <div class="sgp-guest-chat-header">
                    <button type="button" onclick="volverAIdentificacion()" class="btn-guest-chat-back" title="Volver al menú de ayuda">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                    <div style="flex:1; overflow:hidden;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span id="sgpChatTicketCode" class="sgp-chat-code">#TCK-000000</span>
                            <span id="sgpChatTicketBadge" class="sgp-chat-badge badge-abierto">Abierto</span>
                        </div>
                        <div id="sgpChatTicketAsunto" class="sgp-chat-asunto-title">Asunto de la consulta</div>
                    </div>
                    <button type="button" onclick="cerrarSesionChatGuest()" class="btn-guest-chat-new" title="Iniciar un nuevo caso">
                        <i class="ti ti-plus"></i> Nuevo
                    </button>
                </div>

                <!-- Área de Mensajes del Chat -->
                <div id="sgpGuestChatMessages" class="sgp-guest-chat-messages">
                    <div class="sgp-chat-loading">
                        <i class="ti ti-loader animate-spin"></i> Conectando con la Mesa de Ayuda...
                    </div>
                </div>

                <!-- Input para escribir mensaje -->
                <div class="sgp-guest-chat-input-bar">
                    <input type="text" id="sgpGuestChatInputMsg" class="sgp-guest-chat-input" placeholder="Escribe tu mensaje a soporte técnico..." autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault();enviarMensajeChatGuest();}">
                    <button type="button" id="btnSgpGuestSendMsg" onclick="enviarMensajeChatGuest()" class="btn-sgp-guest-send" title="Enviar mensaje">
                        <i class="ti ti-send"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* ── SweetAlert2 siempre por encima de cualquier modal ── */
    .swal2-container {
        z-index: 100000000 !important;
    }

    /* ── Estilos Bento del Modal de Soporte Guest ── */
    .sgp-guest-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        box-sizing: border-box;
        animation: guestFadeIn 0.25s ease-out;
    }
    .sgp-guest-modal-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(226, 232, 240, 0.9);
        width: 100%;
        max-width: 580px;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: guestSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes guestFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes guestSlideUp { from { opacity: 0; transform: translateY(20px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }

    .sgp-guest-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
        color: #ffffff;
    }
    .sgp-guest-header-brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .sgp-guest-icon-badge {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #93c5fd;
        flex-shrink: 0;
    }
    .sgp-guest-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: -0.2px;
    }
    .sgp-guest-subtitle {
        margin: 2px 0 0;
        font-size: 0.78rem;
        color: rgba(255, 255, 255, 0.75);
    }
    .sgp-guest-close-btn {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: #ffffff;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .sgp-guest-close-btn:hover {
        background: rgba(239, 68, 68, 0.85);
        transform: rotate(90deg);
    }
    .sgp-guest-modal-body {
        padding: 20px 22px;
        overflow-y: auto;
        flex: 1;
    }
    .sgp-guest-resume-banner {
        background: #eff6ff;
        border: 1.5px solid #bfdbfe;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 16px;
    }
    .pulse-dot {
        width: 10px;
        height: 10px;
        background: #2563eb;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7);
        animation: pulseDot 1.6s infinite;
    }
    @keyframes pulseDot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
    }
    .btn-guest-resume {
        background: #2563eb;
        color: #ffffff;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .btn-guest-resume:hover { background: #1d4ed8; }

    .sgp-guest-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .sgp-guest-input-group {
        display: flex;
        gap: 8px;
    }
    .sgp-guest-input, .sgp-guest-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        font-size: 0.88rem;
        color: #0f172a;
        background: #f8fafc;
        box-sizing: border-box;
        transition: all 0.2s;
    }
    .sgp-guest-input:focus, .sgp-guest-textarea:focus {
        border-color: #2563eb;
        background: #ffffff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .btn-sgp-guest-search {
        background: #1e3a8a;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 0 18px;
        font-weight: 700;
        font-size: 0.86rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .btn-sgp-guest-search:hover { background: #172554; }
    .sgp-guest-hint {
        display: block;
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 5px;
    }
    .sgp-guest-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    @media (max-width: 520px) {
        .sgp-guest-grid-2 { grid-template-columns: 1fr; }
    }
    .btn-sgp-guest-submit {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 11px 20px;
        font-weight: 700;
        font-size: 0.90rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        transition: all 0.2s;
    }
    .btn-sgp-guest-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
    }

    /* ── Bento Topic Chips ── */
    .sgp-topic-chips-wrapper {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 14px;
    }
    .sgp-topic-chips-title {
        font-size: 0.76rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .sgp-topic-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .sgp-chip {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 20px;
        padding: 5px 11px;
        font-size: 0.74rem;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sgp-chip:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1e40af;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
    }
    .sgp-chip.active {
        background: #1e40af;
        border-color: #1e40af;
        color: #ffffff;
    }

    /* ── Ticket Stub Form Container ── */
    .sgp-ticket-form-card {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    }
    .sgp-ticket-stub-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #0f172a;
        color: #ffffff;
    }
    .sgp-ticket-barcode-wrap {
        display: flex;
        gap: 3px;
        align-items: flex-end;
        height: 22px;
    }
    .barcode-line {
        background: #94a3b8;
        height: 100%;
        display: inline-block;
        border-radius: 1px;
    }
    .barcode-line.thin { width: 1.5px; }
    .barcode-line.mid { width: 3px; }
    .barcode-line.thick { width: 4.5px; }
    .sgp-ticket-stub-heading {
        text-align: right;
    }
    .sgp-ticket-badge-tag {
        font-size: 0.70rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #38bdf8;
        display: block;
    }
    .sgp-ticket-subtag {
        font-size: 0.64rem;
        color: #94a3b8;
    }
    .sgp-ticket-perforated-line {
        position: relative;
        height: 0;
        border-top: 2px dashed #cbd5e1;
        margin: 0;
    }
    .ticket-notch {
        position: absolute;
        width: 18px;
        height: 18px;
        background: #ffffff;
        border-radius: 50%;
        top: -9px;
        border: 1.5px solid #cbd5e1;
        z-index: 2;
    }
    .ticket-notch.notch-left {
        left: -10px;
        clip-path: polygon(50% 0, 100% 0, 100% 100%, 50% 100%);
    }
    .ticket-notch.notch-right {
        right: -10px;
        clip-path: polygon(0 0, 50% 0, 50% 100%, 0 100%);
    }
    .sgp-ticket-body-content {
        padding: 16px 16px 14px;
    }
    .sgp-ticket-footer-actions {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .sgp-ticket-meta-hint {
        font-size: 0.72rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
        max-width: 250px;
        line-height: 1.3;
    }

    /* ── Voucher de Éxito / Rubber Stamp ── */
    .sgp-success-voucher-card {
        background: #ffffff;
        border: 2px dashed #93c5fd;
        border-radius: 18px;
        padding: 24px 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        text-align: center;
    }
    .sgp-voucher-stamp {
        position: absolute;
        top: 16px;
        right: 14px;
        border: 3px solid #059669;
        color: #059669;
        padding: 4px 12px;
        font-size: 0.85rem;
        font-weight: 900;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        border-radius: 8px;
        transform: rotate(12deg);
        mask-image: radial-gradient(circle, #000 70%, rgba(0,0,0,0.4) 100%);
        animation: stampDrop 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes stampDrop {
        0% { transform: scale(2.5) rotate(0deg); opacity: 0; }
        100% { transform: scale(1) rotate(12deg); opacity: 1; }
    }
    .sgp-voucher-barcode {
        font-family: monospace;
        letter-spacing: 4px;
        color: #64748b;
        font-size: 1.1rem;
        margin-bottom: 4px;
    }
    .sgp-voucher-tag {
        font-size: 0.70rem;
        font-weight: 800;
        color: #1e3a8a;
        letter-spacing: 1px;
        margin-bottom: 14px;
    }
    .sgp-voucher-code-box {
        background: #f0fdf4;
        border: 1.5px solid #86efac;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 16px;
    }
    .sgp-voucher-code-title {
        font-size: 0.68rem;
        font-weight: 700;
        color: #166534;
        letter-spacing: 0.5px;
    }
    .sgp-voucher-code-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 4px;
    }
    .sgp-voucher-code {
        font-size: 1.25rem;
        font-weight: 900;
        color: #15803d;
        letter-spacing: 1px;
    }
    .btn-voucher-copy {
        background: #ffffff;
        border: 1.5px solid #86efac;
        color: #166534;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.74rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
    }
    .btn-voucher-copy:hover {
        background: #dcfce7;
    }
    .sgp-voucher-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        text-align: left;
        margin-bottom: 14px;
        background: #f8fafc;
        padding: 12px;
        border-radius: 10px;
    }
    .sgp-vig-item { display: flex; flex-direction: column; }
    .sgp-vig-item.full-width { grid-column: span 2; }
    .sgp-vig-label { font-size: 0.68rem; color: #64748b; font-weight: 600; }
    .sgp-vig-val { font-size: 0.82rem; color: #0f172a; }
    .sgp-vig-badge {
        font-size: 0.70rem;
        font-weight: 800;
        color: #b45309;
        background: #fef3c7;
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        width: fit-content;
    }
    .sgp-voucher-email-notice {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: left;
    }

    /* ── Overlay Animación Generando / Imprimiendo Ticket ── */
    .sgp-ticket-generating-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.92);
        backdrop-filter: blur(6px);
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        animation: guestFadeIn 0.25s ease-out;
    }
    .sgp-printer-animation-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 28px 24px;
        width: 100%;
        max-width: 380px;
        text-align: center;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        position: relative;
    }
    .sgp-printer-slot-machine {
        position: relative;
        width: 190px;
        height: 110px;
        margin: 0 auto 18px;
        background: #0f172a;
        border-radius: 12px;
        border: 3px solid #334155;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: inset 0 6px 12px rgba(0,0,0,0.5);
    }
    .sgp-printer-mouth {
        width: 140px;
        height: 8px;
        background: #000000;
        border-radius: 4px;
        margin-top: 14px;
        box-shadow: 0 2px 4px rgba(255,255,255,0.1);
    }
    .sgp-printer-sheet {
        width: 120px;
        background: #ffffff;
        border-radius: 4px 4px 0 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.25);
        padding: 8px 6px;
        animation: printSheetOut 1.8s infinite alternate ease-in-out;
        transform-origin: top;
    }
    @keyframes printSheetOut {
        0% { transform: translateY(-50px) scale(0.9); opacity: 0.3; }
        100% { transform: translateY(0px) scale(1); opacity: 1; }
    }
    .sgp-printer-paper-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }
    .ph-dot { width: 4px; height: 4px; background: #2563eb; border-radius: 50%; }
    .ph-title { font-size: 0.50rem; font-weight: 800; color: #1e3a8a; }
    .sgp-printer-paper-barcode { font-family: monospace; font-size: 0.55rem; color: #475569; letter-spacing: 2px; }
    .sgp-printer-paper-code { font-size: 0.60rem; font-weight: 800; color: #059669; }
    .sgp-printer-step-info { margin-top: 10px; }
    .sgp-printer-step-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin: 0 auto 10px;
    }
    .sgp-printer-title { margin: 0 0 4px; font-size: 1.05rem; font-weight: 800; color: #0f172a; }
    .sgp-printer-desc { margin: 0 0 14px; font-size: 0.78rem; color: #64748b; }
    .sgp-printer-progress-bar {
        width: 100%;
        height: 6px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .sgp-printer-progress-fill {
        height: 100%;
        width: 65%;
        background: linear-gradient(90deg, #2563eb, #38bdf8);
        border-radius: 10px;
        animation: progressSlide 1.5s infinite ease-in-out;
    }
    @keyframes progressSlide {
        0% { transform: translateX(-100%); width: 30%; }
        50% { width: 70%; }
        100% { transform: translateX(200%); width: 30%; }
    }

    /* ── Chat en Vivo ── */
    .sgp-guest-chat-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        background: #f8fafc;
        border-bottom: 1.5px solid #e2e8f0;
    }
    .btn-guest-chat-back {
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 6px;
        border-radius: 8px;
        display: flex;
        align-items: center;
    }
    .btn-guest-chat-back:hover { background: #e2e8f0; color: #0f172a; }
    .sgp-chat-code {
        font-weight: 800;
        color: #1e3a8a;
        font-size: 0.88rem;
    }
    .sgp-chat-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        text-transform: uppercase;
    }
    .badge-abierto { background: #fef3c7; color: #b45309; }
    .badge-proceso { background: #dbeafe; color: #1d4ed8; }
    .badge-resuelto { background: #d1fae5; color: #047857; }
    .badge-cerrado { background: #e2e8f0; color: #475569; }
    .sgp-chat-asunto-title {
        font-size: 0.82rem;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .btn-guest-chat-new {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 8px;
        padding: 5px 10px;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    .btn-guest-chat-new:hover { background: #e2e8f0; }

    .sgp-guest-chat-messages {
        height: 380px;
        max-height: 52vh;
        overflow-y: auto;
        padding: 16px 18px;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .sgp-chat-loading {
        text-align: center;
        color: #64748b;
        font-size: 0.85rem;
        margin-top: 40px;
    }
    .sgp-msg-bubble {
        max-width: 82%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 0.86rem;
        line-height: 1.45;
        position: relative;
        word-break: break-word;
    }
    .sgp-msg-user {
        align-self: flex-end;
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: #ffffff;
        border-bottom-right-radius: 4px;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25);
    }
    .sgp-msg-soporte {
        align-self: flex-start;
        background: #ffffff;
        color: #0f172a;
        border: 1.5px solid #e2e8f0;
        border-bottom-left-radius: 4px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
    }
    .sgp-msg-author {
        font-size: 0.72rem;
        font-weight: 800;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .sgp-msg-soporte .sgp-msg-author { color: #1e3a8a; }
    .sgp-msg-user .sgp-msg-author { color: #bfdbfe; }
    .sgp-msg-time {
        font-size: 0.68rem;
        opacity: 0.75;
        margin-top: 4px;
        text-align: right;
    }

    .sgp-guest-chat-input-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        background: #ffffff;
        border-top: 1.5px solid #e2e8f0;
    }
    .sgp-guest-chat-input {
        flex: 1;
        padding: 10px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        font-size: 0.88rem;
        outline: none;
        background: #f8fafc;
        box-sizing: border-box;
    }
    .sgp-guest-chat-input:focus {
        border-color: #2563eb;
        background: #ffffff;
    }
    .btn-sgp-guest-send {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #2563eb;
        color: #ffffff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .btn-sgp-guest-send:hover {
        background: #1d4ed8;
        transform: scale(1.05);
    }
    </style>

    <script>
    // ========================================================
    // SCRIPT DE SOPORTE GUEST / LOGIN EN TIEMPO REAL
    // ========================================================
    let sgpGuestPollTimer = null;
    let sgpActiveTicketId = 0;
    let sgpActiveToken = '';
    let sgpLastMsgId = 0;

    function reproducirTonoSoporte() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(800, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1100, ctx.currentTime + 0.18);
            gain.gain.setValueAtTime(0.12, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.25);
        } catch (e) {}
    }

    function abrirSoporteGuestModal() {
        const modal = document.getElementById('modalSoporteGuest');
        if (!modal) return;
        modal.style.display = 'flex';

        // Comprobar si hay una sesión guardada en localStorage
        try {
            const saved = localStorage.getItem('sgp_guest_support_session');
            if (saved) {
                const sData = JSON.parse(saved);
                if (sData && sData.ticket_id && sData.guest_token) {
                    const banner = document.getElementById('sgpGuestActiveBanner');
                    const codeEl = document.getElementById('sgpGuestResumeCode');
                    const asuntoEl = document.getElementById('sgpGuestResumeAsunto');
                    if (banner && codeEl && asuntoEl) {
                        codeEl.textContent = 'Caso Activo: ' + (sData.codigo || '#' + sData.ticket_id);
                        asuntoEl.textContent = sData.asunto ? 'Asunto: ' + sData.asunto : 'Tienes un caso en proceso.';
                        banner.style.display = 'block';
                    }
                }
            }
        } catch (e) {}

        const inp = document.getElementById('sgpGuestIdentInput');
        if (inp) setTimeout(() => inp.focus(), 150);
    }

    function cerrarSoporteGuestModal() {
        const modal = document.getElementById('modalSoporteGuest');
        if (modal) modal.style.display = 'none';
        if (sgpGuestPollTimer) {
            clearInterval(sgpGuestPollTimer);
            sgpGuestPollTimer = null;
        }
    }

    function volverAIdentificacion() {
        if (sgpGuestPollTimer) {
            clearInterval(sgpGuestPollTimer);
            sgpGuestPollTimer = null;
        }
        if (sgpSuccessCountdownTimer) {
            clearInterval(sgpSuccessCountdownTimer);
            sgpSuccessCountdownTimer = null;
        }
        const successView = document.getElementById('sgpGuestViewSuccess');
        if (successView) successView.style.display = 'none';
        document.getElementById('sgpGuestViewChat').style.display = 'none';
        document.getElementById('sgpGuestViewIdent').style.display = 'block';
    }

    function cerrarSesionChatGuest() {
        Swal.fire({
            title: '¿Iniciar un nuevo caso?',
            text: 'Esta acción te permitirá redactar una nueva consulta.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, nuevo caso',
            cancelButtonText: 'Continuar en este',
            confirmButtonColor: '#2563eb'
        }).then((res) => {
            if (res.isConfirmed) {
                try { localStorage.removeItem('sgp_guest_support_session'); } catch (e) {}
                volverAIdentificacion();
                document.getElementById('formSoporteGuest').reset();
                document.getElementById('sgpGuestUsuarioId').value = '';
                document.getElementById('sgpGuestFeedback').style.display = 'none';
                document.getElementById('formSoporteGuest').style.display = 'none';
                const chips = document.getElementById('sgpTopicChipsSection');
                if (chips) chips.style.display = 'none';
                const banner = document.getElementById('sgpGuestActiveBanner');
                if (banner) banner.style.display = 'none';
            }
        });
    }

    function reanudarChatGuardado() {
        try {
            const saved = localStorage.getItem('sgp_guest_support_session');
            if (saved) {
                const s = JSON.parse(saved);
                cargarChatGuest(s.ticket_id, s.guest_token, s.codigo, s.asunto, s.estado || 'En Proceso');
            }
        } catch (e) {}
    }

    // Selección rápida de temas mediante Chips Bento
    function seleccionarChipTema(categoria, sugerencia) {
        const select = document.getElementById('sgpGuestCategoria');
        const asunto = document.getElementById('sgpGuestAsunto');
        if (select) select.value = categoria;
        if (asunto && (!asunto.value || asunto.value.trim().length === 0)) {
            asunto.value = sugerencia;
        }

        // Resaltar visualmente el chip activo
        document.querySelectorAll('.sgp-chip').forEach(ch => ch.classList.remove('active'));
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }

        const msgInput = document.getElementById('sgpGuestMensaje');
        if (msgInput) msgInput.focus();
    }

    // Copiar código de ticket al portapapeles
    function copiarCodigoTicket() {
        const codeEl = document.getElementById('sgpSuccessCode');
        const text = codeEl ? codeEl.textContent.trim() : '';
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const copyIcon = document.getElementById('sgpCopyIcon');
            const copyText = document.getElementById('sgpCopyText');
            if (copyIcon) copyIcon.className = 'ti ti-check';
            if (copyText) copyText.textContent = '¡Copiado!';
            setTimeout(() => {
                if (copyIcon) copyIcon.className = 'ti ti-copy';
                if (copyText) copyText.textContent = 'Copiar';
            }, 2000);
        }).catch(() => {
            // Fallback manual si portapapeles no está disponible
            const tmp = document.createElement('textarea');
            tmp.value = text;
            document.body.appendChild(tmp);
            tmp.select();
            document.execCommand('copy');
            document.body.removeChild(tmp);
        });
    }

    // Entrar al chat directamente desde la pantalla de confirmación
    function entrarAlChatDirecto() {
        if (sgpSuccessCountdownTimer) {
            clearInterval(sgpSuccessCountdownTimer);
            sgpSuccessCountdownTimer = null;
        }
        if (sgpPendingSuccessData) {
            const d = sgpPendingSuccessData;
            document.getElementById('sgpGuestViewSuccess').style.display = 'none';
            cargarChatGuest(d.ticket_id, d.guest_token, d.codigo, d.asunto, 'Abierto');
        }
    }

    async function identificarGuestUsuario() {
        const input = document.getElementById('sgpGuestIdentInput');
        const ident = input ? input.value.trim() : '';
        const feedback = document.getElementById('sgpGuestFeedback');
        const form = document.getElementById('formSoporteGuest');
        const chips = document.getElementById('sgpTopicChipsSection');
        const btn = document.getElementById('btnSgpGuestIdent');

        if (!ident) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Por favor ingresa tu cédula o correo institucional.' });
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader animate-spin"></i>';

        try {
            const resp = await fetch('<?= URLROOT ?>/soporte/identificarGuest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ identificador: ident })
            });
            const data = await resp.json();

            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-search"></i> <span>Verificar</span>';

            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al verificar.' });
                return;
            }

            feedback.style.display = 'block';

            if (data.encontrado && data.usuario) {
                const u = data.usuario;
                document.getElementById('sgpGuestUsuarioId').value = u.id || '';
                document.getElementById('sgpGuestNombre').value = u.nombre || '';
                document.getElementById('sgpGuestCorreo').value = u.correo || '';
                document.getElementById('sgpGuestTelefono').value = u.telefono || '';

                if (data.ticket_activo) {
                    const tk = data.ticket_activo;
                    feedback.innerHTML = `
                        <div style="background:#fef3c7; border:1.5px solid #fde68a; border-radius:12px; padding:14px; color:#92400e;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <strong>¡Hola, ${u.nombre}!</strong>
                                <span style="background:#d97706; color:#fff; font-size:0.72rem; padding:2px 8px; border-radius:6px; font-weight:700;">CASO EN ATENCIÓN</span>
                            </div>
                            <p style="margin:0 0 10px; font-size:0.83rem;">Tienes el ticket <strong>${tk.codigo}</strong> (${tk.asunto}) actualmente abierto con soporte técnico.</p>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button type="button" onclick="cargarChatGuest(${tk.id}, '${tk.guest_token}', '${tk.codigo}', '${tk.asunto.replace(/'/g, "\\'")}', '${tk.estado}')" class="btn-guest-resume" style="padding:8px 16px;">
                                    <i class="ti ti-messages"></i> Entrar al Chat de este Caso
                                </button>
                                <button type="button" onclick="document.getElementById('formSoporteGuest').style.display='block'; if(document.getElementById('sgpTopicChipsSection')) document.getElementById('sgpTopicChipsSection').style.display='block'; this.style.display='none';" style="background:transparent; border:1.5px solid #d97706; color:#92400e; padding:6px 12px; border-radius:8px; font-weight:700; font-size:0.78rem; cursor:pointer;">
                                    Crear un caso diferente
                                </button>
                            </div>
                        </div>
                    `;
                    form.style.display = 'none';
                    if (chips) chips.style.display = 'none';
                } else {
                    feedback.innerHTML = `
                        <div style="background:#ecfdf5; border:1.5px solid #a7f3d0; border-radius:12px; padding:12px 14px; color:#065f46; display:flex; align-items:center; gap:10px;">
                            <i class="ti ti-circle-check" style="font-size:1.4rem; color:#059669;"></i>
                            <div style="flex:1;">
                                <strong style="font-size:0.86rem;">¡Hola, ${u.nombre}!</strong>
                                <div style="font-size:0.78rem; opacity:0.9;">Cédula: ${u.cedula} &bull; Depto: ${u.departamento}</div>
                            </div>
                        </div>
                    `;
                    form.style.display = 'block';
                    if (chips) chips.style.display = 'block';
                }
            } else {
                document.getElementById('sgpGuestUsuarioId').value = '';
                feedback.innerHTML = `
                    <div style="background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:12px; padding:12px 14px; color:#1e40af; display:flex; align-items:flex-start; gap:10px;">
                        <i class="ti ti-info-circle" style="font-size:1.3rem; color:#2563eb; margin-top:1px;"></i>
                        <div style="flex:1; font-size:0.82rem;">
                            No encontramos una cuenta registrada con esa identificación. <strong>¡No te preocupes!</strong> Completa tus datos de contacto y atenderemos tu solicitud directamente.
                        </div>
                    </div>
                `;
                form.style.display = 'block';
                if (chips) chips.style.display = 'block';
            }

        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-search"></i> <span>Verificar</span>';
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor de soporte.' });
        }
    }

    let sgpSuccessCountdownTimer = null;
    let sgpPendingSuccessData    = null;

    async function crearCasoGuest(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitGuestTicket');
        const overlay = document.getElementById('sgpTicketGeneratingOverlay');
        const titleEl = document.getElementById('sgpPrinterStepTitle');
        const subEl   = document.getElementById('sgpPrinterStepSub');
        const codePrev = document.getElementById('sgpPrintingCodePreview');

        const payload = {
            usuario_id: document.getElementById('sgpGuestUsuarioId').value || null,
            nombre:     document.getElementById('sgpGuestNombre').value.trim(),
            correo:     document.getElementById('sgpGuestCorreo').value.trim(),
            telefono:   document.getElementById('sgpGuestTelefono').value.trim(),
            categoria:  document.getElementById('sgpGuestCategoria').value,
            asunto:     document.getElementById('sgpGuestAsunto').value.trim(),
            mensaje:    document.getElementById('sgpGuestMensaje').value.trim()
        };

        if (!payload.nombre || !payload.correo || !payload.asunto || !payload.mensaje) {
            Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Por favor completa todos los campos del caso.' });
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader animate-spin"></i> Generando caso...';

        // Activar overlay con micro-animaciones secuenciales
        if (overlay) {
            overlay.style.display = 'flex';
            if (titleEl) titleEl.textContent = 'Imprimiendo Ticket Digital...';
            if (subEl)   subEl.textContent   = 'Registrando incidencia en la Mesa de Guardia...';
            if (codePrev) codePrev.textContent = '#TCK-GENERANDO';
        }

        const stepTimer1 = setTimeout(() => {
            if (titleEl) titleEl.textContent = 'Notificando a la Guardia Técnica...';
            if (subEl)   subEl.textContent   = 'Despachando alertas internas y confirmación por correo...';
        }, 800);

        const stepTimer2 = setTimeout(() => {
            if (titleEl) titleEl.textContent = 'Conectando Chat en Vivo...';
            if (subEl)   subEl.textContent   = 'Habilitando canal seguro con soporte técnico...';
        }, 1600);

        // Controlador de tiempo de espera con AbortController (máximo 12 segundos)
        const abortCtrl = new AbortController();
        const timeoutId = setTimeout(() => abortCtrl.abort(), 12000);

        try {
            const resp = await fetch('<?= URLROOT ?>/soporte/crearGuest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                signal: abortCtrl.signal
            });
            clearTimeout(timeoutId);
            clearTimeout(stepTimer1);
            clearTimeout(stepTimer2);

            const data = await resp.json();

            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-send"></i> Generar Ticket & Entrar al Chat';

            if (!data.success) {
                if (overlay) overlay.style.display = 'none';
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo crear el ticket.' });
                return;
            }

            // Guardar sesión en localStorage
            try {
                localStorage.setItem('sgp_guest_support_session', JSON.stringify({
                    ticket_id:   data.ticket_id,
                    guest_token: data.guest_token,
                    codigo:      data.codigo,
                    asunto:      payload.asunto,
                    estado:      'Abierto'
                }));
            } catch (err) {}

            // Ocultar overlay de impresión
            if (overlay) overlay.style.display = 'none';

            // Preparar y mostrar comprobante de éxito (Voucher con sello de agua y código)
            sgpPendingSuccessData = {
                ticket_id:   data.ticket_id,
                guest_token: data.guest_token,
                codigo:      data.codigo,
                asunto:      payload.asunto
            };

            document.getElementById('sgpGuestViewIdent').style.display = 'none';
            const successView = document.getElementById('sgpGuestViewSuccess');
            if (successView) {
                document.getElementById('sgpSuccessCode').textContent   = data.codigo;
                document.getElementById('sgpSuccessNombre').textContent = payload.nombre;
                document.getElementById('sgpSuccessAsunto').textContent = payload.asunto;
                successView.style.display = 'block';

                // Reproducir tono sutil de confirmación
                reproducirTonoSoporte();

                // Cuenta regresiva automática para entrar al chat (3 segundos)
                let remaining = 3;
                const countEl = document.getElementById('sgpAutoTransitionCount');
                if (countEl) countEl.textContent = `Ingresando automáticamente al chat en ${remaining} segundos...`;

                if (sgpSuccessCountdownTimer) clearInterval(sgpSuccessCountdownTimer);
                sgpSuccessCountdownTimer = setInterval(() => {
                    remaining--;
                    if (remaining > 0) {
                        if (countEl) countEl.textContent = `Ingresando automáticamente al chat en ${remaining} segundo${remaining !== 1 ? 's' : ''}...`;
                    } else {
                        clearInterval(sgpSuccessCountdownTimer);
                        sgpSuccessCountdownTimer = null;
                        entrarAlChatDirecto();
                    }
                }, 1000);
            } else {
                cargarChatGuest(data.ticket_id, data.guest_token, data.codigo, payload.asunto, 'Abierto');
            }

        } catch (e) {
            clearTimeout(timeoutId);
            clearTimeout(stepTimer1);
            clearTimeout(stepTimer2);
            if (overlay) overlay.style.display = 'none';
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-send"></i> Generar Ticket & Entrar al Chat';

            const isTimeout = e.name === 'AbortError';
            Swal.fire({
                icon: 'error',
                title: isTimeout ? 'Tiempo de espera agotado' : 'Error',
                text: isTimeout 
                    ? 'El servidor tardó más de lo esperado en procesar el caso. Por favor verifica tu conexión y vuelve a intentarlo.'
                    : 'Error al enviar la solicitud: ' + (e.message || 'Verifica tu conexión.')
            });
        }
    }

    async function cargarChatGuest(ticketId, token, codigo, asunto, estado) {
        sgpActiveTicketId = ticketId;
        sgpActiveToken    = token;
        sgpLastMsgId      = 0;

        document.getElementById('sgpGuestViewIdent').style.display = 'none';
        document.getElementById('sgpGuestViewChat').style.display = 'block';

        document.getElementById('sgpChatTicketCode').textContent = codigo;
        document.getElementById('sgpChatTicketAsunto').textContent = asunto;

        const badge = document.getElementById('sgpChatTicketBadge');
        if (badge) {
            badge.textContent = estado;
            badge.className = 'sgp-chat-badge ' + (
                estado === 'Abierto' ? 'badge-abierto' :
                estado === 'En Proceso' ? 'badge-proceso' :
                estado === 'Resuelto' ? 'badge-resuelto' : 'badge-cerrado'
            );
        }

        const msgBox = document.getElementById('sgpGuestChatMessages');
        msgBox.innerHTML = '<div class="sgp-chat-loading"><i class="ti ti-loader animate-spin"></i> Cargando conversación...</div>';

        try {
            const resp = await fetch(`<?= URLROOT ?>/soporte/mensajesGuest?ticket_id=${ticketId}&token=${encodeURIComponent(token)}`);
            const data = await resp.json();

            if (!data.success) {
                msgBox.innerHTML = `<div style="text-align:center; padding:20px; color:#dc2626;">${data.message}</div>`;
                return;
            }

            msgBox.innerHTML = '';
            if (Array.isArray(data.mensajes) && data.mensajes.length > 0) {
                data.mensajes.forEach(m => renderBurbujaGuest(m));
                sgpLastMsgId = data.mensajes[data.mensajes.length - 1].id;
            } else {
                msgBox.innerHTML = '<div class="sgp-chat-loading">No hay mensajes aún. Escribe tu primera consulta abajo.</div>';
            }

            scrollChatGuestToBottom();

            // Iniciar Polling en tiempo real
            if (sgpGuestPollTimer) clearInterval(sgpGuestPollTimer);
            sgpGuestPollTimer = setInterval(pollMensajesGuest, 3500);

            const inp = document.getElementById('sgpGuestChatInputMsg');
            if (inp) setTimeout(() => inp.focus(), 150);

        } catch (e) {
            msgBox.innerHTML = '<div style="text-align:center; padding:20px; color:#dc2626;">Error al cargar el historial del chat.</div>';
        }
    }

    function renderBurbujaGuest(m) {
        const msgBox = document.getElementById('sgpGuestChatMessages');
        if (!msgBox) return;

        const esSoporte = (m.remitente_tipo === 'soporte');
        const bubble = document.createElement('div');
        bubble.className = 'sgp-msg-bubble ' + (esSoporte ? 'sgp-msg-soporte' : 'sgp-msg-user');
        bubble.setAttribute('data-msg-id', m.id);

        const timeStr = m.created_at ? m.created_at.substring(11, 16) : '';
        const authorIcon = esSoporte ? '<i class="ti ti-headset"></i>' : '<i class="ti ti-user"></i>';

        bubble.innerHTML = `
            <div class="sgp-msg-author">${authorIcon} ${m.remitente_nombre || (esSoporte ? 'Soporte Técnico' : 'Tú')}</div>
            <div class="sgp-msg-text">${nl2brGuest(escapeHtmlGuest(m.mensaje))}</div>
            <div class="sgp-msg-time">${timeStr}</div>
        `;

        msgBox.appendChild(bubble);
    }

    async function enviarMensajeChatGuest() {
        const inp = document.getElementById('sgpGuestChatInputMsg');
        const msg = inp ? inp.value.trim() : '';
        if (!msg || sgpActiveTicketId <= 0 || !sgpActiveToken) return;

        inp.value = '';

        // Inserción optimista en pantalla
        renderBurbujaGuest({
            id: 'temp-' + Date.now(),
            remitente_tipo: 'usuario',
            remitente_nombre: 'Tú',
            mensaje: msg,
            created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
        });
        scrollChatGuestToBottom();

        try {
            const resp = await fetch('<?= URLROOT ?>/soporte/mensajeGuest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ticket_id: sgpActiveTicketId,
                    token:     sgpActiveToken,
                    mensaje:   msg
                })
            });
            const data = await resp.json();
            if (data.success && data.message_id) {
                sgpLastMsgId = Math.max(sgpLastMsgId, data.message_id);
            }
        } catch (e) {
            console.warn('[SGP-GUEST-CHAT] Error al enviar mensaje:', e);
        }
    }

    async function pollMensajesGuest() {
        if (sgpActiveTicketId <= 0 || !sgpActiveToken) return;

        try {
            const resp = await fetch(`<?= URLROOT ?>/soporte/nuevosMensajesGuest?ticket_id=${sgpActiveTicketId}&token=${encodeURIComponent(sgpActiveToken)}&after_id=${sgpLastMsgId}`);
            const data = await resp.json();

            if (data.success) {
                if (data.estado) {
                    const badge = document.getElementById('sgpChatTicketBadge');
                    if (badge) {
                        badge.textContent = data.estado;
                        badge.className = 'sgp-chat-badge ' + (
                            data.estado === 'Abierto' ? 'badge-abierto' :
                            data.estado === 'En Proceso' ? 'badge-proceso' :
                            data.estado === 'Resuelto' ? 'badge-resuelto' : 'badge-cerrado'
                        );
                    }
                }

                if (Array.isArray(data.mensajes) && data.mensajes.length > 0) {
                    let hayRespuestaSoporte = false;
                    data.mensajes.forEach(m => {
                        // Evitar duplicados por id
                        const exists = document.querySelector(`.sgp-msg-bubble[data-msg-id="${m.id}"]`);
                        if (!exists) {
                            renderBurbujaGuest(m);
                            sgpLastMsgId = Math.max(sgpLastMsgId, m.id);
                            if (m.remitente_tipo === 'soporte') hayRespuestaSoporte = true;
                        }
                    });
                    scrollChatGuestToBottom();
                    if (hayRespuestaSoporte) reproducirTonoSoporte();
                }
            }
        } catch (e) {}
    }

    function scrollChatGuestToBottom() {
        const msgBox = document.getElementById('sgpGuestChatMessages');
        if (msgBox) {
            setTimeout(() => {
                msgBox.scrollTop = msgBox.scrollHeight;
            }, 60);
        }
    }

    function escapeHtmlGuest(str) {
        return (str || '')
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function nl2brGuest(str) {
        return (str || '').replace(/\n/g, '<br>');
    }
    </script>
    
    <?php 
    // ✨ CRÍTICO: Incluir footer para cargar notification-service.js
    include_once APPROOT . '/views/layouts/footer.php'; 
    ?>
</body>
</html>
