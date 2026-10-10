-- ============================================================
-- SCRIPT: Asignar PIN temporal a pasantes sin PIN configurado
-- Ejecutar desde phpMyAdmin en la BD del proyecto_sgp
-- 
-- PIN temporal asignado: 1234 (hash bcrypt)
-- ⚠️ IMPORTANTE: Cada pasante debe cambiar su PIN después de
--    su primer acceso, o el Admin debe asignarle uno desde la
--    vista de Configuración > Resetear PIN
-- ============================================================

-- Ver cuáles pasantes NO tienen PIN:
SELECT 
    u.id,
    u.cedula,
    u.estado,
    COALESCE(dp.nombres, u.correo) AS nombre,
    COALESCE(dp.apellidos, '') AS apellidos,
    COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
    CASE WHEN u.pin_asistencia IS NULL OR u.pin_asistencia = '' THEN '❌ SIN PIN' ELSE '✅ Tiene PIN' END AS tiene_pin
FROM usuarios u
LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
WHERE u.rol_id = 3
ORDER BY tiene_pin ASC, u.id DESC;

-- ============================================================
-- Para asignar el PIN "1234" (hash bcrypt) a todos los 
-- pasantes que actualmente NO tienen PIN:
-- Hash bcrypt de "1234": $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================
UPDATE usuarios 
SET pin_asistencia = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE rol_id = 3
  AND (pin_asistencia IS NULL OR pin_asistencia = '');

-- Verificar cuántos se actualizaron:
SELECT ROW_COUNT() AS pasantes_actualizados;

-- ============================================================
-- VERIFICACIÓN FINAL: Todos los pasantes activos con Kiosco listo
-- ============================================================
SELECT 
    u.id,
    u.cedula,
    COALESCE(dp.nombres, u.correo) AS nombre,
    COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
    d.nombre AS departamento,
    CASE WHEN u.pin_asistencia IS NOT NULL THEN '✅ PIN OK' ELSE '❌ SIN PIN' END AS pin_status,
    CASE WHEN dpa.estado_pasantia = 'Activo' THEN '✅ Activo' ELSE '⚠️ ' || COALESCE(dpa.estado_pasantia, 'Sin asignar') END AS estado_pasantia_kiosco
FROM usuarios u
LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
LEFT JOIN departamentos d ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
WHERE u.rol_id = 3 AND u.estado = 'activo'
ORDER BY u.id;
