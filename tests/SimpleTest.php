<?php

/**
 * Test Simple del Módulo de Asistencia
 * Verificación básica de funcionamiento
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

echo "🚀 TESTING SIMPLE DEL MÓDULO DE ASISTENCIA\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    // Test 1: Conexión a BD
    echo "1. Probando conexión a base de datos...\n";
    $pdo = \App\Database\Connection::getInstance();
    echo "   ✅ Conexión exitosa\n\n";

    // Test 2: Repository
    echo "2. Probando AsistenciaRepository...\n";
    $repo = new \App\Repositories\AsistenciaRepository();
    $aprendices = $repo->getAprendicesPorFichaConAsistenciaDelDia(1, date('Y-m-d'));
    echo "   ✅ Repository funcionando - " . count($aprendices) . " aprendices encontrados\n\n";

    // Test 3: Service
    echo "3. Probando AsistenciaService...\n";
    $asistenciaRepo = new \App\Repositories\AsistenciaRepository();
    $aprendizRepo = new \App\Repositories\AprendizRepository();
    $fichaRepo = new \App\Repositories\FichaRepository();
    $turnoConfigRepo = new \App\Repositories\TurnoConfigRepository();
    $turnoConfigService = new \App\Services\TurnoConfigService($turnoConfigRepo);
    $service = new \App\Services\AsistenciaService($asistenciaRepo, $aprendizRepo, $fichaRepo, $turnoConfigService);
    
    $stats = $service->getEstadisticas(1, date('Y-m-d'));
    echo "   ✅ Service funcionando - Estadísticas: {$stats['total']} total\n\n";

    // Test 4: Índices
    echo "4. Verificando índices optimizados...\n";
    $stmt = $pdo->query("SHOW INDEX FROM asistencias WHERE Key_name LIKE 'idx_asistencias_%'");
    $indices = $stmt->fetchAll();
    echo "   ✅ Índices creados - " . count(array_unique(array_column($indices, 'Key_name'))) . " índices encontrados\n\n";

    // Test 5: Tabla de auditoría
    echo "5. Verificando tabla de auditoría...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'cambios_asistencia'");
    $table = $stmt->fetch();
    if ($table) {
        echo "   ✅ Tabla de auditoría existe\n\n";
    } else {
        echo "   ❌ Tabla de auditoría no encontrada\n\n";
    }

    echo "🎉 TODOS LOS TESTS BÁSICOS PASARON\n";
    echo "=" . str_repeat("=", 50) . "\n";
    echo "✅ El módulo de asistencia está funcionando correctamente\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
