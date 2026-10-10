-- ══════════════════════════════════════════════════════════════════
-- Migración: Módulo "Mis Actividades" para Pasantes
-- Fecha   : 2026-05-14
-- Descripción: Registro diario de actividades/resumen de trabajo
--              realizadas por el pasante durante su pasantía.
-- ══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS actividades_pasante (
    id          INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    pasante_id  INT            NOT NULL,
    fecha       DATE           NOT NULL,
    titulo      VARCHAR(150)   NOT NULL,
    descripcion TEXT           NOT NULL,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pasante_fecha (pasante_id, fecha),
    CONSTRAINT fk_act_pasante FOREIGN KEY (pasante_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
