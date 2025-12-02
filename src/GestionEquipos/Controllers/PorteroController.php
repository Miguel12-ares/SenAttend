<?php

namespace App\GestionEquipos\Controllers;

use App\Services\AuthService;
use App\GestionEquipos\Services\PorteroIngresoService;
use App\Session\SessionManager;
use App\Support\Response;

/**
 * Controlador para el módulo de portero
 * Gestiona el escaneo de QR y registro de ingresos/salidas de equipos
 */
class PorteroController
{
    private AuthService $authService;
    private PorteroIngresoService $porteroIngresoService;
    private SessionManager $session;

    public function __construct(
        AuthService $authService,
        PorteroIngresoService $porteroIngresoService,
        SessionManager $session
    ) {
        $this->authService = $authService;
        $this->porteroIngresoService = $porteroIngresoService;
        $this->session = $session;
    }

    /**
     * Verifica que el usuario actual es portero
     */
    private function verificarPortero(): ?array
    {
        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            Response::redirect('/login');
            return null;
        }

        if ($user['rol'] !== 'portero') {
            $this->session->start();
            $this->session->flash('error', 'No tienes permisos para acceder a esta sección');
            Response::redirect('/dashboard');
            return null;
        }

        return $user;
    }

    /**
     * Panel principal del portero
     * GET /portero/panel
     */
    public function panel(): void
    {
        $user = $this->verificarPortero();
        if (!$user) return;

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');
        $success = $this->session->getFlash('success');

        // Obtener ingresos activos
        $ingresosActivos = $this->porteroIngresoService->getIngresosActivos(20, 0);
        $totalActivos = $this->porteroIngresoService->countIngresosActivos();

        require __DIR__ . '/../../../views/portero/panel.php';
    }

    /**
     * Vista de escaneo de QR
     * GET /portero/escanear
     */
    public function escanear(): void
    {
        $user = $this->verificarPortero();
        if (!$user) return;

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');
        $success = $this->session->getFlash('success');
        $ultimoProcesamiento = $this->session->get('ultimo_procesamiento', null);
        if ($ultimoProcesamiento) {
            $this->session->remove('ultimo_procesamiento');
        }

        require __DIR__ . '/../../../views/portero/escanear.php';
    }

    /**
     * Procesa el QR escaneado
     * POST /portero/procesar-qr
     */
    public function procesarQR(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/portero/escanear');
        }

        $user = $this->verificarPortero();
        if (!$user) return;

        $qrData = filter_input(INPUT_POST, 'qr_data', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (empty($qrData)) {
            $this->session->start();
            $this->session->flash('error', 'No se recibieron datos del QR');
            Response::redirect('/portero/escanear');
        }

        $result = $this->porteroIngresoService->procesarQR($qrData, (int)$user['id'], $observaciones);

        $this->session->start();
        
        if ($result['success']) {
            $tipoMensaje = $result['type'] === 'ingreso' ? 'success' : 'message';
            $this->session->flash($tipoMensaje, $result['message']);
            
            // Guardar datos para mostrar en la vista
            if (isset($result['data'])) {
                $this->session->set('ultimo_procesamiento', $result['data']);
            }
        } else {
            $this->session->flash('error', $result['message']);
        }

        Response::redirect('/portero/escanear');
    }

    /**
     * API: Procesa QR vía AJAX (para escaneo en tiempo real)
     * POST /api/portero/procesar-qr
     */
    public function apiProcesarQR(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $user = $this->authService->getCurrentUser();
        
        if (!$user || $user['rol'] !== 'portero') {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }

        $qrData = $data['qr_data'] ?? '';
        $observaciones = $data['observaciones'] ?? null;

        if (empty($qrData)) {
            Response::json(['success' => false, 'message' => 'No se recibieron datos del QR'], 400);
            return;
        }

        $result = $this->porteroIngresoService->procesarQR($qrData, (int)$user['id'], $observaciones);
        
        $statusCode = $result['success'] ? 200 : 400;
        Response::json($result, $statusCode);
    }

    /**
     * API: Obtiene ingresos activos
     * GET /api/portero/ingresos-activos
     */
    public function apiIngresosActivos(): void
    {
        $user = $this->authService->getCurrentUser();
        
        if (!$user || $user['rol'] !== 'portero') {
            Response::json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;
        $offset = ($page - 1) * $limit;

        $ingresos = $this->porteroIngresoService->getIngresosActivos($limit, $offset);
        $total = $this->porteroIngresoService->countIngresosActivos();

        Response::json([
            'success' => true,
            'data' => $ingresos,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
}

