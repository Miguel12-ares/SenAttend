<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\GestionEquipos\Services\AprendizEquipoService;
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

    public function __construct(
        AuthService $authService,
        SessionManager $session,
        AprendizEquipoService $aprendizEquipoService
    )
    {
        $this->authService = $authService;
        $this->session = $session;
        $this->aprendizEquipoService = $aprendizEquipoService;
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
}


