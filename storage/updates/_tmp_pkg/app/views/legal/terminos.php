<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png">
    
    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0b1329;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 16px 50px;
        }
        .legal-wrapper {
            width: 100%;
            max-width: 860px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            border: 2px solid #1e3a8a;
            overflow: hidden;
        }
        .legal-header {
            background: linear-gradient(135deg, #0f1d4a 0%, #1e3a8a 100%);
            color: white;
            padding: 30px 32px 24px;
            position: relative;
        }
        .legal-top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        .btn-return {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s ease;
        }
        .btn-return:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(-2px);
        }
        .legal-badge {
            background: #10b981;
            color: white;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 999px;
            letter-spacing: 0.5px;
        }
        .legal-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 6px;
        }
        .legal-subtitle {
            font-size: 0.90rem;
            opacity: 0.88;
            font-weight: 500;
        }
        .legal-tabs {
            display: flex;
            gap: 10px;
            padding: 16px 32px 0;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .legal-tab {
            padding: 10px 18px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }
        .legal-tab.active {
            color: #1e3a8a;
            border-bottom-color: #1e3a8a;
        }
        .legal-body {
            padding: 32px;
            line-height: 1.7;
            font-size: 0.95rem;
            color: #334155;
        }
        .legal-section {
            margin-bottom: 28px;
        }
        .legal-section h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            color: #0f1d4a;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legal-section h3 i {
            color: #2563eb;
        }
        .legal-section p {
            margin-bottom: 10px;
        }
        .legal-section ul {
            margin-left: 20px;
            margin-bottom: 10px;
        }
        .legal-section li {
            margin-bottom: 6px;
        }
        .legal-footer-info {
            background: #f1f5f9;
            padding: 18px 32px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.80rem;
            color: #64748b;
            font-weight: 600;
            flex-wrap: wrap;
            gap: 10px;
        }
    </style>
</head>
<body>

    <div class="legal-wrapper">
        <div class="legal-header">
            <div class="legal-top-nav">
                <a href="<?= URLROOT ?>/auth/login" class="btn-return">
                    <i class="ti ti-arrow-left"></i> Volver al Login
                </a>
                <span class="legal-badge">Documento Oficial</span>
            </div>
            <h1 class="legal-title">Términos y Condiciones de Uso</h1>
            <p class="legal-subtitle">Sistema de Gestión de Pasantes (SGP) — Instituto de Salud Pública del Estado Bolívar (ISPEB)</p>
        </div>

        <div class="legal-tabs">
            <a href="<?= URLROOT ?>/legal/terminos" class="legal-tab active">
                <i class="ti ti-file-text"></i> Términos de Servicio
            </a>
            <a href="<?= URLROOT ?>/legal/privacidad" class="legal-tab">
                <i class="ti ti-shield-lock"></i> Política de Privacidad
            </a>
        </div>

        <div class="legal-body">
            <div class="legal-section">
                <h3><i class="ti ti-check"></i> 1. Aceptación de los Términos</h3>
                <p>Al acceder, registrarse o utilizar el Sistema de Gestión de Pasantes (SGP) del Instituto de Salud Pública del Estado Bolívar (ISPEB), el usuario (pasante, tutor académico, tutor institucional o administrador) declara haber leído, comprendido y aceptado en su totalidad las disposiciones contenidas en este documento normativo.</p>
            </div>

            <div class="legal-section">
                <h3><i class="ti ti-building-hospital"></i> 2. Naturaleza del Sistema y Ámbito de Aplicación</h3>
                <p>El SGP es una plataforma institucional desarrollada bajo la supervisión de la División de Soporte Técnico y Telemática del ISPEB, destinada exclusivamente a:</p>
                <ul>
                    <li>Control, auditoría y seguimiento de la jornada de pasantías y servicio comunitario.</li>
                    <li>Registro de asistencias y permanencia en la sede física institucional.</li>
                    <li>Gestión de evaluaciones académicas, actividades y exámenes rápidos.</li>
                    <li>Emisión de reportes oficiales de culminación de pasantías.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3><i class="ti ti-map-pin"></i> 3. Registro de Asistencia y Presencialidad en Sede</h3>
                <p>El registro de entrada y salida mediante el Módulo Kiosco Digital o código QR oficial exige la **presencia física obligatoria** del pasante en las instalaciones asignadas del ISPEB. Queda terminantemente prohibido:</p>
                <ul>
                    <li>El escaneo remoto del código QR o registro desde ubicaciones ajenas a la sede física.</li>
                    <li>La suplantación de identidad o marcado en nombre de otro pasante.</li>
                    <li>El uso de herramientas de alteración de red, proxies no autorizados o falsificación de geolocalización.</li>
                </ul>
                <p>Cualquier infracción será registrada en la bitácora de auditoría y reportada a la Coordinación de Docencia e Investigación para las sanciones administrativas o académicas a que hubiere lugar.</p>
            </div>

            <div class="legal-section">
                <h3><i class="ti ti-fingerprint"></i> 4. Autenticación y Credenciales Personales</h3>
                <p>Cada usuario es responsable exclusivo de la confidencialidad de su contraseña, PIN de seguridad y claves biométricas vinculadas a su dispositivo. Las sesiones iniciadas mediante biometría o credenciales personales se presumen realizadas por el titular de la cuenta.</p>
            </div>

            <div class="legal-section">
                <h3><i class="ti ti-alert-triangle"></i> 5. Modificaciones y Actualizaciones</h3>
                <p>El ISPEB se reserva el derecho de actualizar los presentes términos para adecuarse a nuevas directrices institucionales, de ciberseguridad o de la administración pública del Estado Bolívar.</p>
            </div>
        </div>

        <div class="legal-footer-info">
            <span>División de Soporte Técnico — ISPEB © <?= date('Y') ?></span>
            <span>Versión 2.0 (Normativa Oficial)</span>
        </div>
    </div>

</body>
</html>
