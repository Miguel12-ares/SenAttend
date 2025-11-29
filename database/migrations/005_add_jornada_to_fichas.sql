-- Migración: Agregar columna jornada a la tabla fichas
-- Fecha: 2025-11-28
-- Descripción: Agrega el campo jornada para vincular fichas con turnos específicos

ALTER TABLE fichas
ADD COLUMN jornada ENUM('Mañana', 'Tarde', 'Noche', 'Mixta') NOT NULL DEFAULT 'Mañana' AFTER nombre;

-- Actualizar registros existentes (aunque el default ya lo hace, es bueno ser explícito si fuera necesario)
-- UPDATE fichas SET jornada = 'Mañana' WHERE jornada IS NULL;

-- Agregar índice para búsquedas por jornada
CREATE INDEX idx_jornada ON fichas(jornada);
