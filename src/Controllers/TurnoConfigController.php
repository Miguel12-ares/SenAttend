<?php

namespace App\Controllers;

use App\Services\TurnoConfigService;
use App\Services\AuthService;
use App\Support\Response;
use Exception;

/**
 * Controlador para gestión de configuración de turnos
 * Solo accesible para usuarios con rol Admin
 * 
 * @author Sistema de Configuración Dinámica
 * @version 1.0
 */
class TurnoConfigController
{
    private TurnoConfigService $turnoConfigService;
    private AuthService $authService;

    public function __construct(
        TurnoConfigService $turnoConfigService,
        AuthService $authService
    ) {
        $this->turnoConfigService = $turnoConfigService;
        $this->authService = $authService;
    }

    /**
     * Vista principal de gestión de horarios
     * GET /configuracion/horarios
     * Solo accesible para Admin
     */
    public function index(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            // Validar que es Admin
            if ($user['rol'] !== 'admin') {
                $_SESSION['errors'] = ['Solo los administradores pueden acceder a esta sección'];
                Response::redirect('/dashboard');
                return;
            }

            // Obtener configuración actual de turnos
            $turnos = $this->turnoConfigService->obtenerTodosTurnos();

            // Headers de seguridad
            $this->establecerHeadersSeguridad();

            // Renderizar vista
            require __DIR__ . '/../../views/configuracion/horarios.php';
            
        } catch (Exception $e) {
            error_log("Error en TurnoConfigController::index: " . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del sistema. Contacte al administrador.'];
            Response::redirect('/dashboard');
        }
    }

    /**
     * Actualiza la configuración de turnos
     * POST /configuracion/horarios/actualizar
     * Solo accesible para Admin
     */
    public function actualizar(): void
    {
        try {
            // Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::redirect('/configuracion/horarios');
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            // Validar que es Admin
            if ($user['rol'] !== 'admin') {
                $_SESSION['errors'] = ['Solo los administradores pueden modificar la configuración'];
                Response::redirect('/dashboard');
                return;
            }

            // Obtener datos del formulario
            $turnos = [];
            
            // Procesar datos de cada turno
            foreach (['Mañana', 'Tarde', 'Noche'] as $nombreTurno) {
                $nombreLower = strtolower($nombreTurno);
                
                // Obtener valores del formulario
                $horaInicio = filter_input(INPUT_POST, $nombreLower . '_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $horaFin = filter_input(INPUT_POST, $nombreLower . '_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $horaLimite = filter_input(INPUT_POST, $nombreLower . '_limite', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                
                // Agregar segundos si no están presentes (formato H:i -> H:i:s)
                if ($horaInicio && strlen($horaInicio) === 5) {
                    $horaInicio .= ':00';
                }
                if ($horaFin && strlen($horaFin) === 5) {
                    $horaFin .= ':00';
                }
                if ($horaLimite && strlen($horaLimite) === 5) {
                    $horaLimite .= ':00';
                }
                
                $turnos[] = [
                    'nombre_turno' => $nombreTurno,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'hora_limite_llegada' => $horaLimite
                ];
            }

            // Actualizar configuración
            $resultado = $this->turnoConfigService->actualizarConfiguracion($turnos, $user['id']);

            if ($resultado['success']) {
                $_SESSION['success'] = $resultado['message'];
            } else {
                $_SESSION['errors'] = [$resultado['message']];
                if (!empty($resultado['details'])) {
                    $_SESSION['errors'] = array_merge($_SESSION['errors'], array_values($resultado['details']));
                }
            }

            Response::redirect('/configuracion/horarios');

        } catch (Exception $e) {
            error_log("Error en TurnoConfigController::actualizar: " . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del sistema. Contacte al administrador.'];
            Response::redirect('/configuracion/horarios');
        }
    }

    /**
     * API: Obtiene configuración de turnos (JSON)
     * GET /api/configuracion/turnos
     */
    public function apiObtenerTurnos(): void
    {
        try {
            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Obtener turnos activos
            $turnos = $this->turnoConfigService->obtenerConfiguracionTurnos();

            // Headers de seguridad
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => [
                    'turnos' => $turnos,
                    'total' => count($turnos)
                ],
                'message' => 'Configuración de turnos obtenida exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en TurnoConfigController::apiObtenerTurnos: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Determina el turno actual basado en la hora
     * GET /api/configuracion/turno-actual?hora=HH:MM:SS
     */
    public function apiTurnoActual(): void
    {
        try {
            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Obtener hora del parámetro o usar hora actual
            $hora = filter_input(INPUT_GET, 'hora', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $turno = $this->turnoConfigService->obtenerTurnoActual($hora);

            // Headers de seguridad
            $this->establecerHeadersAPI();

            if ($turno) {
                Response::json([
                    'success' => true,
                    'data' => [
                        'turno' => $turno,
                        'hora_consultada' => $hora ?? date('H:i:s')
                    ],
                    'message' => 'Turno encontrado'
                ]);
            } else {
                Response::json([
                    'success' => false,
                    'data' => [
                        'turno' => null,
                        'hora_consultada' => $hora ?? date('H:i:s')
                    ],
                    'message' => 'No hay turno activo para esta hora'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error en TurnoConfigController::apiTurnoActual: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    // ============================================================================
    // MÉTODOS PRIVADOS DE UTILIDADES
    // ============================================================================

    /**
     * Verifica si es una petición AJAX
     */
    private function esRequestAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Establece headers de seguridad para páginas web
     */
    private function establecerHeadersSeguridad(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Establece headers de seguridad para API
     */
    private function establecerHeadersAPI(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
