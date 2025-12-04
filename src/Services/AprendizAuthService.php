<?php

namespace App\Services;

use App\Repositories\AprendizRepository;
use App\Session\SessionManager;

/**
 * Servicio de autenticación para Aprendices
 * Permite login usando email y contraseña basada en documento.
 */
class AprendizAuthService
{
    private AprendizRepository $aprendizRepository;
    private SessionManager $session;

    public function __construct(AprendizRepository $aprendizRepository, SessionManager $session)
    {
        $this->aprendizRepository = $aprendizRepository;
        $this->session = $session;
    }

    /**
     * Intenta autenticar un aprendiz por email y contraseña.
     * Retorna los datos del aprendiz sin el hash si tiene éxito, false si falla.
     */
    public function login(string $email, string $password): array|false
    {
        $aprendiz = $this->aprendizRepository->findByEmail($email);

        if (!$aprendiz || empty($aprendiz['password_hash']) || $aprendiz['estado'] !== 'activo') {
            return false;
        }

        if (!password_verify($password, $aprendiz['password_hash'])) {
            return false;
        }

        unset($aprendiz['password_hash']);

        $this->createSession($aprendiz);

        return $aprendiz;
    }

    /**
     * Crea la sesión específica de aprendiz (no interfiere con la de usuarios internos).
     */
    private function createSession(array $aprendiz): void
    {
        $this->session->start();
        $this->session->regenerate();

        $this->session->set('aprendiz_authenticated', true);
        $this->session->set('aprendiz_id', $aprendiz['id']);
        $this->session->set('aprendiz_email', $aprendiz['email']);
        $this->session->set('aprendiz_nombre', $aprendiz['nombre']);
        $this->session->set('aprendiz_apellido', $aprendiz['apellido']);
        $this->session->set('aprendiz_documento', $aprendiz['documento']);
        $this->session->set('aprendiz_login_time', time());
    }

    public function logout(): void
    {
        $this->session->start();
        $this->session->remove('aprendiz_authenticated');
        $this->session->remove('aprendiz_id');
        $this->session->remove('aprendiz_email');
        $this->session->remove('aprendiz_nombre');
        $this->session->remove('aprendiz_apellido');
        $this->session->remove('aprendiz_documento');
        $this->session->remove('aprendiz_login_time');
    }

    public function isAuthenticated(): bool
    {
        $this->session->start();
        return (bool) $this->session->get('aprendiz_authenticated', false);
    }

    public function getCurrentAprendiz(): ?array
    {
        $this->session->start();

        if (!$this->session->get('aprendiz_authenticated')) {
            return null;
        }

        $id = $this->session->get('aprendiz_id');
        if (!$id) {
            return null;
        }

        $aprendiz = $this->aprendizRepository->findById((int)$id);
        if (!$aprendiz) {
            $this->logout();
            return null;
        }

        return $aprendiz;
    }
}


