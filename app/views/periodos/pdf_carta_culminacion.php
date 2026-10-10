<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* ── Cintillo institucional ────────────────────────────────── */
        .cintillo-wrap {
            width: 100%;
            margin-bottom: 0;
            line-height: 0;
        }

        /* ── Línea divisoria ───────────────────────────────────────── */
        .linea-navy {
            width: 100%;
            height: 3px;
            background-color: #162660;
            margin-bottom: 20px;
        }

        /* ── Contenedor principal ──────────────────────────────────── */
        .contenido {
            padding: 0 30px 30px;
        }

        /* ── Lugar y fecha ─────────────────────────────────────────── */
        .lugar-fecha {
            text-align: right;
            font-size: 10.5px;
            color: #444;
            margin-bottom: 24px;
        }

        /* ── Título del documento ──────────────────────────────────── */
        .titulo-doc {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #162660;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .subtitulo-doc {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-bottom: 28px;
        }

        /* ── Referencia ────────────────────────────────────────────── */
        .referencia {
            font-size: 10px;
            color: #555;
            margin-bottom: 20px;
        }

        /* ── Cuerpo de la carta ────────────────────────────────────── */
        .cuerpo {
            font-size: 11px;
            color: #1a1a1a;
            text-align: justify;
            line-height: 1.8;
            margin-bottom: 18px;
        }

        /* ── Tabla de datos del pasante ────────────────────────────── */
        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 20px;
            font-size: 10.5px;
        }
        .tabla-datos th {
            background: #162660;
            color: white;
            padding: 7px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .tabla-datos td {
            padding: 7px 12px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }
        .tabla-datos tr:nth-child(even) td {
            background: #f8fafc;
        }
        .tabla-datos td.etiqueta {
            font-weight: bold;
            color: #374151;
            width: 35%;
            background: #f1f5f9;
        }

        /* ── Firma ─────────────────────────────────────────────────── */
        .firmas {
            margin-top: 50px;
        }
        .firma-bloque {
            display: inline-block;
            width: 40%;
            text-align: center;
            vertical-align: top;
        }
        .firma-linea {
            border-top: 1px solid #1a1a1a;
            padding-top: 6px;
            margin-top: 40px;
        }
        .firma-nombre {
            font-weight: bold;
            font-size: 10.5px;
            color: #162660;
        }
        .firma-cargo {
            font-size: 9.5px;
            color: #555;
            margin-top: 2px;
        }

        /* ── Sello / cuadro de registro ────────────────────────────── */
        .cuadro-registro {
            border: 1.5px solid #162660;
            border-radius: 4px;
            padding: 10px 14px;
            margin: 22px 0;
            font-size: 10px;
            color: #162660;
        }
        .cuadro-registro strong {
            display: block;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            border-bottom: 1px solid #162660;
            padding-bottom: 4px;
        }

        /* ── Pie de página ─────────────────────────────────────────── */
        .pie {
            margin-top: 30px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 8.5px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

<?php
/* ── Cintillo institucional (reutiliza el helper de reportes) ── */
$cintillo_path = __DIR__ . '/../../../public/img/cintillo_isp_bolivar.jpg';

if (!function_exists('imgToBase64')) {
    function imgToBase64(string $path): string {
        static $cache = [];
        if (isset($cache[$path])) return $cache[$path];
        if (file_exists($path)) {
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = ($ext === 'jpg') ? 'jpeg' : $ext;
            $cache[$path] = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            return $cache[$path];
        }
        return $cache[$path] = '';
    }
}
$b64 = imgToBase64($cintillo_path);

// Alias explícito para evitar colisión de nombre con la clase DatePeriod del IDE.
/** @var object $pd Período académico (stdClass) */
$pd = $periodo;
?>

<?php if ($b64): ?>
<div class="cintillo-wrap">
    <img src="<?= $b64 ?>" style="width:100%;height:auto;display:block;" alt="Cintillo Institucional ISP Bolívar">
</div>
<?php else: ?>
<div style="width:100%;padding:10px 20px;background-color:#162660;color:white;font-family:Helvetica,Arial,sans-serif;font-size:11pt;font-weight:bold;">
    Instituto de Salud Pública de Bolívar &nbsp;|&nbsp; Sistema de Gestión de Pasantías
</div>
<?php endif; ?>

<div class="linea-navy"></div>

<div class="contenido">

    <!-- Lugar y fecha -->
    <p class="lugar-fecha">
        <?php
        $meses_es = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        ?>
        Ciudad Bolívar, <?= date('d') ?> de <?= $meses_es[(int)date('m')] ?> de <?= date('Y') ?>
    </p>

    <!-- Título -->
    <p class="titulo-doc">Carta de Culminación de Pasantías</p>
    <p class="subtitulo-doc">Instituto de Salud Pública del Estado Bolívar — Despacho del Director General</p>

    <!-- Referencia -->
    <p class="referencia">
        Ref.: Constancia de Culminación &nbsp;/&nbsp;
        C.I. <?= htmlspecialchars($pasante->cedula ?? 'N/A') ?>
        &nbsp;/&nbsp; <?= date('Y') ?>
    </p>

    <!-- Saludo -->
    <p class="cuerpo">
        Quien suscribe, <strong>Coordinador(a) de Gestión de Recursos Humanos</strong> del Instituto de Salud Pública del Estado Bolívar (I.S.P. Bolívar), en uso de las atribuciones que le confieren las normas internas de la institución, por medio de la presente hace constar:
    </p>

    <!-- Cuerpo principal -->
    <p class="cuerpo">
        Que el(la) ciudadano(a) <strong><?= htmlspecialchars(trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''))) ?></strong>,
        titular de la Cédula de Identidad N.º <strong><?= htmlspecialchars($pasante->cedula ?? '—') ?></strong>,
        cursante en la institución educativa <strong><?= htmlspecialchars($pasante->institucion ?? 'Universidad / Instituto') ?></strong>,
        <strong>realizó satisfactoriamente</strong> su período de pasantías en el Departamento de
        <strong><?= htmlspecialchars($pasante->departamento ?? '—') ?></strong>
        de esta institución, completando un total de
        <strong><?= $totalHorasCalculadas ?> horas académicas</strong>
        (equivalente a <?= $totalPresentes ?> día<?= $totalPresentes !== 1 ? 's' : '' ?> de actividad),
        en el marco del período académico denominado
        <strong>"<?= htmlspecialchars($pd->nombre ?? 'N/A') ?>"</strong>.
    </p>

    <!-- Tabla resumen -->
    <table class="tabla-datos">
        <thead>
            <tr>
                <th colspan="2">Datos Académicos del Pasante</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="etiqueta">Nombre Completo</td>
                <td><?= htmlspecialchars(trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''))) ?></td>
            </tr>
            <tr>
                <td class="etiqueta">Cédula de Identidad</td>
                <td><?= htmlspecialchars($pasante->cedula ?? '—') ?></td>
            </tr>
            <tr>
                <td class="etiqueta">Institución de Procedencia</td>
                <td><?= htmlspecialchars($pasante->institucion ?? '—') ?></td>
            </tr>
            <tr>
                <td class="etiqueta">Departamento Asignado</td>
                <td><?= htmlspecialchars($pasante->departamento ?? '—') ?></td>
            </tr>
            <tr>
                <td class="etiqueta">Período Académico</td>
                <td><?= htmlspecialchars($pd->nombre ?? '—') ?></td>
            </tr>
            <tr>
                <td class="etiqueta">Fecha de Inicio</td>
                <td>
                    <?= !empty($pasante->fecha_inicio)
                        ? date('d/m/Y', strtotime($pasante->fecha_inicio))
                        : (!empty($pd->fecha_inicio) ? date('d/m/Y', strtotime($pd->fecha_inicio)) : '—')
                    ?>
                </td>
            </tr>
            <tr>
                <td class="etiqueta">Fecha de Finalización</td>
                <td>
                    <?= !empty($pasante->fecha_fin)
                        ? date('d/m/Y', strtotime($pasante->fecha_fin))
                        : (!empty($pd->fecha_fin) ? date('d/m/Y', strtotime($pd->fecha_fin)) : '—')
                    ?>
                </td>
            </tr>
            <tr>
                <td class="etiqueta">Días de Asistencia</td>
                <td><?= $totalPresentes ?> día<?= $totalPresentes !== 1 ? 's' : '' ?></td>
            </tr>
            <tr>
                <td class="etiqueta">Total de Horas Acreditadas</td>
                <td><strong><?= $totalPresentes * 8 ?> horas académicas</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Párrafo de cierre -->
    <p class="cuerpo">
        La presente constancia se expide a petición de la parte interesada, en la ciudad de Ciudad Bolívar, a la fecha arriba indicada, para los fines que estime convenientes.
    </p>

    <!-- Cuadro de registro -->
    <div class="cuadro-registro">
        <strong>Registro Institucional</strong>
        Documento generado por el Sistema de Gestión de Pasantías (SGP) del I.S.P. Bolívar.
        Fecha de emisión: <?= date('d/m/Y H:i') ?>.
        Este documento tiene validez oficial con la firma del Coordinador de Recursos Humanos.
    </div>

    <!-- Firmas -->
    <div class="firmas">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="width:45%;text-align:center;vertical-align:bottom;padding-top:50px;">
                    <div style="border-top:1px solid #1a1a1a;padding-top:6px;display:inline-block;min-width:180px;">
                        <div class="firma-nombre">Coordinador(a) de RRHH</div>
                        <div class="firma-cargo">Instituto de Salud Pública del Edo. Bolívar</div>
                    </div>
                </td>
                <td style="width:10%;"></td>
                <td style="width:45%;text-align:center;vertical-align:bottom;padding-top:50px;">
                    <div style="border-top:1px solid #1a1a1a;padding-top:6px;display:inline-block;min-width:180px;">
                        <div class="firma-nombre">Director(a) General</div>
                        <div class="firma-cargo">Instituto de Salud Pública del Edo. Bolívar</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Pie -->
    <div class="pie">
        Instituto de Salud Pública del Estado Bolívar &nbsp;|&nbsp;
        Sistema de Gestión de Pasantías (SGP) &nbsp;|&nbsp;
        Documento generado automáticamente — <?= date('Y') ?>
    </div>

</div>
</body>
</html>
