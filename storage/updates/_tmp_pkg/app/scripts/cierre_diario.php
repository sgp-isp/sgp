<?php
/**
 * SGP — Cierre Diario de Asistencias
 *
 * Garantiza integridad referencial: ningún día hábil del período de pasantía
 * puede quedar sin un registro en la tabla `asistencias`.
 *
 * ALGORITMO:
 *   1. Obtener todos los pasantes con estado_pasantia = 'Activo'
 *   2. Para cada uno, calcular el rango de días hábiles entre fecha_inicio y ayer
 *   3. Por cada día sin registro → insertar estado='Ausente', metodo='Sistema'
 *
 * USO:
 *   - Manual (Admin): GET /admin/cierreDiario  → AdminController::cierreDiario()
 *   - Cron (servidor): php /path/to/sgp/app/scripts/cierre_diario.php
 *
 * @version 1.0 — FASE 3 integridad de datos
 */

declare(strict_types=1);

// ── Bootstrap (solo si se ejecuta como CLI) ──────────────────────────────────
if (php_sapi_name() === 'cli') {
    define('APPROOT', dirname(__DIR__)); // sgp/app
    $config = require APPROOT . '/config/config.php';
    require_once APPROOT . '/core/Database.php';
    // Nota: en modo CLI no hay sesión ni autenticación
}

/**
 * Ejecutar el cierre diario.
 * Retorna un array con el resumen de la operación.
 *
 * @param Database $db       Instancia de base de datos
 * @param string   $fechaHasta Fecha límite (default: ayer)
 * @return array  ['insertados'=>int, 'pasantes'=>int, 'errores'=>[], 'duracion_ms'=>int]
 */
function ejecutarCierreDiario(Database $db, string $fechaHasta = ''): array
{
    $inicio = microtime(true);
    if (empty($fechaHasta)) {
        $fechaHasta = date('Y-m-d', strtotime('-1 day')); // ayer
    }

    $resultado = [
        'insertados' => 0,
        'pasantes'   => 0,
        'errores'    => [],
        'fecha_hasta'=> $fechaHasta,
    ];

    // 1. Obtener pasantes activos con fecha de inicio definida
    $db->query("
        SELECT u.id AS pasante_id, dpa.fecha_inicio_pasantia, dpa.fecha_fin_estimada
        FROM datos_pasante dpa
        INNER JOIN usuarios u ON u.id = dpa.usuario_id
        WHERE dpa.estado_pasantia = 'Activo'
          AND dpa.fecha_inicio_pasantia IS NOT NULL
          AND dpa.departamento_asignado_id IS NOT NULL
          AND dpa.tutor_id IS NOT NULL
    ");
    $pasantes = $db->resultSet();

    if (empty($pasantes)) {
        return $resultado;
    }

    $resultado['pasantes'] = count($pasantes);

    foreach ($pasantes as $p) {
        $pasanteId  = (int)$p->pasante_id;
        $fechaInicio = $p->fecha_inicio_pasantia;
        $fechaFin    = $p->fecha_fin_estimada ?? $fechaHasta;

        // El límite superior es el menor entre ayer y la fecha fin estimada
        $limite = min($fechaHasta, $fechaFin);

        if ($fechaInicio > $limite) continue; // La pasantía aún no comenzó

        try {
            // 2. Obtener días que ya tienen registro
            $db->query("
                SELECT fecha FROM asistencias
                WHERE pasante_id = :pid
                  AND fecha >= :fi
                  AND fecha <= :fl
            ");
            $db->bind(':pid', $pasanteId);
            $db->bind(':fi',  $fechaInicio);
            $db->bind(':fl',  $limite);
            $registrados = $db->resultSet();

            $fechasRegistradas = array_map(fn($r) => $r->fecha, $registrados);

            // 3. Iterar por cada día hábil del período y rellenar los faltantes
            $cursor = new DateTime($fechaInicio);
            $finObj = new DateTime($limite);

            while ($cursor <= $finObj) {
                $diaSemana = (int)$cursor->format('N'); // 1=Lun ... 7=Dom
                if ($diaSemana <= 5) { // Solo días hábiles
                    $fechaStr = $cursor->format('Y-m-d');
                    if (!in_array($fechaStr, $fechasRegistradas)) {
                        // Insertar como Ausente (cierre automático del sistema)
                        $db->query("
                            INSERT INTO asistencias
                                (pasante_id, fecha, hora_registro, estado, metodo, motivo_justificacion)
                            VALUES
                                (:pid, :fecha, '23:59:00', 'Ausente', 'Sistema', 'Cierre automático — sin registro en el kiosco')
                        ");
                        $db->bind(':pid',   $pasanteId);
                        $db->bind(':fecha', $fechaStr);

                        if ($db->execute()) {
                            $resultado['insertados']++;
                        }
                    }
                }
                $cursor->modify('+1 day');
            }
        } catch (Exception $e) {
            $resultado['errores'][] = "Pasante #{$pasanteId}: " . $e->getMessage();
        }
    }

    // ── Auto-finalizar pasantes cuya fecha_fin_estimada ya pasó ────────────────
    $hoy = date('Y-m-d');
    $db->query("
        SELECT dpa.usuario_id,
               CONCAT(dp.nombres,' ',dp.apellidos) AS nombre,
               dpa.fecha_fin_estimada
        FROM datos_pasante dpa
        JOIN usuarios u ON u.id = dpa.usuario_id
        LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
        WHERE dpa.estado_pasantia = 'Activo'
          AND dpa.fecha_fin_estimada IS NOT NULL
          AND dpa.fecha_fin_estimada < :hoy
          AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
    ");
    $db->bind(':hoy', $hoy);
    $pasantesVencidos = $db->resultSet();

    $finalizadosAuto = 0;
    foreach ($pasantesVencidos as $pv) {
        // Marcar como Finalizado e inactivar cuenta
        $db->query("UPDATE datos_pasante SET estado_pasantia = 'Finalizado' WHERE usuario_id = :uid");
        $db->bind(':uid', (int)$pv->usuario_id); $db->execute();

        $db->query("UPDATE usuarios SET estado = 'inactivo' WHERE id = :uid AND rol_id = 3");
        $db->bind(':uid', (int)$pv->usuario_id); $db->execute();

        $finalizadosAuto++;
    }

    if ($finalizadosAuto > 0) {
        $resultado['finalizados_auto'] = $finalizadosAuto;
    }

    $resultado['duracion_ms'] = round((microtime(true) - $inicio) * 1000, 1);
    return $resultado;
}

// ── Ejecución directa desde CLI ──────────────────────────────────────────────
if (php_sapi_name() === 'cli') {
    $db  = Database::getInstance();
    $res = ejecutarCierreDiario($db);

    echo "=== SGP — Cierre Diario ===" . PHP_EOL;
    echo "Fecha hasta : {$res['fecha_hasta']}" . PHP_EOL;
    echo "Pasantes    : {$res['pasantes']}" . PHP_EOL;
    echo "Insertados  : {$res['insertados']} registros de Ausencia" . PHP_EOL;
    echo "Errores     : " . count($res['errores']) . PHP_EOL;
    if (!empty($res['errores'])) {
        foreach ($res['errores'] as $err) echo "  ❌ {$err}" . PHP_EOL;
    }
    echo "Duración    : {$res['duracion_ms']} ms" . PHP_EOL;
}
