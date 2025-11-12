<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Session\SessionManager;

/**
 * Servicio de autenticación
 */
class AuthService
{
    private UserRepository $userRepository;
    private SessionManager $session;

    public function __construct(UserRepository $userRepository, SessionManager $session)
    {
        $this->userRepository = $userRepository;
        $this->session = $session;
    }

    /**
     * Intenta autenticar un usuario
     * Retorna los datos del usuario sin el hash si tiene éxito, false si falla
     */
    public function login(string $email, string $password): array|false
    {
        // Buscar usuario por email
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return false;
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Remover el hash antes de crear la sesión
        unset($user['password_hash']);

        // Crear sesión
        $this->createSession($user);

        return $user;
    }

    /**
     * Crea la sesión del usuario
     */
    private function createSession(array $user): void
    {
        $this->session->start();

        // Regenerar ID de sesión para prevenir fijación de sesión
        $this->session->regenerate();

        // Guardar datos del usuario en la sesión
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_email', $user['email']);
        $this->session->set('user_nombre', $user['nombre']);
        $this->session->set('user_role', $user['rol']);
        $this->session->set('user_documento', $user['documento']);
        $this->session->set('authenticated', true);
        $this->session->set('login_time', time());
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * Obtiene el usuario actualmente autenticado
     */
    public function getCurrentUser(): ?array
    {
        $this->session->start();

        if (!$this->session->get('authenticated')) {
            return null;
        }

        $userId = $this->session->get('user_id');
        if (!$userId) {
            return null;
        }

        // Obtener datos actualizados del usuario
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            // Usuario no existe, cerrar sesión
            $this->logout();
            return null;
        }

        // Remover hash de contraseña
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Verifica si hay un usuario autenticado
     */
    public function isAuthenticated(): bool
    {
        $this->session->start();
        return (bool) $this->session->get('authenticated', false);
    }

    /**
     * Obtiene el rol del usuario actual
     */
    public function getCurrentUserRole(): ?string
    {
        $this->session->start();
        return $this->session->get('user_role');
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        return $this->getCurrentUserRole() === $role;
    }

    /**
     * Verifica si el usuario tiene alguno de los roles especificados
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRole = $this->getCurrentUserRole();
        return $userRole && in_array($userRole, $roles, true);
    }

    /**
     * Registra un nuevo usuario
     */
    public function register(array $data): int
    {
        // Hash de la contraseña
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);

        // Crear usuario
        return $this->userRepository->create($data);
    }

    /**
     * Cambia la contraseña de un usuario
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return false;
        }

        // Verificar contraseña actual
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        // Actualizar con nueva contraseña
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->userRepository->update($userId, ['password_hash' => $newHash]);
    }
}

