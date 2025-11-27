-- Migración: Configuración Dinámica de Turnos
-- Fecha: 2025-11-27
-- Descripción: Tabla para gestionar horarios de turnos de forma dinámica

-- Crear tabla de configuración de turnos
CREATE TABLE IF NOT EXISTS configuracion_turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_turno ENUM('Mañana', 'Tarde', 'Noche') NOT NULL UNIQUE,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    hora_limite_llegada TIME NOT NULL COMMENT 'Hora límite para marcar tardanza',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Permite desactivar turnos sin eliminarlos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para optimizar consultas
    INDEX idx_nombre_turno (nombre_turno),
    INDEX idx_activo (activo),
    INDEX idx_horarios (hora_inicio, hora_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuración dinámica de horarios de turnos para validación de asistencia';

-- Insertar datos semilla con los horarios por defecto
INSERT INTO configuracion_turnos (nombre_turno, hora_inicio, hora_fin, hora_limite_llegada) VALUES
('Mañana', '06:00:00', '12:00:00', '06:20:00'),
('Tarde', '12:00:00', '18:00:00', '12:25:00'),
('Noche', '18:00:00', '23:00:00', '18:20:00');
