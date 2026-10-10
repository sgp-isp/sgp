<?php
/**
 * AutoFillService — Relleno de Asistencias y Utilidades de Períodos
 * ==================================================================
 * Detecta días laborables sin marcaje en asignaciones activas y los
 * rellena como "Ausente" para evitar días sin registro.
 *
 * REGLAS DE NEGOCIO:
 *  - Días sin marcaje    → Ausente (0 horas)
 *  - Feriados L-V        → Justificado (8 horas)
 *  - Nunca rellena fines de semana
 *  - Solo rellena hasta "ayer" — el día actual queda abierto
 *  - No sobreescribe registros existentes (INSERT IGNORE)
 *  - Registra metodo = 'AutoFill' y es_auto_fill = 1
 */
class AutoFillService
{
    private const DIAS_ATRAS_MAX = 365;

    /**
     * Punto de entrada principal del demonio.
     *
     * @param  Database $db      Instancia de la BD
     * @param  bool     $dryRun  Si true: solo simula sin insertar
     * @return array             Estadísticas de la ejecución
     */
    public static function ejecutar(Database $db, bool $dryRun = false): array
    {
        $stats = [
            'revisados'          => 0,
            'pasantes_afectados' => 0,
            'dias_rellenos'      => 0,
            'dias_omitidos'      => 0,
            'errores'            => 0,
            'detalle'            => [],
            'dry_run'            => $dryRun,
        ];

        $ayer        = date('Y-m-d', strtotime('-1 day'));
        $limiteAtras = date('Y-m-d', strtotime('-' . self::DIAS_ATRAS_MAX . ' days'));
        $feriados    = self::cargarFeriados($db);

        $db->query("
            SELECT a.id            AS asignacion_id,
                   a.pasante_id,
                   a.fecha_inicio,
                   LEAST(
                       COALESCE(dpa.fecha_fin_estimada, COALESCE(pa.fecha_fin, '9999-12-31')),
                       CURDATE() - INTERVAL 1 DAY
                   ) AS fecha_fin,
                   dp.nombres,
                   dp.apellidos
            FROM   asignaciones a
            JOIN   datos_personales  dp  ON dp.usuario_id  = a.pasante_id
            LEFT JOIN datos_pasante  dpa ON dpa.usuario_id = a.pasante_id
            LEFT JOIN periodos_academicos pa ON pa.id      = dpa.periodo_id
            WHERE  a.estado = 'activo'
              AND  a.fecha_inicio <= :ayer
            ORDER BY a.pasante_id
        ");
        $db->bind(':ayer', $ayer);
        $asignaciones = $db->resultSet();

        if (empty($asignaciones)) {
            $stats['detalle'][] = 'No hay asignaciones activas.';
            return $stats;
        }

        foreach ($asignaciones as $asig) {
            $stats['revisados']++;

            $fechaDesde = max($asig->fecha_inicio, $limiteAtras);
            $fechaHasta = min($asig->fecha_fin, $ayer);

            if ($fechaDesde > $fechaHasta) {
                continue;
            }

            try {
                $db->query("
                    SELECT fecha FROM asistencias
                    WHERE  pasante_id = :pid
                      AND  fecha BETWEEN :desde AND :hasta
                ");
                $db->bind(':pid',   $asig->pasante_id);
                $db->bind(':desde', $fechaDesde);
                $db->bind(':hasta', $fechaHasta);
                $rows = $db->resultSet();

                $existentes = array_flip(array_column($rows, 'fecha'));

                $diasLaborables = self::generarDiasLaborables($fechaDesde, $fechaHasta, $feriados);
                $diasARellenar  = array_filter($diasLaborables, fn($d) => !isset($existentes[$d]));

                $feriadosAJustificar = [];
                foreach ($feriados as $fFecha => $fNombre) {
                    if ($fFecha >= $fechaDesde && $fFecha <= $fechaHasta
                        && (int)(new \DateTime($fFecha))->format('N') < 6
                        && !isset($existentes[$fFecha])) {
                        $feriadosAJustificar[$fFecha] = $fNombre;
                    }
                }

                if (empty($diasARellenar) && empty($feriadosAJustificar)) {
                    continue;
                }

                if (!$dryRun) {
                    $db->beginTransaction();

                    foreach ($diasARellenar as $dia) {
                        $db->query("
                            INSERT IGNORE INTO asistencias
                                (pasante_id, asignacion_id, fecha,
                                 hora_registro, hora_entrada, hora_salida,
                                 horas_calculadas, estado, metodo, es_auto_fill,
                                 observacion, created_at)
                            VALUES
                                (:pid, :aid, :fecha,
                                 '00:00:00', '00:00:00', '00:00:00',
                                 0.00, 'Ausente', 'AutoFill', 1,
                                 'Sin registro — marcado ausente automáticamente',
                                 :created)
                        ");
                        $db->bind(':pid',     $asig->pasante_id);
                        $db->bind(':aid',     $asig->asignacion_id);
                        $db->bind(':fecha',   $dia);
                        $db->bind(':created', $dia . ' 23:59:00');
                        $db->execute();
                    }

                    foreach ($feriadosAJustificar as $diaF => $nomF) {
                        $db->query("
                            INSERT IGNORE INTO asistencias
                                (pasante_id, asignacion_id, fecha,
                                 hora_registro, hora_entrada, hora_salida,
                                 horas_calculadas, estado, metodo, es_auto_fill,
                                 motivo_justificacion, observacion, created_at)
                            VALUES
                                (:pid, :aid, :fecha,
                                 '08:00:00', '08:00:00', '16:00:00',
                                 8.00, 'Justificado', 'AutoFill', 1,
                                 :motivo, 'Feriado — completado automáticamente',
                                 :created)
                        ");
                        $db->bind(':pid',     $asig->pasante_id);
                        $db->bind(':aid',     $asig->asignacion_id);
                        $db->bind(':fecha',   $diaF);
                        $db->bind(':motivo',  $nomF);
                        $db->bind(':created', $diaF . ' 23:59:00');
                        $db->execute();
                    }

                    $db->commit();
                }

                $cantRellenos    = count($diasARellenar);
                $cantJustificados = count($feriadosAJustificar);
                $allDias = array_merge(array_values($diasARellenar), array_keys($feriadosAJustificar));
                sort($allDias);

                $stats['dias_rellenos']      += $cantRellenos + $cantJustificados;
                $stats['dias_omitidos']      += count($existentes);
                $stats['pasantes_afectados'] += 1;
                $stats['detalle'][]           = [
                    'pasante'      => $asig->nombres . ' ' . $asig->apellidos,
                    'pasante_id'   => $asig->pasante_id,
                    'ausentes'     => $cantRellenos,
                    'justificados' => $cantJustificados,
                    'primer_dia'   => reset($allDias) ?: null,
                    'ultimo_dia'   => end($allDias)   ?: null,
                ];

            } catch (\Throwable $e) {
                if (!$dryRun) {
                    try { $db->rollBack(); } catch (\Throwable $_) {}
                }
                $stats['errores']++;
                $stats['detalle'][] = [
                    'error'      => true,
                    'pasante_id' => $asig->pasante_id,
                    'mensaje'    => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Carga todos los feriados de la BD en un array indexado por fecha.
     * Devuelve: ['2026-01-01' => 'Año Nuevo', ...]
     */
    public static function cargarFeriados(Database $db): array
    {
        $db->query("SELECT fecha, nombre FROM dias_feriados WHERE es_laborable = 0 ORDER BY fecha");
        $rows = $db->resultSet();

        $set = [];
        foreach ($rows as $r) {
            $set[$r->fecha] = $r->nombre;
        }
        return $set;
    }

    /**
     * Genera la lista de días laborables (lunes–viernes, sin feriados)
     * entre dos fechas inclusive.
     */
    public static function generarDiasLaborables(string $desde, string $hasta, array $feriados): array
    {
        $dias   = [];
        $cursor = new \DateTime($desde);
        $fin    = new \DateTime($hasta);

        while ($cursor <= $fin) {
            $dow  = (int) $cursor->format('N');
            $date = $cursor->format('Y-m-d');

            if ($dow < 6 && !isset($feriados[$date])) {
                $dias[] = $date;
            }
            $cursor->modify('+1 day');
        }

        return $dias;
    }
}
