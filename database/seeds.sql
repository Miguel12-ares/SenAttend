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

-- Nombres comunes para generar aprendices con emails automáticos
-- Formato email: nombre_primer_apellido@gmail.com
INSERT INTO aprendices (documento, nombre, apellido, email, estado) VALUES
-- Ficha 2025-0001 (10 aprendices)
('1001000001', 'Carlos', 'Rodríguez García', 'carlos_rodriguez@gmail.com', 'activo'),
('1001000002', 'María', 'López Martínez', 'maria_lopez@gmail.com', 'activo'),
('1001000003', 'Juan', 'González Pérez', 'juan_gonzalez@gmail.com', 'activo'),
('1001000004', 'Ana', 'Hernández Sánchez', 'ana_hernandez@gmail.com', 'activo'),
('1001000005', 'Luis', 'Ramírez Torres', 'luis_ramirez@gmail.com', 'activo'),
('1001000006', 'Laura', 'Díaz Flores', 'laura_diaz@gmail.com', 'activo'),
('1001000007', 'Pedro', 'Moreno Cruz', 'pedro_moreno@gmail.com', 'activo'),
('1001000008', 'Carmen', 'Jiménez Ruiz', 'carmen_jimenez@gmail.com', 'activo'),
('1001000009', 'Jorge', 'Vargas Ortiz', 'jorge_vargas@gmail.com', 'activo'),
('1001000010', 'Diana', 'Castro Vega', 'diana_castro@gmail.com', 'activo'),

-- Ficha 2025-0002 (10 aprendices)
('1001000011', 'Andrés', 'Mendoza Silva', 'andres_mendoza@gmail.com', 'activo'),
('1001000012', 'Patricia', 'Romero León', 'patricia_romero@gmail.com', 'activo'),
('1001000013', 'Roberto', 'Torres Gómez', 'roberto_torres@gmail.com', 'activo'),
('1001000014', 'Sandra', 'Rojas Herrera', 'sandra_rojas@gmail.com', 'activo'),
('1001000015', 'Miguel', 'Gutiérrez Morales', 'miguel_gutierrez@gmail.com', 'activo'),
('1001000016', 'Claudia', 'Peña Campos', 'claudia_pena@gmail.com', 'activo'),
('1001000017', 'Fernando', 'Salazar Ramos', 'fernando_salazar@gmail.com', 'activo'),
('1001000018', 'Liliana', 'Valencia Cortés', 'liliana_valencia@gmail.com', 'activo'),
('1001000019', 'Javier', 'Ortega Navarro', 'javier_ortega@gmail.com', 'activo'),
('1001000020', 'Mónica', 'Aguilar Muñoz', 'monica_aguilar@gmail.com', 'activo'),

-- Ficha 2025-0003 (10 aprendices)
('1001000021', 'Ricardo', 'Medina Reyes', 'ricardo_medina@gmail.com', 'activo'),
('1001000022', 'Gabriela', 'Soto Ibarra', 'gabriela_soto@gmail.com', 'activo'),
('1001000023', 'Eduardo', 'Parra Delgado', 'eduardo_parra@gmail.com', 'activo'),
('1001000024', 'Natalia', 'Ríos Paredes', 'natalia_rios@gmail.com', 'activo'),
('1001000025', 'Daniel', 'Núñez Estrada', 'daniel_nunez@gmail.com', 'activo'),
('1001000026', 'Valentina', 'Cabrera Molina', 'valentina_cabrera@gmail.com', 'activo'),
('1001000027', 'Sebastián', 'Lara Fuentes', 'sebastian_lara@gmail.com', 'activo'),
('1001000028', 'Isabella', 'Carrillo Padilla', 'isabella_carrillo@gmail.com', 'activo'),
('1001000029', 'Camilo', 'Osorio Vega', 'camilo_osorio@gmail.com', 'activo'),
('1001000030', 'Sofía', 'Suárez Mejía', 'sofia_suarez@gmail.com', 'activo'),

-- Ficha 2025-0004 (10 aprendices)
('1001000031', 'Mateo', 'Acosta Figueroa', 'mateo_acosta@gmail.com', 'activo'),
('1001000032', 'Camila', 'Bernal Montoya', 'camila_bernal@gmail.com', 'activo'),
('1001000033', 'Santiago', 'Cárdenas Pacheco', 'santiago_cardenas@gmail.com', 'activo'),
('1001000034', 'Mariana', 'Durán Cáceres', 'mariana_duran@gmail.com', 'activo'),
('1001000035', 'Alejandro', 'Espinosa Villanueva', 'alejandro_espinosa@gmail.com', 'activo'),
('1001000036', 'Juliana', 'Franco Benítez', 'juliana_franco@gmail.com', 'activo'),
('1001000037', 'Nicolás', 'Gil Cardona', 'nicolas_gil@gmail.com', 'activo'),
('1001000038', 'Valeria', 'Henao Duque', 'valeria_henao@gmail.com', 'activo'),
('1001000039', 'Samuel', 'Ibáñez Arbeláez', 'samuel_ibanez@gmail.com', 'activo'),
('1001000040', 'Daniela', 'Jaramillo Escobar', 'daniela_jaramillo@gmail.com', 'activo'),

-- Ficha 2025-0005 (10 aprendices)
('1001000041', 'Emilio', 'Keiser Londoño', 'emilio_keiser@gmail.com', 'activo'),
('1001000042', 'Luciana', 'Luna Marín', 'luciana_luna@gmail.com', 'activo'),
('1001000043', 'Martín', 'Millán Naranjo', 'martin_millan@gmail.com', 'activo'),
('1001000044', 'Antonella', 'Nieto Ochoa', 'antonella_nieto@gmail.com', 'activo'),
('1001000045', 'Tomás', 'Ordóñez Patiño', 'tomas_ordonez@gmail.com', 'activo'),
('1001000046', 'Renata', 'Pinto Quintero', 'renata_pinto@gmail.com', 'activo'),
('1001000047', 'Maximiliano', 'Quiroz Rincón', 'maximiliano_quiroz@gmail.com', 'activo'),
('1001000048', 'Salomé', 'Robles Sandoval', 'salome_robles@gmail.com', 'activo'),
('1001000049', 'Lorenzo', 'Tejada Uribe', 'lorenzo_tejada@gmail.com', 'activo'),
('1001000050', 'Emma', 'Velasco Zapata', 'emma_velasco@gmail.com', 'activo');

-- Continuamos con más aprendices distribuidos en las demás fichas (para llegar a 500)
-- Generamos emails automáticamente basados en nombre_apellido_documento@gmail.com
-- Incluimos el documento para garantizar unicidad del email

INSERT INTO aprendices (documento, nombre, apellido, email, estado)
SELECT 
    CONCAT('10010', LPAD(n, 5, '0')) as documento,
    ELT(FLOOR(1 + (RAND() * 20)), 'Carlos', 'Maria', 'Juan', 'Ana', 'Luis', 'Laura', 'Pedro', 'Carmen', 'Jorge', 'Diana', 
        'Andres', 'Patricia', 'Roberto', 'Sandra', 'Miguel', 'Claudia', 'Fernando', 'Liliana', 'Javier', 'Monica') as nombre,
    ELT(FLOOR(1 + (RAND() * 30)), 'Garcia', 'Lopez', 'Gonzalez', 'Hernandez', 'Ramirez', 'Diaz', 'Moreno', 'Jimenez', 
        'Vargas', 'Castro', 'Mendoza', 'Romero', 'Torres', 'Rojas', 'Gutierrez', 'Pena', 'Salazar', 'Valencia', 
        'Ortega', 'Aguilar', 'Medina', 'Soto', 'Parra', 'Rios', 'Nunez', 'Cabrera', 'Lara', 'Carrillo', 'Osorio', 'Suarez') as apellido,
    CONCAT(
        LOWER(ELT(FLOOR(1 + (RAND() * 20)), 'carlos', 'maria', 'juan', 'ana', 'luis', 'laura', 'pedro', 'carmen', 'jorge', 'diana', 
            'andres', 'patricia', 'roberto', 'sandra', 'miguel', 'claudia', 'fernando', 'liliana', 'javier', 'monica')),
        '_',
        LOWER(ELT(FLOOR(1 + (RAND() * 30)), 'garcia', 'lopez', 'gonzalez', 'hernandez', 'ramirez', 'diaz', 'moreno', 'jimenez', 
            'vargas', 'castro', 'mendoza', 'romero', 'torres', 'rojas', 'gutierrez', 'pena', 'salazar', 'valencia', 
            'ortega', 'aguilar', 'medina', 'soto', 'parra', 'rios', 'nunez', 'cabrera', 'lara', 'carrillo', 'osorio', 'suarez')),
        '_',
        CONCAT('10010', LPAD(n, 5, '0')),
        '@gmail.com'
    ) as email,
    IF(RAND() > 0.95, 'retirado', 'activo') as estado
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

-- Primero: Asegurar que los primeros 50 aprendices estén en las primeras 5 fichas (distribución específica)
INSERT IGNORE INTO ficha_aprendiz (id_ficha, id_aprendiz) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18), (2, 19), (2, 20),
(3, 21), (3, 22), (3, 23), (3, 24), (3, 25), (3, 26), (3, 27), (3, 28), (3, 29), (3, 30),
(4, 31), (4, 32), (4, 33), (4, 34), (4, 35), (4, 36), (4, 37), (4, 38), (4, 39), (4, 40),
(5, 41), (5, 42), (5, 43), (5, 44), (5, 45), (5, 46), (5, 47), (5, 48), (5, 49), (5, 50);

-- Segundo: Vinculamos el resto de aprendices a las fichas (excluyendo los primeros 50)
INSERT INTO ficha_aprendiz (id_ficha, id_aprendiz)
SELECT 
    FLOOR(1 + (RAND() * 45)), -- ID de ficha aleatoria (1-45)
    a.id
FROM aprendices a
WHERE a.estado = 'activo' AND a.id > 50
ON DUPLICATE KEY UPDATE id_ficha = id_ficha; -- Evitar duplicados

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

