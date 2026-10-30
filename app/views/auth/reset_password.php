<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP - Restablecer Contraseña</title>

    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png?v=3">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">

    <style>
        /* ── Barra de fortaleza de contraseña ── */
        .pwd-strength-bar {
            height: 5px;
            border-radius: 4px;
            background: #e2e8f0;
            margin-top: 8px;
            overflow: hidden;
        }
        .pwd-strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: width 0.35s ease, background 0.35s ease;
        }
        .pwd-strength-label {
            font-size: 0.72rem;
            font-weight: 600;
            margin-top: 4px;
            min-height: 16px;
            transition: color 0.3s;
        }
        .pwd-rules {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            margin-top: 10px;
            font-size: 0.78rem;
            color: #64748b;
        }
        .pwd-rule {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 4px;
        }
        .pwd-rule:last-child { margin-bottom: 0; }
        .pwd-rule i {
            font-size: 0.85rem;
            width: 14px;
            flex-shrink: 0;
            transition: color 0.2s;
        }
        .pwd-rule.ok { color: #059669; }
        .pwd-rule.ok i { color: #059669; }
        .pwd-rule.fail i { color: #cbd5e1; }
    </style>
</head>
<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>
    <div class="auth-card" style="position: relative;">
        <a href="<?= URLROOT ?>/auth/login" class="btn-back" title="Cancelar y volver" onclick="return confirmarSalirReset(event)">
            <i class="ti ti-arrow-left"></i>
        </a>

        <div class="auth-header">
            <img src="<?= URLROOT ?>/img/logo.png" alt="SGP Logo" class="auth-logo">
            <h1 class="auth-title">Nueva Contraseña</h1>
            <p class="auth-subtitle">Establece tu nueva clave de acceso</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:.85rem;color:#b91c1c;margin-bottom:16px;text-align:center;">
                <i class="ti ti-alert-circle" style="margin-right:6px;"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= URLROOT ?>/auth/recovery" method="POST" id="formResetPwd" novalidate>
            <input type="hidden" name="step" value="3">

            <!-- Password con Floating Label y Toggle -->
            <div class="form-group">
                <div style="position: relative;">
                    <input type="password" name="password" id="new_password" class="input-modern" placeholder=" " required style="padding-right: 48px;" autocomplete="new-password">
                    <label for="new_password" class="label-floating">
                        <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Contraseña Nueva
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePassword('new_password', this)"></i>
                </div>

                <!-- Barra de fortaleza -->
                <div class="pwd-strength-bar"><div class="pwd-strength-fill" id="pwdFill"></div></div>
                <div class="pwd-strength-label" id="pwdLabel"></div>

                <!-- Reglas visuales -->
                <div class="pwd-rules">
                    <div class="pwd-rule fail" id="rule-len"><i class="ti ti-circle-check"></i>Mínimo 8 caracteres</div>
                    <div class="pwd-rule fail" id="rule-upper"><i class="ti ti-circle-check"></i>Al menos una mayúscula</div>
                    <div class="pwd-rule fail" id="rule-lower"><i class="ti ti-circle-check"></i>Al menos una minúscula</div>
                    <div class="pwd-rule fail" id="rule-num"><i class="ti ti-circle-check"></i>Al menos un número</div>
                </div>
            </div>

            <!-- Confirmar Password -->
            <div class="form-group">
                <input type="password" name="password_confirm" id="confirm_password" class="input-modern" placeholder=" " required style="padding-right: 48px;" autocomplete="new-password">
                <label for="confirm_password" class="label-floating">
                    <i class="ti ti-lock-check" style="margin-right: 8px; font-size: 18px;"></i>Confirmar Contraseña
                </label>
                <i class="ti ti-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 1rem;">
                Actualizar <i class="ti ti-refresh" style="margin-left: 8px;"></i>
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?= URLROOT ?>/auth/login" class="auth-link" onclick="return confirmarSalirReset(event)">Cancelar</a>
        </div>
    </div>

    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>

    <script src="<?= URLROOT ?>/js/sweetalert2.all.min.js"></script>
    <script>
        // Contraseña del sistema a bloquear: Sgp. + cedula del usuario
        var SISTEMA_PWD = 'Sgp.<?= addslashes($cedula ?? '') ?>';

        // ── Confirmación de salida sin completar el cambio de contraseña ──
        function confirmarSalirReset(e) {
            e.preventDefault();
            const destino = e.currentTarget.href;
            Swal.fire({
                icon: 'warning',
                title: '¿Salir sin cambiar tu contraseña?',
                html: 'Ya verificaste tu identidad, pero <strong>tu contraseña NO se modificará</strong> si sales ahora.<br><br>' +
                      'Para volver a intentarlo más tarde deberás repetir el proceso completo (correo + preguntas de seguridad).',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Continuar aquí',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#2563eb',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = destino;
                }
            });
            return false;
        }

        function togglePassword(inputId, icon) {
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

        // ── Fortaleza de contraseña ──
        const levels = [
            { label: '',            color: '#e2e8f0', pct: 0   },
            { label: 'Muy débil',   color: '#ef4444', pct: 20  },
            { label: 'Débil',       color: '#f97316', pct: 40  },
            { label: 'Regular',     color: '#eab308', pct: 60  },
            { label: 'Fuerte',      color: '#22c55e', pct: 80  },
            { label: 'Muy fuerte',  color: '#15803d', pct: 100 },
        ];

        function evaluarFortaleza(pwd) {
            let score = 0;
            if (pwd.length >= 8)  score++;
            if (pwd.length >= 12) score++;
            if (/[A-Z]/.test(pwd)) score++;
            if (/[a-z]/.test(pwd)) score++;
            if (/[0-9]/.test(pwd)) score++;
            if (/[^A-Za-z0-9]/.test(pwd)) score++;
            return Math.min(score, 5);
        }

        function setRule(id, ok) {
            const el = document.getElementById(id);
            el.classList.toggle('ok', ok);
            el.classList.toggle('fail', !ok);
        }

        document.getElementById('new_password').addEventListener('input', function() {
            const pwd = this.value;

            // Actualizar reglas
            setRule('rule-len',   pwd.length >= 8);
            setRule('rule-upper', /[A-Z]/.test(pwd));
            setRule('rule-lower', /[a-z]/.test(pwd));
            setRule('rule-num',   /[0-9]/.test(pwd));

            // Actualizar barra
            const score = pwd.length === 0 ? 0 : evaluarFortaleza(pwd);
            const lv = levels[score];
            const fill = document.getElementById('pwdFill');
            const label = document.getElementById('pwdLabel');
            fill.style.width = lv.pct + '%';
            fill.style.background = lv.color;
            label.textContent = lv.label;
            label.style.color = lv.color;
        });

        // ── Validación al enviar ──
        document.getElementById('formResetPwd').addEventListener('submit', function(e) {
            e.preventDefault();

            const pwd  = document.getElementById('new_password').value;
            const conf = document.getElementById('confirm_password').value;

            // Reglas básicas
            if (pwd.length < 8) {
                Swal.fire({ icon: 'warning', title: 'Contraseña muy corta', text: 'Debe tener al menos 8 caracteres.' });
                return;
            }
            if (!/[A-Z]/.test(pwd) || !/[a-z]/.test(pwd) || !/[0-9]/.test(pwd)) {
                Swal.fire({ icon: 'warning', title: 'Contraseña insegura', text: 'Debe incluir mayúsculas, minúsculas y al menos un número.' });
                return;
            }

            // Bloquear contraseña temporal del sistema
            if (SISTEMA_PWD.length > 4 && pwd === SISTEMA_PWD) {
                Swal.fire({
                    icon: 'error',
                    title: 'Contraseña no permitida',
                    text: 'No puedes usar tu contraseña temporal como nueva contraseña. Elige una contraseña personalizada.',
                });
                return;
            }

            // Coincidencia
            if (pwd !== conf) {
                Swal.fire({ icon: 'error', title: 'Las contraseñas no coinciden', text: 'Verifica que ambos campos sean iguales.' });
                return;
            }

            // Todo OK — enviar
            this.submit();
        });
    </script>
</body>
</html>
