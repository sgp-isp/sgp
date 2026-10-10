<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nómina de Pasantes</title>
    <style>
        @page { margin: 1.5cm 1.8cm; }
        body  { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #111; line-height: 1.4; }
        h1    { font-size: 13px; text-align: center; text-transform: uppercase; margin: 0 0 2px; letter-spacing: 0.5px; }
        .sub  { text-align: center; font-size: 8.5px; color: #555; margin-bottom: 14px; }
        h2    { font-size: 9px; text-transform: uppercase; background: #162660; color: #fff;
                padding: 4px 8px; margin: 14px 0 0; letter-spacing: 0.4px; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 0; }
        th    { background: #e8eef6; padding: 4px 5px; text-align: left; border: 1px solid #bcc8da;
                font-size: 7.5px; text-transform: uppercase; color: #162660; }
        td    { padding: 3.5px 5px; border: 1px solid #dde4ef; vertical-align: middle; }
        .alt  { background: #f5f8ff; }
        .c    { text-align: center; }
        .b    { font-weight: bold; }
        .r    { text-align: right; }
        .pill { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .p-activo    { background: #dcfce7; color: #166534; }
        .p-finaliz   { background: #f1f5f9; color: #475569; }
        .p-suspendid { background: #fef9c3; color: #92400e; }
        .kpi-row  { display: table; width: 100%; margin: 10px 0 14px; }
        .kpi-cell { display: table-cell; text-align: center; padding: 6px 0;
                    border: 1px solid #dde4ef; background: #f8faff; }
        .kpi-num  { font-size: 15px; font-weight: bold; color: #162660; display: block; }
        .kpi-lbl  { font-size: 7px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .foot     { border-top: 1px solid #bbb; margin-top: 14px; padding-top: 5px;
                    text-align: center; font-size: 7.5px; color: #777; }
        .no-data  { text-align: center; padding: 12px; color: #888; font-style: italic; }
    </style>
</head>
<body>

    <?php include 'comunes/header.php'; ?>

    <h1>Nómina General de Pasantes</h1>
    <div class="sub">
        <?= htmlspecialchars($subtitulo_pdf) ?>
        &nbsp;·&nbsp; Generado el <?= date('d/m/Y \a \l\a\s H:i') ?>
    </div>

    <?php
    // ── KPIs globales ──────────────────────────────────────────────────────────
    $totalPasantes   = count($pasantes);
    $totalActivos    = 0; $totalFinalizados = 0;
    $totalPresentes  = 0; $totalAusentes    = 0; $totalJust = 0;

    foreach ($pasantes as $p) {
        $estado = strtolower($p->estado_pasantia ?? '');
        if ($estado === 'activo')      $totalActivos++;
        elseif ($estado === 'finalizado') $totalFinalizados++;
        $totalPresentes += (int)($p->presentes   ?? 0);
        $totalAusentes  += (int)($p->ausentes    ?? 0);
        $totalJust      += (int)($p->justificados ?? 0);
    }
    $totalDias = $totalPresentes + $totalAusentes + $totalJust;
    $pctGlobal = $totalDias > 0 ? round($totalPresentes / $totalDias * 100) : 0;
    ?>

    <table style="margin-bottom:12px;">
        <tr>
            <td class="c" style="border:1px solid #dde4ef;background:#f8faff;padding:6px 0;width:20%">
                <span style="font-size:15px;font-weight:bold;color:#162660;display:block;"><?= $totalPasantes ?></span>
                <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Total Pasantes</span>
            </td>
            <td class="c" style="border:1px solid #dde4ef;background:#f8faff;padding:6px 0;width:20%">
                <span style="font-size:15px;font-weight:bold;color:#166534;display:block;"><?= $totalActivos ?></span>
                <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Activos</span>
            </td>
            <td class="c" style="border:1px solid #dde4ef;background:#f8faff;padding:6px 0;width:20%">
                <span style="font-size:15px;font-weight:bold;color:#475569;display:block;"><?= $totalFinalizados ?></span>
                <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Finalizados</span>
            </td>
            <td class="c" style="border:1px solid #dde4ef;background:#f8faff;padding:6px 0;width:20%">
                <span style="font-size:15px;font-weight:bold;color:#059669;display:block;"><?= $totalPresentes ?></span>
                <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Total Presencias</span>
            </td>
            <td class="c" style="border:1px solid #dde4ef;background:#f8faff;padding:6px 0;width:20%">
                <span style="font-size:15px;font-weight:bold;color:#162660;display:block;"><?= $pctGlobal ?>%</span>
                <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Asistencia Global</span>
            </td>
        </tr>
    </table>

    <?php if (empty($pasantes)): ?>
        <p class="no-data">No hay pasantes registrados para el año <?= $anio ?>.</p>
    <?php else: ?>

    <?php
    // ── Agrupar por departamento ───────────────────────────────────────────────
    $porDepto = [];
    foreach ($pasantes as $p) {
        $dep = $p->departamento ?? 'Sin Departamento';
        $porDepto[$dep][] = $p;
    }
    ?>

    <?php foreach ($porDepto as $deptoNombre => $lista): ?>

        <h2><?= htmlspecialchars($deptoNombre) ?> (<?= count($lista) ?> pasante<?= count($lista) !== 1 ? 's' : '' ?>)</h2>

        <table cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th width="3%"  class="c">N°</th>
                    <th width="9%">Cédula</th>
                    <th width="22%">Apellidos, Nombres</th>
                    <th width="16%">Institución</th>
                    <th width="8%"  class="c">Inicio</th>
                    <th width="8%"  class="c">Fin</th>
                    <th width="6%"  class="c">Estado</th>
                    <th width="5%"  class="c">Pres.</th>
                    <th width="5%"  class="c">Aus.</th>
                    <th width="5%"  class="c">Just.</th>
                    <th width="7%"  class="c">% Asist.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $i => $p):
                    $nombre    = htmlspecialchars(ucwords(strtolower(trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')))));
                    $cedula    = htmlspecialchars($p->cedula       ?? '—');
                    $inst      = htmlspecialchars($p->institucion  ?? '—');
                    $inicio    = $p->fecha_inicio ? date('d/m/Y', strtotime($p->fecha_inicio)) : '—';
                    $fin       = $p->fecha_fin    ? date('d/m/Y', strtotime($p->fecha_fin))    : '—';
                    $pres      = (int)($p->presentes    ?? 0);
                    $ause      = (int)($p->ausentes     ?? 0);
                    $just      = (int)($p->justificados ?? 0);
                    $tot       = $pres + $ause + $just;
                    $pct       = $tot > 0 ? round($pres / $tot * 100) : 0;
                    $estLower  = strtolower($p->estado_pasantia ?? '');
                    $pillClass = $estLower === 'activo' ? 'p-activo' : ($estLower === 'finalizado' ? 'p-finaliz' : 'p-suspendid');
                    $estLabel  = htmlspecialchars(ucfirst($estLower));
                ?>
                <tr class="<?= $i % 2 !== 0 ? 'alt' : '' ?>">
                    <td class="c"><?= $i + 1 ?></td>
                    <td><?= $cedula ?></td>
                    <td class="b"><?= $nombre ?></td>
                    <td><?= $inst ?></td>
                    <td class="c"><?= $inicio ?></td>
                    <td class="c"><?= $fin ?></td>
                    <td class="c"><span class="pill <?= $pillClass ?>"><?= $estLabel ?></span></td>
                    <td class="c" style="color:#166534;font-weight:bold;"><?= $pres ?></td>
                    <td class="c" style="color:#dc2626;"><?= $ause ?></td>
                    <td class="c" style="color:#d97706;"><?= $just ?></td>
                    <td class="c" style="font-weight:bold;color:<?= $pct >= 90 ? '#166534' : ($pct >= 75 ? '#92400e' : '#dc2626') ?>">
                        <?= $pct ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endforeach; ?>
    <?php endif; ?>

    <div class="foot">
        Sistema de Gestión de Pasantes (SGP) &nbsp;·&nbsp; Reporte generado automáticamente &nbsp;·&nbsp; <?= date('d/m/Y H:i') ?>
    </div>

</body>
</html>
