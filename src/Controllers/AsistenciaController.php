<?php

namespace App\Controllers;

use App\Services\AsistenciaService;
use App\Services\AuthService;
use App\Repositories\FichaRepository;
use App\Support\Response;

/**
 * Controlador para Registro de Asistencia
 * Sprint 4 - FUNCIONALIDAD CRÍTICA DEL MVP
 */
class AsistenciaController
{
    private AsistenciaService $asistenciaService;
    private AuthService $authService;
    private FichaRepository $fichaRepository;

    public function __construct(
        AsistenciaService $asistenciaService,
        AuthService $authService,
        FichaRepository $fichaRepository
    ) {
        $this->asistenciaService = $asistenciaService;
        $this->authService = $authService;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Vista principal de registro de asistencia
     * GET /asistencia/registrar
     */
    public function registrar(): void
    {
        $user = $this->authService->getCurrentUser();
        
        // Obtener fichas activas para el selector
        $fichas = $this->fichaRepository->findActive(100, 0);
        
        // Fecha por defecto: hoy
        $fechaSeleccionada = filter_input(INPUT_GET, 'fecha') ?: date('Y-m-d');
        $fichaSeleccionada = filter_input(INPUT_GET, 'ficha', FILTER_VALIDATE_INT);

        $aprendices = [];
        $ficha = null;
        $estadisticas = null;

        // Si hay ficha seleccionada, cargar aprendices
        if ($fichaSeleccionada) {
            $ficha = $this->fichaRepository->findById($fichaSeleccionada);
            $aprendices = $this->asistenciaService->getAprendicesParaRegistro(
                $fichaSeleccionada,
                $fechaSeleccionada
            );
            
            // Obtener estadísticas si ya hay registros
            $estadisticas = $this->asistenciaService->getEstadisticas(
                $fichaSeleccionada,
                $fechaSeleccionada
            );
        }

        // Validar fecha
        $validacionFecha = $this->asistenciaService->validarFechaRegistro($fechaSeleccionada);

        require __DIR__ . '/../../views/asistencia/registrar.php';
    }

    /**
     * Procesa el registro masivo de asistencia
     * POST /asistencia/guardar
     */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/asistencia/registrar');
        }

        $user = $this->authService->getCurrentUser();
        
        // Obtener datos del formulario
        $fichaId = filter_input(INPUT_POST, 'ficha_id', FILTER_VALIDATE_INT);
        $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $asistencias = $_POST['asistencias'] ?? [];

        // Validaciones
        if (!$fichaId) {
            $_SESSION['errors'] = ['Debe seleccionar una ficha'];
            Response::redirect('/asistencia/registrar');
        }

        if (!$fecha) {
            $_SESSION['errors'] = ['Debe seleccionar una fecha'];
            Response::redirect("/asistencia/registrar?ficha={$fichaId}");
        }

        // Validar fecha
        $validacion = $this->asistenciaService->validarFechaRegistro($fecha);
        if (!$validacion['valido']) {
            $_SESSION['errors'] = [$validacion['mensaje']];
            Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
        }

        if (empty($asistencias)) {
            $_SESSION['errors'] = ['Debe marcar al menos un aprendiz'];
            Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
        }

        // Preparar datos de asistencia
        $datosAsistencia = [];
        foreach ($asistencias as $aprendizId => $estado) {
            $datosAsistencia[] = [
                'id_aprendiz' => (int) $aprendizId,
                'estado' => $estado,
            ];
        }

        // Registrar asistencia masiva
        $resultado = $this->asistenciaService->registrarAsistenciaMasiva(
            $fichaId,
            $fecha,
            $datosAsistencia,
            $user['id']
        );

        if ($resultado['success']) {
            $_SESSION['success'] = $resultado['message'];
            
            if (!empty($resultado['errores'])) {
                $_SESSION['warnings'] = $resultado['errores'];
            }
        } else {
            $_SESSION['errors'] = [$resultado['message']];
            if (!empty($resultado['errores'])) {
                $_SESSION['errors'] = array_merge($_SESSION['errors'], $resultado['errores']);
            }
        }

        Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
    }

    /**
     * Modifica el estado de una asistencia existente
     * POST /asistencia/{id}/modificar
     */
    public function modificar(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/asistencia/registrar');
        }

        $user = $this->authService->getCurrentUser();
        
        $nuevoEstado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $motivo = filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        $fichaId = filter_input(INPUT_POST, 'ficha_id', FILTER_VALIDATE_INT);
        $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$nuevoEstado) {
            $_SESSION['errors'] = ['Debe seleccionar un estado'];
            Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
        }

        $resultado = $this->asistenciaService->modificarEstado(
            $id,
            $nuevoEstado,
            $user['id'],
            $motivo
        );

        if ($resultado['success']) {
            $_SESSION['success'] = $resultado['message'];
        } else {
            $_SESSION['errors'] = [$resultado['message']];
        }

        Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
    }

    /**
     * API: Obtiene aprendices de una ficha para registro (JSON)
     * GET /api/asistencia/aprendices/{fichaId}?fecha=YYYY-MM-DD
     */
    public function apiGetAprendices(int $fichaId): void
    {
        $fecha = filter_input(INPUT_GET, 'fecha') ?: date('Y-m-d');
        
        $aprendices = $this->asistenciaService->getAprendicesParaRegistro($fichaId, $fecha);
        $estadisticas = $this->asistenciaService->getEstadisticas($fichaId, $fecha);

        Response::json([
            'success' => true,
            'aprendices' => $aprendices,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * API: Registra asistencia individual (JSON)
     * POST /api/asistencia/registrar
     */
    public function apiRegistrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método no permitido', 405);
        }

        $user = $this->authService->getCurrentUser();
        
        // Obtener JSON del body
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            Response::error('Datos inválidos', 400);
        }

        // Agregar usuario que registra
        $data['registrado_por'] = $user['id'];
        $data['fecha'] = $data['fecha'] ?? date('Y-m-d');

        $resultado = $this->asistenciaService->registrarAsistencia($data);

        if ($resultado['success']) {
            Response::success($resultado, $resultado['message']);
        } else {
            Response::error($resultado['message'], 400);
        }
    }

    /**
     * API: Modifica estado de asistencia (JSON)
     * PUT /api/asistencia/{id}
     */
    public function apiModificar(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
            Response::error('Método no permitido', 405);
        }

        $user = $this->authService->getCurrentUser();
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['estado'])) {
            Response::error('Estado es requerido', 400);
        }

        $resultado = $this->asistenciaService->modificarEstado(
            $id,
            $data['estado'],
            $user['id'],
            $data['motivo'] ?? ''
        );

        if ($resultado['success']) {
            Response::success($resultado, $resultado['message']);
        } else {
            Response::error($resultado['message'], 400);
        }
    }
}

