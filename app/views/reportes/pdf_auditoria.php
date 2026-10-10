<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 2cm 1.5cm; }
body { font-family: Helvetica, Arial, sans-serif; font-size: 9.5px; color: #0D1424; }
table { border-collapse: collapse; width: 100%; }
.section-title {
    font-size: 11px; font-weight: bold; color: #162660;
    border-left: 4px solid #162660; padding-left: 8px;
    margin: 14px 0 8px;
}
th {
    background-color: #162660 !important; color: #ffffff !important;
    padding: 7px 8px; font-size: 8.5px; text-align: left;
    border: 1px solid #162660;
}
td { padding: 5px 8px; font-size: 8.5px; border-bottom: 1px solid #e8ecf2; }
tr:nth-child(even) td { background: #f8fafc; }
.badge {
    display: inline-block; padding: 2px 7px; border-radius: 8px;
    font-size: 7.5px; font-weight: bold;
}
.badge-login   { background: #dbeafe; color: #1d4ed8; }
.badge-pasante { background: #d1fae5; color: #059669; }
.badge-sistema { background: #fef3c7; color: #d97706; }
.badge-error   { background: #fee2e2; color: #dc2626; }
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
    Reporte de Auditoría del Sistema
</p>
<p style="text-align:center; font-size:9px; color:#7480A0; margin:0 0 14px;">
    <?= htmlspecialchars($subtitulo_pdf ?? '') ?>
    &nbsp;|&nbsp; Generado: <?= date('d/m/Y H:i') ?>
</p>

<?php if (empty($registros)): ?>
<div style="text-align:center; padding:30px; color:#94a3b8; font-size:11px;">
    No hay registros de auditoría para el período seleccionado.
</div>
<?php else: ?>

<table>
    <thead>
        <tr>
            <th style="width:4%;">N°</th>
            <th style="width:13%;">Fecha / Hora</th>
            <th style="width:15%;">Usuario</th>
            <th style="width:9%;">Cédula</th>
            <th style="width:12%;">Módulo</th>
            <th style="width:14%;">Acción</th>
            <th style="width:33%;">Descripción</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($registros as $i => $r):
        $mod = strtolower($r->modulo ?? '');
        $badgeClass = str_contains($mod, 'login') || str_contains($mod, 'acceso') ? 'badge-login'
            : (str_contains($mod, 'pasante') || str_contains($mod, 'asistencia') ? 'badge-pasante'
            : (str_contains($mod, 'error') ? 'badge-error' : 'badge-sistema'));
    ?>
    <tr>
        <td style="text-align:center; color:#94a3b8;"><?= $i + 1 ?></td>
        <td><?= htmlspecialchars(isset($r->fecha) ? date('d/m/Y H:i', strtotime($r->fecha)) : '—') ?></td>
        <td><?= htmlspecialchars($r->usuario ?? '—') ?></td>
        <td><?= htmlspecialchars($r->cedula  ?? '—') ?></td>
        <td>
            <span class="badge <?= $badgeClass ?>">
                <?= htmlspecialchars(mb_strimwidth($r->modulo ?? '—', 0, 18, '…')) ?>
            </span>
        </td>
        <td><?= htmlspecialchars(mb_strimwidth($r->accion ?? '—', 0, 22, '…')) ?></td>
        <td style="color:#475569;"><?= htmlspecialchars(mb_strimwidth($r->descripcion ?? '—', 0, 60, '…')) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top:14px; font-size:8.5px; color:#64748b; text-align:right;">
    Total de registros: <strong><?= count($registros) ?></strong>
</div>
<?php endif; ?>

<div class="footer-line">
    SGP — Sistema de Gestión de Pasantes &nbsp;|&nbsp; Documento de uso interno &nbsp;|&nbsp; <?= date('d/m/Y') ?>
</div>

</body>
</html>
