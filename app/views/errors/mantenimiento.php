<?php
/**
 * Pantalla Institucional: Modo Mantenimiento Programado — SGP
 * Mostrada a usuarios cuando el SuperAdmin activa el switch de mantenimiento
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento Programado — SGP ISPEB</title>
    <link rel="stylesheet" href="<?= URLROOT ?>/css/fonts.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #090e1a 0%, #0f172a 50%, #162660 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #ffffff;
        }
        .maint-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            border-radius: 28px;
            padding: 44px 38px;
            max-width: 580px;
            width: 100%;
            text-align: center;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.06);
            position: relative;
            overflow: hidden;
        }
        .maint-card::before {
            content: '';
            position: absolute;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .maint-icon-wrap {
            width: 84px;
            height: 84px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.25), rgba(56, 189, 248, 0.15));
            border: 1.5px solid rgba(56, 189, 248, 0.4);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: #38bdf8;
            font-size: 2.5rem;
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.25);
            animation: pulse-icon 3s infinite ease-in-out;
        }
        @keyframes pulse-icon {
            0%, 100% { transform: scale(1); box-shadow: 0 12px 28px rgba(37, 99, 235, 0.25); }
            50% { transform: scale(1.05); box-shadow: 0 16px 36px rgba(56, 189, 248, 0.4); }
        }
        .maint-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 5px 14px;
            border-radius: 50px;
            margin-bottom: 16px;
        }
        .maint-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        .maint-desc {
            font-size: 0.94rem;
            color: #94a3b8;
            line-height: 1.65;
            margin-bottom: 28px;
        }
        .maint-info-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 28px;
            text-align: left;
            font-size: 0.84rem;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .maint-info-box i {
            font-size: 1.6rem;
            color: #38bdf8;
            flex-shrink: 0;
        }
        .maint-btn-reload {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 14px;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
        }
        .maint-btn-reload:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.5);
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .maint-footer {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.75rem;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="maint-card">
    <div class="maint-icon-wrap">
        <i class="ti ti-tool"></i>
    </div>

    <div class="maint-badge">
        <i class="ti ti-bolt"></i> Labores de Infraestructura
    </div>

    <h1 class="maint-title">Mantenimiento Programado</h1>

    <p class="maint-desc">
        <?= htmlspecialchars($mensaje_mantenimiento ?? 'El Sistema de Gestión de Pasantías (SGP) se encuentra en labores programadas de mantenimiento y actualización de infraestructura. En breve estaremos de vuelta.') ?>
    </p>

    <div class="maint-info-box">
        <i class="ti ti-shield-check"></i>
        <div>
            <strong>Tus datos están protegidos y respaldados.</strong><br>
            El servicio se restablecerá automáticamente al finalizar la optimización del servidor.
        </div>
    </div>

    <a href="<?= URLROOT ?>/auth/login" class="maint-btn-reload" onclick="location.reload(); return false;">
        <i class="ti ti-refresh"></i>
        Verificar Disponibilidad
    </a>

    <div class="maint-footer">
        Dirección de Telemática &middot; Instituto de Salud Pública del Estado Bolívar &middot; SGP v2.0
    </div>
</div>

</body>
</html>
