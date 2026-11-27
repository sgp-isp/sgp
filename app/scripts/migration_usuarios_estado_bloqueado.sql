-- Migración: agregar estado 'bloqueado' a la tabla usuarios
ALTER TABLE usuarios
    MODIFY COLUMN estado ENUM('activo', 'inactivo', 'bloqueado') DEFAULT 'activo';
