<?php

namespace App\GestionEquipos\Services;

use App\GestionEquipos\Repositories\QrEquipoRepository;
use App\GestionEquipos\Repositories\IngresoEquipoRepository;
use App\GestionEquipos\Repositories\AnomaliaEquipoRepository;
use App\GestionEquipos\Repositories\EquipoRepository;
use App\Repositories\AprendizRepository;
use App\Database\Connection;

/**
 * Servicio para gestión de ingresos/salidas de equipos por el portero
 */
class PorteroIngresoService
{
    private QrEquipoRepository $qrEquipoRepository;
    private IngresoEquipoRepository $ingresoEquipoRepository;
    private AnomaliaEquipoRepository $anomaliaEquipoRepository;
    private EquipoRepository $equipoRepository;
    private AprendizRepository $aprendizRepository;

    public function __construct(
        QrEquipoRepository $qrEquipoRepository,
        IngresoEquipoRepository $ingresoEquipoRepository,
        AnomaliaEquipoRepository $anomaliaEquipoRepository,
        EquipoRepository $equipoRepository,
        AprendizRepository $aprendizRepository
    ) {
        $this->qrEquipoRepository = $qrEquipoRepository;
        $this->ingresoEquipoRepository = $ingresoEquipoRepository;
        $this->anomaliaEquipoRepository = $anomaliaEquipoRepository;
        $this->equipoRepository = $equipoRepository;
        $this->aprendizRepository = $aprendizRepository;
    }

    /**
     * Valida un QR escaneado y procesa ingreso o salida
     * 
     * @param string $qrData Datos del QR escaneado (JSON o token)
     * @param int $porteroId ID del usuario portero que realiza la operación
     * @param string|null $observaciones Observaciones opcionales
     * @return array Resultado de la operación
     */
    public function procesarQR(string $qrData, int $porteroId, ?string $observaciones = null): array
    {
        try {
            // Intentar parsear como JSON primero
            $qrInfo = json_decode($qrData, true);
            $qrEquipo = null;
            
            if (!$qrInfo && is_string($qrData)) {
                // Si no es JSON, buscar por token directamente
                $qrEquipo = $this->qrEquipoRepository->findByToken($qrData);
                if (!$qrEquipo) {
                    return [
                        'success' => false,
                        'message' => 'QR no válido o no encontrado',
                        'type' => 'error'
                    ];
                }
                $qrInfo = json_decode($qrEquipo['qr_data'], true);
                if (!$qrInfo) {
                    return [
                        'success' => false,
                        'message' => 'Datos del QR inválidos',
                        'type' => 'error'
                    ];
                }
            } else {
                // Si es JSON, verificar si tiene token o datos directos
                $token = $qrInfo['token'] ?? null;
                
                if ($token) {
                    // Si tiene token, buscar el QR por token
                    $qrEquipo = $this->qrEquipoRepository->findByToken($token);
                    if (!$qrEquipo) {
                        return [
                            'success' => false,
                            'message' => 'QR no válido o inactivo',
                            'type' => 'error'
                        ];
                    }
                } else {
                    // Si no tiene token pero tiene equipo_id y aprendiz_id, buscar QR activo
                    $equipoId = (int)($qrInfo['equipo_id'] ?? 0);
                    $aprendizId = (int)($qrInfo['aprendiz_id'] ?? 0);
                    
                    if ($equipoId > 0 && $aprendizId > 0) {
                        $qrEquipo = $this->qrEquipoRepository->findActiveByEquipoAndAprendiz($equipoId, $aprendizId);
                        // Si no se encuentra el QR en la BD, continuar con los datos del JSON
                        // (permite procesar QRs generados externamente)
                    } else {
                        return [
                            'success' => false,
                            'message' => 'QR inválido: faltan datos de equipo o aprendiz',
                            'type' => 'error'
                        ];
                    }
                }
            }

            // Si se encontró un QR en la BD, validar que esté activo
            if ($qrEquipo) {
                if (!$qrEquipo['activo']) {
                    return [
                        'success' => false,
                        'message' => 'Este QR ha sido desactivado',
                        'type' => 'error'
                    ];
                }

                // Validar expiración si existe
                if (!empty($qrEquipo['fecha_expiracion'])) {
                    $fechaExpiracion = strtotime($qrEquipo['fecha_expiracion']);
                    if (time() > $fechaExpiracion) {
                        return [
                            'success' => false,
                            'message' => 'Este QR ha expirado',
                            'type' => 'error'
                        ];
                    }
                }
            }

            // Obtener IDs del equipo y aprendiz
            $equipoId = (int)($qrInfo['equipo_id'] ?? ($qrEquipo['id_equipo'] ?? 0));
            $aprendizId = (int)($qrInfo['aprendiz_id'] ?? ($qrEquipo['id_aprendiz'] ?? 0));
            
            if ($equipoId <= 0 || $aprendizId <= 0) {
                return [
                    'success' => false,
                    'message' => 'Datos incompletos: faltan ID de equipo o aprendiz',
                    'type' => 'error'
                ];
            }

            // Verificar que el equipo existe
            $equipo = $this->equipoRepository->findById($equipoId);
            if (!$equipo) {
                return [
                    'success' => false,
                    'message' => 'Equipo no encontrado',
                    'type' => 'error'
                ];
            }

            // Verificar que el aprendiz existe
            $aprendiz = $this->aprendizRepository->findById($aprendizId);
            if (!$aprendiz) {
                return [
                    'success' => false,
                    'message' => 'Aprendiz no encontrado',
                    'type' => 'error'
                ];
            }

            // Verificar si hay un ingreso activo (sin salida)
            $ingresoActivo = $this->ingresoEquipoRepository->findIngresoActivo($equipoId);

            if ($ingresoActivo) {
                // Hay un ingreso activo, registrar salida
                return $this->registrarSalida($ingresoActivo['id'], $porteroId, $observaciones, $equipo, $aprendiz);
            } else {
                // No hay ingreso activo, registrar ingreso
                return $this->registrarIngreso($equipoId, $aprendizId, $porteroId, $observaciones, $equipo, $aprendiz);
            }
        } catch (\Exception $e) {
            error_log("Error processing QR: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el QR: ' . $e->getMessage(),
                'type' => 'error'
            ];
        }
    }

    /**
     * Registra un ingreso de equipo
     */
    private function registrarIngreso(
        int $equipoId,
        int $aprendizId,
        int $porteroId,
        ?string $observaciones,
        array $equipo,
        array $aprendiz
    ): array {
        try {
            Connection::beginTransaction();

            $ingresoId = $this->ingresoEquipoRepository->create([
                'id_equipo' => $equipoId,
                'id_aprendiz' => $aprendizId,
                'fecha_ingreso' => date('Y-m-d'),
                'hora_ingreso' => date('H:i:s'),
                'id_portero' => $porteroId,
                'observaciones' => $observaciones,
            ]);

            Connection::commit();

            return [
                'success' => true,
                'message' => "Ingreso registrado: {$equipo['marca']} - Serial: {$equipo['numero_serial']}",
                'type' => 'ingreso',
                'data' => [
                    'id' => $ingresoId,
                    'ingreso_id' => $ingresoId,
                    'equipo' => $equipo,
                    'aprendiz' => $aprendiz,
                    'fecha' => date('Y-m-d'),
                    'hora' => date('H:i:s'),
                    'tipo' => 'ingreso',
                ]
            ];
        } catch (\Exception $e) {
            Connection::rollBack();
            error_log("Error registering ingreso: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al registrar el ingreso',
                'type' => 'error'
            ];
        }
    }

    /**
     * Registra una salida de equipo
     */
    private function registrarSalida(
        int $ingresoId,
        int $porteroId,
        ?string $observaciones,
        array $equipo,
        array $aprendiz
    ): array {
        try {
            Connection::beginTransaction();

            $success = $this->ingresoEquipoRepository->registrarSalida($ingresoId, $porteroId, $observaciones);

            if (!$success) {
                Connection::rollBack();
                return [
                    'success' => false,
                    'message' => 'Error al registrar la salida',
                    'type' => 'error'
                ];
            }

            Connection::commit();

            // Obtener el ingreso actualizado para incluir fecha y hora de salida
            $ingresoActualizado = $this->ingresoEquipoRepository->findById($ingresoId);
            
            return [
                'success' => true,
                'message' => "Salida registrada: {$equipo['marca']} - Serial: {$equipo['numero_serial']}",
                'type' => 'salida',
                'data' => [
                    'id' => $ingresoId,
                    'ingreso_id' => $ingresoId,
                    'equipo' => $equipo,
                    'aprendiz' => $aprendiz,
                    'fecha' => $ingresoActualizado['fecha_salida'] ?? date('Y-m-d'),
                    'hora' => $ingresoActualizado['hora_salida'] ?? date('H:i:s'),
                    'tipo' => 'salida',
                ]
            ];
        } catch (\Exception $e) {
            Connection::rollBack();
            error_log("Error registering salida: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al registrar la salida',
                'type' => 'error'
            ];
        }
    }

    /**
     * Obtiene ingresos activos (sin salida)
     */
    public function getIngresosActivos(int $limit = 50, int $offset = 0): array
    {
        return $this->ingresoEquipoRepository->findIngresosActivos($limit, $offset);
    }

    /**
     * Obtiene el conteo de ingresos activos
     */
    public function countIngresosActivos(): int
    {
        return $this->ingresoEquipoRepository->countIngresosActivos();
    }

    /**
     * Detecta anomalías: equipos que ingresaron pero no han salido
     * (útil para reportes o detección automática)
     */
    public function detectarAnomalias(int $administrativoId): array
    {
        try {
            $ingresosActivos = $this->ingresoEquipoRepository->findIngresosActivos(1000, 0);
            $anomaliasCreadas = 0;
            $errores = [];

            foreach ($ingresosActivos as $ingreso) {
                // Verificar si ya existe una anomalía para este ingreso
                $anomaliaExistente = $this->anomaliaEquipoRepository->findById($ingreso['id']);
                
                if (!$anomaliaExistente || $anomaliaExistente['resuelta']) {
                    // Calcular tiempo transcurrido desde el ingreso
                    $fechaIngreso = strtotime($ingreso['fecha_ingreso'] . ' ' . $ingreso['hora_ingreso']);
                    $tiempoTranscurrido = time() - $fechaIngreso;
                    $horasTranscurridas = $tiempoTranscurrido / 3600;

                    // Si han pasado más de 8 horas, crear anomalía
                    if ($horasTranscurridas > 8) {
                        try {
                            $this->anomaliaEquipoRepository->create([
                                'id_ingreso' => $ingreso['id'],
                                'descripcion' => "Equipo ingresó hace {$horasTranscurridas} horas y no ha registrado salida.",
                                'id_administrativo_gestor' => $administrativoId,
                            ]);
                            $anomaliasCreadas++;
                        } catch (\Exception $e) {
                            $errores[] = "Error creando anomalía para ingreso {$ingreso['id']}: " . $e->getMessage();
                        }
                    }
                }
            }

            return [
                'success' => true,
                'anomalias_creadas' => $anomaliasCreadas,
                'errores' => $errores,
            ];
        } catch (\Exception $e) {
            error_log("Error detecting anomalies: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al detectar anomalías: ' . $e->getMessage(),
            ];
        }
    }
}

