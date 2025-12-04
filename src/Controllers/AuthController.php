<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Session\SessionManager;
use App\Support\Response;

/**
 * Controlador de autenticación
 */
class AuthController
{
    private AuthService $authService;
    private SessionManager $session;

    public function __construct(AuthService $authService, SessionManager $session)
    {
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Muestra la vista de login
     */
    public function viewLogin(): void
    {
        // Si ya está autenticado, redirigir según el rol
        if ($this->authService->isAuthenticated()) {
            $user = $this->authService->getCurrentUser();
            $userRole = $user['rol'] ?? null;
            
            // Redirigir según el rol
            switch ($userRole) {
                case 'portero':
                    Response::redirect('/portero/panel');
                    break;
                case 'aprendiz':
                    Response::redirect('/aprendiz/panel');
                    break;
                default:
                    Response::redirect('/dashboard');
                    break;
            }
        }

        $this->session->start();
        $error = $this->session->getFlash('error');
        $message = $this->session->getFlash('message');

        // Incluir la vista
        require __DIR__ . '/../../views/auth/login.php';
    }

    /**
     * Procesa el login (POST)
     */
    public function login(): void
    {
        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/login');
        }

        // Obtener y sanitizar datos
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // Validaciones básicas
        if (empty($email) || empty($password)) {
            $this->session->flash('error', 'Por favor complete todos los campos');
            Response::redirect('/login');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email inválido');
            Response::redirect('/login');
        }

        // Intentar autenticar
        $user = $this->authService->login($email, $password);

        if (!$user) {
            // Credenciales inválidas - mensaje genérico por seguridad
            $this->session->flash('error', 'Credenciales incorrectas');
            Response::redirect('/login');
        }

        // Login exitoso - redirigir según el rol del usuario
        $intendedUrl = $this->session->get('intended_url');
        $this->session->remove('intended_url');

        // Si no hay URL previa, redirigir según el rol
        if (!$intendedUrl) {
            $userRole = $user['rol'] ?? null;
            
            // Redirigir al panel específico según el rol
            switch ($userRole) {
                case 'portero':
                    $intendedUrl = '/portero/panel';
                    break;
                case 'aprendiz':
                    // Los aprendices van a su panel específico
                    $intendedUrl = '/aprendiz/panel';
                    break;
                case 'admin':
                case 'administrativo':
                case 'instructor':
                default:
                    $intendedUrl = '/dashboard';
                    break;
            }
        }

        Response::redirect($intendedUrl);
    }

    /**
     * Cierra la sesión (logout)
     */
    public function logout(): void
    {
        // Iniciar sesión primero
        $this->session->start();
        
        // Cerrar sesión
        $this->authService->logout();
        
        // Flash message debe establecerse DESPUÉS de iniciar nueva sesión
        $this->session->start();
        $this->session->flash('message', 'Sesión cerrada exitosamente');
        
        Response::redirect('/login');
    }
}

