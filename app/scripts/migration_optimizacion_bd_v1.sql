-- =============================================================================
-- SGP - MIGRACION DE OPTIMIZACION BD v1.0
-- Instituto de Salud Publica del Estado Bolivar
-- Fecha: 2026-05-01
-- =============================================================================
-- ORDEN DE EJECUCION:
--   S1  Unificar instituciones + instituciones_externas
--   S2  TRIGGERs de consistencia tutor/departamento
--   S3  Migracion datos_pasante.institucion_id
--   S4  VIEWs arquitecturales
--   S5  Vista de horas (fuente unica de verdad)
--   S6  Limpiar ENUM asistencias.estado
--   S7  Deduplicar asistencias + UNIQUE constraint
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- =============================================================================
-- S1  UNIFICACION: instituciones_externas -> instituciones
--
-- Objetivo: una sola tabla con discriminador categoria:
--   'origen'    = liceos tecnicos de donde vienen pasantes regulares (9 meses)
--   'actividad' = universidades para actividades_extras y pasantes cortos
-- =============================================================================

-- 1.1 Agregar columnas nuevas a instituciones
--     NOTA: COMMENT debe ir ANTES de AFTER en MySQL.
--     NO se modifica la columna tipo para preservar datos existentes.
ALTER TABLE `instituciones`
    ADD COLUMN `categoria`
        ENUM('origen','actividad') NOT NULL DEFAULT 'origen'
        COMMENT 'origen=liceos pasantes regulares | actividad=universidades/cortos'
        AFTER `tipo`,
    ADD COLUMN `contacto`
        VARCHAR(150) DEFAULT NULL
        COMMENT 'Persona de contacto en la institucion'
        AFTER `categoria`,
    ADD COLUMN `telefono`
        VARCHAR(20) DEFAULT NULL
        COMMENT 'Telefono de la institucion'
        AFTER `contacto`,
    ADD COLUMN `activo`
        TINYINT(1) NOT NULL DEFAULT 1
        AFTER `telefono`;

-- 1.2 Columna temporal de mapeo (se elimina al final de S1)
ALTER TABLE `instituciones`
    ADD COLUMN `_ext_id` INT UNSIGNED DEFAULT NULL;

-- 1.3 Migrar registros de instituciones_externas -> instituciones
INSERT INTO `instituciones`
    (`nombre`, `contacto`, `telefono`, `activo`, `categoria`, `_ext_id`)
SELECT
    `nombre`,
    COALESCE(`contacto`, NULL),
    COALESCE(`telefono`, NULL),
    COALESCE(`activo`, 1),
    'actividad',
    `id`
FROM `instituciones_externas`;

-- 1.4 Redirigir actividades_extras.institucion_id a los nuevos IDs
UPDATE `actividades_extras` ae
JOIN   `instituciones` i
    ON i.`_ext_id` = ae.`institucion_id`
   AND i.`categoria` = 'actividad'
SET ae.`institucion_id` = i.`id`;

-- 1.5 Redirigir datos_pasante.institucion_id para pasantes CORTOS
UPDATE `datos_pasante` dp
JOIN   `instituciones` i
    ON i.`_ext_id` = dp.`institucion_id`
   AND i.`categoria` = 'actividad'
SET dp.`institucion_id` = i.`id`
WHERE dp.`tipo_pasantia` = 'Corta';

-- 1.6 Eliminar columna temporal
ALTER TABLE `instituciones` DROP COLUMN `_ext_id`;

-- 1.7 Eliminar tabla antigua y crear FK en actividades_extras
DROP TABLE IF EXISTS `instituciones_externas`;

ALTER TABLE `actividades_extras`
    ADD CONSTRAINT `fk_actividades_inst_unif`
    FOREIGN KEY (`institucion_id`) REFERENCES `instituciones`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Verificacion S1
SELECT 'S1 completado: distribucion por categoria' AS verificacion;
SELECT categoria, COUNT(*) AS total FROM instituciones GROUP BY categoria;


-- =============================================================================
-- S2  TRIGGERS DE CONSISTENCIA
--
-- Sincroniza datos_pasante.tutor_id y departamento_asignado_id
-- automaticamente cuando se inserta o actualiza una asignacion activa.
-- =============================================================================

DROP TRIGGER IF EXISTS `trg_sync_dp_on_asign_insert`;
DROP TRIGGER IF EXISTS `trg_sync_dp_on_asign_update`;

DELIMITER $$

CREATE TRIGGER `trg_sync_dp_on_asign_insert`
AFTER INSERT ON `asignaciones`
FOR EACH ROW
BEGIN
    IF NEW.estado = 'activo' THEN
        UPDATE `datos_pasante`
        SET
            `tutor_id`                 = NEW.tutor_id,
            `departamento_asignado_id` = NEW.departamento_id,
            `fecha_inicio_pasantia`    = COALESCE(`fecha_inicio_pasantia`, NEW.fecha_inicio),
            `fecha_fin_estimada`       = COALESCE(`fecha_fin_estimada`,    NEW.fecha_fin)
        WHERE `usuario_id` = NEW.pasante_id;
    END IF;
END$$

CREATE TRIGGER `trg_sync_dp_on_asign_update`
AFTER UPDATE ON `asignaciones`
FOR EACH ROW
BEGIN
    IF NEW.estado = 'activo' THEN
        UPDATE `datos_pasante`
        SET
            `tutor_id`                 = NEW.tutor_id,
            `departamento_asignado_id` = NEW.departamento_id
        WHERE `usuario_id` = NEW.pasante_id;
    END IF;
END$$

DELIMITER ;

-- Verificacion S2
SELECT 'S2 completado: triggers en asignaciones' AS verificacion;
SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND EVENT_OBJECT_TABLE = 'asignaciones';


-- =============================================================================
-- S3  MIGRACION datos_pasante.institucion_id
--
-- institucion_procedencia contiene a veces un ID numerico y a veces texto.
-- Aqui se llena institucion_id desde los valores numericos usando la FK correcta.
-- =============================================================================

UPDATE `datos_pasante` dp
INNER JOIN `instituciones` i
    ON i.`id` = CAST(dp.`institucion_procedencia` AS UNSIGNED)
   AND i.`categoria` = 'origen'
SET dp.`institucion_id` = i.`id`
WHERE dp.`institucion_procedencia` REGEXP '^[0-9]+$'
  AND (dp.`institucion_id` IS NULL OR dp.`institucion_id` = 0);

-- Verificacion S3
SELECT 'S3 completado: estado institucion_id en datos_pasante' AS verificacion;
SELECT
    tipo_pasantia,
    COUNT(*) AS total,
    SUM(institucion_id IS NOT NULL) AS con_fk,
    SUM(institucion_id IS NULL) AS sin_fk
FROM datos_pasante
GROUP BY tipo_pasantia;


-- =============================================================================
-- S4  VISTAS ARQUITECTURALES
-- =============================================================================

CREATE OR REPLACE VIEW `v_usuarios_base` AS
SELECT
    u.`id`, u.`cedula`, u.`correo`, u.`rol_id`,
    u.`departamento_id`, u.`avatar`, u.`requiere_cambio_clave`,
    u.`estado`, u.`created_at`, u.`updated_at`,
    COALESCE(dp.`nombres`,   '') AS `nombres`,
    COALESCE(dp.`apellidos`, '') AS `apellidos`,
    dp.`cargo`, dp.`telefono`, dp.`direccion`,
    dp.`genero`, dp.`fecha_nacimiento`,
    CASE WHEN u.`rol_id` = 0 THEN 'Super Administrador'
         ELSE r.`nombre`
    END AS `rol_nombre`
FROM `usuarios` u
LEFT JOIN `datos_personales` dp ON u.`id` = dp.`usuario_id`
LEFT JOIN `roles`             r  ON u.`rol_id` = r.`id`;

CREATE OR REPLACE VIEW `v_dept_efectivo` AS
SELECT
    u.`id` AS `usuario_id`,
    COALESCE(dpa.`departamento_asignado_id`, u.`departamento_id`) AS `dept_id`,
    COALESCE(d_pa.`nombre`, d_u.`nombre`, 'Sin asignar')          AS `dept_nombre`
FROM `usuarios` u
LEFT JOIN `datos_pasante`  dpa  ON u.`id`    = dpa.`usuario_id`
LEFT JOIN `departamentos`  d_pa ON d_pa.`id` = dpa.`departamento_asignado_id`
LEFT JOIN `departamentos`  d_u  ON d_u.`id`  = u.`departamento_id`;

CREATE OR REPLACE VIEW `v_pasante_completo` AS
SELECT
    u.`id`, u.`cedula`, u.`correo`, u.`estado`, u.`avatar`,
    COALESCE(dp.`nombres`,   '') AS `nombres`,
    COALESCE(dp.`apellidos`, '') AS `apellidos`,
    dp.`telefono`, dp.`genero`, dp.`fecha_nacimiento`,
    dpa.`estado_pasantia`, dpa.`tipo_pasantia`,
    dpa.`horas_acumuladas`, dpa.`horas_meta`,
    dpa.`fecha_inicio_pasantia`, dpa.`fecha_fin_estimada`, dpa.`observaciones`,
    dpa.`institucion_id`, dpa.`institucion_procedencia`,
    COALESCE(inst.`nombre`, dpa.`institucion_procedencia`) AS `institucion_nombre`,
    inst.`representante_nombre` AS `inst_rep_nombre`,
    inst.`representante_cargo`  AS `inst_rep_cargo`,
    inst.`representante_correo` AS `inst_rep_correo`,
    inst.`representante_telefono` AS `inst_rep_telefono`,
    dpa.`departamento_asignado_id`,
    dept.`nombre` AS `departamento_nombre`,
    dpa.`periodo_id`,
    per.`nombre`       AS `periodo_nombre`,
    per.`fecha_inicio` AS `periodo_inicio`,
    per.`fecha_fin`    AS `periodo_fin`,
    asg.`id` AS `asignacion_id`,
    asg.`hora_entrada`, asg.`hora_salida`,
    asg.`horas_totales` AS `asig_horas_totales`,
    COALESCE(asg.`tutor_id`, dpa.`tutor_id`) AS `tutor_id`,
    CONCAT(COALESCE(dp_t.`nombres`, ''), ' ', COALESCE(dp_t.`apellidos`, '')) AS `tutor_nombre`
FROM `usuarios` u
LEFT JOIN `datos_personales`    dp   ON u.`id`           = dp.`usuario_id`
LEFT JOIN `datos_pasante`       dpa  ON u.`id`           = dpa.`usuario_id`
LEFT JOIN `instituciones`       inst ON inst.`id`         = dpa.`institucion_id`
LEFT JOIN `departamentos`       dept ON dept.`id`         = dpa.`departamento_asignado_id`
LEFT JOIN `periodos_academicos` per  ON per.`id`          = dpa.`periodo_id`
LEFT JOIN `asignaciones`        asg  ON asg.`pasante_id` = u.`id` AND asg.`estado` = 'activo'
LEFT JOIN `usuarios`            u_t  ON u_t.`id`          = COALESCE(asg.`tutor_id`, dpa.`tutor_id`)
LEFT JOIN `datos_personales`    dp_t ON dp_t.`usuario_id` = u_t.`id`
WHERE u.`rol_id` = 3;


-- =============================================================================
-- S5  VISTA HORAS (solo cuenta fechas <= hoy para excluir dias futuros)
-- =============================================================================

CREATE OR REPLACE VIEW `v_horas_pasante` AS
SELECT
    a.`pasante_id` AS `usuario_id`,
    COUNT(CASE WHEN a.`fecha` <= CURDATE() THEN 1 END) AS `dias_evaluados`,
    SUM(CASE WHEN a.`estado` = 'Presente'   AND a.`fecha` <= CURDATE()
             THEN COALESCE(a.`horas_calculadas`, 8) ELSE 0 END) AS `horas_presentes`,
    SUM(CASE WHEN a.`estado` = 'Justificado' AND a.`fecha` <= CURDATE()
             THEN COALESCE(a.`horas_calculadas`, 8) ELSE 0 END) AS `horas_justificadas`,
    SUM(CASE WHEN a.`estado` IN ('Presente','Justificado') AND a.`fecha` <= CURDATE()
             THEN COALESCE(a.`horas_calculadas`, 8) ELSE 0 END) AS `horas_acum_real`,
    COUNT(CASE WHEN a.`estado` = 'Ausente' AND a.`fecha` <= CURDATE() THEN 1 END) AS `dias_ausentes`,
    dp.`horas_meta`,
    ROUND(
        SUM(CASE WHEN a.`estado` IN ('Presente','Justificado') AND a.`fecha` <= CURDATE()
                 THEN COALESCE(a.`horas_calculadas`, 8) ELSE 0 END)
        / NULLIF(dp.`horas_meta`, 0) * 100
    , 1) AS `porcentaje_real`
FROM `asistencias` a
JOIN `datos_pasante` dp ON dp.`usuario_id` = a.`pasante_id`
GROUP BY a.`pasante_id`, dp.`horas_meta`;

-- Verificacion S4 y S5
SELECT 'S4/S5: Vistas creadas' AS verificacion;
SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('v_usuarios_base','v_dept_efectivo','v_pasante_completo','v_horas_pasante');


-- =============================================================================
-- S6  LIMPIAR ENUM asistencias.estado
-- =============================================================================

UPDATE `asistencias`
SET `estado` = 'Presente'
WHERE `estado` IN ('abierto', 'cerrado');

ALTER TABLE `asistencias`
    MODIFY COLUMN `estado`
        ENUM('Presente','Justificado','Ausente')
        NOT NULL DEFAULT 'Presente';

SELECT 'S6 completado: estados en asistencias' AS verificacion;
SELECT `estado`, COUNT(*) AS total FROM `asistencias` GROUP BY `estado`;


-- =============================================================================
-- S7  DEDUPLICAR ASISTENCIAS + UNIQUE CONSTRAINT
-- =============================================================================

-- Diagnostico previo (deben ser 0 duplicados)
SELECT 'S7: Duplicados antes de limpiar' AS verificacion;
SELECT pasante_id, fecha, COUNT(*) AS duplicados
FROM asistencias
GROUP BY pasante_id, fecha
HAVING COUNT(*) > 1
LIMIT 20;

-- Eliminar duplicados conservando el ID mas alto
DELETE a1
FROM `asistencias` a1
INNER JOIN `asistencias` a2
    ON a1.`pasante_id` = a2.`pasante_id`
   AND a1.`fecha`      = a2.`fecha`
   AND a1.`id`         < a2.`id`;

-- Convertir indice en UNIQUE
ALTER TABLE `asistencias`
    DROP INDEX `idx_pasante_fecha`,
    ADD  UNIQUE KEY `uk_asistencia_diaria` (`pasante_id`, `fecha`);

SELECT 'S7 completado: indice uk_asistencia_diaria' AS verificacion;
SHOW INDEX FROM `asistencias` WHERE Key_name = 'uk_asistencia_diaria';


-- =============================================================================
-- VERIFICACION FINAL
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'VERIFICACION GLOBAL' AS resultado;

SELECT tabla, filas FROM (
    SELECT 'instituciones' AS tabla, COUNT(*) AS filas FROM instituciones
    UNION ALL
    SELECT 'datos_pasante',               COUNT(*) FROM datos_pasante
    UNION ALL
    SELECT 'asistencias',                 COUNT(*) FROM asistencias
    UNION ALL
    SELECT 'asignaciones',                COUNT(*) FROM asignaciones
) resumen;

SELECT 'Triggers activos' AS seccion;
SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING
FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE();

SELECT 'Vistas disponibles' AS seccion;
SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE();

SELECT 'Migracion completada correctamente.' AS resultado;
-- =============================================================================
