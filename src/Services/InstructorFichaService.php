<?php

namespace App\Services;

use App\Repositories\InstructorFichaRepository;
use App\Repositories\UserRepository;
use App\Repositories\FichaRepository;
use Exception;

/**
 * Service para gestionar la lógica de negocio de asignaciones instructor-ficha
 * Implementa el patrón Service siguiendo principios SOLID
 * 
 * @author Sistema SENAttend
 * @version 1.0
 */
class InstructorFichaService
{
    private InstructorFichaRepository $instructorFichaRepository;
    private UserRepository $userRepository;
    private FichaRepository $fichaRepository;

    public function __construct(
        InstructorFichaRepository $instructorFichaRepository,
        UserRepository $userRepository,
        FichaRepository $fichaRepository
    ) {
        $this->instructorFichaRepository = $instructorFichaRepository;
        $this->userRepository = $userRepository;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Obtiene todos los instructores con sus fichas asignadas
     * 
     * @return array Lista de instructores con fichas
     */
    public function getInstructoresConFichas(): array
    {
        try {
            // Obtener todos los instructores
            $instructores = $this->userRepository->findByRole('instructor');
            
            // Para cada instructor, obtener sus fichas asignadas
            foreach ($instructores as &$instructor) {
                $instructor['fichas'] = $this->instructorFichaRepository
                    ->findFichasByInstructor($instructor['id'], true);
                $instructor['total_fichas'] = count($instructor['fichas']);
            }
            
            return $instructores;
        } catch (Exception $e) {
            error_log("Error en getInstructoresConFichas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todas las fichas con sus instructores asignados
     * 
     * @param bool $soloActivas Si true, solo retorna fichas activas
     * @return array Lista de fichas con instructores
     */
    public function getFichasConInstructores(bool $soloActivas = true): array
    {
        try {
            // Obtener fichas según el filtro
            $fichas = $soloActivas 
                ? $this->fichaRepository->findActive(100, 0)
                : $this->fichaRepository->findAll(100, 0);
            
            // Para cada ficha, obtener sus instructores asignados
            foreach ($fichas as &$ficha) {
                $ficha['instructores'] = $this->instructorFichaRepository
                    ->findInstructoresByFicha($ficha['id'], true);
                $ficha['total_instructores'] = count($ficha['instructores']);
            }
            
            return $fichas;
        } catch (Exception $e) {
            error_log("Error en getFichasConInstructores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Asigna múltiples fichas a un instructor
     * 
     * @param int $instructorId ID del instructor
     * @param array $fichaIds Array de IDs de fichas
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return array Resultado de la operación
     */
    public function asignarFichasAInstructor(int $instructorId, array $fichaIds, ?int $asignadoPor = null): array
    {
        try {
            // Validar que el instructor existe y es válido
            $instructor = $this->userRepository->findById($instructorId);
            if (!$instructor || $instructor['rol'] !== 'instructor') {
                throw new Exception('Instructor no válido');
            }
            
            // Validar que todas las fichas existen
            $fichasValidas = [];
            foreach ($fichaIds as $fichaId) {
                $ficha = $this->fichaRepository->findById($fichaId);
                if ($ficha) {
                    $fichasValidas[] = $fichaId;
                }
            }
            
            if (empty($fichasValidas)) {
                throw new Exception('No se proporcionaron fichas válidas');
            }
            
            // Realizar las asignaciones
            $resultado = $this->instructorFichaRepository
                ->createMultiple($instructorId, $fichasValidas, $asignadoPor);
            
            $resultado['instructor'] = $instructor['nombre'];
            $resultado['fichas_solicitadas'] = count($fichaIds);
            $resultado['fichas_validas'] = count($fichasValidas);
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error en asignarFichasAInstructor: " . $e->getMessage());
            return [
                'error' => true,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Asigna múltiples instructores a una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @param array $instructorIds Array de IDs de instructores
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return array Resultado de la operación
     */
    public function asignarInstructoresAFicha(int $fichaId, array $instructorIds, ?int $asignadoPor = null): array
    {
        try {
            // Validar que la ficha existe
            $ficha = $this->fichaRepository->findById($fichaId);
            if (!$ficha) {
                throw new Exception('Ficha no válida');
            }
            
            $resultado = [
                'exitosos' => 0,
                'errores' => 0,
                'duplicados' => 0
            ];
            
            // Asignar cada instructor a la ficha
            foreach ($instructorIds as $instructorId) {
                $instructor = $this->userRepository->findById($instructorId);
                if (!$instructor || $instructor['rol'] !== 'instructor') {
                    $resultado['errores']++;
                    continue;
                }
                
                if ($this->instructorFichaRepository->exists($instructorId, $fichaId)) {
                    $resultado['duplicados']++;
                    continue;
                }
                
                if ($this->instructorFichaRepository->create($instructorId, $fichaId, $asignadoPor)) {
                    $resultado['exitosos']++;
                } else {
                    $resultado['errores']++;
                }
            }
            
            $resultado['ficha'] = $ficha['numero_ficha'] . ' - ' . $ficha['nombre'];
            $resultado['instructores_solicitados'] = count($instructorIds);
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error en asignarInstructoresAFicha: " . $e->getMessage());
            return [
                'error' => true,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Elimina una asignación entre instructor y ficha
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si se eliminó exitosamente
     */
    public function eliminarAsignacion(int $instructorId, int $fichaId): bool
    {
        try {
            return $this->instructorFichaRepository->delete($instructorId, $fichaId);
        } catch (Exception $e) {
            error_log("Error en eliminarAsignacion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sincroniza las fichas de un instructor (reemplaza todas las asignaciones)
     * 
     * @param int $instructorId ID del instructor
     * @param array $fichaIds Array de IDs de fichas
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return bool True si se sincronizó exitosamente
     */
    public function sincronizarFichasInstructor(int $instructorId, array $fichaIds, ?int $asignadoPor = null): bool
    {
        try {
            // Validar instructor
            $instructor = $this->userRepository->findById($instructorId);
            if (!$instructor || $instructor['rol'] !== 'instructor') {
                throw new Exception('Instructor no válido');
            }
            
            // Validar fichas
            $fichasValidas = [];
            foreach ($fichaIds as $fichaId) {
                if ($this->fichaRepository->findById($fichaId)) {
                    $fichasValidas[] = $fichaId;
                }
            }
            
            return $this->instructorFichaRepository
                ->syncFichasForInstructor($instructorId, $fichasValidas, $asignadoPor);
        } catch (Exception $e) {
            error_log("Error en sincronizarFichasInstructor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las fichas disponibles para asignar a un instructor
     * 
     * @param int $instructorId ID del instructor
     * @return array Lista de fichas no asignadas
     */
    public function getFichasDisponiblesParaInstructor(int $instructorId): array
    {
        try {
            // Obtener todas las fichas activas
            $todasLasFichas = $this->fichaRepository->findActive(200, 0);
            
            // Obtener las fichas ya asignadas al instructor
            $fichasAsignadas = $this->instructorFichaRepository
                ->findFichasByInstructor($instructorId, false);
            
            $idsAsignados = array_column($fichasAsignadas, 'id');
            
            // Filtrar las fichas no asignadas
            $fichasDisponibles = array_filter($todasLasFichas, function($ficha) use ($idsAsignados) {
                return !in_array($ficha['id'], $idsAsignados);
            });
            
            return array_values($fichasDisponibles);
        } catch (Exception $e) {
            error_log("Error en getFichasDisponiblesParaInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los instructores disponibles para asignar a una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @return array Lista de instructores no asignados
     */
    public function getInstructoresDisponiblesParaFicha(int $fichaId): array
    {
        try {
            // Obtener todos los instructores
            $todosLosInstructores = $this->userRepository->findByRole('instructor');
            
            // Obtener los instructores ya asignados a la ficha
            $instructoresAsignados = $this->instructorFichaRepository
                ->findInstructoresByFicha($fichaId, false);
            
            $idsAsignados = array_column($instructoresAsignados, 'id');
            
            // Filtrar los instructores no asignados
            $instructoresDisponibles = array_filter($todosLosInstructores, function($instructor) use ($idsAsignados) {
                return !in_array($instructor['id'], $idsAsignados);
            });
            
            return array_values($instructoresDisponibles);
        } catch (Exception $e) {
            error_log("Error en getInstructoresDisponiblesParaFicha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene instructores asignados a una ficha
     *
     * @param int $fichaId
     * @param bool $soloActivos
     * @return array
     */
    public function getInstructoresDeFicha(int $fichaId, bool $soloActivos = true): array
    {
        try {
            return $this->instructorFichaRepository->findInstructoresByFicha($fichaId, $soloActivos);
        } catch (Exception $e) {
            error_log("Error en getInstructoresDeFicha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida si un instructor tiene acceso a una ficha específica
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si tiene acceso
     */
    public function instructorTieneAccesoAFicha(int $instructorId, int $fichaId): bool
    {
        try {
            // Verificar si existe la asignación y está activa
            return $this->instructorFichaRepository->isActive($instructorId, $fichaId);
        } catch (Exception $e) {
            error_log("Error en instructorTieneAccesoAFicha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas generales de asignaciones
     * 
     * @return array Estadísticas
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $stats = $this->instructorFichaRepository->getStats();
            
            // Agregar estadísticas adicionales
            $totalInstructores = $this->userRepository->countByRole('instructor');
            $totalFichas = $this->fichaRepository->count();
            
            $stats['total_instructores'] = $totalInstructores;
            $stats['total_fichas'] = $totalFichas;
            $stats['instructores_sin_asignar'] = $totalInstructores - $stats['total_instructores_asignados'];
            $stats['fichas_sin_asignar'] = $totalFichas - $stats['total_fichas_asignadas'];
            
            // Calcular promedios
            $stats['promedio_fichas_por_instructor'] = $stats['total_instructores_asignados'] > 0
                ? round($stats['total_asignaciones'] / $stats['total_instructores_asignados'], 2)
                : 0;
            
            $stats['promedio_instructores_por_ficha'] = $stats['total_fichas_asignadas'] > 0
                ? round($stats['total_asignaciones'] / $stats['total_fichas_asignadas'], 2)
                : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return [
                'total_instructores' => 0,
                'total_fichas' => 0,
                'total_asignaciones' => 0,
                'asignaciones_activas' => 0,
                'asignaciones_inactivas' => 0,
                'instructores_sin_asignar' => 0,
                'fichas_sin_asignar' => 0,
                'promedio_fichas_por_instructor' => 0,
                'promedio_instructores_por_ficha' => 0
            ];
        }
    }

    /**
     * Obtiene el detalle de asignaciones de un instructor específico
     * 
     * @param int $instructorId ID del instructor
     * @return array Detalle del instructor con sus fichas
     */
    public function getDetalleInstructor(int $instructorId): array
    {
        try {
            $instructor = $this->userRepository->findById($instructorId);
            if (!$instructor || $instructor['rol'] !== 'instructor') {
                throw new Exception('Instructor no válido');
            }
            
            $instructor['fichas_asignadas'] = $this->instructorFichaRepository
                ->findFichasByInstructor($instructorId, false);
            
            $instructor['fichas_activas'] = array_filter($instructor['fichas_asignadas'], function($ficha) {
                return $ficha['asignacion_activa'] == 1 && $ficha['estado'] == 'activa';
            });
            
            $instructor['total_fichas_asignadas'] = count($instructor['fichas_asignadas']);
            $instructor['total_fichas_activas'] = count($instructor['fichas_activas']);
            
            return $instructor;
        } catch (Exception $e) {
            error_log("Error en getDetalleInstructor: " . $e->getMessage());
            return [];
        }
    }
}
