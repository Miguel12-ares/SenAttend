-- Migración: crear tabla historial_exportaciones
-- Registra exportaciones de reportes de asistencia

CREATE TABLE IF NOT EXISTS historial_exportaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    ficha_id INT NOT NULL,
    fecha_reporte DATE NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    total_aprendices INT NOT NULL DEFAULT 0,
    presentes INT NOT NULL DEFAULT 0,
    ausentes INT NOT NULL DEFAULT 0,
    tardanzas INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historial_instructor (instructor_id),
    INDEX idx_historial_ficha (ficha_id),
    INDEX idx_historial_fecha (fecha_reporte),
    CONSTRAINT fk_historial_instructor FOREIGN KEY (instructor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_historial_ficha FOREIGN KEY (ficha_id) REFERENCES fichas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


