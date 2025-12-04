-- Migración: Agregar campos de autenticación a aprendices
-- Fecha: 2025-12-01
-- Descripción: Habilita el inicio de sesión de aprendices mediante email
--              y contraseña (hash de los primeros 5 dígitos del documento).

-- IMPORTANTE:
-- La generación del password_hash se hará en la lógica de importación
-- (PHP), tomando los primeros 5 dígitos del documento y aplicando hash
-- (por ejemplo, password_hash de PHP). Esta migración solo crea la columna.

ALTER TABLE aprendices
ADD COLUMN password_hash VARCHAR(255) NULL AFTER email;

-- Opcionalmente se podrían agregar campos adicionales de auditoría de login,
-- pero por ahora mantenemos el diseño mínimo requerido para la autenticación:
--  - email (ya agregado en migración add_email_and_qr_table.sql)
--  - password_hash (definido aquí)


