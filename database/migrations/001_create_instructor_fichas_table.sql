-- =============================================================================
-- MIGRACIÓN: Crear tabla instructor_fichas
-- Fecha: 2025-11-27
-- Descripción: Tabla intermedia para relación muchos a muchos entre instructores y fichas
-- =============================================================================

-- Crear tabla de relación instructor_fichas
CREATE TABLE IF NOT EXISTS instructor_fichas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    ficha_id INT NOT NULL,
    fecha_asignacion DATE DEFAULT (CURRENT_DATE),
    asignado_por INT NULL COMMENT 'Usuario que realizó la asignación',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Estado de la asignación',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clave única para evitar duplicados
    UNIQUE KEY unique_instructor_ficha (instructor_id, ficha_id),
    
    -- Claves foráneas
    CONSTRAINT fk_instructor_fichas_instructor 
        FOREIGN KEY (instructor_id) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_instructor_fichas_ficha 
        FOREIGN KEY (ficha_id) 
        REFERENCES fichas(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_instructor_fichas_asignado_por 
        FOREIGN KEY (asignado_por) 
        REFERENCES usuarios(id) 
        ON DELETE SET NULL,
    
    -- Índices para optimización de consultas
    INDEX idx_instructor_id (instructor_id),
    INDEX idx_ficha_id (ficha_id),
    INDEX idx_activo (activo),
    INDEX idx_fecha_asignacion (fecha_asignacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Relación muchos a muchos entre instructores y fichas';

-- =============================================================================
-- DATOS INICIALES DE PRUEBA (Opcional - Comentar en producción)
-- =============================================================================

-- Asignar algunas fichas a los instructores existentes para pruebas
-- Instructor 1 (ID: 2) - Asignar primeras 5 fichas
INSERT INTO instructor_fichas (instructor_id, ficha_id, asignado_por) 
SELECT 2, id, 1 FROM fichas WHERE estado = 'activa' LIMIT 5;

-- Instructor 2 (ID: 3) - Asignar siguientes 5 fichas
INSERT INTO instructor_fichas (instructor_id, ficha_id, asignado_por)
SELECT 3, id, 1 FROM fichas WHERE estado = 'activa' LIMIT 5,5;

-- =============================================================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- =============================================================================
/*
Esta tabla permite:
1. Asignar múltiples fichas a un instructor
2. Asignar una ficha a múltiples instructores
3. Rastrear quién y cuándo se hizo la asignación
4. Desactivar asignaciones sin eliminarlas (soft delete con campo 'activo')
5. Evitar duplicados con UNIQUE KEY

Casos de uso:
- Un instructor puede tener asignadas varias fichas
- Una ficha puede ser compartida por varios instructores
- Se mantiene historial de asignaciones
- Permite auditoría completa de cambios
*/
