-- ======================================================================
-- SGP — FERIADOS COMPLETOS 2025-2026 + PERÍODO DE PRODUCCIÓN
-- Instituto de Salud Pública del Estado Bolívar
-- ======================================================================
-- Versión : 1.0  |  Fecha: 2026-04-30
-- Idempotente: seguro para ejecutar múltiples veces (INSERT IGNORE)
--
-- PROPÓSITO:
--   1. Limpiar fechas de Carnaval incorrectas (si se ejecutó el script anterior)
--   2. Completar feriados nacionales/regionales para 2025 y 2026 (año completo)
--   3. Configurar el período académico de producción 2025-10-03 → 2026-06-01
--
-- RELACIÓN CON ASISTENCIAS:
--   Los días en esta tabla son excluidos automáticamente por el sistema
--   de auto-fill, que los registra como "Justificado" en lugar de "Ausente".
--   Aplican globalmente a todos los pasantes del período activo.
--
-- CALENDARIO DE REFERENCIA:
--   Carnaval 2025:     Lun 03/Mar — Mar 04/Mar  (Miércoles Ceniza: 05/Mar)
--   Semana Santa 2025: Lun 14/Abr → Vie 18/Abr  (Pascua: 20/Abr)
--   Carnaval 2026:     Lun 16/Feb — Mar 17/Feb   (Miércoles Ceniza: 18/Feb)
--   Semana Santa 2026: Lun 30/Mar → Vie 03/Abr  (Pascua: 05/Abr)
-- ======================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
-- PASO 1: Limpiar entradas de Carnaval incorrectas
-- El script setup_produccion_periodo_2025_2026.sql insertó
-- 2026-03-02 y 2026-03-03 como Carnaval (INCORRECTO).
-- El Carnaval 2026 real es 16 y 17 de Febrero.
-- ─────────────────────────────────────────────────────────────
DELETE FROM dias_feriados
WHERE  fecha IN ('2026-03-02', '2026-03-03')
  AND  nombre LIKE '%Carnaval%';

-- También limpiar entradas de Lunes/Martes/Miercoles Santo 2026
-- si vinieron duplicadas del script anterior con nombres distintos
DELETE FROM dias_feriados
WHERE  fecha IN ('2026-03-30','2026-03-31','2026-04-01','2026-04-02','2026-04-03')
  AND  nombre LIKE '%Santo%'
  AND  id NOT IN (
      SELECT * FROM (SELECT MIN(id) FROM dias_feriados WHERE fecha IN ('2026-03-30','2026-03-31','2026-04-01','2026-04-02','2026-04-03') GROUP BY fecha) t
  );

-- ─────────────────────────────────────────────────────────────
-- PASO 2: FERIADOS VENEZUELA 2025 — AÑO COMPLETO
-- (INSERT IGNORE respeta los que ya existen de fase1_migracion.sql)
--
-- Días obligatorios por LOTTT + asuetos decretados comúnmente
-- por el ejecutivo nacional para la administración pública.
-- ─────────────────────────────────────────────────────────────

-- ── Enero ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-01-01', 'Año Nuevo',                                    'Nacional',  NOW()),
('2025-01-06', 'Día de Reyes',                                 'Nacional',  NOW());

-- ── Febrero ──────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-02-12', 'Día de la Juventud',                           'Nacional',  NOW());

-- ── Carnaval 2025 (Lun-Mar antes del Miércoles de Ceniza 05/Mar) ─
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-03-03', 'Carnaval — Lunes',                             'Nacional',  NOW()),
('2025-03-04', 'Carnaval — Martes',                            'Nacional',  NOW());

-- ── Marzo ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-03-19', 'San José',                                     'Nacional',  NOW());

-- ── Semana Santa 2025 (Pascua: 20 de Abril) ──────────────────
-- Solo Jueves y Viernes son obligatorios por ley.
-- Lunes-Miércoles son asueto habitual en la administración pública.
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-04-14', 'Lunes Santo — Semana Santa',                   'Nacional',  NOW()),
('2025-04-15', 'Martes Santo — Semana Santa',                  'Nacional',  NOW()),
('2025-04-16', 'Miércoles Santo — Semana Santa',               'Nacional',  NOW()),
('2025-04-17', 'Jueves Santo — Semana Santa',                  'Nacional',  NOW()),
('2025-04-18', 'Viernes Santo — Semana Santa',                 'Nacional',  NOW());

-- ── Abril ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-04-19', 'Declaración de Independencia de Venezuela',    'Nacional',  NOW());

-- ── Mayo ─────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-05-01', 'Día Internacional del Trabajador',             'Nacional',  NOW());

-- ── Junio ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-06-24', 'Batalla de Carabobo / Día del Ejército',       'Nacional',  NOW());

-- ── Julio ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-07-05', 'Día de la Independencia',                      'Nacional',  NOW()),
('2025-07-24', 'Natalicio del Libertador Simón Bolívar',       'Nacional',  NOW());

-- ── Agosto ───────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-08-03', 'Aniversario Fundación Ciudad Bolívar',         'Regional',  NOW());

-- ── Octubre ──────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-10-12', 'Día de la Resistencia Indígena',               'Nacional',  NOW());

-- ── Noviembre ────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-11-17', 'Aniversario de Ciudad Bolívar',                'Regional',  NOW());

-- ── Diciembre ────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2025-12-08', 'Inmaculada Concepción',                        'Nacional',  NOW()),
('2025-12-17', 'Muerte del Libertador Simón Bolívar',          'Nacional',  NOW()),
('2025-12-24', 'Nochebuena',                                   'Nacional',  NOW()),
('2025-12-25', 'Navidad',                                      'Nacional',  NOW()),
('2025-12-31', 'Fin de Año',                                   'Nacional',  NOW());

-- ─────────────────────────────────────────────────────────────
-- PASO 3: FERIADOS VENEZUELA 2026 — AÑO COMPLETO
-- (fase1_migracion.sql ya insertó Ene-Ago; solo faltan Sep-Dic)
-- INSERT IGNORE garantiza que no se duplica nada ya existente.
-- ─────────────────────────────────────────────────────────────

-- ── Enero ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-01-01', 'Año Nuevo',                                    'Nacional',  NOW()),
('2026-01-06', 'Día de Reyes',                                 'Nacional',  NOW());

-- ── Febrero ──────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-02-12', 'Día de la Juventud',                           'Nacional',  NOW());

-- ── Carnaval 2026 (Lun-Mar antes del Miércoles de Ceniza 18/Feb) ─
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-02-16', 'Carnaval — Lunes',                             'Nacional',  NOW()),
('2026-02-17', 'Carnaval — Martes',                            'Nacional',  NOW());

-- ── Marzo ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-03-19', 'San José',                                     'Nacional',  NOW());

-- ── Semana Santa 2026 (Pascua: 5 de Abril) ───────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-03-30', 'Lunes Santo — Semana Santa',                   'Nacional',  NOW()),
('2026-03-31', 'Martes Santo — Semana Santa',                  'Nacional',  NOW()),
('2026-04-01', 'Miércoles Santo — Semana Santa',               'Nacional',  NOW()),
('2026-04-02', 'Jueves Santo — Semana Santa',                  'Nacional',  NOW()),
('2026-04-03', 'Viernes Santo — Semana Santa',                 'Nacional',  NOW());

-- ── Abril ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-04-19', 'Declaración de Independencia de Venezuela',    'Nacional',  NOW());

-- ── Mayo ─────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-05-01', 'Día Internacional del Trabajador',             'Nacional',  NOW());

-- ── Junio ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-06-24', 'Batalla de Carabobo / Día del Ejército',       'Nacional',  NOW());

-- ── Julio ────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-07-05', 'Día de la Independencia',                      'Nacional',  NOW()),
('2026-07-24', 'Natalicio del Libertador Simón Bolívar',       'Nacional',  NOW());

-- ── Agosto ───────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-08-03', 'Aniversario Fundación Ciudad Bolívar',         'Regional',  NOW());

-- ── Octubre ──────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-10-12', 'Día de la Resistencia Indígena',               'Nacional',  NOW());

-- ── Noviembre ────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-11-17', 'Aniversario de Ciudad Bolívar',                'Regional',  NOW());

-- ── Diciembre ────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
('2026-12-08', 'Inmaculada Concepción',                        'Nacional',  NOW()),
('2026-12-17', 'Muerte del Libertador Simón Bolívar',          'Nacional',  NOW()),
('2026-12-24', 'Nochebuena',                                   'Nacional',  NOW()),
('2026-12-25', 'Navidad',                                      'Nacional',  NOW()),
('2026-12-31', 'Fin de Año',                                   'Nacional',  NOW());

-- ─────────────────────────────────────────────────────────────
-- PASO 4: PERÍODO ACADÉMICO DE PRODUCCIÓN
--
-- Cierra cualquier período activo previo (incluyendo el de
-- fase1_migracion.sql que tenía fechas Sep-Ago incorrectas).
-- Luego inserta/actualiza el período correcto de producción.
-- ─────────────────────────────────────────────────────────────

-- Cerrar períodos anteriores que no correspondan a las fechas de producción
UPDATE periodos_academicos
SET    estado = 'cerrado'
WHERE  NOT (fecha_inicio = '2025-10-03' AND fecha_fin = '2026-06-01');

-- Insertar el período correcto si no existe aún
INSERT INTO periodos_academicos (nombre, descripcion, fecha_inicio, fecha_fin, estado, created_at)
SELECT
    'Período 2025-2026',
    'Pasantías Profesionales — Instituto de Salud Pública del Estado Bolívar',
    '2025-10-03',
    '2026-06-01',
    'activo',
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM periodos_academicos
    WHERE fecha_inicio = '2025-10-03' AND fecha_fin = '2026-06-01'
);

-- Si ya existía con esas fechas, asegurarlo como activo
UPDATE periodos_academicos
SET    estado      = 'activo',
       nombre      = 'Período 2025-2026',
       descripcion = 'Pasantías Profesionales — Instituto de Salud Pública del Estado Bolívar'
WHERE  fecha_inicio = '2025-10-03'
  AND  fecha_fin    = '2026-06-01';

SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────
-- VERIFICACIÓN FINAL
-- ─────────────────────────────────────────────────────────────
SELECT '─── FERIADOS POR AÑO ───────────────────────────────────' AS info;

SELECT YEAR(fecha)     AS anio,
       COUNT(*)        AS total_feriados,
       SUM(tipo = 'Nacional')     AS nacionales,
       SUM(tipo = 'Regional')     AS regionales,
       SUM(tipo = 'Institucional')AS institucionales
FROM   dias_feriados
WHERE  YEAR(fecha) IN (2025, 2026)
GROUP  BY YEAR(fecha)
ORDER  BY anio;

SELECT '─── FERIADOS DEL PERÍODO DE PASANTÍAS (Oct-2025 → Jun-2026) ─' AS info;

SELECT fecha,
       DAYNAME(fecha) AS dia_semana,
       nombre,
       tipo
FROM   dias_feriados
WHERE  fecha BETWEEN '2025-10-03' AND '2026-06-01'
ORDER  BY fecha ASC;

SELECT '─── PERÍODO ACTIVO ──────────────────────────────────────' AS info;

SELECT id, nombre, fecha_inicio, fecha_fin, estado, descripcion
FROM   periodos_academicos
WHERE  estado = 'activo';

SELECT 'Script completado exitosamente.' AS resultado;
