<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 2.5cm 2cm; }
body {
    font-family: Helvetica, Arial, sans-serif;
    font-size: 12.5px; color: #0D1424; line-height: 1.75;
}
.carta-body { max-width: 100%; }
.ref-line {
    text-align: right; font-size: 11px;
    color: #64748b; margin-bottom: 24px;
}
.asunto {
    font-weight: bold; font-size: 15px; color: #162660;
    margin-bottom: 20px; border-bottom: 2px solid #162660;
    padding-bottom: 8px; text-align: center;
    letter-spacing: 1.5px;
}
.parrafo { margin-bottom: 16px; text-align: justify; }
.destacado {
    font-weight: bold; color: #162660;
}
.firma-block {
    margin-top: 60px; text-align: center;
}
.firma-linea {
    border-top: 1px solid #0D1424; display: inline-block;
    min-width: 260px; padding-top: 6px;
    font-size: 11px; font-weight: bold;
}
.firma-sub { font-size: 10px; font-weight: normal; color: #475569; }
.sello-box {
    border: 1px dashed #cbd5e1; border-radius: 6px;
    width: 100px; height: 80px; display: inline-block;
    margin-left: 40px; vertical-align: top; margin-top: 6px;
    color: #94a3b8; font-size: 9px; text-align: center;
    padding-top: 32px;
}
.footer-line {
    text-align: center; font-size: 9px; color: #94a3b8;
    margin-top: 36px; padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}
.ficha { width: 100%; border-collapse: collapse; font-size: 11px; margin: 14px 0; }
.ficha td { padding: 7px 12px; border: 1px solid #cbd5e1; vertical-align: middle; }
.ficha .lbl { background: #f8fafc; font-weight: bold; text-transform: uppercase; width: 22%; font-size: 10px; color: #162660; }
.firma-row { display: table; width: 100%; margin-top: 50px; }
.firma-col { display: table-cell; width: 50%; text-align: center; padding: 0 10px; vertical-align: bottom; }
</style>
</head>
<body>

<?php include __DIR__ . '/comunes/header.php'; ?>

<div class="carta-body">

    <div class="ref-line" style="display: table; width: 100%; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
        <div style="display: table-cell; text-align: left; font-size: 11px; font-weight: bold; color: #162660; letter-spacing: 0.5px;">
            N° DE CONTROL: <?= htmlspecialchars($codigoConstancia ?? ('SGP-CST-' . date('Ymd-His'))) ?>
        </div>
        <div style="display: table-cell; text-align: right; font-size: 11px; color: #64748b;">
            Ciudad Bolívar, <?= htmlspecialchars($fechaEmision ?? date('d \d\e F \d\e Y')) ?>
        </div>
    </div>

    <?php
    if (!empty($pasante->nombre_completo)) {
        $nombreCompleto = mb_strtoupper(trim($pasante->nombre_completo), 'UTF-8');
    } else {
        $apellidos = trim($pasante->apellidos ?? '');
        $nombres   = trim($pasante->nombres ?? '');
        if ($nombres !== '' && $apellidos !== '') {
            $nombreCompleto = mb_strtoupper($nombres . ' ' . $apellidos, 'UTF-8');
        } else {
            $nombreCompleto = mb_strtoupper($nombres . $apellidos, 'UTF-8');
        }
    }

    $ciRaw      = trim($pasante->cedula ?? 'N/D');
    $ciTexto    = (preg_match('/^[VEJP]-/i', $ciRaw) || $ciRaw === 'N/D') ? $ciRaw : ('V-' . $ciRaw);
    $depto      = htmlspecialchars($pasante->departamento ?? 'N/D');
    $institucion= htmlspecialchars($pasante->institucion  ?? 'N/D');
    $horasAcum  = (int)($pasante->horas_acumuladas ?? 0);
    $horasMeta  = (int)($pasante->horas_meta       ?? 1440);
    // Si viene de modo manual y no tiene horas acumuladas pero sí horas meta o cumplidas
    if ($horasAcum === 0 && !empty($pasante->horas_meta)) {
        $horasAcum = (int)$pasante->horas_meta;
    }
    $periodoTxt = !empty($pasante->periodo_texto) ? htmlspecialchars($pasante->periodo_texto) : null;

    // La query usa COALESCE y alias 'fecha_inicio' / 'fecha_fin'
    $rawInicio  = $pasante->fecha_inicio ?? $pasante->fecha_inicio_pasantia ?? null;
    $rawFin     = $pasante->fecha_fin    ?? $pasante->fecha_fin_estimada    ?? null;
    $fechaInicio= $rawInicio ? date('d/m/Y', strtotime($rawInicio)) : 'N/D';
    $fechaFin   = $rawFin    ? date('d/m/Y', strtotime($rawFin))    : 'N/D';
    $tutor      = mb_strtoupper($pasante->tutor_nombre ?? 'N/D', 'UTF-8');
    $tutorCargo = htmlspecialchars($pasante->tutor_cargo ?? '');
    $jefeCargo  = htmlspecialchars($pasante->jefe_cargo ?? '');

    $mesesEs = ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $diaEmision = $diaEmision ?? date('d');
    $mesEmision = $mesEmision ?? $mesesEs[(int)date('n')];
    $anioEmision= $anioEmision ?? date('Y');

    // ── Conexión con Plantillas Maestras SGP (SuperAdmin) ─────────
    $db = Database::getInstance();
    $codigoPlantilla = (($tipo ?? 'servicio') === 'servicio') ? 'constancia_servicio' : 'constancia_culminacion';
    $db->query("SELECT * FROM plantillas_reportes WHERE codigo = :cod LIMIT 1");
    $db->bind(':cod', $codigoPlantilla);
    $plantillaMaestra = $db->single();

    $cuerpoDesdePlantilla = null;
    $tituloDesdePlantilla = null;
    $firmasConfiguradas   = null;

    if ($plantillaMaestra) {
        if (!empty($plantillaMaestra->firmas_json)) {
            $firmasConfiguradas = json_decode($plantillaMaestra->firmas_json, true);
        }

        if (!empty($plantillaMaestra->es_personalizada)) {
            $varsMap = [
                '{NOMBRE_PASANTE}'   => htmlspecialchars($nombreCompleto),
                '{CEDULA_PASANTE}'   => htmlspecialchars($ciTexto),
                '{DEPARTAMENTO}'     => $depto,
                '{INSTITUCION}'      => $institucion,
                '{PERIODO}'          => $periodoTxt ?: ($fechaInicio !== 'N/D' ? ($fechaInicio . ' al ' . $fechaFin) : '6 Meses'),
                '{HORAS_ACUMULADAS}' => (string)$horasAcum,
                '{HORAS_META}'       => (string)$horasMeta,
                '{FECHA_INICIO}'     => $fechaInicio,
                '{FECHA_FIN}'        => $fechaFin,
                '{FECHA_EMISION}'    => $diaEmision . ' de ' . $mesEmision . ' de ' . $anioEmision,
                '{NUMERO_CONTROL}'   => htmlspecialchars($codigoConstancia ?? ('SGP-CST-' . date('Ymd-His'))),
                '{TUTOR_NOMBRE}'     => $tutor,
                '{TUTOR_CARGO}'      => $tutorCargo ?: 'Tutor Institucional'
            ];
            $cuerpoDesdePlantilla = str_replace(array_keys($varsMap), array_values($varsMap), $plantillaMaestra->cuerpo_html);
            $tituloDesdePlantilla = str_replace(array_keys($varsMap), array_values($varsMap), $plantillaMaestra->titulo_documento);
        }
    }
    ?>

    <?php if (!empty($pasante->cuerpo_personalizado)): ?>
    <!-- ══════════════════ TEXTO EDITADO PUNTUAL PARA ESTE PASANTE ══════════════════ -->
    <div class="asunto"><?= (($tipo ?? 'servicio') === 'servicio') ? 'CONSTANCIA DE PASANTÍA' : 'CARTA DE CULMINACIÓN DE PASANTÍAS PROFESIONALES' ?></div>
    <div class="parrafo" style="white-space: pre-line; text-align: justify; margin-bottom: 20px;">
        <?= $pasante->cuerpo_personalizado ?>
    </div>

    <?php elseif ($cuerpoDesdePlantilla): ?>
    <!-- ══════════════════ TEXTO DE PLANTILLA MAESTRA DEL SUPERADMIN ══════════════════ -->
    <div class="asunto"><?= htmlspecialchars($tituloDesdePlantilla ?: ((($tipo ?? 'servicio') === 'servicio') ? 'CONSTANCIA DE PASANTÍA' : 'CARTA DE CULMINACIÓN DE PASANTÍAS PROFESIONALES')) ?></div>
    <div class="parrafo" style="text-align: justify; margin-bottom: 20px;">
        <?= $cuerpoDesdePlantilla ?>
    </div>

    <?php elseif (($tipo ?? 'servicio') === 'servicio'): ?>
    <!-- ══════════════════ CONSTANCIA DE SERVICIO (DEFAULT) ══════════════════ -->

    <div class="asunto">CONSTANCIA DE PASANTÍA</div>

    <p class="parrafo">
        Quien suscribe, en su carácter de responsable del 
        <span class="destacado"><?= $depto ?></span>, adscrito a la 
        <span class="destacado">División de Soporte Técnico</span> de la 
        <span class="destacado">Dirección de Telemática</span> del Instituto de Salud Pública del Estado Bolívar,
        hace constar por medio de la presente que el/la ciudadano/a
        <span class="destacado"><?= htmlspecialchars($nombreCompleto) ?></span>,
        titular de la Cédula de Identidad Nro. <span class="destacado"><?= htmlspecialchars($ciTexto) ?></span>,
        se encuentra realizando pasantías en la institución bajo la supervisión del área de
        <span class="destacado"><?= $depto ?></span>,
        cumpliendo una carga horaria de <span class="destacado"><?= $horasMeta ?> horas</span>
        <?php if ($periodoTxt): ?>
        correspondiente a un período de <span class="destacado"><?= $periodoTxt ?></span>.
        <?php elseif ($fechaInicio !== 'N/D'): ?>
        con fecha de inicio el <span class="destacado"><?= $fechaInicio ?></span>
        <?php if ($fechaFin !== 'N/D'): ?>
        y fecha de culminación estimada el <span class="destacado"><?= $fechaFin ?></span>
        <?php endif; ?>.
        <?php else: ?>.
        <?php endif; ?>
    </p>

    <p class="parrafo">
        La presente constancia se expide a petición de la parte interesada, a los <?= htmlspecialchars($diaEmision) ?> días
        del mes de <?= htmlspecialchars($mesEmision) ?> de <?= htmlspecialchars($anioEmision) ?>,
        en Ciudad Bolívar, Estado Bolívar.
    </p>

    <?php else: ?>
    <!-- ══════════════════ CARTA DE CULMINACIÓN (DEFAULT) ══════════════════ -->

    <div class="asunto">CARTA DE CULMINACIÓN DE PASANTÍAS PROFESIONALES</div>

    <p class="parrafo">
        Quien suscribe, en su carácter de responsable del 
        <span class="destacado"><?= $depto ?></span>, adscrito a la 
        <span class="destacado">División de Soporte Técnico</span> de la 
        <span class="destacado">Dirección de Telemática</span> del Instituto de Salud Pública del Estado Bolívar,
        certifica por medio de la presente que el/la ciudadano/a
        <span class="destacado"><?= htmlspecialchars($nombreCompleto) ?></span>,
        titular de la Cédula de Identidad Nro. <span class="destacado"><?= htmlspecialchars($ciTexto) ?></span>,
        ha <span class="destacado">culminado satisfactoriamente</span> sus Pasantías Profesionales
        en la institución bajo la supervisión del área de <span class="destacado"><?= $depto ?></span>,
        <?php if ($periodoTxt): ?>
        durante un período de <span class="destacado"><?= $periodoTxt ?></span>,
        <?php endif; ?>
        completando una carga horaria de <span class="destacado"><?= $horasAcum ?> horas</span> 
        cumpliendo con las <span class="destacado"><?= $horasMeta ?> horas</span> requeridas.
    </p>

    <p class="parrafo">
        Se expide la presente carta a petición de la parte interesada, a los <?= htmlspecialchars($diaEmision) ?> días
        del mes de <?= htmlspecialchars($mesEmision) ?> de <?= htmlspecialchars($anioEmision) ?>,
        en Ciudad Bolívar, Estado Bolívar.
    </p>
    <?php endif; ?>

    <!-- ══════════════════ TABLA DE DATOS (Estilo Antiguo + CSS Moderno) ══════════════════ -->
    <h2 style="font-size:12px; text-transform:uppercase; border-bottom:1px solid #cbd5e1; padding-bottom:4px; margin:24px 0 10px; color:#162660; letter-spacing:0.8px;">Datos del Pasante</h2>
    <table class="ficha" cellspacing="0" cellpadding="0">
        <tr>
            <td class="lbl">Nombre completo</td>
            <td><?= htmlspecialchars($nombreCompleto) ?></td>
            <td class="lbl">Cédula</td>
            <td><?= htmlspecialchars($ciTexto) ?></td>
        </tr>
        <tr>
            <td class="lbl">Departamento</td>
            <td><?= $depto ?></td>
            <td class="lbl">Estado</td>
            <td><?= htmlspecialchars($pasante->estado_pasantia ?? 'Finalizado') ?></td>
        </tr>
        <tr>
            <td class="lbl">Tutor Asignado</td>
            <td><?= $tutor ?></td>
            <td class="lbl">C.I. Tutor</td>
            <td><?= htmlspecialchars($pasante->tutor_cedula ?? 'N/D') ?></td>
        </tr>
        <tr>
            <?php if ($periodoTxt || $fechaInicio === 'N/D'): ?>
            <td class="lbl">Período / Duración</td>
            <td><?= $periodoTxt ?: '6 Meses' ?></td>
            <td class="lbl">Carga Horaria</td>
            <td><?= $horasMeta ?> Horas</td>
            <?php else: ?>
            <td class="lbl">Fecha Inicio</td>
            <td><?= $fechaInicio ?></td>
            <td class="lbl">Fecha Fin Est.</td>
            <td><?= $fechaFin ?></td>
            <?php endif; ?>
        </tr>
        <tr>
            <td class="lbl">Institución</td>
            <td colspan="3"><?= $institucion ?></td>
        </tr>
    </table>

</div>

<!-- ══════════════════ FIRMAS Y SELLO ══════════════════ -->
<?php
$cargoMostrar = !empty($firmasConfiguradas['firmante_cargo']) ? $firmasConfiguradas['firmante_cargo'] : 'Jefe de la División de Soporte Técnico de la Dirección de Telemática';
$nombreFirmanteMostrar = (!empty($firmasConfiguradas['firmante_nombre']) && $firmasConfiguradas['firmante_nombre'] !== '{TUTOR_NOMBRE}') ? $firmasConfiguradas['firmante_nombre'] : $tutor;
$mostrarSelloDoc = isset($firmasConfiguradas['mostrar_sello']) ? !empty($firmasConfiguradas['mostrar_sello']) : true;
?>
<div class="firma-row">
    <div class="firma-col" style="vertical-align: bottom;">
        <div class="firma-linea" style="min-width: 280px;">
            <?= htmlspecialchars($nombreFirmanteMostrar) ?><br>
            <span class="firma-sub" style="font-weight: bold; color: #162660; text-transform: uppercase;"><?= htmlspecialchars($cargoMostrar) ?></span>
        </div>
    </div>
    <?php if ($mostrarSelloDoc): ?>
    <div class="firma-col" style="vertical-align: bottom;">
        <div class="sello-box" style="margin: 0 auto 12px; display: block; width: 140px; height: 90px; padding-top: 40px; font-size: 9px; border-width: 2px;">Sello<br>Oficial</div>
    </div>
    <?php endif; ?>
</div>

<div class="footer-line">
    Documento generado por el Sistema de Gestión de Pasantes (SGP) &nbsp;|&nbsp;
    Instituto de Salud Pública del Estado Bolívar &nbsp;|&nbsp; <?= date('d/m/Y h:i A') ?>
</div>

</body>
</html>
