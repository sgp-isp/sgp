<?php
$errorMsg = $error ?? '';
$sinPreg  = (stripos($errorMsg, 'sin preguntas') !== false || stripos($errorMsg, 'preguntas de seguridad') !== false);
$correoNF = (stripos($errorMsg, 'correo no encontrado') !== false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Recuperación</title>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notifications.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">
    <script>const URLROOT = <?php echo json_encode(URLROOT, JSON_UNESCAPED_SLASHES) ?>;</script> <!-- [FIX-C3] -->

    <style>
    .recovery-alert {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        border: 1.5px solid #fed7aa;
        border-left: 4px solid #f97316;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 20px;
        animation: slideInAlert 0.35s cubic-bezier(.22,1,.36,1);
    }
    .recovery-alert.danger {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border-color: #fca5a5;
        border-left-color: #ef4444;
    }
    @keyframes slideInAlert {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .recovery-alert-icon {
        width: 38px; height: 38px; border-radius: 10px;
        background: rgba(249,115,22,0.12);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: #f97316; flex-shrink: 0;
    }
    .recovery-alert.danger .recovery-alert-icon { background: rgba(239,68,68,0.12); color: #ef4444; }
    .recovery-alert-title { font-weight: 700; font-size: 0.92rem; color: #9a3412; margin-bottom: 4px; }
    .recovery-alert.danger .recovery-alert-title { color: #991b1b; }
    .recovery-alert-body { font-size: 0.82rem; color: #7c3a1a; line-height: 1.5; }
    .recovery-alert.danger .recovery-alert-body { color: #7f1d1d; }
    .recovery-alert-actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-alert-primary {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 7px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 700;
        background: #f97316; color: #fff; border: none; cursor: pointer;
        text-decoration: none; transition: background 0.15s;
    }
    .btn-alert-primary:hover { background: #ea6c0b; }
    .btn-alert-secondary {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 7px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 700;
        background: transparent; color: #9a3412; border: 1.5px solid #fed7aa;
        cursor: pointer; text-decoration: none; transition: all 0.15s;
    }
    .btn-alert-secondary:hover { background: rgba(249,115,22,0.08); }
    </style>
</head>
<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>
    <div class="auth-card" style="position: relative;">
        <a href="<?= URLROOT ?>/auth/login" class="btn-back" title="Volver al Login">
            <i class="ti ti-arrow-left"></i>
        </a>

        <div class="auth-header">
            <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" class="auth-logo">
            <h1 class="auth-title">Recuperar Cuenta</h1>
            <p class="auth-subtitle">Paso 1: Identificación</p>
        </div>

        <?php if ($sinPreg): ?>
        <div class="recovery-alert">
            <div class="recovery-alert-icon"><i class="ti ti-shield-x"></i></div>
            <div>
                <div class="recovery-alert-title">Cuenta sin preguntas de seguridad</div>
                <div class="recovery-alert-body">
                    Esta cuenta no tiene preguntas de seguridad configuradas,
                    por lo que no es posible recuperarla por este medio.<br><br>
                    <strong>¿Qué puedes hacer?</strong><br>
                    Contacta al administrador del sistema para restablecer tu contraseña.
                </div>
                <div class="recovery-alert-actions">
                    <a href="<?= URLROOT ?>/auth/login" class="btn-alert-primary">
                        <i class="ti ti-arrow-left"></i> Volver al Login
                    </a>
                    <a href="mailto:soporte@sgp.local" class="btn-alert-secondary">
                        <i class="ti ti-mail"></i> Contactar soporte
                    </a>
                </div>
            </div>
        </div>
        <?php elseif ($correoNF): ?>
        <div class="recovery-alert danger">
            <div class="recovery-alert-icon"><i class="ti ti-user-x"></i></div>
            <div>
                <div class="recovery-alert-title">Correo no registrado</div>
                <div class="recovery-alert-body">
                    No encontramos ninguna cuenta con ese correo.<br>
                    Verifica que esté escrito correctamente.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form action="<?= URLROOT ?>/auth/recovery" method="POST">
            <input type="hidden" name="step" value="1">
            <div class="form-group">
                <input type="email" name="email" id="email" class="input-modern" placeholder=" " required>
                <label for="email" class="label-floating">
                    <i class="ti ti-search" style="margin-right: 8px; font-size: 18px;"></i>Ingrese su correo electrónico
                </label>
                <div class="email-feedback"></div>
                <span class="input-hint">Ingresa el correo con el que te registraste</span>
            </div>
            <button type="submit" class="btn-primary" style="margin-top: 1rem;">
                Continuar <i class="ti ti-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?= URLROOT ?>/auth/login" class="auth-link">Cancelar y volver</a>
        </div>
    </div>

    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    <script src="<?= URLROOT ?>/js/validation.js"></script>
    <script>
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('blur', function() { validateEmailWithFeedback(this); });

        <?php if (!empty($errorMsg) && !$sinPreg && !$correoNF): ?>
        if (typeof NotificationService !== 'undefined') {
            NotificationService.error('<?= htmlspecialchars($errorMsg) ?>');
        }
        <?php endif; ?>
    </script>
</body>
</html>
