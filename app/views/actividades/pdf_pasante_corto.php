<?php
/**
 * PDF: Resumen de Pasante Corto
 */
$hMeta   = max(1, (int)($pasante->horas_meta ?? 480));
$hAcum   = (int)($pasante->horas_acumuladas ?? 0);
$progPct = min(100, round($hAcum / $hMeta * 100));
$barColor= $progPct >= 80 ? '#059669' : ($progPct >= 50 ? '#2563eb' : '#dc2626');
$nombre  = mb_strtoupper(trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? '')), 'UTF-8');
$pathLogo= APPROOT . '/../public/img/cintillo_isp_bolivar.jpg';
$bLogo   = file_exists($pathLogo) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathLogo)) : '';

// Stats de asistencia
$totP = $totA = $totJ = $totLab = 0;
foreach ($asistencias as $a) {
    $dn = (int)date('N', strtotime($a->fecha));
    if ($dn <= 5) {
        $totLab++;
        if     ($a->estado === 'Presente')    $totP++;
        elseif ($a->estado === 'Ausente')     $totA++;
        elseif ($a->estado === 'Justificado') $totJ++;
    }
}
$pctAsist = $totLab > 0 ? round($totP / $totLab * 100) : 0;
$aColor   = $pctAsist >= 80 ? '#059669' : ($pctAsist >= 60 ? '#d97706' : '#dc2626');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 1.2cm 1.8cm 1cm 1.8cm; }
* { box-sizing: border-box; }
body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; margin:0; padding:0; }
.lbl { font-size:8px; text-transform:uppercase; color:#64748b; font-weight:bold; letter-spacing:0.4px; display:block; margin-bottom:2px; }
.val { font-size:12px; color:#1e293b; }
.sec { font-size:9px; font-weight:bold; color:#1e3a8a; text-transform:uppercase;
       letter-spacing:0.5px; border-bottom:1.5px solid #dbeafe;
       padding:3px 0 3px 9px; margin:12px 0 6px; position:relative; }
.sec::before { content:''; position:absolute; left:0; top:0; width:3px; height:15px; background:#7c3aed; border-radius:2px; }
.tbl { width:100%; border-collapse:collapse; }
.tbl th { background:#f5f3ff; color:#5b21b6; font-size:8px; text-transform:uppercase; letter-spacing:0.3px; padding:6px 8px; text-align:left; }
.tbl td { padding:5px 8px; border-bottom:1px solid #f1f5f9; font-size:10px; }
</style>
</head>
<body>

<!-- Logo -->
<?php if($bLogo): ?>
<img src="<?= $bLogo ?>" style="width:100%;max-height:60px;object-fit:contain;margin-bottom:12px;">
<?php endif; ?>

<!-- Encabezado -->
<div style="text-align:center;margin-bottom:16px;">
    <div style="font-size:14px;font-weight:bold;color:#172554;">RESUMEN DE PASANTÍA CORTA</div>
    <div style="font-size:10px;color:<?= ($pasante->estado_pasantia ?? '') === 'Finalizado' ? '#059669' : '#7c3aed' ?>;font-weight:bold;">
        PASANTE CORTO<?= ($pasante->estado_pasantia ?? '') === 'Finalizado' ? ' · EGRESADO' : '' ?>
    </div>
</div>

<!-- Datos del pasante -->
<div class="sec">Datos del Pasante</div>
<table class="tbl">
    <tr>
        <td width="50%"><span class="lbl">Cédula</span><span class="val">V-<?= htmlspecialchars($pasante->cedula ?? '—') ?></span></td>
        <td><span class="lbl">Carrera / Especialidad</span><span class="val"><?= htmlspecialchars($pasante->carrera ?? 'No especificada') ?></span></td>
    </tr>
    <tr>
        <td><span class="lbl">Institución de Origen</span><span class="val"><?= htmlspecialchars($pasante->institucion ?? '—') ?></span></td>
        <td><span class="lbl">Período</span><span class="val"><?= htmlspecialchars($pasante->periodo ?? '—') ?></span></td>
    </tr>
    <tr>
        <td><span class="lbl">Departamento Asignado</span><span class="val"><?= htmlspecialchars($pasante->departamento ?? '—') ?></span></td>
        <td><span class="lbl">Tutor</span><span class="val"><?= htmlspecialchars(trim($pasante->tutor_nombre ?? '—')) ?></span></td>
    </tr>
    <tr>
        <td><span class="lbl">Fecha Inicio</span><span class="val"><?= $pasante->fecha_inicio ? date('d/m/Y', strtotime($pasante->fecha_inicio)) : '—' ?></span></td>
        <td><span class="lbl">Fecha Fin Estimada</span><span class="val"><?= $pasante->fecha_fin ? date('d/m/Y', strtotime($pasante->fecha_fin)) : '—' ?></span></td>
    </tr>
</table>

<!-- Progreso de horas -->
<div class="sec">Progreso de Horas</div>
<table class="tbl">
    <tr>
        <td width="33%"><span class="lbl">Horas Acumuladas</span><span class="val" style="font-size:16px;font-weight:bold;color:<?= $barColor ?>;"><?= $hAcum ?>h</span></td>
        <td width="33%"><span class="lbl">Meta</span><span class="val" style="font-size:16px;font-weight:bold;color:#64748b;"><?= $hMeta ?>h</span></td>
        <td><span class="lbl">Progreso</span><span class="val" style="font-size:16px;font-weight:bold;color:<?= $barColor ?>;"><?= $progPct ?>%</span></td>
    </tr>
</table>
<div style="margin:6px 0 12px;background:#e2e8f0;border-radius:6px;height:8px;overflow:hidden;">
    <div style="width:<?= $progPct ?>%;height:100%;background:<?= $barColor ?>;border-radius:6px;"></div>
</div>

<!-- Resumen de asistencia -->
<div class="sec">Resumen de Asistencia</div>
<table class="tbl">
    <tr>
        <td width="25%"><span class="lbl">Días Laborables</span><span class="val" style="font-size:14px;font-weight:bold;"><?= $totLab ?></span></td>
        <td width="25%"><span class="lbl">Presentes</span><span class="val" style="font-size:14px;font-weight:bold;color:#059669;"><?= $totP ?></span></td>
        <td width="25%"><span class="lbl">Ausentes</span><span class="val" style="font-size:14px;font-weight:bold;color:#dc2626;"><?= $totA ?></span></td>
        <td><span class="lbl">% Asistencia</span><span class="val" style="font-size:14px;font-weight:bold;color:<?= $aColor ?>;"><?= $pctAsist ?>%</span></td>
    </tr>
</table>

<!-- Evaluaciones -->
<?php if(!empty($evaluaciones)): ?>
<div class="sec">Evaluaciones ISP</div>
<table class="tbl">
    <thead>
        <tr><th>Fecha</th><th>Lapso / Fase</th><th style="text-align:center;">Promedio</th><th>Evaluador</th></tr>
    </thead>
    <tbody>
    <?php foreach($evaluaciones as $ev):
        $p = (float)$ev->promedio_final;
        $c = $p>=4?'#059669':($p>=3?'#d97706':'#dc2626');
    ?>
    <tr>
        <td><?= date('d/m/Y', strtotime($ev->fecha_evaluacion)) ?></td>
        <td><?= htmlspecialchars($ev->lapso_academico ?? '—') ?></td>
        <td style="text-align:center;font-weight:bold;color:<?= $c ?>;"><?= number_format($p, 2) ?> / 5.00</td>
        <td><?= htmlspecialchars(trim($ev->tutor_nombre ?? '—')) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Historial de asistencia (últimos 100) -->
<div class="sec" style="margin-top:14px;">Historial de Asistencia</div>
<table class="tbl">
    <thead>
        <tr><th>Fecha</th><th>Hora</th><th>Estado</th><th>Origen</th><th>Nota</th></tr>
    </thead>
    <tbody>
    <?php foreach($asistencias as $a):
        $esAutoFill = (bool)($a->es_auto_fill ?? 0);
        $motivo     = $a->motivo_justificacion ?? '';
        $esFeriado  = $esAutoFill && stripos($motivo, 'Feriado') !== false;

        // Estado a mostrar: auto-fill de días normales → Presente en el reporte
        $estadoDisplay = $a->estado ?? '—';
        if ($esAutoFill && !$esFeriado && $estadoDisplay === 'Presente') {
            $estadoDisplay = 'Presente';
        }
        $colors = ['Presente' => '#059669', 'Ausente' => '#dc2626', 'Justificado' => '#2563eb'];
        $c = $colors[$estadoDisplay] ?? '#64748b';

        // Origen legible
        $origen = $esAutoFill
            ? ($esFeriado ? 'Feriado' : 'Auto-llenado')
            : ucfirst(strtolower($a->metodo ?? 'Manual'));

        // Nota: solo para Justificados no-autofill o feriados
        $nota = '';
        if ($esFeriado) {
            $nota = 'Día no laborable';
        } elseif (!$esAutoFill && !empty($motivo)) {
            $nota = $motivo;
        }
    ?>
    <tr>
        <td style="font-weight:bold;"><?= date('d/m/Y', strtotime($a->fecha)) ?></td>
        <td><?= ($a->hora_registro && !$esAutoFill) ? substr($a->hora_registro,0,5) : '—' ?></td>
        <td style="color:<?= $c ?>;font-weight:bold;"><?= htmlspecialchars($estadoDisplay) ?></td>
        <td style="color:#64748b;font-size:8px;"><?= htmlspecialchars($origen) ?></td>
        <td style="color:#94a3b8;font-size:8px;"><?= htmlspecialchars($nota) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div style="font-size:7.5px;color:#94a3b8;margin-top:4px;padding:4px 0;border-top:1px dashed #f1f5f9;">
    <strong>Auto-llenado:</strong> Días marcados retroactivamente al registrar la pasantía con fecha anterior.
    <strong>Feriado:</strong> Días no laborables según el calendario institucional.
</div>

<div style="margin-top:18px;text-align:center;font-size:8px;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:8px;">
    Generado el <?= date('d/m/Y H:i') ?> — Sistema de Gestión de Pasantías (SGP) · Instituto de Salud Pública del Estado Bolívar
</div>
</body>
</html>
