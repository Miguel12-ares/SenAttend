-- Migración: Agregar campo email a aprendices y tabla codigos_qr
-- Fecha: 2025-01-XX
-- Descripción: Agrega campo email a aprendices y crea tabla para códigos QR con expiración

-- Eliminar campo codigo_carnet si existe (migración desde versión anterior)
-- Nota: Ejecuta manualmente si el campo existe, o ignora si no existe
-- ALTER TABLE aprendices DROP COLUMN IF EXISTS codigo_carnet;
-- ALTER TABLE aprendices DROP INDEX IF EXISTS idx_codigo_carnet;

-- Agregar campo email a la tabla aprendices (solo si no existe)
-- Verifica primero si el campo ya existe antes de ejecutar
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'aprendices' 
               AND COLUMN_NAME = 'email');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE aprendices ADD COLUMN email VARCHAR(100) UNIQUE AFTER apellido, ADD INDEX idx_email (email)', 
    'SELECT "Campo email ya existe, omitiendo creación" AS mensaje');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear tabla de códigos QR (si no existe)
CREATE TABLE IF NOT EXISTS codigos_qr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    id_aprendiz INT NOT NULL,
    qr_data TEXT NOT NULL,
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    fecha_uso TIMESTAMP NULL,
    FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_aprendiz (id_aprendiz),
    INDEX idx_expiracion (fecha_expiracion),
    INDEX idx_usado (usado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;