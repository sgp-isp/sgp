-- ============================================================
-- Migración: Agregar columna `tipo` a periodos_academicos
-- SGP — Instituto de Salud Pública de Bolívar
-- Fecha: 2026-05-09
-- Razón: PeriodosController y ActividadesController ya usan
--        WHERE tipo = 'Corto'/'Regular', pero la migración
--        original no definió esta columna.
-- ============================================================

ALTER TABLE `periodos_academicos`
    ADD COLUMN IF NOT EXISTS `tipo` ENUM('Regular','Corto') NOT NULL DEFAULT 'Regular'
    AFTER `nombre`;

-- Actualizar periodos existentes: si el nombre contiene
-- palabras clave de periodo corto, marcarlo como Corto.
-- Ajustar el UPDATE según los nombres reales en producción.
UPDATE `periodos_academicos`
SET `tipo` = 'Corto'
WHERE LOWER(nombre) REGEXP 'corto|short|pasant|brigad';
