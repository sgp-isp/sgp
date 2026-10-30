<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha Personal</title>
    <style>
        @page { margin: 2cm 2.2cm; }
        body   { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #111; line-height: 1.5; }
        h1     { font-size: 20px; text-align: center; text-transform: uppercase; margin: 0 0 4px; }
        h2     { font-size: 16px; text-transform: uppercase; border-bottom: 1px solid #999; padding-bottom: 4px; margin: 18px 0 10px; }
        .sub   { text-align: center; font-size: 13px; color: #555; margin-bottom: 14px; }
        .ficha { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; }
        .ficha td { padding: 7px 10px; border: 1px solid #ddd; vertical-align: middle; }
        .ficha .lbl { background: #e8e8e8; font-weight: bold; text-transform: uppercase; width: 18%; font-size: 12px; }
        table  { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 14px; }
        th     { background: #e8e8e8; padding: 7px 8px; text-align: left; border: 1px solid #ccc; font-size: 12px; text-transform: uppercase; }
        td     { padding: 6px 8px; border: 1px solid #ddd; vertical-align: middle; }
        .alt   { background: #f7f7f7; }
        .c     { text-align: center; }
        .b     { font-weight: bold; }
        .foot  { border-top: 1px solid #bbb; margin-top: 18px; padding-top: 6px; text-align: center; font-size: 11px; color: #777; }
    </style>
</head>
<body>

    <?php include 'comunes/header.php'; ?>

    <h1>Ficha Personal</h1>
    <div class="sub">
        <?= htmlspecialchars($subtitulo_pdf) ?>
        &nbsp;|&nbsp; Generado el <?= date('d/m/Y \a \l\a\s H:i') ?>
    </div>

    <h2>Datos del Pasante</h2>
    <table class="ficha" cellspacing="0" cellpadding="0">
        <tr>
            <td class="lbl">Nombre</td>
            <td class="b"><?= htmlspecialchars(ucwords(strtolower(trim(($pasante->apellidos ?? '') . ', ' . ($pasante->nombres ?? ''))))) ?></td>
            <td class="lbl">Cédula</td>
            <td><?= htmlspecialchars($pasante->cedula ?? '—') ?></td>
        </tr>
        <tr>
            <td class="lbl">Departamento</td>
            <td><?= htmlspecialchars($pasante->departamento ?? 'Sin asignar') ?></td>
            <td class="lbl">Horas acum.</td>
            <?php $hAcum = (int)($pasante->horas_acumuladas ?? 0); $dAcum = (int)ceil($hAcum / 8); ?>
            <td><?= $hAcum ?> hrs (<?= $dAcum ?> día<?= $dAcum !== 1 ? 's' : '' ?>)</td>
        </tr>
    </table>

    <h2>Historial de Asistencias</h2>
    <table cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th width="5%"  class="c">N°</th>
                <th width="14%" class="c">Fecha</th>
                <th width="14%" class="c">H. Entrada</th>
                <th width="14%" class="c">H. Salida</th>
                <th width="18%" class="c">Estado</th>
                <th width="35%">Observación</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($asistencias)): ?>
            <tr>
                <td colspan="6" class="c" style="color:#777; padding: 10px;">Sin registros de asistencia.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($asistencias as $i => $a):
                $estadoRaw = strtolower(trim($a->estado ?? ''));
                $estadoClr = match($estadoRaw) {
                    'presente'     => '#16a34a',
                    'tarde'        => '#d97706',
                    'ausente'      => '#dc2626',
                    'justificado'  => '#2563eb',
                    default        => '#6b7280',
                };
            ?>
            <?php
                $hEnt = $a->hora_entrada ? date('H:i', strtotime($a->hora_entrada)) : '08:00';
                $hSal = $a->hora_salida  ? date('H:i', strtotime($a->hora_salida))  : '16:00';

                // Construir texto de observación (feriado específico tiene prioridad)
                if ($estadoRaw === 'justificado') {
                    $feriadoNombre = trim($a->feriado_nombre ?? '');
                    $motivo        = trim($a->motivo ?? '');
                    $esGenerico    = stripos($motivo, 'feriado') !== false && strlen($motivo) < 40;
                    if ($feriadoNombre !== '') {
                        $obsTexto = 'Feriado: ' . $feriadoNombre;
                    } elseif ($motivo !== '' && !$esGenerico) {
                        $obsTexto = $motivo;
                    } elseif ($esGenerico) {
                        $obsTexto = 'Dia feriado (no registrado en el calendario del sistema)';
                    } else {
                        $obsTexto = 'Justificacion sin motivo registrado';
                    }
                } else {
                    $obsTexto = '';
                }
            ?>
            <tr class="<?= $i % 2 !== 0 ? 'alt' : '' ?>">
                <td class="c"><?= $i + 1 ?></td>
                <td class="c"><?= $a->fecha ? date('d/m/Y', strtotime($a->fecha)) : '—' ?></td>
                <td class="c"><?= $hEnt ?></td>
                <td class="c"><?= $hSal ?></td>
                <td class="c" style="color:<?= $estadoClr ?>; font-weight:bold;"><?= htmlspecialchars(ucfirst($estadoRaw) ?: '—') ?></td>
                <td><?= htmlspecialchars($obsTexto) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($asistencias)): ?>
    <div class="sub" style="margin-top:4px;">
        Total de registros: <strong><?= count($asistencias) ?></strong>
    </div>
    <?php endif; ?>

    <div class="foot">
        Documento generado electrónicamente &mdash; Sistema de Gestión de Pasantes (SGP) &mdash;
        Instituto de Salud Pública de Bolívar &mdash; <?= date('d/m/Y H:i') ?>
    </div>

</body>
</html>
