-- ============================================================
-- Migración: Historial Multi-Período en datos_pasante (BUG-04)
-- SGP — Instituto de Salud Pública de Bolívar
-- ============================================================
--
-- PROPÓSITO:
-- Permitir que un pasante que haya cursado un período académico previo
-- pueda ser asignado a un nuevo período sin sobrescribir ni destruir
-- el historial de pasantía del período anterior.
--
-- CAMBIOS:
-- 1. Elimina el índice UNIQUE simple sobre `usuario_id` en `datos_pasante`.
-- 2. Agrega el índice UNIQUE compuesto `(usuario_id, periodo_id)`.
--    Esto permite 1 registro por cada período cursado por el pasante.
-- ============================================================

-- 1. Eliminar índice unique simple anterior si existe
SET @exist_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'datos_pasante'
      AND INDEX_NAME = 'usuario_id'
);

SET @sql_drop := IF(@exist_idx > 0,
    'ALTER TABLE `datos_pasante` DROP INDEX `usuario_id`',
    'SELECT 1'
);
PREPARE stmt_drop FROM @sql_drop;
EXECUTE stmt_drop;
DEALLOCATE PREPARE stmt_drop;

-- 2. Agregar índice UNIQUE compuesto (usuario_id, periodo_id) si no existe
SET @exist_uq := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'datos_pasante'
      AND INDEX_NAME = 'uq_pasante_periodo'
);

SET @sql_add := IF(@exist_uq = 0,
    'ALTER TABLE `datos_pasante` ADD UNIQUE KEY `uq_pasante_periodo` (`usuario_id`, `periodo_id`)',
    'SELECT 1'
);
PREPARE stmt_add FROM @sql_add;
EXECUTE stmt_add;
DEALLOCATE PREPARE stmt_add;
