-- ══════════════════════════════════════════════════════════════════════════
-- SGP — SCRIPT DE DEPURACIÓN PARA PRODUCCIÓN v2.0
-- Instituto de Salud Pública del Estado Bolívar
-- ══════════════════════════════════════════════════════════════════════════
-- Versión : 2.0  |  Fecha: 2026-04-30
-- CORRECCIÓN: Usa DELETE FROM en lugar de TRUNCATE para las tablas
--             relacionadas por FK, compatible con phpMyAdmin aunque tenga
--             activa la opción "Habilite revisión de claves foráneas".
--
-- INSTRUCCIONES:
--   1. phpMyAdmin → proyecto_sgp → pestaña SQL
--   2. Pegar este script completo → clic en "Continuar"
--   ► NO es necesario desmarcar ninguna casilla de FK.
-- ══════════════════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 1: Actividades de pasantías cortas
--   Orden correcto: primero los hijos, luego los padres
-- ─────────────────────────────────────────────────────────────────────────
-- actividad_asistencias referencia actividades_extras Y actividad_participantes
DELETE FROM `actividad_asistencias`;
-- actividad_evidencias referencia actividades_extras
DELETE FROM `actividad_evidencias`;
-- actividad_participantes referencia actividades_extras
DELETE FROM `actividad_participantes`;
-- ahora sí se puede vaciar el padre
DELETE FROM `actividades_extras`;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 2: Asistencias y asignaciones
--   asistencias referencia asignaciones y usuarios → borrar primero
-- ─────────────────────────────────────────────────────────────────────────
DELETE FROM `asistencias`;
DELETE FROM `asignaciones`;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 3: Evaluaciones (referencia usuarios)
-- ─────────────────────────────────────────────────────────────────────────
DELETE FROM `evaluaciones`;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 4: Notificaciones (referencia usuarios)
-- ─────────────────────────────────────────────────────────────────────────
DELETE FROM `notificaciones`;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 5: Bitácora completa
-- ─────────────────────────────────────────────────────────────────────────
DELETE FROM `bitacora_historico`;
DELETE FROM `bitacora`;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 6: Intentos de acceso
-- ─────────────────────────────────────────────────────────────────────────
DELETE FROM `intentos_acceso`;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 7: Datos de pasante
-- ─────────────────────────────────────────────────────────────────────────
DELETE FROM `datos_pasante`;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 8: Respuestas de seguridad SOLO de pasantes
-- ─────────────────────────────────────────────────────────────────────────
DELETE ur FROM `usuarios_respuestas` ur
INNER JOIN `usuarios` u ON u.id = ur.usuario_id
WHERE u.rol_id = 3;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 9: Permisos custom SOLO de pasantes
-- ─────────────────────────────────────────────────────────────────────────
DELETE up FROM `usuario_permisos` up
INNER JOIN `usuarios` u ON u.id = up.usuario_id
WHERE u.rol_id = 3;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 10: Datos personales SOLO de pasantes
-- ─────────────────────────────────────────────────────────────────────────
DELETE dp FROM `datos_personales` dp
INNER JOIN `usuarios` u ON u.id = dp.usuario_id
WHERE u.rol_id = 3;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 11: Eliminar usuarios pasantes (rol_id = 3)
-- ─────────────────────────────────────────────────────────────────────────
DELETE FROM `usuarios` WHERE `rol_id` = 3;

-- ─────────────────────────────────────────────────────────────────────────
-- FASE 12: Resetear AUTO_INCREMENT
-- ─────────────────────────────────────────────────────────────────────────
ALTER TABLE `asistencias`             AUTO_INCREMENT = 1;
ALTER TABLE `asignaciones`            AUTO_INCREMENT = 1;
ALTER TABLE `evaluaciones`            AUTO_INCREMENT = 1;
ALTER TABLE `notificaciones`          AUTO_INCREMENT = 1;
ALTER TABLE `bitacora`                AUTO_INCREMENT = 1;
ALTER TABLE `bitacora_historico`      AUTO_INCREMENT = 1;
ALTER TABLE `datos_pasante`           AUTO_INCREMENT = 1;
ALTER TABLE `intentos_acceso`         AUTO_INCREMENT = 1;
ALTER TABLE `actividades_extras`      AUTO_INCREMENT = 1;
ALTER TABLE `actividad_asistencias`   AUTO_INCREMENT = 1;
ALTER TABLE `actividad_participantes` AUTO_INCREMENT = 1;
ALTER TABLE `actividad_evidencias`    AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ══════════════════════════════════════════════════════════════════════════
-- VERIFICACIÓN FINAL
-- ══════════════════════════════════════════════════════════════════════════
SELECT '══ VERIFICACIÓN DE DEPURACIÓN ════════════════════════════' AS info;

SELECT '── Usuarios restantes por rol ──────────────────────────' AS seccion;
SELECT
    r.nombre     AS rol,
    u.rol_id,
    COUNT(*)     AS total_usuarios,
    SUM(u.estado = 'activo')   AS activos,
    SUM(u.estado = 'inactivo') AS inactivos
FROM usuarios u
JOIN roles r ON r.id = u.rol_id
GROUP BY u.rol_id, r.nombre
ORDER BY u.rol_id;

SELECT '── Conteo de tablas limpiadas (deben ser 0) ─────────────' AS seccion;
SELECT 'asistencias'          AS tabla, COUNT(*) AS registros FROM asistencias
UNION ALL
SELECT 'asignaciones',                  COUNT(*) FROM asignaciones
UNION ALL
SELECT 'evaluaciones',                  COUNT(*) FROM evaluaciones
UNION ALL
SELECT 'notificaciones',                COUNT(*) FROM notificaciones
UNION ALL
SELECT 'bitacora',                      COUNT(*) FROM bitacora
UNION ALL
SELECT 'bitacora_historico',            COUNT(*) FROM bitacora_historico
UNION ALL
SELECT 'datos_pasante',                 COUNT(*) FROM datos_pasante
UNION ALL
SELECT 'actividades_extras',            COUNT(*) FROM actividades_extras
UNION ALL
SELECT 'actividad_asistencias',         COUNT(*) FROM actividad_asistencias
UNION ALL
SELECT 'actividad_participantes',       COUNT(*) FROM actividad_participantes
UNION ALL
SELECT 'intentos_acceso',              COUNT(*) FROM intentos_acceso;

SELECT '── Configuración conservada ─────────────────────────────' AS seccion;
SELECT 'departamentos'        AS tabla, COUNT(*) AS registros FROM departamentos
UNION ALL
SELECT 'instituciones',                 COUNT(*) FROM instituciones
UNION ALL
SELECT 'periodos_academicos',           COUNT(*) FROM periodos_academicos
UNION ALL
SELECT 'dias_feriados',                 COUNT(*) FROM dias_feriados
UNION ALL
SELECT 'roles',                         COUNT(*) FROM roles
UNION ALL
SELECT 'modulos_sistema',               COUNT(*) FROM modulos_sistema
UNION ALL
SELECT 'permisos_rol',                  COUNT(*) FROM permisos_rol;

SELECT '── Período activo ───────────────────────────────────────' AS seccion;
SELECT id, nombre, fecha_inicio, fecha_fin, estado
FROM periodos_academicos
WHERE estado = 'activo';

SELECT '✔ Depuración completada. La BD está lista para producción.' AS resultado;
-- ══════════════════════════════════════════════════════════════════════════
