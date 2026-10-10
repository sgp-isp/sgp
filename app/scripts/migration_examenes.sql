-- ============================================================
-- Migración: Módulo de Exámenes Rápidos (Quiz)
-- SGP — Sistema de Gestión de Pasantías
-- ============================================================

CREATE TABLE IF NOT EXISTS examenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    periodo_id INT NULL,
    creado_por INT NOT NULL,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    intentos_permitidos TINYINT NOT NULL DEFAULT 1,
    activo TINYINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS examen_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examen_id INT NOT NULL,
    orden TINYINT NOT NULL DEFAULT 1,
    enunciado TEXT NOT NULL,
    tipo ENUM('opcion_multiple','verdadero_falso') NOT NULL DEFAULT 'opcion_multiple',
    puntos TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY (examen_id) REFERENCES examenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS examen_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    texto VARCHAR(500) NOT NULL,
    es_correcta TINYINT NOT NULL DEFAULT 0,
    FOREIGN KEY (pregunta_id) REFERENCES examen_preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS examen_intentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examen_id INT NOT NULL,
    pasante_id INT NOT NULL,
    iniciado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enviado_at TIMESTAMP NULL,
    puntaje_obtenido DECIMAL(6,2) NULL,
    puntaje_maximo DECIMAL(6,2) NULL,
    porcentaje DECIMAL(5,2) NULL,
    UNIQUE KEY uk_intento (examen_id, pasante_id),
    FOREIGN KEY (examen_id) REFERENCES examenes(id) ON DELETE CASCADE,
    FOREIGN KEY (pasante_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS examen_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intento_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    opcion_id_elegida INT NULL,
    FOREIGN KEY (intento_id) REFERENCES examen_intentos(id) ON DELETE CASCADE,
    FOREIGN KEY (pregunta_id) REFERENCES examen_preguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (opcion_id_elegida) REFERENCES examen_opciones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
