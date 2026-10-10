-- ============================================================
-- Migración: Evidencias fotográficas de Actividades Extras
-- Descripción: Almacena rutas de imágenes subidas como
--              evidencia para actividades de Servicio Comunitario
-- Fecha: 2026-04-20
-- ============================================================

CREATE TABLE IF NOT EXISTS `actividad_evidencias` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actividad_id` INT UNSIGNED NOT NULL,
    `ruta`         VARCHAR(255) NOT NULL,
    `nombre_orig`  VARCHAR(150) NULL,
    `descripcion`  VARCHAR(255) NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`actividad_id`) REFERENCES `actividades_extras`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
