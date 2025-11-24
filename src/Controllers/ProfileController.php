<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Session\SessionManager;
use App\Support\Response;

/**
 * Controlador de perfil de usuario
 */
class ProfileController
{
    private AuthService $authService;
    private SessionManager $session;

    public function __construct(AuthService $authService, SessionManager $session)
    {
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Muestra la vista del perfil del usuario
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            Response::redirect('/login');
        }

        $this->session->start();
        $error = $this->session->getFlash('error');
        $success = $this->session->getFlash('success');
        
        // Incluir la vista
        require __DIR__ . '/../../views/profile/index.php';
    }

    /**
     * Procesa el cambio de contraseña (POST)
     */
    public function cambiarPassword(): void
    {
        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/perfil');
        }

        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            Response::redirect('/login');
        }

        // Obtener datos del formulario
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validaciones
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->session->flash('error', 'Por favor complete todos los campos');
            Response::redirect('/perfil');
        }

        if ($newPassword !== $confirmPassword) {
            $this->session->flash('error', 'Las contraseñas nuevas no coinciden');
            Response::redirect('/perfil');
        }

        if (strlen($newPassword) < 6) {
            $this->session->flash('error', 'La contraseña debe tener al menos 6 caracteres');
            Response::redirect('/perfil');
        }

        // Intentar cambiar la contraseña
        $success = $this->authService->changePassword(
            $user['id'],
            $currentPassword,
            $newPassword
        );

        if ($success) {
            $this->session->flash('success', 'Contraseña cambiada exitosamente');
        } else {
            $this->session->flash('error', 'La contraseña actual es incorrecta');
        }

        Response::redirect('/perfil');
    }
}

