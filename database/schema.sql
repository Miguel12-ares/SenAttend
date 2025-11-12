-- SENAttend - Esquema de Base de Datos MVP
-- Sistema de Asistencia SENA
-- Versión: 1.0

-- Eliminar base de datos si existe (solo para desarrollo)
-- DROP DATABASE IF EXISTS sena_asistencia;

-- Crear base de datos
-- CREATE DATABASE IF NOT EXISTS sena_asistencia DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sena_asistencia;

-- Tabla: usuarios
-- Almacena información de instructores, coordinadores y administradores
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('instructor', 'coordinador', 'admin') NOT NULL DEFAULT 'instructor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_documento (documento),
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: aprendices
-- Almacena información de los aprendices
CREATE TABLE IF NOT EXISTS aprendices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    codigo_carnet VARCHAR(50),
    estado ENUM('activo', 'retirado') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_documento (documento),
    INDEX idx_codigo_carnet (codigo_carnet),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: fichas
-- Almacena información de las fichas formativas
CREATE TABLE IF NOT EXISTS fichas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_ficha VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    estado ENUM('activa', 'finalizada') NOT NULL DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero (numero_ficha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: ficha_aprendiz
-- Relación muchos a muchos entre fichas y aprendices
CREATE TABLE IF NOT EXISTS ficha_aprendiz (
    id_ficha INT NOT NULL,
    id_aprendiz INT NOT NULL,
    fecha_vinculacion DATE DEFAULT (CURRENT_DATE),
    PRIMARY KEY (id_ficha, id_aprendiz),
    FOREIGN KEY (id_ficha) REFERENCES fichas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id) ON DELETE CASCADE,
    INDEX idx_ficha (id_ficha),
    INDEX idx_aprendiz (id_aprendiz)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: asistencias
-- Almacena los registros de asistencia
CREATE TABLE IF NOT EXISTS asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_aprendiz INT NOT NULL,
    id_ficha INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('presente', 'ausente', 'tardanza') NOT NULL DEFAULT 'presente',
    registrado_por INT NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id) ON DELETE CASCADE,
    FOREIGN KEY (id_ficha) REFERENCES fichas(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_registro (id_aprendiz, id_ficha, fecha),
    INDEX idx_fecha (fecha),
    INDEX idx_aprendiz_fecha (id_aprendiz, fecha),
    INDEX idx_ficha_fecha (id_ficha, fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentarios sobre el esquema
-- Los índices están optimizados para las consultas más frecuentes:
-- 1. Búsqueda de usuarios por email y documento
-- 2. Búsqueda de fichas por número
-- 3. Búsqueda de aprendices por documento y código de carnet
-- 4. Consultas de asistencia por fecha, aprendiz y ficha
-- 5. La clave única en asistencias previene duplicados por día

