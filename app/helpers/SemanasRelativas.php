<?php
/**
 * SemanasRelativas — Motor de Semanas Relativas para SGP
 * ========================================================
 * Calcula semanas de pasantía basándose MATEMÁTICAMENTE en la
 * fecha_inicio del pasante. NUNCA usa date('W') de PHP.
 *
 * PROBLEMA que resuelve:
 *   date('W') retorna la semana del año calendario ISO, lo que provoca
 *   cruces de año (semana 52 de dic → semana 1 de ene) y mezcla
 *   pasantes que iniciaron en fechas distintas.
 *
 * SOLUCIÓN:
 *   Semana 1 = primera semana laboral a partir de fecha_inicio.
 *   Cada 5 días laborables = una semana de pasantía.
 *   Las semanas son relativas a cada pasante, no al calendario civil.
 *
 * EJEMPLO:
 *   inicio = 2025-09-15 (lunes)
 *   Semana 1: 15-sep al 19-sep
 *   Semana 2: 22-sep al 26-sep
 *   Semana 13 (trimestre 1): 08-dic al 12-dic
 *
 * USO:
 *   $semanas = SemanasRelativas::obtenerTrimestre($inicio, 1, $feriados);
 *   $num     = SemanasRelativas::numSemana($inicio, '2025-09-22');
 */
class SemanasRelativas
{
    /** Días laborables por semana */
    private const DIAS_POR_SEMANA = 5;

    /** Semanas por trimestre */
    private const SEMANAS_POR_TRIMESTRE = 13;

    /**
     * Retorna el número de semana relativa (1-based) para una fecha dada.
     *
     * @param  string $fechaInicio  Fecha de inicio de la pasantía 'Y-m-d'
     * @param  string $fecha        Fecha objetivo 'Y-m-d'
     * @param  array  $feriados     Set de feriados ['Y-m-d' => nombre]
     * @return int                  Número de semana (≥1); 0 si fecha < inicio
     */
    public static function numSemana(string $fechaInicio, string $fecha, array $feriados = []): int
    {
        $inicio  = new \DateTime($fechaInicio);
        $objetivo = new \DateTime($fecha);

        if ($objetivo < $inicio) {
            return 0;
        }

        // Contar días laborables desde inicio hasta fecha (inclusive inicio)
        $diasLaborables = self::contarDiasLaborables($fechaInicio, $fecha, $feriados);

        // Semana = ceil(días laborables / 5)
        return (int) ceil($diasLaborables / self::DIAS_POR_SEMANA);
    }

    /**
     * Retorna el trimestre al que pertenece una semana.
     * Trimestre 1: semanas 1-13 | Trimestre 2: 14-26 | Trimestre 3: 27-36
     *
     * @param  int $numSemana
     * @return int  1, 2 o 3
     */
    public static function numTrimestre(int $numSemana): int
    {
        return (int) ceil($numSemana / self::SEMANAS_POR_TRIMESTRE);
    }

    /**
     * Genera la estructura completa de un trimestre para un pasante.
     * Devuelve un array con todas las semanas del trimestre, sus fechas
     * y los días laborables de cada semana.
     *
     * Usado principalmente por el generador de PDFs (DomPDF).
     *
     * @param  string $fechaInicio  Fecha de inicio de la pasantía
     * @param  int    $trimestre    Número de trimestre (1, 2 o 3)
     * @param  array  $feriados     Set de feriados ['Y-m-d' => nombre]
     * @return array  [
     *     'trimestre' => 1,
     *     'semanas'   => [
     *         1 => [
     *             'num'          => 1,
     *             'fecha_inicio' => '2025-09-15',
     *             'fecha_fin'    => '2025-09-19',
     *             'dias'         => ['2025-09-15', '2025-09-16', ...],
     *         ],
     *         ...
     *     ]
     * ]
     */
    public static function obtenerTrimestre(string $fechaInicio, int $trimestre, array $feriados = []): array
    {
        $semanaInicio = ($trimestre - 1) * self::SEMANAS_POR_TRIMESTRE + 1;
        $semanaFin    = $trimestre       * self::SEMANAS_POR_TRIMESTRE;

        $resultado = [
            'trimestre' => $trimestre,
            'semanas'   => [],
        ];

        $cursor   = new \DateTime($fechaInicio);
        $semActual = 1;
        $diasEnSemana = [];

        // Avanzar hasta la semana de inicio del trimestre
        while ($semActual < $semanaInicio) {
            $dias = self::avanzarUnaSemanaLaboral($cursor, $feriados);
            $semActual++;
        }

        // Recoger las semanas del trimestre
        while ($semActual <= $semanaFin) {
            $diasSem = self::avanzarUnaSemanaLaboral($cursor, $feriados);

            if (!empty($diasSem)) {
                $resultado['semanas'][$semActual] = [
                    'num'          => $semActual,
                    'fecha_inicio' => $diasSem[0],
                    'fecha_fin'    => end($diasSem),
                    'dias'         => $diasSem,
                ];
            }
            $semActual++;
        }

        return $resultado;
    }

    /**
     * Retorna las fechas de inicio y fin de una semana relativa específica.
     *
     * @param  string $fechaInicio  Fecha de inicio de la pasantía
     * @param  int    $numSemana    Número de semana (1-based)
     * @param  array  $feriados
     * @return array ['inicio' => 'Y-m-d', 'fin' => 'Y-m-d', 'dias' => [...]]
     */
    public static function obtenerSemana(string $fechaInicio, int $numSemana, array $feriados = []): array
    {
        $cursor = new \DateTime($fechaInicio);

        for ($s = 1; $s < $numSemana; $s++) {
            self::avanzarUnaSemanaLaboral($cursor, $feriados);
        }

        $dias = self::avanzarUnaSemanaLaboral($cursor, $feriados);

        return [
            'num'    => $numSemana,
            'inicio' => $dias[0]    ?? $fechaInicio,
            'fin'    => end($dias)  ?: $fechaInicio,
            'dias'   => $dias,
        ];
    }

    /**
     * Genera el mapa completo de semanas de toda la pasantía (180 días).
     * Útil para el Almanaque Anual.
     *
     * @return array  [semana_num => [...]]
     */
    public static function obtenerMapaCompleto(string $fechaInicio, array $feriados = []): array
    {
        $cursor    = new \DateTime($fechaInicio);
        $semanas   = [];
        $numSemana = 1;
        $diasTotales = 0;
        $limite    = 180; // días laborables máximos

        while ($diasTotales < $limite) {
            $dias = self::avanzarUnaSemanaLaboral($cursor, $feriados);
            if (empty($dias)) break;

            $diasTotales += count($dias);

            $semanas[$numSemana] = [
                'num'          => $numSemana,
                'trimestre'    => self::numTrimestre($numSemana),
                'fecha_inicio' => $dias[0],
                'fecha_fin'    => end($dias),
                'dias'         => $dias,
            ];
            $numSemana++;
        }

        return $semanas;
    }

    // ── Métodos privados ──────────────────────────────────────────────────────

    /**
     * Avanza el cursor exactamente 5 días laborables y retorna esas fechas.
     * El cursor queda apuntando al día siguiente al último día de la semana.
     *
     * @param  \DateTime $cursor   Se modifica in-place
     * @param  array     $feriados
     * @return string[]            Fechas de los días laborables de la semana
     */
    private static function avanzarUnaSemanaLaboral(\DateTime $cursor, array $feriados): array
    {
        $dias     = [];
        $contados = 0;

        while ($contados < self::DIAS_POR_SEMANA) {
            $dow  = (int) $cursor->format('N');
            $date = $cursor->format('Y-m-d');

            if ($dow < 6 && !isset($feriados[$date])) {
                $dias[] = $date;
                $contados++;
            }
            $cursor->modify('+1 day');
        }

        return $dias;
    }

    /**
     * Cuenta días laborables entre dos fechas (inclusive).
     */
    private static function contarDiasLaborables(string $desde, string $hasta, array $feriados): int
    {
        $count  = 0;
        $cursor = new \DateTime($desde);
        $fin    = new \DateTime($hasta);

        while ($cursor <= $fin) {
            $dow  = (int) $cursor->format('N');
            $date = $cursor->format('Y-m-d');
            if ($dow < 6 && !isset($feriados[$date])) {
                $count++;
            }
            $cursor->modify('+1 day');
        }

        return $count;
    }
}
