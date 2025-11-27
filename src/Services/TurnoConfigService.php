<?php

namespace App\Services;

use App\Repositories\TurnoConfigRepository;
use App\Exceptions\ValidationException;

/**
 * Servicio de lógica de negocio para configuración de turnos
 * Gestiona la validación y determinación de turnos y tardanzas
 * 
 * @author Sistema de Configuración Dinámica
 * @version 1.0
 */
class TurnoConfigService
{
    private TurnoConfigRepository $turnoConfigRepository;

    public function __construct(TurnoConfigRepository $turnoConfigRepository)
    {
        $this->turnoConfigRepository = $turnoConfigRepository;
    }

    /**
     * Obtiene todas las configuraciones de turnos activos
     * 
     * @return array Lista de turnos con su configuración
     */
    public function obtenerConfiguracionTurnos(): array
    {
        return $this->turnoConfigRepository->findAllActive();
    }

    /**
     * Obtiene todos los turnos (activos e inactivos)
     * 
     * @return array Lista completa de turnos
     */
    public function obtenerTodosTurnos(): array
    {
        return $this->turnoConfigRepository->findAll();
    }

    /**
     * Determina el turno actual basado en una hora específica
     * 
     * @param string|null $hora Hora en formato H:i:s (si es null, usa hora actual)
     * @return array|null Configuración del turno o null si no hay turno activo
     */
    public function obtenerTurnoActual(?string $hora = null): ?array
    {
        if ($hora === null) {
            $hora = date('H:i:s');
        }

        // Validar formato de hora
        if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $hora)) {
            error_log("TurnoConfigService::obtenerTurnoActual - Formato de hora inválido: {$hora}");
            return null;
        }

        return $this->turnoConfigRepository->findByHora($hora);
    }

    /**
     * Valida si una hora específica representa una tardanza para un turno
     * 
     * @param string $hora Hora a validar en formato H:i:s
     * @param string $nombreTurno Nombre del turno (Mañana, Tarde, Noche)
     * @return bool True si es tardanza, False si es puntual
     */
    public function validarTardanza(string $hora, string $nombreTurno): bool
    {
        $turno = $this->turnoConfigRepository->findByNombre($nombreTurno);
        
        if (!$turno) {
            error_log("TurnoConfigService::validarTardanza - Turno no encontrado: {$nombreTurno}");
            return false;
        }

        // Si la hora es mayor que la hora límite de llegada, es tardanza
        return $hora > $turno['hora_limite_llegada'];
    }

    /**
     * Determina automáticamente el estado de asistencia (presente o tardanza)
     * basado en la hora y el turno correspondiente
     * 
     * @param string|null $hora Hora de registro (si es null, usa hora actual)
     * @return string Estado: 'presente' o 'tardanza'
     */
    public function determinarEstadoAsistencia(?string $hora = null): string
    {
        if ($hora === null) {
            $hora = date('H:i:s');
        }

        $turno = $this->obtenerTurnoActual($hora);
        
        if (!$turno) {
            // Si no hay turno activo, marcar como presente por defecto
            error_log("TurnoConfigService::determinarEstadoAsistencia - No hay turno activo para hora: {$hora}");
            return 'presente';
        }

        // Validar si es tardanza
        if ($this->validarTardanza($hora, $turno['nombre_turno'])) {
            return 'tardanza';
        }

        return 'presente';
    }

    /**
     * Actualiza la configuración de múltiples turnos
     * Solo accesible para usuarios con rol Admin
     * 
     * @param array $turnos Array de turnos con su configuración
     * @param int $usuarioId ID del usuario que realiza la actualización
     * @return array Resultado de la operación
     * @throws ValidationException Si hay errores de validación
     */
    public function actualizarConfiguracion(array $turnos, int $usuarioId): array
    {
        try {
            // Validar permisos del usuario (debe ser Admin)
            if (!$this->validarPermisosAdmin($usuarioId)) {
                throw new ValidationException('usuario', 'Solo los administradores pueden modificar la configuración de turnos');
            }

            // Validar datos de entrada
            $errores = $this->validarDatosConfiguracion($turnos);
            if (!empty($errores)) {
                throw ValidationException::withMultipleErrors($errores, 'Errores de validación en configuración de turnos');
            }

            // Actualizar en la base de datos
            $resultado = $this->turnoConfigRepository->updateMultiple($turnos);

            if (!$resultado) {
                throw new \RuntimeException('Error al actualizar la configuración en la base de datos');
            }

            // Log de operación crítica
            $this->logOperacionCritica('ACTUALIZACION_CONFIG_TURNOS', [
                'usuario_id' => $usuarioId,
                'turnos_actualizados' => count($turnos),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'message' => 'Configuración de turnos actualizada exitosamente',
                'turnos_actualizados' => count($turnos)
            ];

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'VALIDATION_ERROR',
                'details' => $e->toArray()
            ];
        } catch (\Exception $e) {
            error_log("Error crítico en actualizarConfiguracion: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error interno del sistema. Contacte al administrador.',
                'error_type' => 'SYSTEM_ERROR'
            ];
        }
    }

    /**
     * Valida que el usuario tenga permisos de administrador
     * 
     * @param int $usuarioId ID del usuario
     * @return bool True si es admin
     */
    private function validarPermisosAdmin(int $usuarioId): bool
    {
        try {
            $stmt = \App\Database\Connection::prepare(
                'SELECT rol FROM usuarios WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $usuarioId]);
            $usuario = $stmt->fetch();

            return $usuario && $usuario['rol'] === 'admin';

        } catch (\PDOException $e) {
            error_log("Error validando permisos admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida los datos de configuración de turnos
     * 
     * @param array $turnos Array de turnos a validar
     * @return array Array de errores (vacío si no hay errores)
     */
    private function validarDatosConfiguracion(array $turnos): array
    {
        $errores = [];
        $turnosValidos = ['Mañana', 'Tarde', 'Noche'];

        foreach ($turnos as $index => $turno) {
            $turnoKey = "turno_{$index}";

            // Validar nombre de turno
            if (empty($turno['nombre_turno']) || !in_array($turno['nombre_turno'], $turnosValidos)) {
                $errores["{$turnoKey}_nombre"] = "Nombre de turno inválido";
            }

            // Validar formato de horas
            if (empty($turno['hora_inicio']) || !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $turno['hora_inicio'])) {
                $errores["{$turnoKey}_inicio"] = "Formato de hora de inicio inválido";
            }

            if (empty($turno['hora_fin']) || !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $turno['hora_fin'])) {
                $errores["{$turnoKey}_fin"] = "Formato de hora de fin inválido";
            }

            if (empty($turno['hora_limite_llegada']) || !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $turno['hora_limite_llegada'])) {
                $errores["{$turnoKey}_limite"] = "Formato de hora límite inválido";
            }

            // Validar lógica de negocio
            if (isset($turno['hora_inicio']) && isset($turno['hora_fin'])) {
                if ($turno['hora_inicio'] >= $turno['hora_fin']) {
                    $errores["{$turnoKey}_logica"] = "La hora de inicio debe ser menor que la hora de fin";
                }
            }

            if (isset($turno['hora_inicio']) && isset($turno['hora_limite_llegada']) && isset($turno['hora_fin'])) {
                if ($turno['hora_limite_llegada'] < $turno['hora_inicio'] || $turno['hora_limite_llegada'] > $turno['hora_fin']) {
                    $errores["{$turnoKey}_limite_rango"] = "La hora límite debe estar entre la hora de inicio y fin";
                }
            }
        }

        return $errores;
    }

    /**
     * Log de operaciones críticas
     * 
     * @param string $operacion Nombre de la operación
     * @param array $datos Datos de la operación
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

        error_log("TURNO_CONFIG_OPERACION_CRITICA: " . json_encode($logEntry));
    }
}
