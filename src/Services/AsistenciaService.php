<?php

namespace App\Services;

use App\Repositories\AsistenciaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\FichaRepository;

/**
 * Servicio de lógica de negocio para Asistencias
 * Sprint 4 - Registro Manual de Asistencia
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
     * Registra asistencia de un aprendiz
     * Validaciones:
     * - Ficha debe existir y estar activa
     * - Aprendiz debe existir y estar activo
     * - No debe existir registro duplicado
     */
    public function registrarAsistencia(array $data): array
    {
        // Validar ficha
        $ficha = $this->fichaRepository->findById($data['id_ficha']);
        if (!$ficha) {
            return ['success' => false, 'message' => 'La ficha no existe'];
        }
        if ($ficha['estado'] !== 'activa') {
            return ['success' => false, 'message' => 'La ficha no está activa'];
        }

        // Validar aprendiz
        $aprendiz = $this->aprendizRepository->findById($data['id_aprendiz']);
        if (!$aprendiz) {
            return ['success' => false, 'message' => 'El aprendiz no existe'];
        }
        if ($aprendiz['estado'] !== 'activo') {
            return ['success' => false, 'message' => 'El aprendiz no está activo'];
        }

        // Verificar si ya existe registro (doble validación además de UNIQUE KEY)
        if ($this->asistenciaRepository->existe(
            $data['id_aprendiz'],
            $data['id_ficha'],
            $data['fecha']
        )) {
            return [
                'success' => false,
                'message' => 'Ya existe un registro de asistencia para este aprendiz en esta fecha'
            ];
        }

        // Determinar estado de tardanza si se proporciona hora
        if (isset($data['hora'])) {
            $hora = strtotime($data['hora']);
            $horaLimite = strtotime('07:30:00'); // Configurable

            if ($hora > $horaLimite && $data['estado'] === 'presente') {
                $data['estado'] = 'tardanza';
            }
        } else {
            $data['hora'] = date('H:i:s');
        }

        // Registrar asistencia
        try {
            $id = $this->asistenciaRepository->create($data);

            return [
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'id' => $id
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar asistencia: ' . $e->getMessage()
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
     * Modifica el estado de una asistencia existente
     * Validaciones de auditoría
     */
    public function modificarEstado(int $asistenciaId, string $nuevoEstado, int $modificadoPor, string $motivo = ''): array
    {
        // Validar estado
        if (!in_array($nuevoEstado, ['presente', 'ausente', 'tardanza'])) {
            return ['success' => false, 'message' => 'Estado inválido'];
        }

        // Verificar que existe
        $asistencia = $this->asistenciaRepository->findById($asistenciaId);
        if (!$asistencia) {
            return ['success' => false, 'message' => 'Registro de asistencia no encontrado'];
        }

        // Actualizar estado
        $resultado = $this->asistenciaRepository->updateEstado(
            $asistenciaId,
            $nuevoEstado,
            $modificadoPor
        );

        if ($resultado) {
            // TODO: Registrar en tabla de auditoría si se implementa
            return [
                'success' => true,
                'message' => 'Estado de asistencia actualizado exitosamente'
            ];
        }

        return ['success' => false, 'message' => 'Error al actualizar el estado'];
    }

    /**
     * Obtiene aprendices de una ficha con su estado de asistencia
     * Para mostrar en la interfaz de registro
     */
    public function getAprendicesParaRegistro(int $fichaId, string $fecha): array
    {
        return $this->asistenciaRepository->getAprendicesConAsistencia($fichaId, $fecha);
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
}

