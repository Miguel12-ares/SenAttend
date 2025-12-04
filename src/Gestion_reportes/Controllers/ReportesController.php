<?php

namespace App\Gestion_reportes\Controllers;

use App\Services\AuthService;
use App\Session\SessionManager;
use App\Gestion_reportes\Services\ReportGenerationService;
use App\Gestion_reportes\Services\ExcelExportService;

/**
 * Controlador del módulo de Gestión de Reportes de Asistencia.
 *
 * - Dashboard de reportes para instructores
 * - Generación de reportes vía AJAX
 * - Historial de exportaciones
 */
class ReportesController
{
    private AuthService $authService;
    private SessionManager $session;
    private ReportGenerationService $reportGenerationService;
    private ExcelExportService $excelExportService;

    public function __construct(
        AuthService $authService,
        SessionManager $session,
        ReportGenerationService $reportGenerationService,
        ExcelExportService $excelExportService
    ) {
        $this->authService = $authService;
        $this->session = $session;
        $this->reportGenerationService = $reportGenerationService;
        $this->excelExportService = $excelExportService;
    }

    /**
     * Dashboard de reportes (selector de fichas + historial)
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();

        // Solo instructores
        if (!$user || $user['rol'] !== 'instructor') {
            header('Location: /dashboard');
            exit;
        }

        $fichas = $this->reportGenerationService->getFichasDisponiblesParaInstructor((int) $user['id']);
        $historial = $this->reportGenerationService->getHistorialExportaciones((int) $user['id']);

        $csrfToken = $this->ensureCsrfToken();

        require __DIR__ . '/../../../views/gestion_reportes/index.php';
    }

    /**
     * Endpoint AJAX para generar reporte de asistencia y devolver URL de descarga.
     */
    public function generar(): void
    {
        header('Content-Type: application/json');

        $user = $this->authService->getCurrentUser();
        if (!$user || $user['rol'] !== 'instructor') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        // Validar CSRF básico
        $this->session->start();
        $csrfToken = $_POST['_token'] ?? '';
        if (!$this->validateCsrfToken($csrfToken)) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        $fichaId = isset($_POST['ficha_id']) ? (int) $_POST['ficha_id'] : 0;
        $fecha = $_POST['fecha'] ?? date('Y-m-d');

        if ($fichaId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ficha inválida.']);
            return;
        }

        try {
            $datosReporte = $this->reportGenerationService->generarDatosReporte(
                $fichaId,
                $fecha,
                (int) $user['id']
            );

            $rows = $datosReporte['rows'];
            $meta = $datosReporte['meta'];
            $stats = $datosReporte['stats'];

            // Nombre de archivo: reporte_ficha_{codigo}_{fecha}_{hora}.xlsx
            $codigoFicha = $rows[0]['ficha'] ?? 'ficha';
            $codigoFicha = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $codigoFicha);
            $timestamp = date('Ymd_His');
            $fileName = "reporte_ficha_{$codigoFicha}_{$fecha}_{$timestamp}.xlsx";

            $filePath = $this->excelExportService->generateExcel($rows, $fileName, $meta);

            // Registrar historial (no debe bloquear la descarga en caso de fallo)
            $this->reportGenerationService->registrarHistorialExportacion(
                (int) $user['id'],
                $fichaId,
                $fecha,
                $fileName,
                $stats
            );

            // Exponer ruta relativa segura para descarga
            $publicPath = '/exports/' . basename($filePath);

            echo json_encode([
                'success' => true,
                'message' => 'Reporte generado correctamente.',
                'download_url' => $publicPath,
                'file_name' => $fileName,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('Error generando reporte: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Ocurrió un error al generar el reporte: ' . $e->getMessage(),
            ]);
        }
    }

    private function ensureCsrfToken(): string
    {
        $this->session->start();
        $token = $this->session->get('_csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('_csrf_token', $token);
        }
        return $token;
    }

    private function validateCsrfToken(string $token): bool
    {
        $this->session->start();
        $stored = $this->session->get('_csrf_token');
        return is_string($stored) && hash_equals($stored, $token);
    }
}


