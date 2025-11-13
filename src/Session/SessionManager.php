<?php

namespace App\Session;

/**
 * Gestor de sesiones con seguridad mejorada
 */
class SessionManager
{
    private bool $started = false;

    /**
     * Inicia la sesión si no está iniciada
     */
    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Configuración de cookies seguras
        session_set_cookie_params([
            'lifetime' => 0, // Hasta que se cierre el navegador
            'path' => '/',
            'domain' => '',
            'secure' => false, // Cambiar a true en producción con HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();
        $this->started = true;

        // Prevenir fijación de sesión en nuevas sesiones
        if (!$this->has('_initialized')) {
            $this->regenerate();
            $this->set('_initialized', true);
        }
    }

    /**
     * Regenera el ID de sesión (previene fijación de sesión)
     */
    public function regenerate(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_regenerate_id(true);
        }
        return false;
    }

    /**
     * Establece un valor en la sesión
     */
    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene un valor de la sesión
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica si existe una clave en la sesión
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * Elimina un valor de la sesión
     */
    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * Destruye completamente la sesión
     */
    public function destroy(): void
    {
        // Iniciar sesión si no está activa
        $this->start();
        
        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Eliminar cookie de sesión del navegador
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Strict'
                ]
            );
        }

        // Destruir el archivo de sesión del servidor
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        $this->started = false;
    }

    /**
     * Obtiene todos los datos de la sesión
     */
    public function all(): array
    {
        $this->start();
        return $_SESSION;
    }

    /**
     * Limpia todos los datos de la sesión sin destruirla
     */
    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }

    /**
     * Establece un mensaje flash
     */
    public function flash(string $key, mixed $value): void
    {
        $this->set('_flash', array_merge(
            $this->get('_flash', []),
            [$key => $value]
        ));
    }

    /**
     * Obtiene y elimina un mensaje flash
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $flash = $this->get('_flash', []);
        $value = $flash[$key] ?? $default;

        unset($flash[$key]);
        $this->set('_flash', $flash);

        return $value;
    }

    /**
     * Obtiene todos los mensajes flash y los limpia
     */
    public function getAllFlash(): array
    {
        $flash = $this->get('_flash', []);
        $this->remove('_flash');
        return $flash;
    }
}

