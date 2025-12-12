<?php

namespace App\Services;

use App\Repositories\InstructorFichaRepository;
use App\Repositories\UserRepository;
use App\Repositories\FichaRepository;
use App\Database\Connection;
use PDO;
use PDOException;
use Exception;

/**
 * Service para gestionar la lógica de negocio de asignaciones instructor-ficha
 * Implementa el patrón Service siguiendo principios SOLID
 * 
 * @author Sistema SENAttend
 * @version 1.0
 */
class InstructorFichaService
{
    private InstructorFichaRepository $instructorFichaRepository;
    private UserRepository $userRepository;
    private FichaRepository $fichaRepository;

    public function __construct(
        InstructorFichaRepository $instructorFichaRepository,
        UserRepository $userRepository,
        FichaRepository $fichaRepository
    ) {
        $this->instructorFichaRepository = $instructorFichaRepository;
        $this->userRepository = $userRepository;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Obtiene todos los instructores con sus fichas asignadas
     * 
     * @return array Lista de instructores con fichas
     */
    public function getInstructoresConFichas(): array
    {
        try {
            // Obtener todos los instructores
            $instructores = $this->userRepository->findByRole('instructor');
            
            // Para cada instructor, obtener sus fichas asignadas
            foreach ($instructores as &$instructor) {
                $instructor['fichas'] = $this->instructorFichaRepository
                    ->findFichasByInstructor($instructor['id'], true);
                $instructor['total_fichas'] = count($instructor['fichas']);
            }
            
            return $instructores;
        } catch (Exception $e) {
            error_log("Error en getInstructoresConFichas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todas las fichas con sus instructores asignados
     * 
     * @param bool $soloActivas Si true, solo retorna fichas activas
     * @return array Lista de fichas con instructores
     */
    public function getFichasConInstructores(bool $soloActivas = true): array
    {
        try {
            // Obtener fichas según el filtro
            $fichas = $soloActivas 
                ? $this->fichaRepository->findActive(100, 0)
                : $this->fichaRepository->findAll(100, 0);
            
            // Para cada ficha, obtener sus instructores asignados
            foreach ($fichas as &$ficha) {
                $ficha['instructores'] = $this->instructorFichaRepository
                    ->findInstructoresByFicha($ficha['id'], true);
                $ficha['total_instructores'] = count($ficha['instructores']);
            }
            
            return $fichas;
        } catch (Exception $e) {
            error_log("Error en getFichasConInstructores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Asigna múltiples fichas a un instructor
     * 
     * @param int $instructorId ID del instructor
     * @param array $fichaIds Array de IDs de fichas
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return array Resultado de la operación
     */
    public function asignarFichasAInstructor(int $instructorId, array $fichaIds, ?int $asignadoPor = null): array
    {
        try {
            // Validar que el instructor existe y es válido
            $instructor = $this->userRepository->findById($instructorId);
            if (!$instructor || $instructor['rol'] !== 'instructor') {
                throw new Exception('Instructor no válido');
            }
            
            // Validar que todas las fichas existen
            $fichasValidas = [];
            foreach ($fichaIds as $fichaId) {
                $ficha = $this->fichaRepository->findById($fichaId);
                if ($ficha) {
                    $fichasValidas[] = $fichaId;
                }
            }
            
            if (empty($fichasValidas)) {
                throw new Exception('No se proporcionaron fichas válidas');
            }
            
            // Realizar las asignaciones
            $resultado = $this->instructorFichaRepository
                ->createMultiple($instructorId, $fichasValidas, $asignadoPor);
            
            $resultado['instructor'] = $instructor['nombre'];
            $resultado['fichas_solicitadas'] = count($fichaIds);
            $resultado['fichas_validas'] = count($fichasValidas);
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error en asignarFichasAInstructor: " . $e->getMessage());
            return [
                'error' => true,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Asigna múltiples instructores a una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @param array $instructorIds Array de IDs de instructores
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return array Resultado de la operación
     */
    public function asignarInstructoresAFicha(int $fichaId, array $instructorIds, ?int $asignadoPor = null, ?int $liderInstructorId = null): array
    {
        try {
            // Validar que la ficha existe
            $ficha = $this->fichaRepository->findById($fichaId);
            if (!$ficha) {
                throw new Exception('Ficha no válida');
            }
            
            $resultado = [
                'exitosos' => 0,
                'errores' => 0,
                'duplicados' => 0,
                'eliminados' => 0,
            ];

            // Normalizar lista de instructores nuevos (evitar duplicados en el array)
            $instructorIds = array_values(array_unique(array_map('intval', $instructorIds)));

            // Obtener instructores actualmente asignados a la ficha (activos)
            $asignadosActuales = $this->instructorFichaRepository->findInstructoresByFicha($fichaId, false);
            $idsActuales = array_column($asignadosActuales, 'id');

            // Primero: eliminar asignaciones que ya no estén en la nueva lista
            $idsAEliminar = array_diff($idsActuales, $instructorIds);
            foreach ($idsAEliminar as $instructorIdEliminar) {
                if ($this->instructorFichaRepository->delete((int)$instructorIdEliminar, $fichaId)) {
                    $resultado['eliminados']++;
                }
            }

            // Segundo: asegurar asignación de los instructores enviados
            foreach ($instructorIds as $instructorId) {
                $instructor = $this->userRepository->findById($instructorId);
                if (!$instructor || $instructor['rol'] !== 'instructor') {
                    $resultado['errores']++;
                    continue;
                }
                
                if ($this->instructorFichaRepository->exists($instructorId, $fichaId)) {
                    $resultado['duplicados']++;
                    continue;
                }
                
                if ($this->instructorFichaRepository->create($instructorId, $fichaId, $asignadoPor)) {
                    $resultado['exitosos']++;
                } else {
                    $resultado['errores']++;
                }
            }
            
            $resultado['ficha'] = $ficha['numero_ficha'] . ' - ' . $ficha['nombre'];
            $resultado['instructores_solicitados'] = count($instructorIds);
            
            // Actualizar instructor líder de la ficha (si corresponde)
            $this->actualizarInstructorLiderDeFicha($fichaId, $liderInstructorId, $instructorIds);

            return $resultado;
        } catch (Exception $e) {
            error_log("Error en asignarInstructoresAFicha: " . $e->getMessage());
            return [
                'error' => true,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene el instructor líder (si existe) de una ficha
     *
     * @param int $fichaId
     * @return int|null ID del instructor líder o null si no hay
     */
    public function getInstructorLiderDeFicha(int $fichaId): ?int
    {
        try {
            $sql = 'SELECT id_instructor 
                    FROM instructor_lider_ficha 
                    WHERE id_ficha = :ficha_id 
                    LIMIT 1';

            $stmt = Connection::prepare($sql);
            $stmt->execute(['ficha_id' => $fichaId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? (int) $row['id_instructor'] : null;
        } catch (PDOException $e) {
            error_log("Error en getInstructorLiderDeFicha: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene todos los instructores que actualmente son líderes de al menos una ficha
     *
     * @return array Lista de instructores líderes con cantidad de fichas lideradas
     */
    public function getInstructoresLideres(): array
    {
        try {
            $sql = 'SELECT 
                        u.id,
                        u.documento,
                        u.nombre,
                        u.email,
                        COUNT(ilf.id_ficha) AS total_fichas_lider
                    FROM instructor_lider_ficha ilf
                    INNER JOIN usuarios u ON ilf.id_instructor = u.id
                    GROUP BY u.id, u.documento, u.nombre, u.email
                    ORDER BY u.nombre ASC';

            $stmt = Connection::query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getInstructoresLideres: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las fichas donde un instructor es líder
     *
     * @param int $instructorId
     * @return array
     */
    public function getFichasLideradasPorInstructor(int $instructorId): array
    {
        try {
            $sql = 'SELECT f.id, f.numero_ficha, f.nombre, f.jornada, f.estado
                    FROM instructor_lider_ficha ilf
                    INNER JOIN fichas f ON ilf.id_ficha = f.id
                    WHERE ilf.id_instructor = :id_instructor
                    ORDER BY f.numero_ficha ASC';

            $stmt = Connection::prepare($sql);
            $stmt->execute(['id_instructor' => $instructorId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getFichasLideradasPorInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Elimina la relación de líder para una ficha específica
     *
     * @param int $instructorId
     * @param int $fichaId
     * @return bool
     */
    public function eliminarLiderDeFicha(int $instructorId, int $fichaId): bool
    {
        try {
            $sql = 'DELETE FROM instructor_lider_ficha 
                    WHERE id_instructor = :id_instructor 
                    AND id_ficha = :id_ficha';

            $stmt = Connection::prepare($sql);
            return $stmt->execute([
                'id_instructor' => $instructorId,
                'id_ficha' => $fichaId,
            ]);
        } catch (PDOException $e) {
            error_log("Error en eliminarLiderDeFicha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa importación masiva de líderes desde CSV
     * Formato esperado: documento_instructor, numero_ficha
     *
     * @param string $csvPath
     * @return array
     */
    public function importarLideresDesdeCsv(string $csvPath): array
    {
        $resultado = [
            'success' => false,
            'imported' => 0,
            'errors' => [],
        ];

        if (!is_readable($csvPath)) {
            $resultado['errors'][] = 'No se pudo leer el archivo CSV';
            return $resultado;
        }

        if (($handle = fopen($csvPath, 'r')) === false) {
            $resultado['errors'][] = 'No se pudo abrir el archivo CSV';
            return $resultado;
        }

        $header = fgetcsv($handle, 0, ';');
        if ($header === false) {
            fclose($handle);
            $resultado['errors'][] = 'El archivo CSV está vacío o es inválido';
            return $resultado;
        }

        // Intentar detectar separador ; o ,
        if (count($header) === 1) {
            // Reintentar con coma
            rewind($handle);
            $header = fgetcsv($handle, 0, ',');
        }

        $header = array_map('trim', $header);
        $map = array_flip($header);

        if (!isset($map['documento_instructor']) || !isset($map['numero_ficha'])) {
            fclose($handle);
            $resultado['errors'][] = 'El CSV debe contener las columnas: documento_instructor, numero_ficha';
            return $resultado;
        }

        $linea = 1;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $linea++;

            if (count($row) === 1) {
                $row = str_getcsv($row[0], ',');
            }

            $row = array_map('trim', $row);

            $documento = $row[$map['documento_instructor']] ?? '';
            $numeroFicha = $row[$map['numero_ficha']] ?? '';

            if ($documento === '' || $numeroFicha === '') {
                $resultado['errors'][] = "Línea {$linea}: columnas vacías";
                continue;
            }

            try {
                $instructor = $this->userRepository->findByDocumento($documento);
                if (!$instructor || $instructor['rol'] !== 'instructor') {
                    $resultado['errors'][] = "Línea {$linea}: instructor con documento {$documento} no encontrado o no es rol instructor";
                    continue;
                }

                $ficha = $this->fichaRepository->findByNumero($numeroFicha);
                if (!$ficha) {
                    $resultado['errors'][] = "Línea {$linea}: ficha con número {$numeroFicha} no encontrada";
                    continue;
                }

                // Validar que el instructor esté asignado a esa ficha
                $instructorId = (int) $instructor['id'];
                $fichaId = (int) $ficha['id'];
                if (!$this->instructorFichaRepository->exists($instructorId, $fichaId)) {
                    $resultado['errors'][] = "Línea {$linea}: el instructor {$documento} no está asignado a la ficha {$numeroFicha}; debe asignarse primero para poder ser líder";
                    continue;
                }

                // Establecer/actualizar líder de esta ficha
                $ok = $this->actualizarInstructorLiderDeFicha(
                    $fichaId,
                    $instructorId,
                    [$instructorId]
                );

                if ($ok) {
                    $resultado['imported']++;
                } else {
                    $resultado['errors'][] = "Línea {$linea}: no se pudo actualizar líder para la ficha {$numeroFicha}";
                }
            } catch (Exception $e) {
                $resultado['errors'][] = "Línea {$linea}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $resultado['success'] = $resultado['imported'] > 0;
        return $resultado;
    }

    /**
     * Actualiza el instructor líder de una ficha
     *
     * - Garantiza máximo un líder por ficha en la tabla instructor_lider_ficha
     * - Solo permite marcar como líder a instructores efectivamente asignados a la ficha
     *
     * @param int $fichaId
     * @param int|null $liderInstructorId
     * @param array $instructorIdsAsociados IDs de instructores asociados en esta operación
     * @return bool
     */
    public function actualizarInstructorLiderDeFicha(int $fichaId, ?int $liderInstructorId, array $instructorIdsAsociados = []): bool
    {
        try {
            // Si se pasa un líder pero no está entre los instructores de la ficha, lo ignoramos
            if ($liderInstructorId !== null && !in_array($liderInstructorId, $instructorIdsAsociados, true)) {
                $liderInstructorId = null;
            }

            Connection::beginTransaction();

            // Consultar si ya existe un registro de líder para esta ficha
            $selectSql = 'SELECT id FROM instructor_lider_ficha WHERE id_ficha = :ficha_id LIMIT 1';
            $selectStmt = Connection::prepare($selectSql);
            $selectStmt->execute(['ficha_id' => $fichaId]);
            $existing = $selectStmt->fetch(PDO::FETCH_ASSOC);

            // Si no se envía nuevo líder
            if ($liderInstructorId === null) {
                // Si había un registro previo, lo eliminamos; el ID desaparece pero no se crea uno nuevo
                if ($existing) {
                    $deleteSql = 'DELETE FROM instructor_lider_ficha WHERE id = :id';
                    $deleteStmt = Connection::prepare($deleteSql);
                    $deleteStmt->execute(['id' => $existing['id']]);
                }

                Connection::commit();
                return true;
            }

            // Validar que el instructor existe y está asignado a la ficha
            if (!$this->instructorFichaRepository->exists($liderInstructorId, $fichaId)) {
                Connection::commit();
                return true;
            }

            if ($existing) {
                // Actualizar el registro existente (se mantiene el mismo ID)
                $updateSql = 'UPDATE instructor_lider_ficha 
                              SET id_instructor = :id_instructor 
                              WHERE id = :id';
                $updateStmt = Connection::prepare($updateSql);
                $updateStmt->execute([
                    'id_instructor' => $liderInstructorId,
                    'id' => $existing['id'],
                ]);
            } else {
                // No existe registro aún: insertar uno nuevo
                $insertSql = 'INSERT INTO instructor_lider_ficha (id_instructor, id_ficha) 
                              VALUES (:id_instructor, :ficha_id)';
                $insertStmt = Connection::prepare($insertSql);
                $insertStmt->execute([
                    'id_instructor' => $liderInstructorId,
                    'ficha_id' => $fichaId,
                ]);
            }

            Connection::commit();
            return true;
        } catch (PDOException $e) {
            Connection::rollBack();
            error_log("Error en actualizarInstructorLiderDeFicha: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Connection::rollBack();
            error_log("Error en actualizarInstructorLiderDeFicha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una asignación entre instructor y ficha
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si se eliminó exitosamente
     */
    public function eliminarAsignacion(int $instructorId, int $fichaId): bool
    {
        try {
            return $this->instructorFichaRepository->delete($instructorId, $fichaId);
        } catch (Exception $e) {
            error_log("Error en eliminarAsignacion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sincroniza las fichas de un instructor (reemplaza todas las asignaciones)
     * 
     * @param int $instructorId ID del instructor
     * @param array $fichaIds Array de IDs de fichas
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return bool True si se sincronizó exitosamente
     */
    public function sincronizarFichasInstructor(int $instructorId, array $fichaIds, ?int $asignadoPor = null): bool
    {
        try {
            // Validar instructor
            $instructor = $this->userRepository->findById($instructorId);
            if (!$instructor || $instructor['rol'] !== 'instructor') {
                throw new Exception('Instructor no válido');
            }
            
            // Validar fichas
            $fichasValidas = [];
            foreach ($fichaIds as $fichaId) {
                if ($this->fichaRepository->findById($fichaId)) {
                    $fichasValidas[] = $fichaId;
                }
            }
            
            return $this->instructorFichaRepository
                ->syncFichasForInstructor($instructorId, $fichasValidas, $asignadoPor);
        } catch (Exception $e) {
            error_log("Error en sincronizarFichasInstructor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las fichas disponibles para asignar a un instructor
     * 
     * @param int $instructorId ID del instructor
     * @return array Lista de fichas no asignadas
     */
    public function getFichasDisponiblesParaInstructor(int $instructorId): array
    {
        try {
            // Obtener todas las fichas activas
            $todasLasFichas = $this->fichaRepository->findActive(200, 0);
            
            // Obtener las fichas ya asignadas al instructor
            $fichasAsignadas = $this->instructorFichaRepository
                ->findFichasByInstructor($instructorId, false);
            
            $idsAsignados = array_column($fichasAsignadas, 'id');
            
            // Filtrar las fichas no asignadas
            $fichasDisponibles = array_filter($todasLasFichas, function($ficha) use ($idsAsignados) {
                return !in_array($ficha['id'], $idsAsignados);
            });
            
            return array_values($fichasDisponibles);
        } catch (Exception $e) {
            error_log("Error en getFichasDisponiblesParaInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los instructores disponibles para asignar a una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @return array Lista de instructores no asignados
     */
    public function getInstructoresDisponiblesParaFicha(int $fichaId): array
    {
        try {
            // Obtener todos los instructores
            $todosLosInstructores = $this->userRepository->findByRole('instructor');
            
            // Obtener los instructores ya asignados a la ficha
            $instructoresAsignados = $this->instructorFichaRepository
                ->findInstructoresByFicha($fichaId, false);
            
            $idsAsignados = array_column($instructoresAsignados, 'id');
            
            // Filtrar los instructores no asignados
            $instructoresDisponibles = array_filter($todosLosInstructores, function($instructor) use ($idsAsignados) {
                return !in_array($instructor['id'], $idsAsignados);
            });
            
            return array_values($instructoresDisponibles);
        } catch (Exception $e) {
            error_log("Error en getInstructoresDisponiblesParaFicha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene instructores asignados a una ficha
     *
     * @param int $fichaId
     * @param bool $soloActivos
     * @return array
     */
    public function getInstructoresDeFicha(int $fichaId, bool $soloActivos = true): array
    {
        try {
            return $this->instructorFichaRepository->findInstructoresByFicha($fichaId, $soloActivos);
        } catch (Exception $e) {
            error_log("Error en getInstructoresDeFicha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida si un instructor tiene acceso a una ficha específica
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si tiene acceso
     */
    public function instructorTieneAccesoAFicha(int $instructorId, int $fichaId): bool
    {
        try {
            // Verificar si existe la asignación y está activa
            return $this->instructorFichaRepository->isActive($instructorId, $fichaId);
        } catch (Exception $e) {
            error_log("Error en instructorTieneAccesoAFicha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas generales de asignaciones
     * 
     * @return array Estadísticas
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $stats = $this->instructorFichaRepository->getStats();
            
            // Agregar estadísticas adicionales
            $totalInstructores = $this->userRepository->countByRole('instructor');
            $totalFichas = $this->fichaRepository->count();
            
            $stats['total_instructores'] = $totalInstructores;
            $stats['total_fichas'] = $totalFichas;
            $stats['instructores_sin_asignar'] = $totalInstructores - $stats['total_instructores_asignados'];
            $stats['fichas_sin_asignar'] = $totalFichas - $stats['total_fichas_asignadas'];
            
            // Calcular promedios
            $stats['promedio_fichas_por_instructor'] = $stats['total_instructores_asignados'] > 0
                ? round($stats['total_asignaciones'] / $stats['total_instructores_asignados'], 2)
                : 0;
            
            $stats['promedio_instructores_por_ficha'] = $stats['total_fichas_asignadas'] > 0
                ? round($stats['total_asignaciones'] / $stats['total_fichas_asignadas'], 2)
                : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return [
                'total_instructores' => 0,
                'total_fichas' => 0,
                'total_asignaciones' => 0,
                'asignaciones_activas' => 0,
                'asignaciones_inactivas' => 0,
                'instructores_sin_asignar' => 0,
                'fichas_sin_asignar' => 0,
                'promedio_fichas_por_instructor' => 0,
                'promedio_instructores_por_ficha' => 0
            ];
        }
    }

    /**
     * Obtiene el detalle de asignaciones de un instructor específico
     * 
     * @param int $instructorId ID del instructor
     * @return array Detalle del instructor con sus fichas
     */
    public function getDetalleInstructor(int $instructorId): array
    {
        try {
            $instructor = $this->userRepository->findById($instructorId);
            if (!$instructor || $instructor['rol'] !== 'instructor') {
                throw new Exception('Instructor no válido');
            }
            
            $instructor['fichas_asignadas'] = $this->instructorFichaRepository
                ->findFichasByInstructor($instructorId, false);
            
            $instructor['fichas_activas'] = array_filter($instructor['fichas_asignadas'], function($ficha) {
                return $ficha['asignacion_activa'] == 1 && $ficha['estado'] == 'activa';
            });
            
            $instructor['total_fichas_asignadas'] = count($instructor['fichas_asignadas']);
            $instructor['total_fichas_activas'] = count($instructor['fichas_activas']);
            
            return $instructor;
        } catch (Exception $e) {
            error_log("Error en getDetalleInstructor: " . $e->getMessage());
            return [];
        }
    }
}
