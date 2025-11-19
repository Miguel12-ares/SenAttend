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

        // Agregar conteo de aprendices
        foreach ($fichas as &$ficha) {
            $ficha['total_aprendices'] = $this->fichaRepository->countAprendices($ficha['id']);
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

    /**
     * API: Crea una nueva ficha (JSON)
     * POST /api/fichas
     */
    public function apiCreate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'errors' => ['Método no permitido']], 405);
            return;
        }

        // Obtener datos JSON del body
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $data = $_POST; // Fallback a POST form data
        }

        // Usar el Service con validación
        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->create($data);
        
        $statusCode = $result['success'] ? 201 : 400;
        Response::json($result, $statusCode);
    }

    /**
     * API: Actualiza una ficha (JSON)
     * PUT /api/fichas/{id}
     */
    public function apiUpdate(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'errors' => ['Método no permitido']], 405);
            return;
        }

        // Obtener datos JSON del body
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $data = $_POST;
        }

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->update($id, $data);
        
        $statusCode = $result['success'] ? 200 : 400;
        Response::json($result, $statusCode);
    }

    /**
     * API: Elimina una ficha (JSON)
     * DELETE /api/fichas/{id}
     */
    public function apiDelete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'errors' => ['Método no permitido']], 405);
            return;
        }

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->delete($id);
        
        $statusCode = $result['success'] ? 200 : 400;
        Response::json($result, $statusCode);
    }

    /**
     * API: Obtiene una ficha específica (JSON)
     * GET /api/fichas/{id}
     */
    public function apiShow(int $id): void
    {
        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $ficha = $fichaService->getFichaDetalle($id);

        if (!$ficha) {
            Response::json(['success' => false, 'error' => 'Ficha no encontrada'], 404);
            return;
        }

        Response::json(['success' => true, 'data' => $ficha]);
    }

    /**
     * API: Búsqueda avanzada de fichas (JSON)
     * GET /api/fichas/search
     */
    public function apiSearch(): void
    {
        $filters = [
            'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '',
            'estado' => filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '',
            'fecha_desde' => filter_input(INPUT_GET, 'fecha_desde', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '',
            'fecha_hasta' => filter_input(INPUT_GET, 'fecha_hasta', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '',
        ];

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->getFichasAdvanced($filters, $page, $limit);
        
        // Agregar conteo de aprendices a cada ficha
        foreach ($result['data'] as &$ficha) {
            $ficha['total_aprendices'] = $this->fichaRepository->countAprendices($ficha['id']);
        }
        
        Response::json(['success' => true, 'result' => $result]);
    }

    /**
     * API: Cambia el estado de una ficha (JSON)
     * POST /api/fichas/{id}/estado
     */
    public function apiCambiarEstado(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'errors' => ['Método no permitido']], 405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }

        if (!isset($data['estado'])) {
            Response::json(['success' => false, 'errors' => ['Estado no proporcionado']], 400);
            return;
        }

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->cambiarEstado($id, $data['estado']);
        
        $statusCode = $result['success'] ? 200 : 400;
        Response::json($result, $statusCode);
    }

    /**
     * API: Importa fichas desde CSV (JSON)
     * POST /api/fichas/importar
     */
    public function apiImportarCSV(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'errors' => ['Método no permitido']], 405);
            return;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            Response::json(['success' => false, 'errors' => ['Error al subir el archivo']], 400);
            return;
        }

        // Validar extensión usando el nombre original
        $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            Response::json(['success' => false, 'errors' => ['El archivo debe ser CSV']], 400);
            return;
        }

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->importarCSV($_FILES['csv_file']['tmp_name']);
        
        $statusCode = $result['success'] ? 200 : 400;
        Response::json($result, $statusCode);
    }

    /**
     * API: Valida un archivo CSV antes de importar fichas (JSON)
     * POST /api/fichas/validar-csv
     */
    public function apiValidarCSV(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'errors' => ['Método no permitido']], 405);
            return;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            Response::json(['success' => false, 'errors' => ['Error al subir el archivo']], 400);
            return;
        }

        // Validar extensión usando el nombre original
        $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            Response::json(['success' => false, 'errors' => ['El archivo debe ser CSV']], 400);
            return;
        }

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->validarFormatoCSV($_FILES['csv_file']['tmp_name']);
        Response::json($result);
    }

    /**
     * API: Obtiene estadísticas de fichas (JSON)
     * GET /api/fichas/estadisticas
     */
    public function apiEstadisticas(): void
    {
        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $stats = $fichaService->getEstadisticas();
        Response::json(['success' => true, 'data' => $stats]);
    }

    /**
     * API: Asigna un aprendiz a una ficha con validación de cupo (JSON)
     * POST /api/fichas/{id}/asignar-aprendiz
     */
    public function apiAsignarAprendiz(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['success' => false, 'errors' => ['Método no permitido']], 405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }

        if (!isset($data['aprendiz_id'])) {
            Response::json(['success' => false, 'errors' => ['ID de aprendiz no proporcionado']], 400);
            return;
        }

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $cupoMaximo = $data['cupo_maximo'] ?? 30;
        $result = $fichaService->assignAprendiz($id, (int)$data['aprendiz_id'], $cupoMaximo);
        
        $statusCode = $result['success'] ? 200 : 400;
        Response::json($result, $statusCode);
    }

    /**
     * API: Valida cupo disponible en una ficha (JSON)
     * GET /api/fichas/{id}/cupo
     */
    public function apiValidarCupo(int $id): void
    {
        $cupoMaximo = filter_input(INPUT_GET, 'cupo_maximo', FILTER_VALIDATE_INT) ?: 30;

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $result = $fichaService->validarCupoDisponible($id, $cupoMaximo);
        Response::json(['success' => true, 'data' => $result]);
    }

    /**
     * API: Busca fichas por número (JSON)
     * GET /api/fichas/buscar-numero?numero=XXX&exacto=true/false
     */
    public function apiBuscarPorNumero(): void
    {
        $numero = filter_input(INPUT_GET, 'numero', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $exacto = filter_input(INPUT_GET, 'exacto', FILTER_VALIDATE_BOOLEAN);

        if (!$numero) {
            Response::json(['success' => false, 'errors' => ['Número de ficha requerido']], 400);
            return;
        }

        $fichaService = new \App\Services\FichaService(
            $this->fichaRepository,
            $this->aprendizRepository
        );

        $fichas = $fichaService->searchByNumeroFicha($numero, $exacto);
        Response::json(['success' => true, 'data' => $fichas]);
    }
}

