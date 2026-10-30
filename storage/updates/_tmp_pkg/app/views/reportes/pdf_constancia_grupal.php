<?php
/**
 * PDF: Constancia Grupal de Pasantes — SGP
 * Variables disponibles: $pasantes, $jefe, $tipo, $tipoLabel, $contexto, $estado
 */
$pathLogo = APPROOT . '/../public/img/cintillo_isp_bolivar.jpg';
$bLogo    = file_exists($pathLogo)
    ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathLogo))
    : '';

$totalPasantes = count($pasantes);
$hoy           = date('d/m/Y');
$hoySql        = date('Y-m-d');

// Agrupar por departamento para mostrar secciones
$grupos = [];
foreach ($pasantes as $p) {
    $dept = $p->departamento ?? 'Sin Departamento';
    $grupos[$dept][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 1.5cm 1.8cm 1.2cm 1.8cm; }
* { box-sizing: border-box; }
body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #1e293b; margin:0; padding:0; }

/* Header */
.doc-header { text-align:center; margin-bottom:14px; }
.doc-titulo  { font-size:13px; font-weight:bold; color:#172554; text-transform:uppercase; letter-spacing:0.5px; margin:8px 0 2px; }
.doc-sub     { font-size:9px; color:#64748b; margin:0 0 6px; }

/* Bloque de contexto */
.ctx-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:8px 14px; margin-bottom:12px; display:flex; justify-content:space-between; font-size:9px; }
.ctx-item strong { color:#1e293b; font-size:9.5px; }
.ctx-item span   { color:#64748b; }

/* Texto de presentación */
.doc-p { font-size:10px; color:#334155; line-height:1.6; text-align:justify; margin:0 0 12px; }

/* Tabla principal */
table.main { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:16px; }
table.main thead tr { background:#172554; color:white; }
table.main thead th { padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; letter-spacing:0.3px; }
table.main tbody tr { border-bottom:1px solid #f1f5f9; }
table.main tbody tr:nth-child(even) { background:#f8fafc; }
table.main tbody td { padding:5px 8px; vertical-align:middle; }
table.main tfoot tr { background:#f1f5f9; font-weight:bold; }
table.main tfoot td { padding:5px 8px; font-size:8.5px; }

/* Sección departamento */
.dept-header { background:#e0e7ff; padding:5px 10px; font-size:9px; font-weight:bold; color:#1e3a8a; margin:10px 0 4px; border-left:3px solid #2563eb; }

/* Firmas */
.firma-row { display:flex; justify-content:space-between; margin-top:28px; gap:16px; }
.firma-box { flex:1; text-align:center; }
.firma-line { border-top:1.5px solid #334155; margin-bottom:4px; padding-top:6px; }
.firma-nombre { font-size:9px; font-weight:bold; color:#1e293b; }
.firma-cargo  { font-size:8px; color:#64748b; }

/* Pie */
.pie { text-align:center; font-size:7.5px; color:#94a3b8; margin-top:14px; border-top:1px solid #f1f5f9; padding-top:6px; }

/* Badge estado */
.badge-fin { background:#dcfce7; color:#15803d; padding:1px 7px; border-radius:20px; font-weight:bold; font-size:7.5px; }
.badge-act { background:#dbeafe; color:#1e3a8a; padding:1px 7px; border-radius:20px; font-weight:bold; font-size:7.5px; }
</style>
</head>
<body>

<!-- Logo institucional -->
<?php if ($bLogo): ?>
<img src="<?= $bLogo ?>" style="width:100%;max-height:58px;object-fit:contain;margin-bottom:10px;">
<?php endif; ?>

<!-- Título del documento -->
<div class="doc-header">
    <div class="doc-titulo"><?= htmlspecialchars($tipoLabel) ?> — Relación de Pasantes</div>
    <div class="doc-sub">Instituto de Salud Pública del Estado Bolívar · División de Telemática</div>
</div>

<!-- Contexto del reporte -->
<div class="ctx-box">
    <div class="ctx-item"><span>Filtro: </span><strong><?= htmlspecialchars($contexto) ?></strong></div>
    <div class="ctx-item"><span>Estado: </span><strong><?= $estado === 'todos' ? 'Activos y Finalizados' : htmlspecialchars($estado) ?></strong></div>
    <div class="ctx-item"><span>Total pasantes: </span><strong><?= $totalPasantes ?></strong></div>
    <div class="ctx-item"><span>Fecha emisión: </span><strong><?= $hoy ?></strong></div>
</div>

<!-- Párrafo introductorio -->
<p class="doc-p">
    Quien suscribe, en su condición de responsable de la División de Telemática del Instituto de Salud
    Pública del Estado Bolívar, hace constar que los ciudadanos y ciudadanas relacionados a continuación
    <?= $tipo === 'culminacion'
        ? 'han <strong>culminado satisfactoriamente</strong> su período de pasantías'
        : 'prestaron <strong>servicios de pasantía</strong>' ?>
    en esta institución, conforme a los datos que se detallan:
</p>

<!-- Tabla con todos los pasantes agrupados por departamento -->
<?php $globalIdx = 1; foreach ($grupos as $deptNombre => $listaPasantes): ?>
<div class="dept-header"><?= htmlspecialchars($deptNombre) ?> <span style="font-weight:normal;color:#475569;">(<?= count($listaPasantes) ?> pasante<?= count($listaPasantes)!==1?'s':'' ?>)</span></div>
<table class="main">
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:26%;">Nombre Completo</th>
            <th style="width:10%;">Cédula</th>
            <th style="width:20%;">Institución de Origen</th>
            <th style="width:12%;">Período</th>
            <th style="width:10%;">Inicio</th>
            <th style="width:10%;">Fin</th>
            <th style="width:8%;">Horas</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($listaPasantes as $p):
        $hAcum = (int)($p->horas_acumuladas ?? 0);
        $hMeta = max(1, (int)($p->horas_meta ?? 1440));
        $pct   = min(100, round($hAcum / $hMeta * 100));
        $estadoBadge = ($p->estado_pasantia === 'Finalizado')
            ? '<span class="badge-fin">Finalizado</span>'
            : '<span class="badge-act">Activo</span>';
    ?>
    <tr>
        <td style="text-align:center;color:#94a3b8;font-weight:700;"><?= $globalIdx++ ?></td>
        <td style="font-weight:700;color:#1e293b;">
            <?= htmlspecialchars(mb_strtoupper(trim(($p->apellidos??'').' '.($p->nombres??'')), 'UTF-8')) ?>
            <?= $estadoBadge ?>
        </td>
        <td style="font-family:monospace;"><?= htmlspecialchars($p->cedula ?? '—') ?></td>
        <td><?= htmlspecialchars($p->institucion ?? '—') ?></td>
        <td><?= htmlspecialchars($p->periodo_nombre ?? '—') ?></td>
        <td><?= $p->fecha_inicio ? date('d/m/Y', strtotime($p->fecha_inicio)) : '—' ?></td>
        <td><?= $p->fecha_fin    ? date('d/m/Y', strtotime($p->fecha_fin))    : '—' ?></td>
        <td style="text-align:center;font-weight:700;color:#2563eb;"><?= $hAcum ?>h</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endforeach; ?>

<!-- Totales -->
<table class="main">
    <tfoot>
        <tr>
            <td colspan="7" style="text-align:right;color:#475569;">Total de pasantes relacionados:</td>
            <td style="text-align:center;color:#172554;font-size:11px;"><?= $totalPasantes ?></td>
        </tr>
    </tfoot>
</table>

<!-- Firmas -->
<div class="firma-row">
    <div class="firma-box">
        <div class="firma-line"></div>
        <div class="firma-nombre"><?= htmlspecialchars(trim(($jefe->nombres??'').' '.($jefe->apellidos??''))) ?></div>
        <div class="firma-cargo"><?= htmlspecialchars($jefe->cargo ?? 'Jefe de División') ?></div>
        <div class="firma-cargo">División de Telemática — ISP Bolívar</div>
    </div>
    <div class="firma-box">
        <div class="firma-line"></div>
        <div class="firma-nombre">Sello Institucional</div>
        <div class="firma-cargo">División de Desarrollo de Personal</div>
    </div>
</div>

<!-- Pie -->
<div class="pie">
    Documento generado el <?= $hoy ?> por el Sistema de Gestión de Pasantías (SGP) ·
    Instituto de Salud Pública del Estado Bolívar ·
    Este documento tiene validez con firma y sello originales.
</div>

</body>
</html>
