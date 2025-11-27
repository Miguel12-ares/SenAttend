<?php

namespace App\Controllers;

use App\Repositories\AprendizRepository;
use App\Services\QRService;
use App\Support\Response;
use Exception;

/**
 * Controlador para la página pública de inicio
 * Permite generación de QR para aprendices sin autenticación
 */
class HomeController
{
    private AprendizRepository $aprendizRepository;
    private QRService $qrService;

    public function __construct(
        AprendizRepository $aprendizRepository,
        QRService $qrService
    ) {
        $this->aprendizRepository = $aprendizRepository;
        $this->qrService = $qrService;
    }

    /**
     * Vista principal de la página de inicio (pública)
     * GET /home
     */
    public function index(): void
    {
        try {
            // Headers de seguridad
            $this->establecerHeadersSeguridad();

            // Incluir la vista
            require __DIR__ . '/../../views/home/index.php';
            
        } catch (Exception $e) {
            error_log("Error en HomeController::index: " . $e->getMessage());
            Response::serverError();
        }
    }

    /**
     * API: Validar si un aprendiz existe por documento (público)
     * POST /api/public/aprendiz/validar
     */
    public function apiValidarAprendiz(): void
    {
        try {
            // Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }

            // Obtener datos del POST
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            // Validar campo requerido
            if (!isset($data['documento']) || empty($data['documento'])) {
                Response::error('El documento es requerido', 400);
                return;
            }

            // Sanitizar documento
            $documento = filter_var($data['documento'], FILTER_SANITIZE_STRING);
            $documento = trim($documento);

            // Validar formato de documento (solo números, 6-20 dígitos)
            if (!preg_match('/^\d{6,20}$/', $documento)) {
                Response::error('El documento debe contener solo números (6-20 dígitos)', 400);
                return;
            }

            // Buscar aprendiz por documento
            $aprendiz = $this->aprendizRepository->findByDocumento($documento);

            if (!$aprendiz) {
                Response::error('Aprendiz no encontrado en el sistema', 404);
                return;
            }

            // Verificar que está activo
            if ($aprendiz['estado'] !== 'activo') {
                Response::error('El aprendiz no está activo en el sistema', 400);
                return;
            }

            // Obtener fichas del aprendiz
            $fichas = $this->aprendizRepository->getFichas($aprendiz['id']);

            if (empty($fichas)) {
                Response::error('El aprendiz no está vinculado a ninguna ficha', 400);
                return;
            }

            // Generar código QR con expiración de 3 minutos y envío por correo
            $resultadoQR = $this->qrService->generarCodigoQR($aprendiz['id'], true);

            if (!$resultadoQR['success']) {
                Response::error($resultadoQR['message'], 400);
                return;
            }

            // Log de generación pública
            $this->logGeneracionPublica($aprendiz['documento'], $_SERVER['REMOTE_ADDR'] ?? 'unknown');

            // Headers de seguridad
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => [
                    'aprendiz' => [
                        'nombre_completo' => $aprendiz['nombre'] . ' ' . $aprendiz['apellido'],
                        'documento' => $aprendiz['documento'],
                        'email' => $aprendiz['email'] ?? null
                    ],
                    'qr_data' => $resultadoQR['data']['qr_data'],  // Formato: "TOKEN|ID_APRENDIZ|FECHA"
                    'token' => $resultadoQR['data']['token'],
                    'fecha_generacion' => $resultadoQR['data']['fecha_generacion'] ?? null,
                    'fecha_expiracion' => $resultadoQR['data']['fecha_expiracion'],
                    'email_enviado' => $resultadoQR['data']['email_enviado'],
                    'email_message' => $resultadoQR['data']['email_message'],
                    'fichas' => $fichas
                ],
                'message' => 'Código QR generado exitosamente' . 
                    ($resultadoQR['data']['email_enviado'] ? ' y enviado por correo' : '')
            ]);

        } catch (Exception $e) {
            error_log("Error en HomeController::apiValidarAprendiz: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Mensaje más específico para errores de base de datos
            $message = 'Error interno del servidor';
            if (strpos($e->getMessage(), 'Database connection') !== false) {
                $message = 'Error de conexión a la base de datos. Verifica que MySQL esté corriendo y que la tabla codigos_qr exista.';
            }
            
            Response::error($message, 500);
        }
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
     * Log de generación pública de QR
     */
    private function logGeneracionPublica(string $documento, string $ip): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operacion' => 'GENERACION_QR_PUBLICA',
            'documento' => $documento,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        error_log("QR_PUBLICO: " . json_encode($logEntry));
    }
}
