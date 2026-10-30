-- SGP ISPEB - Actualización Institucional: Respaldo Nube y Seguridad
INSERT INTO mail_notificaciones_reglas 
    (codigo_evento, nombre_evento, descripcion, activo, enviar_a_roles, created_at, updated_at)
VALUES 
    ('RESPALDO_NUBE', 'Respaldo Integral en la Nube', 'Envío periódico del archivo ZIP cifrado con la base de datos a Gmail', 1, '0', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    nombre_evento = VALUES(nombre_evento),
    descripcion = VALUES(descripcion);
