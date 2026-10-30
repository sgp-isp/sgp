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
    padding: 7px 12px; margin: 16px 0 6px;
    border-radius: 0 6px 6px 0;
}
th {
    background-color: #162660 !important; color: #ffffff !important;
    padding: 7px 8px; font-size: 8px; text-align: left;
    border: 1px solid #162660;
}
td { padding: 5px 8px; font-size: 8.5px; border-bottom: 1px solid #e8ecf2; }
tr:nth-child(even) td { background: #f8fafc; }
.badge {
    display: inline-block; padding: 2px 7px; border-radius: 8px;
    font-size: 7.5px; font-weight: bold;
}
.badge-activo    { background: #d1fae5; color: #059669; }
.badge-inactivo  { background: #fee2e2; color: #dc2626; }
.kpi-row {
    display: flex; gap: 14px; margin-bottom: 16px;
}
.kpi-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 10px; text-align: center;
}
.kpi-val { font-size: 20px; font-weight: bold; color: #162660; }
.kpi-lbl { font-size: 8px; color: #64748b; margin-top: 2px; }
.footer-line {
    text-align: center; font-size: 8px; color: #94a3b8;
    margin-top: 28px; padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}
</style>
</head>
<body>

<?php include __DIR__ . '/comunes/header.php'; ?>

<p style="text-align:center; font-size:13px; font-weight:bold; color:#162660;
   text-transform:uppercase; margin:10px 0 4px;">
    Matriz de Asignaciones de Pasantes
</p>
<p style="text-align:center; font-size:9px; color:#7480A0; margin:0 0 14px;">
    <?= htmlspecialchars($subtitulo_pdf ?? 'Todos los Departamentos') ?>
    &nbsp;|&nbsp; Generado: <?= date('d/m/Y H:i') ?>
</p>

<?php if (empty($asignaciones)): ?>
<div style="text-align:center; padding:30px; color:#94a3b8; font-size:11px;">
    No hay asignaciones activas para los filtros seleccionados.
</div>
<?php else:
    // Agrupar por departamento
    $porDepto = [];
    foreach ($asignaciones as $a) {
        $dep = $a->departamento ?? 'Sin Departamento';
        $porDepto[$dep][] = $a;
    }

    // KPIs
    $total    = count($asignaciones);
    $totalDep = count($porDepto);
?>

<!-- KPIs -->
<div class="kpi-row">
    <div class="kpi-box">
        <div class="kpi-val"><?= $total ?></div>
        <div class="kpi-lbl">Total Asignaciones</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-val"><?= $totalDep ?></div>
        <div class="kpi-lbl">Departamentos</div>
    </div>
</div>

<?php foreach ($porDepto as $depto => $filas): ?>

<div class="section-depto">
    <?= htmlspecialchars($depto) ?>
    <span style="font-size:8.5px; font-weight:normal; color:#3A4768; margin-left:8px;">
        (<?= count($filas) ?> pasante<?= count($filas) > 1 ? 's' : '' ?>)
    </span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:4%;">N°</th>
            <th style="width:10%;">Cédula</th>
            <th style="width:26%;">Apellidos y Nombres</th>
            <th style="width:22%;">Institución</th>
            <th style="width:22%;">Tutor Asignado</th>
            <th style="width:10%;">F. Inicio</th>
            <th style="width:6%;">Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($filas as $idx => $a): ?>
    <tr>
        <td style="text-align:center; color:#94a3b8;"><?= $idx + 1 ?></td>
        <td><?= htmlspecialchars($a->cedula ?? '—') ?></td>
        <td><?= htmlspecialchars(mb_strtoupper(($a->apellidos ?? '') . ', ' . ($a->nombres ?? ''), 'UTF-8')) ?></td>
        <td style="color:#475569;"><?= htmlspecialchars($a->institucion ?? 'N/D') ?></td>
        <td><?= htmlspecialchars($a->tutor ?? '—') ?></td>
        <td style="text-align:center;">
            <?= $a->fecha_inicio ? date('d/m/Y', strtotime($a->fecha_inicio)) : '—' ?>
        </td>
        <td>
            <span class="badge <?= $a->estado === 'activo' ? 'badge-activo' : 'badge-inactivo' ?>">
                <?= htmlspecialchars(ucfirst($a->estado ?? '—')) ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php endforeach; ?>
<?php endif; ?>

<div class="footer-line">
    SGP — Sistema de Gestión de Pasantes &nbsp;|&nbsp; Documento generado el <?= date('d/m/Y') ?>
</div>

</body>
</html>
