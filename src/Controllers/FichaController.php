<?php

namespace App\Controllers;

use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;
use App\Services\AuthService;
use App\Support\Response;

/**
 * Controlador para gestión completa de Fichas
 * Sprint 3 - CRUD Completo
 */
class FichaController
{
    private FichaRepository $fichaRepository;
    private AprendizRepository $aprendizRepository;
    private AuthService $authService;

    public function __construct(
        FichaRepository $fichaRepository,
        AprendizRepository $aprendizRepository,
        AuthService $authService
    ) {
        $this->fichaRepository = $fichaRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->authService = $authService;
    }

    /**
     * Lista todas las fichas con paginación y búsqueda
     * GET /fichas
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();
        
        // Parámetros de búsqueda y paginación
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        $estado = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

        // Obtener fichas según filtros
        if ($search) {
            $fichas = $this->fichaRepository->search($search, $limit, $offset);
            $total = $this->fichaRepository->countSearch($search);
        } elseif ($estado) {
            $fichas = $this->fichaRepository->findByEstado($estado, $limit, $offset);
            $total = $this->fichaRepository->countByEstado($estado);
        } else {
            $fichas = $this->fichaRepository->findAll($limit, $offset);
            $total = $this->fichaRepository->count();
        }

        // Calcular paginación
        $totalPages = ceil($total / $limit);

        require __DIR__ . '/../../views/fichas/index.php';
    }

    /**
     * Muestra detalles de una ficha
     * GET /fichas/{id}
     */
    public function show(int $id): void
    {
        $user = $this->authService->getCurrentUser();
        $ficha = $this->fichaRepository->findById($id);

        if (!$ficha) {
            Response::notFound();
        }

        // Obtener aprendices de la ficha
        $aprendices = $this->aprendizRepository->findByFicha($id, 100, 0);
        $totalAprendices = $this->fichaRepository->countAprendices($id);

        require __DIR__ . '/../../views/fichas/show.php';
    }

    /**
     * Muestra formulario para crear ficha
     * GET /fichas/crear
     */
    public function create(): void
    {
        $user = $this->authService->getCurrentUser();
        require __DIR__ . '/../../views/fichas/create.php';
    }

    /**
     * Almacena una nueva ficha
     * POST /fichas
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/fichas');
        }

        // Validar datos
        $numeroFicha = filter_input(INPUT_POST, 'numero_ficha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'activa';

        $errors = [];

        if (empty($numeroFicha)) {
            $errors[] = 'El número de ficha es requerido';
        } elseif ($this->fichaRepository->findByNumero($numeroFicha)) {
            $errors[] = 'El número de ficha ya existe';
        }

        if (empty($nombre)) {
            $errors[] = 'El nombre es requerido';
        }

        if (!in_array($estado, ['activa', 'finalizada'])) {
            $errors[] = 'Estado inválido';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/fichas/crear');
        }

        // Crear ficha
        try {
            $this->fichaRepository->create([
                'numero_ficha' => $numeroFicha,
                'nombre' => $nombre,
                'estado' => $estado,
            ]);

            $_SESSION['success'] = 'Ficha creada exitosamente';
            Response::redirect('/fichas');
        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al crear la ficha'];
            Response::redirect('/fichas/crear');
        }
    }

    /**
     * Muestra formulario para editar ficha
     * GET /fichas/{id}/editar
     */
    public function edit(int $id): void
    {
        $user = $this->authService->getCurrentUser();
        $ficha = $this->fichaRepository->findById($id);

        if (!$ficha) {
            Response::notFound();
        }

        require __DIR__ . '/../../views/fichas/edit.php';
    }

    /**
     * Actualiza una ficha
     * POST /fichas/{id}
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/fichas');
        }

        $ficha = $this->fichaRepository->findById($id);
        if (!$ficha) {
            Response::notFound();
        }

        // Validar datos
        $numeroFicha = filter_input(INPUT_POST, 'numero_ficha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $errors = [];

        if (empty($numeroFicha)) {
            $errors[] = 'El número de ficha es requerido';
        } elseif ($numeroFicha !== $ficha['numero_ficha']) {
            $existing = $this->fichaRepository->findByNumero($numeroFicha);
            if ($existing && $existing['id'] != $id) {
                $errors[] = 'El número de ficha ya existe';
            }
        }

        if (empty($nombre)) {
            $errors[] = 'El nombre es requerido';
        }

        if (!in_array($estado, ['activa', 'finalizada'])) {
            $errors[] = 'Estado inválido';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect("/fichas/{$id}/editar");
        }

        // Actualizar ficha
        try {
            $this->fichaRepository->update($id, [
                'numero_ficha' => $numeroFicha,
                'nombre' => $nombre,
                'estado' => $estado,
            ]);

            $_SESSION['success'] = 'Ficha actualizada exitosamente';
            Response::redirect('/fichas');
        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al actualizar la ficha'];
            Response::redirect("/fichas/{$id}/editar");
        }
    }

    /**
     * Elimina una ficha
     * POST /fichas/{id}/eliminar
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/fichas');
        }

        // Verificar que no tenga aprendices asignados
        $totalAprendices = $this->fichaRepository->countAprendices($id);
        
        if ($totalAprendices > 0) {
            $_SESSION['errors'] = ['No se puede eliminar una ficha con aprendices asignados'];
            Response::redirect('/fichas');
        }

        try {
            $this->fichaRepository->delete($id);
            $_SESSION['success'] = 'Ficha eliminada exitosamente';
        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al eliminar la ficha'];
        }

        Response::redirect('/fichas');
    }

    /**
     * API: Lista fichas activas (JSON)
     * GET /api/fichas
     */
    public function apiList(): void
    {
        $fichas = $this->fichaRepository->findActive(100, 0);
        Response::json($fichas);
    }

    /**
     * API: Obtiene aprendices de una ficha (JSON)
     * GET /api/fichas/{id}/aprendices
     */
    public function apiAprendices(int $id): void
    {
        $aprendices = $this->aprendizRepository->findByFicha($id, 500, 0);
        Response::json($aprendices);
    }
}

