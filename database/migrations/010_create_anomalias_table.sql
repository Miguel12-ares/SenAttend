-- Migración: Crear tabla de anomalías de asistencia
-- Permite registrar anomalías por aprendiz o para la ficha en general
-- Fecha: 2025-11-30

CREATE TABLE IF NOT EXISTS anomalias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Referencias (id_asistencia e id_aprendiz pueden ser NULL si es anomalía general de ficha)
    id_asistencia INT NULL COMMENT 'ID de asistencia específica (NULL si es anomalía general de ficha)',
    id_aprendiz INT NULL COMMENT 'ID del aprendiz (NULL si es anomalía general de ficha)',
    id_ficha INT NOT NULL COMMENT 'ID de la ficha (siempre requerido)',
    
    -- Tipo de anomalía predefinida
    tipo_anomalia ENUM(
        'inasistencia_no_justificada',
        'inasistencia_justificada'
    ) NOT NULL COMMENT 'Tipo de anomalía predefinida',
    
    -- Información adicional
    descripcion TEXT NULL COMMENT 'Descripción adicional de la anomalía',
    
    -- Auditoría
    registrado_por INT NOT NULL COMMENT 'Usuario que registra la anomalía',
    fecha_asistencia DATE NOT NULL COMMENT 'Fecha de la asistencia relacionada',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del registro',
    
    -- Información de auditoría completa
    ip_address VARCHAR(45) NULL COMMENT 'Dirección IP del usuario que registra',
    user_agent TEXT NULL COMMENT 'User agent del navegador',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    CONSTRAINT fk_anomalias_asistencia
        FOREIGN KEY (id_asistencia) REFERENCES asistencias(id)
        ON DELETE CASCADE,
    
    CONSTRAINT fk_anomalias_aprendiz
        FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id)
        ON DELETE CASCADE,
    
    CONSTRAINT fk_anomalias_ficha
        FOREIGN KEY (id_ficha) REFERENCES fichas(id)
        ON DELETE CASCADE,
    
    CONSTRAINT fk_anomalias_registrado_por
        FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
        ON DELETE RESTRICT,
    
    -- Índices para optimización
    INDEX idx_anomalias_asistencia (id_asistencia),
    INDEX idx_anomalias_aprendiz (id_aprendiz),
    INDEX idx_anomalias_ficha (id_ficha),
    INDEX idx_anomalias_fecha_asistencia (fecha_asistencia),
    INDEX idx_anomalias_tipo (tipo_anomalia),
    INDEX idx_anomalias_registrado_por (registrado_por),
    INDEX idx_anomalias_fecha_registro (fecha_registro)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Anomalías registradas en asistencias (por aprendiz o ficha general). Permite registro hasta 3 días después de la asistencia.';

-- Nota: La validación de que al menos uno de id_asistencia o id_aprendiz debe estar presente
-- se maneja a nivel de aplicación, no con CHECK constraint (compatibilidad con MySQL < 8.0.16)

