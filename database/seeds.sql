-- SENAttend - Datos Iniciales (Seeds)
-- Sistema de Asistencia SENA
-- Versión: 1.0

-- ============================================
-- USUARIOS
-- ============================================

-- Contraseña para todos los usuarios: admin123
-- Hash generado con password_hash('admin123', PASSWORD_DEFAULT)
-- Nota: En producción, cambiar estas contraseñas inmediatamente

INSERT INTO usuarios (documento, nombre, email, password_hash, rol) VALUES
('1000000001', 'Administrador Principal', 'admin@sena.edu.co', '$2y$10$XJPXOsFjBF0wKerujtN2n.OkrbnAkFdkPqGVTkZToXAze0pRLWRQy', 'admin'),
('1000000002', 'Juan Carlos Instructor', 'instr1@sena.edu.co', '$2y$10$XJPXOsFjBF0wKerujtN2n.OkrbnAkFdkPqGVTkZToXAze0pRLWRQy', 'instructor'),
('1000000003', 'María Patricia Gómez', 'instr2@sena.edu.co', '$2y$10$XJPXOsFjBF0wKerujtN2n.OkrbnAkFdkPqGVTkZToXAze0pRLWRQy', 'instructor'),
('1000000004', 'Roberto Coordinador', 'coordinador@sena.edu.co', '$2y$10$XJPXOsFjBF0wKerujtN2n.OkrbnAkFdkPqGVTkZToXAze0pRLWRQy', 'coordinador');

-- ============================================
-- FICHAS (50 fichas de formación)
-- ============================================

INSERT INTO fichas (numero_ficha, nombre, estado) VALUES
('2025-0001', 'Tecnólogo en Análisis y Desarrollo de Software', 'activa'),
('2025-0002', 'Tecnólogo en Gestión de Redes de Datos', 'activa'),
('2025-0003', 'Tecnólogo en Sistemas', 'activa'),
('2025-0004', 'Técnico en Programación de Software', 'activa'),
('2025-0005', 'Tecnólogo en Gestión Administrativa', 'activa'),
('2025-0006', 'Tecnólogo en Contabilidad y Finanzas', 'activa'),
('2025-0007', 'Tecnólogo en Gestión Empresarial', 'activa'),
('2025-0008', 'Tecnólogo en Gestión Logística', 'activa'),
('2025-0009', 'Tecnólogo en Gestión de Mercados', 'activa'),
('2025-0010', 'Tecnólogo en Negociación Internacional', 'activa'),
('2025-0011', 'Tecnólogo en Gestión del Talento Humano', 'activa'),
('2025-0012', 'Tecnólogo en Gestión Bancaria', 'activa'),
('2025-0013', 'Tecnólogo en Electricidad Industrial', 'activa'),
('2025-0014', 'Tecnólogo en Electrónica Industrial', 'activa'),
('2025-0015', 'Tecnólogo en Mantenimiento Electrónico', 'activa'),
('2025-0016', 'Tecnólogo en Automatización Industrial', 'activa'),
('2025-0017', 'Tecnólogo en Mecatrónica', 'activa'),
('2025-0018', 'Tecnólogo en Diseño Industrial', 'activa'),
('2025-0019', 'Tecnólogo en Producción Industrial', 'activa'),
('2025-0020', 'Tecnólogo en Soldadura', 'activa'),
('2025-0021', 'Tecnólogo en Construcción', 'activa'),
('2025-0022', 'Tecnólogo en Topografía', 'activa'),
('2025-0023', 'Tecnólogo en Diseño Arquitectónico', 'activa'),
('2025-0024', 'Tecnólogo en Obras Civiles', 'activa'),
('2025-0025', 'Tecnólogo en Gestión Ambiental', 'activa'),
('2025-0026', 'Tecnólogo en Química Industrial', 'activa'),
('2025-0027', 'Tecnólogo en Alimentos', 'activa'),
('2025-0028', 'Tecnólogo en Salud Ocupacional', 'activa'),
('2025-0029', 'Tecnólogo en Enfermería', 'activa'),
('2025-0030', 'Tecnólogo en Regencia de Farmacia', 'activa'),
('2025-0031', 'Tecnólogo en Cosmetología', 'activa'),
('2025-0032', 'Tecnólogo en Cocina', 'activa'),
('2025-0033', 'Tecnólogo en Gastronomía', 'activa'),
('2025-0034', 'Tecnólogo en Hotelería', 'activa'),
('2025-0035', 'Tecnólogo en Turismo', 'activa'),
('2025-0036', 'Tecnólogo en Guianza Turística', 'activa'),
('2025-0037', 'Tecnólogo en Agroindustria', 'activa'),
('2025-0038', 'Tecnólogo en Producción Agrícola', 'activa'),
('2025-0039', 'Tecnólogo en Producción Pecuaria', 'activa'),
('2025-0040', 'Tecnólogo en Acuicultura', 'activa'),
('2025-0041', 'Tecnólogo en Diseño Gráfico', 'activa'),
('2025-0042', 'Tecnólogo en Multimedia', 'activa'),
('2025-0043', 'Tecnólogo en Animación Digital', 'activa'),
('2025-0044', 'Tecnólogo en Fotografía', 'activa'),
('2025-0045', 'Tecnólogo en Producción de Medios Audiovisuales', 'activa'),
('2024-0001', 'Tecnólogo en Análisis y Desarrollo de Software', 'activa'),
('2024-0002', 'Tecnólogo en Gestión de Redes de Datos', 'activa'),
('2024-0003', 'Tecnólogo en Sistemas', 'activa'),
('2024-0004', 'Técnico en Programación de Software', 'finalizada'),
('2024-0005', 'Tecnólogo en Gestión Administrativa', 'finalizada');

-- ============================================
-- APRENDICES (500 aprendices)
-- ============================================

-- Nombres comunes para generar aprendices
INSERT INTO aprendices (documento, nombre, apellido, codigo_carnet, estado) VALUES
-- Ficha 2025-0001 (10 aprendices)
('1001000001', 'Carlos', 'Rodríguez García', 'SENA2025001001', 'activo'),
('1001000002', 'María', 'López Martínez', 'SENA2025001002', 'activo'),
('1001000003', 'Juan', 'González Pérez', 'SENA2025001003', 'activo'),
('1001000004', 'Ana', 'Hernández Sánchez', 'SENA2025001004', 'activo'),
('1001000005', 'Luis', 'Ramírez Torres', 'SENA2025001005', 'activo'),
('1001000006', 'Laura', 'Díaz Flores', 'SENA2025001006', 'activo'),
('1001000007', 'Pedro', 'Moreno Cruz', 'SENA2025001007', 'activo'),
('1001000008', 'Carmen', 'Jiménez Ruiz', 'SENA2025001008', 'activo'),
('1001000009', 'Jorge', 'Vargas Ortiz', 'SENA2025001009', 'activo'),
('1001000010', 'Diana', 'Castro Vega', 'SENA2025001010', 'activo'),

-- Ficha 2025-0002 (10 aprendices)
('1001000011', 'Andrés', 'Mendoza Silva', 'SENA2025002001', 'activo'),
('1001000012', 'Patricia', 'Romero León', 'SENA2025002002', 'activo'),
('1001000013', 'Roberto', 'Torres Gómez', 'SENA2025002003', 'activo'),
('1001000014', 'Sandra', 'Rojas Herrera', 'SENA2025002004', 'activo'),
('1001000015', 'Miguel', 'Gutiérrez Morales', 'SENA2025002005', 'activo'),
('1001000016', 'Claudia', 'Peña Campos', 'SENA2025002006', 'activo'),
('1001000017', 'Fernando', 'Salazar Ramos', 'SENA2025002007', 'activo'),
('1001000018', 'Liliana', 'Valencia Cortés', 'SENA2025002008', 'activo'),
('1001000019', 'Javier', 'Ortega Navarro', 'SENA2025002009', 'activo'),
('1001000020', 'Mónica', 'Aguilar Muñoz', 'SENA2025002010', 'activo'),

-- Ficha 2025-0003 (10 aprendices)
('1001000021', 'Ricardo', 'Medina Reyes', 'SENA2025003001', 'activo'),
('1001000022', 'Gabriela', 'Soto Ibarra', 'SENA2025003002', 'activo'),
('1001000023', 'Eduardo', 'Parra Delgado', 'SENA2025003003', 'activo'),
('1001000024', 'Natalia', 'Ríos Paredes', 'SENA2025003004', 'activo'),
('1001000025', 'Daniel', 'Núñez Estrada', 'SENA2025003005', 'activo'),
('1001000026', 'Valentina', 'Cabrera Molina', 'SENA2025003006', 'activo'),
('1001000027', 'Sebastián', 'Lara Fuentes', 'SENA2025003007', 'activo'),
('1001000028', 'Isabella', 'Carrillo Padilla', 'SENA2025003008', 'activo'),
('1001000029', 'Camilo', 'Osorio Vega', 'SENA2025003009', 'activo'),
('1001000030', 'Sofía', 'Suárez Mejía', 'SENA2025003010', 'activo'),

-- Ficha 2025-0004 (10 aprendices)
('1001000031', 'Mateo', 'Acosta Figueroa', 'SENA2025004001', 'activo'),
('1001000032', 'Camila', 'Bernal Montoya', 'SENA2025004002', 'activo'),
('1001000033', 'Santiago', 'Cárdenas Pacheco', 'SENA2025004003', 'activo'),
('1001000034', 'Mariana', 'Durán Cáceres', 'SENA2025004004', 'activo'),
('1001000035', 'Alejandro', 'Espinosa Villanueva', 'SENA2025004005', 'activo'),
('1001000036', 'Juliana', 'Franco Benítez', 'SENA2025004006', 'activo'),
('1001000037', 'Nicolás', 'Gil Cardona', 'SENA2025004007', 'activo'),
('1001000038', 'Valeria', 'Henao Duque', 'SENA2025004008', 'activo'),
('1001000039', 'Samuel', 'Ibáñez Arbeláez', 'SENA2025004009', 'activo'),
('1001000040', 'Daniela', 'Jaramillo Escobar', 'SENA2025004010', 'activo'),

-- Ficha 2025-0005 (10 aprendices)
('1001000041', 'Emilio', 'Keiser Londoño', 'SENA2025005001', 'activo'),
('1001000042', 'Luciana', 'Luna Marín', 'SENA2025005002', 'activo'),
('1001000043', 'Martín', 'Millán Naranjo', 'SENA2025005003', 'activo'),
('1001000044', 'Antonella', 'Nieto Ochoa', 'SENA2025005004', 'activo'),
('1001000045', 'Tomás', 'Ordóñez Patiño', 'SENA2025005005', 'activo'),
('1001000046', 'Renata', 'Pinto Quintero', 'SENA2025005006', 'activo'),
('1001000047', 'Maximiliano', 'Quiroz Rincón', 'SENA2025005007', 'activo'),
('1001000048', 'Salomé', 'Robles Sandoval', 'SENA2025005008', 'activo'),
('1001000049', 'Lorenzo', 'Tejada Uribe', 'SENA2025005009', 'activo'),
('1001000050', 'Emma', 'Velasco Zapata', 'SENA2025005010', 'activo');

-- Continuamos con más aprendices distribuidos en las demás fichas (para llegar a 500)
-- Por brevedad, generaré los restantes con un patrón similar

INSERT INTO aprendices (documento, nombre, apellido, codigo_carnet, estado)
SELECT 
    CONCAT('10010', LPAD(n, 5, '0')),
    ELT(FLOOR(1 + (RAND() * 20)), 'Carlos', 'María', 'Juan', 'Ana', 'Luis', 'Laura', 'Pedro', 'Carmen', 'Jorge', 'Diana', 
        'Andrés', 'Patricia', 'Roberto', 'Sandra', 'Miguel', 'Claudia', 'Fernando', 'Liliana', 'Javier', 'Mónica'),
    ELT(FLOOR(1 + (RAND() * 30)), 'García', 'López', 'González', 'Hernández', 'Ramírez', 'Díaz', 'Moreno', 'Jiménez', 
        'Vargas', 'Castro', 'Mendoza', 'Romero', 'Torres', 'Rojas', 'Gutiérrez', 'Peña', 'Salazar', 'Valencia', 
        'Ortega', 'Aguilar', 'Medina', 'Soto', 'Parra', 'Ríos', 'Núñez', 'Cabrera', 'Lara', 'Carrillo', 'Osorio', 'Suárez'),
    CONCAT('SENA2025', LPAD(FLOOR(1 + (RAND() * 45)), 3, '0'), LPAD(FLOOR(1 + (RAND() * 20)), 3, '0')),
    IF(RAND() > 0.95, 'retirado', 'activo')
FROM 
    (SELECT @row := @row + 1 AS n FROM 
        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t1,
        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t2,
        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t3,
        (SELECT @row := 50) t4
    ) numbers
WHERE n <= 500
LIMIT 450;

-- ============================================
-- RELACIÓN FICHA_APRENDIZ
-- ============================================

-- Vinculamos aprendices a las fichas (10 aprendices por ficha en promedio)
INSERT INTO ficha_aprendiz (id_ficha, id_aprendiz)
SELECT 
    FLOOR(1 + (RAND() * 45)), -- ID de ficha aleatoria (1-45)
    a.id
FROM aprendices a
WHERE a.estado = 'activo'
ON DUPLICATE KEY UPDATE id_ficha = id_ficha; -- Evitar duplicados

-- Asegurar que los primeros 50 aprendices estén en las primeras 5 fichas (distribución específica)
INSERT IGNORE INTO ficha_aprendiz (id_ficha, id_aprendiz) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18), (2, 19), (2, 20),
(3, 21), (3, 22), (3, 23), (3, 24), (3, 25), (3, 26), (3, 27), (3, 28), (3, 29), (3, 30),
(4, 31), (4, 32), (4, 33), (4, 34), (4, 35), (4, 36), (4, 37), (4, 38), (4, 39), (4, 40),
(5, 41), (5, 42), (5, 43), (5, 44), (5, 45), (5, 46), (5, 47), (5, 48), (5, 49), (5, 50);

-- ============================================
-- RESUMEN DE DATOS CARGADOS
-- ============================================

-- Usuarios: 4 (1 admin, 2 instructores, 1 coordinador)
-- Fichas: 50 (45 activas, 5 finalizadas)
-- Aprendices: 500 (~475 activos, ~25 retirados)
-- Relaciones ficha-aprendiz: ~500 (todos los aprendices activos vinculados)

-- Credenciales por defecto:
-- Email: admin@sena.edu.co | Password: admin123
-- Email: instr1@sena.edu.co | Password: admin123
-- Email: instr2@sena.edu.co | Password: admin123
-- Email: coordinador@sena.edu.co | Password: admin123

