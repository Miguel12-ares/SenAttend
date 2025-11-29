<?php

namespace App\Services;

use App\Repositories\InstructorRepository;

/**
 * Servicio de lógica de negocio para Instructores
 * Maneja validaciones, creación, actualización y procesamiento CSV
 */
class InstructorService
{
    private InstructorRepository $instructorRepository;

    public function __construct(InstructorRepository $instructorRepository)
    {
        $this->instructorRepository = $instructorRepository;
    }

    /**
     * Valida los datos de un instructor antes de crear/actualizar
     * 
     * @param array $data Datos a validar
     * @param int|null $excludeId ID a excluir en validación de unicidad (para edición)
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validateInstructorData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar documento
        if (empty($data['documento'])) {
            $errors['documento'] = 'El documento es obligatorio';
        } elseif (!preg_match('/^\d{7,15}$/', $data['documento'])) {
            $errors['documento'] = 'El documento debe ser numérico y tener entre 7 y 15 dígitos';
        } elseif ($this->instructorRepository->checkDocumentExists($data['documento'], $excludeId)) {
            $errors['documento'] = 'Este documento ya está registrado en el sistema';
        }

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 2 || strlen($data['nombre']) > 100) {
            $errors['nombre'] = 'El nombre debe tener entre 2 y 100 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El formato del email no es válido';
        } elseif ($this->instructorRepository->checkEmailExists($data['email'], $excludeId)) {
            $errors['email'] = 'Este email ya está registrado en el sistema';
        }

        // Validar password (solo si se está creando o si se proporciona al editar)
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
            } elseif (!preg_match('/[A-Z]/', $data['password'])) {
                $errors['password'] = 'La contraseña debe contener al menos una letra mayúscula';
            } elseif (!preg_match('/[0-9]/', $data['password'])) {
                $errors['password'] = 'La contraseña debe contener al menos un número';
            } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data['password'])) {
                $errors['password'] = 'La contraseña debe contener al menos un carácter especial';
            }

            // Validar confirmación de password
            if (isset($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden';
            }
        }

        return $errors;
    }

    /**
     * Crea un nuevo instructor con validaciones
     * La contraseña por defecto son los primeros 5-6 dígitos del documento
     * 
     * @param array $data Datos del instructor
     * @return array ['success' => bool, 'id' => int|null, 'errors' => array]
     */
    public function createInstructor(array $data): array
    {
        // Validar datos
        $errors = $this->validateInstructorData($data);

        if (!empty($errors)) {
            return [
                'success' => false,
                'id' => null,
                'errors' => $errors
            ];
        }

        try {
            // Generar contraseña por defecto: primeros 5-6 dígitos del documento
            $defaultPassword = substr($data['documento'], 0, 6);
            $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

            $instructorData = [
                'documento' => trim($data['documento']),
                'nombre' => trim($data['nombre']),
                'email' => trim(strtolower($data['email'])),
                'password_hash' => $passwordHash
            ];

            $id = $this->instructorRepository->create($instructorData);

            return [
                'success' => true,
                'id' => $id,
                'errors' => [],
                'default_password' => $defaultPassword // Para mostrar al usuario
            ];

        } catch (\Exception $e) {
            error_log("Error en InstructorService::createInstructor - " . $e->getMessage());
            return [
                'success' => false,
                'id' => null,
                'errors' => ['general' => 'Error al crear el instructor. Por favor, intente nuevamente.']
            ];
        }
    }

    /**
     * Actualiza un instructor existente con validaciones
     * 
     * @param int $id ID del instructor
     * @param array $data Datos a actualizar
     * @return array ['success' => bool, 'errors' => array]
     */
    public function updateInstructor(int $id, array $data): array
    {
        // Verificar que el instructor existe
        $instructor = $this->instructorRepository->findById($id);
        if (!$instructor) {
            return [
                'success' => false,
                'errors' => ['general' => 'Instructor no encontrado']
            ];
        }

        // Validar datos
        $errors = $this->validateInstructorData($data, $id);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        try {
            $updateData = [
                'documento' => trim($data['documento']),
                'nombre' => trim($data['nombre']),
                'email' => trim(strtolower($data['email']))
            ];

            // Si se proporciona una nueva contraseña, hashearla
            if (!empty($data['password'])) {
                $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $success = $this->instructorRepository->update($id, $updateData);

            return [
                'success' => $success,
                'errors' => $success ? [] : ['general' => 'Error al actualizar el instructor']
            ];

        } catch (\Exception $e) {
            error_log("Error en InstructorService::updateInstructor - " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['general' => 'Error al actualizar el instructor. Por favor, intente nuevamente.']
            ];
        }
    }

    /**
     * Obtiene instructores con filtros y paginación
     * 
     * @param array $filters Filtros a aplicar
     * @param int $page Página actual
     * @param int $limit Registros por página
     * @return array
     */
    public function getInstructores(array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $instructores = $this->instructorRepository->findAll($filters, $limit, $offset);
            $total = $this->instructorRepository->count($filters);
            $totalPages = ceil($total / $limit);

            return [
                'success' => true,
                'data' => $instructores,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ];

        } catch (\Exception $e) {
            error_log("Error en InstructorService::getInstructores - " . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'pagination' => [],
                'error' => 'Error al obtener instructores'
            ];
        }
    }

    /**
     * Obtiene un instructor por ID con información completa
     * 
     * @param int $id ID del instructor
     * @return array|null
     */
    public function getInstructorDetalle(int $id): ?array
    {
        try {
            return $this->instructorRepository->findById($id);
        } catch (\Exception $e) {
            error_log("Error en InstructorService::getInstructorDetalle - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Elimina un instructor
     * 
     * @param int $id ID del instructor
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteInstructor(int $id): array
    {
        try {
            // Verificar que el instructor existe
            $instructor = $this->instructorRepository->findById($id);
            if (!$instructor) {
                return [
                    'success' => false,
                    'message' => 'Instructor no encontrado'
                ];
            }

            $success = $this->instructorRepository->delete($id);

            return [
                'success' => $success,
                'message' => $success ? 'Instructor eliminado correctamente' : 'Error al eliminar el instructor'
            ];

        } catch (\Exception $e) {
            error_log("Error en InstructorService::deleteInstructor - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar el instructor. Puede que tenga registros asociados.'
            ];
        }
    }

    /**
     * Valida la estructura de un archivo CSV
     * 
     * @param string $filePath Ruta del archivo CSV
     * @return array ['valid' => bool, 'errors' => array, 'headers' => array]
     */
    public function validateCsvStructure(string $filePath): array
    {
        $errors = [];

        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'errors' => ['El archivo no existe'],
                'headers' => []
            ];
        }

        $file = fopen($filePath, 'r');
        if (!$file) {
            return [
                'valid' => false,
                'errors' => ['No se pudo abrir el archivo'],
                'headers' => []
            ];
        }

        // Leer headers
        $headers = fgetcsv($file);
        fclose($file);

        if (!$headers) {
            return [
                'valid' => false,
                'errors' => ['El archivo está vacío o no tiene el formato correcto'],
                'headers' => []
            ];
        }

        // Headers esperados (sin password)
        $expectedHeaders = ['documento', 'nombre', 'email'];
        $missingHeaders = array_diff($expectedHeaders, $headers);

        if (!empty($missingHeaders)) {
            $errors[] = 'Faltan las siguientes columnas: ' . implode(', ', $missingHeaders);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'headers' => $headers
        ];
    }

    /**
     * Pre-valida los datos del CSV antes de importar
     * 
     * @param string $filePath Ruta del archivo CSV
     * @return array ['valid' => bool, 'errors' => array, 'preview' => array, 'total_rows' => int]
     */
    public function preValidateImport(string $filePath): array
    {
        // Primero validar estructura
        $structureValidation = $this->validateCsvStructure($filePath);
        if (!$structureValidation['valid']) {
            return [
                'valid' => false,
                'errors' => $structureValidation['errors'],
                'preview' => [],
                'total_rows' => 0
            ];
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file); // Saltar headers
        
        $errors = [];
        $preview = [];
        $rowNumber = 1;
        $documentos = [];

        while (($row = fgetcsv($file)) !== false && $rowNumber <= 100) { // Validar máximo 100 filas
            $rowNumber++;
            
            $data = array_combine($headers, $row);
            
            // Validar documento
            if (empty($data['documento']) || !preg_match('/^\d{7,15}$/', $data['documento'])) {
                $errors[] = "Fila $rowNumber: Documento inválido";
            } elseif (in_array($data['documento'], $documentos)) {
                $errors[] = "Fila $rowNumber: Documento duplicado en el archivo";
            } else {
                $documentos[] = $data['documento'];
            }

            // Validar nombre
            if (empty($data['nombre']) || strlen($data['nombre']) < 2) {
                $errors[] = "Fila $rowNumber: Nombre inválido";
            }

            // Validar email
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Fila $rowNumber: Email inválido";
            }

            // Agregar a preview (primeras 5 filas)
            if (count($preview) < 5) {
                $preview[] = $data;
            }
        }

        $totalRows = $rowNumber - 1;
        fclose($file);

        // Verificar duplicados en base de datos
        if (!empty($documentos)) {
            $existingDocs = $this->instructorRepository->findByDocumentos($documentos);
            if (!empty($existingDocs)) {
                foreach ($existingDocs as $doc) {
                    $errors[] = "Documento {$doc['documento']} ya existe en el sistema";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'preview' => $preview,
            'total_rows' => $totalRows
        ];
    }

    /**
     * Procesa la importación de instructores desde CSV
     * Contraseña por defecto: primeros 5-6 dígitos del documento
     * 
     * @param string $filePath Ruta del archivo CSV
     * @return array ['success' => bool, 'imported' => int, 'errors' => array, 'details' => array]
     */
    public function processCsvBatch(string $filePath): array
    {
        // Pre-validar
        $validation = $this->preValidateImport($filePath);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => $validation['errors'],
                'details' => []
            ];
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file); // Saltar headers
        
        $imported = 0;
        $errors = [];
        $details = [];

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);
            
            try {
                // Generar contraseña por defecto
                $defaultPassword = substr($data['documento'], 0, 6);
                
                $instructorData = [
                    'documento' => trim($data['documento']),
                    'nombre' => trim($data['nombre']),
                    'email' => trim(strtolower($data['email'])),
                    'password_hash' => password_hash($defaultPassword, PASSWORD_DEFAULT)
                ];

                $id = $this->instructorRepository->create($instructorData);
                $imported++;
                
                $details[] = [
                    'documento' => $data['documento'],
                    'nombre' => $data['nombre'],
                    'status' => 'success',
                    'default_password' => $defaultPassword
                ];

            } catch (\Exception $e) {
                $errors[] = "Error al importar documento {$data['documento']}: " . $e->getMessage();
                $details[] = [
                    'documento' => $data['documento'],
                    'nombre' => $data['nombre'],
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        fclose($file);

        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'errors' => $errors,
            'details' => $details
        ];
    }

    /**
     * Genera una plantilla CSV de ejemplo
     * 
     * @return string Contenido del CSV
     */
    public function generateCsvTemplate(): string
    {
        $template = "documento,nombre,email\n";
        $template .= "12345678,Juan Pérez,juan.perez@sena.edu.co\n";
        $template .= "87654321,María González,maria.gonzalez@sena.edu.co\n";
        
        return $template;
    }

    /**
     * Obtiene estadísticas de instructores
     * 
     * @return array
     */
    public function getEstadisticas(): array
    {
        try {
            return $this->instructorRepository->getStats();
        } catch (\Exception $e) {
            error_log("Error en InstructorService::getEstadisticas - " . $e->getMessage());
            return [
                'total_instructores' => 0,
                'nuevos_hoy' => 0,
                'nuevos_este_ano' => 0
            ];
        }
    }
}
