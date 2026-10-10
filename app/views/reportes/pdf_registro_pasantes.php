<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro General de Pasantes</title>
    <style>
        @page { margin: 1.5cm 1.8cm; }
        body   { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #111; line-height: 1.4; }
        h1     { font-size: 14px; text-align: center; text-transform: uppercase; margin: 0 0 2px; }
        h2     { font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #999; padding-bottom: 3px; margin: 14px 0 7px; }
        .sub   { text-align: center; font-size: 9px; color: #555; margin-bottom: 12px; }
        table  { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 12px; }
        th     { background: #e8e8e8; padding: 5px 6px; text-align: left; border: 1px solid #ccc; font-size: 8px; text-transform: uppercase; }
        td     { padding: 4px 6px; border: 1px solid #ddd; vertical-align: middle; }
        .alt   { background: #f7f7f7; }
        .c     { text-align: center; }
        .b     { font-weight: bold; }
        .foot  { border-top: 1px solid #bbb; margin-top: 14px; padding-top: 5px; text-align: center; font-size: 8px; color: #777; }
    </style>
</head>
<body>

    <?php include 'comunes/header.php'; ?>

    <h1>Registro General de Pasantes</h1>
    <div class="sub">
        <?= htmlspecialchars($subtitulo_pdf) ?>
        &nbsp;|&nbsp; Generado el <?= date('d/m/Y \\a \\l\\a\\s H:i') ?>
    </div>

    <h2>Listado de Pasantes Registrados</h2>

    <?php if (!empty($pasantes)): ?>
    <table cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th width="4%"  class="c">N°</th>
                <th width="9%">Cédula</th>
                <th width="22%">Nombres y Apellidos</th>
                <th width="22%">Institución</th>
                <th width="20%">Departamento</th>
                <th width="11%"  class="c">F. Inicio</th>
                <th width="12%" class="c">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pasantes as $i => $p): ?>
            <?php
                $nombre    = htmlspecialchars(trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')));
                $cedula    = htmlspecialchars($p->cedula       ?? '—');
                $inst      = htmlspecialchars($p->institucion  ?? 'Sin especificar');
                $depto     = htmlspecialchars($p->departamento ?? 'Sin asignar');
                $fInicio   = $p->fecha_inicio ? date('d/m/Y', strtotime($p->fecha_inicio)) : '—';
                $estadoRaw = strtolower($p->estado ?? '');
                $estadoTxt = htmlspecialchars(ucfirst($p->estado ?? '—'));
                $estadoClr = match($estadoRaw) {
                    'activo'     => '#16a34a',
                    'finalizado' => '#1d4ed8',
                    'retirado'   => '#9ca3af',
                    default      => '#f59e0b',
                };
            ?>
            <tr class="<?= $i % 2 !== 0 ? 'alt' : '' ?>">
                <td class="c"><?= $i + 1 ?></td>
                <td><?= $cedula ?></td>
                <td class="b"><?= $nombre ?></td>
                <td><?= $inst ?></td>
                <td><?= $depto ?></td>
                <td class="c"><?= $fInicio ?></td>
                <td class="c" style="color:<?= $estadoClr ?>; font-weight:bold;"><?= $estadoTxt ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="sub" style="margin-top:4px;">
        Total de registros: <strong><?= count($pasantes) ?></strong>
    </div>
    <?php else: ?>
    <p style="text-align:center; color:#777; font-size:10px;">No hay pasantes registrados para los filtros seleccionados.</p>
    <?php endif; ?>

    <div class="foot">
        Documento generado electrónicamente &mdash; Sistema de Gestión de Pasantes (SGP) &mdash;
        Instituto de Salud Pública de Bolívar &mdash; <?= date('d/m/Y H:i') ?>
    </div>

</body>
</html>
