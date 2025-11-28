<?php

namespace App\Controllers;

use App\Services\InstructorFichaService;
use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Repositories\FichaRepository;
use App\Support\Response;
use Exception;

/**
 * Controller para gestionar las asignaciones instructor-ficha
 * Solo accesible por roles Admin y Administrativo
 * 
 * @author Sistema SENAttend
 * @version 1.0
 */
class InstructorFichaController
{
    private InstructorFichaService $instructorFichaService;
    private AuthService $authService;
    private UserRepository $userRepository;
    private FichaRepository $fichaRepository;

    public function __construct(
        InstructorFichaService $instructorFichaService,
        AuthService $authService,
        UserRepository $userRepository,
        FichaRepository $fichaRepository
    ) {
        $this->instructorFichaService = $instructorFichaService;
        $this->authService = $authService;
        $this->userRepository = $userRepository;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Vista principal de gestión de asignaciones
     * GET /instructor-fichas
     */
    public function index(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            // Validar permisos - Solo Admin y Administrativo
            if (!$this->tienePermisos($user)) {
                $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
                Response::redirect('/dashboard');
                return;
            }
            
            // Paginación para el bloque de fichas
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
            $page = max(1, $page);
            $perPage = 6;
            $totalFichas = $this->fichaRepository->countByEstado('activa');
            $offset = ($page - 1) * $perPage;
            $fichasListado = $this->fichaRepository->findActive($perPage, $offset);
            $pagination = [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $totalFichas,
                'totalPages' => max(1, (int) ceil($totalFichas / $perPage)),
            ];
            
            // Datos para tabs y asignación rápida
            $instructores = $this->instructorFichaService->getInstructoresConFichas();
            $fichasParaAsignacionRapida = $this->fichaRepository->findActive(300, 0);
            
            require __DIR__ . '/../../views/instructor-fichas/index.php';
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::index: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la página de asignaciones';
            Response::redirect('/dashboard');
        }
    }

    /**
     * Vista de detalle de un instructor con sus fichas
     * GET /instructor-fichas/instructor/{id}
     */
    public function verInstructor(int $instructorId): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
                Response::redirect('/dashboard');
                return;
            }
            
            $instructor = $this->instructorFichaService->getDetalleInstructor($instructorId);
            if (empty($instructor)) {
                $_SESSION['error'] = 'Instructor no encontrado';
                Response::redirect('/instructor-fichas');
                return;
            }
            $fichasDisponibles = $this->instructorFichaService
                ->getFichasDisponiblesParaInstructor($instructorId);
            
            require __DIR__ . '/../../views/instructor-fichas/instructor-detalle.php';
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::verInstructor: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el detalle del instructor';
            Response::redirect('/instructor-fichas');
        }
    }

    /**
     * Vista de detalle de una ficha con sus instructores
     * GET /instructor-fichas/ficha/{id}
     */
    public function verFicha(int $fichaId): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
                Response::redirect('/dashboard');
                return;
            }
            
            $ficha = $this->fichaRepository->findById($fichaId);
            if (!$ficha) {
                $_SESSION['error'] = 'Ficha no encontrada';
                Response::redirect('/instructor-fichas');
                return;
            }
            
            $instructoresAsignados = $this->instructorFichaService->getInstructoresDeFicha($fichaId);
            $instructoresDisponibles = $this->instructorFichaService
                ->getInstructoresDisponiblesParaFicha($fichaId);
            
            require __DIR__ . '/../../views/instructor-fichas/ficha-detalle.php';
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::verFicha: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el detalle de la ficha';
            Response::redirect('/instructor-fichas');
        }
    }

    /**
     * API: Asignar fichas a un instructor
     * POST /api/instructor-fichas/asignar-fichas
     */
    public function asignarFichas(): void
    {
        try {
            // Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::json(['error' => 'Método no permitido'], 405);
                return;
            }
            
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            // Obtener datos del request
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['instructor_id']) || !isset($data['ficha_ids'])) {
                Response::json(['error' => 'Datos incompletos'], 400);
                return;
            }
            
            $instructorId = (int) $data['instructor_id'];
            $fichaIds = array_map('intval', $data['ficha_ids']);
            
            // Realizar la asignación
            $resultado = $this->instructorFichaService->asignarFichasAInstructor(
                $instructorId,
                $fichaIds,
                $user['id']
            );
            
            if (isset($resultado['error']) && $resultado['error']) {
                Response::json(['error' => $resultado['mensaje']], 400);
                return;
            }
            
            Response::json([
                'success' => true,
                'mensaje' => 'Asignaciones realizadas correctamente',
                'resultado' => $resultado
            ]);
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::asignarFichas: " . $e->getMessage());
            Response::json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * API: Asignar instructores a una ficha
     * POST /api/instructor-fichas/asignar-instructores
     */
    public function asignarInstructores(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::json(['error' => 'Método no permitido'], 405);
                return;
            }
            
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['ficha_id']) || !isset($data['instructor_ids'])) {
                Response::json(['error' => 'Datos incompletos'], 400);
                return;
            }
            
            $fichaId = (int) $data['ficha_id'];
            $instructorIds = array_map('intval', $data['instructor_ids']);
            
            $resultado = $this->instructorFichaService->asignarInstructoresAFicha(
                $fichaId,
                $instructorIds,
                $user['id']
            );
            
            if (isset($resultado['error']) && $resultado['error']) {
                Response::json(['error' => $resultado['mensaje']], 400);
                return;
            }
            
            Response::json([
                'success' => true,
                'mensaje' => 'Asignaciones realizadas correctamente',
                'resultado' => $resultado
            ]);
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::asignarInstructores: " . $e->getMessage());
            Response::json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * API: Sincronizar fichas de un instructor (reemplaza todas)
     * POST /api/instructor-fichas/sincronizar
     */
    public function sincronizarFichas(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::json(['error' => 'Método no permitido'], 405);
                return;
            }
            
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['instructor_id']) || !isset($data['ficha_ids'])) {
                Response::json(['error' => 'Datos incompletos'], 400);
                return;
            }
            
            $instructorId = (int) $data['instructor_id'];
            $fichaIds = array_map('intval', $data['ficha_ids']);
            
            $resultado = $this->instructorFichaService->sincronizarFichasInstructor(
                $instructorId,
                $fichaIds,
                $user['id']
            );
            
            if ($resultado) {
                Response::json([
                    'success' => true,
                    'mensaje' => 'Fichas sincronizadas correctamente'
                ]);
            } else {
                Response::json(['error' => 'Error al sincronizar fichas'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::sincronizarFichas: " . $e->getMessage());
            Response::json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * API: Eliminar una asignación específica
     * DELETE /api/instructor-fichas/eliminar
     */
    public function eliminarAsignacion(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::json(['error' => 'Método no permitido'], 405);
                return;
            }
            
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            // Para DELETE real o POST con _method=DELETE
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['instructor_id']) || !isset($data['ficha_id'])) {
                Response::json(['error' => 'Datos incompletos'], 400);
                return;
            }
            
            $instructorId = (int) $data['instructor_id'];
            $fichaId = (int) $data['ficha_id'];
            
            $resultado = $this->instructorFichaService->eliminarAsignacion($instructorId, $fichaId);
            
            if ($resultado) {
                Response::json([
                    'success' => true,
                    'mensaje' => 'Asignación eliminada correctamente'
                ]);
            } else {
                Response::json(['error' => 'Error al eliminar la asignación'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::eliminarAsignacion: " . $e->getMessage());
            Response::json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * API: Obtener fichas disponibles para un instructor
     * GET /api/instructor-fichas/fichas-disponibles/{instructorId}
     */
    public function getFichasDisponibles(int $instructorId): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $fichasDisponibles = $this->instructorFichaService
                ->getFichasDisponiblesParaInstructor($instructorId);
            
            Response::json([
                'success' => true,
                'fichas' => $fichasDisponibles
            ]);
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::getFichasDisponibles: " . $e->getMessage());
            Response::json(['error' => 'Error al obtener fichas disponibles'], 500);
        }
    }

    /**
     * API: Obtener instructores disponibles para una ficha
     * GET /api/instructor-fichas/instructores-disponibles/{fichaId}
     */
    public function getInstructoresDisponibles(int $fichaId): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $instructoresDisponibles = $this->instructorFichaService
                ->getInstructoresDisponiblesParaFicha($fichaId);
            
            Response::json([
                'success' => true,
                'instructores' => $instructoresDisponibles
            ]);
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::getInstructoresDisponibles: " . $e->getMessage());
            Response::json(['error' => 'Error al obtener instructores disponibles'], 500);
        }
    }

    /**
     * API: Obtener estadísticas de asignaciones
     * GET /api/instructor-fichas/estadisticas
     */
    public function getEstadisticas(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $estadisticas = $this->instructorFichaService->obtenerEstadisticas();
            
            Response::json([
                'success' => true,
                'estadisticas' => $estadisticas
            ]);
            
        } catch (Exception $e) {
            error_log("Error en InstructorFichaController::getEstadisticas: " . $e->getMessage());
            Response::json(['error' => 'Error al obtener estadísticas'], 500);
        }
    }

    /**
     * API: Obtener fichas de un instructor específico
     * GET /api/instructor-fichas/instructor/{id}/fichas
     */
    public function getFichasInstructor(int $instructorId): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $fichas = $this->instructorFichaService
                ->getDetalleInstructor($instructorId)['fichas_asignadas'] ?? [];
            
            Response::json([
                'success' => true,
                'fichas' => $fichas
            ]);
            
        } catch (Exception $e) {
            error_log("Error en getFichasInstructor: " . $e->getMessage());
            Response::json(['error' => 'Error al obtener fichas del instructor'], 500);
        }
    }

    /**
     * API: Obtener instructores de una ficha específica
     * GET /api/instructor-fichas/ficha/{id}/instructores
     */
    public function getInstructoresFicha(int $fichaId): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $instructores = $this->instructorFichaService->getInstructoresDeFicha($fichaId, true);
            
            Response::json([
                'success' => true,
                'instructores' => $instructores
            ]);
            
        } catch (Exception $e) {
            error_log("Error en getInstructoresFicha: " . $e->getMessage());
            Response::json(['error' => 'Error al obtener instructores de la ficha'], 500);
        }
    }

    /**
     * API: Obtener todos los instructores
     * GET /api/instructores
     */
    public function getAllInstructores(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->tienePermisos($user)) {
                Response::json(['error' => 'Sin permisos'], 403);
                return;
            }
            
            $instructores = $this->userRepository->findByRole('instructor');
            
            Response::json([
                'success' => true,
                'data' => $instructores
            ]);
            
        } catch (Exception $e) {
            error_log("Error en getAllInstructores: " . $e->getMessage());
            Response::json(['error' => 'Error al obtener instructores'], 500);
        }
    }

    /**
     * Valida si el usuario tiene permisos para gestionar asignaciones
     * 
     * @param array $user Usuario actual
     * @return bool True si tiene permisos
     */
    private function tienePermisos(array $user): bool
    {
        // Solo Admin y Administrativo pueden gestionar asignaciones
        // Si existe el rol 'administrativo', lo incluimos
        return in_array($user['rol'], ['admin', 'administrativo', 'coordinador']);
    }
}
