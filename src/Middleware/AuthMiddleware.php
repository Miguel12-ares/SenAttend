<?php

namespace App\Middleware;

use App\Session\SessionManager;

/**
 * Middleware de autenticación
 * Verifica que el usuario esté autenticado antes de acceder a rutas protegidas
 */
class AuthMiddleware
{
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    /**
     * Verifica si el usuario está autenticado
     * Redirige a login si no lo está
     */
    public function handle(): bool
    {
        $this->session->start();

        // Verificar si existe sesión de usuario
        if (!$this->session->has('user_id') || !$this->session->has('user_email')) {
            // Guardar la URL que intentaba acceder para redirigir después del login
            $this->session->set('intended_url', $_SERVER['REQUEST_URI'] ?? '/');

            // Redirigir a login
            header('Location: /login');
            exit;
        }

        // Usuario autenticado
        return true;
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        $userRole = $this->session->get('user_role');
        return $userRole === $role;
    }

    /**
     * Verifica si el usuario tiene alguno de los roles especificados
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRole = $this->session->get('user_role');
        return in_array($userRole, $roles, true);
    }

    /**
     * Obtiene el usuario actual de la sesión
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->session->has('user_id')) {
            return null;
        }

        return [
            'id' => $this->session->get('user_id'),
            'email' => $this->session->get('user_email'),
            'nombre' => $this->session->get('user_nombre'),
            'rol' => $this->session->get('user_role'),
            'documento' => $this->session->get('user_documento'),
        ];
    }
}

