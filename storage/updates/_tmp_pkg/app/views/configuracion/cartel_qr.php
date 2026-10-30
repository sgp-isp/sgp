<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'Cartel Oficial — Registro de Asistencia') ?></title>
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png">
    
    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    
    <!-- Script QR Code offline -->
    <script src="<?= URLROOT ?>/js/qrcode.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #0b1329;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 24px 15px 40px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ── BARRA DE ACCIÓN FLOTANTE (SOLO PANTALLA) ── */
        .no-print-bar {
            width: 100%;
            max-width: 650px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #1e293b;
            padding: 12px 20px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            border: 1px solid #334155;
            z-index: 100;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .btn-back {
            background: #334155;
            color: #f1f5f9;
        }
        .btn-back:hover {
            background: #475569;
            color: #fff;
        }

        .btn-print {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
        }
        .btn-print:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.5);
        }

        /* ── HOJA DE CARTEL IMPRIMIBLE (DISEÑO VERTICAL ORIGINAL) ── */
        .cartel-sheet {
            background-color: #ffffff;
            /* Patrón de seguridad oficial (Guilloche / micro-ondas sutiles) */
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(30, 58, 138, 0.025) 0%, transparent 60%),
                repeating-linear-gradient(45deg, rgba(2, 132, 199, 0.015) 0px, rgba(2, 132, 199, 0.015) 2px, transparent 2px, transparent 10px),
                repeating-linear-gradient(-45deg, rgba(16, 185, 129, 0.012) 0px, rgba(16, 185, 129, 0.012) 2px, transparent 2px, transparent 10px);
            width: 100%;
            max-width: 650px;
            min-height: 0;
            padding: 24px 28px 20px;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border: 3.5px solid #1e3a8a;
            overflow: visible;
        }

        /* Marco institucional interior de seguridad con micro-matiz */
        .cartel-sheet::before {
            content: '';
            position: absolute;
            inset: 6px;
            border: 1.5px dashed #cbd5e1;
            border-radius: 14px;
            pointer-events: none;
            z-index: 1;
        }

        /* Marca de agua institucional de fondo (sello central) */
        .cartel-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 440px;
            height: 440px;
            background: radial-gradient(circle, rgba(30, 58, 138, 0.035) 0%, rgba(2, 132, 199, 0.01) 70%, transparent 100%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cartel-watermark::after {
            content: 'ISPEB';
            font-family: 'Outfit', sans-serif;
            font-size: 8rem;
            font-weight: 900;
            color: rgba(30, 58, 138, 0.025);
            letter-spacing: 12px;
            user-select: none;
        }

        /* ── 1. CINTILLO SUPERIOR: 3 LOGOS OFICIALES ── */
        .cartel-top-strip {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 16px;
        }

        .strip-col-left {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
        }

        .strip-col-center {
            flex: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }

        .strip-col-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 0;
        }

        .logo-top-sgp {
            height: 56px;
            width: auto;
            max-width: 110px;
            object-fit: contain;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.1));
        }

        .logo-top-salud {
            height: 58px;
            width: auto;
            max-width: 275px;
            object-fit: contain;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.08));
        }

        .logo-top-gobe {
            height: 56px;
            width: auto;
            max-width: 175px;
            object-fit: contain;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.08));
        }

        /* ── 2. ENCABEZADO INSTITUCIONAL TIPOGRÁFICO DE ALTO IMPACTO ── */
        .cartel-inst-header {
            position: relative;
            z-index: 2;
            text-align: center;
            margin-bottom: 10px;
        }

        .inst-full-name {
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            font-size: 1.45rem;
            font-weight: 900;
            color: #0b1f4c;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            line-height: 1.22;
            margin-bottom: 6px;
            text-shadow: 0 1px 2px rgba(11, 31, 76, 0.08);
        }

        .inst-division-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .inst-division-badge {
            font-size: 0.85rem;
            font-weight: 800;
            color: #0369a1;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            background: #f0f9ff;
            border: 1.5px solid #bae6fd;
            padding: 4px 14px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .tag-official {
            background: #f8fafc;
            color: #1e3a8a;
            border: 1.5px solid #cbd5e1;
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* ── 3. TÍTULO HERO DEL CARTEL ── */
        .cartel-hero-title {
            position: relative;
            z-index: 2;
            text-align: center;
            margin: 6px 0 10px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fdf4;
            color: #059669;
            border: 1px solid #bbf7d0;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }

        .cartel-main-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 1.95rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }

        .cartel-sub-heading {
            font-size: 0.84rem;
            color: #475569;
            font-weight: 600;
            margin-top: 4px;
        }

        /* ── 4. CONTENEDOR DEL CÓDIGO QR ── */
        .cartel-qr-wrapper {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 8px 0 12px;
        }

        .qr-frame {
            padding: 10px;
            background: #ffffff;
            border: 2.5px solid #10b981;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.18);
            display: inline-block;
            position: relative;
        }

        .qr-frame::before, .qr-frame::after {
            display: none !important;
        }

        #qrcodeCanvas {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        #qrcodeCanvas img, #qrcodeCanvas canvas {
            display: block;
            border-radius: 8px;
            width: 215px;
            height: 215px;
        }

        .qr-scan-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 0.82rem;
            font-weight: 800;
            color: #059669;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* ── 5. GUÍA RÁPIDA EN 3 PASOS ── */
        .cartel-steps {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            background: rgba(248, 250, 252, 0.95);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 14px;
            backdrop-filter: blur(4px);
        }

        .step-item {
            text-align: center;
            padding: 4px 6px;
        }

        .step-num {
            width: 28px;
            height: 28px;
            background: #1e3a8a;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
            font-weight: 800;
            margin: 0 auto 6px;
        }

        .step-title {
            font-size: 0.88rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .step-desc {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            line-height: 1.35;
        }

        /* ── 6. PIE CON ENLACE Y VALIDACIÓN INSTITUCIONAL ── */
        .cartel-footer {
            position: relative;
            z-index: 2;
            border-top: 1.5px dashed #cbd5e1;
            padding-top: 14px;
            text-align: center;
        }

        .footer-chips {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .footer-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            padding: 5px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.4px;
            border: 1px solid #e2e8f0;
        }

        .footer-chip.chip-url {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
            font-weight: 800;
        }

        .footer-chip.chip-gps {
            background: #fefce8;
            color: #854d0e;
            border-color: #fef08a;
        }

        .footer-chip.chip-audit {
            background: #f0fdf4;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .footer-warning {
            font-size: 0.74rem;
            color: #64748b;
            font-weight: 600;
            line-height: 1.4;
            max-width: 620px;
            margin: 0 auto;
        }

        /* ── 7. RESPONSIVE MÓVIL (PANTALLA) ── */
        .show-mobile { display: none !important; }

        @media screen and (max-width: 640px) {
            body { padding: 12px 10px 30px !important; }
            .no-print-bar { padding: 8px 12px !important; gap: 8px !important; margin-bottom: 12px !important; }
            .btn-action { padding: 8px 12px !important; font-size: 0.8rem !important; }
            .hide-mobile { display: none !important; }
            .show-mobile { display: inline !important; }

            .cartel-sheet {
                padding: 14px 12px 16px !important;
                border-width: 2.5px !important;
                border-radius: 14px !important;
                max-width: 100% !important;
            }

            .cartel-sheet::before { inset: 4px !important; }
            .cartel-top-strip { gap: 6px !important; padding-bottom: 8px !important; }
            .logo-top-sgp { height: 28px !important; }
            .logo-top-salud { height: 30px !important; }
            .logo-top-gobe { height: 28px !important; }

            .inst-full-name { font-size: 0.94rem !important; }
            .cartel-main-heading { font-size: 1.35rem !important; }
            .cartel-sub-heading { font-size: 0.74rem !important; }

            #qrcodeCanvas img, #qrcodeCanvas canvas {
                width: 190px !important;
                height: 190px !important;
            }

            .cartel-steps {
                grid-template-columns: 1fr !important;
                gap: 6px !important;
                padding: 8px 10px !important;
            }

            .step-item {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                text-align: left !important;
                padding: 2px 0 !important;
            }

            .step-num {
                margin: 0 !important;
                flex-shrink: 0 !important;
                width: 20px !important;
                height: 20px !important;
                font-size: 0.7rem !important;
            }
        }

        /* ── 8. REGLAS ESTRICTAS DE IMPRESIÓN (UNA SOLA HOJA VERTICAL 100% GARANTIZADA) ── */
        @page {
            size: portrait;
            margin: 5mm;
        }

        @media print {
            *, *::before, *::after {
                box-sizing: border-box !important;
            }

            html, body {
                width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .cartel-sheet {
                box-shadow: none !important;
                border: 2px solid #1e3a8a !important;
                border-radius: 10px !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                max-height: none !important;
                min-height: 0 !important;
                padding: 4mm 6mm 3mm !important;
                margin: 0 auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                overflow: visible !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                box-sizing: border-box !important;
            }

            .cartel-sheet::before {
                border-radius: 8px !important;
                inset: 2mm !important;
            }

            .cartel-watermark {
                width: 260px !important;
                height: 260px !important;
            }
            .cartel-watermark::after {
                font-size: 4rem !important;
            }

            .cartel-top-strip {
                padding-bottom: 1.5mm !important;
                margin-bottom: 1mm !important;
                gap: 10px !important;
            }

            .logo-top-sgp {
                height: 28px !important;
                max-width: 70px !important;
            }

            .logo-top-salud {
                height: 30px !important;
                max-width: 160px !important;
            }

            .logo-top-gobe {
                height: 28px !important;
                max-width: 110px !important;
            }

            .inst-full-name {
                font-size: 0.95rem !important;
                line-height: 1.15 !important;
                margin-bottom: 0.5mm !important;
                font-weight: 800 !important;
            }

            .inst-division-row {
                gap: 4px !important;
                margin-top: 0.5mm !important;
            }

            .inst-division-badge, .tag-official {
                font-size: 0.62rem !important;
                padding: 1px 6px !important;
                font-weight: 700 !important;
            }

            .cartel-hero-title {
                margin: 1mm 0 !important;
            }

            .hero-badge {
                font-size: 0.58rem !important;
                padding: 1px 5px !important;
                margin-bottom: 0.5mm !important;
            }

            .cartel-main-heading {
                font-size: 1.25rem !important;
                letter-spacing: -0.2px !important;
                margin-bottom: 0.5mm !important;
                line-height: 1.0 !important;
            }

            .cartel-sub-heading {
                font-size: 0.65rem !important;
                line-height: 1.15 !important;
                margin-top: 0.5mm !important;
            }

            .cartel-qr-wrapper {
                margin: 1mm 0 1.5mm !important;
                padding: 0 !important;
            }

            .qr-frame {
                padding: 5px !important;
                border: 2px solid #10b981 !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                background: #ffffff !important;
            }

            .qr-frame::before, .qr-frame::after {
                display: none !important;
            }

            #qrcodeCanvas img, #qrcodeCanvas canvas {
                width: 110px !important;
                height: 110px !important;
                max-width: 110px !important;
                max-height: 110px !important;
                margin: 0 auto !important;
            }

            .qr-scan-label {
                font-size: 0.62rem !important;
                margin-top: 1mm !important;
                font-weight: 800 !important;
            }

            .cartel-steps {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 4px !important;
                padding: 3px 6px !important;
                margin-bottom: 1.5mm !important;
                border-radius: 6px !important;
            }

            .step-item {
                padding: 1px 3px !important;
                text-align: center !important;
                display: block !important;
            }

            .step-num {
                width: 16px !important;
                height: 16px !important;
                font-size: 0.60rem !important;
                margin: 0 auto 1px !important;
            }

            .step-title {
                font-size: 0.66rem !important;
                font-weight: 800 !important;
                display: block !important;
                margin-bottom: 0.5px !important;
            }

            .step-desc {
                font-size: 0.55rem !important;
                line-height: 1.1 !important;
                display: block !important;
            }

            .cartel-footer {
                border-top: 1px dashed #cbd5e1 !important;
                padding-top: 1mm !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .footer-chips {
                gap: 4px !important;
                margin-bottom: 1mm !important;
            }

            .footer-chip {
                font-size: 0.60rem !important;
                padding: 1px 6px !important;
                font-weight: 700 !important;
            }

            .footer-warning {
                font-size: 0.52rem !important;
                line-height: 1.15 !important;
            }
        }
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR DE ACCIÓN (SOLO PANTALLA) -->
    <div class="no-print-bar">
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="<?= URLROOT ?>/configuracion" class="btn-action btn-back">
                <i class="ti ti-arrow-left"></i> Volver a Configuración
            </a>
        <?php else: ?>
            <a href="<?= URLROOT ?>/auth/login" class="btn-action btn-back">
                <i class="ti ti-arrow-left"></i> Volver al Login
            </a>
        <?php endif; ?>
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="ti ti-printer"></i>
            <span class="hide-mobile">Imprimir Cartel (Carta / A4 — 1 Hoja)</span>
            <span class="show-mobile">Imprimir Cartel</span>
        </button>
    </div>

    <!-- HOJA IMPRIMIBLE DEL CARTEL OFICIAL (DISEÑO INSTITUCIONAL ULTRA PREMIUM CON FONDO DE SEGURIDAD) -->
    <div class="cartel-sheet">
        
        <!-- Marca de agua de seguridad institucional en el fondo -->
        <div class="cartel-watermark"></div>

        <!-- 1. CINTILLO SUPERIOR: 3 LOGOS OFICIALES -->
        <!-- Izquierda: SGP | Centro: Salud Bolívar | Derecha: Gobernación -->
        <div class="cartel-top-strip">
            <div class="strip-col-left">
                <img src="<?= URLROOT ?>/img/logo.png"
                     alt="SGP - Sistema de Gestión de Pasantes"
                     class="logo-top-sgp"
                     onerror="this.style.display='none'">
            </div>
            <div class="strip-col-center">
                <img src="<?= URLROOT ?>/img/cintillo-salud.png"
                     alt="Salud Bolívar - ISPEB"
                     class="logo-top-salud"
                     onerror="this.style.display='none'">
            </div>
            <div class="strip-col-right">
                <img src="<?= URLROOT ?>/img/gobe.png"
                     alt="Gobernación del Estado Bolívar"
                     class="logo-top-gobe"
                     onerror="this.style.display='none'">
            </div>
        </div>

        <!-- 2. ENCABEZADO INSTITUCIONAL TIPOGRÁFICO DE ALTO IMPACTO -->
        <div class="cartel-inst-header">
            <h2 class="inst-full-name">Instituto de Salud Pública del Estado Bolívar</h2>
            <div class="inst-division-row">
                <span class="inst-division-badge">
                    <i class="ti ti-cpu"></i> División de Soporte Técnico
                </span>
                <span class="tag-official">
                    <i class="ti ti-clock-check" style="color:#2563eb;"></i> Registro de Jornada Diaria
                </span>
            </div>
        </div>

        <!-- 3. TÍTULO HERO DEL CARTEL -->
        <div class="cartel-hero-title">
            <div class="hero-badge">
                <i class="ti ti-device-mobile-message"></i> Módulo Kiosco Digital
            </div>
            <h1 class="cartel-main-heading">REGISTRO DE ASISTENCIA</h1>
            <p class="cartel-sub-heading">Escanea el código con tu celular para acceder al sistema y registrar tu jornada</p>
        </div>

        <!-- 4. CÓDIGO QR GENERADO (CON BASE SÓLIDA PARA MÁXIMA LECTURA) -->
        <div class="cartel-qr-wrapper">
            <div class="qr-frame">
                <div id="qrcodeCanvas"></div>
            </div>
            <div class="qr-scan-label">
                <i class="ti ti-scan"></i> Escanear con Cámara Móvil
            </div>
        </div>

        <!-- 5. GUÍA RÁPIDA EN 3 PASOS -->
        <div class="cartel-steps">
            <div class="step-item">
                <div class="step-num">1</div>
                <div class="step-title">Abre tu Cámara</div>
                <div class="step-desc">Apunta al código QR desde tu teléfono inteligente</div>
            </div>
            <div class="step-item">
                <div class="step-num">2</div>
                <div class="step-title">Accede al Sistema</div>
                <div class="step-desc">Abre el portal de inicio de sesión en tu móvil</div>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <div class="step-title">Confirma tu Jornada</div>
                <div class="step-desc">Inicia sesión o pulsa "Marcar Asistencia"</div>
            </div>
        </div>

        <!-- 6. PIE CON ENLACE OFICIAL Y VALIDACIÓN EN SEDE -->
        <div class="cartel-footer">
            <div class="footer-chips">
                <div class="footer-chip chip-url">
                    <i class="ti ti-world" style="color:#2563eb;"></i> Portal Oficial: <?= htmlspecialchars($data['urlBase'] ?? rtrim(URLROOT, '/')) ?>
                </div>
                <div class="footer-chip chip-gps">
                    <i class="ti ti-map-pin-check" style="color:#854d0e;"></i> Ubicación Presencial Requerida
                </div>
                <div class="footer-chip chip-audit">
                    <i class="ti ti-shield-check" style="color:#16a34a;"></i> Registro Oficial Auditado
                </div>
            </div>
            <p class="footer-warning">
                Documento de control emitido por la División de Soporte Técnico del ISPEB. El registro de asistencia mediante este punto está auditado y restringido exclusivamente al personal y pasantes dentro de la sede física.
            </p>
        </div>

    </div><!-- /cartel-sheet -->

    <script>
    // Generar el código QR de alta resolución con qrcode.min.js
    document.addEventListener('DOMContentLoaded', function() {
        const qrUrl = <?= json_encode($data['qrUrl'], JSON_UNESCAPED_SLASHES) ?>;
        
        new QRCode(document.getElementById("qrcodeCanvas"), {
            text: qrUrl,
            width: 240,
            height: 240,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    });
    </script>
</body>
</html>
