<?php

namespace App\Services;

use App\Repositories\AnomaliaRepository;
use App\Repositories\AsistenciaRepository;
use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\UserRepository;
use RuntimeException;

/**
 * Servicio para gestión de anomalías de asistencia
 * Permite registrar anomalías por aprendiz o para la ficha en general
 * Validación: Solo se pueden registrar hasta 3 días después del registro de asistencia
 */
class AnomaliaService
{
    private AnomaliaRepository $anomaliaRepository;
    private AsistenciaRepository $asistenciaRepository;
    private FichaRepository $fichaRepository;
    private AprendizRepository $aprendizRepository;
    private UserRepository $userRepository;

    // Tipos de anomalías predefinidas
    public const TIPO_INASISTENCIA_NO_JUSTIFICADA = 'inasistencia_no_justificada';
    public const TIPO_INASISTENCIA_JUSTIFICADA = 'inasistencia_justificada';

    // Días permitidos para registrar anomalías después de la asistencia
    private const DIAS_LIMITE_REGISTRO = 3;

    public function __construct(
        AnomaliaRepository $anomaliaRepository,
        AsistenciaRepository $asistenciaRepository,
        FichaRepository $fichaRepository,
        AprendizRepository $aprendizRepository,
        UserRepository $userRepository
    ) {
        $this->anomaliaRepository = $anomaliaRepository;
        $this->asistenciaRepository = $asistenciaRepository;
        $this->fichaRepository = $fichaRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Obtiene los tipos de anomalías predefinidas
     * 
     * @return array Tipos de anomalías con información de visualización
     */
    public function getTiposAnomalias(): array
    {
        return [
            self::TIPO_INASISTENCIA_NO_JUSTIFICADA => [
                'codigo' => self::TIPO_INASISTENCIA_NO_JUSTIFICADA,
                'nombre' => 'Inasistencia no justificada',
                'color' => '#dc3545', // Rojo
                'color_clase' => 'danger',
                'icono' => 'exclamation-triangle'
            ],
            self::TIPO_INASISTENCIA_JUSTIFICADA => [
                'codigo' => self::TIPO_INASISTENCIA_JUSTIFICADA,
                'nombre' => 'Inasistencia justificada',
                'color' => '#fd7e14', // Anaranjado
                'color_clase' => 'warning',
                'icono' => 'info-circle'
            ]
        ];
    }

    /**
     * Valida que se pueda registrar una anomalía para una fecha de asistencia
     * Solo se pueden registrar hasta 3 días después del registro de asistencia
     * 
     * @param string $fechaAsistencia Fecha de la asistencia (YYYY-MM-DD)
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarFechaRegistro(string $fechaAsistencia): array
    {
        try {
            $fechaAsistenciaTimestamp = strtotime($fechaAsistencia);
            if ($fechaAsistenciaTimestamp === false) {
                return [
                    'valido' => false,
                    'mensaje' => 'Fecha de asistencia inválida'
                ];
            }

            $fechaActual = strtotime(date('Y-m-d'));
            $diferenciaDias = floor(($fechaActual - $fechaAsistenciaTimestamp) / (60 * 60 * 24));

            // No se pueden registrar anomalías para fechas futuras
            if ($diferenciaDias < 0) {
                return [
                    'valido' => false,
                    'mensaje' => 'No se pueden registrar anomalías para fechas futuras'
                ];
            }

            // Solo se pueden registrar hasta 3 días después
            if ($diferenciaDias > self::DIAS_LIMITE_REGISTRO) {
                return [
                    'valido' => false,
                    'mensaje' => "Solo se pueden registrar anomalías hasta " . self::DIAS_LIMITE_REGISTRO . " días después del registro de asistencia. Han pasado {$diferenciaDias} días."
                ];
            }

            return [
                'valido' => true,
                'mensaje' => 'Fecha válida para registro de anomalía',
                'dias_transcurridos' => $diferenciaDias,
                'dias_restantes' => self::DIAS_LIMITE_REGISTRO - $diferenciaDias
            ];
        } catch (\Exception $e) {
            error_log("Error validando fecha de registro de anomalía: " . $e->getMessage());
            return [
                'valido' => false,
                'mensaje' => 'Error al validar fecha'
            ];
        }
    }

    /**
     * Registra una anomalía por aprendiz
     * 
     * @param array $data Datos de la anomalía
     * @param int $usuarioId ID del usuario que registra
     * @return array Resultado estructurado
     */
    public function registrarAnomaliaAprendiz(array $data, int $usuarioId): array
    {
        try {
            // Validar datos requeridos
            if (empty($data['id_aprendiz']) || empty($data['id_ficha']) || empty($data['fecha_asistencia']) || empty($data['tipo_anomalia'])) {
                return [
                    'success' => false,
                    'message' => 'Faltan datos requeridos para registrar la anomalía'
                ];
            }

            // Validar tipo de anomalía
            $tiposValidos = array_keys($this->getTiposAnomalias());
            if (!in_array($data['tipo_anomalia'], $tiposValidos)) {
                return [
                    'success' => false,
                    'message' => 'Tipo de anomalía inválido'
                ];
            }

            // Validar que el aprendiz existe y verificar su estado de asistencia
            $asistencia = $this->asistenciaRepository->findByAprendizFichaFecha(
                (int) $data['id_aprendiz'],
                (int) $data['id_ficha'],
                $data['fecha_asistencia']
            );

            // No permitir registrar anomalías para aprendices presentes
            if ($asistencia && $asistencia['estado'] === 'presente') {
                return [
                    'success' => false,
                    'message' => 'No se pueden registrar anomalías para aprendices que están presentes'
                ];
            }

            // Validar que el aprendiz existe
            $aprendiz = $this->aprendizRepository->findById($data['id_aprendiz']);
            if (!$aprendiz) {
                return [
                    'success' => false,
                    'message' => 'El aprendiz no existe'
                ];
            }

            // Validar que la ficha existe
            $ficha = $this->fichaRepository->findById($data['id_ficha']);
            if (!$ficha) {
                return [
                    'success' => false,
                    'message' => 'La ficha no existe'
                ];
            }

            // Validar fecha de registro (hasta 3 días después)
            $validacionFecha = $this->validarFechaRegistro($data['fecha_asistencia']);
            if (!$validacionFecha['valido']) {
                return [
                    'success' => false,
                    'message' => $validacionFecha['mensaje']
                ];
            }

            // Buscar asistencia relacionada si existe
            $asistencia = null;
            if (!empty($data['id_asistencia'])) {
                // Si se proporciona id_asistencia, validar que existe
                $asistencia = $this->asistenciaRepository->findById($data['id_asistencia']);
                if (!$asistencia) {
                    return [
                        'success' => false,
                        'message' => 'La asistencia especificada no existe'
                    ];
                }
            } else {
                // Buscar asistencia por aprendiz, ficha y fecha
                $asistencia = $this->asistenciaRepository->findByAprendizFichaFecha(
                    $data['id_aprendiz'],
                    $data['id_ficha'],
                    $data['fecha_asistencia']
                );
                if ($asistencia) {
                    $data['id_asistencia'] = $asistencia['id'];
                }
            }

            // Validar que no se registren anomalías para aprendices presentes
            if ($asistencia && isset($asistencia['estado']) && $asistencia['estado'] === 'presente') {
                return [
                    'success' => false,
                    'message' => 'No se pueden registrar anomalías para aprendices que están presentes'
                ];
            }

            // Verificar si ya existe una anomalía del mismo tipo
            if ($this->anomaliaRepository->existeAnomalia(
                $data['id_asistencia'] ?? null,
                $data['id_aprendiz'],
                $data['id_ficha'],
                $data['fecha_asistencia'],
                $data['tipo_anomalia']
            )) {
                return [
                    'success' => false,
                    'message' => 'Ya existe una anomalía de este tipo para este aprendiz en esta fecha'
                ];
            }

            // Preparar datos para inserción
            $datosAnomalia = [
                'id_asistencia' => $data['id_asistencia'] ?? null,
                'id_aprendiz' => $data['id_aprendiz'],
                'id_ficha' => $data['id_ficha'],
                'tipo_anomalia' => $data['tipo_anomalia'],
                'descripcion' => $data['descripcion'] ?? null,
                'registrado_por' => $usuarioId,
                'fecha_asistencia' => $data['fecha_asistencia'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];

            // Registrar anomalía
            $anomaliaId = $this->anomaliaRepository->create($datosAnomalia);

            // Log de operación crítica
            $this->logOperacionCritica('REGISTRO_ANOMALIA_APRENDIZ', [
                'anomalia_id' => $anomaliaId,
                'aprendiz_id' => $data['id_aprendiz'],
                'ficha_id' => $data['id_ficha'],
                'tipo_anomalia' => $data['tipo_anomalia'],
                'fecha_asistencia' => $data['fecha_asistencia'],
                'usuario_id' => $usuarioId
            ]);

            return [
                'success' => true,
                'message' => 'Anomalía registrada exitosamente',
                'id' => $anomaliaId,
                'data' => $datosAnomalia
            ];

        } catch (\Exception $e) {
            error_log("Error registrando anomalía de aprendiz: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al registrar anomalía: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Registra una anomalía general de ficha
     * 
     * @param array $data Datos de la anomalía
     * @param int $usuarioId ID del usuario que registra
     * @return array Resultado estructurado
     */
    public function registrarAnomaliaFicha(array $data, int $usuarioId): array
    {
        try {
            // Validar datos requeridos
            if (empty($data['id_ficha']) || empty($data['fecha_asistencia']) || empty($data['tipo_anomalia'])) {
                return [
                    'success' => false,
                    'message' => 'Faltan datos requeridos para registrar la anomalía'
                ];
            }

            // Validar tipo de anomalía
            $tiposValidos = array_keys($this->getTiposAnomalias());
            if (!in_array($data['tipo_anomalia'], $tiposValidos)) {
                return [
                    'success' => false,
                    'message' => 'Tipo de anomalía inválido'
                ];
            }

            // Validar que la ficha existe
            $ficha = $this->fichaRepository->findById($data['id_ficha']);
            if (!$ficha) {
                return [
                    'success' => false,
                    'message' => 'La ficha no existe'
                ];
            }

            // Validar fecha de registro (hasta 3 días después)
            $validacionFecha = $this->validarFechaRegistro($data['fecha_asistencia']);
            if (!$validacionFecha['valido']) {
                return [
                    'success' => false,
                    'message' => $validacionFecha['mensaje']
                ];
            }

            // Verificar si ya existe una anomalía del mismo tipo para la ficha
            if ($this->anomaliaRepository->existeAnomalia(
                null,
                null,
                $data['id_ficha'],
                $data['fecha_asistencia'],
                $data['tipo_anomalia']
            )) {
                return [
                    'success' => false,
                    'message' => 'Ya existe una anomalía de este tipo para esta ficha en esta fecha'
                ];
            }

            // Obtener todos los aprendices de la ficha para esa fecha
            $aprendices = $this->asistenciaRepository->getAprendicesPorFichaConAsistenciaDelDia(
                $data['id_ficha'],
                $data['fecha_asistencia']
            );

            // Filtrar solo los aprendices que NO están presentes (ausentes, tardanzas, sin registro)
            $aprendicesNoPresentes = array_filter($aprendices, function($aprendiz) {
                // Si no hay estado o es null, se trata como ausente (ya procesado en el repositorio)
                return $aprendiz['asistencia_estado'] !== 'presente';
            });

            // Preparar datos para inserción
            $datosAnomalia = [
                'id_asistencia' => null,
                'id_aprendiz' => null,
                'id_ficha' => $data['id_ficha'],
                'tipo_anomalia' => $data['tipo_anomalia'],
                'descripcion' => $data['descripcion'] ?? null,
                'registrado_por' => $usuarioId,
                'fecha_asistencia' => $data['fecha_asistencia'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];

            // Registrar anomalía general de ficha
            $anomaliaId = $this->anomaliaRepository->create($datosAnomalia);

            // Registrar la misma anomalía para todos los aprendices no presentes
            $anomaliasRegistradas = 0;
            $errores = [];
            
            foreach ($aprendicesNoPresentes as $aprendiz) {
                try {
                    // Buscar asistencia relacionada si existe
                    $asistencia = null;
                    if (!empty($aprendiz['asistencia_id'])) {
                        $asistencia = $this->asistenciaRepository->findById($aprendiz['asistencia_id']);
                    } else {
                        // Buscar asistencia por aprendiz, ficha y fecha
                        $asistencia = $this->asistenciaRepository->findByAprendizFichaFecha(
                            $aprendiz['id_aprendiz'],
                            $data['id_ficha'],
                            $data['fecha_asistencia']
                        );
                    }

                    // Verificar si ya existe una anomalía del mismo tipo para este aprendiz
                    if (!$this->anomaliaRepository->existeAnomalia(
                        $asistencia ? $asistencia['id'] : null,
                        $aprendiz['id_aprendiz'],
                        $data['id_ficha'],
                        $data['fecha_asistencia'],
                        $data['tipo_anomalia']
                    )) {
                        // Preparar datos para anomalía del aprendiz
                        $datosAnomaliaAprendiz = [
                            'id_asistencia' => $asistencia ? $asistencia['id'] : null,
                            'id_aprendiz' => $aprendiz['id_aprendiz'],
                            'id_ficha' => $data['id_ficha'],
                            'tipo_anomalia' => $data['tipo_anomalia'],
                            'descripcion' => $data['descripcion'] ?? null,
                            'registrado_por' => $usuarioId,
                            'fecha_asistencia' => $data['fecha_asistencia'],
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                        ];

                        // Registrar anomalía para el aprendiz
                        $this->anomaliaRepository->create($datosAnomaliaAprendiz);
                        $anomaliasRegistradas++;
                    }
                } catch (\Exception $e) {
                    error_log("Error registrando anomalía para aprendiz {$aprendiz['id_aprendiz']}: " . $e->getMessage());
                    $errores[] = "Error para aprendiz {$aprendiz['nombre_completo']}: " . $e->getMessage();
                }
            }

            // Log de operación crítica
            $this->logOperacionCritica('REGISTRO_ANOMALIA_FICHA', [
                'anomalia_id' => $anomaliaId,
                'ficha_id' => $data['id_ficha'],
                'tipo_anomalia' => $data['tipo_anomalia'],
                'fecha_asistencia' => $data['fecha_asistencia'],
                'usuario_id' => $usuarioId,
                'aprendices_afectados' => count($aprendicesNoPresentes),
                'anomalias_registradas' => $anomaliasRegistradas
            ]);

            $mensaje = "Anomalía de ficha registrada exitosamente. Se registró para {$anomaliasRegistradas} aprendiz(es) no presente(s).";
            if (!empty($errores)) {
                $mensaje .= " Algunos errores: " . implode('; ', array_slice($errores, 0, 3));
            }

            return [
                'success' => true,
                'message' => $mensaje,
                'id' => $anomaliaId,
                'data' => $datosAnomalia,
                'aprendices_afectados' => count($aprendicesNoPresentes),
                'anomalias_registradas' => $anomaliasRegistradas,
                'errores' => $errores
            ];

        } catch (\Exception $e) {
            error_log("Error registrando anomalía de ficha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al registrar anomalía: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene anomalías de un aprendiz para una fecha específica
     * 
     * @param int $aprendizId ID del aprendiz
     * @param int $fichaId ID de la ficha
     * @param string $fecha Fecha de asistencia (YYYY-MM-DD)
     * @return array Lista de anomalías
     */
    public function getAnomaliasAprendiz(int $aprendizId, int $fichaId, string $fecha): array
    {
        return $this->anomaliaRepository->findByAprendizAndFecha($aprendizId, $fichaId, $fecha);
    }

    /**
     * Obtiene anomalías generales de una ficha para una fecha específica
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fecha Fecha de asistencia (YYYY-MM-DD)
     * @return array Lista de anomalías
     */
    public function getAnomaliasFicha(int $fichaId, string $fecha): array
    {
        return $this->anomaliaRepository->findByFichaAndFecha($fichaId, $fecha);
    }

    /**
     * Log de operaciones críticas
     */
    private function logOperacionCritica(string $operacion, array $datos): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operacion' => $operacion,
            'datos' => $datos,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        error_log("OPERACION_CRITICA_ANOMALIA: " . json_encode($logEntry));
    }
}

