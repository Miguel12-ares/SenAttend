<?php

namespace App\Controllers;

use App\Repositories\AprendizRepository;
use App\Support\Response;
use Exception;

/**
 * Controlador para la página pública de inicio
 * Permite generación de QR para aprendices sin autenticación
 */
class HomeController
{
    private AprendizRepository $aprendizRepository;

    public function __construct(AprendizRepository $aprendizRepository)
    {
        $this->aprendizRepository = $aprendizRepository;
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

            // Preparar datos simplificados para el QR
            // Solo ID y fecha para hacer el código más pequeño y fácil de escanear
            $qrData = $aprendiz['id'] . '|' . date('Y-m-d');

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
                        'codigo_carnet' => $aprendiz['codigo_carnet']
                    ],
                    'qr_data' => $qrData,  // Formato simple: "ID|FECHA"
                    'fichas' => $fichas
                ],
                'message' => 'Código QR generado exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en HomeController::apiValidarAprendiz: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
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
