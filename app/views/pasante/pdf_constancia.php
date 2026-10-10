<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Pasantía</title>
    <style>
        @page  { margin: 1.8cm 2cm; }
        body   { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #111; line-height: 1.6; }
        h1     { font-size: 15px; text-align: center; text-transform: uppercase; letter-spacing: 1px; color: #162660; margin: 20px 0 4px; }
        .sub   { text-align: center; font-size: 9px; color: #555; margin-bottom: 18px; }
        .body-text { font-size: 11px; text-align: justify; margin-bottom: 16px; line-height: 1.8; }
        .highlight { font-weight: bold; text-transform: uppercase; }
        .ficha { width: 100%; border-collapse: collapse; font-size: 9.5px; margin: 14px 0; }
        .ficha td { padding: 6px 10px; border: 1px solid #ddd; vertical-align: middle; }
        .ficha .lbl { background: #eef0f7; font-weight: bold; text-transform: uppercase; width: 22%; font-size: 8.5px; color: #162660; }
        .firma-row { display: table; width: 100%; margin-top: 50px; }
        .firma-col { display: table-cell; width: 50%; text-align: center; padding: 0 20px; }
        .firma-line { border-top: 1px solid #333; margin-bottom: 4px; }
        .firma-name { font-weight: bold; font-size: 10px; }
        .firma-title{ font-size: 9px; color: #555; }
        .footer { border-top: 1px solid #ccc; margin-top: 30px; padding-top: 6px; text-align: center; font-size: 8px; color: #888; }
        .sello-area { text-align: center; margin-top: 40px; color: #ccc; font-size: 9px; }
    </style>
</head>
<body>

    <?php include '../app/views/reportes/comunes/header.php'; ?>

    <h1>Constancia de Pasantía</h1>
    <div class="sub">Sistema de Gestión de Pasantes — Dirección de Telemática &nbsp;|&nbsp; Generado el <?= date('d/m/Y \a \l\a\s H:i') ?></div>

    <?php
    $nombres    = htmlspecialchars(strtoupper(trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''))));
    $cedula     = htmlspecialchars($pasante->cedula ?? '—');
    $depto      = htmlspecialchars($pasante->departamento ?? 'Sin asignar');
    $tutor      = htmlspecialchars($pasante->tutor_nombre ?? 'Sin tutor asignado');
    $tutorCi    = htmlspecialchars($pasante->tutor_cedula ?? '—');
    $horas      = (int)($pasante->horas_meta ?? 1440);
    $estado     = htmlspecialchars($pasante->estado_pasantia ?? '—');

    $fechaInicio = '—';
    $fechaFin    = '—';
    if (!empty($pasante->fecha_inicio_pasantia)) {
        $fechaInicio = date('d/m/Y', strtotime($pasante->fecha_inicio_pasantia));
    }
    if (!empty($pasante->fecha_fin_estimada)) {
        $fechaFin = date('d/m/Y', strtotime($pasante->fecha_fin_estimada));
    }
    ?>

    <p class="body-text">
        Quien suscribe, en su carácter de responsable del Sistema de Gestión de Pasantes (SGP) de la
        <strong>Dirección de Telemática</strong>, hace constar por medio de la presente que el/la ciudadano(a)
        <span class="highlight"><?= $nombres ?></span>, titular de la Cédula de Identidad Nro.
        <span class="highlight"><?= $cedula ?></span>,
        se encuentra realizando pasantías en la institución bajo la supervisión del Departamento de
        <span class="highlight"><?= $depto ?></span>,
        cumpliendo una carga horaria de <span class="highlight"><?= $horas ?> horas</span>,
        con fecha de inicio el <span class="highlight"><?= $fechaInicio ?></span>
        <?php if ($fechaFin !== '—'): ?>
        y fecha de culminación estimada el <span class="highlight"><?= $fechaFin ?></span>
        <?php endif; ?>.
    </p>

    <p class="body-text">
        La presente constancia se expide a petición de la parte interesada, en <?= htmlspecialchars(date('d')) ?> días
        del mes de <?= htmlspecialchars(strftime('%B', time()) ?: date('F')) ?> de <?= date('Y') ?>,
        en Ciudad Bolívar, Estado Bolívar.
    </p>

    <h2 style="font-size:10px; text-transform:uppercase; border-bottom:1px solid #999; padding-bottom:3px; margin:16px 0 8px; color:#162660;">Datos del Pasante</h2>
    <table class="ficha" cellspacing="0" cellpadding="0">
        <tr>
            <td class="lbl">Nombre completo</td>
            <td><?= $nombres ?></td>
            <td class="lbl">Cédula</td>
            <td><?= $cedula ?></td>
        </tr>
        <tr>
            <td class="lbl">Departamento</td>
            <td><?= $depto ?></td>
            <td class="lbl">Estado</td>
            <td><?= $estado ?></td>
        </tr>
        <tr>
            <td class="lbl">Tutor Asignado</td>
            <td><?= $tutor ?></td>
            <td class="lbl">C.I. Tutor</td>
            <td><?= $tutorCi ?></td>
        </tr>
        <tr>
            <td class="lbl">Fecha Inicio</td>
            <td><?= $fechaInicio ?></td>
            <td class="lbl">Fecha Fin Est.</td>
            <td><?= $fechaFin ?></td>
        </tr>
        <tr>
            <td class="lbl">Horas requeridas</td>
            <td colspan="3"><?= $horas ?> horas académicas</td>
        </tr>
    </table>

    <!-- Firmas -->
    <div class="firma-row">
        <div class="firma-col">
            <div style="height:60px;"></div>
            <div class="firma-line"></div>
            <div class="firma-name"><?= $tutor ?></div>
            <div class="firma-title">Tutor de Pasantía</div>
        </div>
        <div class="firma-col">
            <div class="sello-area">[Sello Institucional]</div>
            <div class="firma-line"></div>
            <div class="firma-name">Dirección de Telemática</div>
            <div class="firma-title">Coordinación de Pasantías</div>
        </div>
    </div>

    <div class="footer">
        Documento generado automáticamente por el SGP · <?= date('d/m/Y H:i:s') ?> ·
        Válido solo con sello y firma original
    </div>

</body>
</html>
