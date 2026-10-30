<?php
declare(strict_types=1);

/**
 * PeriodoService — Servicio Centralizado de Contexto y Ciclo de Vida de Períodos
 *
 * SGP — Instituto de Salud Pública de Bolívar
 *
 * PROPÓSITO:
 * Proporcionar una única fuente de verdad sobre el estado del período académico
 * actual para todos los roles del sistema (Admin, SuperAdmin, Tutor, Pasante).
 * Evita consultas SQL duplicadas y garantiza que los módulos operativos nunca
 * mezclen datos de cohortes cerradas o pasantes culminados.
 */
class PeriodoService
{
    private static ?object $periodoActivo = null;
    private static bool $activoConsultado = false;
    private static ?int $totalHistoricos = null;

    /**
     * Obtiene el período académico activo actual (con caché en memoria por request).
     */
    public static function getActivo(): ?object
    {
        if (self::$activoConsultado) {
            return self::$periodoActivo;
        }

        try {
            $db = Database::getInstance();
            $db->query("
                SELECT id, nombre, estado, fecha_inicio, fecha_fin, tipo,
                       DATEDIFF(fecha_fin, CURDATE()) AS dias_restantes
                FROM periodos_academicos
                WHERE estado = 'Activo'
                ORDER BY fecha_inicio DESC
                LIMIT 1
            ");
            $res = $db->single();
            self::$periodoActivo = $res ?: null;
        } catch (\Throwable $e) {
            error_log('[PeriodoService::getActivo] ' . $e->getMessage());
            self::$periodoActivo = null;
        }

        self::$activoConsultado = true;
        return self::$periodoActivo;
    }

    /**
     * Indica si existe un período académico activo en este momento.
     */
    public static function hayPeriodoActivo(): bool
    {
        return self::getActivo() !== null;
    }

    /**
     * Retorna el ID del período en contexto actual.
     * Prioridad:
     * 1. Período filtrado explícitamente en la sesión (`selected_periodo_id`).
     * 2. Período activo vigente en el sistema.
     * 3. 0 si no hay ningún período en contexto.
     */
    public static function getIdContexto(): int
    {
        $enSesion = (int)(Session::get('selected_periodo_id') ?? 0);
        if ($enSesion > 0) {
            return $enSesion;
        }

        $activo = self::getActivo();
        return (int)($activo->id ?? 0);
    }

    /**
     * Retorna el total de pasantes (usuarios con rol_id = 3) registrados en toda la historia del sistema.
     * Permite saber si una tabla está vacía por falta de alumnos o porque ya culminaron.
     */
    public static function totalPasantesHistoricos(): int
    {
        if (self::$totalHistoricos !== null) {
            return self::$totalHistoricos;
        }

        try {
            $db = Database::getInstance();
            $db->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol_id = 3");
            self::$totalHistoricos = (int)($db->single()->total ?? 0);
        } catch (\Throwable $e) {
            self::$totalHistoricos = 0;
        }

        return self::$totalHistoricos;
    }

    /**
     * Retorna el conteo de pasantes activos en el período actual.
     */
    public static function totalPasantesActivos(): int
    {
        $activo = self::getActivo();
        if (!$activo) {
            return 0;
        }

        try {
            $db = Database::getInstance();
            $db->query("
                SELECT COUNT(*) AS total
                FROM datos_pasante
                WHERE periodo_id = :pid AND estado_pasantia = 'Activo'
            ");
            $db->bind(':pid', $activo->id);
            return (int)($db->single()->total ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Genera una cláusula SQL segura para filtrar únicamente pasantes activos
     * de la cohorte activa en módulos operativos.
     */
    public static function clausulaFiltroActivo(string $aliasDpa = 'dpa'): string
    {
        $activo = self::getActivo();
        if (!$activo) {
            // Si no hay período activo, forzar condición falsa para que no traiga registros viejos
            return "AND 1 = 0";
        }
        $pid = (int)$activo->id;
        return "AND {$aliasDpa}.periodo_id = {$pid} AND {$aliasDpa}.estado_pasantia = 'Activo'";
    }

    /**
     * Limpia la caché en memoria (usar tras crear o cerrar un período).
     */
    public static function limpiarCache(): void
    {
        self::$periodoActivo = null;
        self::$activoConsultado = false;
        self::$totalHistoricos = null;
    }
}
