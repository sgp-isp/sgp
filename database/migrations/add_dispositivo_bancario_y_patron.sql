-- Migración: Soporte para Vinculación Bancaria de Dispositivos y Patrón de Desbloqueo Gestual
-- Sistema de Gestión de Pasantes (SGP) - ISPEB

-- 1. Agregar columnas para patrón de desbloqueo en la tabla usuarios
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS patron_hash VARCHAR(255) NULL AFTER password,
ADD COLUMN IF NOT EXISTS patron_activo TINYINT(1) DEFAULT 0 AFTER patron_hash,
ADD COLUMN IF NOT EXISTS patron_actualizado_at DATETIME NULL AFTER patron_activo;

-- 2. Agregar columnas para vinculación bancaria con OTP en pasante_dispositivos
ALTER TABLE pasante_dispositivos
ADD COLUMN IF NOT EXISTS metodo_auth ENUM('huella', 'patron', 'ambos', 'ninguno') DEFAULT 'huella' AFTER estado,
ADD COLUMN IF NOT EXISTS otp_codigo VARCHAR(10) NULL AFTER metodo_auth,
ADD COLUMN IF NOT EXISTS otp_expira_at DATETIME NULL AFTER otp_codigo,
ADD COLUMN IF NOT EXISTS otp_intentos INT DEFAULT 0 AFTER otp_expira_at,
ADD COLUMN IF NOT EXISTS verificado_at DATETIME NULL AFTER otp_intentos,
ADD COLUMN IF NOT EXISTS cedula_asociada VARCHAR(20) NULL AFTER verificado_at;

-- 3. Crear índice para búsqueda rápida por cédula y device_uuid
ALTER TABLE pasante_dispositivos
ADD INDEX IF NOT EXISTS idx_disp_cedula (cedula_asociada),
ADD INDEX IF NOT EXISTS idx_disp_verificado (usuario_id, estado, verificado_at);

-- 4. Registrar regla de correo para OTP de vinculación
INSERT IGNORE INTO mail_reglas (codigo_evento, titulo, descripcion, activo, enviar_a_roles, creado_at)
VALUES (
    'VINCULACION_DISPOSITIVO_OTP',
    'Código de Autorización para Nuevo Dispositivo',
    'Envía un código OTP de 6 dígitos al correo del usuario para validar la vinculación de su dispositivo frecuente.',
    1,
    '0,1,2,3',
    NOW()
);
