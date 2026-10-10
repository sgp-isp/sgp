<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Contraseña Requerido - SGP</title>
    <link rel="stylesheet" href="<?= URLROOT ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">
    
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
</head>
<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>
    
    <!-- Tarjeta Premium Flotante -->
    <div class="auth-card">
        <div class="auth-header">
            <div style="width: 80px; height: 80px; background: #FEF3C7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="ti ti-lock" style="font-size: 40px; color: #F59E0B;"></i>
            </div>
            <h1 class="auth-title">Cambio de Contraseña Obligatorio</h1>
            <p class="auth-subtitle">Por seguridad, debes actualizar tu contraseña temporal antes de continuar</p>
        </div>

        <!-- Alerta informativa -->
        <div style="background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <p style="color: #92400E; font-size: 14px; margin: 0;">
                <i class="ti ti-alert-triangle" style="margin-right: 8px;"></i>
                <strong>Atención:</strong> Detectamos que estás usando una contraseña temporal. Por tu seguridad, debes cambiarla ahora.
            </p>
        </div>

        <?php $cedula = $data['cedula'] ?? ''; ?>
        <form action="<?= URLROOT ?>/auth/cambiar-password" method="POST" id="passwordChangeForm">

            <!-- Nueva Contraseña -->
            <div class="form-group has-toggle" style="margin-bottom:8px;">
                <input type="password" name="new_password" id="new_password" class="input-modern" placeholder=" " required
                       oninput="onNuevoPasswordInput(this)">
                <label for="new_password" class="label-floating">
                    <i class="ti ti-lock" style="margin-right: 8px; font-size: 18px;"></i>Nueva Contraseña
                </label>
                <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('new_password', this)"></i>
            </div>

            <!-- Alerta contraseña temporal -->
            <div id="tempPassAlert" style="display:none;background:#fef2f2;border:1px solid #fecaca;
                border-radius:8px;padding:8px 12px;margin-bottom:8px;font-size:.78rem;
                color:#dc2626;display:none;align-items:center;gap:6px;">
                <i class="ti ti-alert-triangle"></i>
                No puedes usar tu contraseña temporal como nueva contraseña.
            </div>

            <!-- Barra de fortaleza mejorada -->
            <div style="margin-bottom:14px;">
                <div style="display:flex;gap:4px;margin-bottom:5px;" id="strengthBars">
                    <div class="sbar" style="height:4px;flex:1;border-radius:10px;background:#e2e8f0;transition:background .3s;"></div>
                    <div class="sbar" style="height:4px;flex:1;border-radius:10px;background:#e2e8f0;transition:background .3s;"></div>
                    <div class="sbar" style="height:4px;flex:1;border-radius:10px;background:#e2e8f0;transition:background .3s;"></div>
                    <div class="sbar" style="height:4px;flex:1;border-radius:10px;background:#e2e8f0;transition:background .3s;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div id="strengthLabel" style="font-size:.72rem;font-weight:700;color:#94a3b8;transition:color .3s;">Ingresa una contraseña</div>
                    <div style="font-size:.68rem;color:#94a3b8;">Mín. 8 car. · May. · Min. · Número · Símbolo</div>
                </div>
                <!-- Requisitos visuales -->
                <div id="reqList" style="margin-top:8px;display:grid;grid-template-columns:1fr 1fr;gap:3px;">
                    <div class="req" data-req="len"  style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;"><i class="ti ti-circle" style="font-size:.65rem;"></i> 8+ caracteres</div>
                    <div class="req" data-req="upper" style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;"><i class="ti ti-circle" style="font-size:.65rem;"></i> Mayúscula</div>
                    <div class="req" data-req="lower" style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;"><i class="ti ti-circle" style="font-size:.65rem;"></i> Minúscula</div>
                    <div class="req" data-req="num"   style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;"><i class="ti ti-circle" style="font-size:.65rem;"></i> Número</div>
                    <div class="req" data-req="sym"   style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;"><i class="ti ti-circle" style="font-size:.65rem;"></i> Símbolo (!@#$…)</div>
                </div>
            </div>

            <!-- Confirmar Nueva Contraseña -->
            <div class="form-group has-toggle" style="margin-top:4px;margin-bottom:8px;">
                <input type="password" name="confirm_password" id="confirm_password" class="input-modern" placeholder=" " required
                       oninput="onConfirmInput(this)">
                <label for="confirm_password" class="label-floating">
                    <i class="ti ti-lock-check" style="margin-right: 8px; font-size: 18px;"></i>Repetir Contraseña
                </label>
                <i class="ti ti-eye password-toggle" onclick="togglePasswordVisibility('confirm_password', this)"></i>
            </div>
            <div id="matchHint" style="display:none;font-size:.75rem;color:#dc2626;margin-bottom:12px;
                 display:flex;align-items:center;gap:5px;">
                <i class="ti ti-x"></i> Las contraseñas no coinciden
            </div>
            <div id="matchOk" style="display:none;font-size:.75rem;color:#059669;margin-bottom:12px;
                 align-items:center;gap:5px;">
                <i class="ti ti-check"></i> Las contraseñas coinciden
            </div>

            <!-- Botón de Cambiar -->
            <button type="submit" class="btn-primary" style="margin-top: 16px;" id="submitBtn" disabled>
                Cambiar Contraseña
                <i class="ti ti-arrow-right" style="font-size: 20px;"></i>
            </button>
        </form>

        <!-- Logout option -->
        <div style="text-align: center; margin-top: 20px;">
            <a href="<?= URLROOT ?>/auth/logout" class="auth-link" style="font-size: 14px; display: inline-flex; align-items: center; gap: 6px;" onclick="event.preventDefault(); confirmLogout();">
                <i class="ti ti-logout"></i> Cerrar Sesión
            </a>
        </div>
    </div>

    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    
    <script src="<?= URLROOT ?>/js/sweetalert2.min.js"></script>
    <script src="<?= URLROOT ?>/js/validation.js"></script>
    
    <script>
        const _CEDULA = '<?= htmlspecialchars($cedula ?? '', ENT_QUOTES) ?>';
        const _TEMP_PASS = 'Sgp.' + _CEDULA;

        // ── Requisitos ──────────────────────────────────────────────────
        const REQS = {
            len:   v => v.length >= 8,
            upper: v => /[A-Z]/.test(v),
            lower: v => /[a-z]/.test(v),
            num:   v => /[0-9]/.test(v),
            sym:   v => /[!@#$%^&*()_\-+=.,?":{}\[\]<>]/.test(v),
        };
        const STRENGTHS = [
            { min:0, label:'Muy débil',   colors:['#ef4444','#e2e8f0','#e2e8f0','#e2e8f0'] },
            { min:1, label:'Débil',       colors:['#f97316','#f97316','#e2e8f0','#e2e8f0'] },
            { min:2, label:'Regular',     colors:['#f59e0b','#f59e0b','#f59e0b','#e2e8f0'] },
            { min:3, label:'Buena',       colors:['#10b981','#10b981','#10b981','#10b981'] },
            { min:5, label:'Muy fuerte',  colors:['#059669','#059669','#059669','#059669'] },
        ];

        function onNuevoPasswordInput(inp) {
            const val = inp.value;
            const bars  = document.querySelectorAll('.sbar');
            const label = document.getElementById('strengthLabel');
            const submitBtn = document.getElementById('submitBtn');

            // Verificar si es la contraseña temporal
            const esTemp = val.length > 0 && val === _TEMP_PASS;
            const alert = document.getElementById('tempPassAlert');
            alert.style.display = esTemp ? 'flex' : 'none';

            // Requisitos visuales
            let passed = 0;
            Object.entries(REQS).forEach(([key, fn]) => {
                const ok = fn(val);
                if (ok) passed++;
                const el = document.querySelector(`.req[data-req="${key}"]`);
                if (el) {
                    el.style.color = ok ? '#059669' : '#94a3b8';
                    el.querySelector('i').className = ok ? 'ti ti-check-circle' : 'ti ti-circle';
                    el.querySelector('i').style.color = ok ? '#059669' : '#e2e8f0';
                }
            });

            // Barra de fortaleza
            const lvl = val.length === 0 ? -1 :
                        passed >= 5 ? 3 : passed >= 3 ? 2 : passed >= 2 ? 1 : 0;
            if (lvl < 0) {
                bars.forEach(b => b.style.background = '#e2e8f0');
                label.textContent = 'Ingresa una contraseña';
                label.style.color = '#94a3b8';
            } else {
                const s = STRENGTHS[Math.min(lvl, STRENGTHS.length-1)];
                bars.forEach((b,i) => b.style.background = s.colors[i]);
                label.textContent = s.label;
                label.style.color = s.colors[0];
            }

            // Habilitar botón solo si todos los requisitos pasan y no es temporal
            const allOk = passed === Object.keys(REQS).length && !esTemp;
            checkSubmit(allOk);
            onConfirmInput(document.getElementById('confirm_password'));
        }

        function onConfirmInput(inp) {
            const newP = document.getElementById('new_password').value;
            const matchHint = document.getElementById('matchHint');
            const matchOk   = document.getElementById('matchOk');
            if (!inp.value) {
                matchHint.style.display = 'none'; matchOk.style.display = 'none'; return;
            }
            const ok = inp.value === newP;
            matchHint.style.display = ok ? 'none' : 'flex';
            matchOk.style.display   = ok ? 'flex' : 'none';
            checkSubmit(ok);
        }

        function checkSubmit(condicion) {
            const newP    = document.getElementById('new_password').value;
            const confP   = document.getElementById('confirm_password').value;
            const allReqs = Object.values(REQS).every(fn => fn(newP));
            const match   = newP === confP && confP.length > 0;
            const noTemp  = newP !== _TEMP_PASS;
            document.getElementById('submitBtn').disabled = !(allReqs && match && noTemp);
        }

        // Form submission
        document.getElementById('passwordChangeForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Validate passwords match
            if (newPassword !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Las contraseñas no coinciden',
                    confirmButtonColor: '#162660'
                });
                return;
            }

            // Validar requisitos estrictos
            const validation = validatePasswordRequirements(newPassword);
            if (!validation.isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Contraseña Débil',
                    text: validation.message,
                    confirmButtonColor: '#162660'
                });
                return;
            }
            
            // Submit form
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-loader animate-spin"></i> Cambiando...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            // [FIX-A3] Adjuntar token CSRF al FormData
            formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content || '');

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Contraseña Cambiada!',
                        text: 'Redirigiendo al sistema...',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.redirect || '<?= URLROOT ?>/dashboard';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#162660'
                    });
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error del Sistema',
                    text: 'Ocurrió un error inesperado',
                    confirmButtonColor: '#162660'
                });
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
        
        // Logout confirmation
        function confirmLogout() {
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: 'Deberás cambiar tu contraseña temporal la próxima vez que inicies sesión',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= URLROOT ?>/auth/logout';
                }
            });
        }
    </script>
</body>
</html>
