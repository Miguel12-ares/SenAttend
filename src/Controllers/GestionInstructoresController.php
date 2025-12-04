<?php

namespace App\Controllers;

use App\Repositories\InstructorRepository;
use App\Services\InstructorService;
use App\Services\AuthService;
use App\Support\Response;

/**
 * Controlador para gestión completa de Instructores
 * Accesible solo para roles: admin y administrativo
 */
class GestionInstructoresController
{
    private InstructorRepository $instructorRepository;
    private InstructorService $instructorService;
    private AuthService $authService;

    public function __construct(
        InstructorService $instructorService,
        InstructorRepository $instructorRepository,
        AuthService $authService
    ) {
        $this->instructorService = $instructorService;
        $this->instructorRepository = $instructorRepository;
        $this->authService = $authService;
    }

    /**
     * Lista todos los instructores con paginación y búsqueda
     * GET /gestion-instructores
     */
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();
        
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = 20;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($nombre)) {
            $filters['nombre'] = $nombre;
        }

        $result = $this->instructorService->getInstructores($filters, $page, $limit);
        
        $instructores = $result['data'] ?? [];
        $pagination = $result['pagination'] ?? [];
        $totalPages = $pagination['total_pages'] ?? 1;
        $total = $pagination['total_records'] ?? 0;

        require __DIR__ . '/../../views/gestion_instructores/index.php';
    }

    /**
     * Muestra formulario para crear instructor
     * GET /gestion-instructores/crear
     */
    public function create(): void
    {
        $user = $this->authService->getCurrentUser();
        require __DIR__ . '/../../views/gestion_instructores/create.php';
    }

    /**
     * Almacena un nuevo instructor
     * POST /gestion-instructores
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-instructores');
        }

        // Sanitizar datos
        $documento = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        $data = [
            'documento' => $documento,
            'nombre' => $nombre,
            'email' => $email
        ];

        $result = $this->instructorService->createInstructor($data);

        if (!$result['success']) {
            $_SESSION['errors'] = $result['errors'];
            $_SESSION['old'] = $_POST;
            Response::redirect('/gestion-instructores/crear');
        }

        // Mostrar mensaje con la contraseña por defecto
        $defaultPassword = $result['default_password'] ?? substr($documento, 0, 6);
        $_SESSION['success'] = "Instructor creado exitosamente. Contraseña temporal: <strong>{$defaultPassword}</strong>";
        Response::redirect('/gestion-instructores');
    }

    /**
     * Muestra formulario para editar instructor
     * GET /gestion-instructores/{id}/editar
     */
    public function edit(int $id): void
    {
        $user = $this->authService->getCurrentUser();
        $instructor = $this->instructorService->getInstructorDetalle($id);

        if (!$instructor) {
            Response::notFound();
        }

        require __DIR__ . '/../../views/gestion_instructores/edit.php';
    }

    /**
     * Actualiza un instructor
     * POST /gestion-instructores/{id}
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-instructores');
        }

        $instructor = $this->instructorRepository->findById($id);
        if (!$instructor) {
            Response::notFound();
        }

        // Sanitizar datos
        $documento = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $passwordConfirm = filter_input(INPUT_POST, 'password_confirm', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $data = [
            'documento' => $documento,
            'nombre' => $nombre,
            'email' => $email
        ];

        // Solo incluir password si se proporcionó
        if (!empty($password)) {
            $data['password'] = $password;
            $data['password_confirm'] = $passwordConfirm;
        }

        $result = $this->instructorService->updateInstructor($id, $data);

        if (!$result['success']) {
            $_SESSION['errors'] = $result['errors'];
            $_SESSION['old'] = $_POST;
            Response::redirect("/gestion-instructores/{$id}/editar");
        }

        $_SESSION['success'] = 'Instructor actualizado exitosamente';
        Response::redirect('/gestion-instructores');
    }

    /**
     * Elimina un instructor
     * POST /gestion-instructores/{id}/eliminar
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-instructores');
        }

        $result = $this->instructorService->deleteInstructor($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['errors'] = [$result['message']];
        }

        Response::redirect('/gestion-instructores');
    }

    /**
     * Muestra vista de importación CSV
     * GET /gestion-instructores/importar
     */
    public function importView(): void
    {
        $user = $this->authService->getCurrentUser();
        require __DIR__ . '/../../views/gestion_instructores/import.php';
    }

    /**
     * Procesa importación de instructores desde CSV
     * POST /gestion-instructores/importar-csv
     */
    public function processImport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-instructores/importar');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors'] = ['Error al subir el archivo'];
            Response::redirect('/gestion-instructores/importar');
        }

        // Validar extensión
        $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $_SESSION['errors'] = ['El archivo debe ser CSV'];
            Response::redirect('/gestion-instructores/importar');
        }

        try {
            $result = $this->instructorService->processCsvBatch($_FILES['csv_file']['tmp_name']);

            if ($result['success']) {
                $_SESSION['success'] = "Se importaron {$result['imported']} instructores exitosamente";
                
                // Guardar detalles en sesión para mostrar contraseñas
                if (!empty($result['details'])) {
                    $_SESSION['import_details'] = $result['details'];
                }
            }

            if (!empty($result['errors'])) {
                $_SESSION['warnings'] = $result['errors'];
            }

        } catch (\Exception $e) {
            $_SESSION['errors'] = ['Error al importar: ' . $e->getMessage()];
        }

        Response::redirect('/gestion-instructores/importar');
    }

    /**
     * Descarga plantilla CSV de ejemplo
     * GET /gestion-instructores/plantilla-csv
     */
    public function downloadTemplate(): void
    {
        $template = $this->instructorService->generateCsvTemplate();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_instructores.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $template;
        exit;
    }
}
