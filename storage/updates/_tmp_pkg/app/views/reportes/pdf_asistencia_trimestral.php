<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { size: letter portrait; margin: 1cm 1cm; }
body {
    font-family: Helvetica, Arial, sans-serif;
    font-size: 11px;
    color: #000;
    line-height: 1.2;
}
table { border-collapse: collapse; }
.info-table {
    width: 100%;
    margin-bottom: 8px;
    border: 1px solid #000;
}
.info-table td {
    border: 1px solid #000;
    padding: 3px 8px;
    vertical-align: middle;
}
.info-label {
    font-weight: bold;
    color: #162660;
    font-size: 10px;
    text-transform: uppercase;
    display: block;
    margin-bottom: 1px;
}
.info-value {
    font-size: 13px;
    font-weight: bold;
}
.asistencia-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.asistencia-table td {
    border: 1px solid #000;
    padding: 2px;
    text-align: center;
    vertical-align: middle;
    height: 22px;
}
.asistencia-table thead th {
    font-size: 10px !important;
}
.week-num-cell {
    background: #f8fafc;
    font-size: 10px;
}
.marca {
    font-weight: bold;
    font-size: 13px;
}
.marca-x { color: #1D9E75; }
.marca-a { color: #E24B4A; }
.marca-j { color: #BA7517; }
.divider-row td {
    border-bottom: 2px solid #162660;
}
.firma-table {
    width: 100%;
    margin-top: 35mm;
    border-collapse: collapse;
}
.firma-table td {
    width: 45%;
    text-align: center;
    padding: 0 15px;
    vertical-align: top;
}
.firma-linea {
    border-top: 1px solid #000;
    padding-top: 6px;
    margin-top: 30px;
    font-size: 11px;
    font-weight: bold;
}
.firma-sub {
    font-size: 11px;
    font-weight: normal;
}
.leyenda {
    margin-top: 6px;
    font-size: 9px;
    border: 1px solid #ccc;
    padding: 4px 7px;
}
</style>
</head>
<body>

<?php include __DIR__ . '/comunes/header.php'; ?>

<p style="text-align:center; font-size:13px; font-weight:bold;
   color:#162660; text-transform:uppercase; margin:5px 0 8px;">
    Registro y Control de Asistencia
</p>

<table style="width:100%; border-collapse:collapse;
              margin-bottom:8px; border:1px solid #000;">
    <tr>
        <td style="width:28%; border:1px solid #000;
                   padding:5px 8px; font-size:11px;
                   font-weight:bold; background:#f1f5f9;
                   vertical-align:middle;">
            Nombre y Apellido:
        </td>
        <td style="border:1px solid #000; padding:5px 8px;
                   font-size:13px; font-weight:bold;
                   vertical-align:middle;">
            <?= htmlspecialchars(mb_strtoupper(
                ($pasante->apellidos ?? '') . ' ' .
                ($pasante->nombres ?? ''), 'UTF-8'
            )) ?>
        </td>
    </tr>
    <tr>
        <td style="width:28%; border:1px solid #000;
                   padding:5px 8px; font-size:11px;
                   font-weight:bold; background:#f1f5f9;
                   vertical-align:middle;">
            Tutor Empresarial:
        </td>
        <td style="border:1px solid #000; padding:5px 8px;
                   font-size:13px; font-weight:bold;
                   vertical-align:middle;">
            <?= htmlspecialchars(mb_strtoupper(
                trim(($pasante->tutor_cargo ?? '') . ' ' .
                ($pasante->tutor_nombre ?? 'N/D')), 'UTF-8'
            )) ?>
        </td>
    </tr>
    <tr>
        <td style="width:28%; border:1px solid #000;
                   padding:5px 8px; font-size:11px;
                   font-weight:bold; background:#f1f5f9;
                   vertical-align:middle;">
            Dependencia:
        </td>
        <td style="border:1px solid #000; padding:5px 8px;
                   font-size:13px; font-weight:bold;
                   vertical-align:middle;">
            <?= htmlspecialchars(mb_strtoupper(
                $pasante->departamento ?? 'N/D', 'UTF-8'
            )) ?>
        </td>
    </tr>
    <tr>
        <td style="width:28%; border:1px solid #000;
                   padding:5px 8px; font-size:11px;
                   font-weight:bold; background:#f1f5f9;
                   vertical-align:middle;">
            Lapso de Pasant&iacute;as:
        </td>
        <td style="border:1px solid #000; padding:5px 8px;
                   font-size:13px; font-weight:bold;
                   vertical-align:middle;">
            Desde: <?= date('d/m/Y', strtotime($pasante->fecha_inicio_pasantia)) ?>
            &nbsp;&nbsp;&nbsp;
            Hasta: <?= date('d/m/Y', strtotime($pasante->fecha_fin_estimada)) ?>
        </td>
    </tr>
</table>

<table class="asistencia-table">
    <thead>
        <tr>
            <th rowspan="2"
                style="background-color:#162660 !important; color:#ffffff !important;
                       font-weight:bold; padding:8px 4px; text-align:center;
                       border:1px solid #000; vertical-align:middle; width:13%;
                       font-size:13px; letter-spacing:.3px;">
                Semana<br>N&ordm;
            </th>
            <th colspan="7"
                style="background-color:#162660 !important; color:#ffffff !important;
                       font-weight:bold; padding:8px 2px; text-align:center;
                       border:1px solid #000; font-size:14px; letter-spacing:.5px;">
                D&iacute;as
            </th>
            <th colspan="2"
                style="background-color:#162660 !important; color:#ffffff !important;
                       font-weight:bold; padding:8px 2px; text-align:center;
                       border:1px solid #000; width:14%; font-size:13px; letter-spacing:.3px;">
                Firma
            </th>
            <th rowspan="2"
                style="background-color:#162660 !important; color:#ffffff !important;
                       font-weight:bold; padding:8px 4px; text-align:center;
                       border:1px solid #000; width:14%; font-size:13px; letter-spacing:.3px;
                       vertical-align:middle;">
                Observaciones
            </th>
        </tr>
        <tr>
            <?php foreach(['D','L','M','M','J','V','S'] as $dia): ?>
            <th style="background-color:#162660 !important; color:#ffffff !important;
                       font-weight:bold; padding:5px 2px; text-align:center;
                       border:1px solid #000; width:6%; font-size:13px;"><?= $dia ?></th>
            <?php endforeach; ?>
            <th style="background-color:#162660 !important; color:#ffffff !important; border:1px solid #000; width:7%;"></th>
            <th style="background-color:#162660 !important; color:#ffffff !important; border:1px solid #000; width:7%;"></th>
        </tr>
    </thead>
    <tbody>
    <?php
    // Usar DateTime para evitar problemas de zona horaria con aritmética de timestamps
    $dtInicio = new DateTime($fecha_inicio); // primer día del trimestre (Lunes del pasante)

    // Retroceder al Domingo de la primera semana del trimestre
    $dow_inicio = (int)$dtInicio->format('w'); // 0=Dom, 1=Lun ... 6=Sab
    $dtDomingo1 = (clone $dtInicio)->modify("-{$dow_inicio} days");

    // Columnas: D=0, L=1, M=2, M=3, J=4, V=5, S=6
    $orden_dias = [0, 1, 2, 3, 4, 5, 6];

    for ($s = 1; $s <= 14; $s++):
        // Domingo de la semana s (cada semana avanza 7 días)
        $dtDomSem   = (clone $dtDomingo1)->modify('+' . (($s - 1) * 7) . ' days');
        // Número ISO de semana del año (solicitado por el usuario)
        $semana_num = (int)$dtDomSem->modify('+1 day')->format('W'); // Lunes de esa semana
        $dtDomSem   = (clone $dtDomingo1)->modify('+' . (($s - 1) * 7) . ' days'); // restaurar al domingo
        $es_divisor = in_array($s, [5, 10, 14]);
    ?>
    <tr style="height:30px;<?= $es_divisor ? 'border-bottom:2px solid #162660;' : '' ?>">
        <td style="background:#e8e8e8; text-align:center;
                   border:1px solid #000; font-size:11px;
                   vertical-align:middle; font-weight:bold;
                   padding:4px;">
            <?= $semana_num ?>
        </td>
        <?php foreach ($orden_dias as $dow):
            $dtDia    = (clone $dtDomSem)->modify("+{$dow} days");
            $fecha_dia = $dtDia->format('Y-m-d');
            $reg = $asistencias_procesadas[$fecha_dia][$dow] ?? null;
        ?>
        <td style="text-align:center; border:1px solid #000;
                   padding:2px; vertical-align:middle;">
            <?php if ($reg):
                $estado = $reg->estado ?? '';
                if (in_array($estado, ['Presente','abierto','cerrado'])): ?>
                    <strong style="color:#1D9E75;font-size:13px;">X</strong>
                <?php elseif ($estado === 'Ausente'): ?>
                    <strong style="color:#E24B4A;font-size:13px;">A</strong>
                <?php elseif ($estado === 'Justificado'): ?>
                    <strong style="color:#BA7517;font-size:13px;">J</strong>
                <?php endif;
            endif; ?>
        </td>
        <?php endforeach; ?>
        <td style="border:1px solid #000;"></td>
        <td style="border:1px solid #000;"></td>
        <td style="border:1px solid #000;"></td>
    </tr>
    <?php endfor; ?>
    </tbody>
</table>

<div class="leyenda">
    <strong>Leyenda:</strong>
    <strong style="color:#1D9E75;">X</strong> = Asisti&oacute; &nbsp;|&nbsp;
    <strong style="color:#E24B4A;">A</strong> = Ausencia injustificada &nbsp;|&nbsp;
    <strong style="color:#BA7517;">J</strong> = Justificado
</div>

<table class="firma-table">
    <tr>
        <td>
            <div class="firma-linea">
                Pasante (Firma y Huella)<br>
                <span class="firma-sub">
                    <?= htmlspecialchars(
                        mb_strtoupper(
                            ($pasante->apellidos ?? '') . ' ' .
                            ($pasante->nombres ?? ''),
                            'UTF-8'
                        )
                    ) ?>
                </span><br>
                <span class="firma-sub">
                    C.I: <?= htmlspecialchars($pasante->cedula ?? 'N/D') ?>
                </span>
            </div>
        </td>
        <td style="width:10%;"></td>
        <td>
            <div class="firma-linea">
                Tutor Empresarial (Firma y Sello)<br>
                <span class="firma-sub">
                    <?= htmlspecialchars(
                        mb_strtoupper($pasante->tutor_nombre ?? 'N/D', 'UTF-8')
                    ) ?>
                </span><br>
                <span class="firma-sub">
                    <?= htmlspecialchars($pasante->tutor_cargo ?? '') ?>
                </span>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
