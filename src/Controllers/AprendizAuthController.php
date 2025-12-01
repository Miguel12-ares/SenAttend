<?php

namespace App\Controllers;

use App\Services\AprendizAuthService;
use App\GestionEquipos\Services\AprendizEquipoService;
use App\Session\SessionManager;
use App\Support\Response;

/**
 * Controlador de autenticación para aprendices
 * Flujo separado del login de usuarios internos.
 */
class AprendizAuthController
{
    private AprendizAuthService $authService;
    private SessionManager $session;
    private AprendizEquipoService $aprendizEquipoService;

    public function __construct(
        AprendizAuthService $authService,
        SessionManager $session,
        AprendizEquipoService $aprendizEquipoService
    )
    {
        $this->authService = $authService;
        $this->session = $session;
        $this->aprendizEquipoService = $aprendizEquipoService;
    }

    /**
     * Muestra la vista de login de aprendices
     * GET /aprendiz/login
     */
    public function viewLogin(): void
    {
        // Si el aprendiz ya está autenticado, más adelante lo enviaremos a su panel
        if ($this->authService->isAuthenticated()) {
            Response::redirect('/aprendiz/panel');
        }

        $this->session->start();
        $error = $this->session->getFlash('aprendiz_error');
        $message = $this->session->getFlash('aprendiz_message');

        require __DIR__ . '/../../views/auth/aprendiz-login.php';
    }

    /**
     * Procesa el login de aprendiz
     * POST /aprendiz/auth/login
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendiz/login');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->session->start();
            $this->session->flash('aprendiz_error', 'Por favor complete todos los campos');
            Response::redirect('/aprendiz/login');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->start();
            $this->session->flash('aprendiz_error', 'Correo electrónico inválido');
            Response::redirect('/aprendiz/login');
        }

        $aprendiz = $this->authService->login($email, $password);

        if (!$aprendiz) {
            $this->session->start();
            $this->session->flash('aprendiz_error', 'Credenciales incorrectas o aprendiz inactivo');
            Response::redirect('/aprendiz/login');
        }

        Response::redirect('/aprendiz/panel');
    }

    /**
     * Cierra la sesión del aprendiz
     * GET /aprendiz/logout
     */
    public function logout(): void
    {
        $this->authService->logout();

        $this->session->start();
        $this->session->flash('aprendiz_message', 'Sesión de aprendiz cerrada correctamente');

        Response::redirect('/aprendiz/login');
    }

    /**
     * Panel básico de aprendiz (placeholder para siguientes pasos)
     * GET /aprendiz/panel
     */
    public function panel(): void
    {
        $aprendiz = $this->authService->getCurrentAprendiz();

        if (!$aprendiz) {
            Response::redirect('/aprendiz/login');
        }

        $this->session->start();
        $error = $this->session->getFlash('aprendiz_error');
        $message = $this->session->getFlash('aprendiz_message');

        $equipos = $this->aprendizEquipoService->getEquiposDeAprendiz((int)$aprendiz['id']);

        require __DIR__ . '/../../views/aprendiz/panel.php';
    }
}


