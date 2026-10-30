-- ============================================================
-- MIGRATION: Módulo Portal Pasante + Defaults permisos rol 3
-- SGP | Ejecutar UNA SOLA VEZ en phpMyAdmin
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- Módulo del Portal del Pasante (rol_base=3)
INSERT IGNORE INTO `modulos_sistema` (`clave`, `nombre`, `icono`, `ruta_base`, `grupo`, `rol_base`, `activo`, `orden`)
VALUES ('portal_pasante', 'Portal del Pasante', 'ti-user-circle', '/pasante', 'Pasantías', 3, 1, 15);

-- Acciones del módulo (ver perfil y descargar constancia)
INSERT IGNORE INTO `acciones_modulo` (`modulo_id`, `clave`, `nombre`, `tipo`)
SELECT id, 'ver_perfil_pasante', 'Ver Mi Perfil', 'ver'
FROM `modulos_sistema` WHERE `clave` = 'portal_pasante';

INSERT IGNORE INTO `acciones_modulo` (`modulo_id`, `clave`, `nombre`, `tipo`)
SELECT id, 'descargar_constancia', 'Descargar Constancia', 'exportar'
FROM `modulos_sistema` WHERE `clave` = 'portal_pasante';

-- Defaults permisos_rol para Pasante (rol_id=3): ambas acciones habilitadas
INSERT IGNORE INTO `permisos_rol` (`rol_id`, `accion_id`, `habilitado`)
SELECT 3, id, 1 FROM `acciones_modulo`
WHERE `clave` IN ('ver_perfil_pasante', 'descargar_constancia');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- Verificación:
SELECT ms.nombre AS modulo, am.clave AS accion, pr.habilitado
FROM permisos_rol pr
JOIN acciones_modulo am ON pr.accion_id = am.id
JOIN modulos_sistema ms ON am.modulo_id = ms.id
WHERE pr.rol_id = 3;
