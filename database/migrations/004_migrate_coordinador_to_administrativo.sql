-- ============================================
-- Migración: Cambiar rol 'coordinador' a 'administrativo'
-- Fecha: 2025-11-28
-- Descripción: Reemplaza todas las referencias del rol 'coordinador' 
--              por 'administrativo' en la base de datos
-- ============================================

-- PASO 1: Modificar el ENUM de la tabla usuarios para incluir 'administrativo'
-- Primero agregamos 'administrativo' al ENUM
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM('instructor', 'coordinador', 'admin', 'administrativo') NOT NULL DEFAULT 'instructor';

-- PASO 2: Actualizar todos los usuarios con rol 'coordinador' a 'administrativo'
UPDATE usuarios 
SET rol = 'administrativo' 
WHERE rol = 'coordinador';

-- PASO 3: Remover 'coordinador' del ENUM (ahora que no hay registros con ese valor)
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM('instructor', 'admin', 'administrativo') NOT NULL DEFAULT 'instructor';

-- PASO 4: Verificar que la migración fue exitosa
-- Ejecutar esta consulta para confirmar:
-- SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol;

-- ============================================
-- RESULTADO ESPERADO:
-- - Todos los usuarios 'coordinador' ahora son 'administrativo'
-- - El ENUM solo contiene: 'instructor', 'admin', 'administrativo'
-- ============================================
