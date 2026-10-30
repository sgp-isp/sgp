<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Ejecutivo Administrativo</title>
    <style>
        @page { margin: 1.5cm 1.8cm; }
        body   { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #111; line-height: 1.4; }
        h1     { font-size: 14px; text-align: center; text-transform: uppercase; margin: 0 0 2px; }
        h2     { font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #999; padding-bottom: 3px; margin: 14px 0 7px; }
        .sub   { text-align: center; font-size: 9px; color: #555; margin-bottom: 12px; }
        table  { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
        th     { background: #e8e8e8; padding: 5px 8px; text-align: left; border: 1px solid #ccc; font-size: 9px; text-transform: uppercase; }
        td     { padding: 5px 8px; border: 1px solid #ddd; vertical-align: middle; }
        .kpi   { text-align: center; width: 20%; }
        .knum  { font-size: 20px; font-weight: bold; display: block; }
        .klbl  { font-size: 8px; text-transform: uppercase; color: #555; }
        .alt   { background: #f7f7f7; }
        .c     { text-align: center; }
        .b     { font-weight: bold; }
        .foot  { border-top: 1px solid #bbb; margin-top: 14px; padding-top: 5px; text-align: center; font-size: 8px; color: #777; }
    </style>
</head>
<body>

    <?php include 'comunes/header.php'; ?>

    <h1>Resumen Ejecutivo Administrativo</h1>
    <div class="sub">
        <?= htmlspecialchars($subtitulo_pdf) ?>
        &nbsp;|&nbsp; Generado el <?= date('d/m/Y \a \l\a\s H:i') ?>
    </div>

    <h2>Estadísticas Generales de Pasantes</h2>
    <table cellspacing="0" cellpadding="0">
        <tr>
            <td class="kpi"><span class="knum"><?= $stats['total'] ?></span><span class="klbl">Total</span></td>
            <td class="kpi"><span class="knum"><?= $stats['activo'] ?></span><span class="klbl">Activos</span></td>
            <td class="kpi"><span class="knum"><?= $stats['finalizado'] ?></span><span class="klbl">Finalizados</span></td>
            <td class="kpi"><span class="knum"><?= $stats['retirado'] ?></span><span class="klbl">Retirados</span></td>
            <td class="kpi"><span class="knum"><?= $stats['pendiente'] ?></span><span class="klbl">Pendientes</span></td>
        </tr>
    </table>

    <h2>Top 3 Departamentos con Mayor Asignación</h2>
    <?php if (!empty($departamentos)): ?>
    <table cellspacing="0" cellpadding="0">
        <thead><tr><th width="6%">#</th><th width="74%">Departamento / Área</th><th width="20%" class="c">Pasantes</th></tr></thead>
        <tbody>
            <?php foreach ($departamentos as $i => $depto): ?>
            <tr class="<?= $i % 2 ? 'alt' : '' ?>">
                <td class="c b"><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($depto->nombre) ?></td>
                <td class="c b"><?= $depto->total ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="font-size:10px; color:#777; text-align:center;">Sin datos de departamentos para el período.</p>
    <?php endif; ?>

    <h2>Distribución por Institución de Procedencia</h2>
    <?php if (!empty($instituciones)): ?>
    <table cellspacing="0" cellpadding="0">
        <thead><tr><th width="80%">Institución Educativa</th><th width="20%" class="c">Pasantes</th></tr></thead>
        <tbody>
            <?php foreach ($instituciones as $i => $inst): ?>
            <tr class="<?= $i % 2 ? 'alt' : '' ?>">
                <td><?= htmlspecialchars($inst->nombre) ?></td>
                <td class="c b"><?= $inst->total ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="font-size:10px; color:#777; text-align:center;">Sin datos de instituciones para el período.</p>
    <?php endif; ?>

    <div class="foot">
        Documento generado electrónicamente &mdash; Sistema de Gestión de Pasantes (SGP) &mdash;
        Instituto de Salud Pública de Bolívar &mdash; <?= date('d/m/Y H:i') ?>
    </div>

</body>
</html>
