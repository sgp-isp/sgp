-- =====================================================================
-- MIGRACIÓN: Campo es_firmante en datos_personales
-- Propósito: Permitir elegir qué administrador firma los documentos PDF
--            sin depender del orden de registro (LIMIT 1).
-- Ejecución: Una sola vez. Idempotente gracias al IF NOT EXISTS manual.
-- =====================================================================

-- 1. Agregar la columna si no existe
ALTER TABLE `datos_personales`
    ADD COLUMN IF NOT EXISTS `es_firmante` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = este admin firma los reportes PDF institucionales';

-- 2. Marcar como firmante al primer administrador activo (rol_id=1)
--    Solo si ninguno está marcado todavía (primera ejecución).
UPDATE `datos_personales` dp
JOIN   `usuarios` u ON u.id = dp.usuario_id
SET    dp.es_firmante = 1
WHERE  u.rol_id   = 1
  AND  u.estado   = 'activo'
  AND  dp.es_firmante = 0
ORDER  BY u.id ASC
LIMIT  1;
