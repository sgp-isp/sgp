<?php
/**
 * DateHelper — Utilidades de Fechas y Dias Habiles para SGP
 * ===========================================================
 *
 * [OPT-01] Centraliza la expresion SQL de dias habiles (L-V) que antes
 * estaba duplicada en PasanteModel (x2), PeriodosController (x2) y
 * AsistenciasController. Ahora existe un unico punto de verdad.
 *
 * @package SGP\Helpers
 */
class DateHelper
{
    /**
     * Fragmento SQL que calcula dias habiles (L-V) entre dos fechas.
     * Usa aritmetica de enteros (FLOOR + DAYOFWEEK) — sin iteracion.
     *
     * @param string $campoInicio  Columna/expresion SQL de fecha inicio
     * @param string $campoFin     Columna/expresion SQL de fecha fin
     * @return string              Fragmento SQL embebible en SELECT/CASE
     */
    public static function diasHabilesSQL(string $campoInicio, string $campoFin): string
    {
        return "GREATEST(0,
            DATEDIFF({$campoFin}, {$campoInicio}) + 1
            - FLOOR((DATEDIFF({$campoFin}, {$campoInicio}) + DAYOFWEEK({$campoInicio})) / 7)
            - FLOOR((DATEDIFF({$campoFin}, {$campoInicio}) + DAYOFWEEK({$campoInicio}) - 1) / 7)
        )";
    }

    /**
     * Macro SQL de dias habiles con limite de fecha_fin estimada/del periodo.
     * Equivale al fragmento recurrente en PasanteModel para horas acumuladas.
     *
     * @return string  Fragmento SQL listo para usar en SELECT
     */
    public static function diasHabilesHastaHoySQL(): string
    {
        $fin    = "LEAST(COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin), CURDATE())";
        $inicio = "dpa.fecha_inicio_pasantia";
        return self::diasHabilesSQL($inicio, $fin);
    }

    /**
     * Calcula en PHP cuantos dias habiles L-V hay entre dos fechas.
     *
     * @param string $fechaInicio  Formato Y-m-d
     * @param string $fechaFin     Formato Y-m-d
     * @return int                 Numero de dias habiles
     */
    public static function calcularDiasHabilesPHP(string $fechaInicio, string $fechaFin): int
    {
        $inicio  = new DateTime($fechaInicio);
        $fin     = new DateTime($fechaFin);
        $conteo  = 0;
        $current = clone $inicio;

        while ($current <= $fin) {
            $dow = (int)$current->format('N');
            if ($dow <= 5) {
                $conteo++;
            }
            $current->modify('+1 day');
        }

        return $conteo;
    }

    /**
     * Calcula horas meta: dias habiles L-V x 8h.
     * Garantiza minimo de 160h (20 dias = 1 mes laboral).
     *
     * @param string $fechaInicio  Formato Y-m-d
     * @param string $fechaFin     Formato Y-m-d
     * @return int                 Horas meta (multiplo de 8)
     */
    public static function calcularHorasMeta(string $fechaInicio, string $fechaFin): int
    {
        $dias = self::calcularDiasHabilesPHP($fechaInicio, $fechaFin);
        return max(160, $dias * 8);
    }

    /**
     * Calcula la fecha fin estimada sumando N dias habiles a una fecha inicio.
     * Salta sabados, domingos y feriados opcionales.
     *
     * @param string   $fechaInicio  Formato Y-m-d
     * @param int      $diasHabiles  Dias habiles a agregar (default 180 aprox 9 meses)
     * @param string[] $feriados     Array de fechas Y-m-d a excluir
     * @return string                Fecha fin en formato Y-m-d
     */
    public static function calcularFechaFin(
        string $fechaInicio,
        int    $diasHabiles = 180,
        array  $feriados    = []
    ): string {
        $fecha  = new DateTime($fechaInicio);
        $conteo = 0;

        while ($conteo < $diasHabiles) {
            $fecha->modify('+1 day');
            $dow = (int)$fecha->format('N');

            if ($dow >= 6) {
                continue;
            }
            if (in_array($fecha->format('Y-m-d'), $feriados, true)) {
                continue;
            }

            $conteo++;
        }

        return $fecha->format('Y-m-d');
    }
}
