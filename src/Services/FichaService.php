<?php

namespace App\Services;

use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;

/**
 * Servicio de lógica de negocio para Fichas
 * Maneja validaciones, reglas de negocio y operaciones complejas
 */
class FichaService
{
    private FichaRepository $fichaRepository;
    private AprendizRepository $aprendizRepository;

    public function __construct(
        FichaRepository $fichaRepository,
        AprendizRepository $aprendizRepository
    ) {
        $this->fichaRepository = $fichaRepository;
        $this->aprendizRepository = $aprendizRepository;
    }

    /**
     * Valida los datos de una ficha antes de crear/actualizar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar número de ficha
        if (empty($data['numero_ficha'])) {
            $errors[] = 'El número de ficha es requerido';
        } else {
            // Verificar formato: solo números y letras, sin espacios
            if (!preg_match('/^[A-Z0-9]{4,20}$/i', $data['numero_ficha'])) {
                $errors[] = 'El número de ficha debe contener entre 4 y 20 caracteres alfanuméricos';
            }

            // Verificar unicidad
            $existing = $this->fichaRepository->findByNumero($data['numero_ficha']);
            if ($existing && (!$excludeId || $existing['id'] != $excludeId)) {
                $errors[] = 'El número de ficha ya existe';
            }
        }

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es requerido';
        } elseif (strlen($data['nombre']) < 10) {
            $errors[] = 'El nombre debe tener al menos 10 caracteres';
        } elseif (strlen($data['nombre']) > 200) {
            $errors[] = 'El nombre no puede exceder 200 caracteres';
        }

        // Validar estado
        if (!isset($data['estado']) || !in_array($data['estado'], ['activa', 'finalizada'])) {
            $errors[] = 'El estado debe ser "activa" o "finalizada"';
        }

        return $errors;
    }

    /**
     * Crea una nueva ficha con validaciones
     */
    public function create(array $data): array
    {
        $errors = $this->validate($data);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $fichaId = $this->fichaRepository->create([
                'numero_ficha' => strtoupper(trim($data['numero_ficha'])),
                'nombre' => trim($data['nombre']),
                'estado' => $data['estado'] ?? 'activa',
            ]);

            return [
                'success' => true,
                'message' => 'Ficha creada exitosamente',
                'data' => ['id' => $fichaId]
            ];
        } catch (\Exception $e) {
            error_log("FichaService::create error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al crear la ficha']];
        }
    }

    /**
     * Actualiza una ficha existente con validaciones
     */
    public function update(int $id, array $data): array
    {
        $ficha = $this->fichaRepository->findById($id);
        
        if (!$ficha) {
            return ['success' => false, 'errors' => ['Ficha no encontrada']];
        }

        $errors = $this->validate($data, $id);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->fichaRepository->update($id, [
                'numero_ficha' => strtoupper(trim($data['numero_ficha'])),
                'nombre' => trim($data['nombre']),
                'estado' => $data['estado'],
            ]);

            return [
                'success' => true,
                'message' => 'Ficha actualizada exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("FichaService::update error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al actualizar la ficha']];
        }
    }

    /**
     * Elimina una ficha con validaciones de negocio
     */
    public function delete(int $id): array
    {
        $ficha = $this->fichaRepository->findById($id);
        
        if (!$ficha) {
            return ['success' => false, 'errors' => ['Ficha no encontrada']];
        }

        // Validar que no tenga aprendices asignados
        $totalAprendices = $this->fichaRepository->countAprendices($id);
        
        if ($totalAprendices > 0) {
            return [
                'success' => false,
                'errors' => ["No se puede eliminar una ficha con {$totalAprendices} aprendices asignados"]
            ];
        }

        try {
            $this->fichaRepository->delete($id);
            return [
                'success' => true,
                'message' => 'Ficha eliminada exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("FichaService::delete error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al eliminar la ficha']];
        }
    }

    /**
     * Obtiene fichas con filtros dinámicos y paginación
     */
    public function getFichas(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $search = $filters['search'] ?? '';
        $estado = $filters['estado'] ?? '';

        // Aplicar filtros
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

        // Agregar información adicional a cada ficha
        foreach ($fichas as &$ficha) {
            $ficha['total_aprendices'] = $this->fichaRepository->countAprendices($ficha['id']);
        }

        return [
            'data' => $fichas,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Obtiene una ficha con información completa
     */
    public function getFichaDetalle(int $id): ?array
    {
        $ficha = $this->fichaRepository->findById($id);
        
        if (!$ficha) {
            return null;
        }

        // Agregar información adicional
        $ficha['total_aprendices'] = $this->fichaRepository->countAprendices($id);
        $ficha['aprendices'] = $this->aprendizRepository->findByFicha($id, 100, 0);

        return $ficha;
    }

    /**
     * Cambia el estado de una ficha
     */
    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        if (!in_array($nuevoEstado, ['activa', 'finalizada'])) {
            return ['success' => false, 'errors' => ['Estado inválido']];
        }

        $ficha = $this->fichaRepository->findById($id);
        
        if (!$ficha) {
            return ['success' => false, 'errors' => ['Ficha no encontrada']];
        }

        try {
            $this->fichaRepository->update($id, ['estado' => $nuevoEstado]);
            return [
                'success' => true,
                'message' => "Ficha marcada como {$nuevoEstado}"
            ];
        } catch (\Exception $e) {
            error_log("FichaService::cambiarEstado error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al cambiar el estado']];
        }
    }

    /**
     * Importa fichas desde archivo CSV
     * Formato esperado: numero_ficha,nombre,estado
     */
    public function importarCSV(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'errors' => ['Archivo no encontrado']];
        }

        // Nota: La validación de extensión se hace en el Controller
        // usando el nombre original del archivo, no el path temporal

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

            // Validar encabezado
            if (!$header || count($header) < 2) {
                fclose($file);
                return ['success' => false, 'errors' => ['Formato de CSV inválido. Se esperan las columnas: numero_ficha, nombre, estado']];
            }

            while (($data = fgetcsv($file)) !== false) {
                $lineNumber++;

                // Validar que tenga al menos 2 columnas
                if (count($data) < 2) {
                    $errors[] = "Línea {$lineNumber}: Datos incompletos (se requieren al menos número y nombre)";
                    $skipped++;
                    continue;
                }

                $numeroFicha = trim($data[0] ?? '');
                $nombre = trim($data[1] ?? '');
                $estado = isset($data[2]) && !empty(trim($data[2])) ? trim($data[2]) : 'activa';

                // Validar datos mínimos
                if (empty($numeroFicha) || empty($nombre)) {
                    $errors[] = "Línea {$lineNumber}: Número de ficha o nombre vacío";
                    $skipped++;
                    continue;
                }

                // Validar estado
                if (!in_array($estado, ['activa', 'finalizada'])) {
                    $errors[] = "Línea {$lineNumber}: Estado '{$estado}' inválido (debe ser 'activa' o 'finalizada')";
                    $skipped++;
                    continue;
                }

                // Verificar si ya existe
                if ($this->fichaRepository->findByNumero($numeroFicha)) {
                    $errors[] = "Línea {$lineNumber}: Ficha {$numeroFicha} ya existe";
                    $skipped++;
                    continue;
                }

                // Crear ficha
                try {
                    $result = $this->create([
                        'numero_ficha' => $numeroFicha,
                        'nombre' => $nombre,
                        'estado' => $estado
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

            $message = "Importación completada: {$imported} fichas importadas";
            if ($skipped > 0) {
                $message .= ", {$skipped} omitidas";
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
            error_log("FichaService::importarCSV error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al procesar el archivo: ' . $e->getMessage()]];
        }
    }

    /**
     * Obtiene fichas con filtros dinámicos avanzados (usando búsqueda avanzada del repositorio)
     */
    public function getFichasAdvanced(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        // Usar búsqueda avanzada del repositorio
        $fichas = $this->fichaRepository->advancedSearch($filters, $limit, $offset);
        $total = $this->fichaRepository->countAdvancedSearch($filters);

        // Agregar información adicional a cada ficha
        foreach ($fichas as &$ficha) {
            $ficha['total_aprendices'] = $this->fichaRepository->countAprendices($ficha['id']);
        }

        return [
            'data' => $fichas,
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
     * Obtiene estadísticas completas de fichas
     */
    public function getEstadisticas(): array
    {
        $stats = $this->fichaRepository->getStats();
        $topFichas = $this->fichaRepository->getTopFichasByAprendices(5);

        return [
            'totales' => $stats,
            'top_fichas' => $topFichas
        ];
    }

    /**
     * Asigna un aprendiz a una ficha con validación de cupo
     * Incluye transacción para garantizar consistencia
     */
    public function assignAprendiz(int $fichaId, int $aprendizId, int $cupoMaximo = 30): array
    {
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            return ['success' => false, 'errors' => ['Ficha no encontrada']];
        }

        $aprendiz = $this->aprendizRepository->findById($aprendizId);
        if (!$aprendiz) {
            return ['success' => false, 'errors' => ['Aprendiz no encontrado']];
        }

        // Validar que la ficha esté activa
        if ($ficha['estado'] !== 'activa') {
            return ['success' => false, 'errors' => ['No se pueden asignar aprendices a fichas finalizadas']];
        }

        // Validar que el aprendiz esté activo
        if ($aprendiz['estado'] !== 'activo') {
            return ['success' => false, 'errors' => ['No se pueden asignar aprendices retirados']];
        }

        // Verificar si ya está asignado
        if ($this->aprendizRepository->isAttachedToFicha($aprendizId, $fichaId)) {
            return ['success' => false, 'errors' => ['El aprendiz ya está asignado a esta ficha']];
        }

        // Validar cupo disponible
        $aprendicesActuales = $this->fichaRepository->countAprendices($fichaId);
        if ($aprendicesActuales >= $cupoMaximo) {
            return [
                'success' => false, 
                'errors' => ["La ficha ha alcanzado el cupo máximo de {$cupoMaximo} aprendices"]
            ];
        }

        try {
            // Iniciar transacción
            \App\Database\Connection::beginTransaction();

            // Asignar aprendiz a la ficha
            $result = $this->aprendizRepository->attachToFicha($aprendizId, $fichaId);
            
            if (!$result) {
                throw new \Exception('Error al asignar el aprendiz a la ficha');
            }

            // Confirmar transacción
            \App\Database\Connection::commit();

            return [
                'success' => true,
                'message' => 'Aprendiz asignado exitosamente a la ficha',
                'data' => [
                    'ficha_id' => $fichaId,
                    'aprendiz_id' => $aprendizId,
                    'cupo_usado' => $aprendicesActuales + 1,
                    'cupo_disponible' => $cupoMaximo - ($aprendicesActuales + 1)
                ]
            ];

        } catch (\Exception $e) {
            // Revertir transacción
            \App\Database\Connection::rollBack();
            error_log("FichaService::assignAprendiz error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Error al asignar el aprendiz: ' . $e->getMessage()]];
        }
    }

    /**
     * Valida si una ficha puede recibir más aprendices
     */
    public function validarCupoDisponible(int $fichaId, int $cupoMaximo = 30): array
    {
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            return ['valid' => false, 'message' => 'Ficha no encontrada'];
        }

        if ($ficha['estado'] !== 'activa') {
            return ['valid' => false, 'message' => 'La ficha no está activa'];
        }

        $aprendicesActuales = $this->fichaRepository->countAprendices($fichaId);
        $cupoDisponible = $cupoMaximo - $aprendicesActuales;

        return [
            'valid' => $cupoDisponible > 0,
            'message' => $cupoDisponible > 0 ? 'Cupo disponible' : 'Cupo completo',
            'data' => [
                'cupo_usado' => $aprendicesActuales,
                'cupo_maximo' => $cupoMaximo,
                'cupo_disponible' => $cupoDisponible
            ]
        ];
    }

    /**
     * Busca fichas por número con coincidencia exacta o parcial
     */
    public function searchByNumeroFicha(string $numeroFicha, bool $exactMatch = false): array
    {
        if ($exactMatch) {
            $ficha = $this->fichaRepository->findByNumero($numeroFicha);
            return $ficha ? [$ficha] : [];
        }

        return $this->fichaRepository->search($numeroFicha, 50, 0);
    }

    /**
     * Obtiene fichas activas con información de programas (adaptado sin tabla programas_formacion)
     */
    public function getFichasActivas(int $limit = 50, int $offset = 0): array
    {
        return $this->fichaRepository->findActive($limit, $offset);
    }

    /**
     * Paginación mejorada con metadatos completos
     */
    public function paginate(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $result = $this->getFichasAdvanced($filters, $page, $limit);
        
        // Agregar metadatos adicionales de paginación
        $result['pagination']['has_previous'] = $page > 1;
        $result['pagination']['has_next'] = $page < $result['pagination']['total_pages'];
        $result['pagination']['previous_page'] = $page > 1 ? $page - 1 : null;
        $result['pagination']['next_page'] = $page < $result['pagination']['total_pages'] ? $page + 1 : null;
        
        return $result;
    }

    /**
     * Valida el formato de un archivo CSV antes de importar
     */
    public function validarFormatoCSV(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['valid' => false, 'errors' => ['Archivo no encontrado']];
        }

        try {
            $file = fopen($filePath, 'r');
            if (!$file) {
                return ['valid' => false, 'errors' => ['No se pudo abrir el archivo']];
            }

            $header = fgetcsv($file);
            $lineNumber = 1;
            $errores = [];
            $fichasValidas = 0;
            $duplicados = [];

            if (!$header || count($header) < 2) {
                fclose($file);
                return ['valid' => false, 'errors' => ['El CSV debe tener al menos 2 columnas: numero_ficha, nombre']];
            }

            // Validar contenido
            while (($data = fgetcsv($file)) !== false) {
                $lineNumber++;
                
                if (count($data) < 2) {
                    $errores[] = "Línea {$lineNumber}: Datos incompletos";
                    continue;
                }

                $numeroFicha = trim($data[0] ?? '');
                $nombre = trim($data[1] ?? '');
                $estado = trim($data[2] ?? 'activa');

                if (empty($numeroFicha)) {
                    $errores[] = "Línea {$lineNumber}: Número de ficha vacío";
                    continue;
                }

                if (empty($nombre)) {
                    $errores[] = "Línea {$lineNumber}: Nombre vacío";
                    continue;
                }

                if (!in_array($estado, ['activa', 'finalizada'])) {
                    $errores[] = "Línea {$lineNumber}: Estado '{$estado}' inválido";
                    continue;
                }

                // Verificar duplicados en el archivo
                if (in_array($numeroFicha, $duplicados)) {
                    $errores[] = "Línea {$lineNumber}: Número de ficha '{$numeroFicha}' duplicado en el archivo";
                    continue;
                }

                $duplicados[] = $numeroFicha;

                // Verificar si ya existe en BD
                if ($this->fichaRepository->findByNumero($numeroFicha)) {
                    $errores[] = "Línea {$lineNumber}: Ficha '{$numeroFicha}' ya existe en el sistema";
                    continue;
                }

                $fichasValidas++;
            }

            fclose($file);

            return [
                'valid' => true,
                'message' => 'Validación completada',
                'fichas_validas' => $fichasValidas,
                'total_lineas' => $lineNumber - 1,
                'errores' => $errores,
                'tiene_errores' => !empty($errores)
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'errors' => ['Error al validar: ' . $e->getMessage()]];
        }
    }
}

