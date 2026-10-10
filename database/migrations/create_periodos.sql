-- ============================================================
-- Migración: Módulo Períodos Académicos
-- SGP — Instituto de Salud Pública de Bolívar
-- ============================================================

-- 1. Crear tabla periodos_academicos si no existe
CREATE TABLE IF NOT EXISTS `periodos_academicos` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`        VARCHAR(100) NOT NULL,
    `descripcion`   TEXT NULL,
    `fecha_inicio`  DATE NOT NULL,
    `fecha_fin`     DATE NOT NULL,
    `estado`        ENUM('Activo','Cerrado','Planificado') NOT NULL DEFAULT 'Planificado',
    `creado_por`    INT UNSIGNED NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Agregar periodo_id a datos_pasante si no existe
ALTER TABLE `datos_pasante` ADD COLUMN IF NOT EXISTS `periodo_id` INT UNSIGNED NULL;
