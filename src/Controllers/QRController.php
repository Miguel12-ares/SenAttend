<?php

namespace App\Controllers;

use App\Services\AsistenciaService;
use App\Services\AuthService;
use App\Services\QRService;
use App\Repositories\AprendizRepository;
use App\Repositories\FichaRepository;
use App\Support\Response;
use Exception;

/**
 * Controlador para módulo QR
 * Gestiona generación y escaneo de códigos QR para registro de asistencia
 * 
 * @version 1.0
 */
class QRController
{
    private AsistenciaService $asistenciaService;
    private AuthService $authService;
    private QRService $qrService;
    private AprendizRepository $aprendizRepository;
    private FichaRepository $fichaRepository;

    public function __construct(
        AsistenciaService $asistenciaService,
        AuthService $authService,
        QRService $qrService,
        AprendizRepository $aprendizRepository,
        FichaRepository $fichaRepository
    ) {
        $this->asistenciaService = $asistenciaService;
        $this->authService = $authService;
        $this->qrService = $qrService;
        $this->aprendizRepository = $aprendizRepository;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Vista para generar QR (Aprendices)
     * GET /qr/generar
     */
    public function generar(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            // Headers de seguridad
            $this->establecerHeadersSeguridad();

            // Pasar variables a la vista
            require __DIR__ . '/../../views/qr/generar.php';
            
        } catch (Exception $e) {
            error_log("Error en QRController::generar: " . $e->getMessage());
            $this->redirectConError('Error interno del sistema.');
        }
    }

    /**
     * Vista para escanear QR (Instructores)
     * GET /qr/escanear
     */
    public function escanear(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            // Validar que es instructor, coordinador o admin
            if (!in_array($user['rol'], ['instructor', 'coordinador', 'admin'])) {
                $this->redirectConError('No tiene permisos para escanear códigos QR');
                return;
            }

            // Obtener fichas activas para el selector
            $fichas = $this->fichaRepository->findActive(100, 0);
            
            // Headers de seguridad
            $this->establecerHeadersSeguridad();

            // Pasar variables a la vista
            require __DIR__ . '/../../views/qr/escanear.php';
            
        } catch (Exception $e) {
            error_log("Error en QRController::escanear: " . $e->getMessage());
            $this->redirectConError('Error interno del sistema.');
        }
    }

    /**
     * API: Obtiene información de aprendiz por documento (para generar QR)
     * GET /api/qr/aprendiz/{documento}
     */
    public function apiObtenerAprendiz(string $documento): void
    {
        try {
            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Sanitizar documento
            $documento = filter_var($documento, FILTER_SANITIZE_STRING);

            // Buscar aprendiz por documento
            $aprendiz = $this->aprendizRepository->findByDocumento($documento);

            if (!$aprendiz) {
                Response::error('Aprendiz no encontrado', 404);
                return;
            }

            // Obtener fichas del aprendiz
            $fichas = $this->aprendizRepository->getFichas($aprendiz['id']);

            // Headers de seguridad
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => [
                    'aprendiz' => $aprendiz,
                    'fichas' => $fichas
                ],
                'message' => 'Aprendiz encontrado'
            ]);

        } catch (Exception $e) {
            error_log("Error en QRController::apiObtenerAprendiz: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Procesa escaneo de QR y registra asistencia
     * POST /api/qr/procesar
     */
    public function apiProcesarQR(): void
    {
        try {
            // Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }

            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            // Validar que es instructor, coordinador o admin
            if (!in_array($user['rol'], ['instructor', 'coordinador', 'admin'])) {
                Response::error('No tiene permisos para registrar asistencia', 403);
                return;
            }

            // Obtener datos del QR escaneado
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            // Validar campos requeridos
            if (!isset($data['qr_data']) || !isset($data['ficha_id'])) {
                Response::error('Datos incompletos', 400);
                return;
            }

            $qrDataRaw = $data['qr_data'];

            // Validar código QR usando el servicio (verifica expiración y uso)
            $validacionQR = $this->qrService->validarCodigoQR($qrDataRaw);

            if (!$validacionQR['success']) {
                Response::error($validacionQR['message'], 400);
                return;
            }

            $aprendizId = $validacionQR['data']['aprendiz_id'];
            $aprendiz = $validacionQR['data']['aprendiz'];

            $fichaId = (int) $data['ficha_id'];
            $fecha = date('Y-m-d');
            $hora = date('H:i:s');

            // Verificar que la ficha existe y está activa
            $ficha = $this->fichaRepository->findById($fichaId);
            if (!$ficha || $ficha['estado'] !== 'activa') {
                Response::error('Ficha no encontrada o inactiva', 404);
                return;
            }

            // Verificar que el aprendiz pertenece a la ficha
            $fichasAprendiz = $this->aprendizRepository->getFichas($aprendizId);
            $perteneceAFicha = false;
            foreach ($fichasAprendiz as $f) {
                if ($f['id'] == $fichaId) {
                    $perteneceAFicha = true;
                    break;
                }
            }

            if (!$perteneceAFicha) {
                Response::error('El aprendiz no pertenece a esta ficha', 400);
                return;
            }

            // Determinar estado automáticamente (presente o tardanza)
            $estado = 'presente';
            $horaLimite = '07:30:00'; // Configurable
            if ($hora > $horaLimite) {
                $estado = 'tardanza';
            }

            // Registrar asistencia automática
            $resultado = $this->asistenciaService->registrarAsistencia([
                'id_aprendiz' => $aprendizId,
                'id_ficha' => $fichaId,
                'fecha' => $fecha,
                'hora' => $hora,
                'estado' => $estado,
                'registrado_por' => $user['id'],
                'observaciones' => 'Registro automático vía QR'
            ], $user['id']);

            // Headers de seguridad
            $this->establecerHeadersAPI();

            if ($resultado['success']) {
                // Log de operación
                $this->logOperacionCritica('REGISTRO_QR', [
                    'aprendiz_id' => $aprendizId,
                    'aprendiz_documento' => $aprendiz['documento'],
                    'aprendiz_nombre' => $aprendiz['nombre'] . ' ' . $aprendiz['apellido'],
                    'ficha_id' => $fichaId,
                    'estado' => $estado,
                    'registrado_por' => $user['id']
                ]);

                Response::success([
                    'asistencia_id' => $resultado['id'],
                    'aprendiz' => [
                        'id' => $aprendizId,
                        'documento' => $aprendiz['documento'],
                        'nombre' => $aprendiz['nombre'] . ' ' . $aprendiz['apellido']
                    ],
                    'estado' => $estado,
                    'fecha' => $fecha,
                    'hora' => $hora
                ], 'Asistencia registrada exitosamente');
            } else {
                Response::error($resultado['message'], 400, $resultado);
            }

        } catch (Exception $e) {
            error_log("Error en QRController::apiProcesarQR: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Buscar aprendiz por documento para generación de QR
     * GET /api/qr/buscar?documento=xxx
     */
    public function apiBuscarAprendiz(): void
    {
        try {
            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Obtener parámetro de búsqueda
            $documento = filter_input(INPUT_GET, 'documento', FILTER_SANITIZE_STRING);

            if (empty($documento)) {
                Response::error('Documento es requerido', 400);
                return;
            }

            // Buscar aprendiz
            $aprendiz = $this->aprendizRepository->findByDocumento($documento);

            if (!$aprendiz) {
                Response::error('Aprendiz no encontrado', 404);
                return;
            }

            // Verificar que está activo
            if ($aprendiz['estado'] !== 'activo') {
                Response::error('El aprendiz no está activo', 400);
                return;
            }

            // Obtener fichas del aprendiz
            $fichas = $this->aprendizRepository->getFichas($aprendiz['id']);

            if (empty($fichas)) {
                Response::error('El aprendiz no está vinculado a ninguna ficha', 400);
                return;
            }

            // Headers de seguridad
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => [
                    'aprendiz' => [
                        'id' => $aprendiz['id'],
                        'documento' => $aprendiz['documento'],
                        'nombre' => $aprendiz['nombre'],
                        'apellido' => $aprendiz['apellido'],
                        'nombre_completo' => $aprendiz['nombre'] . ' ' . $aprendiz['apellido'],
                        'email' => $aprendiz['email'] ?? null
                    ],
                    'fichas' => $fichas
                ],
                'message' => 'Aprendiz encontrado'
            ]);

        } catch (Exception $e) {
            error_log("Error en QRController::apiBuscarAprendiz: " . $e->getMessage());
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

    /**
     * Redirecciona con mensaje de error
     */
    private function redirectConError(string $mensaje): void
    {
        $_SESSION['errors'] = [$mensaje];
        Response::redirect('/');
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

        error_log("QR_OPERACION_CRITICA: " . json_encode($logEntry));
    }
}

