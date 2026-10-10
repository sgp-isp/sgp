-- =====================================================
-- Insertar Usuario Administrador Adicional
-- =====================================================
-- Este script crea un segundo usuario administrador
-- con todos los datos necesarios para pruebas
-- =====================================================

USE sgp_v1;

-- Insertar segundo administrador
-- Nombre: Luis Yáñez
-- Email: luis.yanez@sgp.local
-- Contraseña: admin123 (misma que el admin principal para facilitar)
INSERT INTO usuarios (
    rol_id, 
    departamento_id, 
    perfil_completado, 
    activo,
    correo, 
    password, 
    nombre_completo, 
    cedula, 
    telefono,
    institucion_procedencia
) VALUES (
    1, -- Administrador (rol_id = 1)
    NULL, -- Sin departamento específico
    1, -- Perfil completado
    1, -- Usuario activo
    'luis.yanez@sgp.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'Luis Yáñez',
    'V-12345678',
    '0414-1234567',
    'Instituto Universitario de Tecnología'
);

-- Verificar inserción
SELECT 
    id,
    nombre_completo,
    correo,
    rol_id,
    perfil_completado,
    activo,
    created_at
FROM usuarios
WHERE correo = 'luis.yanez@sgp.local';

-- =====================================================
-- Credenciales del nuevo administrador:
-- =====================================================
-- Email: luis.yanez@sgp.local
-- Contraseña: admin123
-- =====================================================
