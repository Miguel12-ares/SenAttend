<?php
/**
 * Script para verificar y ejecutar migración de configuracion_turnos
 */

require_once __DIR__ . '/../../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Verificar si la tabla ya existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'configuracion_turnos'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "La tabla 'configuracion_turnos' ya existe.\n";
        echo "¿Desea eliminarla y recrearla? (s/n): ";
        
        // Para ejecución automática, comentar las siguientes líneas
        // $handle = fopen("php://stdin", "r");
        // $line = fgets($handle);
        // if(trim($line) != 's') {
        //     echo "Operación cancelada.\n";
        //     exit(0);
        // }
        
        // Para ejecución automática, descomentar la siguiente línea
        echo "Eliminando tabla existente...\n";
        $pdo->exec("DROP TABLE IF EXISTS configuracion_turnos");
    }

    echo "Creando tabla configuracion_turnos...\n";
    
    // Crear tabla
    $pdo->exec("
        CREATE TABLE configuracion_turnos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre_turno ENUM('Mañana', 'Tarde', 'Noche') NOT NULL UNIQUE,
            hora_inicio TIME NOT NULL,
            hora_fin TIME NOT NULL,
            hora_limite_llegada TIME NOT NULL COMMENT 'Hora límite para marcar tardanza',
            activo BOOLEAN DEFAULT TRUE COMMENT 'Permite desactivar turnos sin eliminarlos',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_nombre_turno (nombre_turno),
            INDEX idx_activo (activo),
            INDEX idx_horarios (hora_inicio, hora_fin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Configuración dinámica de horarios de turnos para validación de asistencia'
    ");

    echo "Insertando datos semilla...\n";
    
    // Insertar datos semilla
    $pdo->exec("
        INSERT INTO configuracion_turnos (nombre_turno, hora_inicio, hora_fin, hora_limite_llegada) VALUES
        ('Mañana', '06:00:00', '12:00:00', '06:20:00'),
        ('Tarde', '12:00:00', '18:00:00', '12:25:00'),
        ('Noche', '18:00:00', '23:00:00', '18:20:00')
    ");

    echo "\n✓ Migración completada exitosamente!\n\n";
    
    // Mostrar datos
    $stmt = $pdo->query('SELECT * FROM configuracion_turnos ORDER BY hora_inicio');
    $turnos = $stmt->fetchAll();
    
    echo "Turnos configurados:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-10s %-15s %-15s %-20s\n", "Turno", "Hora Inicio", "Hora Fin", "Hora Límite");
    echo str_repeat('-', 80) . "\n";
    
    foreach ($turnos as $turno) {
        printf("%-10s %-15s %-15s %-20s\n",
            $turno['nombre_turno'],
            $turno['hora_inicio'],
            $turno['hora_fin'],
            $turno['hora_limite_llegada']
        );
    }
    echo str_repeat('-', 80) . "\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
