<?php

namespace App\Controllers;

use App\Repositories\PorteroRepository;
use App\Services\PorteroService;
use App\Services\AuthService;
use App\Support\Response;

/**
 * Controlador para gestión completa de Porteros
 * Accesible para roles: admin y administrativo
 */
class GestionPorterosController
{
    private PorteroRepository $porteroRepository;
    private PorteroService $porteroService;
    private AuthService $authService;

    public function __construct(
        PorteroService $porteroService,
        PorteroRepository $porteroRepository,
        AuthService $authService
    ) {
        $this->porteroService = $porteroService;
        $this->porteroRepository = $porteroRepository;
        $this->authService = $authService;
    }

    /**
     * Lista todos los porteros con paginación y búsqueda
     * GET /gestion-porteros
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

        $result = $this->porteroService->getPorteros($filters, $page, $limit);
        
        $porteros = $result['data'] ?? [];
        $pagination = $result['pagination'] ?? [];
        $totalPages = $pagination['total_pages'] ?? 1;
        $total = $pagination['total_records'] ?? 0;

        require __DIR__ . '/../../views/gestion_porteros/index.php';
    }

    /**
     * Muestra formulario para crear portero
     * GET /gestion-porteros/crear
     */
    public function create(): void
    {
        $user = $this->authService->getCurrentUser();
        require __DIR__ . '/../../views/gestion_porteros/create.php';
    }

    /**
     * Almacena un nuevo portero
     * POST /gestion-porteros
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-porteros');
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

        $result = $this->porteroService->createPortero($data);

        if (!$result['success']) {
            $_SESSION['errors'] = $result['errors'];
            $_SESSION['old'] = $_POST;
            Response::redirect('/gestion-porteros/crear');
        }

        // Mostrar mensaje con la contraseña por defecto
        $defaultPassword = $result['default_password'] ?? substr($documento, 0, 6);
        $_SESSION['success'] = "Portero creado exitosamente. Contraseña temporal: <strong>{$defaultPassword}</strong>";
        Response::redirect('/gestion-porteros');
    }

    /**
     * Muestra formulario para editar portero
     * GET /gestion-porteros/{id}/editar
     */
    public function edit(int $id): void
    {
        $user = $this->authService->getCurrentUser();
        $portero = $this->porteroService->getPorteroDetalle($id);

        if (!$portero) {
            Response::notFound();
        }

        require __DIR__ . '/../../views/gestion_porteros/edit.php';
    }

    /**
     * Actualiza un portero
     * POST /gestion-porteros/{id}
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-porteros');
        }

        $portero = $this->porteroRepository->findById($id);
        if (!$portero) {
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

        $result = $this->porteroService->updatePortero($id, $data);

        if (!$result['success']) {
            $_SESSION['errors'] = $result['errors'];
            $_SESSION['old'] = $_POST;
            Response::redirect("/gestion-porteros/{$id}/editar");
        }

        $_SESSION['success'] = 'Portero actualizado exitosamente';
        Response::redirect('/gestion-porteros');
    }

    /**
     * Elimina un portero
     * POST /gestion-porteros/{id}/eliminar
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-porteros');
        }

        $result = $this->porteroService->deletePortero($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['errors'] = [$result['message']];
        }

        Response::redirect('/gestion-porteros');
    }

    /**
     * Muestra vista de importación CSV
     * GET /gestion-porteros/importar
     */
    public function importView(): void
    {
        $user = $this->authService->getCurrentUser();
        require __DIR__ . '/../../views/gestion_porteros/import.php';
    }

    /**
     * Procesa importación de porteros desde CSV
     * POST /gestion-porteros/importar-csv
     */
    public function processImport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/gestion-porteros/importar');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors'] = ['Error al subir el archivo'];
            Response::redirect('/gestion-porteros/importar');
        }

        // Validar extensión
        $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $_SESSION['errors'] = ['El archivo debe ser CSV'];
            Response::redirect('/gestion-porteros/importar');
        }

        try {
            $result = $this->porteroService->processCsvBatch($_FILES['csv_file']['tmp_name']);

            if ($result['success']) {
                $_SESSION['success'] = "Se importaron {$result['imported']} porteros exitosamente";
                
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

        Response::redirect('/gestion-porteros/importar');
    }

    /**
     * Descarga plantilla CSV de ejemplo
     * GET /gestion-porteros/plantilla-csv
     */
    public function downloadTemplate(): void
    {
        $template = $this->porteroService->generateCsvTemplate();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_porteros.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $template;
        exit;
    }

    /**
     * Exporta porteros a CSV
     * GET /gestion-porteros/exportar-csv
     */
    public function exportCsv(): void
    {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($nombre)) {
            $filters['nombre'] = $nombre;
        }

        $csv = $this->porteroService->exportToCsv($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="porteros_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }
}
