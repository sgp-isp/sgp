-- ============================================================
-- Migración: Módulo Actividades Extras
-- Descripción: Grupos/brigadas de servicio comunitario,
--              pasantías cortas y mantenimiento de instituciones
--              universitarias externas.
-- Fecha: 2026-04-14
-- ============================================================

-- Instituciones externas (universidades, institutos)
CREATE TABLE IF NOT EXISTS `instituciones_externas` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`     VARCHAR(150) NOT NULL,
    `tipo`       ENUM('Universidad','Instituto','Colegio Técnico','Otro') DEFAULT 'Universidad',
    `contacto`   VARCHAR(150) NULL,
    `telefono`   VARCHAR(20) NULL,
    `activo`     TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Actividades / Brigadas
CREATE TABLE IF NOT EXISTS `actividades_extras` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`          VARCHAR(150) NOT NULL,
    `tipo`            ENUM('Servicio Comunitario','Pasantía Corta','Mantenimiento','Otro') DEFAULT 'Servicio Comunitario',
    `institucion_id`  INT UNSIGNED NULL,
    `supervisor_id`   INT UNSIGNED NULL COMMENT 'usuario_id del tutor/supervisor',
    `descripcion`     TEXT NULL,
    `fecha_inicio`    DATE NOT NULL,
    `fecha_fin`       DATE NULL,
    `estado`          ENUM('Activa','Finalizada','Cancelada') DEFAULT 'Activa',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`institucion_id`) REFERENCES `instituciones_externas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Participantes de cada actividad
CREATE TABLE IF NOT EXISTS `actividad_participantes` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actividad_id`   INT UNSIGNED NOT NULL,
    `nombres`        VARCHAR(100) NOT NULL,
    `apellidos`      VARCHAR(100) NOT NULL,
    `cedula`         VARCHAR(15) NOT NULL,
    `carrera`        VARCHAR(100) NULL,
    `telefono`       VARCHAR(20) NULL,
    `observaciones`  TEXT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`actividad_id`) REFERENCES `actividades_extras`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asistencia por actividad (registro manual)
CREATE TABLE IF NOT EXISTS `actividad_asistencias` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actividad_id`    INT UNSIGNED NOT NULL,
    `participante_id` INT UNSIGNED NOT NULL,
    `fecha`           DATE NOT NULL,
    `estado`          ENUM('Presente','Ausente','Justificado') DEFAULT 'Presente',
    `notas`           VARCHAR(255) NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_asistencia` (`actividad_id`, `participante_id`, `fecha`),
    FOREIGN KEY (`actividad_id`)    REFERENCES `actividades_extras`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`participante_id`) REFERENCES `actividad_participantes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
