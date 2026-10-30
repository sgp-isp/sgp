-- =====================================================================
-- SETUP DE PRODUCCIÓN: Período Académico 2025-2026
-- Ejecutar en orden. Verificar cada sección antes de continuar.
-- =====================================================================

-- ─────────────────────────────────────────────────────────────────────
-- PASO 1: Migración firmante de documentos
-- (Ejecutar si aún no se hizo migration_firmante_documentos.sql)
-- ─────────────────────────────────────────────────────────────────────
ALTER TABLE `datos_personales`
    ADD COLUMN IF NOT EXISTS `es_firmante` TINYINT(1) NOT NULL DEFAULT 0;

-- ─────────────────────────────────────────────────────────────────────
-- PASO 2: Marcar al administrador principal como firmante
-- Reemplaza el usuario_id=1 si el admin real tiene otro ID.
-- ─────────────────────────────────────────────────────────────────────
UPDATE datos_personales
SET    es_firmante = 1
WHERE  usuario_id = (
    SELECT id FROM usuarios
    WHERE  rol_id = 1 AND estado = 'activo'
    ORDER  BY id ASC
    LIMIT  1
);

-- ─────────────────────────────────────────────────────────────────────
-- PASO 3: Crear el período académico 2025-2026
-- ─────────────────────────────────────────────────────────────────────
INSERT INTO periodos_academicos (nombre, descripcion, fecha_inicio, fecha_fin, estado, created_at)
VALUES (
    'Período 2025-2026',
    'Pasantías Profesionales — Instituto de Salud Pública del Estado Bolívar',
    '2025-10-03',
    '2026-06-01',
    'Activo',
    NOW()
);

-- Guarda el ID generado para referenciarlo en pasos posteriores
SET @periodo_id = LAST_INSERT_ID();
SELECT @periodo_id AS 'ID del período creado';

-- ─────────────────────────────────────────────────────────────────────
-- PASO 4: Feriados nacionales faltantes (2025-2026)
-- Solo inserta los que no existen.
-- ─────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO dias_feriados (fecha, nombre, tipo, created_at) VALUES
-- Diciembre 2025
('2025-12-17', 'Muerte del Libertador Simón Bolívar',   'Nacional', NOW()),
('2025-12-24', 'Nochebuena',                             'Nacional', NOW()),
('2025-12-25', 'Navidad',                                'Nacional', NOW()),
('2025-12-31', 'Fin de Año',                             'Nacional', NOW()),
-- Enero 2026
('2026-01-01', 'Año Nuevo',                              'Nacional', NOW()),
-- Carnaval 2026 (lunes 2 y martes 3 de marzo)
('2026-03-02', 'Carnaval — Lunes',                       'Nacional', NOW()),
('2026-03-03', 'Carnaval — Martes',                      'Nacional', NOW()),
-- Semana Santa 2026 (jueves 2 y viernes 3 de abril)
('2026-04-02', 'Jueves Santo',                           'Nacional', NOW()),
('2026-04-03', 'Viernes Santo',                          'Nacional', NOW()),
-- Abril 2026
('2026-04-19', 'Declaración de Independencia de Venezuela', 'Nacional', NOW()),
-- Mayo 2026
('2026-05-01', 'Día Internacional del Trabajador',       'Nacional', NOW());

-- Verificar feriados insertados
SELECT fecha, nombre, tipo
FROM   dias_feriados
WHERE  fecha BETWEEN '2025-12-01' AND '2026-05-31'
ORDER  BY fecha ASC;

-- ─────────────────────────────────────────────────────────────────────
-- PASO 5: (Opcional) Relleno retroactivo de asistencias como Presente
--
-- Si los pasantes YA existen y han estado asistiendo desde el 3 oct 2025,
-- este bloque inserta un registro "Presente" para cada día hábil sin
-- registro, desde la fecha_inicio de cada pasante hasta AYER.
--
-- IMPORTANTE: Ejecutar SOLO si confirmas que no hay registros previos
-- que quieras conservar. Usa INSERT IGNORE para no sobreescribir nada.
-- ─────────────────────────────────────────────────────────────────────

/*  DESCOMENTA este bloque cuando estés listo para el relleno masivo:

-- Crear tabla temporal con todos los días hábiles en el rango del período
DROP TEMPORARY TABLE IF EXISTS tmp_dias_habiles;
CREATE TEMPORARY TABLE tmp_dias_habiles (fecha DATE);

SET @d = '2025-10-03';
WHILE @d < CURDATE() DO
    -- Solo Lunes a Viernes
    IF DAYOFWEEK(@d) NOT IN (1, 7) THEN
        -- Solo si no es feriado
        IF NOT EXISTS (SELECT 1 FROM dias_feriados WHERE fecha = @d) THEN
            INSERT INTO tmp_dias_habiles VALUES (@d);
        END IF;
    END IF;
    SET @d = DATE_ADD(@d, INTERVAL 1 DAY);
END WHILE;

-- Insertar Presente para cada pasante activo en cada día hábil sin registro
INSERT IGNORE INTO asistencias
    (pasante_id, fecha, estado, hora_entrada, metodo, es_auto_fill, created_at)
SELECT
    dpa.usuario_id,
    tmp.fecha,
    'Presente',
    '08:00:00',
    'Retroactivo',
    1,
    NOW()
FROM datos_pasante dpa
JOIN tmp_dias_habiles tmp ON tmp.fecha >= dpa.fecha_inicio_pasantia
JOIN usuarios u ON u.id = dpa.usuario_id AND u.rol_id = 3 AND u.estado = 'activo'
WHERE dpa.estado_pasantia = 'Activo'
  AND NOT EXISTS (
    SELECT 1 FROM asistencias a
    WHERE a.pasante_id = dpa.usuario_id AND a.fecha = tmp.fecha
  );

DROP TEMPORARY TABLE IF EXISTS tmp_dias_habiles;

SELECT COUNT(*) AS 'Registros retroactivos insertados' FROM asistencias WHERE metodo = 'Retroactivo';
*/

-- ─────────────────────────────────────────────────────────────────────
-- FIN DEL SCRIPT
-- ─────────────────────────────────────────────────────────────────────
