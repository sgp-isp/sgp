<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia — SGP</title>
    <meta name="referrer" content="no-referrer">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png">

    <!-- ===== MISMOS ASSETS QUE LOGIN ===== -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css?v=<?= filemtime(APPROOT.'/../public/css/swal-bento-navy.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">

    <script>const URLROOT = <?php echo json_encode(URLROOT, JSON_UNESCAPED_SLASHES) ?>;</script> <!-- [FIX-C3] -->

    <style>
    /* ===== KIOSCO: OVERRIDE SOBRE EL SISTEMA DE LOGIN ===== */

    /* Fondo con overlay más oscuro para el kiosco */
    .auth-wrapper::before {
        background: rgba(0, 0, 0, 0.55) !important;
    }

    /* Tarjeta más ancha para el kiosco */
    .auth-card {
        max-width: 440px !important;
        padding: 40px 44px !important;
    }

    /* ===== RELOJ DIGITAL ===== */
    .kiosco-clock {
        text-align: center;
        margin-bottom: 28px;
    }
    .clock-time {
        font-size: clamp(2rem, 10vw, 4rem);
        font-weight: 800;
        letter-spacing: clamp(1px, 0.5vw, 4px);
        line-height: 1;
        font-variant-numeric: tabular-nums;
        background: linear-gradient(135deg, #162660, #2563eb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        word-break: keep-all;
        white-space: nowrap;
    }
    .clock-ampm {
        font-size: 0.35em;
        font-weight: 700;
        letter-spacing: 1px;
        background: linear-gradient(135deg, #64748b, #94a3b8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-left: 4px;
        vertical-align: super;
    }
    .clock-date {
        font-size: clamp(0.75rem, 2.5vw, 0.95rem);
        color: #64748b;
        margin-top: 6px;
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    .clock-separator {
        background: linear-gradient(135deg, #162660, #2563eb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: blink 1s step-end infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    @media (max-width: 480px) {
        .auth-card { padding: 28px 20px !important; }
        .kiosco-clock { margin-bottom: 18px; }
    }

    /* ===== BOTÓN VERDE KIOSCO ===== */
    .btn-kiosco {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #059669, #10b981);
        color: white;
        border: none;
        border-radius: 14px;
        font-size: 1.05rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 20px rgba(5, 150, 105, 0.35);
        letter-spacing: 0.3px;
        margin-bottom: 16px;
    }
    .btn-kiosco:hover  { transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 28px rgba(5,150,105,0.5); }
    .btn-kiosco:active { transform: translateY(0) scale(0.98); }
    .btn-kiosco:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

    /* ===== SPINNER ===== */
    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { animation: spin 0.8s linear infinite; display: inline-block; }

    /* ===== VOLVER - OUTLINE AZUL INSTITUCIONAL ===== */
    .kiosco-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #162660;
        text-decoration: none;
        border: 2px solid #162660;
        border-radius: 50px;
        background: transparent;
        cursor: pointer;
        transition: all 0.25s ease;
        letter-spacing: 0.2px;
    }
    .kiosco-back:hover {
        background: rgba(22, 38, 96, 0.07);
        border-color: #2563eb;
        color: #2563eb;
        transform: translateY(-1px);
        text-decoration: none;
    }

    /* ===== BADGE DE ESTADO ACTIVO ===== */
    .kiosco-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        background: rgba(5, 150, 105, 0.1);
        border: 1px solid rgba(5, 150, 105, 0.2);
        border-radius: 50px;
        margin-bottom: 24px;
    }
    .status-dot {
        width: 8px; height: 8px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse-dot 2s ease-in-out infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50%       { transform: scale(1.4); opacity: 0.7; }
    }
    .kiosco-status span { font-size: 0.8rem; font-weight: 600; color: #059669; }

    /* ===== PANEL DE RESULTADO ===== */
    #panelResultado {
        display: none;
        text-align: center;
        animation: fadeInUp 0.4s ease;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .resultado-icon {
        width: 80px; height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 2.5rem;
    }
    .resultado-ok      { background: rgba(5,150,105,0.12);  border: 3px solid #10b981; color: #059669; }
    .resultado-retardo { background: rgba(234,88,12,0.12);  border: 3px solid #f97316; color: #ea580c; }
    .resultado-warn    { background: rgba(245,158,11,0.12); border: 3px solid #f59e0b; color: #d97706; }
    .resultado-err     { background: rgba(239,68,68,0.12);  border: 3px solid #ef4444; color: #dc2626; }
    .resultado-nombre { font-size: 1.3rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
    .resultado-depto  { color: #64748b; font-size: 0.88rem; margin-bottom: 12px; }
    .resultado-hora   {
        display: inline-block;
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 24px;
        font-size: 1.8rem;
        font-weight: 800;
        letter-spacing: 3px;
        color: #162660;
        margin-bottom: 20px;
    }

    /* ===== PANEL DE RECUPERACIÓN ===== */
    #panelRecuperacion {
        display: none;
        animation: fadeInRight 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    @keyframes fadeInRight {
        from { opacity: 0; transform: translateX(20px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    .recuperacion-header {
        text-align: center;
        margin-bottom: 24px;
    }
    .recuperacion-icon {
        width: 72px; height: 72px;
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.2rem;
        margin: 0 auto 16px;
    }
    .recuperacion-title {
        font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 8px;
    }
    .recuperacion-desc {
        color: #64748b; font-size: 0.95rem; line-height: 1.4;
    }
    .btn-outline-cancel {
        width: 100%;
        padding: 14px;
        background: transparent;
        color: #64748b;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 12px;
    }
    .btn-outline-cancel:hover {
        background: #f8fafc; color: #1e293b; border-color: #cbd5e1;
    }
    /* ===== BANNER FERIADO ===== */
    #bannerFeriado {
        display: none;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 20px;
        text-align: center;
        animation: fadeInUp 0.4s ease;
    }
    #bannerFeriado .bf-icon { font-size: 2rem; margin-bottom: 6px; }
    #bannerFeriado .bf-title { font-size: 1rem; font-weight: 800; margin-bottom: 2px; }
    #bannerFeriado .bf-desc { font-size: 0.82rem; font-weight: 500; opacity: 0.92; line-height: 1.4; }
    /* [SGP-FIX-KIOSCO-FERIADO-LABORABLE] Variante informativa (no bloquea el registro) */
    #bannerFeriado.laborable { background: linear-gradient(135deg, #22c55e, #16a34a); }
    #panelFormulario.bloqueado { opacity: 0.45; pointer-events: none; filter: grayscale(0.4); }
    </style>
</head>
<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>

    <!-- ===== TARJETA KIOSCO (misma clase que login) ===== -->
    <div class="auth-card">

        <!-- RELOJ DIGITAL -->
        <div class="kiosco-clock">
            <div class="clock-time">
                <span id="clockHH">--</span><span class="clock-separator">:</span><span id="clockMM">--</span><span class="clock-separator">:</span><span id="clockSS">--</span><span class="clock-ampm" id="clockAMPM"></span>
            </div>
            <div class="clock-date" id="clockDate">Cargando fecha...</div>
        </div>

        <!-- BADGE SISTEMA ACTIVO -->
        <div class="kiosco-status">
            <div class="status-dot"></div>
            <span>Registro de Asistencia Activo</span>
        </div>

        <!-- TÍTULO -->
        <div class="auth-header" style="margin-bottom: 24px;">
            <h1 class="auth-title" style="font-size: 1.4rem;">Registro de Asistencia</h1>
            <p class="auth-subtitle">Ingresa tu cédula y PIN para marcar tu entrada</p>
        </div>

        <!-- ── BANNER FERIADO (oculto por defecto, visible si hoy es feriado) ── -->
        <div id="bannerFeriado">
            <div class="bf-icon" id="bfIcon">🎉</div>
            <div class="bf-title" id="bfNombre">Día Feriado</div>
            <div class="bf-desc" id="bfDesc">Este es un día no laborable. La asistencia se registrará automáticamente como Justificado.</div>
        </div>

        <!-- ── PANEL FORMULARIO ── -->
        <div id="panelFormulario">
            <form id="kioscoForm" onsubmit="marcarAsistencia(event)">

                <!-- Cédula -->
                <div class="form-group">
                    <input type="text"
                           id="cedula" name="cedula"
                           class="input-modern"
                           placeholder=" "
                           maxlength="10"
                           inputmode="numeric"
                           autocomplete="off"
                           required>
                    <label for="cedula" class="label-floating">
                        <i class="ti ti-id-badge" style="margin-right:8px;font-size:18px;"></i>Número de Cédula
                    </label>
                </div>

                <!-- PIN -->
                <div class="form-group has-toggle">
                    <input type="password"
                           id="pin" name="pin_asistencia"
                           class="input-modern"
                           placeholder=" "
                           maxlength="4"
                           pattern="[0-9]{4}"
                           inputmode="numeric"
                           autocomplete="off"
                           required
                           style="letter-spacing:8px;font-size:1.4rem;text-align:center;">
                    <label for="pin" class="label-floating">
                        <i class="ti ti-lock" style="margin-right:8px;font-size:18px;"></i>PIN de Asistencia
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePinVis(this)"></i>
                </div>

                <!-- BOTÓN REGISTRAR -->
                <button type="submit" class="btn-kiosco" id="btnMarcar">
                    <i class="ti ti-clock-check" style="font-size:1.2rem;"></i>
                    <span id="btnText">Marcar Asistencia</span>
                </button>

                <!-- LINK OLVIDÓ PIN -->
                <div style="text-align: center; margin-top: 20px;">
                    <a href="#" onclick="solicitarResetPin(event)" class="auth-link" style="color:#162660;font-size:0.9rem;font-weight:600;text-decoration:none;">
                        ¿Olvidaste tu PIN? Solicitar reseteo
                    </a>
                </div>
            </form>
        </div>

        <!-- ── PANEL DE RESULTADO (oculto al inicio) ── -->
        <div id="panelResultado">
            <div id="resultIcon" class="resultado-icon resultado-ok">
                <i id="resultIconInner" class="ti ti-check"></i>
            </div>
            <div id="resultNombre" class="resultado-nombre"></div>
            <div id="resultDepto"  class="resultado-depto"></div>
            <div id="resultHora"   class="resultado-hora"></div>
            <p id="resultMsg" style="color:#64748b;font-size:0.88rem;margin-bottom:20px;"></p>
            <button class="kiosco-back" onclick="volverFormulario()">
                <i class="ti ti-arrow-left"></i> Volver
            </button>
        </div>

        <!-- ── PANEL DE RECUPERACIÓN (oculto al inicio) ── -->
        <div id="panelRecuperacion">
            <div class="recuperacion-header">
                <div class="recuperacion-icon"><i class="ti ti-lock-question"></i></div>
                <div class="recuperacion-title">¿Olvidaste tu PIN?</div>
                <div class="recuperacion-desc">Ingresa tu cédula para notificar a tu tutor y administrador. Te ayudaremos a restablecerlo.</div>
            </div>
            
            <form id="formRecuperacion" onsubmit="enviarSolicitudRecuperacion(event)">
                <div class="form-group">
                    <input type="text" id="cedulaRecuperacion" class="input-modern" placeholder=" " maxlength="10" inputmode="numeric" autocomplete="off" required>
                    <label for="cedulaRecuperacion" class="label-floating">
                        <i class="ti ti-id-badge" style="margin-right:8px;font-size:18px;"></i>Verifica tu Cédula
                    </label>
                </div>
                
                <button type="submit" class="btn-primary" style="width:100%; padding:14px; font-size:1.05rem;" id="btnEnviarRecuperacion">
                    <i class="ti ti-send" style="margin-right:8px;"></i> <span id="textBtnRecuperacion">Enviar Solicitud</span>
                </button>
                <button type="button" class="btn-outline-cancel" onclick="cancelarRecuperacion()">
                    Cancelar y Volver
                </button>
            </form>
        </div>

        <!-- ── FOOTER VOLVER AL ACCESO ── -->
        <div style="text-align: center; margin-top: 32px; padding-top: 24px; border-top: 1px dashed #e2e8f0;">
            <a href="<?= URLROOT ?>/auth/logout" class="auth-link" style="color: #64748b; font-size: 0.9rem; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: color 0.2s;">
                <i class="ti ti-arrow-left"></i> Volver al Acceso Administrativo
            </a>
        </div>

    </div><!-- /auth-card -->

    <!-- ===== SCRIPTS ===== -->
    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    <script src="<?= URLROOT ?>/js/notyf.min.js"></script>
    <script src="<?= URLROOT ?>/js/sweetalert2.min.js"></script>

    <script>
    // ── Reloj digital ─────────────────────────────────────────────
    const DIAS  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    function actualizarReloj() {
        var now  = new Date();
        var h24  = now.getHours();
        var mm   = String(now.getMinutes()).padStart(2, '0');
        var ss   = String(now.getSeconds()).padStart(2, '0');
        var ampm = h24 >= 12 ? 'PM' : 'AM';
        var h12  = h24 % 12 || 12;
        document.getElementById('clockHH').textContent   = String(h12).padStart(2, '0');
        document.getElementById('clockMM').textContent   = mm;
        document.getElementById('clockSS').textContent   = ss;
        document.getElementById('clockAMPM').textContent = ampm;
        document.getElementById('clockDate').textContent =
            DIAS[now.getDay()] + ', ' + now.getDate() + ' de ' + MESES[now.getMonth()] + ' de ' + now.getFullYear();
    }
    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    // ── Solo números ───────────────────────────────────────────────
    document.getElementById('cedula').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
    document.getElementById('cedulaRecuperacion').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
    document.getElementById('pin').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });

    // ── Toggle ojito PIN ───────────────────────────────────────────
    function togglePinVis(icon) {
        var pinEl = document.getElementById('pin');
        if (pinEl.type === 'password') {
            pinEl.type = 'text';
            icon.classList.replace('ti-eye', 'ti-eye-off');
        } else {
            pinEl.type = 'password';
            icon.classList.replace('ti-eye-off', 'ti-eye');
        }
    }

    // ── Verificar feriado al cargar la página ─────────────────────
    // [SGP-FIX-KIOSCO-FERIADO-LABORABLE] Si el feriado es laborable
    // (es_laborable=1), solo se informa — el formulario NO se bloquea
    // y la asistencia se registra con normalidad.
    (async function verificarFeriado() {
        try {
            var r = await fetch(URLROOT + '/kiosco/esFeriado');
            var d = await r.json();
            if (d.is_feriado) {
                document.getElementById('bfNombre').textContent = d.nombre;
                document.getElementById('bannerFeriado').style.display = 'block';

                if (d.es_laborable) {
                    document.getElementById('bannerFeriado').classList.add('laborable');
                    document.getElementById('bfIcon').textContent = '📅';
                    document.getElementById('bfDesc').textContent = 'Es un feriado laborable — la asistencia se registra con normalidad.';
                    document.querySelector('.kiosco-status span').textContent = '📅 Hoy es día feriado (laborable)';
                    document.querySelector('.status-dot').style.background = '#22c55e';
                    // El panel NO se bloquea: se permite marcar asistencia normalmente.
                } else {
                    document.getElementById('panelFormulario').classList.add('bloqueado');
                    // Cambiar badge de estado a modo feriado
                    document.querySelector('.kiosco-status span').textContent = '🎉 Hoy es día feriado — No laborable';
                    document.querySelector('.status-dot').style.background = '#f59e0b';
                }
            }
        } catch(e) { /* silencioso */ }
    })();

    // ── Marcar Asistencia (AJAX real) ─────────────────────────────
    async function marcarAsistencia(e) {
        e.preventDefault();

        // Validación frontend: bloquear fines de semana
        var dow = new Date().getDay(); // 0=Dom, 6=Sáb
        if (dow === 0 || dow === 6) {
            var nombreDia = dow === 6 ? 'sábado' : 'domingo';
            mostrarResultado({ success: false, message: 'Hoy es ' + nombreDia + '. No se registra asistencia los fines de semana — solo días hábiles de lunes a viernes.' });
            return;
        }

        var btn  = document.getElementById('btnMarcar');
        var txt  = document.getElementById('btnText');
        btn.disabled = true;
        txt.textContent = 'Verificando...';
        btn.querySelector('i').className = 'ti ti-loader spinner';

        try {
            var resp = await fetch(URLROOT + '/kiosco/marcar', {
                method: 'POST',
                body: new FormData(document.getElementById('kioscoForm'))
            });
            var json = await resp.json();

            if (json.tipo === 'finalizado' || json.tipo === 'retirado' || json.tipo === 'inactivo') {
                mostrarAlertaPasanteInactivo(json);
            } else {
                mostrarResultado(json);
            }
        } catch (err) {
            mostrarResultado({ success: false, message: 'Error de conexión. Intenta de nuevo.' });
        }

        btn.disabled = false;
        btn.querySelector('i').className = 'ti ti-clock-check';
        txt.textContent = 'Marcar Asistencia';
    }

    function mostrarAlertaPasanteInactivo(json) {
        var esFinalizado = json.tipo === 'finalizado';
        var nombre = json.nombre || 'Pasante';
        Swal.fire({
            icon: esFinalizado ? 'success' : 'warning',
            title: esFinalizado ? '¡Pasantía Completada!' : 'Acceso No Disponible',
            html: '<div style="text-align:center;">'
                + '<div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:16px;'
                + 'background:' + (esFinalizado ? 'linear-gradient(135deg,#dcfce7,#bbf7d0)' : 'linear-gradient(135deg,#fef3c7,#fde68a)')
                + ';margin:0 auto 16px;">'
                + '<span style="font-size:1.8rem;">' + (esFinalizado ? '🎓' : '⚠️') + '</span>'
                + '</div>'
                + '<div style="font-size:1.15rem;font-weight:800;color:#0f172a;margin-bottom:4px;">' + nombre + '</div>'
                + '<div style="display:inline-block;padding:4px 14px;border-radius:20px;font-size:.75rem;font-weight:700;margin-bottom:16px;'
                + 'background:' + (esFinalizado ? '#dcfce7;color:#15803d' : '#fef3c7;color:#b45309') + ';">'
                + (esFinalizado ? 'Pasantía Finalizada' : (json.tipo === 'retirado' ? 'Retirado' : 'Cuenta Inactiva'))
                + '</div>'
                + '<p style="color:#475569;font-size:.9rem;line-height:1.6;margin:0;">' + (json.message || '') + '</p>'
                + '</div>',
            confirmButtonText: 'Entendido',
            confirmButtonColor: esFinalizado ? '#15803d' : '#b45309',
            allowOutsideClick: true,
            customClass: { popup: 'swal-kiosco-inactivo' }
        }).then(function() {
            document.getElementById('kioscoForm').reset();
        });
    }

    // ── Mostrar panel de resultado ─────────────────────────────────
    function mostrarResultado(json) {
        var tipo;
        if (json.success) {
            tipo = json.retardo ? 'retardo' : 'ok';
        } else if (json.is_feriado) {
            tipo = 'feriado';
        } else if (json.tipo === 'finalizado') {
            tipo = 'finalizado';
        } else if (json.tipo === 'retirado' || json.tipo === 'inactivo') {
            tipo = 'inactivo';
        } else {
            tipo = json.ya_registro ? 'warn' : 'err';
        }
        var iconMap  = {
            ok:         'ti-check',
            retardo:    'ti-clock-exclamation',
            warn:       'ti-clock',
            feriado:    'ti-calendar-event',
            finalizado: 'ti-certificate',
            inactivo:   'ti-user-off',
            err:        'ti-x'
        };
        var classMap = {
            ok:         'resultado-ok',
            retardo:    'resultado-retardo',
            warn:       'resultado-warn',
            feriado:    'resultado-warn',
            finalizado: 'resultado-ok',
            inactivo:   'resultado-warn',
            err:        'resultado-err'
        };

        var iconEl = document.getElementById('resultIcon');
        iconEl.className = 'resultado-icon ' + classMap[tipo];
        document.getElementById('resultIconInner').className = 'ti ' + iconMap[tipo];

        var horaActual = new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });

        if (json.success && json.pasante) {
            document.getElementById('resultNombre').textContent =
                json.pasante.apellidos + ', ' + json.pasante.nombres;
            document.getElementById('resultDepto').textContent =
                'Departamento: ' + json.pasante.departamento;
            document.getElementById('resultHora').textContent = json.hora;
        } else if (tipo === 'finalizado') {
            document.getElementById('resultNombre').textContent = json.nombre || 'Pasante';
            document.getElementById('resultDepto').textContent = 'Pasantía Completada';
            document.getElementById('resultHora').textContent = horaActual;
        } else if (tipo === 'inactivo') {
            document.getElementById('resultNombre').textContent = json.nombre || 'Pasante';
            document.getElementById('resultDepto').textContent = 'Cuenta Inactiva';
            document.getElementById('resultHora').textContent = horaActual;
        } else {
            document.getElementById('resultNombre').textContent =
                json.ya_registro ? '¡Ya marcaste hoy!' : 'Acceso denegado';
            document.getElementById('resultDepto').textContent = '';
            document.getElementById('resultHora').textContent = horaActual;
        }
        document.getElementById('resultMsg').textContent = json.message ?? '';

        document.getElementById('panelFormulario').style.display = 'none';
        document.getElementById('panelResultado').style.display  = 'block';

        if (json.success) setTimeout(volverFormulario, 8000);
    }

    // ── Volver al formulario ───────────────────────────────────────
    function volverFormulario() {
        document.getElementById('kioscoForm').reset();
        document.getElementById('panelResultado').style.display  = 'none';
        document.getElementById('panelFormulario').style.display = 'block';
        document.getElementById('cedula').focus();
    }

    // ── Mostrar Panel de Recuperación ─────────────────────────────
    function solicitarResetPin(e) {
        e.preventDefault();
        // Ocultar formulario de asistencia
        document.getElementById('panelFormulario').style.display = 'none';
        
        // Copiar cedula si ya la ingresó
        let cedulaActual = document.getElementById('cedula').value.trim();
        document.getElementById('cedulaRecuperacion').value = cedulaActual;
        
        // Mostrar panel recuperación
        document.getElementById('panelRecuperacion').style.display = 'block';
        document.getElementById('cedulaRecuperacion').focus();
    }

    // ── Ocultar Panel de Recuperación ─────────────────────────────
    function cancelarRecuperacion() {
        document.getElementById('panelRecuperacion').style.display = 'none';
        document.getElementById('panelFormulario').style.display = 'block';
    }

    // ── Enviar Solicitud de Recuperación ──────────────────────────
    async function enviarSolicitudRecuperacion(e) {
        e.preventDefault();
        
        let cedulaInput = document.getElementById('cedulaRecuperacion');
        let cedula = cedulaInput.value.trim();
        if(!cedula) {
            NotificationService.error('Ingresa tu cédula');
            return;
        }

        let btn = document.getElementById('btnEnviarRecuperacion');
        let txt = document.getElementById('textBtnRecuperacion');
        let icon = btn.querySelector('i');
        
        // Estado de carga
        btn.disabled = true;
        txt.textContent = 'Enviando...';
        icon.className = 'ti ti-loader spinner';
        
        try {
            let formData = new FormData();
            formData.append('cedula', cedula);

            let resp = await fetch(URLROOT + '/kiosco/solicitarResetPin', {
                method: 'POST',
                body: formData
            });
            let json = await resp.json();

            // Si es pasante finalizado/inactivo, mostrar SweetAlert
            if (json.tipo === 'finalizado' || json.tipo === 'inactivo') {
                document.getElementById('panelRecuperacion').style.display = 'none';
                document.getElementById('panelFormulario').style.display = 'block';
                document.getElementById('cedulaRecuperacion').value = '';
                btn.disabled = false;
                txt.textContent = 'Enviar Solicitud';
                icon.className = 'ti ti-send';
                mostrarAlertaPasanteInactivo(json);
                return;
            }

            // Ocultar modal de recuperación
            document.getElementById('panelRecuperacion').style.display = 'none';
            document.getElementById('cedulaRecuperacion').value = '';

            // Mostrar resultado en el panel de resultados
            document.getElementById('panelFormulario').style.display = 'none';
            
            let iconEl = document.getElementById('resultIcon');
            let iconInner = document.getElementById('resultIconInner');
            if(json.success) {
                iconEl.className = 'resultado-icon resultado-ok';
                iconInner.className = 'ti ti-mail-check';
                document.getElementById('resultNombre').textContent = '¡Solicitud Enviada!';
                document.getElementById('resultDepto').textContent = '';
                document.getElementById('resultHora').textContent = '';
                document.getElementById('resultHora').style.display = 'none'; // Ocultar hora
                document.getElementById('resultMsg').textContent = json.message;
            } else {
                iconEl.className = 'resultado-icon resultado-err';
                iconInner.className = 'ti ti-x';
                document.getElementById('resultNombre').textContent = 'Error';
                document.getElementById('resultDepto').textContent = '';
                document.getElementById('resultHora').style.display = 'none'; // Ocultar hora
                document.getElementById('resultMsg').textContent = json.message;
            }
            
            document.getElementById('panelResultado').style.display = 'block';
            setTimeout(() => {
                document.getElementById('resultHora').style.display = 'inline-block'; // restaurar display
                volverFormulario();
            }, 6000);
            
        } catch (err) {
            console.error(err);
            NotificationService.error('Error de conexión');
        }
        
        // Restaurar botón
        btn.disabled = false;
        txt.textContent = 'Enviar Solicitud';
        icon.className = 'ti ti-send';
    }
    </script>
</body>
</html>
