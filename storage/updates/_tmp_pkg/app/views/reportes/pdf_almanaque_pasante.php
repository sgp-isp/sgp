<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 0.9cm 1.4cm; size: A4 portrait; }
body  { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #0f172a; line-height: 1.3; }

/* ── Títulos ── */
.doc-title {
    font-size: 12px; font-weight: bold; color: #162660;
    text-align: center; text-transform: uppercase;
    letter-spacing: 1px; margin: 0 0 2px;
}
.doc-sub {
    text-align: center; font-size: 8px; color: #64748b; margin-bottom: 6px;
}

/* ── Sección ── */
.sec-title {
    font-size: 8px; font-weight: bold; text-transform: uppercase;
    letter-spacing: .8px; color: #fff;
    background: #162660; padding: 3px 8px;
    margin: 6px 0 4px; border-radius: 2px;
}

/* ── Tabla datos pasante ── */
.tbl-datos { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 0; }
.tbl-datos td { padding: 3px 6px; border: 1px solid #cbd5e1; }
.tbl-datos .lbl { background: #f1f5f9; font-weight: bold; width: 22%; font-size: 8px; text-transform: uppercase; color: #475569; }

/* ── KPIs ── */
.kpi-wrap { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.kpi-wrap td { width: 16.6%; padding: 0 3px; text-align: center; border: none; }
.kpi-box {
    border: 1px solid #e2e8f0; border-radius: 4px;
    padding: 5px 3px 4px; background: #f8fafc;
}
.kpi-val { font-size: 16px; font-weight: 900; line-height: 1; display: block; }
.kpi-lbl { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; display: block; }

/* ── Grilla mensual (3 meses por fila) ── */
.mes-wrap { margin-bottom: 6px; }
.mes-nombre {
    font-size: 8.5px; font-weight: bold; text-transform: uppercase;
    color: #1e3a8a; letter-spacing: .5px; margin-bottom: 2px;
}
.mes-grid { border-collapse: collapse; font-size: 7.5px; width: 100%; }
.mes-grid th {
    text-align: center; padding: 2px 1px;
    background: #1e3a8a; color: #fff; border: 1px solid #1e3a8a;
    font-weight: bold;
}
.mes-grid td {
    text-align: center; padding: 1px 0;
    border: 1px solid #e2e8f0;
    font-size: 7.5px; height: 15px; vertical-align: middle;
}
.dia-P    { background: #dcfce7; color: #15803d; font-weight: bold; }
.dia-A    { background: #fee2e2; color: #b91c1c; font-weight: bold; }
.dia-J    { background: #dbeafe; color: #1d4ed8; font-weight: bold; }
.dia-F    { background: #fef9c3; color: #92400e; }
.dia-fin  { background: #f8fafc; color: #cbd5e1; }
.dia-vacio{ background: #fff; }
/* Letra de estado para impresión B&N */
.dia-ltr  { font-size: 5px; display: block; line-height: 1; font-weight: bold; opacity: .8; }

/* ── Evaluaciones ── */
.tbl-eval { width: 100%; border-collapse: collapse; font-size: 8.5px; }
.tbl-eval th { background: #1e3a8a; color: #fff; padding: 4px 6px; text-align: left; border: 1px solid #1e3a8a; font-size: 8px; }
.tbl-eval td { padding: 4px 6px; border: 1px solid #e2e8f0; vertical-align: top; }
.tbl-eval tr:nth-child(even) td { background: #f8fafc; }

/* ── Leyenda ── */
.leyenda { border-collapse: collapse; font-size: 8px; margin-bottom: 5px; }
.leyenda td { padding: 1px 6px; vertical-align: middle; }
.l-box {
    display: inline-block; width: 10px; height: 10px; border-radius: 2px;
    margin-right: 3px; vertical-align: middle;
    font-size: 6px; font-weight: bold; text-align: center; line-height: 10px;
}

/* ── Firma ── */
.firma-wrap { width: 100%; border-collapse: collapse; margin-top: 10px; }
.firma-wrap td { text-align: center; padding: 0 12px; border: none; }
.firma-linea {
    border-top: 1px solid #0f172a; display: inline-block;
    min-width: 180px; padding-top: 4px;
    font-size: 9px; font-weight: bold;
}
.firma-sub { font-size: 8px; color: #64748b; font-weight: normal; }

/* ── Footer ── */
.footer { text-align: center; font-size: 8px; color: #94a3b8; margin-top: 8px; padding-top: 4px; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>

<?php include __DIR__ . '/comunes/header.php'; ?>

<p class="doc-title">Reporte Individual de Pasantía — Almanaque Histórico</p>
<p class="doc-sub">
    Generado el <?= date('d/m/Y \a \l\a\s H:i') ?>
    &nbsp;|&nbsp; Sistema de Gestión de Pasantes (SGP)
</p>

<?php
/* ── Variables base ── */
$nombreCompleto = mb_strtoupper(
    trim(($pasante->apellidos ?? '') . ', ' . ($pasante->nombres ?? '')), 'UTF-8'
);
$ci          = htmlspecialchars($pasante->cedula    ?? '—');
$depto       = htmlspecialchars($pasante->departamento      ?? '—');
$inst        = htmlspecialchars($pasante->institucion_nombre ?? '—');
$estado      = htmlspecialchars($pasante->estado_pasantia   ?? '—');
$fechaIni    = $pasante->fecha_inicio ? date('d/m/Y', strtotime($pasante->fecha_inicio)) : '—';
$fechaFin    = $pasante->fecha_fin    ? date('d/m/Y', strtotime($pasante->fecha_fin))    : 'En curso';
?>

<!-- ═══ DATOS DEL PASANTE ══════════════════════════════════════════ -->
<div class="sec-title">Datos del Pasante</div>
<table class="tbl-datos">
    <tr>
        <td class="lbl">Nombre</td>
        <td class="b"><?= $nombreCompleto ?></td>
        <td class="lbl">Cédula</td>
        <td>V-<?= $ci ?></td>
    </tr>
    <tr>
        <td class="lbl">Departamento</td>
        <td><?= $depto ?></td>
        <td class="lbl">Estado</td>
        <td><?= $estado ?></td>
    </tr>
    <tr>
        <td class="lbl">Institución</td>
        <td><?= $inst ?></td>
        <td class="lbl">Período</td>
        <td><?= $fechaIni ?> — <?= $fechaFin ?></td>
    </tr>
</table>

<!-- ═══ KPIs ══════════════════════════════════════════════════════ -->
<div class="sec-title">Resumen de Desempeño</div>
<table class="kpi-wrap">
    <tr>
        <td>
            <div class="kpi-box">
                <span class="kpi-val" style="color:#16a34a;"><?= $stats['P'] ?></span>
                <span class="kpi-lbl">Días Presentes</span>
            </div>
        </td>
        <td>
            <div class="kpi-box">
                <span class="kpi-val" style="color:#dc2626;"><?= $stats['A'] ?></span>
                <span class="kpi-lbl">Días Ausentes</span>
            </div>
        </td>
        <td>
            <div class="kpi-box">
                <span class="kpi-val" style="color:#2563eb;"><?= $stats['J'] ?></span>
                <span class="kpi-lbl">Justificados</span>
            </div>
        </td>
        <td>
            <div class="kpi-box">
                <span class="kpi-val" style="color:<?= $pct >= 90 ? '#16a34a' : ($pct >= 75 ? '#d97706' : '#dc2626') ?>;"><?= $pct ?>%</span>
                <span class="kpi-lbl">% Asistencia</span>
            </div>
        </td>
        <td>
            <div class="kpi-box">
                <span class="kpi-val" style="color:#162660;"><?= $horasAcum ?></span>
                <span class="kpi-lbl">Horas Acum.</span>
            </div>
        </td>
        <td>
            <div class="kpi-box">
                <span class="kpi-val" style="color:#475569;"><?= $pctHoras ?>%</span>
                <span class="kpi-lbl">Progreso Meta</span>
            </div>
        </td>
    </tr>
</table>

<!-- ═══ REGISTRO MENSUAL ══════════════════════════════════════════ -->
<div class="sec-title">Registro Mensual de Asistencias</div>

<!-- Leyenda — color + letra para impresión B&N -->
<table class="leyenda">
    <tr>
        <td><span class="l-box" style="background:#dcfce7;border:1px solid #86efac;color:#15803d;">P</span>Presente</td>
        <td><span class="l-box" style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;">A</span>Ausente</td>
        <td><span class="l-box" style="background:#dbeafe;border:1px solid #93c5fd;color:#1d4ed8;">J</span>Justificado</td>
        <td><span class="l-box" style="background:#fef9c3;border:1px solid #fde047;color:#92400e;">F</span>Feriado / No laborable</td>
        <td><span class="l-box" style="background:#f8fafc;border:1px solid #e2e8f0;color:#94a3b8;">&nbsp;</span>Fin de semana</td>
    </tr>
</table>

<?php
/* ── Construir grilla mes a mes desde fecha_inicio hasta hoy o fecha_fin ── */
$mesesNombres = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];
$diasNombres = ['L','M','M','J','V','S','D'];

$fechaInicio = $pasante->fecha_inicio ? new DateTime($pasante->fecha_inicio) : new DateTime();
$fechaLimite = $pasante->fecha_fin
    ? new DateTime(min($pasante->fecha_fin, date('Y-m-d')))
    : new DateTime();

// Primer día del mes de inicio, último día del mes de fin
$cursor = new DateTime($fechaInicio->format('Y-m-01'));
$fin    = new DateTime($fechaLimite->format('Y-m-t'));

// Agrupar meses en filas de 3 para layout de tabla
$mesesData = [];
while ($cursor <= $fin) {
    $mesesData[] = [
        'anio' => (int)$cursor->format('Y'),
        'mes'  => (int)$cursor->format('n'),
    ];
    $cursor->modify('+1 month');
}

// 3 meses por fila — encaja en A4 vertical con hasta 12 meses en 4 filas
$cols = 3;
$rows = array_chunk($mesesData, $cols);
?>

<?php foreach ($rows as $fila): ?>
<table style="width:100%;border-collapse:collapse;margin-bottom:6px;">
    <tr>
    <?php foreach ($fila as $mesInfo): ?>
        <?php
        $anioM  = $mesInfo['anio'];
        $mesM   = $mesInfo['mes'];
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mesM, $anioM);
        // día de la semana del día 1 (1=Lun ... 7=Dom)
        $primerDia = (int)(new DateTime("$anioM-$mesM-01"))->format('N');
        ?>
        <td style="vertical-align:top;padding:0 6px 0 0;width:33.3%;">
            <div class="mes-nombre"><?= $mesesNombres[$mesM] ?> <?= $anioM ?></div>
            <table class="mes-grid">
                <thead>
                    <tr>
                        <?php foreach ($diasNombres as $d): ?>
                        <th><?= $d ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $col = 1;
                echo '<tr>';
                // Celdas vacías antes del primer día
                for ($v = 1; $v < $primerDia; $v++) {
                    echo '<td class="dia-vacio"></td>';
                    $col++;
                }
                for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                    $fechaStr = sprintf('%04d-%02d-%02d', $anioM, $mesM, $dia);
                    $dow      = (int)(new DateTime($fechaStr))->format('N'); // 1=Lun..7=Dom

                    // Determinar clase y etiqueta
                    $sym = '';
                    if ($dow >= 6) {
                        $clase = 'dia-fin';
                        $label = $dia;
                    } elseif (isset($porFecha[$fechaStr])) {
                        $est = strtolower($porFecha[$fechaStr]->estado ?? '');
                        if ($est === 'presente')         { $clase = 'dia-P'; $label = $dia; $sym = 'P'; }
                        elseif ($est === 'ausente')       { $clase = 'dia-A'; $label = $dia; $sym = 'A'; }
                        elseif ($est === 'justificado')   { $clase = 'dia-J'; $label = $dia; $sym = 'J'; }
                        else { $clase = 'dia-fin'; $label = $dia; }
                    } else {
                        $clase = 'dia-vacio';
                        $label = $dia;
                    }

                    $cell = $sym
                        ? "{$label}<span class=\"dia-ltr\">{$sym}</span>"
                        : $label;
                    echo "<td class=\"{$clase}\">{$cell}</td>";
                    $col++;
                    if ($col > 7) {
                        echo '</tr><tr>';
                        $col = 1;
                    }
                }
                // Rellenar última fila
                while ($col <= 7 && $col > 1) {
                    echo '<td class="dia-vacio"></td>';
                    $col++;
                }
                echo '</tr>';
                ?>
                </tbody>
            </table>
        </td>
    <?php endforeach; ?>
    <?php // Celdas vacías si la fila no está completa
    $faltantes = $cols - count($fila);
    for ($f = 0; $f < $faltantes; $f++): ?>
        <td style="width:33.3%;"></td>
    <?php endfor; ?>
    </tr>
</table>
<?php endforeach; ?>

<!-- ═══ EVALUACIONES ══════════════════════════════════════════════ -->
<?php if (!empty($evaluaciones)): ?>
<div class="sec-title">Evaluaciones del Período</div>
<table class="tbl-eval">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Lapso</th>
            <th style="text-align:center;">Promedio</th>
            <th>Evaluador</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($evaluaciones as $ev): ?>
        <tr>
            <td style="white-space:nowrap;"><?= $ev->fecha_evaluacion ? date('d/m/Y', strtotime($ev->fecha_evaluacion)) : '—' ?></td>
            <td><?= htmlspecialchars($ev->lapso_academico ?? '—') ?></td>
            <td style="text-align:center;font-weight:bold;color:<?= ((float)($ev->promedio_final ?? 0)) >= 14 ? '#16a34a' : '#d97706' ?>;">
                <?= number_format((float)($ev->promedio_final ?? 0), 1) ?>
            </td>
            <td><?= htmlspecialchars($ev->evaluador ?? '—') ?></td>
            <td style="font-size:8.5px;"><?= htmlspecialchars($ev->observaciones ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ═══ FIRMA ════════════════════════════════════════════════════ -->
<table class="firma-wrap">
    <tr>
        <td>
            <div class="firma-linea">
                <?= htmlspecialchars($jefeNombre) ?><br>
                <span class="firma-sub"><?= $jefeCargo ?></span><br>
                <span class="firma-sub">Instituto de Salud Pública del Estado Bolívar</span>
            </div>
        </td>
        <td>
            <div style="border:1px dashed #cbd5e1;border-radius:4px;width:80px;height:60px;
                        display:inline-block;color:#94a3b8;font-size:7px;
                        text-align:center;padding-top:22px;">
                Sello
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    Documento generado electrónicamente &mdash; Sistema de Gestión de Pasantes (SGP) &mdash;
    Instituto de Salud Pública del Estado Bolívar &mdash; <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>
