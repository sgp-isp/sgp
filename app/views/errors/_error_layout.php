<?php
/**
 * Layout compartido para todas las páginas de error del SGP.
 * Variables esperadas:
 *   $errCode    — número del error (404, 403, 500…)
 *   $errIcon    — clase Tabler Icon (ti-compass-off, ti-lock, ti-server-off…)
 *   $errAccent  — color HEX del acento (#2563eb, #dc2626, #d97706…)
 *   $errBg      — color HEX fondo del icono (#eff6ff, #fef2f2…)
 *   $errTitle   — título principal
 *   $errSub     — subtítulo / descripción
 *   $errHint    — texto de ayuda secundario (opcional)
 *   $errActions — array de botones: [['label'=>'…','href'=>'…','primary'=>true/false], …]
 */
/** @var string $_url */
/** @var string $_homeLink */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $errCode ?> — SGP</title>
    <link rel="icon" type="image/png" href="<?= $_url ?>/img/favicon.png">
    <link rel="stylesheet" href="<?= $_url ?>/css/fonts.css">
    <link rel="stylesheet" href="<?= $_url ?>/css/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', 'Inter', system-ui, sans-serif;
            background: #F8FAFD;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* ── Strip superior con gradiente ── */
        .err-strip {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #172554 0%, #1e3a8a 40%, <?= $errAccent ?> 100%);
        }

        /* ── Card principal ── */
        .err-card {
            background: white;
            border: 1px solid #DDE2F0;
            border-radius: 24px;
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.08), 0 8px 16px -8px rgba(0,0,0,0.04);
            max-width: 520px;
            width: 100%;
            overflow: hidden;
            animation: errIn .5s cubic-bezier(0.16,1,0.3,1) both;
        }

        /* ── Header con gradiente oscuro ── */
        .err-header {
            background: linear-gradient(135deg, #172554 0%, #1e3a8a 55%, <?= $errAccent ?> 100%);
            padding: 36px 32px 32px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .err-header::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            pointer-events: none;
        }
        .err-header::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 150px; height: 150px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Número de error grande — protagonista centrado */
        .err-code {
            font-size: 8rem;
            font-weight: 900;
            color: rgba(255,255,255,0.22);
            line-height: 1;
            letter-spacing: -6px;
            position: relative;
            z-index: 1;
            display: block;
            margin-bottom: 6px;
            pointer-events: none;
            white-space: nowrap;
        }

        /* Ícono pequeño — esquina superior derecha */
        .err-icon-wrap {
            position: absolute;
            top: 14px; right: 16px;
            z-index: 2;
            width: 36px; height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            border: 1.5px solid rgba(255,255,255,0.22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .err-icon-wrap i {
            font-size: 1rem;
            color: rgba(255,255,255,0.85);
        }
        .err-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 50px;
            padding: 4px 14px;
            color: rgba(255,255,255,0.85);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-top: 8px;
        }

        /* ── Cuerpo ── */
        .err-body {
            padding: 32px;
            text-align: center;
        }
        .err-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0D1424;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .err-sub {
            font-size: 0.88rem;
            color: #7480A0;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .err-hint {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #F8FAFD;
            border: 1px solid #DDE2F0;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.78rem;
            color: #7480A0;
            margin: 12px 0 0;
        }

        /* ── Divisor ── */
        .err-divider {
            height: 1px;
            background: #DDE2F0;
            margin: 0 32px;
        }

        /* ── Acciones ── */
        .err-actions {
            padding: 22px 32px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 22px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all .25s cubic-bezier(0.16,1,0.3,1);
            border: none;
        }
        .err-btn-primary {
            background: <?= $errAccent ?>;
            color: white;
            box-shadow: 0 4px 14px <?= $errAccent ?>55;
        }
        .err-btn-primary:hover {
            opacity: .9;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px <?= $errAccent ?>44;
            color: white;
            text-decoration: none;
        }
        .err-btn-secondary {
            background: white;
            color: #0D1424;
            border: 1.5px solid #DDE2F0;
        }
        .err-btn-secondary:hover {
            background: #F8FAFD;
            border-color: #b0bacc;
            transform: translateY(-2px);
            color: #0D1424;
            text-decoration: none;
        }

        /* ── Branding footer ── */
        .err-brand {
            margin-top: 28px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7480A0;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .err-brand-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e3a8a, <?= $errAccent ?>);
        }

        @keyframes errIn {
            from { opacity: 0; transform: translateY(20px) scale(.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="err-strip"></div>

    <div class="err-card">
        <!-- Header -->
        <div class="err-header">
            <div class="err-icon-wrap">
                <i class="ti <?= $errIcon ?>"></i>
            </div>
            <span class="err-code"><?= $errCode ?></span>
            <div class="err-badge">
                <i class="ti ti-alert-circle" style="font-size:.85rem;"></i>
                Error <?= $errCode ?>
            </div>
        </div>

        <!-- Cuerpo -->
        <div class="err-body">
            <h1 class="err-title"><?= htmlspecialchars($errTitle) ?></h1>
            <p class="err-sub"><?= htmlspecialchars($errSub) ?></p>
            <?php if (!empty($errHint)): ?>
            <div class="err-hint">
                <i class="ti ti-info-circle" style="font-size:.9rem;color:<?= $errAccent ?>;"></i>
                <?= htmlspecialchars($errHint) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="err-divider"></div>

        <!-- Acciones -->
        <div class="err-actions">
            <?php foreach ($errActions as $action): ?>
            <a href="<?= htmlspecialchars($action['href']) ?>"
               class="err-btn <?= !empty($action['primary']) ? 'err-btn-primary' : 'err-btn-secondary' ?>">
                <?php if (!empty($action['icon'])): ?>
                <i class="ti <?= $action['icon'] ?>"></i>
                <?php endif; ?>
                <?= htmlspecialchars($action['label']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Branding -->
    <div class="err-brand">
        <div class="err-brand-dot"></div>
        SGP — Sistema de Gestión de Pasantías
    </div>
</body>
</html>
