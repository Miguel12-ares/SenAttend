<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\UserRepository;

/**
 * Controlador del Dashboard principal
 */
class DashboardController
{
    private AuthService $authService;
    private FichaRepository $fichaRepository;
    private AprendizRepository $aprendizRepository;
    private UserRepository $userRepository;

    public function __construct(
        AuthService $authService,
        FichaRepository $fichaRepository,
        AprendizRepository $aprendizRepository,
        UserRepository $userRepository
    ) {
        $this->authService = $authService;
        $this->fichaRepository = $fichaRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Vista principal del dashboard
     * Requiere autenticación (protegida por AuthMiddleware)
     */
    public function index(): void
    {
        // Obtener usuario actual
        $user = $this->authService->getCurrentUser();

        // Obtener estadísticas básicas
        $stats = [
            'total_fichas' => $this->fichaRepository->count(),
            'total_aprendices' => $this->aprendizRepository->count(),
            'total_usuarios' => $this->userRepository->count(),
        ];

        // Obtener fichas activas recientes
        $fichasActivas = $this->fichaRepository->findActive(10, 0);

        // Incluir la vista
        require __DIR__ . '/../../views/dashboard/index.php';
    }
}

