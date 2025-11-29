<?php

/**
 * Test específico de lógica de asistencia automática por jornada/turno.
 *
 * Casos a validar:
 * - Aprendiz jornada Mañana escaneando a las 06:05 → presente
 * - Aprendiz jornada Mañana escaneando a las 06:25 → tardanza
 * - Aprendiz jornada Mañana escaneando a las 12:05 → tardanza (muy tarde pero sigue siendo tardanza)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Repositories\AsistenciaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\FichaRepository;
use App\Repositories\TurnoConfigRepository;
use App\Services\AsistenciaService;
use App\Services\TurnoConfigService;

echo "🔍 TEST LÓGICA DE ASISTENCIA AUTOMÁTICA\n";
echo "=" . str_repeat("=", 60) . "\n\n";

try {
    $asistenciaRepo = new AsistenciaRepository();
    $aprendizRepo = new AprendizRepository();
    $fichaRepo = new FichaRepository();
    $turnoConfigRepo = new TurnoConfigRepository();
    $turnoConfigService = new TurnoConfigService($turnoConfigRepo);

    $service = new AsistenciaService(
        $asistenciaRepo,
        $aprendizRepo,
        $fichaRepo,
        $turnoConfigService
    );

    // ID de ficha y aprendiz de prueba (ajustar si es necesario según tu BD)
    $fichaId = 1;
    $aprendizId = 75;
    $fecha = date('Y-m-d');

    $casos = [
        ['hora' => '06:05:00', 'esperado' => 'presente'],
        ['hora' => '06:25:00', 'esperado' => 'tardanza'],
        ['hora' => '12:05:00', 'esperado' => 'tardanza'],
    ];

    foreach ($casos as $caso) {
        echo "Caso hora {$caso['hora']} (esperado: {$caso['esperado']})...\n";

        $data = [
            'id_aprendiz' => $aprendizId,
            'id_ficha' => $fichaId,
            'fecha' => $fecha,
            'hora' => $caso['hora'],
            'registrado_por' => 2,
            'observaciones' => 'Test lógica automática'
        ];

        $resultado = $service->registrarAsistenciaAutomatica($data, 1);

        if (!$resultado['success']) {
            echo "  ❌ Error en registro: {$resultado['message']}\n";
            continue;
        }

        $estadoReal = $resultado['data']['estado'] ?? '(desconocido)';

        if ($estadoReal === $caso['esperado']) {
            echo "  ✅ Estado correcto: {$estadoReal}\n";
        } else {
            echo "  ❌ Estado incorrecto. Esperado {$caso['esperado']}, obtenido {$estadoReal}\n";
        }
    }

    echo "\n✅ Test de lógica de asistencia automática finalizado.\n";

} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}


