-- ============================================================
-- Migración: Nuevos tipos de pregunta en Exámenes Rápidos
-- Agrega soporte para Emparejamiento (Tap-to-Match estilo Duolingo)
-- SGP — Sistema de Gestión de Pasantías
-- ============================================================

ALTER TABLE examenes
    ADD COLUMN duracion_minutos INT NULL DEFAULT NULL AFTER intentos_permitidos;

ALTER TABLE examen_preguntas
    MODIFY COLUMN tipo ENUM('opcion_multiple','verdadero_falso','emparejamiento')
    NOT NULL DEFAULT 'opcion_multiple';

ALTER TABLE examen_opciones
    ADD COLUMN texto_par VARCHAR(500) NULL DEFAULT NULL AFTER texto;

ALTER TABLE examen_respuestas
    ADD COLUMN opcion_par_elegida_id INT NULL DEFAULT NULL AFTER opcion_id_elegida;
