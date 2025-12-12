<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\AnalyticsService;
use App\Session\SessionManager;
use App\Gestion_reportes\Services\ExcelExportService;
use RuntimeException;

/**
 * Controlador del módulo de Analítica y Reportes
 * 
 * Gestiona la interfaz de usuario para generación de reportes estadísticos
 * de asistencia para usuarios con rol administrativo.
 * 
 * @author Analytics Module
 * @version 1.0
 */
class AnalyticsController
{
    private AuthService $authService;
    private SessionManager $session;
    private AnalyticsService $analyticsService;
    private ExcelExportService $excelExportService;

    public function __construct(
        AuthService $authService,
        SessionManager $session,
        AnalyticsService $analyticsService,
        ExcelExportService $excelExportService
    ) {
        $this->authService = $authService;
        $this->session = $session;
        $this->analyticsService = $analyticsService;
        $this->excelExportService = $excelExportService;
    }

    /**
     * Vista principal del módulo de analítica
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();

        // Solo admin y administrativo
        if (!$user || !in_array($user['rol'], ['admin', 'administrativo'])) {
            header('Location: /dashboard');
            exit;
        }

        // Obtener fichas disponibles
        $fichas = $this->analyticsService->getFichasDisponibles();

        // Generar token CSRF
        $csrfToken = $this->ensureCsrfToken();

        require __DIR__ . '/../../views/analytics/index.php';
    }

    /**
     * Genera reporte semanal vía AJAX
     */
    public function generateWeeklyReport(): void
    {
        header('Content-Type: application/json');

        $user = $this->authService->getCurrentUser();
        
        // Validar permisos
        if (!$user || !in_array($user['rol'], ['admin', 'administrativo'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        // Validar CSRF
        $this->session->start();
        $csrfToken = $_POST['_token'] ?? '';
        if (!$this->validateCsrfToken($csrfToken)) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        // Obtener parámetros
        $fichaId = isset($_POST['ficha_id']) ? (int)$_POST['ficha_id'] : 0;
        $fechaInicio = $_POST['fecha_inicio'] ?? null;

        if ($fichaId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ficha inválida.']);
            return;
        }

        try {
            // Generar datos del reporte
            $reportData = $this->analyticsService->generateWeeklyReport($fichaId, $fechaInicio);

            // Validar datos mínimos
            if (!isset($reportData['metadata']['numero_ficha'])) {
                $this->logError('Falta numero_ficha en metadata', $reportData);
                throw new \RuntimeException('Datos insuficientes para generar el reporte (numero_ficha ausente)');
            }

            // Formatear para Excel
            $excelData = $this->analyticsService->formatForExcel($reportData);
            if (empty($excelData['resumen']) || empty($excelData['aprendices'])) {
                $this->logError('Datos insuficientes en excelData', $excelData);
                throw new \RuntimeException('No hay datos suficientes para exportar');
            }

            // Generar nombre de archivo
            $numeroFicha = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $reportData['metadata']['numero_ficha']);
            $timestamp = date('Ymd_His');
            $fileName = "reporte_semanal_{$numeroFicha}_{$timestamp}.xlsx";

            // Generar archivo Excel
            $filePath = $this->generateExcelFile($excelData, $fileName, $reportData['metadata']);

            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Reporte semanal generado correctamente.',
                'download_url' => '/exports/' . basename($filePath),
                'file_name' => $fileName,
                'metadata' => $reportData['metadata']
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            $this->logError('Error generando reporte semanal: ' . $e->getMessage(), isset($reportData) ? $reportData : []);
            error_log('Error generando reporte semanal: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Genera reporte mensual vía AJAX
     */
    public function generateMonthlyReport(): void
    {
        header('Content-Type: application/json');

        $user = $this->authService->getCurrentUser();
        
        // Validar permisos
        if (!$user || !in_array($user['rol'], ['admin', 'administrativo'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        // Validar CSRF
        $this->session->start();
        $csrfToken = $_POST['_token'] ?? '';
        if (!$this->validateCsrfToken($csrfToken)) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        // Obtener parámetros
        $fichaId = isset($_POST['ficha_id']) ? (int)$_POST['ficha_id'] : 0;
        $mes = isset($_POST['mes']) ? (int)$_POST['mes'] : null;
        $año = isset($_POST['año']) ? (int)$_POST['año'] : null;

        if ($fichaId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ficha inválida.']);
            return;
        }

        try {
            // Generar datos del reporte
            $reportData = $this->analyticsService->generateMonthlyReport($fichaId, $mes, $año);

            // Formatear para Excel
            $excelData = $this->analyticsService->formatForExcel($reportData);

            // Generar nombre de archivo
            $numeroFicha = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $reportData['metadata']['numero_ficha']);
            $mesNombre = date('m', strtotime($reportData['metadata']['fecha_inicio']));
            $añoNombre = date('Y', strtotime($reportData['metadata']['fecha_inicio']));
            $timestamp = date('Ymd_His');
            $fileName = "reporte_mensual_{$numeroFicha}_{$añoNombre}_{$mesNombre}_{$timestamp}.xlsx";

            // Generar archivo Excel
            $filePath = $this->generateExcelFile($excelData, $fileName, $reportData['metadata']);

            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Reporte mensual generado correctamente.',
                'download_url' => '/exports/' . basename($filePath),
                'file_name' => $fileName,
                'metadata' => $reportData['metadata']
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            error_log('Error generando reporte mensual: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Genera archivo Excel con múltiples hojas
     * 
     * @param array $data Datos formateados para Excel
     * @param string $fileName Nombre del archivo
     * @param array $metadata Metadatos del reporte
     * @return string Ruta del archivo generado
     */
    private function generateExcelFile(array $data, string $fileName, array $metadata): string
    {
        // Directorio de exportaciones
        $exportDir = __DIR__ . '/../../public/exports';
        
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filePath = $exportDir . '/' . $fileName;

        // Crear archivo Excel usando PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remover hoja por defecto

        $sheetIndex = 0;

        // Crear hoja de resumen
        if (isset($data['resumen'])) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Resumen General');
            $spreadsheet->addSheet($sheet, $sheetIndex++);
            
            $this->populateSheet($sheet, $data['resumen']['headers'], $data['resumen']['data']);
        }

        // Crear hoja de aprendices
        if (isset($data['aprendices'])) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Estadísticas Aprendices');
            $spreadsheet->addSheet($sheet, $sheetIndex++);
            
            $this->populateSheet($sheet, $data['aprendices']['headers'], $data['aprendices']['data']);
        }

        // Crear hoja de problemáticos
        if (isset($data['problematicos'])) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Aprendices Problemáticos');
            $spreadsheet->addSheet($sheet, $sheetIndex++);
            
            $this->populateSheet($sheet, $data['problematicos']['headers'], $data['problematicos']['data']);
        }

        // Crear hoja de patrones de tardanzas
        if (isset($data['patrones_tardanzas'])) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Patrones Tardanzas');
            $spreadsheet->addSheet($sheet, $sheetIndex++);
            
            $this->populateSheet($sheet, $data['patrones_tardanzas']['headers'], $data['patrones_tardanzas']['data']);
        }

        // Guardar archivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * Puebla una hoja de Excel con datos
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Hoja a poblar
     * @param array $headers Encabezados
     * @param array $data Datos
     */
    private function populateSheet($sheet, array $headers, array $data): void
    {
        // Escribir encabezados
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF39A900'); // Verde SENA
            $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        // Escribir datos
        $row = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($rowData as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Autoajustar columnas
        foreach (range('A', $col) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
    }

    /**
     * Asegura que existe un token CSRF en la sesión
     * 
     * @return string Token CSRF
     */
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

    /**
     * Valida un token CSRF
     * 
     * @param string $token Token a validar
     * @return bool True si es válido
     */
    private function validateCsrfToken(string $token): bool
    {
        $this->session->start();
        $stored = $this->session->get('_csrf_token');
        
        return is_string($stored) && hash_equals($stored, $token);
    }
    /**
     * Log de errores detallado en logs/analytics_errors.log
     */
    private function logError($mensaje, $contexto = []) {
        $logFile = __DIR__ . '/../../logs/analytics_errors.log';
        $fecha = date('Y-m-d H:i:s');
        $contextoTxt = json_encode($contexto, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        $entry = "[$fecha] $mensaje\n$contextoTxt\n\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
    
    /**
     * Descarga un archivo de reporte Excel
     */
    public function downloadReport(): void
    {
        $user = $this->authService->getCurrentUser();
        
        // Validar permisos
        if (!$user || !in_array($user['rol'], ['admin', 'administrativo'])) {
            http_response_code(403);
            echo 'Acceso denegado';
            return;
        }
        
        // Obtener nombre del archivo de la URL
        $requestUri = $_SERVER['REQUEST_URI'];
        $fileName = basename(parse_url($requestUri, PHP_URL_PATH));
        
        // Validar que el archivo existe
        $filePath = __DIR__ . '/../../public/exports/' . $fileName;
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }
        
        // Validar que es un archivo .xlsx
        if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'xlsx') {
            http_response_code(400);
            echo 'Tipo de archivo no válido';
            return;
        }
        
        // Configurar headers para forzar descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        
        // Limpiar buffer de salida
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Enviar archivo
        readfile($filePath);
        exit;
    }
}
