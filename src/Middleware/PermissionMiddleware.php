<?php

namespace App\Middleware;

use App\Session\SessionManager;

/**
 * Middleware centralizado de permisos (RBAC)
 *
 * - Verifica el rol del usuario para cada petición
 * - Aplica matriz de permisos definida en config/permissions_config.php
 * - Bloquea y registra intentos de acceso no autorizado
 */
class PermissionMiddleware
{
    private SessionManager $session;

    /**
     * @var array Matriz de permisos cargada desde permissions_config.php
     */
    private array $config;

    public function __construct(SessionManager $session, array $config)
    {
        $this->session = $session;
        $this->config = $config['permissions'] ?? [];
    }

    /**
     * Autoriza una petición según método, URI y rol de usuario.
     *
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $uri URI normalizada (sin query string)
     * @return bool true si está autorizado; si no, redirige y termina el script.
     */
    public function authorize(string $method, string $uri): bool
    {
        $this->session->start();

        $userRole = $this->session->get('user_role');

        // Rutas públicas: si no hay usuario y la ruta no exige rol, se permite.
        // Si hay matriz de roles y la lista está vacía, se considera pública.
        $allowedRoles = $this->getAllowedRolesForRoute($method, $uri);

        if ($allowedRoles === null) {
            // Ruta no mapeada en la matriz de permisos:
            // por compatibilidad, permitimos el acceso, pero se recomienda
            // ir agregando aquí todas las rutas sensibles.
            return true;
        }

        if (empty($allowedRoles)) {
            // Ruta explícitamente pública
            return true;
        }

        // Si la ruta exige roles, pero no hay usuario autenticado, bloquear
        if (!$userRole) {
            $this->logDeniedAccess($method, $uri, null, 'NO_AUTH');
            $this->redirectForbidden('/login');
        }

        // Verificar si el rol del usuario está permitido
        if (!in_array($userRole, $allowedRoles, true)) {
            $this->logDeniedAccess($method, $uri, $userRole, 'ROLE_NOT_ALLOWED');
            
            // Si ya estamos en dashboard y no tiene permiso, redirigir a login para evitar bucle
            if ($uri === '/dashboard') {
                $this->session->destroy(); // Cerrar sesión si no tiene acceso al dashboard
                $this->redirectForbidden('/login?error=access_denied');
            }
            
            $this->redirectForbidden('/dashboard');
        }

        return true;
    }

    /**
     * Obtiene los roles permitidos para una ruta y método.
     *
     * @return array|null Array de roles permitidos, [] para pública,
     *                    null si la ruta no está definida en la matriz.
     */
    private function getAllowedRolesForRoute(string $method, string $uri): ?array
    {
        $method = strtoupper($method);

        $exact = $this->config['exact'][$method] ?? [];

        // Coincidencia exacta primero
        if (array_key_exists($uri, $exact)) {
            return $exact[$uri];
        }

        // Coincidencia por patrones (rutas dinámicas)
        $patterns = $this->config['patterns'][$method] ?? [];
        foreach ($patterns as $patternConfig) {
            if (!isset($patternConfig['pattern'], $patternConfig['roles'])) {
                continue;
            }

            if (preg_match($patternConfig['pattern'], $uri)) {
                return $patternConfig['roles'];
            }
        }

        // Ruta no configurada en la matriz
        return null;
    }

    /**
     * Registra intentos de acceso denegado en el log de errores de PHP.
     * Se puede cambiar a un archivo dedicado si se requiere.
     */
    private function logDeniedAccess(string $method, string $uri, ?string $role, string $reason): void
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'uri' => $uri,
            'role' => $role,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        error_log('RBAC_DENIED: ' . json_encode($entry));
    }

    /**
     * Envía cabecera 403 y redirige a una ruta segura.
     */
    private function redirectForbidden(string $redirectTo): void
    {
        http_response_code(403);
        header('Location: ' . $redirectTo);
        exit;
    }
}


