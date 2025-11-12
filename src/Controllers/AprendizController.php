<?php

namespace App\Controllers;

use App\Repositories\AprendizRepository;
use App\Repositories\FichaRepository;
use App\Services\AuthService;
use App\Support\Response;

/**
 * Controlador para gestión completa de Aprendices
 * Sprint 3 - CRUD Completo
 */
class AprendizController
{
    private AprendizRepository $aprendizRepository;
    private FichaRepository $fichaRepository;
    private AuthService $authService;

    public function __construct(
        AprendizRepository $aprendizRepository,
        FichaRepository $fichaRepository,
        AuthService $authService
    ) {
        $this->aprendizRepository = $aprendizRepository;
        $this->fichaRepository = $fichaRepository;
        $this->authService = $authService;
    }

    /**
     * Lista todos los aprendices con paginación y búsqueda
     * GET /aprendices
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();
        
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        $fichaId = filter_input(INPUT_GET, 'ficha', FILTER_VALIDATE_INT);
        $estado = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

        // Obtener aprendices según filtros
        if ($fichaId) {
            $aprendices = $this->aprendizRepository->findByFicha($fichaId, $limit, $offset);
            $total = count($this->aprendizRepository->findByFicha($fichaId, 1000, 0));
        } elseif ($search) {
            $aprendices = $this->aprendizRepository->search($search, $limit, $offset);
            $total = $this->aprendizRepository->countSearch($search);
        } elseif ($estado) {
            $aprendices = $this->aprendizRepository->findByEstado($estado, $limit, $offset);
            $total = $this->aprendizRepository->countByEstado($estado);
        } else {
            $aprendices = $this->aprendizRepository->findAll($limit, $offset);
            $total = $this->aprendizRepository->count();
        }

        $totalPages = ceil($total / $limit);
        
        // Obtener todas las fichas para el filtro
        $fichas = $this->fichaRepository->findActive(100, 0);

        require __DIR__ . '/../../views/aprendices/index.php';
    }

    /**
     * Muestra detalles de un aprendiz
     * GET /aprendices/{id}
     */
    public function show(int $id): void
    {
        $user = $this->authService->getCurrentUser();
        $aprendiz = $this->aprendizRepository->findById($id);

        if (!$aprendiz) {
            Response::notFound();
        }

        // Obtener fichas del aprendiz
        $fichas = $this->aprendizRepository->getFichas($id);

        require __DIR__ . '/../../views/aprendices/show.php';
    }

    /**
     * Muestra formulario para crear aprendiz
     * GET /aprendices/crear
     */
    public function create(): void
    {
        $user = $this->authService->getCurrentUser();
        $fichas = $this->fichaRepository->findActive(100, 0);
        require __DIR__ . '/../../views/aprendices/create.php';
    }

    /**
     * Almacena un nuevo aprendiz
     * POST /aprendices
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendices');
        }

        // Validar datos
        $documento = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $codigoCarnet = filter_input(INPUT_POST, 'codigo_carnet', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'activo';
        $fichaId = filter_input(INPUT_POST, 'ficha_id', FILTER_VALIDATE_INT);

        $errors = [];

        if (empty($documento)) {
            $errors[] = 'El documento es requerido';
        } elseif ($this->aprendizRepository->findByDocumento($documento)) {
            $errors[] = 'El documento ya existe';
        }

        if (empty($nombre)) {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($apellido)) {
            $errors[] = 'El apellido es requerido';
        }

        if (!in_array($estado, ['activo', 'retirado'])) {
            $errors[] = 'Estado inválido';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/aprendices/crear');
        }

        // Crear aprendiz
        try {
            $aprendizId = $this->aprendizRepository->create([
                'documento' => $documento,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'codigo_carnet' => $codigoCarnet,
                'estado' => $estado,
            ]);

            // Vincular con ficha si se seleccionó
            if ($fichaId) {
                $this->aprendizRepository->attachToFicha($aprendizId, $fichaId);
            }

            $_SESSION['success'] = 'Aprendiz creado exitosamente';
            Response::redirect('/aprendices');
        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al crear el aprendiz: ' . $e->getMessage()];
            Response::redirect('/aprendices/crear');
        }
    }

    /**
     * Muestra formulario para editar aprendiz
     * GET /aprendices/{id}/editar
     */
    public function edit(int $id): void
    {
        $user = $this->authService->getCurrentUser();
        $aprendiz = $this->aprendizRepository->findById($id);

        if (!$aprendiz) {
            Response::notFound();
        }

        $fichas = $this->fichaRepository->findActive(100, 0);
        $fichasAsignadas = $this->aprendizRepository->getFichas($id);

        require __DIR__ . '/../../views/aprendices/edit.php';
    }

    /**
     * Actualiza un aprendiz
     * POST /aprendices/{id}
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendices');
        }

        $aprendiz = $this->aprendizRepository->findById($id);
        if (!$aprendiz) {
            Response::notFound();
        }

        // Validar datos
        $documento = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $codigoCarnet = filter_input(INPUT_POST, 'codigo_carnet', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $errors = [];

        if (empty($documento)) {
            $errors[] = 'El documento es requerido';
        } elseif ($documento !== $aprendiz['documento']) {
            $existing = $this->aprendizRepository->findByDocumento($documento);
            if ($existing && $existing['id'] != $id) {
                $errors[] = 'El documento ya existe';
            }
        }

        if (empty($nombre)) {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($apellido)) {
            $errors[] = 'El apellido es requerido';
        }

        if (!in_array($estado, ['activo', 'retirado'])) {
            $errors[] = 'Estado inválido';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect("/aprendices/{$id}/editar");
        }

        // Actualizar aprendiz
        try {
            $this->aprendizRepository->update($id, [
                'documento' => $documento,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'codigo_carnet' => $codigoCarnet,
                'estado' => $estado,
            ]);

            $_SESSION['success'] = 'Aprendiz actualizado exitosamente';
            Response::redirect('/aprendices');
        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al actualizar el aprendiz'];
            Response::redirect("/aprendices/{$id}/editar");
        }
    }

    /**
     * Elimina un aprendiz
     * POST /aprendices/{id}/eliminar
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendices');
        }

        try {
            $this->aprendizRepository->delete($id);
            $_SESSION['success'] = 'Aprendiz eliminado exitosamente';
        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al eliminar el aprendiz'];
        }

        Response::redirect('/aprendices');
    }

    /**
     * Importación masiva de aprendices desde CSV
     * POST /aprendices/importar
     */
    public function import(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendices');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors'] = ['Error al subir el archivo'];
            Response::redirect('/aprendices');
        }

        $fichaId = filter_input(INPUT_POST, 'ficha_id', FILTER_VALIDATE_INT);
        
        if (!$fichaId) {
            $_SESSION['errors'] = ['Debe seleccionar una ficha'];
            Response::redirect('/aprendices');
        }

        try {
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $imported = 0;
            $errors = [];
            
            // Saltar encabezado
            fgetcsv($file);
            
            while (($data = fgetcsv($file)) !== false) {
                if (count($data) < 4) continue;
                
                [$documento, $nombre, $apellido, $codigoCarnet] = $data;
                
                // Verificar si ya existe
                if ($this->aprendizRepository->findByDocumento($documento)) {
                    $errors[] = "Documento {$documento} ya existe";
                    continue;
                }
                
                // Crear aprendiz
                $aprendizId = $this->aprendizRepository->create([
                    'documento' => trim($documento),
                    'nombre' => trim($nombre),
                    'apellido' => trim($apellido),
                    'codigo_carnet' => trim($codigoCarnet),
                    'estado' => 'activo',
                ]);
                
                // Vincular con ficha
                $this->aprendizRepository->attachToFicha($aprendizId, $fichaId);
                $imported++;
            }
            
            fclose($file);
            
            $_SESSION['success'] = "Se importaron {$imported} aprendices exitosamente";
            
            if (!empty($errors)) {
                $_SESSION['warnings'] = $errors;
            }
            
        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al importar: ' . $e->getMessage()];
        }

        Response::redirect('/aprendices');
    }
}

