-- ============================================================
-- Migración: Límite de intentos para el Kiosco de Asistencia
-- Descripción: Registra intentos fallidos de PIN (marcar) y
--              solicitudes de reseteo de PIN (reset_pin) por
--              IP + cédula, con bloqueo temporal tras superar
--              el máximo permitido.
-- Hallazgos: N-2, N-5 (auditoría AppSec SGP)
-- Fecha: 2026-07-10
-- ============================================================

CREATE TABLE IF NOT EXISTS `intentos_pin_kiosco` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `direccion_ip`    VARCHAR(45)  NOT NULL,
    `cedula`          VARCHAR(15)  NOT NULL,
    `accion`          ENUM('marcar','reset_pin') NOT NULL DEFAULT 'marcar',
    `intentos`        INT UNSIGNED NOT NULL DEFAULT 1,
    `ultimo_intento`  DATETIME     NOT NULL,
    `bloqueado_hasta` DATETIME     NULL,
    UNIQUE KEY `uk_intento_kiosco` (`direccion_ip`, `cedula`, `accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
