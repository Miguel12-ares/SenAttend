<?php

namespace App\Services;

use App\Repositories\AsistenciaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\FichaRepository;
use App\Exceptions\DuplicateAsistenciaException;
use App\Exceptions\ValidationException;
use App\Database\Connection;
use RuntimeException;
use PDOException;
use PDO;
use Exception;

/**
 * Servicio optimizado de lógica de negocio para Asistencias
 * Sprint 4 - Registro Manual de Asistencia
 * Dev 2: AsistenciaService (Líder Fase)
 * 
 * @author Dev 2 - AsistenciaService Líder
 * @version 2.0
 */
class AsistenciaService
{
    private AsistenciaRepository $asistenciaRepository;
    private AprendizRepository $aprendizRepository;
    private FichaRepository $fichaRepository;

    public function __construct(
        AsistenciaRepository $asistenciaRepository,
        AprendizRepository $aprendizRepository,
        FichaRepository $fichaRepository
    ) {
        $this->asistenciaRepository = $asistenciaRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Registra asistencia de un aprendiz con validaciones completas
     * Dev 2: Método optimizado con excepciones específicas y RBAC
     * 
     * @param array $data Datos de la asistencia
     * @param int $usuarioId ID del usuario que registra
     * @return array Resultado estructurado con éxito/error
     * @throws DuplicateAsistenciaException Si ya existe registro
     * @throws ValidationException Si hay errores de validación
     */
    public function registrarAsistencia(array $data, int $usuarioId = null): array
    {
        try {
            // Validar permisos del usuario que registra (middleware RBAC)
            if ($usuarioId && !$this->validarPermisosUsuario($usuarioId, 'registrar_asistencia')) {
                throw new ValidationException('usuario', 'Usuario no tiene permisos para registrar asistencia');
            }

            // Validar datos de entrada
            $this->validarDatosRegistro($data);

            // Validar estado de aprendiz, ficha y sesión activos
            $this->validarEntidadesActivas($data['id_aprendiz'], $data['id_ficha']);

            // Validar duplicados antes de insertar
            if ($this->asistenciaRepository->existe(
                $data['id_aprendiz'],
                $data['id_ficha'],
                $data['fecha']
            )) {
                throw new DuplicateAsistenciaException(
                    $data['id_aprendiz'],
                    $data['id_ficha'],
                    $data['fecha']
                );
            }

            // Procesar hora si no está definida, pero respetar el estado manual
            if (!isset($data['hora'])) {
                $data['hora'] = date('H:i:s');
            }
            
            // NO aplicar lógica automática de tardanza en registro manual
            // El instructor decide conscientemente el estado

            // Agregar usuario que registra
            $data['registrado_por'] = $usuarioId ?? $data['registrado_por'];

            // Registrar asistencia usando el repository optimizado
            $id = $this->asistenciaRepository->registrarAsistencia($data);

            // Log de operación crítica
            $this->logOperacionCritica('REGISTRO_ASISTENCIA', [
                'asistencia_id' => $id,
                'aprendiz_id' => $data['id_aprendiz'],
                'ficha_id' => $data['id_ficha'],
                'estado' => $data['estado'],
                'usuario_id' => $data['registrado_por']
            ]);

            return [
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'id' => $id,
                'data' => [
                    'aprendiz_id' => $data['id_aprendiz'],
                    'estado' => $data['estado'],
                    'fecha' => $data['fecha'],
                    'hora' => $data['hora']
                ]
            ];

        } catch (DuplicateAsistenciaException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'DUPLICATE_ASISTENCIA',
                'details' => $e->toArray()
            ];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'VALIDATION_ERROR',
                'details' => $e->toArray()
            ];
        } catch (RuntimeException $e) {
            // Log error crítico sin exponer información sensible
            error_log("Error crítico en registrarAsistencia: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error interno del sistema. Contacte al administrador.',
                'error_type' => 'SYSTEM_ERROR'
            ];
        }
    }

    /**
     * Registra asistencia masiva para una ficha
     * Recibe un array de aprendices con sus estados
     */
    public function registrarAsistenciaMasiva(int $fichaId, string $fecha, array $asistencias, int $registradoPor): array
    {
        $exitosos = 0;
        $errores = [];
        $hora = date('H:i:s');

        foreach ($asistencias as $asistencia) {
            $result = $this->registrarAsistencia([
                'id_aprendiz' => $asistencia['id_aprendiz'],
                'id_ficha' => $fichaId,
                'fecha' => $fecha,
                'hora' => $hora,
                'estado' => $asistencia['estado'],
                'registrado_por' => $registradoPor,
            ]);

            if ($result['success']) {
                $exitosos++;
            } else {
                $errores[] = "Aprendiz {$asistencia['id_aprendiz']}: {$result['message']}";
            }
        }

        return [
            'success' => $exitosos > 0,
            'total' => count($asistencias),
            'exitosos' => $exitosos,
            'errores' => $errores,
            'message' => "Se registraron {$exitosos} de " . count($asistencias) . " asistencias"
        ];
    }

    /**
     * Modifica el estado de una asistencia existente con auditoría completa
     * Dev 2: Método optimizado con validación de transición y auditoría
     * 
     * @param int $asistenciaId ID de la asistencia a modificar
     * @param string $nuevoEstado Nuevo estado (presente, ausente, tardanza)
     * @param int $modificadoPor ID del usuario que modifica
     * @param string $motivo Motivo del cambio
     * @return array Resultado estructurado
     */
    public function modificarEstadoAsistencia(int $asistenciaId, string $nuevoEstado, int $modificadoPor, string $motivo = ''): array
    {
        try {
            // Validar permisos del usuario
            if (!$this->validarPermisosUsuario($modificadoPor, 'modificar_asistencia')) {
                throw new ValidationException('usuario', 'Usuario no tiene permisos para modificar asistencia');
            }

            // Validar estado
            if (!in_array($nuevoEstado, ['presente', 'ausente', 'tardanza'])) {
                throw new ValidationException('estado', 'Estado debe ser: presente, ausente o tardanza');
            }

            // Verificar que existe la asistencia
            $asistencia = $this->asistenciaRepository->findById($asistenciaId);
            if (!$asistencia) {
                throw new ValidationException('asistencia', 'Registro de asistencia no encontrado');
            }

            $estadoAnterior = $asistencia['estado'];

            // Validar transición de estados (no permitir cambio después de X horas)
            if (!$this->validarTransicionEstado($asistencia, $nuevoEstado)) {
                throw new ValidationException('transicion', 'No se permite cambiar el estado después del tiempo límite');
            }

            // Si el estado es el mismo, no hacer nada
            if ($estadoAnterior === $nuevoEstado) {
                return [
                    'success' => true,
                    'message' => 'El estado ya es el solicitado',
                    'no_change' => true
                ];
            }

            Connection::beginTransaction();

            try {
                // Actualizar estado en la tabla principal
                $resultado = $this->asistenciaRepository->updateEstado(
                    $asistenciaId,
                    $nuevoEstado,
                    $modificadoPor
                );

                if (!$resultado) {
                    throw new RuntimeException('Error al actualizar el estado en la base de datos');
                }

                // Insertar registro en tabla de auditoría
                $this->insertarCambioAuditoria([
                    'id_asistencia' => $asistenciaId,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $nuevoEstado,
                    'motivo_cambio' => $motivo,
                    'modificado_por' => $modificadoPor,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);

                Connection::commit();

                // Log de operación crítica
                $this->logOperacionCritica('MODIFICACION_ASISTENCIA', [
                    'asistencia_id' => $asistenciaId,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $nuevoEstado,
                    'modificado_por' => $modificadoPor,
                    'motivo' => $motivo
                ]);

                // Notificar cambio vía evento (preparar para WebSocket futuro)
                $this->notificarCambioAsistencia($asistenciaId, $estadoAnterior, $nuevoEstado);

                return [
                    'success' => true,
                    'message' => 'Estado de asistencia actualizado exitosamente',
                    'data' => [
                        'asistencia_id' => $asistenciaId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $nuevoEstado,
                        'modificado_por' => $modificadoPor,
                        'fecha_cambio' => date('Y-m-d H:i:s')
                    ]
                ];

            } catch (Exception $e) {
                Connection::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'VALIDATION_ERROR',
                'details' => $e->toArray()
            ];
        } catch (RuntimeException $e) {
            error_log("Error crítico en modificarEstadoAsistencia: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error interno del sistema. Contacte al administrador.',
                'error_type' => 'SYSTEM_ERROR'
            ];
        }
    }

    /**
     * Obtiene aprendices de una ficha con su estado de asistencia
     * Para mostrar en la interfaz de registro
     * Dev 2: Método optimizado usando repository mejorado
     */
    public function getAprendicesParaRegistro(int $fichaId, string $fecha): array
    {
        return $this->asistenciaRepository->getAprendicesPorFichaConAsistenciaDelDia($fichaId, $fecha);
    }

    /**
     * Obtiene estadísticas de asistencia
     */
    public function getEstadisticas(int $fichaId, string $fecha): array
    {
        $stats = $this->asistenciaRepository->getEstadisticas($fichaId, $fecha);
        
        // Calcular porcentajes
        if ($stats['total'] > 0) {
            $stats['porcentaje_presentes'] = round(($stats['presentes'] / $stats['total']) * 100, 2);
            $stats['porcentaje_ausentes'] = round(($stats['ausentes'] / $stats['total']) * 100, 2);
            $stats['porcentaje_tardanzas'] = round(($stats['tardanzas'] / $stats['total']) * 100, 2);
        } else {
            $stats['porcentaje_presentes'] = 0;
            $stats['porcentaje_ausentes'] = 0;
            $stats['porcentaje_tardanzas'] = 0;
        }

        return $stats;
    }

    /**
     * Valida que se puedan registrar asistencias para una fecha
     * Reglas de negocio:
     * - No permitir registros futuros
     * - Permitir modificaciones del día actual y hasta 7 días atrás (configurable)
     */
    public function validarFechaRegistro(string $fecha): array
    {
        $hoy = date('Y-m-d');
        $fechaRegistro = date('Y-m-d', strtotime($fecha));

        // No permitir fechas futuras
        if ($fechaRegistro > $hoy) {
            return [
                'valido' => false,
                'mensaje' => 'No se pueden registrar asistencias para fechas futuras'
            ];
        }

        // Verificar límite de días hacia atrás (7 días)
        $diasAtras = (strtotime($hoy) - strtotime($fechaRegistro)) / 86400;
        if ($diasAtras > 7) {
            return [
                'valido' => false,
                'mensaje' => 'Solo se pueden registrar asistencias de los últimos 7 días'
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'Fecha válida para registro'
        ];
    }

    /**
     * Obtiene historial de cambios de asistencia con filtros
     * Dev 2: Lógica de cambios auditados
     * 
     * @param array $filtros Filtros: aprendiz_id, ficha_id, fecha_inicio, fecha_fin
     * @return array Historial de cambios
     */
    public function obtenerHistorialCambios(array $filtros = []): array
    {
        try {
            $whereConditions = [];
            $params = [];

            // Construir condiciones WHERE dinámicamente
            if (!empty($filtros['aprendiz_id'])) {
                $whereConditions[] = 'a.id_aprendiz = :aprendiz_id';
                $params['aprendiz_id'] = $filtros['aprendiz_id'];
            }

            if (!empty($filtros['ficha_id'])) {
                $whereConditions[] = 'a.id_ficha = :ficha_id';
                $params['ficha_id'] = $filtros['ficha_id'];
            }

            if (!empty($filtros['fecha_inicio'])) {
                $whereConditions[] = 'ca.fecha_cambio >= :fecha_inicio';
                $params['fecha_inicio'] = $filtros['fecha_inicio'] . ' 00:00:00';
            }

            if (!empty($filtros['fecha_fin'])) {
                $whereConditions[] = 'ca.fecha_cambio <= :fecha_fin';
                $params['fecha_fin'] = $filtros['fecha_fin'] . ' 23:59:59';
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $stmt = Connection::prepare(
                "SELECT 
                    ca.id,
                    ca.id_asistencia,
                    ca.estado_anterior,
                    ca.estado_nuevo,
                    ca.motivo_cambio,
                    ca.fecha_cambio,
                    ca.ip_address,
                    u.nombre as modificado_por_nombre,
                    u.rol as modificado_por_rol,
                    ap.documento as aprendiz_documento,
                    ap.nombre as aprendiz_nombre,
                    ap.apellido as aprendiz_apellido,
                    f.numero_ficha,
                    a.fecha as fecha_asistencia
                 FROM cambios_asistencia ca
                 INNER JOIN usuarios u ON ca.modificado_por = u.id
                 INNER JOIN asistencias a ON ca.id_asistencia = a.id
                 INNER JOIN aprendices ap ON a.id_aprendiz = ap.id
                 INNER JOIN fichas f ON a.id_ficha = f.id
                 {$whereClause}
                 ORDER BY ca.fecha_cambio DESC
                 LIMIT 100"
            );

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo historial de cambios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida permisos del usuario para operaciones de asistencia
     * Dev 2: Middleware RBAC integrado
     */
    private function validarPermisosUsuario(int $usuarioId, string $accion): bool
    {
        try {
            $stmt = Connection::prepare(
                'SELECT rol FROM usuarios WHERE id = :id AND id IS NOT NULL'
            );
            $stmt->execute(['id' => $usuarioId]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                return false;
            }

            // Definir permisos por rol
            $permisos = [
                'admin' => ['registrar_asistencia', 'modificar_asistencia', 'ver_historial'],
                'coordinador' => ['registrar_asistencia', 'modificar_asistencia', 'ver_historial'],
                'instructor' => ['registrar_asistencia', 'modificar_asistencia']
            ];

            return in_array($accion, $permisos[$usuario['rol']] ?? []);

        } catch (PDOException $e) {
            error_log("Error validando permisos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida datos de entrada para registro
     */
    private function validarDatosRegistro(array $data): void
    {
        $errores = [];

        if (empty($data['id_aprendiz']) || !is_int($data['id_aprendiz'])) {
            $errores['id_aprendiz'] = 'ID de aprendiz es requerido y debe ser entero';
        }

        if (empty($data['id_ficha']) || !is_int($data['id_ficha'])) {
            $errores['id_ficha'] = 'ID de ficha es requerido y debe ser entero';
        }

        if (empty($data['fecha']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
            $errores['fecha'] = 'Fecha es requerida y debe estar en formato Y-m-d';
        }

        if (empty($data['estado']) || !in_array($data['estado'], ['presente', 'ausente', 'tardanza'])) {
            $errores['estado'] = 'Estado debe ser: presente, ausente o tardanza';
        }

        if (!empty($errores)) {
            throw ValidationException::withMultipleErrors($errores, 'Errores de validación en datos de registro');
        }
    }

    /**
     * Valida que las entidades estén activas
     */
    private function validarEntidadesActivas(int $aprendizId, int $fichaId): void
    {
        // Validar aprendiz activo
        $aprendiz = $this->aprendizRepository->findById($aprendizId);
        if (!$aprendiz || $aprendiz['estado'] !== 'activo') {
            throw new ValidationException('aprendiz', 'El aprendiz no existe o no está activo');
        }

        // Validar ficha activa
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha || $ficha['estado'] !== 'activa') {
            throw new ValidationException('ficha', 'La ficha no existe o no está activa');
        }
    }

    /**
     * Procesa el estado de tardanza automáticamente
     * SOLO para registro automático (escaneo de carnets, etc.)
     * NO usar en registro manual donde el instructor decide el estado
     */
    private function procesarEstadoTardanzaAutomatico(array $data): array
    {
        if (!isset($data['hora'])) {
            $data['hora'] = date('H:i:s');
        }

        // Lógica de tardanza configurable (7:30 AM por defecto)
        $horaLimite = '07:30:00';
        if ($data['estado'] === 'presente' && $data['hora'] > $horaLimite) {
            $data['estado'] = 'tardanza';
        }

        return $data;
    }

    /**
     * Aplica lógica de tardanza automática para registro por escaneo
     * Usar este método cuando el sistema detecta automáticamente la asistencia
     */
    public function registrarAsistenciaAutomatica(array $data, int $usuarioId = null): array
    {
        // Aplicar lógica automática de tardanza
        $data = $this->procesarEstadoTardanzaAutomatico($data);
        
        // Usar el método normal de registro
        return $this->registrarAsistencia($data, $usuarioId);
    }

    /**
     * Valida transición de estados con ventana temporal
     */
    private function validarTransicionEstado(array $asistencia, string $nuevoEstado): bool
    {
        // Ventana temporal configurable: 24 horas
        $ventanaHoras = 24;
        $fechaLimite = date('Y-m-d H:i:s', strtotime($asistencia['created_at'] . " +{$ventanaHoras} hours"));
        
        return date('Y-m-d H:i:s') <= $fechaLimite;
    }

    /**
     * Inserta registro de cambio en tabla de auditoría
     */
    private function insertarCambioAuditoria(array $data): void
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO cambios_asistencia 
                 (id_asistencia, estado_anterior, estado_nuevo, motivo_cambio, modificado_por, ip_address, user_agent)
                 VALUES (:id_asistencia, :estado_anterior, :estado_nuevo, :motivo_cambio, :modificado_por, :ip_address, :user_agent)'
            );

            $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Error insertando cambio de auditoría: " . $e->getMessage());
            throw new RuntimeException('Error registrando cambio en auditoría');
        }
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

        error_log("OPERACION_CRITICA: " . json_encode($logEntry));
    }

    /**
     * Notifica cambio de asistencia (preparar para WebSocket futuro)
     */
    private function notificarCambioAsistencia(int $asistenciaId, string $estadoAnterior, string $estadoNuevo): void
    {
        // TODO: Implementar notificación WebSocket en futuras versiones
        // Por ahora, solo log
        error_log("CAMBIO_ASISTENCIA: ID:{$asistenciaId}, {$estadoAnterior} -> {$estadoNuevo}");
    }

    /**
     * Método legacy para compatibilidad - usar modificarEstadoAsistencia()
     * @deprecated
     */
    public function modificarEstado(int $asistenciaId, string $nuevoEstado, int $modificadoPor, string $motivo = ''): array
    {
        return $this->modificarEstadoAsistencia($asistenciaId, $nuevoEstado, $modificadoPor, $motivo);
    }
}

