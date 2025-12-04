<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\GestionEquipos\Services\AprendizEquipoService;
use App\Repositories\AsistenciaRepository;
use App\Session\SessionManager;
use App\Support\Response;

/**
 * Controlador para panel de aprendices
 * Ahora usa el sistema de autenticación unificado
 */
class AprendizAuthController
{
    private AuthService $authService;
    private SessionManager $session;
    private AprendizEquipoService $aprendizEquipoService;
    private AsistenciaRepository $asistenciaRepository;

    public function __construct(
        AuthService $authService,
        SessionManager $session,
        AprendizEquipoService $aprendizEquipoService,
        AsistenciaRepository $asistenciaRepository
    )
    {
        $this->authService = $authService;
        $this->session = $session;
        $this->aprendizEquipoService = $aprendizEquipoService;
        $this->asistenciaRepository = $asistenciaRepository;
    }

    /**
     * Panel básico de aprendiz
     * GET /aprendiz/panel
     */
    public function panel(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        $this->session->start();
        $error = $this->session->getFlash('error') ?? $this->session->getFlash('aprendiz_error');
        $message = $this->session->getFlash('message') ?? $this->session->getFlash('aprendiz_message');
        $success = $this->session->getFlash('success');

        $equipos = $this->aprendizEquipoService->getEquiposDeAprendiz((int)$user['id']);

        require __DIR__ . '/../../views/aprendiz/panel.php';
    }

    /**
     * Muestra las asistencias del aprendiz
     * GET /aprendiz/asistencias
     */
    public function asistencias(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        $this->session->start();
        $error = $this->session->getFlash('error') ?? $this->session->getFlash('aprendiz_error');
        $message = $this->session->getFlash('message') ?? $this->session->getFlash('aprendiz_message');
        $success = $this->session->getFlash('success');

        // Obtener asistencias del aprendiz
        // El ID del usuario cuando es aprendiz ya es el ID del aprendiz en la tabla aprendices
        $aprendizId = (int)$user['id'];
        
        // Obtener asistencias
        $asistencias = $this->asistenciaRepository->findByAprendiz($aprendizId);

        require __DIR__ . '/../../views/aprendiz/asistencias.php';
    }

    /**
     * Genera QR de asistencia para el aprendiz autenticado
     * GET /aprendiz/generar-qr
     */
    public function generarQR(): void
    {
        $user = $this->authService->getCurrentUser();

        if (!$user || $user['rol'] !== 'aprendiz') {
            Response::redirect('/login');
        }

        // Si el aprendiz tiene documento, redirigir a /home con el documento prellenado
        if (!empty($user['documento'])) {
            Response::redirect('/home?documento=' . urlencode($user['documento']));
        } else {
            // Si no tiene documento, redirigir a /home para ingresarlo manualmente
            Response::redirect('/home');
        }
    }
}


