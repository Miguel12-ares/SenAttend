-- Tabla de auditoría para cambios en asistencias
-- Dev 2: AsistenciaService - Lógica de cambios auditados

CREATE TABLE IF NOT EXISTS cambios_asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_asistencia INT NOT NULL,
    estado_anterior ENUM('presente', 'ausente', 'tardanza') NOT NULL,
    estado_nuevo ENUM('presente', 'ausente', 'tardanza') NOT NULL,
    motivo_cambio TEXT,
    modificado_por INT NOT NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    observaciones_adicionales TEXT,
    
    FOREIGN KEY (id_asistencia) REFERENCES asistencias(id) ON DELETE CASCADE,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    
    INDEX idx_asistencia_fecha (id_asistencia, fecha_cambio),
    INDEX idx_modificado_por_fecha (modificado_por, fecha_cambio),
    INDEX idx_fecha_cambio (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentarios sobre la tabla de auditoría
-- Esta tabla registra todos los cambios realizados en los estados de asistencia
-- Permite trazabilidad completa de modificaciones para cumplimiento y auditoría
-- Incluye información del usuario, timestamp, IP y motivo del cambio
