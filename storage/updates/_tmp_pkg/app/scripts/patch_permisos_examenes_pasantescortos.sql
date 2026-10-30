-- ============================================================
-- PATCH: Módulo Exámenes + Acciones Pasantes Cortos en Permisos
-- SGP | Ejecutar en phpMyAdmin sobre la BD existente
-- ============================================================

-- 1. Módulo Exámenes
INSERT IGNORE INTO `modulos_sistema` (`clave`,`nombre`,`icono`,`ruta_base`,`grupo`,`rol_base`,`activo`,`orden`)
VALUES ('examenes','Exámenes','ti-pencil-question','/examenes','Académico',1,1,15);

-- 2. Acciones del módulo Exámenes
INSERT IGNORE INTO `acciones_modulo` (`modulo_id`,`clave`,`nombre`,`tipo`)
SELECT m.id,'ver_examenes','Ver Exámenes','ver'                       FROM modulos_sistema m WHERE m.clave='examenes'
UNION ALL
SELECT m.id,'crear_examen','Crear Examen','crear'                     FROM modulos_sistema m WHERE m.clave='examenes'
UNION ALL
SELECT m.id,'editar_examen','Editar / Publicar Examen','editar'       FROM modulos_sistema m WHERE m.clave='examenes'
UNION ALL
SELECT m.id,'eliminar_examen','Eliminar Examen / Intento','eliminar'  FROM modulos_sistema m WHERE m.clave='examenes';

-- 3. Nuevas acciones de Pasantes Cortos dentro del módulo Actividades Extras
INSERT IGNORE INTO `acciones_modulo` (`modulo_id`,`clave`,`nombre`,`tipo`)
SELECT m.id,'registrar_pasante_corto','Registrar Pasante Corto','crear'              FROM modulos_sistema m WHERE m.clave='actividades_extras'
UNION ALL
SELECT m.id,'ver_pasante_corto','Ver Almanaque Pasante Corto','ver'                  FROM modulos_sistema m WHERE m.clave='actividades_extras'
UNION ALL
SELECT m.id,'marcar_asistencia_corta','Marcar Asistencia Pasante Corto','editar'     FROM modulos_sistema m WHERE m.clave='actividades_extras';

-- 4. Defaults para Administrador (rol=1): todas las nuevas acciones habilitadas
INSERT IGNORE INTO `permisos_rol` (`rol_id`,`accion_id`,`habilitado`)
SELECT 1, a.id, 1
FROM acciones_modulo a
WHERE a.clave IN (
    'ver_examenes','crear_examen','editar_examen','eliminar_examen',
    'registrar_pasante_corto','ver_pasante_corto','marcar_asistencia_corta'
);

-- Verificación
SELECT m.clave AS modulo, a.clave AS accion, a.tipo,
       COALESCE(pr.habilitado, 0) AS rol1_habilitado
FROM acciones_modulo a
JOIN modulos_sistema m ON m.id = a.modulo_id
LEFT JOIN permisos_rol pr ON pr.accion_id = a.id AND pr.rol_id = 1
WHERE m.clave IN ('examenes','actividades_extras')
  AND a.clave IN (
      'ver_examenes','crear_examen','editar_examen','eliminar_examen',
      'ver_actividades','crear_actividad',
      'registrar_pasante_corto','ver_pasante_corto','marcar_asistencia_corta'
  )
ORDER BY m.orden, a.tipo;
