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
            background: #0284c7;
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
            color: #0284c7;
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
                <span class="legal-badge">Protección de Datos</span>
            </div>
            <h1 class="legal-title">Política de Privacidad y Tratamiento de Datos</h1>
            <p class="legal-subtitle">Instituto de Salud Pública del Estado Bolívar (ISPEB)</p>
        </div>

        <div class="legal-tabs">
            <a href="<?= URLROOT ?>/legal/terminos" class="legal-tab">
                <i class="ti ti-file-text"></i> Términos de Servicio
            </a>
            <a href="<?= URLROOT ?>/legal/privacidad" class="legal-tab active">
                <i class="ti ti-shield-lock"></i> Política de Privacidad
            </a>
        </div>

        <div class="legal-body">
            <div class="legal-section">
                <h3><i class="ti ti-database"></i> 1. Información que Recopilamos</h3>
                <p>El Sistema de Gestión de Pasantes (SGP) procesa exclusivamente la información estrictamente necesaria para el cumplimiento académico, control de asistencia y auditoría del proceso de pasantías:</p>
                <ul>
                    <li>**Datos de Identidad:** Cédula de Identidad, nombres, apellidos, correo electrónico institucional/personal y número telefónico.</li>
                    <li>**Datos Académicos:** Universidad o institución de origen, carrera o especialidad, período asignado y evaluaciones.</li>
                    <li>**Datos de Jornada y Auditoría:** Fecha, hora exacta de entrada/salida, dirección IP de marcaje, modelo de dispositivo y navegador.</li>
                    <li>**Datos Biométricos (WebAuthn):** El sistema **nunca almacena huellas dactilares ni imágenes faciales**. La autenticación biométrica genera una firma criptográfica asimétrica que valida la identidad en el chip seguro del teléfono sin transferir datos biológicos al servidor.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3><i class="ti ti-target"></i> 2. Finalidad del Tratamiento</h3>
                <p>La información recopilada se destina de forma exclusiva a:</p>
                <ul>
                    <li>Verificar el cumplimiento de horas prácticas exigidas por el plan de pasantías y la legislación educativa.</li>
                    <li>Garantizar la transparencia y prevenir irregularidades en el registro de asistencia presencial.</li>
                    <li>Enviar notificaciones institucionales relevantes (publicación de exámenes rápidos, alertas de seguridad de cuenta y restablecimiento de claves).</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3><i class="ti ti-lock"></i> 3. Seguridad y Confidencialidad</h3>
                <p>El ISPEB implementa rigurosas medidas de seguridad técnica:</p>
                <ul>
                    <li>Cifrado de datos en tránsito mediante protocolos seguros HTTPS / TLS.</li>
                    <li>Almacenamiento de contraseñas bajo algoritmos criptográficos irreversibles (Bcrypt / Argon2).</li>
                    <li>Tokens de seguridad CSRF y protección activa contra ataques de inyección y suplantación.</li>
                    <li>Acceso restringido por roles y permisos auditados en bitácora permanente.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3><i class="ti ti-share-off"></i> 4. No Divulgación a Terceros</h3>
                <p>Bajo ninguna circunstancia los datos registrados en el SGP serán cedidos, comercializados o transferidos a entidades privadas ajenas al proceso académico e institucional del Instituto de Salud Pública del Estado Bolívar.</p>
            </div>
        </div>

        <div class="legal-footer-info">
            <span>Coordinación de Docencia e Investigación / Telemática — ISPEB</span>
            <span>Garantía de Confidencialidad Institucional</span>
        </div>
    </div>

</body>
</html>
