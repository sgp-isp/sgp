<?php
/**
 * correccion_pasantes.php
 * Aplica las correcciones post-inyección a los 12 pasantes reales:
 *   1. Email → primera_letra_nombre + primer_apellido@sgp.local
 *   2. Password → bcrypt('SGP.' + cedula)
 *   3. datos_pasante → estado='Activo', dept=Soporte Técnico, tutor=admin
 *   4. INSERT INTO asignaciones (vinculación formal con depto/tutor)
 *   5. Asistencias completas hasta fecha_fin del período (2026-06-01)
 */
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

$dbHost = 'localhost'; $dbName = 'proyecto_sgp'; $dbUser = 'root'; $dbPass = '';

$DEPT_ID    = 1;   // Soporte Técnico
$TUTOR_ID   = 1;   // Joel Acosta (admin@sgp.local)
$FECHA_INI  = '2025-10-03';
$FECHA_FIN  = '2026-06-01';   // Fin del período — se registran TODOS los días

// ── Datos de los 12 pasantes: [usuario_id, cedula, email_correcto] ────────────
$PASANTES = [
    // id    cédula        email                          password_plain
    [113, '32254951', 'evalle@sgp.local'],      // ELIFER   / DEL VALLE…  (DEL = art.)
    [114, '33547626', 'orodriguez@sgp.local'],  // ORIANNYS / RODRIGUEZ
    [115, '33662103', 'jdellan@sgp.local'],     // JESUS    / DELLAN
    [116, '32548202', 'gperez@sgp.local'],      // GERALDINE/ PEREZ
    [117, '32586993', 'vzapata@sgp.local'],     // VICTORIA / ZAPATA
    [118, '33645800', 'jcarreno@sgp.local'],    // JESUS A. / CARREÑO (sin tilde)
    [119, '33645821', 'lacosta@sgp.local'],     // LUISMERY / ACOSTA
    [120, '33670484', 'ncarias@sgp.local'],     // NICOLE   / CARIAS
    [121, '33923469', 'ngutierrez@sgp.local'],  // NICOLL   / GUTIERREZ
    [122, '32744202', 'ymedina@sgp.local'],     // YULIANA  / MEDINA
    [123, '33201645', 'hzambrano@sgp.local'],   // HECTMARIS/ ZAMBRANO
    [124, '30323345', 'kpino@sgp.local'],       // KLEYDIS  / PINO
];

echo <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Corrección Pasantes — SGP</title>
<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;font-size:13px}
h1{color:#60a5fa;border-bottom:1px solid #334155;padding-bottom:8px}
h2{color:#93c5fd;margin-top:20px}
.ok{color:#4ade80}.err{color:#f87171}.warn{color:#fbbf24}
.box{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:14px;margin:10px 0}
.done{background:#14532d;border:1px solid #166534;border-radius:8px;padding:14px;color:#bbf7d0;margin:10px 0}
table{width:100%;border-collapse:collapse;margin-top:8px}
th{background:#1e3a8a;color:#bfdbfe;padding:6px 10px;text-align:left}
td{padding:5px 10px;border-bottom:1px solid #1e293b}</style></head><body>
<h1>🔧 Corrección de Pasantes Reales — Cohorte 2025-2026</h1>
HTML;

// ── Conexión ──────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    echo "<p style='color:#f87171'>❌ Conexión fallida: " . $e->getMessage() . "</p></body></html>"; exit;
}

// ── Cargar feriados desde BD ──────────────────────────────────────────────────
$feriados = [];
foreach ($pdo->query("SELECT fecha FROM dias_feriados")->fetchAll() as $f) {
    $feriados[$f['fecha']] = true;
}

// ── Función: días L-V en el rango con su estado ───────────────────────────────
function diasAsistencia(string $inicio, string $fin, array $feriados): array {
    $dias = []; $cur = new DateTime($inicio); $end = new DateTime($fin);
    while ($cur <= $end) {
        $dow = (int)$cur->format('N'); $d = $cur->format('Y-m-d');
        if ($dow <= 5) $dias[$d] = isset($feriados[$d]) ? 'Justificado' : 'Presente';
        $cur->modify('+1 day');
    }
    return $dias;
}

// Calcular días del período completo (para referencia)
$todosDias    = diasAsistencia($FECHA_INI, $FECHA_FIN, $feriados);
$totalPeriodo = count($todosDias);
$horasAcum    = array_sum(array_map(fn($e) => 8, $todosDias)); // todos los días cuentan 8h

echo "<div class='box'><h2>Período completo: {$FECHA_INI} → {$FECHA_FIN}</h2>";
$presentes    = count(array_filter($todosDias, fn($e) => $e === 'Presente'));
$justificados = count(array_filter($todosDias, fn($e) => $e === 'Justificado'));
echo "<p>Total días L-V: <strong>{$totalPeriodo}</strong> | Presentes: <strong class='ok'>{$presentes}</strong> | Justificados: <strong class='warn'>{$justificados}</strong> | Horas acum: <strong>{$horasAcum}h</strong></p>";
echo "</div>";

// ─────────────────────────────────────────────────────────────────────────────
// LOOP PRINCIPAL: Corregir cada pasante
// ─────────────────────────────────────────────────────────────────────────────
echo "<div class='box'><h2>Correcciones por pasante</h2>";
echo "<table><tr><th>ID</th><th>Cédula</th><th>Email nuevo</th><th>Password</th><th>Asignación</th><th>Asistencias añadidas</th><th>Estado</th></tr>";

$stmtAsist = $pdo->prepare("
    INSERT IGNORE INTO asistencias
        (pasante_id, fecha, hora_registro, hora_entrada, hora_salida,
         horas_calculadas, estado, metodo, motivo_justificacion, es_auto_fill, created_at)
    VALUES (?, ?, '08:00:00', ?, ?, 8.00, ?, 'Manual', ?, 1, NOW())
");

$stmtAsignacion = $pdo->prepare("
    INSERT INTO asignaciones
        (pasante_id, tutor_id, departamento_id, fecha_inicio, fecha_fin,
         hora_entrada, hora_salida, horas_totales, horas_cumplidas, estado, created_at)
    VALUES (?, ?, ?, ?, ?, '08:00:00', '16:00:00', ?, ?, 'activo', NOW())
    ON DUPLICATE KEY UPDATE
        tutor_id        = VALUES(tutor_id),
        departamento_id = VALUES(departamento_id),
        fecha_fin       = VALUES(fecha_fin),
        horas_totales   = VALUES(horas_totales),
        horas_cumplidas = VALUES(horas_cumplidas),
        estado          = 'activo'
");

$totalAsistenciasNuevas = 0;
$errores = [];

foreach ($PASANTES as [$uid, $cedula, $email]) {
    $password   = password_hash('SGP.' . $cedula, PASSWORD_DEFAULT);
    $passPlain  = 'SGP.' . $cedula;

    $pdo->beginTransaction();
    try {
        // 1. Actualizar email y password
        $pdo->prepare("UPDATE usuarios SET correo = ?, password = ? WHERE id = ?")
            ->execute([$email, $password, $uid]);

        // 2. Actualizar datos_pasante: Activo, Soporte Técnico, admin como tutor
        $pdo->prepare("
            UPDATE datos_pasante
            SET estado_pasantia        = 'Activo',
                departamento_asignado_id = ?,
                tutor_id               = ?,
                fecha_inicio_pasantia  = ?,
                fecha_fin_estimada     = ?,
                horas_acumuladas       = ?,
                horas_meta             = 1376
            WHERE usuario_id = ?
        ")->execute([$DEPT_ID, $TUTOR_ID, $FECHA_INI, $FECHA_FIN, $horasAcum, $uid]);

        // 3. Crear / actualizar asignacion formal
        $stmtAsignacion->execute([
            $uid, $TUTOR_ID, $DEPT_ID,
            $FECHA_INI, $FECHA_FIN,
            1368,      // horas_totales
            $horasAcum // horas_cumplidas (período completo × 8h)
        ]);

        // 4. Insertar asistencias que falten (IGNORE si ya existen)
        $nuevas = 0;
        foreach ($todosDias as $fecha => $estado) {
            if ($estado === 'Presente') {
                $rows = $stmtAsist->execute([$uid, $fecha, '08:00:00', '16:00:00', 'Presente', null]);
            } else {
                $rows = $stmtAsist->execute([$uid, $fecha, null, null, 'Justificado', 'Día feriado nacional/regional']);
            }
            $nuevas += $stmtAsist->rowCount();
        }
        $totalAsistenciasNuevas += $nuevas;

        $pdo->commit();
        echo "<tr><td>{$uid}</td><td>{$cedula}</td><td class='ok'>{$email}</td><td class='warn'>{$passPlain}</td>"
           . "<td class='ok'>Soporte Técnico / Joel Acosta</td>"
           . "<td class='ok'>+{$nuevas} nuevas</td><td class='ok'>✔ OK</td></tr>";

    } catch (Throwable $e) {
        $pdo->rollBack();
        $msg = $e->getMessage();
        $errores[] = "ID {$uid}: {$msg}";
        echo "<tr><td>{$uid}</td><td>{$cedula}</td><td>{$email}</td><td>—</td><td>—</td><td>—</td>"
           . "<td class='err'>❌ " . htmlspecialchars($msg) . "</td></tr>";
    }
}
echo "</table></div>";

// ─────────────────────────────────────────────────────────────────────────────
// RESUMEN
// ─────────────────────────────────────────────────────────────────────────────
echo "<div class='" . (empty($errores) ? 'done' : 'box') . "'>";
echo "<h2>" . (empty($errores) ? "✅ Corrección completada" : "⚠ Completada con errores") . "</h2>";
echo "<table>";
echo "<tr><td>Pasantes corregidos</td><td>" . (count($PASANTES) - count($errores)) . " / " . count($PASANTES) . "</td></tr>";
echo "<tr><td>Asistencias añadidas (nuevas)</td><td>{$totalAsistenciasNuevas}</td></tr>";
echo "<tr><td>Período cubierto</td><td>{$FECHA_INI} → {$FECHA_FIN} ({$totalPeriodo} días L-V)</td></tr>";
echo "<tr><td>Estado</td><td>Activo — Soporte Técnico — Joel Acosta</td></tr>";
echo "<tr><td>Correos</td><td>primera_letra_nombre + primer_apellido @sgp.local</td></tr>";
echo "<tr><td>Contraseña</td><td>SGP.{cédula}  (ej: SGP.32254951)</td></tr>";
echo "</table>";
if (!empty($errores)) {
    echo "<ul>"; foreach ($errores as $e) echo "<li style='color:#f87171'>" . htmlspecialchars($e) . "</li>"; echo "</ul>";
}
echo "</div>";

// ─────────────────────────────────────────────────────────────────────────────
// TABLA DE CREDENCIALES PARA ENTREGAR AL JEFE
// ─────────────────────────────────────────────────────────────────────────────
echo "<div class='box'><h2>📋 Credenciales de acceso — Para entregar</h2>";
echo "<table><tr><th>Nombre completo</th><th>Cédula</th><th>Correo</th><th>Contraseña</th></tr>";
$nombres = [
    [113,'ELIFER ISABEL DEL VALLE DIAZ SARMINETO','32254951','evalle@sgp.local'],
    [114,'ORIANNYS SHARAYS RODRIGUEZ RENGIFO','33547626','orodriguez@sgp.local'],
    [115,'JESUS SAMUEL DELLAN GARCIA','33662103','jdellan@sgp.local'],
    [116,'GERALDINE GABRIELA PEREZ FARIAS','32548202','gperez@sgp.local'],
    [117,'VICTORIA ANTHONELLA ZAPATA RIOS','32586993','vzapata@sgp.local'],
    [118,'JESUS ALEJANDRO CARREÑO GARCIA','33645800','jcarreno@sgp.local'],
    [119,'LUISMERY ALEJANDRA ACOSTA MENDOZA','33645821','lacosta@sgp.local'],
    [120,'NICOLE ANGELINA CARIAS PRIETO','33670484','ncarias@sgp.local'],
    [121,'NICOLL SARAY GUTIERREZ G','33923469','ngutierrez@sgp.local'],
    [122,'YULIANA VALENTINA MEDINA RODRIGUEZ','32744202','ymedina@sgp.local'],
    [123,'HECTMARIS ZAMBRANO','33201645','hzambrano@sgp.local'],
    [124,'KLEYDIS PINO','30323345','kpino@sgp.local'],
];
foreach ($nombres as [,$nombre,$ced,$correo]) {
    echo "<tr><td>{$nombre}</td><td>{$ced}</td><td class='ok'>{$correo}</td><td class='warn'>SGP.{$ced}</td></tr>";
}
echo "</table></div></body></html>";
