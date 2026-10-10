<?php
/**
 * SGP — Script de Data Seed (Fase 1)
 * ====================================
 * Inyecta 25 pasantes completos con asistencias realistas
 * para pruebas de escala visual (Bento UI / Almanaque de Calor).
 *
 * PRERREQUISITO: Ejecutar fase1_migracion.sql antes de este script.
 * IDEMPOTENTE:   Usa cédulas únicas — no duplica si se corre dos veces.
 * USO CLI:       php sgp/app/scripts/seed_datos.php
 * USO WEB:       Solo accesible desde localhost (guard integrado)
 */

// ── Guard: solo en desarrollo ──────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!in_array($host, ['localhost', '127.0.0.1', '::1'])) {
        http_response_code(403);
        exit('Acceso denegado: este script es solo para desarrollo local.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// ── Conexión directa PDO ───────────────────────────────────────────────────
$dsn = 'mysql:host=localhost;dbname=proyecto_sgp;charset=utf8mb4';
try {
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('Error de conexión: ' . $e->getMessage());
}

echo "=== SGP DATA SEED — FASE 1 ===\n\n";

// ── Recuperar IDs de contexto ──────────────────────────────────────────────
$periodoId = $pdo->query("SELECT id FROM periodos_academicos WHERE estado='activo' LIMIT 1")->fetchColumn();
$tutorIds  = $pdo->query("SELECT id FROM usuarios WHERE rol_id=2 AND estado='activo'")->fetchAll(PDO::FETCH_COLUMN);
$deptIds   = $pdo->query("SELECT id FROM departamentos WHERE activo=1")->fetchAll(PDO::FETCH_COLUMN);
$instIds   = $pdo->query("SELECT id FROM instituciones")->fetchAll(PDO::FETCH_COLUMN);

if (!$periodoId) { exit("ERROR: No hay periodo académico activo. Ejecuta fase1_migracion.sql primero.\n"); }
if (empty($tutorIds)) { exit("ERROR: No hay tutores activos en la BD.\n"); }

// ── Feriados hardcoded (para el generador de asistencias) ─────────────────
$feriados = array_flip([
    '2025-10-12','2025-12-08','2025-12-17','2025-12-24','2025-12-25','2025-12-31',
    '2026-01-01','2026-01-06','2026-02-12','2026-02-16','2026-02-17',
    '2026-03-19','2026-03-30','2026-03-31','2026-04-01','2026-04-02','2026-04-03',
    '2026-04-19','2026-05-01',
]);

// ── Catálogo de datos venezolanos ──────────────────────────────────────────
$nombres = [
    ['Carlos',     'M'], ['Andrés',    'M'], ['Luis',       'M'], ['Miguel',    'M'],
    ['Rafael',     'M'], ['Daniel',    'M'], ['José',       'M'], ['Eduardo',   'M'],
    ['Gabriel',    'M'], ['Omar',      'M'], ['Roberto',    'M'], ['Frank',     'M'],
    ['Yonathan',   'M'], ['Aleixis',   'M'], ['Kenner',     'M'], ['Brayan',    'M'],
    ['María',      'F'], ['Ana',       'F'], ['Laura',      'F'], ['Valentina', 'F'],
    ['Gabriela',   'F'], ['Sofía',     'F'], ['Paola',      'F'], ['Karla',     'F'],
    ['Andreina',   'F'],
];

$apellidos = [
    'González','Rodríguez','López','Martínez','Pérez','García','Hernández',
    'Morales','Castillo','Vargas','Medina','Rojas','Silva','Torres','Ramos',
    'Díaz','Flores','Gutiérrez','Ramírez','Suárez','Arteaga','Blanco',
    'Figueroa','Mendoza','Núñez',
];

// Fechas de inicio: 3 grupos para probar variedad en el almanaque
$gruposFechaInicio = [
    '2025-09-15', // 12 pasantes — ciclo completo
    '2025-09-15', '2025-09-15', '2025-09-15', '2025-09-15',
    '2025-09-15', '2025-09-15', '2025-09-15', '2025-09-15',
    '2025-09-15', '2025-09-15', '2025-09-15',
    '2025-11-03', // 8 pasantes — incorporación tardía
    '2025-11-03', '2025-11-03', '2025-11-03',
    '2025-11-03', '2025-11-03', '2025-11-03', '2025-11-03',
    '2026-01-05', // 5 pasantes — reciente
    '2026-01-05', '2026-01-05', '2026-01-05', '2026-01-05',
];

// ── Función: genera días laborables entre dos fechas ──────────────────────
function diasLaborables(string $inicio, string $fin, array $feriados): array {
    $dias = [];
    $cur  = new DateTime($inicio);
    $end  = new DateTime($fin);
    while ($cur <= $end) {
        $dow  = (int)$cur->format('N'); // 1=Lun, 7=Dom
        $date = $cur->format('Y-m-d');
        if ($dow < 6 && !isset($feriados[$date])) {
            $dias[] = $date;
        }
        $cur->modify('+1 day');
    }
    return $dias;
}

// ── Función: estado aleatorio por semilla (reproducible) ──────────────────
function estadoAsistencia(int $seed): string {
    $r = ($seed * 1103515245 + 12345) & 0x7fffffff;
    $p = $r % 100;
    if ($p < 83) return 'Presente';
    if ($p < 91) return 'Ausente';
    return 'Justificado';
}

// ── Contadores ─────────────────────────────────────────────────────────────
$creados = 0; $omitidos = 0; $asistenciasCreadas = 0;
$hoy     = '2026-03-31'; // fecha de referencia del seed

// ── Loop principal: crear pasantes ────────────────────────────────────────
foreach ($nombres as $i => [$nombre, $genero]) {
    $apellido1  = $apellidos[$i];
    $apellido2  = $apellidos[(($i + 7) % count($apellidos))];
    $cedula     = '27' . str_pad((100000 + ($i * 4321)), 6, '0', STR_PAD_LEFT);
    $correo     = strtolower(
        iconv('UTF-8', 'ASCII//TRANSLIT', $nombre) . '.' .
        iconv('UTF-8', 'ASCII//TRANSLIT', $apellido1) .
        $i . '@gmail.com'
    );
    $telefono   = '0414-' . str_pad(7000000 + ($i * 113), 7, '0', STR_PAD_LEFT);
    $fechaInicio = $gruposFechaInicio[$i];
    $fechaFin    = date('Y-m-d', strtotime($fechaInicio . ' +180 weekdays'));
    $tutorId     = $tutorIds[$i % count($tutorIds)];
    $deptId      = $deptIds[$i % count($deptIds)];
    $instId      = $instIds[$i % count($instIds)];

    // ── Idempotencia: skip si ya existe ───────────────────────────────────
    $existe = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ?");
    $existe->execute([$cedula]);
    if ($existe->fetchColumn()) {
        echo "  [OMITIDO]  C.I. {$cedula} — {$nombre} {$apellido1} ya existe\n";
        $omitidos++;
        continue;
    }

    $pdo->beginTransaction();
    try {
        // 1. usuarios
        $pdo->prepare("
            INSERT INTO usuarios (cedula, correo, password, rol_id, estado, requiere_cambio_clave, created_at)
            VALUES (?, ?, ?, 3, 'activo', 0, ?)
        ")->execute([
            $cedula,
            $correo,
            password_hash('Sgp2025*', PASSWORD_DEFAULT),
            $fechaInicio,
        ]);
        $usuarioId = (int)$pdo->lastInsertId();

        // 2. datos_personales
        $pdo->prepare("
            INSERT INTO datos_personales (usuario_id, nombres, apellidos, telefono, genero, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $usuarioId,
            $nombre,
            "$apellido1 $apellido2",
            $telefono,
            $genero,
            $fechaInicio,
        ]);

        // 3. datos_pasante
        $diasHabiles = diasLaborables($fechaInicio, $hoy, $feriados);
        $horasAcum   = count($diasHabiles) * 8;
        $pdo->prepare("
            INSERT INTO datos_pasante
                (usuario_id, periodo_id, institucion_id, institucion_procedencia,
                 estado_pasantia, fecha_inicio_pasantia, fecha_fin_estimada,
                 horas_acumuladas, horas_meta, departamento_asignado_id, tutor_id, created_at)
            VALUES (?, ?, ?, ?, 'Activo', ?, ?, ?, 1440, ?, ?, ?)
        ")->execute([
            $usuarioId,
            $periodoId,
            $instId,
            $pdo->query("SELECT nombre FROM instituciones WHERE id={$instId}")->fetchColumn(),
            $fechaInicio,
            $fechaFin,
            $horasAcum,
            $deptId,
            $tutorId,
            $fechaInicio,
        ]);

        // 4. asignaciones
        $pdo->prepare("
            INSERT INTO asignaciones
                (pasante_id, tutor_id, departamento_id, fecha_inicio, fecha_fin,
                 hora_entrada, hora_salida, horas_totales, horas_cumplidas, estado, created_at)
            VALUES (?, ?, ?, ?, ?, '08:00:00', '16:00:00', 1440, ?, 'activo', ?)
        ")->execute([
            $usuarioId, $tutorId, $deptId,
            $fechaInicio, $fechaFin,
            $horasAcum,
            $fechaInicio,
        ]);
        $asignacionId = (int)$pdo->lastInsertId();

        // 5. asistencias — un registro por día laborable
        $stmtAsist = $pdo->prepare("
            INSERT INTO asistencias
                (pasante_id, asignacion_id, fecha, hora_registro, metodo,
                 hora_entrada, hora_salida, horas_calculadas, estado, created_at)
            VALUES (?, ?, ?, ?, 'Kiosco', ?, ?, 8.00, ?, ?)
        ");

        foreach ($diasHabiles as $dia) {
            $seed   = crc32($cedula . $dia);
            $estado = estadoAsistencia($seed);

            if ($estado === 'Presente') {
                // Hora de entrada: 07:50–08:30, salida: 15:55–17:10
                $entMinutos = 470 + ($seed % 40);   // 7h50 + variación
                $salMinutos = 955 + ($seed % 75);   // 15h55 + variación
                $horaEnt    = sprintf('%02d:%02d:00', intdiv($entMinutos, 60), $entMinutos % 60);
                $horaSal    = sprintf('%02d:%02d:00', intdiv($salMinutos, 60), $salMinutos % 60);
            } else {
                $horaEnt = null;
                $horaSal = null;
            }

            $stmtAsist->execute([
                $usuarioId, $asignacionId, $dia,
                $horaEnt ?? '00:00:00',
                $horaEnt, $horaSal,
                $estado, $dia . ' 08:00:00',
            ]);
            $asistenciasCreadas++;
        }

        $pdo->commit();
        $creados++;
        echo "  [OK] {$cedula} — {$nombre} {$apellido1} {$apellido2} | " .
             "Dept: {$deptId} | Inicio: {$fechaInicio} | " .
             count($diasHabiles) . " días ({$horasAcum}h)\n";

    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "  [ERROR] {$cedula} — {$nombre}: " . $e->getMessage() . "\n";
    }
}

// ── Resumen ────────────────────────────────────────────────────────────────
echo "\n" . str_repeat('=', 50) . "\n";
echo "SEED COMPLETADO:\n";
echo "  Pasantes creados:    {$creados}\n";
echo "  Pasantes omitidos:   {$omitidos} (ya existían)\n";
echo "  Asistencias creadas: {$asistenciasCreadas}\n";
echo str_repeat('=', 50) . "\n";
