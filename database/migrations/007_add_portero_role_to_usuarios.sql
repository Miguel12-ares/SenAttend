-- Migración: Agregar rol 'portero' a la tabla usuarios
-- Fecha: 2025-12-01
-- Descripción: Extiende el ENUM de rol para soportar el nuevo rol de portero,
--              utilizado para la gestión de ingreso y salida de equipos.

ALTER TABLE usuarios
MODIFY COLUMN rol ENUM('instructor', 'admin', 'administrativo', 'portero')
    NOT NULL DEFAULT 'instructor';

-- Notas:
-- - La migración 004 ya había ajustado los roles a:
--     'instructor', 'admin', 'administrativo'
-- - Aquí simplemente se agrega el nuevo rol 'portero' manteniendo compatibilidad.


