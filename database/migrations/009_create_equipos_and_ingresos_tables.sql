-- Migración: Tablas para gestión de equipos portátiles
-- Fecha: 2025-12-01
-- Descripción:
--  Crea el modelo de datos para:
--    - equipos
--    - aprendiz_equipo (relación aprendiz-equipo)
--    - ingresos_equipos (registro de ingresos/salidas)
--    - anomalias_equipos (registro de incidencias y anomalías)

-- ============================================================================
-- Tabla: equipos
-- Descripción: Catálogo de equipos registrados por los aprendices
-- ============================================================================

CREATE TABLE IF NOT EXISTS equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_serial VARCHAR(100) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    imagen VARCHAR(255) NULL COMMENT 'Ruta o nombre de archivo de la imagen del equipo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN NOT NULL DEFAULT TRUE,

    -- Índices
    UNIQUE KEY unique_numero_serial (numero_serial),
    INDEX idx_activo (activo),
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Equipos portátiles registrados en el sistema';



CREATE TABLE IF NOT EXISTS aprendiz_equipo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_aprendiz INT NOT NULL,
    id_equipo INT NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMP NULL,

    -- Claves foráneas
    CONSTRAINT fk_aprendiz_equipo_aprendiz
        FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_aprendiz_equipo_equipo
        FOREIGN KEY (id_equipo) REFERENCES equipos(id)
        ON DELETE RESTRICT,

    -- Índices
    UNIQUE KEY unique_aprendiz_equipo_activo (id_aprendiz, id_equipo, estado),
    INDEX idx_id_aprendiz (id_aprendiz),
    INDEX idx_id_equipo (id_equipo),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Relación entre aprendices y equipos (historial de asignaciones)';


-- ============================================================================
-- Tabla: qr_equipos
-- Descripción: Códigos QR únicos por equipo para control de ingreso/salida
-- ============================================================================

CREATE TABLE IF NOT EXISTS qr_equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_equipo INT NOT NULL,
    id_aprendiz INT NOT NULL,
    token VARCHAR(64) NOT NULL COMMENT 'Identificador único del QR',
    qr_data TEXT NOT NULL COMMENT 'Payload codificado en el QR (JSON u otro formato)',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Indica si el QR está vigente para escaneo',

    -- Claves foráneas
    CONSTRAINT fk_qr_equipos_equipo
        FOREIGN KEY (id_equipo) REFERENCES equipos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_qr_equipos_aprendiz
        FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id)
        ON DELETE CASCADE,

    -- Índices
    UNIQUE KEY unique_qr_token (token),
    UNIQUE KEY unique_qr_equipo_activo (id_equipo, activo),
    INDEX idx_qr_equipo (id_equipo),
    INDEX idx_qr_aprendiz (id_aprendiz),
    INDEX idx_qr_activo (activo),
    INDEX idx_qr_fecha_expiracion (fecha_expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Códigos QR asociados a equipos y su dueño (aprendiz)';


-- ============================================================================
-- Tabla: ingresos_equipos
-- Descripción: Registro de ingresos y salidas de equipos en el CTA
-- ============================================================================

CREATE TABLE IF NOT EXISTS ingresos_equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_equipo INT NOT NULL,
    id_aprendiz INT NOT NULL,
    fecha_ingreso DATE NOT NULL,
    hora_ingreso TIME NOT NULL,
    fecha_salida DATE NULL,
    hora_salida TIME NULL,
    id_portero INT NOT NULL COMMENT 'Usuario con rol portero que realiza la operación',
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Claves foráneas
    CONSTRAINT fk_ingresos_equipos_equipo
        FOREIGN KEY (id_equipo) REFERENCES equipos(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_ingresos_equipos_aprendiz
        FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_ingresos_equipos_portero
        FOREIGN KEY (id_portero) REFERENCES usuarios(id)
        ON DELETE RESTRICT,

    -- Índices
    INDEX idx_ingresos_equipo (id_equipo),
    INDEX idx_ingresos_aprendiz (id_aprendiz),
    INDEX idx_ingresos_portero (id_portero),
    INDEX idx_ingresos_fecha_ingreso (fecha_ingreso),
    INDEX idx_ingresos_fecha_salida (fecha_salida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registros de ingreso y salida de equipos en el CTA';


-- ============================================================================
-- Tabla: anomalias_equipos
-- Descripción: Registro de anomalías en el flujo de ingreso/salida de equipos
-- ============================================================================

CREATE TABLE IF NOT EXISTS anomalias_equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_ingreso INT NOT NULL,
    descripcion TEXT NOT NULL,
    id_administrativo_gestor INT NOT NULL COMMENT 'Usuario administrativo que gestiona la anomalía',
    creada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resuelta BOOLEAN NOT NULL DEFAULT FALSE,
    resuelta_en TIMESTAMP NULL,

    -- Claves foráneas
    CONSTRAINT fk_anomalias_ingreso
        FOREIGN KEY (id_ingreso) REFERENCES ingresos_equipos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_anomalias_administrativo
        FOREIGN KEY (id_administrativo_gestor) REFERENCES usuarios(id)
        ON DELETE RESTRICT,

    -- Índices
    INDEX idx_anomalias_ingreso (id_ingreso),
    INDEX idx_anomalias_resuelta (resuelta),
    INDEX idx_anomalias_creada_en (creada_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Anomalías y eventos especiales asociados a ingresos de equipos';


