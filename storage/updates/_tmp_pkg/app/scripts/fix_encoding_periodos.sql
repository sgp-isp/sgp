-- Fix encoding: correct garbled accented characters in periodos_academicos
SET NAMES utf8mb4;

UPDATE `periodos_academicos`
SET
    `nombre`      = 'Período 2025-2026',
    `descripcion` = 'Pasantías Profesionales - Instituto de Salud Pública del Estado Bolívar'
WHERE id = 4;

SELECT id, nombre, descripcion FROM `periodos_academicos`;
