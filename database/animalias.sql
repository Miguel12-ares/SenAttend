-- Tabla de anomalías para excusas y correcciones de asistencia
-- Dev: Módulo de Estadísticas - Tabla Anomalias

CREATE TABLE IF NOT EXISTS anomalias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_asistencia INT NOT NULL,
    tipo ENUM('excusa','correccion','observacion') NOT NULL DEFAULT 'excusa',
    motivo TEXT NOT NULL,
    documento_soporte VARCHAR(255) DEFAULT NULL,
    registrado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_asistencia) REFERENCES asistencias(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,

    INDEX idx_anomalias_id_asistencia (id_asistencia),
    INDEX idx_anomalias_tipo (tipo),
    INDEX idx_anomalias_registrado_por (registrado_por),
    INDEX idx_anomalias_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentarios sobre la tabla anomalías
-- Esta tabla almacena excusas, correcciones y observaciones relacionadas con registros de asistencia
-- Permite justificar ausencias y documentar cambios en los estados de asistencia
