<?php
/**
 * RetardoHelper — Clasificador de Puntualidad
 * ==============================================
 * Clasifica la hora de llegada de un pasante sin modificar ni
 * descontar sus horas acumuladas.
 *
 * REGLA DE NEGOCIO:
 *   Los retardos son INFORMATIVOS, no penalizan las 8 horas
 *   que siempre se acreditan al pasante. Esta clasificación
 *   existe exclusivamente para el Dashboard de Puntualidad del Tutor.
 *
 * CATEGORÍAS:
 *   A tiempo     → llegó antes o dentro del periodo de gracia
 *   Retardo Leve → llegó tarde pero menos de 1 hora
 *   Retardo Severo → llegó con 1 hora o más de retraso
 */
class RetardoHelper
{
    // Minutos de gracia por defecto (configurable)
    public const GRACIA_MINUTOS   = 15;

    // Umbral para clasificar como "Severo" (en minutos de retraso)
    public const SEVERO_MINUTOS   = 60;

    // Códigos de clasificación (para uso en DB o lógica condicional)
    public const ESTADO_A_TIEMPO  = 'a_tiempo';
    public const ESTADO_LEVE      = 'retardo_leve';
    public const ESTADO_SEVERO    = 'retardo_severo';
    public const ESTADO_AUSENTE   = 'ausente';
    public const ESTADO_SIN_DATO  = 'sin_dato';

    /**
     * Clasifica la puntualidad de un registro de asistencia.
     *
     * @param  string|null $horaEntrada    Hora real de llegada  'HH:MM:SS' o null
     * @param  string      $horaAsignacion Hora oficial de entrada 'HH:MM:SS'
     * @param  int         $graciaMinutos  Minutos de tolerancia (default: 15)
     * @return string                      Una de las constantes ESTADO_*
     */
    public static function clasificar(
        ?string $horaEntrada,
        string  $horaAsignacion,
        int     $graciaMinutos = self::GRACIA_MINUTOS
    ): string {
        if ($horaEntrada === null || $horaEntrada === '') {
            return self::ESTADO_SIN_DATO;
        }

        $minEntrada    = self::horaAMinutos($horaEntrada);
        $minAsignacion = self::horaAMinutos($horaAsignacion);
        $retraso       = $minEntrada - $minAsignacion;

        if ($retraso <= $graciaMinutos) {
            return self::ESTADO_A_TIEMPO;
        }

        if ($retraso < self::SEVERO_MINUTOS) {
            return self::ESTADO_LEVE;
        }

        return self::ESTADO_SEVERO;
    }

    /**
     * Clasifica y retorna el array completo con todos los datos calculados.
     * Útil para el Dashboard de Puntualidad.
     *
     * @return array [
     *     'estado'        => string,
     *     'retraso_min'   => int,      // minutos de retraso (0 si llegó a tiempo)
     *     'etiqueta'      => string,   // texto legible para UI
     *     'color'         => string,   // clase CSS o hex para Bento UI
     *     'icono'         => string,   // Tabler Icon
     * ]
     */
    public static function analizar(
        ?string $horaEntrada,
        string  $horaAsignacion,
        string  $estadoAsistencia = 'Presente',
        int     $graciaMinutos    = self::GRACIA_MINUTOS
    ): array {
        if ($estadoAsistencia === 'Ausente') {
            return [
                'estado'      => self::ESTADO_AUSENTE,
                'retraso_min' => null,
                'etiqueta'    => 'Ausente',
                'color'       => '#ef4444',
                'icono'       => 'ti-user-off',
            ];
        }

        if ($horaEntrada === null || $horaEntrada === '') {
            return [
                'estado'      => self::ESTADO_SIN_DATO,
                'retraso_min' => null,
                'etiqueta'    => 'Sin registro',
                'color'       => '#94a3b8',
                'icono'       => 'ti-clock-off',
            ];
        }

        $minEntrada    = self::horaAMinutos($horaEntrada);
        $minAsignacion = self::horaAMinutos($horaAsignacion);
        $retraso       = max(0, $minEntrada - $minAsignacion);
        $estado        = self::clasificar($horaEntrada, $horaAsignacion, $graciaMinutos);

        switch ($estado) {
            case self::ESTADO_A_TIEMPO:
                return [
                    'estado'      => $estado,
                    'retraso_min' => 0,
                    'etiqueta'    => 'A tiempo',
                    'color'       => '#10b981',
                    'icono'       => 'ti-circle-check',
                ];

            case self::ESTADO_LEVE:
                $minStr = $retraso . ' min';
                return [
                    'estado'      => $estado,
                    'retraso_min' => $retraso,
                    'etiqueta'    => "Retardo Leve ({$minStr})",
                    'color'       => '#f59e0b',
                    'icono'       => 'ti-clock-exclamation',
                ];

            case self::ESTADO_SEVERO:
                $horas = intdiv($retraso, 60);
                $mins  = $retraso % 60;
                $label = $horas > 0 ? "{$horas}h {$mins}min" : "{$mins} min";
                return [
                    'estado'      => $estado,
                    'retraso_min' => $retraso,
                    'etiqueta'    => "Retardo Severo ({$label})",
                    'color'       => '#ef4444',
                    'icono'       => 'ti-alert-triangle',
                ];

            default:
                return [
                    'estado'      => self::ESTADO_SIN_DATO,
                    'retraso_min' => null,
                    'etiqueta'    => 'Sin dato',
                    'color'       => '#94a3b8',
                    'icono'       => 'ti-minus',
                ];
        }
    }

    /**
     * Genera un resumen estadístico de puntualidad para una lista de asistencias.
     * Usado por el Dashboard de Puntualidad del Tutor.
     *
     * @param  array  $asistencias  Array de objetos con hora_entrada, hora_asignacion, estado
     * @param  int    $graciaMinutos
     * @return array [
     *     'total'        => int,
     *     'a_tiempo'     => int,
     *     'leve'         => int,
     *     'severo'       => int,
     *     'ausente'      => int,
     *     'pct_puntual'  => float,  // porcentaje de días a tiempo
     *     'retraso_prom' => float,  // minutos promedio de retraso (excluye ausentes)
     * ]
     */
    public static function resumen(array $asistencias, int $graciaMinutos = self::GRACIA_MINUTOS): array
    {
        $contadores    = [
            self::ESTADO_A_TIEMPO => 0,
            self::ESTADO_LEVE     => 0,
            self::ESTADO_SEVERO   => 0,
            self::ESTADO_AUSENTE  => 0,
            self::ESTADO_SIN_DATO => 0,
        ];
        $totalRetraso  = 0;
        $diasConRetraso = 0;

        foreach ($asistencias as $a) {
            $horaEnt    = $a->hora_entrada  ?? null;
            $horaAsig   = $a->hora_asignacion ?? '08:00:00';
            $estadoAsist = $a->estado ?? 'Presente';

            $analisis = self::analizar($horaEnt, $horaAsig, $estadoAsist, $graciaMinutos);
            $contadores[$analisis['estado']] = ($contadores[$analisis['estado']] ?? 0) + 1;

            if ($analisis['retraso_min'] > 0) {
                $totalRetraso   += $analisis['retraso_min'];
                $diasConRetraso++;
            }
        }

        $total       = count($asistencias);
        $pctPuntual  = $total > 0
            ? round(($contadores[self::ESTADO_A_TIEMPO] / $total) * 100, 1)
            : 0.0;
        $retrasoProm = $diasConRetraso > 0
            ? round($totalRetraso / $diasConRetraso, 1)
            : 0.0;

        return [
            'total'        => $total,
            'a_tiempo'     => $contadores[self::ESTADO_A_TIEMPO],
            'leve'         => $contadores[self::ESTADO_LEVE],
            'severo'       => $contadores[self::ESTADO_SEVERO],
            'ausente'      => $contadores[self::ESTADO_AUSENTE],
            'sin_dato'     => $contadores[self::ESTADO_SIN_DATO],
            'pct_puntual'  => $pctPuntual,
            'retraso_prom' => $retrasoProm,
        ];
    }

    // ── Utilidades ────────────────────────────────────────────────────────────

    /**
     * Convierte una hora 'HH:MM:SS' o 'HH:MM' a minutos desde medianoche.
     */
    public static function horaAMinutos(string $hora): int
    {
        $partes = explode(':', $hora);
        return ((int)($partes[0] ?? 0) * 60) + (int)($partes[1] ?? 0);
    }

    /**
     * Convierte minutos desde medianoche a 'HH:MM'.
     */
    public static function minutosAHora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
