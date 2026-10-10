-- ══════════════════════════════════════════════════════════════════════════
-- Asignar departamento a Joel Acosta (Administrador)
-- Jefe del Departamento de Soporte Técnico
-- ══════════════════════════════════════════════════════════════════════════

-- Ver los departamentos disponibles primero:
SELECT id, nombre FROM departamentos ORDER BY id;

-- Asignar "Soporte Técnico" al admin (correo: admin@sgo.local)
-- Busca el ID del departamento por nombre y lo asigna automáticamente
UPDATE usuarios
SET departamento_id = (
    SELECT id FROM departamentos WHERE nombre LIKE '%Soporte%' LIMIT 1
)
WHERE correo = 'admin@sgp.local' AND rol_id = 1;

-- Verificar el resultado
SELECT u.id, u.correo, u.rol_id, d.nombre AS departamento
FROM usuarios u
LEFT JOIN departamentos d ON d.id = u.departamento_id
WHERE u.rol_id IN (1, 2)
ORDER BY u.rol_id;
