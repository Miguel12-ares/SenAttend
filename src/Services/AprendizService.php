<?php

namespace App\Services;

use App\Repositories\AprendizRepository;
use App\Repositories\FichaRepository;

/**
 * Servicio de lógica de negocio para Aprendices
 * Maneja validaciones, importación CSV y operaciones complejas
 */
class AprendizService
{
    private AprendizRepository $aprendizRepository;
    private FichaRepository $fichaRepository;

    public function __construct(
        AprendizRepository $aprendizRepository,
        FichaRepository $fichaRepository
    ) {
        $this->aprendizRepository = $aprendizRepository;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Valida los datos de un aprendiz antes de crear/actualizar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar documento
        if (empty($data['documento'])) {
            $errors[] = 'El documento es requerido';
        } else {
            // Validar formato: solo números entre 6 y 20 dígitos
            if (!preg_match('/^[0-9]{6,20}$/', $data['documento'])) {
                $errors[] = 'El documento debe contener entre 6 y 20 dígitos';
            }

            // Verificar unicidad
            $existing = $this->aprendizRepository->findByDocumento($data['documento']);
            if ($existing && (!$excludeId || $existing['id'] != $excludeId)) {
                $errors[] = 'El documento ya existe';
            }
        }

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es requerido';
        } elseif (strlen($data['nombre']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no puede exceder 100 caracteres';
        }

        // Validar apellido
        if (empty($data['apellido'])) {
            $errors[] = 'El apellido es requerido';
        } elseif (strlen($data['apellido']) < 2) {
            $errors[] = 'El apellido debe tener al menos 2 caracteres';
        } elseif (strlen($data['apellido']) > 100) {
            $errors[] = 'El apellido no puede exceder 100 caracteres';
        }

        // Validar código de carnet (opcional)
        if (!empty($data['codigo_carnet'])) {
            if (strlen($data['codigo_carnet']) > 50) {
                $errors[] = 'El código de carnet no puede exceder 50 caracteres';
            }
        }

        // Validar estado
        if (!isset($data['estado']) || !in_array($data['estado'], ['activo', 'retirado'])) {
            $errors[] = 'El estado debe ser "activo" o "retirado"';
        }

        return $errors;
    }

    /**
     * Crea un nuevo aprendiz con validaciones
     * Incluye transacción para crear usuario+aprendiz si se requiere
     */
    public function create(array $data): array
    {
        $errors = $this->validate($data);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Iniciar transacción
            \App\Database\Connection::beginTransaction();

            $aprendizId = $this->aprendizRepository->create([
                'documento' => trim($data['documento']),
                'nombre' => ucwords(strtolower(trim($data['nombre']))),
                'apellido' => ucwords(strtolower(trim($data['apellido']))),
                'codigo_carnet' => !empty($data['codigo_carnet']) ? trim($data['codigo_carnet']) : null,
                'estado' => $data['estado'] ?? 'activo',
            ]);

            // Vincular con ficha si se proporcionó
            if (!empty($data['ficha_id'])) {
                $this->aprendizRepository->attachToFicha($aprendizId, (int)$data['ficha_id']);
            }

            // Si se requiere crear usuario (para futuras funcionalidades)
            if (!empty($data['create_user']) && !empty($data['email'])) {
                $this->createUserForAprendiz($aprendizId, $data);
            }

            // Confirmar transacción
            \App\Database\Connection::commit();

            return [
                'success' => true,
                'message' => 'Aprendiz creado exitosamente',
                'data' => ['id' => $aprendizId]
            ];
        } catch (\Exception $e) {
            // Revertir transacción
            \App\Database\Connection::rollBack();
            error_log("AprendizService::create error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al crear el aprendiz']];
        }
    }

    /**
     * Crea un usuario asociado al aprendiz (para futuras funcionalidades)
     */
    private function createUserForAprendiz(int $aprendizId, array $data): void
    {
        // Esta funcionalidad se puede implementar cuando se requiera
        // que los aprendices tengan acceso al sistema
        // Por ahora solo registramos el log para futuras implementaciones
        error_log("Usuario para aprendiz {$aprendizId} pendiente de implementar");
    }

    /**
     * Actualiza un aprendiz existente con validaciones
     */
    public function update(int $id, array $data): array
    {
        $aprendiz = $this->aprendizRepository->findById($id);
        
        if (!$aprendiz) {
            return ['success' => false, 'errors' => ['Aprendiz no encontrado']];
        }

        $errors = $this->validate($data, $id);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->aprendizRepository->update($id, [
                'documento' => trim($data['documento']),
                'nombre' => ucwords(strtolower(trim($data['nombre']))),
                'apellido' => ucwords(strtolower(trim($data['apellido']))),
                'codigo_carnet' => !empty($data['codigo_carnet']) ? trim($data['codigo_carnet']) : null,
                'estado' => $data['estado'],
            ]);

            return [
                'success' => true,
                'message' => 'Aprendiz actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("AprendizService::update error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al actualizar el aprendiz']];
        }
    }

    /**
     * Elimina un aprendiz
     */
    public function delete(int $id): array
    {
        $aprendiz = $this->aprendizRepository->findById($id);
        
        if (!$aprendiz) {
            return ['success' => false, 'errors' => ['Aprendiz no encontrado']];
        }

        try {
            $this->aprendizRepository->delete($id);
            return [
                'success' => true,
                'message' => 'Aprendiz eliminado exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("AprendizService::delete error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al eliminar el aprendiz']];
        }
    }

    /**
     * Obtiene aprendices con filtros dinámicos y paginación
     */
    public function getAprendices(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $search = $filters['search'] ?? '';
        $estado = $filters['estado'] ?? '';
        $fichaId = $filters['ficha_id'] ?? null;

        // Aplicar filtros
        if ($fichaId) {
            $aprendices = $this->aprendizRepository->findByFicha($fichaId, $limit, $offset);
            // Para contar, necesitamos obtener todos y contar (no es óptimo pero funcional)
            $allFromFicha = $this->aprendizRepository->findByFicha($fichaId, 10000, 0);
            $total = count($allFromFicha);
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

        // Agregar información adicional a cada aprendiz
        foreach ($aprendices as &$aprendiz) {
            $aprendiz['fichas'] = $this->aprendizRepository->getFichas($aprendiz['id']);
            $aprendiz['total_fichas'] = count($aprendiz['fichas']);
        }

        return [
            'data' => $aprendices,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Obtiene un aprendiz con información completa
     */
    public function getAprendizDetalle(int $id): ?array
    {
        $aprendiz = $this->aprendizRepository->findById($id);
        
        if (!$aprendiz) {
            return null;
        }

        // Agregar información adicional
        $aprendiz['fichas'] = $this->aprendizRepository->getFichas($id);

        return $aprendiz;
    }

    /**
     * Importa aprendices desde archivo CSV
     * Formato esperado: documento,nombre,apellido,codigo_carnet
     */
    public function importarCSV(string $filePath, int $fichaId): array
    {
        // Validar que la ficha existe
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            return ['success' => false, 'errors' => ['La ficha seleccionada no existe']];
        }

        if (!file_exists($filePath)) {
            return ['success' => false, 'errors' => ['Archivo no encontrado']];
        }

        try {
            $file = fopen($filePath, 'r');
            if (!$file) {
                return ['success' => false, 'errors' => ['No se pudo abrir el archivo']];
            }

            $imported = 0;
            $errors = [];
            $skipped = 0;
            $lineNumber = 0;

            // Leer encabezado
            $header = fgetcsv($file);
            $lineNumber++;

            while (($data = fgetcsv($file)) !== false) {
                $lineNumber++;

                // Validar que tenga al menos 3 columnas
                if (count($data) < 3) {
                    $errors[] = "Línea {$lineNumber}: Datos incompletos";
                    $skipped++;
                    continue;
                }

                $documento = trim($data[0] ?? '');
                $nombre = trim($data[1] ?? '');
                $apellido = trim($data[2] ?? '');
                $codigoCarnet = trim($data[3] ?? '');

                // Validar datos mínimos
                if (empty($documento) || empty($nombre) || empty($apellido)) {
                    $errors[] = "Línea {$lineNumber}: Documento, nombre o apellido vacío";
                    $skipped++;
                    continue;
                }

                // Verificar si ya existe
                if ($this->aprendizRepository->findByDocumento($documento)) {
                    $errors[] = "Línea {$lineNumber}: Documento {$documento} ya existe";
                    $skipped++;
                    continue;
                }

                // Crear aprendiz
                try {
                    $result = $this->create([
                        'documento' => $documento,
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'codigo_carnet' => $codigoCarnet,
                        'estado' => 'activo',
                        'ficha_id' => $fichaId
                    ]);

                    if ($result['success']) {
                        $imported++;
                    } else {
                        $errors[] = "Línea {$lineNumber}: " . implode(', ', $result['errors']);
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Línea {$lineNumber}: Error al crear - " . $e->getMessage();
                    $skipped++;
                }
            }

            fclose($file);

            $message = "Importación completada: {$imported} aprendices importados";
            if ($skipped > 0) {
                $message .= ", {$skipped} omitidos";
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]
            ];

        } catch (\Exception $e) {
            error_log("AprendizService::importarCSV error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al procesar el archivo: ' . $e->getMessage()]];
        }
    }

    /**
     * Vincula un aprendiz a una ficha
     */
    public function vincularFicha(int $aprendizId, int $fichaId): array
    {
        $aprendiz = $this->aprendizRepository->findById($aprendizId);
        if (!$aprendiz) {
            return ['success' => false, 'errors' => ['Aprendiz no encontrado']];
        }

        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            return ['success' => false, 'errors' => ['Ficha no encontrada']];
        }

        try {
            $this->aprendizRepository->attachToFicha($aprendizId, $fichaId);
            return [
                'success' => true,
                'message' => 'Aprendiz vinculado a la ficha exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("AprendizService::vincularFicha error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al vincular el aprendiz']];
        }
    }

    /**
     * Desvincula un aprendiz de una ficha
     */
    public function desvincularFicha(int $aprendizId, int $fichaId): array
    {
        try {
            $this->aprendizRepository->detachFromFicha($aprendizId, $fichaId);
            return [
                'success' => true,
                'message' => 'Aprendiz desvinculado de la ficha exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("AprendizService::desvincularFicha error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al desvincular el aprendiz']];
        }
    }

    /**
     * Cambia el estado de un aprendiz
     */
    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        if (!in_array($nuevoEstado, ['activo', 'retirado'])) {
            return ['success' => false, 'errors' => ['Estado inválido']];
        }

        $aprendiz = $this->aprendizRepository->findById($id);
        
        if (!$aprendiz) {
            return ['success' => false, 'errors' => ['Aprendiz no encontrado']];
        }

        try {
            $this->aprendizRepository->update($id, ['estado' => $nuevoEstado]);
            return [
                'success' => true,
                'message' => "Aprendiz marcado como {$nuevoEstado}"
            ];
        } catch (\Exception $e) {
            error_log("AprendizService::cambiarEstado error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al cambiar el estado']];
        }
    }

    /**
     * Obtiene aprendices con filtros dinámicos avanzados
     */
    public function getAprendicesAdvanced(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        // Usar búsqueda avanzada del repositorio
        $aprendices = $this->aprendizRepository->advancedSearch($filters, $limit, $offset);
        $total = $this->aprendizRepository->countAdvancedSearch($filters);

        // Agregar información adicional a cada aprendiz
        foreach ($aprendices as &$aprendiz) {
            $aprendiz['fichas'] = $this->aprendizRepository->getFichas($aprendiz['id']);
            $aprendiz['total_fichas'] = count($aprendiz['fichas']);
        }

        return [
            'data' => $aprendices,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ],
            'filters_applied' => $filters
        ];
    }

    /**
     * Obtiene estadísticas completas de aprendices
     */
    public function getEstadisticas(): array
    {
        $stats = $this->aprendizRepository->getStats();
        
        return [
            'totales' => $stats
        ];
    }

    /**
     * Valida el formato de un archivo CSV antes de importar
     */
    public function validarFormatoCSV(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['valid' => false, 'errors' => ['Archivo no encontrado']];
        }

        // Nota: La validación de extensión se hace en el Controller
        // usando el nombre original del archivo, no el path temporal

        try {
            $file = fopen($filePath, 'r');
            if (!$file) {
                return ['valid' => false, 'errors' => ['No se pudo abrir el archivo']];
            }

            $header = fgetcsv($file);
            fclose($file);

            if (!$header || count($header) < 3) {
                return ['valid' => false, 'errors' => ['El CSV debe tener al menos 3 columnas: documento, nombre, apellido']];
            }

            return [
                'valid' => true,
                'message' => 'Formato válido',
                'columns' => count($header)
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'errors' => ['Error al validar: ' . $e->getMessage()]];
        }
    }

    /**
     * Valida múltiples aprendices antes de importar (pre-validación)
     */
    public function preValidarImportacion(string $filePath): array
    {
        $validacion = $this->validarFormatoCSV($filePath);
        
        if (!$validacion['valid']) {
            return $validacion;
        }

        try {
            $file = fopen($filePath, 'r');
            $lineNumber = 0;
            $erroresValidacion = [];
            $aprendicesValidos = 0;
            
            // Saltar encabezado
            fgetcsv($file);
            $lineNumber++;

            // Recolectar todos los documentos para verificar duplicados
            $documentosEnArchivo = [];

            while (($data = fgetcsv($file)) !== false) {
                $lineNumber++;
                
                if (count($data) < 3) {
                    $erroresValidacion[] = "Línea {$lineNumber}: Faltan columnas requeridas";
                    continue;
                }

                $documento = trim($data[0] ?? '');
                $nombre = trim($data[1] ?? '');
                $apellido = trim($data[2] ?? '');

                // Validar datos básicos
                if (empty($documento)) {
                    $erroresValidacion[] = "Línea {$lineNumber}: Documento vacío";
                    continue;
                }

                if (empty($nombre)) {
                    $erroresValidacion[] = "Línea {$lineNumber}: Nombre vacío";
                    continue;
                }

                if (empty($apellido)) {
                    $erroresValidacion[] = "Línea {$lineNumber}: Apellido vacío";
                    continue;
                }

                // Validar formato de documento
                if (!preg_match('/^[0-9]{6,20}$/', $documento)) {
                    $erroresValidacion[] = "Línea {$lineNumber}: Formato de documento inválido (6-20 dígitos)";
                    continue;
                }

                // Verificar duplicados dentro del archivo
                if (in_array($documento, $documentosEnArchivo)) {
                    $erroresValidacion[] = "Línea {$lineNumber}: Documento {$documento} duplicado en el archivo";
                    continue;
                }

                $documentosEnArchivo[] = $documento;
                $aprendicesValidos++;
            }

            fclose($file);

            // Verificar documentos existentes en BD
            if (!empty($documentosEnArchivo)) {
                $existentes = $this->aprendizRepository->findByDocumentos($documentosEnArchivo);
                if (!empty($existentes)) {
                    foreach ($existentes as $existente) {
                        $erroresValidacion[] = "Documento {$existente['documento']} ya existe en el sistema";
                    }
                }
            }

            return [
                'valid' => true,
                'aprendices_validos' => $aprendicesValidos,
                'total_lineas' => $lineNumber - 1,
                'errores' => $erroresValidacion,
                'tiene_errores' => !empty($erroresValidacion)
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'errors' => ['Error en validación: ' . $e->getMessage()]];
        }
    }

    /**
     * Importa aprendices con manejo de errores robusto y transaccional
     */
    public function importarCSVRobusto(string $filePath, int $fichaId): array
    {
        // Pre-validar
        $validacion = $this->preValidarImportacion($filePath);
        
        if (!$validacion['valid']) {
            return [
                'success' => false,
                'errors' => $validacion['errors'] ?? ['Validación falló']
            ];
        }

        // Validar que la ficha existe
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            return ['success' => false, 'errors' => ['La ficha seleccionada no existe']];
        }

        // Proceder con la importación usando el método existente
        return $this->importarCSV($filePath, $fichaId);
    }

    /**
     * Importa aprendices desde archivo CSV con formato extendido
     * Formato esperado: documento,nombres,apellidos,email,numero_ficha,codigo_carnet
     */
    public function importFromCSV(string $filePath, int $fichaId): array
    {
        // Validar que la ficha existe
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            return ['success' => false, 'errors' => ['La ficha seleccionada no existe']];
        }

        if (!file_exists($filePath)) {
            return ['success' => false, 'errors' => ['Archivo no encontrado']];
        }

        try {
            $file = fopen($filePath, 'r');
            if (!$file) {
                return ['success' => false, 'errors' => ['No se pudo abrir el archivo']];
            }

            $imported = 0;
            $errors = [];
            $skipped = 0;
            $lineNumber = 0;

            // Leer encabezado
            $header = fgetcsv($file);
            $lineNumber++;

            while (($data = fgetcsv($file)) !== false) {
                $lineNumber++;

                // Validar que tenga al menos 3 columnas (documento, nombres, apellidos)
                if (count($data) < 3) {
                    $errors[] = "Línea {$lineNumber}: Datos incompletos (mínimo: documento, nombres, apellidos)";
                    $skipped++;
                    continue;
                }

                $documento = trim($data[0] ?? '');
                $nombres = trim($data[1] ?? '');
                $apellidos = trim($data[2] ?? '');
                $email = trim($data[3] ?? '');
                $numeroFicha = trim($data[4] ?? '');
                $codigoCarnet = trim($data[5] ?? '');

                // Validar datos mínimos
                if (empty($documento) || empty($nombres) || empty($apellidos)) {
                    $errors[] = "Línea {$lineNumber}: Documento, nombres o apellidos vacío";
                    $skipped++;
                    continue;
                }

                // Validar email si se proporciona
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Línea {$lineNumber}: Email {$email} no válido";
                    $skipped++;
                    continue;
                }

                // Verificar si ya existe
                if ($this->aprendizRepository->findByDocumento($documento)) {
                    $errors[] = "Línea {$lineNumber}: Documento {$documento} ya existe";
                    $skipped++;
                    continue;
                }

                // Crear aprendiz
                try {
                    $result = $this->create([
                        'documento' => $documento,
                        'nombre' => $nombres,
                        'apellido' => $apellidos,
                        'codigo_carnet' => $codigoCarnet,
                        'estado' => 'activo',
                        'ficha_id' => $fichaId,
                        'email' => $email // Para futuras extensiones
                    ]);

                    if ($result['success']) {
                        $imported++;
                    } else {
                        $errors[] = "Línea {$lineNumber}: " . implode(', ', $result['errors']);
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Línea {$lineNumber}: Error al crear - " . $e->getMessage();
                    $skipped++;
                }
            }

            fclose($file);

            $message = "Importación completada: {$imported} aprendices importados";
            if ($skipped > 0) {
                $message .= ", {$skipped} omitidos";
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]
            ];

        } catch (\Exception $e) {
            error_log("AprendizService::importFromCSV error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al procesar el archivo: ' . $e->getMessage()]];
        }
    }

    /**
     * Valida múltiples operaciones de vinculación
     */
    public function vincularMultiples(array $aprendicesIds, int $fichaId): array
    {
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            return ['success' => false, 'errors' => ['Ficha no encontrada']];
        }

        $vinculados = 0;
        $errores = [];

        foreach ($aprendicesIds as $aprendizId) {
            $aprendiz = $this->aprendizRepository->findById($aprendizId);
            
            if (!$aprendiz) {
                $errores[] = "Aprendiz ID {$aprendizId} no encontrado";
                continue;
            }

            // Verificar si ya está vinculado
            if ($this->aprendizRepository->isAttachedToFicha($aprendizId, $fichaId)) {
                $errores[] = "Aprendiz {$aprendiz['nombre']} {$aprendiz['apellido']} ya está vinculado a esta ficha";
                continue;
            }

            try {
                if ($this->aprendizRepository->attachToFicha($aprendizId, $fichaId)) {
                    $vinculados++;
                }
            } catch (\Exception $e) {
                $errores[] = "Error al vincular aprendiz ID {$aprendizId}: " . $e->getMessage();
            }
        }

        return [
            'success' => $vinculados > 0,
            'message' => "{$vinculados} aprendices vinculados exitosamente",
            'data' => [
                'vinculados' => $vinculados,
                'errores' => $errores
            ]
        ];
    }

    /**
     * Valida documento y email únicos antes de crear aprendiz
     */
    public function validateUniqueFields(string $documento, ?string $email = null, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar documento único
        $existingByDoc = $this->aprendizRepository->findByDocumento($documento);
        if ($existingByDoc && (!$excludeId || $existingByDoc['id'] != $excludeId)) {
            $errors[] = "El documento {$documento} ya está registrado";
        }

        // Validar email único si se proporciona
        if (!empty($email)) {
            // Nota: Aquí se podría agregar validación de email único
            // si se extiende el esquema para incluir email en aprendices
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "El email {$email} no tiene un formato válido";
            }
        }

        return $errors;
    }

    /**
     * Busca aprendices por documento, nombre o código con filtros avanzados
     */
    public function findByDocumentoNombreCodigo(string $search, array $filters = []): array
    {
        // Usar el método de búsqueda avanzada existente
        $searchFilters = array_merge($filters, ['search' => $search]);
        $result = $this->getAprendicesAdvanced($searchFilters, 1, 100);
        
        return $result['data'];
    }

    /**
     * Obtiene aprendices con paginación y filtros específicos del requerimiento
     */
    public function getAprendicesWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        // Mapear filtros específicos del requerimiento
        $mappedFilters = [];
        
        if (!empty($filters['documento'])) {
            $mappedFilters['search'] = $filters['documento'];
        }
        
        if (!empty($filters['nombre'])) {
            $mappedFilters['search'] = $filters['nombre'];
        }
        
        if (!empty($filters['codigo'])) {
            $mappedFilters['search'] = $filters['codigo'];
        }
        
        if (!empty($filters['ficha_id'])) {
            $mappedFilters['ficha_id'] = $filters['ficha_id'];
        }
        
        if (!empty($filters['estado'])) {
            $mappedFilters['estado'] = $filters['estado'];
        }

        return $this->getAprendicesAdvanced($mappedFilters, $page, $limit);
    }
}

