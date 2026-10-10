<?php
// ── Datos calculados ──────────────────────────────────────────────────────────
$nombrePasante = mb_strtoupper(trim(($eval->nombres ?? '') . ' ' . ($eval->apellidos ?? '')));
$cedula = htmlspecialchars($eval->cedula ?? '');
$instNombre = htmlspecialchars($eval->inst_nombre ?? 'N/D');
$departamento = htmlspecialchars($eval->departamento ?? 'N/D');
$fechaEval = !empty($eval->fecha_evaluacion) ? date('d/m/Y', strtotime($eval->fecha_evaluacion)) : '—';

$fechaInicio = !empty($eval->fecha_inicio_pasantia) ? date('d/m/Y', strtotime($eval->fecha_inicio_pasantia)) : '—';
$fechaFin = !empty($eval->fecha_fin_estimada) ? date('d/m/Y', strtotime($eval->fecha_fin_estimada)) : '—';

if (!empty($eval->fecha_inicio_pasantia) && !empty($eval->fecha_fin_estimada)) {
    $dias = (int) ((strtotime($eval->fecha_fin_estimada) - strtotime($eval->fecha_inicio_pasantia)) / 86400);
    $semanas = max(1, round($dias / 7));
} elseif (!empty($eval->horas_meta)) {
    $semanas = max(1, round((int) $eval->horas_meta / 40));
} else {
    $semanas = '—';
}
$duracion = is_numeric($semanas) ? $semanas . ' SEMANAS' : $semanas;
$tutorNombre = mb_strtoupper(trim(($eval->tutor_nombres ?? '') . ' ' . ($eval->tutor_apellidos ?? '')));
$tutorCargo = mb_strtoupper($eval->tutor_cargo ?? '');
$tutorTel = htmlspecialchars($eval->tutor_tel ?? '');
$jefeNombre = mb_strtoupper(trim(($eval->jefe_nombres ?? '') . ' ' . ($eval->jefe_apellidos ?? '')));
$jefeCargo = mb_strtoupper($eval->jefe_cargo ?? '');
$jefeTel = htmlspecialchars($eval->jefe_tel ?? '');

$prom = (float) ($eval->promedio_final ?? 0);
if ($prom >= 4.5)
    $resultado = 'EXCELENTE';
elseif ($prom >= 3.5)
    $resultado = 'MUY BUENO';
elseif ($prom >= 2.5)
    $resultado = 'BUENO';
elseif ($prom >= 1.5)
    $resultado = 'REGULAR';
else
    $resultado = 'DEFICIENTE';

// Cintillo como Base64 con altura controlada
$imgPath = __DIR__ . '/../../public/img/cintillo_isp_bolivar.jpg';
$bCintillo = file_exists($imgPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imgPath)) : '';

$criterios = [
    'criterio_iniciativa' => ['Iniciativa', 'Demuestra interés para desenvolverse por si mismo en la Institución, estableciendo la colaboración necesaria. Tiene capacidad creadora, aporta ideas y sugerencias para el mejoramiento de las condiciones de trabajo y correcciones de fallas.'],
    'criterio_interes' => ['Interés', 'Manifiesta interés y entusiasmo para aprender y asimilar nuevos conocimientos mediante el entrenamiento, se preocupa por presentar a tiempo las tareas o informes exigidos siguiendo las instrucciones recibidas.'],
    'criterio_conocimiento' => ['Conocimiento de Trabajo', 'Su preparación educativa y formación técnica le permite aplicar en la práctica los conocimientos adquiridos de manera efectiva, tiene suficientes conocimientos básicos para comprender y resolver problemas en su área de trabajo.'],
    'criterio_analisis' => ['Habilidad Analítica', 'Su razonamiento sobre problemas Técnicos son lógicos, sus opiniones censatas y correctas es efectivo en exponer soluciones adecuadas a circunstancias imprevistas en la empresa.'],
    'criterio_comunicacion' => ['Comunicación', 'Tiene habilidad para exponer y razonar con propiedad sus conocimientos e ideas, sabe escuchar, su comunicación oral y escrita es clara, precisa y concisa.'],
    'criterio_aprendizaje' => ['Habilidad de Aprendizaje', 'Tiene capacidad para aprender y entrenarse rápidamente, adquiere fácilmente los conocimientos sobre los procesos administrativos, procedimientos, instrumento y equipos de trabajo, adaptándose a ellos, siendo su actitud hacia la institución positiva.'],
    'criterio_companerismo' => ['Compañerismo', 'Mantiene relaciones inter personales con sus compañeros y trabajadores de la empresa, en su trato es cortes y educado con los demás, muestra disposición para trabajar en grupo sin ser intransigente y obstaculizando.'],
    'criterio_cooperacion' => ['Cooperación', 'Dispuesto a colaborar con sus compañeros de estudio y supervisores, espontáneamente, aun cuando no se le exige tratar de ser útil se puede contar con él para trabajo adicionales simples.'],
    'criterio_puntualidad' => ['Puntualidad y Asistencia', 'Cumple con el horario normal de trabajo, rara vez llega con retraso, asiste con regularidad y falta solo por razones muy bien justificadas.'],
    'criterio_presentacion' => ['Presentación del Trabajo', 'Cumple con los requisitos de Presentación exigidos por la Institución.'],
    'criterio_desarrollo' => ['Desarrollo Analítico', 'El o los problemas están planteados de forma clara, precisa y objetiva que permiten captar los principios y técnicas de solución empleados en el trabajo.'],
    'criterio_analisis_res' => ['Análisis de Resultados', 'Presenta el estudio realizado, una comparación acorde con los valores obtenidos.'],
    'criterio_conclusiones' => ['Conclusiones', 'Cumple el trabajo con sus respectivas consecuencias y resultados al término del mismo.'],
    'criterio_recomendacion' => ['Recomendación', 'Desarrolla razonamiento y consejo lógico acerca de los problemas analizados.'],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: letter portrait;
            margin: 0.5cm 1.0cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }

        /* Cintillo: ancho completo, altura proporcional controlada */
        .cintillo {
            width: 100%;
            margin-bottom: 2px;
            text-align: center;
        }

        .cintillo img {
            max-width: 100%;
            height: 13mm;
            object-fit: contain;
        }

        /* Títulos institucionales: serif para fidelidad al documento oficial */
        .inst-titles {
            text-align: center;
            margin-bottom: 2px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .inst-titles .subtitulo {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            margin-top: 1px;
            text-decoration: underline;
            font-weight: bold;
        }

        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: -1px;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 2px;
            vertical-align: middle;
            font-size: 9px;
        }

        /* Encabezado de sección (fila gris) */
        .sec-hd td {
            background: #d9d9d9;
            font-weight: bold;
            text-align: center;
            font-size: 8.5px;
            padding: 2px;
        }

        /* Columnas de calificación */
        .th-cal {
            width: 6%;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            padding: 2px 2px;
            vertical-align: bottom;
        }

        .td-x {
            width: 6%;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            padding: 2px;
        }

        /* Filas de criterio */
        .td-nombre {
            width: 15%;
            font-weight: bold;
            font-size: 11px;
            vertical-align: middle;
            padding: 3px;
        }

        .td-desc {
            font-size: 11px;
            color: #111;
            vertical-align: middle;
            padding: 3px 4px;
            text-align: justify;
        }

        /* Firmas — fila superior con nombres */
        .firma-top td {
            font-size: 9px;
            padding: 3px 5px;
            vertical-align: top;
            height: 18px;
        }

        /* Firmas — fila inferior con espacio para sello */
        .firma-row td {
            height: 90px;
            vertical-align: bottom;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 5px;
        }
    </style>
</head>

<body>

    <!-- Cintillo reducido (altura fija 14mm) -->
    <?php if ($bCintillo): ?>
        <div class="cintillo">
            <img src="<?= $bCintillo ?>" alt="Cintillo ISP Bolívar">
        </div>
    <?php else: ?>
        <div style="width:100%;height:14mm;background:#162660;margin-bottom:3px;"></div>
    <?php endif; ?>

    <!-- Títulos institucionales -->
    <div class="inst-titles">
        DIRECCIÓN DE RECURSOS HUMANOS<br>
        DIVISIÓN DE DESARROLLO DEL PERSONAL<br>
        DEPARTAMENTO DE CAPACITACIÓN Y DESARROLLO ORGANIZACIONAL
        <div class="subtitulo">Evaluación de Pasantías</div>
    </div>

    <!-- DATOS DEL PASANTE -->
    <table>
        <tr class="sec-hd">
            <td colspan="4">Datos del Pasante</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Nombres y Apellido:</strong> <?= $nombrePasante ?></td>
            <td><strong>Cédula de Identidad:</strong> V- <?= $cedula ?></td>
            <td><strong>Tipo de Pasantía:</strong> REGULAR</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Instituto de Procedencia:</strong> <?= $instNombre ?></td>
            <td colspan="2"><strong>Especialidad que Cursa:</strong> MENCIÓN TELEMÁTICA</td>
        </tr>
        <tr>
            <td style="width:20%;"><strong>Semestre o Año que Cursa:</strong></td>
            <td style="width:25%;"><strong>Duración de Pasantías:</strong> <?= $duracion ?></td>
            <td style="width:35%;"><strong>Lapso Evaluado:</strong> Desde: <?= $fechaInicio ?> Hasta: <?= $fechaFin ?>
            </td>
            <td style="width:20%;"><strong>Fecha de Evaluación:</strong> <?= $fechaEval ?></td>
        </tr>
    </table>

    <!-- DATOS DEL TUTOR -->
    <table>
        <tr class="sec-hd">
            <td colspan="3">Datos del Tutor</td>
        </tr>
        <tr>
            <td style="width:38%;"><strong>Nombres y Apellidos:</strong> <?= $jefeNombre ?></td>
            <td style="width:34%;"><strong>Cargo:</strong> <?= $jefeCargo ?></td>
            <td style="width:28%;"><strong>Extensión Telefónica:</strong> <?= $jefeTel ?></td>
        </tr>
        <tr>
            <td><strong>Dirección:</strong> DIRECCIÓN DE TELEMÁTICA</td>
            <td><strong>División:</strong> DIVISIÓN DE SOPORTE TÉCNICO</td>
            <td><strong>Departamento/ Coordinación/ Unidad:</strong> SOPORTE TÉCNICO</td>
        </tr>
    </table>

    <!-- TABLA DE CRITERIOS -->
    <table>
        <thead>
            <tr>
                <td colspan="2" style="font-size:11px; padding:3px 4px;">
                    A continuación mencionamos los factores considerados para evaluar el desempeño del pasante y la
                    presentación del trabajo; para ello deber analizar cada factor y marcar con una equis (X) la casilla
                    que define la actuación del evaluado.
                </td>
                <th class="th-cal">Excelente</th>
                <th class="th-cal">Sobre<br>Promedio</th>
                <th class="th-cal">Promedio</th>
                <th class="th-cal">Bajo<br>Promedio</th>
                <th class="th-cal">Deficiente</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($criterios as $campo => [$label, $desc]):
                $val = (int) ($eval->$campo ?? 0); ?>
                <tr>
                    <td class="td-nombre"><?= $label ?></td>
                    <td class="td-desc"><?= $desc ?></td>
                    <?php for ($r = 5; $r >= 1; $r--): ?>
                        <td class="td-x"><?= ($val === $r) ? 'X' : '' ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" style="padding:3px 4px; font-size:8px; vertical-align:top; height:16px;">
                    <strong>Observaciones:</strong>
                    <?= !empty($eval->observaciones) ? '&nbsp;' . htmlspecialchars($eval->observaciones) : '' ?>
                </td>
                <td colspan="5"
                    style="text-align:right; padding:3px 4px; font-size:9px; font-weight:bold; vertical-align:bottom;">
                    Resultado: <?= $resultado ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- FIRMAS -->
    <table style="margin-top:3mm;">
        <tr class="firma-top">
            <td style="width:30%; border:1px solid #000;"><strong>Tutor Empresarial:</strong><br><?= $jefeNombre ?></td>
            <td style="width:25%; border:1px solid #000;"><strong>Evaluado:</strong></td>
            <td colspan="2" style="border:1px solid #000;">División de Desarrollo de Personal<br>(Departamento de
                Capacitación y Desarrollo Organizacional)</td>
        </tr>
        <tr class="firma-row">
            <td style="border:1px solid #000;"><strong>Firma y Sello:</strong></td>
            <td style="border:1px solid #000;"><strong>Firma y huella:</strong></td>
            <td style="width:28%; border:1px solid #000;"><strong>Firma y Sello:</strong></td>
            <td style="width:17%; border:1px solid #000;"><strong>Fecha:</strong></td>
        </tr>
    </table>

</body>

</html>