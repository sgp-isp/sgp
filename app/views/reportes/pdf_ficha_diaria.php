<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 2cm 1.5cm; }
body { font-family: Helvetica, Arial, sans-serif; font-size: 9.5px; color: #0D1424; }
table { border-collapse: collapse; width: 100%; }
.section-depto {
    font-size: 10.5px; font-weight: bold; color: #162660;
    background: #f1f5ff; border-left: 5px solid #162660;
    padding: 8px 12px; margin: 16px 0 6px;
    border-radius: 0 6px 6px 0;
}
th {
    background-color: #162660 !important; color: #ffffff !important;
    padding: 6px 8px; font-size: 8px; text-align: left;
    border: 1px solid #162660;
}
td { padding: 5px 8px; font-size: 8.5px; border-bottom: 1px solid #e8ecf2; }
tr:nth-child(even) td { background: #f8fafc; }
.badge {
    display: inline-block; padding: 2px 8px; border-radius: 8px;
    font-size: 7.5px; font-weight: bold;
}
.badge-presente   { background: #d1fae5; color: #059669; }
.badge-ausente    { background: #fee2e2; color: #dc2626; }
.badge-justificado{ background: #fef3c7; color: #d97706; }
.resumen-depto {
    font-size: 8px; color: #475569;
    text-align: right; margin-bottom: 12px;
}
.footer-line {
    text-align: center; font-size: 8px; color: #94a3b8;
    margin-top: 28px; padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}
</style>
</head>
<body>

<?php include __DIR__ . '/comunes/header.php'; ?>

<?php
$diasEs    = ['','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$mesesEs   = ['','enero','febrero','marzo','abril','mayo','junio',
               'julio','agosto','septiembre','octubre','noviembre','diciembre'];
$diaNombre = $diasEs[(int)date('N', strtotime($fecha))];
$diaNum    = (int)date('j', strtotime($fecha));
$mesNombre = $mesesEs[(int)date('n', strtotime($fecha))];
$anio      = date('Y', strtotime($fecha));
?>
<p style="text-align:center; font-size:13px; font-weight:bold; color:#162660;
   text-transform:uppercase; margin:10px 0 4px;">
    Ficha Diaria de Actividad
</p>

<!-- ── BLOQUE DE FECHA PROMINENTE ─────────────────────────── -->
<div style="display:flex; justify-content:center; margin:6px 0 14px;">
    <div style="background:linear-gradient(135deg,#162660 0%,#2563eb 100%);
                border-radius:12px; padding:10px 28px; text-align:center;
                display:inline-block;">
        <div style="font-size:8px; color:rgba(255,255,255,0.7); text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:3px;">
            Fecha de Asistencia
        </div>
        <div style="font-size:17px; font-weight:900; color:#ffffff; letter-spacing:0.5px;">
            <?= $diaNombre ?>, <?= $diaNum ?> de <?= $mesNombre ?> de <?= $anio ?>
        </div>
        <div style="font-size:8px; color:rgba(255,255,255,0.6); margin-top:3px;">
            Generado: <?= date('d/m/Y h:i A') ?>
        </div>
    </div>
</div>

<?php if (!($esHorarioLaboral ?? true)): ?>
<div style="background:#fef9c3; border:1px solid #fde047; border-radius:8px;
     padding:8px 12px; margin-bottom:12px; font-size:8.5px; color:#854d0e;
     display:flex; align-items:center; gap:8px;">
    <strong>⚠ Aviso:</strong>
    Este reporte fue generado fuera del horario laboral (<?= date('h:i A') ?>).
    Los datos mostrados corresponden a los registros cargados hasta este momento —
    la jornada aún puede no haber iniciado o ya finalizó.
</div>
<?php endif; ?>

<?php if (empty($porDepto)): ?>
<div style="text-align:center; padding:30px; color:#94a3b8; font-size:11px;">
    No hay registros de asistencia para la fecha <?= date('d/m/Y', strtotime($fecha)) ?>.<br>
    <span style="font-size:9px; margin-top:6px; display:block;">
        Si la jornada aún no ha iniciado, los registros aparecerán una vez que los pasantes marquen asistencia.
    </span>
</div>
<?php else:
    $totalPresentes  = 0;
    $totalAusentes   = 0;
    $totalJustif     = 0;
    foreach ($porDepto as $depto => $filas):
        $pres = 0; $ause = 0; $just = 0;
        foreach ($filas as $f) {
            if ($f->estado === 'Presente') $pres++;
            elseif ($f->estado === 'Ausente') $ause++;
            elseif ($f->estado === 'Justificado') $just++;
        }
        $totalPresentes += $pres; $totalAusentes += $ause; $totalJustif += $just;
?>

<div class="section-depto">
    <?= htmlspecialchars($depto) ?>
    <span style="font-size:8.5px; font-weight:normal; color:#3A4768; margin-left:10px;">
        Total: <?= count($filas) ?> pasante(s)
    </span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:4%;">N°</th>
            <th style="width:10%;">Cédula</th>
            <th style="width:26%;">Apellidos y Nombres</th>
            <th style="width:12%;">Fecha</th>
            <th style="width:11%;">Hora Registro</th>
            <th style="width:11%;">Método</th>
            <th style="width:11%;">Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($filas as $idx => $r):
        $badgeClass = $r->estado === 'Presente' ? 'badge-presente'
            : ($r->estado === 'Justificado' ? 'badge-justificado' : 'badge-ausente');
    ?>
    <tr>
        <td style="text-align:center; color:#94a3b8;"><?= $idx + 1 ?></td>
        <td><?= htmlspecialchars($r->cedula ?? '—') ?></td>
        <td><?= htmlspecialchars(mb_strtoupper(($r->apellidos ?? '') . ', ' . ($r->nombres ?? ''), 'UTF-8')) ?></td>
        <td style="text-align:center; color:#475569; font-weight:600;">
            <?= isset($r->fecha) ? date('d/m/Y', strtotime($r->fecha)) : '—' ?>
        </td>
        <td style="text-align:center;">
            <?= htmlspecialchars($r->hora_registro ? date('h:i A', strtotime($r->hora_registro)) : '—') ?>
        </td>
        <td style="text-align:center; color:#64748b;">
            <?= htmlspecialchars(ucfirst($r->metodo ?? '—')) ?>
        </td>
        <td>
            <span class="badge <?= $badgeClass ?>">
                <?= htmlspecialchars($r->estado ?? '—') ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="resumen-depto">
    <strong style="color:#059669;">P: <?= $pres ?></strong> &nbsp;
    <strong style="color:#dc2626;">A: <?= $ause ?></strong> &nbsp;
    <strong style="color:#d97706;">J: <?= $just ?></strong>
</div>

<?php endforeach; ?>

<!-- Resumen global -->
<div style="margin-top:14px; border:1px solid #e2e8f0; border-radius:8px;
            padding:10px 16px; background:#f8fafc; display:flex;
            justify-content:space-between; font-size:9px;">
    <span>Total registros del día:
        <strong><?= array_sum(array_map('count', $porDepto)) ?></strong>
    </span>
    <span style="color:#059669;">Presentes: <strong><?= $totalPresentes ?></strong></span>
    <span style="color:#dc2626;">Ausentes: <strong><?= $totalAusentes ?></strong></span>
    <span style="color:#d97706;">Justificados: <strong><?= $totalJustif ?></strong></span>
</div>

<?php endif; ?>

<div class="footer-line">
    SGP — Sistema de Gestión de Pasantes &nbsp;|&nbsp; Documento de uso interno &nbsp;|&nbsp; <?= date('d/m/Y') ?>
</div>

</body>
</html>
