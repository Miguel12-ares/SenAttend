<?php

namespace App\Console\Commands;

use App\Services\AsistenciaService;
use App\Services\TurnoConfigService;
use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\AsistenciaRepository;
use App\Repositories\TurnoConfigRepository;
use App\Database\Connection;
use Exception;

/**
 * Comando para marcar automáticamente como "ausente" a los aprendices
 * que no registraron asistencia al finalizar su jornada.
 * 
 * Uso sugerido: Ejecutar vía CRON a las 12:00, 18:00 y 23:00
 */
class MarcarAusentesCommand
{
    private AsistenciaService $asistenciaService;
    private TurnoConfigService $turnoConfigService;
    private TurnoConfigRepository $turnoConfigRepository;
    private FichaRepository $fichaRepository;
    private AprendizRepository $aprendizRepository;
    private AsistenciaRepository $asistenciaRepository;

    public function __construct()
    {
        // Inicializar dependencias manualmente (en un framework real usaríamos DI container)
        $this->asistenciaRepository = new AsistenciaRepository();
        $this->aprendizRepository = new AprendizRepository();
        $this->fichaRepository = new FichaRepository();
        $this->turnoConfigRepository = new \App\Repositories\TurnoConfigRepository();
        $this->turnoConfigService = new TurnoConfigService($this->turnoConfigRepository);
        $this->asistenciaService = new AsistenciaService(
            $this->asistenciaRepository,
            $this->aprendizRepository,
            $this->fichaRepository,
            $this->turnoConfigService
        );
    }

    public function execute(): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] Iniciando proceso de marcado de ausencias...\n";

        try {
            $fecha = date('Y-m-d');
            $horaActual = date('H:i:s');

            // 1. Identificar qué jornadas ya terminaron
            $turnos = $this->turnoConfigService->obtenerConfiguracionTurnos();
            $jornadasTerminadas = [];

            foreach ($turnos as $turno) {
                // Consideramos terminada si la hora actual es mayor a la hora fin del turno
                // O si estamos muy cerca del fin (ej. 5 mins antes) para asegurar
                if ($horaActual >= $turno['hora_fin']) {
                    $jornadasTerminadas[] = $turno['nombre_turno'];
                }
            }

            if (empty($jornadasTerminadas)) {
                echo "No hay jornadas terminadas para procesar en este momento.\n";
                return;
            }

            echo "Jornadas terminadas: " . implode(', ', $jornadasTerminadas) . "\n";

            // 2. Obtener fichas de esas jornadas
            foreach ($jornadasTerminadas as $jornada) {
                $this->procesarJornada($jornada, $fecha);
            }

            echo "[" . date('Y-m-d H:i:s') . "] Proceso finalizado correctamente.\n";

        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            error_log("Error en MarcarAusentesCommand: " . $e->getMessage());
        }
    }

    private function procesarJornada(string $jornada, string $fecha): void
    {
        echo "Procesando jornada: {$jornada}...\n";

        // Obtener fichas activas de esta jornada
        // Por ahora obtenemos todas y filtramos en PHP, idealmente optimizar query
        $fichas = $this->fichaRepository->findActive(1000, 0); 
        
        $countAusentes = 0;

        foreach ($fichas as $ficha) {
            if (($ficha['jornada'] ?? null) !== $jornada) {
                continue;
            }

            // Usamos el método del servicio que ya tiene la lógica de vinculación
            $aprendicesEstado = $this->asistenciaService->getAprendicesParaRegistro($ficha['id'], $fecha);

            foreach ($aprendicesEstado as $aprendiz) {
                // Si no tiene estado (es decir, no marcó asistencia), marcar como ausente
                if (empty($aprendiz['estado'])) {
                    try {
                        $this->asistenciaService->registrarAsistencia([
                            'id_aprendiz' => $aprendiz['id'],
                            'id_ficha' => $ficha['id'],
                            'fecha' => $fecha,
                            'hora' => date('H:i:s'), // Hora actual de cierre
                            'estado' => 'ausente',
                            'registrado_por' => 1, // ID del sistema/admin (ajustar según necesidad)
                            'observaciones' => 'Ausencia automática por fin de jornada'
                        ], 1); // Usuario sistema
                        
                        $countAusentes++;
                    } catch (Exception $e) {
                        echo "Error marcando aprendiz ID {$aprendiz['id']}: " . $e->getMessage() . "\n";
                    }
                }
            }
        }

        echo "Se marcaron {$countAusentes} ausencias para la jornada {$jornada}.\n";
    }
}

// Ejecutar si se llama directamente
if (php_sapi_name() === 'cli' && realpath($argv[0]) == realpath(__FILE__)) {
    $command = new MarcarAusentesCommand();
    $command->execute();
}
