<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosco — Fuera de Servicio</title>
    <link rel="icon" type="image/png" href="<?= URLROOT ?>/img/favicon.png">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/css/fonts.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #172554 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 28px;
            padding: 52px 44px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(16px);
            animation: fadeUp .5s cubic-bezier(0.16,1,0.3,1) both;
        }

        .icon-ring {
            width: 88px; height: 88px;
            border-radius: 50%;
            background: rgba(239,68,68,0.15);
            border: 2px solid rgba(239,68,68,0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .icon-ring i { font-size: 2.4rem; color: #f87171; }

        h1 {
            color: white;
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }
        p {
            color: rgba(255,255,255,0.6);
            font-size: 0.92rem;
            line-height: 1.65;
            margin-bottom: 8px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 50px;
            padding: 5px 16px;
            color: #fca5a5;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        .hint {
            margin-top: 32px;
            padding: 14px 18px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.08);
            font-size: 0.8rem;
            color: rgba(255,255,255,0.45);
            line-height: 1.55;
        }

        .brand {
            margin-top: 36px;
            color: rgba(255,255,255,0.3);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: .3px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-ring">
            <i class="ti ti-device-desktop-off"></i>
        </div>

        <div class="badge">
            <i class="ti ti-alert-triangle" style="font-size:.85rem;"></i>
            Servicio suspendido
        </div>

        <h1>Kiosco temporalmente<br>fuera de servicio</h1>
        <p>El registro de asistencia está deshabilitado en este momento por el administrador del sistema.</p>
        <p>Por favor intenta más tarde o comunícate con tu supervisor.</p>

        <div class="hint">
            <i class="ti ti-info-circle" style="margin-right:5px;"></i>
            Si necesitas registrar tu asistencia con urgencia, contacta directamente al personal de RRHH o a tu tutor asignado.
        </div>
    </div>

    <div class="brand">SGP — Sistema de Gestión de Pasantías</div>
</body>
</html>
