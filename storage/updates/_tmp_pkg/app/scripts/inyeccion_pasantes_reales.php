<?php
/**
 * inyeccion_pasantes_reales.php
 * Inyección de pasantes reales — Cohorte 2025-2026
 * Instituto de Salud Pública del Estado Bolívar
 *
 * INSTRUCCIONES:
 *   1. Ejecutar feriados_y_periodo_produccion.sql y depuracion_produccion.sql ANTES de este script.
 *   2. Abrir en el navegador: localhost/proyecto_sgp/sgp/app/scripts/inyeccion_pasantes_reales.php
 *   3. El script es idempotente: si un pasante ya existe (por cédula), lo omite.
 *
 * RESULTADO:
 *   - 12 usuarios creados (rol=3, password=cédula sin puntos)
 *   - datos_personales + datos_pasante para cada uno
 *   - Asistencias desde 2025-10-03 hasta HOY:
 *       Presente  → días laborables (L-V sin feriados)
 *       Justificado → días feriados (nacionales/regionales)
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

// ─────────────────────────────────────────────────────────────────────────────
// CONFIG BASE DE DATOS
// ─────────────────────────────────────────────────────────────────────────────
$dbHost = 'localhost';
$dbName = 'proyecto_sgp';
$dbUser = 'root';
$dbPass = '';

// ─────────────────────────────────────────────────────────────────────────────
// PARÁMETROS DE INYECCIÓN
// ─────────────────────────────────────────────────────────────────────────────
$FECHA_INICIO  = '2025-10-03';           // Inicio del período
$FECHA_FIN     = '2026-06-01';           // Fin del período
$HORAS_META    = 1376;                   // 172 días hábiles × 8h (Oct-3 → Jun-1, ambos inclusive)
$HASTA_HOY     = date('Y-m-d');          // Rellenar asistencias hasta hoy

// ─────────────────────────────────────────────────────────────────────────────
// DATOS DE LOS 12 PASANTES
// Formato: [cedula, nombres, apellidos, institucion_clave]
// ─────────────────────────────────────────────────────────────────────────────
$PASANTES = [
    // ── Juan Bautista (8 pasantes) ──────────────────────────────────────────
    ['32254951', 'ELIFER ISABEL',      'DEL VALLE DIAZ SARMINETO', 'juan_bautista'],
    ['33547626', 'ORIANNYS SHARAYS',   'RODRIGUEZ RENGIFO',        'juan_bautista'],
    ['33662103', 'JESUS SAMUEL',       'DELLAN GARCIA',            'juan_bautista'],
    ['32548202', 'GERALDINE GABRIELA', 'PEREZ FARIAS',             'juan_bautista'],
    ['32586993', 'VICTORIA ANTHONELLA','ZAPATA RIOS',              'juan_bautista'],
    ['33645800', 'JESUS ALEJANDRO',    'CARREÑO GARCIA',           'juan_bautista'],
    ['33645821', 'LUISMERY ALEJANDRA', 'ACOSTA MENDOZA',           'juan_bautista'],
    ['33670484', 'NICOLE ANGELINA',    'CARIAS PRIETO',            'juan_bautista'],
    // ── Fe y Alegría (4 pasantes) ────────────────────────────────────────────
    ['33923469', 'NICOLL SARAY',       'GUTIERREZ G',              'fe_y_alegria'],
    ['32744202', 'YULIANA VALENTINA',  'MEDINA RODRIGUEZ',         'fe_y_alegria'],
    ['33201645', 'HECTMARIS',          'ZAMBRANO',                 'fe_y_alegria'],
    ['30323345', 'KLEYDIS',            'PINO',                     'fe_y_alegria'],
];

// Instituciones a buscar/crear
$INSTITUCIONES = [
    'juan_bautista' => [
        'nombre'   => 'E.T.C. Juan Bautista Dalla Costa',
        'tipo'     => 'Escuela Técnica',
        'ubicacion'=> 'Ciudad Bolívar, Bolívar',
    ],
    'fe_y_alegria'  => [
        'nombre'   => 'Instituto Fe y Alegría',
        'tipo'     => 'Escuela Técnica',
        'ubicacion'=> 'Ciudad Bolívar, Bolívar',
    ],
];

// ─────────────────────────────────────────────────────────────────────────────
// ESTILOS HTML
// ─────────────────────────────────────────────────────────────────────────────
echo <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Inyección Pasantes Reales — SGP</title>
<style>
  body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;font-size:13px}
  h1{color:#60a5fa;border-bottom:1px solid #334155;padding-bottom:8px}
  h2{color:#93c5fd;margin-top:24px}
  .ok{color:#4ade80}.err{color:#f87171}.warn{color:#fbbf24}.info{color:#94a3b8}
  .box{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:16px;margin:12px 0}
  .summary{background:#14532d;border:1px solid #166534;border-radius:8px;padding:16px;color:#bbf7d0}
  .error-box{background:#450a0a;border:1px solid #7f1d1d;border-radius:8px;padding:16px;color:#fca5a5}
  table{width:100%;border-collapse:collapse;margin-top:8px}
  th{background:#1e3a8a;color:#bfdbfe;padding:6px 10px;text-align:left}
  td{padding:5px 10px;border-bottom:1px solid #1e293b}
  tr:hover td{background:#1e293b}
</style></head><body>
<h1>🔧 Inyección de Pasantes Reales — Cohorte 2025-2026</h1>
HTML;

// ─────────────────────────────────────────────────────────────────────────────
// CONEXIÓN
// ─────────────────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo "<div class='error-box'>❌ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</div></body></html>";
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 0: VERIFICACIONES PREVIAS
// ─────────────────────────────────────────────────────────────────────────────
echo "<div class='box'><h2>Paso 0 — Verificaciones previas</h2>";

// Verificar que la BD fue depurada
$totalPasantes = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 3")->fetchColumn();
if ($totalPasantes > 12) {
    echo "<p class='warn'>⚠ Hay {$totalPasantes} pasantes en la BD. Asegúrate de haber ejecutado depuracion_produccion.sql si quieres una BD limpia.</p>";
} else {
    echo "<p class='ok'>✔ BD verificada — {$totalPasantes} pasante(s) existentes</p>";
}

// Período activo
$periodo = $pdo->query("SELECT id, nombre, fecha_inicio, fecha_fin FROM periodos_academicos WHERE estado = 'activo' LIMIT 1")->fetch();
if (!$periodo) {
    echo "<p class='err'>❌ No hay período activo. Ejecuta feriados_y_periodo_produccion.sql primero.</p></div></body></html>";
    exit;
}
$PERIODO_ID = (int)$periodo['id'];
echo "<p class='ok'>✔ Período activo: <strong>{$periodo['nombre']}</strong> ({$periodo['fecha_inicio']} → {$periodo['fecha_fin']}) — ID: {$PERIODO_ID}</p>";

// Cargar feriados desde la BD
$feriadosRaw = $pdo->query("SELECT fecha FROM dias_feriados ORDER BY fecha")->fetchAll();
$FERIADOS = [];
foreach ($feriadosRaw as $f) {
    $FERIADOS[$f['fecha']] = true;
}
echo "<p class='ok'>✔ Feriados cargados desde BD: <strong>" . count($FERIADOS) . "</strong> registros</p>";
echo "</div>";

// ─────────────────────────────────────────────────────────────────────────────
// PASO 1: RESOLVER IDs DE INSTITUCIONES (buscar o crear)
// ─────────────────────────────────────────────────────────────────────────────
echo "<div class='box'><h2>Paso 1 — Instituciones</h2>";
$instIds = [];

foreach ($INSTITUCIONES as $clave => $inst) {
    // Buscar por nombre parcial (case-insensitive)
    $palabraBusqueda = ($clave === 'juan_bautista') ? 'Juan Bautista' : 'Fe y Alegr';
    $stmt = $pdo->prepare("SELECT id, nombre FROM instituciones WHERE nombre LIKE ? LIMIT 1");
    $stmt->execute(["%{$palabraBusqueda}%"]);
    $found = $stmt->fetch();

    if ($found) {
        $instIds[$clave] = (int)$found['id'];
        echo "<p class='ok'>✔ Institución encontrada: <strong>{$found['nombre']}</strong> (ID: {$found['id']})</p>";
    } else {
        $pdo->prepare("INSERT INTO instituciones (nombre, tipo, ubicacion) VALUES (?, ?, ?)")
            ->execute([$inst['nombre'], $inst['tipo'], $inst['ubicacion']]);
        $instIds[$clave] = (int)$pdo->lastInsertId();
        echo "<p class='warn'>➕ Institución creada: <strong>{$inst['nombre']}</strong> (ID: {$instIds[$clave]})</p>";
    }
}
echo "</div>";

// ─────────────────────────────────────────────────────────────────────────────
// FUNCIÓN: Generar lista de días a registrar entre dos fechas
// Devuelve ['fecha' => 'Presente'|'Justificado'] solo para L-V
// ─────────────────────────────────────────────────────────────────────────────
function generarDiasAsistencia(string $inicio, string $hasta, array $feriados): array {
    $dias   = [];
    $cur    = new DateTime($inicio);
    $end    = new DateTime($hasta);

    while ($cur <= $end) {
        $dow  = (int)$cur->format('N'); // 1=Lun … 7=Dom
        $date = $cur->format('Y-m-d');

        if ($dow >= 1 && $dow <= 5) { // Solo Lunes a Viernes
            $dias[$date] = isset($feriados[$date]) ? 'Justificado' : 'Presente';
        }
        $cur->modify('+1 day');
    }
    return $dias;
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 2: CREAR PASANTES
// ─────────────────────────────────────────────────────────────────────────────
echo "<div class='box'><h2>Paso 2 — Creación de Usuarios, Datos y Asistencias</h2>";
echo "<table><tr><th>Cédula</th><th>Nombre</th><th>Institución</th><th>Días</th><th>Presentes</th><th>Justificados</th><th>Estado</th></tr>";

$contadorCreados    = 0;
$contadorOmitidos   = 0;
$contadorAsistencias= 0;
$errores            = [];

foreach ($PASANTES as [$cedula, $nombres, $apellidos, $instClave]) {

    // ── Idempotencia: omitir si ya existe ────────────────────────────────────
    $existe = (int)$pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE cedula = ?")->execute([$cedula])
              ? $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE cedula = ?") : null;
    $stmtEx = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ?");
    $stmtEx->execute([$cedula]);
    $usuarioExistente = $stmtEx->fetchColumn();

    if ($usuarioExistente) {
        echo "<tr><td>{$cedula}</td><td>{$nombres} {$apellidos}</td><td>—</td><td colspan='3'>—</td><td class='warn'>OMITIDO (ya existe)</td></tr>";
        $contadorOmitidos++;
        continue;
    }

    $instId     = $instIds[$instClave];
    $instNombre = $INSTITUCIONES[$instClave]['nombre'];
    $correo     = "V-{$cedula}@pasante.sgp";
    $password   = password_hash($cedula, PASSWORD_DEFAULT);

    // Calcular días de asistencia a insertar
    $diasAsistencia = generarDiasAsistencia($FECHA_INICIO, $HASTA_HOY, $FERIADOS);
    $totalDias      = count($diasAsistencia);
    $nPresentes     = count(array_filter($diasAsistencia, fn($e) => $e === 'Presente'));
    $nJustificados  = count(array_filter($diasAsistencia, fn($e) => $e === 'Justificado'));
    $horasAcum      = $totalDias * 8; // Todos los días cuentan 8h

    $pdo->beginTransaction();
    try {
        // ── 1. usuarios ──────────────────────────────────────────────────────
        $pdo->prepare("
            INSERT INTO usuarios (cedula, correo, password, rol_id, estado, requiere_cambio_clave, created_at)
            VALUES (?, ?, ?, 3, 'activo', 0, NOW())
        ")->execute([$cedula, $correo, $password]);
        $uid = (int)$pdo->lastInsertId();

        // ── 2. datos_personales ──────────────────────────────────────────────
        $pdo->prepare("
            INSERT INTO datos_personales (usuario_id, nombres, apellidos, created_at)
            VALUES (?, ?, ?, NOW())
        ")->execute([$uid, $nombres, $apellidos]);

        // ── 3. datos_pasante ─────────────────────────────────────────────────
        // estado_pasantia = 'Sin Asignar' — el admin asignará departamento/tutor via UI
        $pdo->prepare("
            INSERT INTO datos_pasante
                (usuario_id, periodo_id, institucion_id, institucion_procedencia,
                 estado_pasantia, fecha_inicio_pasantia, fecha_fin_estimada,
                 horas_acumuladas, horas_meta, departamento_asignado_id, tutor_id, created_at)
            VALUES (?, ?, ?, ?, 'Pendiente', ?, ?, ?, ?, NULL, NULL, NOW())
        ")->execute([
            $uid,
            $PERIODO_ID,
            $instId,
            (string)$instId,
            $FECHA_INICIO,
            $FECHA_FIN,
            $horasAcum,
            $HORAS_META,
        ]);

        // ── 4. asistencias ───────────────────────────────────────────────────
        $stmtAsist = $pdo->prepare("
            INSERT IGNORE INTO asistencias
                (pasante_id, fecha, hora_registro, hora_entrada, hora_salida,
                 horas_calculadas, estado, metodo, motivo_justificacion, es_auto_fill, created_at)
            VALUES (?, ?, '08:00:00', ?, ?, 8.00, ?, 'Manual', ?, 1, NOW())
        ");

        foreach ($diasAsistencia as $fecha => $estado) {
            if ($estado === 'Presente') {
                $stmtAsist->execute([$uid, $fecha, '08:00:00', '16:00:00', 'Presente', null]);
            } else {
                $stmtAsist->execute([$uid, $fecha, null, null, 'Justificado', 'Día feriado nacional/regional']);
            }
            $contadorAsistencias++;
        }

        $pdo->commit();
        $contadorCreados++;
        echo "<tr><td>{$cedula}</td><td>{$nombres} {$apellidos}</td><td>{$instNombre}</td>"
           . "<td>{$totalDias}</td><td class='ok'>{$nPresentes}</td><td class='warn'>{$nJustificados}</td>"
           . "<td class='ok'>✔ CREADO (ID: {$uid})</td></tr>";

    } catch (Throwable $e) {
        $pdo->rollBack();
        $msg = $e->getMessage();
        $errores[] = "CI {$cedula} ({$nombres}): {$msg}";
        echo "<tr><td>{$cedula}</td><td>{$nombres} {$apellidos}</td><td>{$instNombre}</td>"
           . "<td colspan='3'>—</td><td class='err'>❌ ERROR: " . htmlspecialchars($msg) . "</td></tr>";
    }
}

echo "</table></div>";

// ─────────────────────────────────────────────────────────────────────────────
// RESUMEN FINAL
// ─────────────────────────────────────────────────────────────────────────────
$claseResumen = empty($errores) ? 'summary' : 'error-box';
echo "<div class='{$claseResumen}'>";
echo "<h2>" . (empty($errores) ? "✅ Inyección completada exitosamente" : "⚠ Completada con errores") . "</h2>";
echo "<table>";
echo "<tr><td><strong>Pasantes creados</strong></td><td class='ok'>{$contadorCreados} / " . count($PASANTES) . "</td></tr>";
echo "<tr><td><strong>Pasantes omitidos</strong> (ya existían)</td><td class='warn'>{$contadorOmitidos}</td></tr>";
echo "<tr><td><strong>Registros de asistencia insertados</strong></td><td class='ok'>{$contadorAsistencias}</td></tr>";
echo "<tr><td><strong>Período asignado</strong></td><td>{$periodo['nombre']} (ID: {$PERIODO_ID})</td></tr>";
echo "<tr><td><strong>Rango de asistencias</strong></td><td>{$FECHA_INICIO} → {$HASTA_HOY}</td></tr>";
echo "<tr><td><strong>Horas meta por pasante</strong></td><td>{$HORAS_META}h (171 días hábiles × 8h)</td></tr>";
echo "<tr><td><strong>Estado inicial</strong></td><td>Sin Asignar — pendiente asignación via módulo de Asignaciones</td></tr>";
echo "</table>";

if (!empty($errores)) {
    echo "<h2>Errores encontrados:</h2><ul>";
    foreach ($errores as $err) {
        echo "<li class='err'>" . htmlspecialchars($err) . "</li>";
    }
    echo "</ul>";
}

echo "</div>";

// ─────────────────────────────────────────────────────────────────────────────
// PRÓXIMOS PASOS
// ─────────────────────────────────────────────────────────────────────────────
echo <<<HTML
<div class='box'>
<h2>📋 Próximos pasos (orden correcto)</h2>
<ol>
  <li>Ir a <strong>Módulo de Asignaciones</strong> → asignar cada pasante a su departamento (Redes o Soporte Técnico) y su tutor/administrador.</li>
  <li>Las asistencias ya están cargadas — aparecerán en el almanaque y en los reportes inmediatamente.</li>
  <li>El jefe puede imprimir las planillas de asistencia individual desde <strong>Reportes → PDF Trimestral</strong>.</li>
  <li>Ejecutar <strong>asignar_depto_admin.sql</strong> si aún no se hizo (asigna Soporte Técnico al admin).</li>
</ol>
</div>
</body></html>
HTML;
