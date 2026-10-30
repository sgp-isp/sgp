<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia — SGP</title>
    <meta name="referrer" content="no-referrer">
    
    <!-- PWA & Mobile Meta Tags -->
    <meta name="theme-color" content="#162660">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Kiosco SGP">
    <meta name="application-name" content="Kiosco SGP">
    <link rel="manifest" href="<?= URLROOT ?>/manifest.json?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= URLROOT ?>/img/apple-touch-icon.png?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= URLROOT ?>/img/favicon-32.png?v=<?= @filemtime(APPROOT . '/../public/img/favicon-32.png') ?: 3 ?>">
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png?v=<?= @filemtime(APPROOT . '/../public/img/favicon.png') ?: 3 ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= URLROOT ?>/img/pwa-icon-192.png?v=2">

    <!-- ===== ASSETS DEL SISTEMA ===== -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/notyf.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/swal-bento-navy.css?v=<?= filemtime(APPROOT.'/../public/css/swal-bento-navy.css') ?>">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/style.css?v=<?= filemtime(APPROOT.'/../public/css/style.css') ?>">

    <script>const URLROOT = <?php echo json_encode(URLROOT, JSON_UNESCAPED_SLASHES) ?>;</script>

    <style>
    /* ===== KIOSCO FULLSCREEN & CONTENEDOR ===== */
    .auth-wrapper::before {
        background: rgba(15, 23, 42, 0.65) !important;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .auth-card {
        max-width: 480px !important;
        width: 100% !important;
        padding: 34px 36px !important;
        border-radius: 26px !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25) !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    @media (max-width: 480px) {
        .auth-card {
            padding: 24px 20px !important;
            border-radius: 20px !important;
            margin: 12px;
        }
    }

    /* ===== RELOJ DIGITAL ===== */
    .kiosco-clock {
        text-align: center;
        margin-bottom: 20px;
    }
    .clock-time {
        font-size: clamp(2.2rem, 9vw, 3.8rem);
        font-weight: 900;
        letter-spacing: clamp(1px, 0.5vw, 3px);
        line-height: 1;
        font-variant-numeric: tabular-nums;
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        word-break: keep-all;
        white-space: nowrap;
    }
    .clock-ampm {
        font-size: 0.35em;
        font-weight: 800;
        letter-spacing: 1px;
        color: #64748b;
        -webkit-text-fill-color: #64748b;
        margin-left: 4px;
        vertical-align: super;
    }
    .clock-date {
        font-size: clamp(0.75rem, 2.6vw, 0.92rem);
        color: #64748b;
        margin-top: 6px;
        font-weight: 600;
    }
    .clock-separator {
        background: linear-gradient(135deg, #172554, #2563eb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: blink 1s step-end infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }

    /* ===== BADGE DE ESTADO ===== */
    .kiosco-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 6px 14px;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.25);
        border-radius: 999px;
        margin: 0 auto 18px;
        display: flex;
        width: fit-content;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
        animation: pulseDot 2s infinite;
    }
    @keyframes pulseDot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.6; }
    }
    .kiosco-status span {
        font-size: 0.78rem;
        font-weight: 700;
        color: #059669;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ===== BOTONES PRIMARIOS ===== */
    .btn-kiosco {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: white;
        border: none;
        border-radius: 14px;
        font-size: 1.02rem;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 18px rgba(5, 150, 105, 0.35);
        letter-spacing: 0.3px;
    }
    .btn-kiosco:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(5, 150, 105, 0.45);
    }
    .btn-kiosco:active { transform: translateY(0); }
    .btn-kiosco:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }

    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { animation: spin 0.8s linear infinite; display: inline-block; }

    /* ===== BANNER FERIADO ===== */
    #bannerFeriado {
        display: none;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 18px;
        text-align: center;
        animation: fadeIn 0.3s ease;
    }
    #bannerFeriado.laborable { background: linear-gradient(135deg, #10b981, #059669); }
    #panelCedula.bloqueado { opacity: 0.45; pointer-events: none; filter: grayscale(0.5); }

    /* ===== ANIMACIONES DE TRANSICIÓN ENTRE PASOS ===== */
    .fade-step {
        animation: stepFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    @keyframes stepFadeIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ===== BENTO UI: PERFIL DEL PASANTE ===== */
    .bento-profile-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }
    .bento-profile-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
    }
    .bento-avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, #172554, #2563eb);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        font-weight: 900;
        flex-shrink: 0;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
    }
    .bento-user-info {
        flex: 1;
        min-width: 0;
    }
    .bento-name {
        font-size: 1.12rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bento-cedula {
        font-size: 0.8rem;
        font-weight: 700;
        color: #2563eb;
        background: #eff6ff;
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-block;
    }

    .bento-tags {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
    }
    .bento-tag {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.78rem;
        color: #475569;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bento-tag i { color: #059669; font-size: 0.95rem; flex-shrink: 0; }

    /* Caja de progreso con Anillo SVG y Mini Métricas */
    /* Caja de progreso con Anillo SVG y Mini Métricas */
    .bento-progress-box {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border: 1.5px solid #e2e8f0;
        border-radius: 18px;
        padding: 12px 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }
    .bento-ring-wrap {
        position: relative;
        width: 78px;
        height: 78px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bento-ring-svg {
        transform: rotate(-90deg);
        width: 78px;
        height: 78px;
        filter: none;
    }
    .bento-ring-text {
        position: absolute;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        line-height: 1;
    }
    .bento-ring-pct {
        font-size: 1.10rem;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.5px;
    }
    .bento-ring-text small {
        font-size: 0.58rem;
        font-weight: 800;
        color: #10b981;
        text-transform: uppercase;
        margin-top: 3px;
        letter-spacing: 0.5px;
    }
    .bento-progress-details {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .bpd-metric-card {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 8px;
        min-height: 38px;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: transform 0.15s ease;
    }
    .bpd-metric-card:hover {
        transform: translateX(2px);
    }
    .bpd-metric-icon {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.88rem;
        flex-shrink: 0;
    }
    .bpd-metric-data {
        display: flex;
        flex-direction: column;
        min-width: 0;
        line-height: 1.2;
    }
    .bpd-lbl {
        font-size: 0.65rem;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    .bpd-val {
        font-size: 0.82rem;
        color: #0f172a;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        margin-top: 1px;
    }

    @media (max-width: 380px) {
        .bento-progress-box {
            padding: 10px 8px;
            gap: 8px;
        }
        .bento-ring-wrap, .bento-ring-svg {
            width: 70px;
            height: 70px;
        }
        .bpd-metric-card {
            padding: 4px 6px;
            gap: 6px;
        }
        .bpd-metric-icon {
            width: 24px;
            height: 24px;
            font-size: 0.80rem;
        }
        .bpd-lbl {
            font-size: 0.62rem;
        }
        .bpd-val {
            font-size: 0.78rem;
        }
    }

    /* Alerta ya marcó hoy con Flama de Racha 🔥 */
    .bento-alerta-ya-marco {
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 45%, #fff7ed 100%);
        border: 1.5px solid #86efac;
        border-radius: 16px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #065f46;
        margin-top: 14px;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.12);
        animation: streakBounce 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    .bento-ym-flame-box {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        background: linear-gradient(135deg, #ea580c, #f97316);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35);
        animation: flamePulse 1.2s infinite alternate ease-in-out;
    }
    .bento-ym-flame-icon {
        font-size: 1.55rem;
        line-height: 1;
    }
    .bento-ym-info {
        flex: 1;
        min-width: 0;
        text-align: left;
    }
    .bento-ym-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 2px;
    }
    .bento-ym-status-badge {
        font-size: 0.76rem;
        font-weight: 800;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .bento-ym-time {
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
    }
    .bento-ym-racha-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 2px 0;
    }
    .bento-ym-racha-label {
        font-size: 0.72rem;
        font-weight: 800;
        color: #9a3412;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .bento-ym-racha-count {
        font-size: 0.86rem;
        font-weight: 900;
        color: #ea580c;
    }
    .bento-ym-frase {
        font-size: 0.72rem;
        color: #475569;
        font-weight: 600;
        line-height: 1.3;
    }

    /* Botones de selección de método */
    .bento-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 16px;
    }
    .btn-metodo {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 16px;
        border: 2px solid transparent;
        cursor: pointer;
        text-align: left;
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        width: 100%;
        background: #fff;
    }
    .btn-metodo:hover {
        transform: translateY(-2px);
    }
    .btn-metodo i:first-child {
        font-size: 1.5rem;
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-metodo .bm-title {
        display: block;
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .btn-metodo .bm-sub {
        display: block;
        font-size: 0.76rem;
        color: #64748b;
        margin-top: 2px;
    }
    .btn-metodo .bm-arrow {
        margin-left: auto;
        color: #94a3b8;
        font-size: 1.1rem;
        transition: transform 0.2s;
    }
    .btn-metodo:hover .bm-arrow {
        transform: translateX(4px);
    }

    /* Método QR */
    .btn-metodo-qr {
        border-color: #a7f3d0;
        background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        box-shadow: 0 4px 16px rgba(16, 185, 129, 0.12);
    }
    .btn-metodo-qr:hover {
        border-color: #10b981;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.25);
    }
    .btn-metodo-qr i:first-child {
        background: #d1fae5;
        color: #059669;
    }

    /* Método PIN */
    .btn-metodo-pin {
        border-color: #bfdbfe;
        background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        box-shadow: 0 4px 16px rgba(37, 99, 235, 0.1);
    }
    .btn-metodo-pin:hover {
        border-color: #2563eb;
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.2);
    }
    .btn-metodo-pin i:first-child {
        background: #dbeafe;
        color: #1d4ed8;
    }

    /* Botón cambiar cédula */
    .bento-cambiar-cedula {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 600;
        margin-top: 14px;
        cursor: pointer;
        padding: 6px;
        transition: color 0.15s;
    }
    .bento-cambiar-cedula:hover {
        color: #2563eb;
    }

    /* ===== PASO 3A: INPUT PIN ===== */
    .pin-user-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 12px 4px 6px;
        background: #f1f5f9;
        border-radius: 999px;
        margin-bottom: 8px;
    }
    .puc-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #1e3a8a;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .puc-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e293b;
    }

    /* ===== PASO 3B: VISOR QR (FASE 3) ===== */
    .qr-scanner-wrapper {
        position: relative;
        width: 100%;
        max-width: 320px;
        margin: 16px auto;
        border-radius: 20px;
        overflow: hidden;
        background: #0f172a;
        border: 3px solid #10b981;
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.35);
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .qr-laser-line {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, #10b981, #34d399, transparent);
        box-shadow: 0 0 12px #10b981;
        animation: laserScan 2s ease-in-out infinite alternate;
        z-index: 10;
        pointer-events: none;
    }
    @keyframes laserScan {
        from { top: 5%; }
        to   { top: 92%; }
    }
    #qrReader {
        width: 100% !important;
        height: 100% !important;
        border: none !important;
        position: relative;
        background: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #qrReader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 17px;
    }
    #qrReader__scan_region {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100% !important;
        height: 100% !important;
    }
    #qrReader img, #qrReader__dashboard {
        display: none !important;
    }

    /* Botón cancelar */
    .btn-outline-cancel {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        padding: 11px;
        background: transparent;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-outline-cancel:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #94a3b8;
    }

    /* ===== PANEL RESULTADO & RECUPERACIÓN ===== */
    #panelResultado, #panelRecuperacion, #panelBento, #panelPin, #panelQr {
        display: none;
    }
    .resultado-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 2.2rem;
    }
    .resultado-ok { background: #dcfce7; color: #15803d; }
    .resultado-retardo { background: #fef3c7; color: #b45309; }
    .resultado-warn { background: #fee2e2; color: #b91c1c; }
    .resultado-err { background: #fee2e2; color: #dc2626; }
    .resultado-nombre { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
    .resultado-depto { font-size: 0.88rem; color: #64748b; font-weight: 600; margin-bottom: 8px; }
    .resultado-hora { font-size: 1.4rem; font-weight: 900; color: #059669; margin-bottom: 12px; }

    .recuperacion-header { text-align: center; margin-bottom: 20px; }
    .recuperacion-icon { width: 56px; height: 56px; border-radius: 50%; background: #ede9fe; color: #6d28d9; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 12px; }
    .recuperacion-title { font-size: 1.2rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
    .recuperacion-desc { font-size: 0.82rem; color: #64748b; line-height: 1.5; }

    /* ===== GAMIFICACIÓN: ANIMACIÓN DE RACHA 🔥 ===== */
    .racha-gamification-card {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 50%, #fed7aa 100%);
        border: 2px solid #fb923c;
        border-radius: 18px;
        padding: 16px 18px;
        margin: 16px 0;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 10px 25px rgba(251, 146, 60, 0.25);
        animation: streakBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        position: relative;
        overflow: hidden;
    }
    .racha-gamification-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, transparent 70%);
        animation: streakGlow 4s linear infinite;
        pointer-events: none;
    }
    @keyframes streakBounce {
        0% { transform: scale(0.7) translateY(20px); opacity: 0; }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    @keyframes streakGlow {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .racha-flame-wrapper {
        width: 58px;
        height: 58px;
        background: linear-gradient(135deg, #ea580c, #f97316);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 6px 18px rgba(234, 88, 12, 0.4);
        animation: flamePulse 1.2s infinite alternate ease-in-out;
    }
    @keyframes flamePulse {
        0% { transform: scale(1) rotate(-3deg); filter: drop-shadow(0 0 5px #f97316); }
        100% { transform: scale(1.14) rotate(4deg); filter: drop-shadow(0 0 14px #ea580c); }
    }
    .racha-flame-icon {
        font-size: 2.1rem;
        line-height: 1;
    }
    .racha-content {
        flex: 1;
        text-align: left;
    }
    .racha-title {
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 1px;
        color: #c2410c;
        text-transform: uppercase;
        margin-bottom: 2px;
    }
    .racha-days {
        font-size: 1.28rem;
        font-weight: 900;
        color: #9a3412;
        line-height: 1.1;
        letter-spacing: 0.5px;
    }
    .racha-days span {
        font-size: 1.6rem;
        color: #ea580c;
    }
    .racha-frase {
        font-size: 0.8rem;
        color: #7c2d12;
        font-weight: 600;
        margin-top: 3px;
        line-height: 1.3;
    }
    </style>
</head>
<body class="auth-wrapper">
    <?php include_once APPROOT . '/views/layouts/header_strip.php'; ?>

    <!-- ===== TARJETA PRINCIPAL DEL KIOSCO ===== -->
    <div class="auth-card">

        <!-- RELOJ DIGITAL PERMANENTE -->
        <div class="kiosco-clock">
            <div class="clock-time">
                <span id="clockHH">--</span><span class="clock-separator">:</span><span id="clockMM">--</span><span class="clock-separator">:</span><span id="clockSS">--</span><span class="clock-ampm" id="clockAMPM"></span>
            </div>
            <div class="clock-date" id="clockDate">Cargando fecha...</div>
        </div>

        <!-- BADGE ESTADO -->
        <div class="kiosco-status">
            <div class="status-dot"></div>
            <span>Registro Activo</span>
        </div>

        <!-- BANNER FERIADO (si aplica) -->
        <div id="bannerFeriado">
            <div style="font-size:1.8rem;margin-bottom:4px;" id="bfIcon">🎉</div>
            <div style="font-size:0.95rem;font-weight:800;" id="bfNombre">Día Feriado</div>
            <div style="font-size:0.78rem;opacity:0.9;" id="bfDesc">Día no laborable</div>
        </div>

        <!-- ==============================================================
             PASO 1: IDENTIFICACIÓN POR CÉDULA (LIMPIA)
             ============================================================== -->
        <div id="panelCedula" class="fade-step">
            <div class="auth-header" style="margin-bottom: 22px; text-align:center;">
                <h1 class="auth-title" style="font-size: 1.35rem; margin-bottom:4px;">Registro de Asistencia</h1>
                <p class="auth-subtitle" style="font-size: 0.85rem;">Ingresa tu número de cédula para identificarte</p>
            </div>

            <form id="formCedula" onsubmit="validarCedula(event)">
                <div class="form-group" style="margin-bottom: 20px;">
                    <input type="text"
                           id="cedulaInput"
                           class="input-modern"
                           placeholder=" "
                           maxlength="10"
                           inputmode="numeric"
                           autocomplete="off"
                           required
                           autofocus
                           style="font-size:1.25rem; font-weight:700; letter-spacing:1px; text-align:center;">
                    <label for="cedulaInput" class="label-floating" style="justify-content:center;">
                        <i class="ti ti-id-badge" style="margin-right:6px;font-size:18px;"></i>Número de Cédula
                    </label>
                </div>

                <button type="submit" class="btn-kiosco" id="btnConsultar">
                    <span id="textBtnConsultar">Continuar</span>
                    <i class="ti ti-arrow-right" style="font-size:1.15rem;" id="iconBtnConsultar"></i>
                </button>
            </form>
        </div>

        <!-- ==============================================================
             PASO 2: PERFIL BENTO UI + SELECTOR DE MÉTODO
             ============================================================== -->
        <div id="panelBento" class="fade-step">
            <div class="bento-profile-card">
                
                <!-- Cabecera de Usuario -->
                <div class="bento-profile-header">
                    <div class="bento-avatar" id="bentoAvatar">--</div>
                    <div class="bento-user-info">
                        <h2 class="bento-name" id="bentoNombre">Nombre Pasante</h2>
                        <div class="bento-cedula" id="bentoCedula">V-00.000.000</div>
                    </div>
                </div>

                <!-- Etiquetas de asignación -->
                <div class="bento-tags">
                    <div class="bento-tag">
                        <i class="ti ti-building-community"></i>
                        <span id="bentoDepto">Departamento</span>
                    </div>
                    <div class="bento-tag">
                        <i class="ti ti-school"></i>
                        <span id="bentoInst">Institución</span>
                    </div>
                </div>

                <!-- Anillo SVG de Progreso de Pasantía -->
                <div class="bento-progress-box">
                    <div class="bento-ring-wrap">
                        <svg class="bento-ring-svg" viewBox="0 0 100 100">
                            <!-- Círculo de fondo -->
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e2e8f0" stroke-width="9"/>
                            <!-- Círculo de progreso animado (circunferencia 2*PI*40 = 251.32) -->
                            <circle id="bentoRingCircle"
                                    cx="50" cy="50" r="40"
                                    fill="none"
                                    stroke="url(#bentoGrad)"
                                    stroke-width="9"
                                    stroke-linecap="round"
                                    stroke-dasharray="0 251.32"
                                    style="transition: stroke-dasharray 0.8s ease;"/>
                            <defs>
                                <linearGradient id="bentoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#10b981"/>
                                    <stop offset="100%" stop-color="#2563eb"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="bento-ring-text">
                            <span id="bentoPct" class="bento-ring-pct">0%</span>
                            <small>Meta</small>
                        </div>
                    </div>

                    <div class="bento-progress-details">
                        <div class="bpd-metric-card">
                            <div class="bpd-metric-icon" style="background:#eff6ff;color:#2563eb;">
                                <i class="ti ti-clock"></i>
                            </div>
                            <div class="bpd-metric-data">
                                <span class="bpd-lbl">Horas acumuladas</span>
                                <strong class="bpd-val" id="bentoHoras">0 / 1440 h</strong>
                            </div>
                        </div>
                        <div class="bpd-metric-card">
                            <div class="bpd-metric-icon" style="background:#ecfdf5;color:#059669;">
                                <i class="ti ti-calendar-check"></i>
                            </div>
                            <div class="bpd-metric-data">
                                <span class="bpd-lbl">Días asistidos</span>
                                <strong class="bpd-val" id="bentoDias">0 días</strong>
                            </div>
                        </div>
                        <div class="bpd-metric-card">
                            <div class="bpd-metric-icon" style="background:#fef3c7;color:#d97706;">
                                <i class="ti ti-hourglass-empty"></i>
                            </div>
                            <div class="bpd-metric-data">
                                <span class="bpd-lbl">Días restantes</span>
                                <strong class="bpd-val" id="bentoRestantes" style="color:#059669;">0 días</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banner si ya marcó hoy (con Gamificación de Racha 🔥) -->
                <div id="bentoYaMarco" class="bento-alerta-ya-marco" style="display:none;">
                    <div class="bento-ym-flame-box">
                        <span class="bento-ym-flame-icon">🔥</span>
                    </div>
                    <div class="bento-ym-info">
                        <div class="bento-ym-top">
                            <span class="bento-ym-status-badge"><i class="ti ti-circle-check"></i> ¡Asistencia Registrada!</span>
                            <span id="bentoHoraHoy" class="bento-ym-time">--:-- --</span>
                        </div>
                        <div class="bento-ym-racha-row">
                            <span class="bento-ym-racha-label">Racha Activa:</span>
                            <strong id="bentoRachaVal" class="bento-ym-racha-count">1 DÍA CONSECUTIVO</strong>
                        </div>
                        <div id="bentoRachaFrase" class="bento-ym-frase">¡Gran constancia! Mantén encendida tu flama.</div>
                    </div>
                </div>

                <!-- Botón de ir al portal cuando ya marcó hoy -->
                <div id="bentoYaMarcoBtns" style="display:none; margin-top:10px;">
                    <a href="<?= URLROOT ?>/auth/login" class="btn-kiosco" style="background:linear-gradient(135deg, #1e3a8a, #2563eb); text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="ti ti-login"></i> Ingresar al Portal de Pasantes
                    </a>
                </div>

                <!-- Botones de Acción (Si no ha marcado hoy) -->
                <div id="bentoAcciones" class="bento-actions">
                    <button type="button" class="btn-metodo btn-metodo-qr" onclick="seleccionarMetodo('QR')">
                        <i class="ti ti-qrcode"></i>
                        <div>
                            <span class="bm-title">Marcar con Código QR</span>
                            <span class="bm-sub">Escanear el cartel oficial de la oficina</span>
                        </div>
                        <i class="ti ti-chevron-right bm-arrow"></i>
                    </button>

                    <button type="button" class="btn-metodo btn-metodo-pin" onclick="seleccionarMetodo('PIN')">
                        <i class="ti ti-lock"></i>
                        <div>
                            <span class="bm-title">Marcar con PIN</span>
                            <span class="bm-sub">Ingresar tu clave de 4 dígitos</span>
                        </div>
                        <i class="ti ti-chevron-right bm-arrow"></i>
                    </button>
                </div>

                <!-- Botón para cambiar de usuario -->
                <button type="button" class="bento-cambiar-cedula" onclick="volverACedula()">
                    <i class="ti ti-arrow-left"></i> ¿No eres tú? Cambiar cédula
                </button>
            </div>
        </div>

        <!-- ==============================================================
             PASO 3A: ENTRADA DE PIN DE 4 DÍGITOS
             ============================================================== -->
        <div id="panelPin" class="fade-step">
            <div style="text-align:center; margin-bottom:16px;">
                <div class="pin-user-chip">
                    <span id="pinChipAvatar" class="puc-avatar">--</span>
                    <span id="pinChipNombre" class="puc-name">Pasante</span>
                </div>
                <h3 style="font-size:1.2rem; font-weight:800; color:#0f172a; margin:4px 0 2px;">Ingresa tu PIN</h3>
                <p style="font-size:0.82rem; color:#64748b; margin:0;">Código numérico personal de 4 dígitos</p>
            </div>

            <form id="pinForm" onsubmit="confirmarAsistenciaPin(event)">
                <div class="form-group has-toggle" style="margin-bottom:20px;">
                    <input type="password"
                           id="pinInput"
                           class="input-modern"
                           placeholder=" "
                           maxlength="4"
                           pattern="[0-9]{4}"
                           inputmode="numeric"
                           autocomplete="off"
                           required
                           style="letter-spacing:10px; font-size:1.5rem; text-align:center; font-weight:800;">
                    <label for="pinInput" class="label-floating" style="justify-content:center;">
                        <i class="ti ti-lock" style="margin-right:6px;"></i>PIN de Asistencia
                    </label>
                    <i class="ti ti-eye password-toggle" onclick="togglePinVis(this)"></i>
                </div>

                <button type="submit" class="btn-kiosco" id="btnConfirmarPin">
                    <i class="ti ti-check" style="font-size:1.2rem;"></i>
                    <span id="textBtnPin">Confirmar Asistencia</span>
                </button>
            </form>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding-top:14px; border-top:1px dashed #e2e8f0;">
                <button type="button" class="bento-cambiar-cedula" style="margin:0; width:auto;" onclick="volverABento()">
                    <i class="ti ti-arrow-left"></i> Cambiar método
                </button>
                <a href="#" onclick="solicitarResetPin(event)" class="auth-link" style="color:#2563eb; font-size:0.8rem; font-weight:700; text-decoration:none;">
                    ¿Olvidaste tu PIN?
                </a>
            </div>
        </div>

        <!-- ==============================================================
             PASO 3B: VISOR DE CÁMARA QR (INTEGRACIÓN FASE 3)
             ============================================================== -->
        <div id="panelQr" class="fade-step">
            <div style="text-align:center; margin-bottom:12px;">
                <h3 style="font-size:1.18rem; font-weight:800; color:#0f172a; margin:0 0 4px;">Escanear Código QR</h3>
                <p style="font-size:0.82rem; color:#64748b; margin:0;">Apunta la cámara al cartel oficial pegado en la oficina</p>
            </div>

            <!-- Contenedor del escáner con efecto láser -->
            <div class="qr-scanner-wrapper" id="qrScannerWrapper">
                <div id="qrReader" style="width:100%; height:100%;"></div>
                <div class="qr-laser-line"></div>
            </div>

            <div id="qrLoadingText" style="text-align:center; font-size:0.82rem; color:#64748b; margin-bottom:12px;">
                <i class="ti ti-camera" style="color:#10b981;"></i> Esperando detección del código...
            </div>

            <button type="button" class="btn-outline-cancel" onclick="cancelarEscaneoQr()">
                <i class="ti ti-arrow-left"></i> Cancelar y volver
            </button>
        </div>

        <!-- ==============================================================
             PANEL DE RESULTADO (CONFIRMACIÓN)
             ============================================================== -->
        <div id="panelResultado" class="fade-step" style="text-align:center;">
            <div id="resultIcon" class="resultado-icon resultado-ok">
                <i id="resultIconInner" class="ti ti-check"></i>
            </div>
            <div id="resultNombre" class="resultado-nombre"></div>
            <div id="resultDepto" class="resultado-depto"></div>
            <div id="resultHora" class="resultado-hora"></div>

            <!-- TARJETA DE GAMIFICACIÓN: RACHA DE ASISTENCIA 🔥 -->
            <div id="resultRachaBox" class="racha-gamification-card" style="display:none;">
                <div class="racha-flame-wrapper">
                    <span class="racha-flame-icon">🔥</span>
                </div>
                <div class="racha-content">
                    <div class="racha-title">¡Racha de Asistencia!</div>
                    <div class="racha-days"><span id="resultRachaDias">1</span> DÍAS CONSECUTIVOS</div>
                    <div class="racha-frase" id="resultRachaFrase">¡Excelente constancia! Sigue sumando a tu meta.</div>
                </div>
            </div>

            <p id="resultMsg" style="color:#64748b;font-size:0.88rem;margin-bottom:14px;line-height:1.5;"></p>

            <div style="display:flex; flex-direction:column; gap:10px; margin-top:16px;">
                <button type="button" class="btn-kiosco" style="background:linear-gradient(135deg, #059669, #10b981); color:#ffffff; font-weight:800; font-size:1.02rem; border:none; box-shadow:0 4px 14px rgba(16,185,129,0.35); display:flex; align-items:center; justify-content:center; gap:8px; padding:13px 18px; border-radius:12px; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'" onclick="volverACedula()">
                    <i class="ti ti-arrow-left" style="font-size:1.25rem;"></i> Volver al Quiosco
                </button>
                <a href="<?= URLROOT ?>/auth/login" class="btn-kiosco" style="background:#f8fafc; color:#1e3a8a; border:1.5px solid #cbd5e1; box-shadow:none; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px; font-size:0.88rem; padding:11px 16px; border-radius:12px; font-weight:700;">
                    <i class="ti ti-user-circle"></i> Ir al Portal / Mi Cuenta
                </a>
            </div>
        </div>

        <!-- Canvas festivo para animación de confeti 🔥 -->
        <canvas id="kioscoConfettiCanvas" style="position:fixed; top:0; left:0; width:100vw; height:100vh; pointer-events:none; z-index:99999; display:none;"></canvas>

        <!-- ==============================================================
             PANEL DE RECUPERACIÓN DE PIN
             ============================================================== -->
        <div id="panelRecuperacion" class="fade-step">
            <div class="recuperacion-header">
                <div class="recuperacion-icon"><i class="ti ti-lock-question"></i></div>
                <div class="recuperacion-title">¿Olvidaste tu PIN?</div>
                <div class="recuperacion-desc">Notificaremos a tu tutor y administrador para ayudarte a restablecer tu código de acceso.</div>
            </div>

            <form id="formRecuperacion" onsubmit="enviarSolicitudRecuperacion(event)">
                <div class="form-group" style="margin-bottom:16px;">
                    <input type="text" id="cedulaRecuperacion" class="input-modern" placeholder=" " maxlength="10" inputmode="numeric" autocomplete="off" required style="font-size:1.15rem; font-weight:700; text-align:center;">
                    <label for="cedulaRecuperacion" class="label-floating" style="justify-content:center;">Número de Cédula</label>
                </div>
                
                <button type="submit" class="btn-kiosco" style="background:#6d28d9; margin-bottom:10px;" id="btnEnviarRecuperacion">
                    <i class="ti ti-send"></i> <span id="textBtnRecuperacion">Enviar Solicitud</span>
                </button>
                <button type="button" class="btn-outline-cancel" onclick="cancelarRecuperacion()">
                    Cancelar y Volver
                </button>
            </form>
        </div>

        <!-- FOOTER: ENLACE AL PORTAL WEB DEL SISTEMA -->
        <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px dashed #e2e8f0;">
            <a href="<?= URLROOT ?>/auth/login" class="auth-link" style="color: #2563eb; font-size: 0.88rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="ti ti-user-circle" style="font-size:1.15rem;"></i> Acceso al Portal (Pasantes y Personal)
            </a>
        </div>

    </div><!-- /auth-card -->

    <!-- ===== SCRIPTS ===== -->
    <?php include_once APPROOT . '/views/layouts/footer.php'; ?>
    <script src="<?= URLROOT ?>/js/notyf.min.js"></script>
    <script src="<?= URLROOT ?>/js/sweetalert2.min.js"></script>
    <script src="<?= URLROOT ?>/js/html5-qrcode.min.js"></script>

    <script>
    // ── Token URL opcional (si el usuario abrió el quiosco desde el cartel) ──
    const initialUrlToken = <?php echo json_encode($data['urlToken'] ?? '', JSON_UNESCAPED_SLASHES); ?>;

    // ── Estado global del quiosco en memoria ──────────────────────
    let pasanteActual = null;
    let timerAutoReset = null;

    // ── Reloj digital permanente ──────────────────────────────────
    const DIAS  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    function actualizarReloj() {
        const now  = new Date();
        const h24  = now.getHours();
        const mm   = String(now.getMinutes()).padStart(2, '0');
        const ss   = String(now.getSeconds()).padStart(2, '0');
        const ampm = h24 >= 12 ? 'PM' : 'AM';
        const h12  = h24 % 12 || 12;

        document.getElementById('clockHH').textContent   = String(h12).padStart(2, '0');
        document.getElementById('clockMM').textContent   = mm;
        document.getElementById('clockSS').textContent   = ss;
        document.getElementById('clockAMPM').textContent = ampm;
        document.getElementById('clockDate').textContent =
            DIAS[now.getDay()] + ', ' + now.getDate() + ' de ' + MESES[now.getMonth()] + ' de ' + now.getFullYear();
    }
    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    // ── Filtro de solo números ─────────────────────────────────────
    ['cedulaInput', 'pinInput', 'cedulaRecuperacion'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });
        }
    });

    // ── Alternar visibilidad de PIN ────────────────────────────────
    function togglePinVis(icon) {
        const pinEl = document.getElementById('pinInput');
        if (pinEl.type === 'password') {
            pinEl.type = 'text';
            icon.classList.replace('ti-eye', 'ti-eye-off');
        } else {
            pinEl.type = 'password';
            icon.classList.replace('ti-eye-off', 'ti-eye');
        }
    }

    // ── Geolocalización y Geocerca GPS (Anti-Buddy Punching) ──────
    let cachedGpsCoords = null;

    function solicitarGpsSilencioso() {
        if (navigator.geolocation && !cachedGpsCoords) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    cachedGpsCoords = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        accuracy: pos.coords.accuracy
                    };
                },
                (err) => {
                    console.warn("Aviso GPS anticipado:", err);
                },
                { enableHighAccuracy: true, timeout: 6000, maximumAge: 60000 }
            );
        }
    }
    // Pre-solicitar permiso al iniciar para mayor agilidad
    solicitarGpsSilencioso();

    async function obtenerCoordenadasGps() {
        if (cachedGpsCoords) return cachedGpsCoords;
        if (!navigator.geolocation) return null;

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    cachedGpsCoords = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        accuracy: pos.coords.accuracy
                    };
                    resolve(cachedGpsCoords);
                },
                (err) => {
                    console.warn("Aviso al obtener GPS:", err);
                    resolve(null);
                },
                { enableHighAccuracy: true, timeout: 7000, maximumAge: 10000 }
            );
        });
    }

    // ── Verificar feriado al cargar ────────────────────────────────
    (async function verificarFeriado() {
        try {
            const r = await fetch(URLROOT + '/kiosco/esFeriado');
            const d = await r.json();
            if (d.is_feriado) {
                const banner = document.getElementById('bannerFeriado');
                document.getElementById('bfNombre').textContent = d.nombre;
                banner.style.display = 'block';

                if (d.es_laborable) {
                    banner.classList.add('laborable');
                    document.getElementById('bfIcon').textContent = '📅';
                    document.getElementById('bfDesc').textContent = 'Es un feriado laborable — la asistencia se registra con normalidad.';
                    document.querySelector('.kiosco-status span').textContent = 'Feriado Laborable';
                    document.querySelector('.status-dot').style.background = '#10b981';
                } else {
                    document.getElementById('panelCedula').classList.add('bloqueado');
                    document.querySelector('.kiosco-status span').textContent = 'Feriado — No Laborable';
                    document.querySelector('.status-dot').style.background = '#f59e0b';
                }
            }
        } catch(e) {}
    })();

    // ==============================================================
    // PASO 1 ➔ PASO 2: VALIDACIÓN SILENCIOSA DE CÉDULA
    // ==============================================================
    async function validarCedula(e) {
        e.preventDefault();

        // Validar fin de semana
        const dow = new Date().getDay();
        if (dow === 0 || dow === 6) {
            const nombreDia = dow === 6 ? 'sábado' : 'domingo';
            Swal.fire({
                icon: 'info',
                title: 'Día no laborable',
                text: 'Hoy es ' + nombreDia + '. No se registra asistencia los fines de semana.',
                confirmButtonColor: '#1e3a8a'
            });
            return;
        }

        const cedula = document.getElementById('cedulaInput').value.trim();
        if (!cedula) return;

        const btn = document.getElementById('btnConsultar');
        const txt = document.getElementById('textBtnConsultar');
        const ico = document.getElementById('iconBtnConsultar');

        btn.disabled = true;
        txt.textContent = 'Verificando...';
        ico.className = 'ti ti-loader spinner';

        try {
            const formData = new FormData();
            formData.append('cedula', cedula);

            const resp = await fetch(URLROOT + '/kiosco/consultarPasante', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            if (res.success && res.pasante) {
                pasanteActual = res.pasante;
                mostrarPerfilBento(res);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: res.message || 'Cédula no encontrada.',
                    confirmButtonColor: '#1e3a8a'
                }).then(() => {
                    document.getElementById('cedulaInput').value = '';
                    document.getElementById('cedulaInput').focus();
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo verificar la cédula. Intenta de nuevo.',
                confirmButtonColor: '#1e3a8a'
            });
        } finally {
            btn.disabled = false;
            txt.textContent = 'Continuar';
            ico.className = 'ti ti-arrow-right';
        }
    }

    // ==============================================================
    // RENDERIZAR PERFIL BENTO UI
    // ==============================================================
    function mostrarPerfilBento(res) {
        const p = res.pasante;

        // Hidratar datos básicos
        document.getElementById('bentoAvatar').textContent = p.iniciales || 'PA';
        document.getElementById('bentoNombre').textContent = p.nombre_completo;
        document.getElementById('bentoCedula').textContent = 'C.I. ' + p.cedula;
        document.getElementById('bentoDepto').textContent  = p.departamento;
        document.getElementById('bentoInst').textContent   = p.institucion;

        // Progreso de horas
        document.getElementById('bentoHoras').textContent     = p.horas_acumuladas + ' / ' + p.horas_meta + ' h';
        document.getElementById('bentoDias').textContent      = p.dias_asistidos + ' días';
        document.getElementById('bentoRestantes').textContent = p.dias_restantes + ' días';
        document.getElementById('bentoPct').textContent       = p.porcentaje + '%';

        // Animar el círculo SVG (circunferencia = 251.32)
        const circ = 251.32;
        const dash = (p.porcentaje / 100) * circ;
        const gap  = circ - dash;
        document.getElementById('bentoRingCircle').style.strokeDasharray = `${dash.toFixed(1)} ${gap.toFixed(1)}`;

        // Si ya marcó hoy
        if (res.ya_marco_hoy) {
            document.getElementById('bentoYaMarco').style.display  = 'flex';
            const ymBtns = document.getElementById('bentoYaMarcoBtns');
            if (ymBtns) ymBtns.style.display = 'block';

            document.getElementById('bentoHoraHoy').textContent    = 'Marcada a las ' + (res.hora_hoy || '--');

            // Actualizar flama de racha activa
            const rNum = parseInt(res.racha || (p && p.racha) || 0, 10);
            const rValEl = document.getElementById('bentoRachaVal');
            const rFraseEl = document.getElementById('bentoRachaFrase');
            if (rValEl) {
                if (rNum > 0) {
                    rValEl.textContent = rNum + (rNum === 1 ? ' DÍA CONSECUTIVO' : ' DÍAS CONSECUTIVOS');
                    if (rNum === 1) {
                        rFraseEl.textContent = '¡Excelente constancia! Tu meta diaria comienza con éxito.';
                    } else if (rNum >= 2 && rNum <= 4) {
                        rFraseEl.textContent = '¡En llamas! Mantén el ritmo y supera tu récord personal.';
                    } else if (rNum >= 5 && rNum <= 9) {
                        rFraseEl.textContent = '¡Semana perfecta! Compromiso y excelencia profesional.';
                    } else {
                        rFraseEl.textContent = '¡Nivel Leyenda! Asistencia y disciplina impecables.';
                    }
                } else {
                    rValEl.textContent = '1 DÍA CONSECUTIVO';
                    rFraseEl.textContent = '¡Gran constancia! Mantén encendida tu flama.';
                }
            }
            document.getElementById('bentoAcciones').style.display = 'none';
        } else {
            document.getElementById('bentoYaMarco').style.display  = 'none';
            const ymBtns = document.getElementById('bentoYaMarcoBtns');
            if (ymBtns) ymBtns.style.display = 'none';
            document.getElementById('bentoAcciones').style.display = 'flex';

            // Adaptar botón QR si el usuario ya trajo el token en la URL
            const btnQr = document.querySelector('.btn-metodo-qr');
            if (btnQr) {
                if (initialUrlToken) {
                    btnQr.querySelector('.bm-title').textContent = 'Confirmar con QR de Oficina';
                    btnQr.querySelector('.bm-sub').textContent   = 'Cartel oficial escaneado. Toca para marcar directamente.';
                    btnQr.style.border     = '2px solid #10b981';
                    btnQr.style.background = '#f0fdf4';
                } else {
                    btnQr.querySelector('.bm-title').textContent = 'Marcar con Código QR';
                    btnQr.querySelector('.bm-sub').textContent   = 'Escanear el cartel oficial de la oficina';
                    btnQr.style.border     = '';
                    btnQr.style.background = '';
                }
            }
        }

        // Chip para el panel PIN
        document.getElementById('pinChipAvatar').textContent = p.iniciales || 'PA';
        document.getElementById('pinChipNombre').textContent = p.nombres;

        // Transición de paneles
        document.getElementById('panelCedula').style.display = 'none';
        document.getElementById('panelBento').style.display  = 'block';
    }

    // ==============================================================
    // NAVEGACIÓN Y SELECCIÓN DE MÉTODO (PIN vs QR)
    // ==============================================================
    function seleccionarMetodo(metodo) {
        if (!pasanteActual) return;

        if (metodo === 'PIN') {
            document.getElementById('panelBento').style.display = 'none';
            document.getElementById('panelPin').style.display = 'block';
            document.getElementById('pinInput').value = '';
            document.getElementById('pinInput').focus();
        } else if (metodo === 'QR') {
            if (initialUrlToken) {
                // Token detectado en la URL: marcar directamente sin abrir cámara
                enviarMarcajeQr(initialUrlToken);
            } else {
                document.getElementById('panelBento').style.display = 'none';
                document.getElementById('panelQr').style.display = 'block';
                iniciarLectorQr();
            }
        }
    }

    function volverABento() {
        document.getElementById('panelPin').style.display = 'none';
        document.getElementById('panelQr').style.display  = 'none';
        detenerLectorQr().finally(() => {
            document.getElementById('panelBento').style.display = 'block';
        });
    }

    function volverACedula() {
        if (timerAutoReset) clearTimeout(timerAutoReset);
        detenerLectorQr();
        pasanteActual = null;
        document.getElementById('formCedula').reset();
        document.getElementById('panelBento').style.display       = 'none';
        document.getElementById('panelPin').style.display         = 'none';
        document.getElementById('panelQr').style.display          = 'none';
        document.getElementById('panelResultado').style.display   = 'none';
        document.getElementById('panelRecuperacion').style.display= 'none';
        document.getElementById('panelCedula').style.display      = 'block';
        document.getElementById('cedulaInput').focus();
    }

    // ==============================================================
    // CONFIRMACIÓN DE ASISTENCIA POR PIN
    // ==============================================================
    async function confirmarAsistenciaPin(e) {
        e.preventDefault();
        if (!pasanteActual) return;

        const pin = document.getElementById('pinInput').value.trim();
        if (pin.length !== 4) return;

        const btn = document.getElementById('btnConfirmarPin');
        const txt = document.getElementById('textBtnPin');
        const ico = btn.querySelector('i');

        btn.disabled = true;
        txt.textContent = 'Verificando GPS...';
        ico.className = 'ti ti-loader spinner';

        try {
            const coords = await obtenerCoordenadasGps();

            txt.textContent = 'Registrando...';
            const formData = new FormData();
            formData.append('cedula', pasanteActual.cedula);
            formData.append('metodo', 'PIN');
            formData.append('pin_asistencia', pin);
            if (coords) {
                formData.append('lat', coords.lat);
                formData.append('lng', coords.lng);
            }

            const resp = await fetch(URLROOT + '/kiosco/marcar', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            if (res.success) {
                mostrarResultado(res);
            } else if (res.requiere_gps) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Permiso GPS Necesario',
                    text: res.message || 'Debes conceder acceso a tu ubicación GPS para verificar que te encuentras en las instalaciones del ISPEB.',
                    confirmButtonColor: '#1e3a8a'
                });
            } else if (res.fuera_geocerca) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fuera de Perímetro',
                    text: res.message,
                    confirmButtonColor: '#1e3a8a'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'PIN Incorrecto',
                    text: res.message || 'Verifica tu PIN e intenta nuevamente.',
                    confirmButtonColor: '#1e3a8a'
                }).then(() => {
                    document.getElementById('pinInput').value = '';
                    document.getElementById('pinInput').focus();
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo comunicar con el servidor.',
                confirmButtonColor: '#1e3a8a'
            });
        } finally {
            btn.disabled = false;
            txt.textContent = 'Confirmar Asistencia';
            ico.className = 'ti ti-check';
        }
    }

    // ==============================================================
    // LECTOR DE CÓDIGO QR CON HTML5-QRCODE (FASE 3)
    // ==============================================================
    let html5QrInstance = null;
    let isQrScanning = false;
    let isProcessingQr = false;

    async function iniciarLectorQr() {
        if (!pasanteActual) return;
        isProcessingQr = false;

        const loadingText = document.getElementById('qrLoadingText');
        loadingText.innerHTML = '<i class="ti ti-camera" style="color:#10b981;"></i> Activando cámara del dispositivo...';

        await detenerLectorQr();

        try {
            if (typeof Html5Qrcode === 'undefined') {
                throw new Error('Librería QR no cargada');
            }

            html5QrInstance = new Html5Qrcode("qrReader");

            const qrConfig = {
                fps: 15,
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    return {
                        width: Math.floor(minEdge * 0.72),
                        height: Math.floor(minEdge * 0.72)
                    };
                },
                aspectRatio: 1.0
            };

            // Intentar primero con cámara trasera (environment)
            html5QrInstance.start(
                { facingMode: "environment" },
                qrConfig,
                onQrScanSuccess,
                onQrScanFailure
            ).then(() => {
                isQrScanning = true;
                loadingText.innerHTML = '<i class="ti ti-scan" style="color:#10b981;"></i> Enfoca el cartel oficial de la oficina';
            }).catch(err => {
                console.warn("Fallo cámara environment, probando modo genérico:", err);
                // Si no tiene cámara trasera (laptop/PC), probar frontal
                html5QrInstance.start(
                    { facingMode: "user" },
                    qrConfig,
                    onQrScanSuccess,
                    onQrScanFailure
                ).then(() => {
                    isQrScanning = true;
                    loadingText.innerHTML = '<i class="ti ti-scan" style="color:#10b981;"></i> Enfoca el cartel oficial de la oficina';
                }).catch(err2 => {
                    console.error("Error al iniciar cámara:", err2);
                    tratarErrorCamara(err2);
                });
            });

        } catch (e) {
            console.error("Excepción al configurar lector QR:", e);
            tratarErrorCamara(e);
        }
    }

    function onQrScanSuccess(decodedText, decodedResult) {
        if (isProcessingQr) return;
        isProcessingQr = true;

        if (navigator.vibrate) {
            try { navigator.vibrate([100, 50, 100]); } catch (e) {}
        }

        const loadingText = document.getElementById('qrLoadingText');
        loadingText.innerHTML = '<i class="ti ti-loader spinner" style="color:#10b981;"></i> Verificando código QR...';

        detenerLectorQr().finally(() => {
            enviarMarcajeQr(decodedText);
        });
    }

    function onQrScanFailure(error) {
        // Cuadro continuo sin código detectado — comportamiento normal
    }

    function tratarErrorCamara(err) {
        let msg = 'No se pudo iniciar la cámara para escanear el código QR.';
        const errStr = (err && (err.name || err.message)) ? (err.name + ': ' + err.message) : String(err);

        if (errStr.includes('NotAllowedError') || errStr.includes('Permission denied')) {
            msg = 'El navegador bloqueó el acceso a la cámara. Concede permisos o utiliza tu PIN de 4 dígitos.';
        } else if (errStr.includes('NotFoundError') || errStr.includes('DevicesNotFoundError')) {
            msg = 'No se detectó ninguna cámara disponible en tu dispositivo.';
        } else if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            msg = 'El acceso a la cámara requiere una conexión HTTPS segura.';
        }

        Swal.fire({
            icon: 'warning',
            title: 'Cámara No Disponible',
            text: msg,
            confirmButtonText: 'Marcar con PIN',
            showCancelButton: true,
            cancelButtonText: 'Regresar',
            confirmButtonColor: '#1e3a8a',
            cancelButtonColor: '#64748b'
        }).then((result) => {
            if (result.isConfirmed) {
                seleccionarMetodo('PIN');
            } else {
                cancelarEscaneoQr();
            }
        });
    }

    function detenerLectorQr() {
        return new Promise((resolve) => {
            if (html5QrInstance && isQrScanning) {
                html5QrInstance.stop().then(() => {
                    html5QrInstance.clear();
                    isQrScanning = false;
                    resolve();
                }).catch(err => {
                    console.warn("Aviso al detener lector QR:", err);
                    isQrScanning = false;
                    resolve();
                });
            } else {
                resolve();
            }
        });
    }

    function cancelarEscaneoQr() {
        detenerLectorQr().finally(() => {
            volverABento();
        });
    }

    async function enviarMarcajeQr(qrTokenScanned) {
        if (!pasanteActual) return;

        Swal.fire({
            title: 'Registrando Asistencia',
            text: 'Validando ubicación GPS y código del cartel...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const coords = await obtenerCoordenadasGps();

            // Dispositivo de Confianza — Generar o recuperar UUID persistente del equipo
            let deviceUuid = localStorage.getItem('sgp_trusted_device_uuid');
            if (!deviceUuid) {
                if (window.crypto && crypto.randomUUID) {
                    deviceUuid = crypto.randomUUID();
                } else {
                    deviceUuid = 'dev_' + ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                        (c ^ (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))).toString(16)
                    );
                }
                localStorage.setItem('sgp_trusted_device_uuid', deviceUuid);
            }

            // Detección del modelo o nombre del dispositivo
            let deviceName = 'Navegador Móvil';
            const ua = navigator.userAgent || '';
            if (/android/i.test(ua)) {
                const match = ua.match(/;\s*([^;)]+)\s*Build/i);
                deviceName = match ? match[1].trim() : 'Android Phone';
            } else if (/iPhone/i.test(ua)) {
                deviceName = 'Apple iPhone';
            } else if (/iPad/i.test(ua)) {
                deviceName = 'Apple iPad';
            } else if (/Windows/i.test(ua)) {
                deviceName = 'PC Windows';
            } else if (/Macintosh/i.test(ua)) {
                deviceName = 'Mac';
            }

            const formData = new FormData();
            formData.append('cedula', pasanteActual.cedula);
            formData.append('metodo', 'QR');
            formData.append('qr_token', qrTokenScanned);
            formData.append('device_uuid', deviceUuid);
            formData.append('device_name', deviceName);
            if (coords) {
                formData.append('lat', coords.lat);
                formData.append('lng', coords.lng);
            }

            const resp = await fetch(URLROOT + '/kiosco/marcar', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();
            Swal.close();

            if (res.success) {
                mostrarResultado(res);
            } else if (res.requiere_gps) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Permiso GPS Necesario',
                    text: res.message || 'Debes conceder acceso a tu ubicación GPS para verificar que te encuentras en el ISPEB.',
                    confirmButtonColor: '#1e3a8a'
                }).then(() => volverABento());
            } else if (res.fuera_geocerca) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fuera de Perímetro',
                    text: res.message,
                    confirmButtonColor: '#1e3a8a'
                }).then(() => volverABento());
            } else {
                const titleAlert = (res.message && res.message.includes('Dispositivo')) 
                    ? 'Seguridad de Dispositivo' 
                    : (res.message && res.message.includes('código') ? 'Código QR No Válido' : 'Aviso de Marcaje');
                Swal.fire({
                    icon: 'error',
                    title: titleAlert,
                    text: res.message || 'No se pudo procesar el marcaje de asistencia.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#1e3a8a'
                }).then(() => {
                    isProcessingQr = false;
                    volverABento();
                });
            }
        } catch (e) {
            Swal.close();
            isProcessingQr = false;
            Swal.fire({
                icon: 'error',
                title: 'Error de Red',
                text: 'No se pudo conectar con el servidor para registrar la asistencia.',
                confirmButtonColor: '#1e3a8a'
            }).then(() => volverABento());
        }
    }

    // ==============================================================
    // PANEL RESULTADO
    // ==============================================================
    function mostrarResultado(json) {
        document.getElementById('panelPin').style.display   = 'none';
        document.getElementById('panelQr').style.display    = 'none';
        document.getElementById('panelBento').style.display = 'none';

        const iconEl   = document.getElementById('resultIcon');
        const innerIco = document.getElementById('resultIconInner');

        if (json.retardo) {
            iconEl.className   = 'resultado-icon resultado-retardo';
            innerIco.className = 'ti ti-clock-exclamation';
        } else {
            iconEl.className   = 'resultado-icon resultado-ok';
            innerIco.className = 'ti ti-check';
        }

        document.getElementById('resultNombre').textContent = json.pasante ? (json.pasante.nombres + ' ' + json.pasante.apellidos) : '';
        document.getElementById('resultDepto').textContent  = json.pasante ? ('Departamento: ' + json.pasante.departamento) : '';
        document.getElementById('resultHora').textContent   = json.hora || '';
        document.getElementById('resultMsg').textContent    = json.message || '¡Asistencia registrada!';

        // Tarjeta de gamificación: Racha de Asistencia 🔥
        const rachaBox = document.getElementById('resultRachaBox');
        if (json.racha && json.racha > 0) {
            document.getElementById('resultRachaDias').textContent = json.racha;
            const rachaFrase = document.getElementById('resultRachaFrase');
            if (json.racha === 1) {
                rachaFrase.textContent = '¡Excelente inicio! Tu constancia laboral comienza hoy.';
            } else if (json.racha >= 2 && json.racha <= 4) {
                rachaFrase.textContent = '¡En llamas! Mantén el ritmo y supera tu récord personal.';
            } else if (json.racha >= 5 && json.racha <= 9) {
                rachaFrase.textContent = '¡Semana perfecta! Compromiso y excelencia profesional.';
            } else {
                rachaFrase.textContent = '¡Nivel Leyenda! Asistencia y disciplina impecables.';
            }
            rachaBox.style.display = 'flex';
        } else {
            rachaBox.style.display = 'none';
        }

        document.getElementById('panelResultado').style.display = 'block';

        // 1. Efectos Sensoriales de Éxito: Chime acústico, Vibración háptica y Confeti 🎉
        reproducirChimeExito();
        vibrarDispositivo();
        lanzarConfettiCelebracion();

        // 2. Se mantiene la tarjeta y la animación de racha fijas en pantalla
        // El usuario regresa al quiosco únicamente cuando presiona el botón "Volver al Quiosco"
        if (timerAutoReset) {
            clearTimeout(timerAutoReset);
            timerAutoReset = null;
        }
    }

    // ==============================================================
    // EFECTOS SENSORIALES: AUDIO CHIME, VIBRACIÓN Y CONFETI
    // ==============================================================
    function reproducirChimeExito() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const t = ctx.currentTime;

            // Nota 1: Do5 (523.25 Hz)
            const o1 = ctx.createOscillator();
            const g1 = ctx.createGain();
            o1.type = 'sine';
            o1.frequency.setValueAtTime(523.25, t);
            g1.gain.setValueAtTime(0.15, t);
            g1.gain.exponentialRampToValueAtTime(0.001, t + 0.3);
            o1.connect(g1);
            g1.connect(ctx.destination);
            o1.start(t);
            o1.stop(t + 0.3);

            // Nota 2: Mi5 (659.25 Hz)
            const o2 = ctx.createOscillator();
            const g2 = ctx.createGain();
            o2.type = 'sine';
            o2.frequency.setValueAtTime(659.25, t + 0.1);
            g2.gain.setValueAtTime(0.18, t + 0.1);
            g2.gain.exponentialRampToValueAtTime(0.001, t + 0.45);
            o2.connect(g2);
            g2.connect(ctx.destination);
            o2.start(t + 0.1);
            o2.stop(t + 0.45);

            // Nota 3 Triunfal: Sol5 (783.99 Hz)
            const o3 = ctx.createOscillator();
            const g3 = ctx.createGain();
            o3.type = 'triangle';
            o3.frequency.setValueAtTime(783.99, t + 0.22);
            g3.gain.setValueAtTime(0.22, t + 0.22);
            g3.gain.exponentialRampToValueAtTime(0.001, t + 0.7);
            o3.connect(g3);
            g3.connect(ctx.destination);
            o3.start(t + 0.22);
            o3.stop(t + 0.7);
        } catch(err) {
            console.warn("Audio chime no disponible:", err);
        }
    }

    function vibrarDispositivo() {
        if ('vibrate' in navigator) {
            try {
                navigator.vibrate([100, 60, 140]);
            } catch(e) {}
        }
    }

    function lanzarConfettiCelebracion() {
        const canvas = document.getElementById('kioscoConfettiCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        canvas.style.display = 'block';

        const particles = [];
        const colors = ['#ea580c', '#f97316', '#fbbf24', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'];
        const total = Math.min(80, Math.floor(window.innerWidth / 5));

        for (let i = 0; i < total; i++) {
            particles.push({
                x: window.innerWidth / 2 + (Math.random() - 0.5) * 60,
                y: window.innerHeight * 0.45,
                w: Math.random() * 8 + 6,
                h: Math.random() * 6 + 4,
                color: colors[Math.floor(Math.random() * colors.length)],
                vx: (Math.random() - 0.5) * 14,
                vy: -(Math.random() * 12 + 6),
                gravity: 0.35,
                rot: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 10,
                opacity: 1
            });
        }

        let start = null;
        function render(time) {
            if (!start) start = time;
            const elapsed = time - start;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            let alive = 0;
            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += p.gravity;
                p.rot += p.rotSpeed;
                p.vx *= 0.98;

                if (elapsed > 1600) {
                    p.opacity -= 0.02;
                }

                if (p.opacity > 0 && p.y < canvas.height) {
                    alive++;
                    ctx.save();
                    ctx.translate(p.x, p.y);
                    ctx.rotate((p.rot * Math.PI) / 180);
                    ctx.globalAlpha = Math.max(0, p.opacity);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                    ctx.restore();
                }
            });

            if (alive > 0 && elapsed < 3000) {
                requestAnimationFrame(render);
            } else {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                canvas.style.display = 'none';
            }
        }
        requestAnimationFrame(render);
    }

    // ==============================================================
    // RECUPERACIÓN DE PIN
    // ==============================================================
    function solicitarResetPin(e) {
        e.preventDefault();
        document.getElementById('panelCedula').style.display = 'none';
        document.getElementById('panelBento').style.display  = 'none';
        document.getElementById('panelPin').style.display    = 'none';

        const ced = pasanteActual ? pasanteActual.cedula : document.getElementById('cedulaInput').value.trim();
        document.getElementById('cedulaRecuperacion').value = ced;
        document.getElementById('panelRecuperacion').style.display = 'block';
        document.getElementById('cedulaRecuperacion').focus();
    }

    function cancelarRecuperacion() {
        document.getElementById('panelRecuperacion').style.display = 'none';
        if (pasanteActual) {
            document.getElementById('panelPin').style.display = 'block';
        } else {
            document.getElementById('panelCedula').style.display = 'block';
        }
    }

    async function enviarSolicitudRecuperacion(e) {
        e.preventDefault();
        const ced = document.getElementById('cedulaRecuperacion').value.trim();
        if (!ced) return;

        const btn = document.getElementById('btnEnviarRecuperacion');
        const txt = document.getElementById('textBtnRecuperacion');
        const ico = btn.querySelector('i');

        btn.disabled = true;
        txt.textContent = 'Enviando...';
        ico.className = 'ti ti-loader spinner';

        try {
            const formData = new FormData();
            formData.append('cedula', ced);

            const resp = await fetch(URLROOT + '/kiosco/solicitarResetPin', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            Swal.fire({
                icon: res.success ? 'success' : 'warning',
                title: res.success ? '¡Solicitud Enviada!' : 'Atención',
                text: res.message || '',
                confirmButtonColor: '#1e3a8a'
            }).then(() => {
                volverACedula();
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo enviar la solicitud.',
                confirmButtonColor: '#1e3a8a'
            });
        } finally {
            btn.disabled = false;
            txt.textContent = 'Enviar Solicitud';
            ico.className = 'ti ti-send';
        }
    }

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
                    .then(function(reg) { if (reg) reg.update(); })
                    .catch(function(){});
            });
        }
    }
    </script>
</body>
</html>
