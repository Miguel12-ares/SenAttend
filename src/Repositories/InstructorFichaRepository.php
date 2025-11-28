<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;
use Exception;

/**
 * Repository para gestionar la relación muchos a muchos entre instructores y fichas
 * Implementa el patrón Repository siguiendo principios SOLID
 * 
 * @author Sistema SENAttend
 * @version 1.0
 */
class InstructorFichaRepository
{
    /**
     * Obtiene todas las fichas asignadas a un instructor específico
     * 
     * @param int $instructorId ID del instructor
     * @param bool $soloActivas Si true, solo retorna fichas con estado activo
     * @return array Lista de fichas asignadas
     */
    public function findFichasByInstructor(int $instructorId, bool $soloActivas = true): array
    {
        try {
            $sql = 'SELECT f.id, f.numero_ficha, f.nombre, f.estado,
                           inf.fecha_asignacion, inf.activo as asignacion_activa,
                           inf.created_at as fecha_asignacion_creada
                    FROM instructor_fichas inf
                    INNER JOIN fichas f ON inf.ficha_id = f.id
                    WHERE inf.instructor_id = :instructor_id';
            
            $params = ['instructor_id' => $instructorId];
            
            if ($soloActivas) {
                $sql .= ' AND inf.activo = 1 AND f.estado = "activa"';
            }
            
            $sql .= ' ORDER BY f.numero_ficha ASC';
            
            $stmt = Connection::prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en findFichasByInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los instructores asignados a una ficha específica
     * 
     * @param int $fichaId ID de la ficha
     * @param bool $soloActivos Si true, solo retorna asignaciones activas
     * @return array Lista de instructores asignados
     */
    public function findInstructoresByFicha(int $fichaId, bool $soloActivos = true): array
    {
        try {
            $sql = 'SELECT u.id, u.documento, u.nombre, u.email, u.rol,
                           inf.fecha_asignacion, inf.activo as asignacion_activa,
                           inf.created_at as fecha_asignacion_creada
                    FROM instructor_fichas inf
                    INNER JOIN usuarios u ON inf.instructor_id = u.id
                    WHERE inf.ficha_id = :ficha_id
                    AND u.rol = "instructor"';
            
            $params = ['ficha_id' => $fichaId];
            
            if ($soloActivos) {
                $sql .= ' AND inf.activo = 1';
            }
            
            $sql .= ' ORDER BY u.nombre ASC';
            
            $stmt = Connection::prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en findInstructoresByFicha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea una nueva asignación entre instructor y ficha
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return bool True si se creó exitosamente
     */
    public function create(int $instructorId, int $fichaId, ?int $asignadoPor = null): bool
    {
        try {
            $sql = 'INSERT INTO instructor_fichas 
                    (instructor_id, ficha_id, asignado_por, fecha_asignacion, activo) 
                    VALUES (:instructor_id, :ficha_id, :asignado_por, CURDATE(), 1)';
            
            $stmt = Connection::prepare($sql);
            
            return $stmt->execute([
                'instructor_id' => $instructorId,
                'ficha_id' => $fichaId,
                'asignado_por' => $asignadoPor
            ]);
        } catch (PDOException $e) {
            // Si es error de duplicado, lo manejamos silenciosamente
            if ($e->getCode() == 23000) {
                error_log("Asignación duplicada: Instructor {$instructorId} - Ficha {$fichaId}");
                return false;
            }
            error_log("Error en create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea múltiples asignaciones de fichas a un instructor
     * 
     * @param int $instructorId ID del instructor
     * @param array $fichaIds Array de IDs de fichas
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return array Resultado con éxitos y errores
     */
    public function createMultiple(int $instructorId, array $fichaIds, ?int $asignadoPor = null): array
    {
        $resultado = [
            'exitosos' => 0,
            'errores' => 0,
            'duplicados' => 0
        ];

        try {
            Connection::beginTransaction();
            
            foreach ($fichaIds as $fichaId) {
                if ($this->exists($instructorId, $fichaId)) {
                    $resultado['duplicados']++;
                    continue;
                }
                
                if ($this->create($instructorId, $fichaId, $asignadoPor)) {
                    $resultado['exitosos']++;
                } else {
                    $resultado['errores']++;
                }
            }
            
            Connection::commit();
        } catch (Exception $e) {
            Connection::rollBack();
            error_log("Error en createMultiple: " . $e->getMessage());
            $resultado['errores'] = count($fichaIds);
        }
        
        return $resultado;
    }

    /**
     * Elimina una asignación entre instructor y ficha
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si se eliminó exitosamente
     */
    public function delete(int $instructorId, int $fichaId): bool
    {
        try {
            $sql = 'DELETE FROM instructor_fichas 
                    WHERE instructor_id = :instructor_id 
                    AND ficha_id = :ficha_id';
            
            $stmt = Connection::prepare($sql);
            
            return $stmt->execute([
                'instructor_id' => $instructorId,
                'ficha_id' => $fichaId
            ]);
        } catch (PDOException $e) {
            error_log("Error en delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactiva una asignación (soft delete)
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si se desactivó exitosamente
     */
    public function deactivate(int $instructorId, int $fichaId): bool
    {
        try {
            $sql = 'UPDATE instructor_fichas 
                    SET activo = 0, updated_at = CURRENT_TIMESTAMP
                    WHERE instructor_id = :instructor_id 
                    AND ficha_id = :ficha_id';
            
            $stmt = Connection::prepare($sql);
            
            return $stmt->execute([
                'instructor_id' => $instructorId,
                'ficha_id' => $fichaId
            ]);
        } catch (PDOException $e) {
            error_log("Error en deactivate: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reactiva una asignación desactivada
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si se reactivó exitosamente
     */
    public function reactivate(int $instructorId, int $fichaId): bool
    {
        try {
            $sql = 'UPDATE instructor_fichas 
                    SET activo = 1, updated_at = CURRENT_TIMESTAMP
                    WHERE instructor_id = :instructor_id 
                    AND ficha_id = :ficha_id';
            
            $stmt = Connection::prepare($sql);
            
            return $stmt->execute([
                'instructor_id' => $instructorId,
                'ficha_id' => $fichaId
            ]);
        } catch (PDOException $e) {
            error_log("Error en reactivate: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si existe una asignación entre instructor y ficha
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si existe la asignación
     */
    public function exists(int $instructorId, int $fichaId): bool
    {
        try {
            $sql = 'SELECT COUNT(*) as total 
                    FROM instructor_fichas 
                    WHERE instructor_id = :instructor_id 
                    AND ficha_id = :ficha_id';
            
            $stmt = Connection::prepare($sql);
            $stmt->execute([
                'instructor_id' => $instructorId,
                'ficha_id' => $fichaId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error en exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si una asignación está activa
     * 
     * @param int $instructorId ID del instructor
     * @param int $fichaId ID de la ficha
     * @return bool True si la asignación existe y está activa
     */
    public function isActive(int $instructorId, int $fichaId): bool
    {
        try {
            $sql = 'SELECT activo 
                    FROM instructor_fichas 
                    WHERE instructor_id = :instructor_id 
                    AND ficha_id = :ficha_id';
            
            $stmt = Connection::prepare($sql);
            $stmt->execute([
                'instructor_id' => $instructorId,
                'ficha_id' => $fichaId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['activo'] == 1;
        } catch (PDOException $e) {
            error_log("Error en isActive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina todas las asignaciones de un instructor
     * 
     * @param int $instructorId ID del instructor
     * @return bool True si se eliminaron exitosamente
     */
    public function deleteAllByInstructor(int $instructorId): bool
    {
        try {
            $sql = 'DELETE FROM instructor_fichas WHERE instructor_id = :instructor_id';
            
            $stmt = Connection::prepare($sql);
            
            return $stmt->execute(['instructor_id' => $instructorId]);
        } catch (PDOException $e) {
            error_log("Error en deleteAllByInstructor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina todas las asignaciones de una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @return bool True si se eliminaron exitosamente
     */
    public function deleteAllByFicha(int $fichaId): bool
    {
        try {
            $sql = 'DELETE FROM instructor_fichas WHERE ficha_id = :ficha_id';
            
            $stmt = Connection::prepare($sql);
            
            return $stmt->execute(['ficha_id' => $fichaId]);
        } catch (PDOException $e) {
            error_log("Error en deleteAllByFicha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas de asignaciones
     * 
     * @return array Estadísticas generales
     */
    public function getStats(): array
    {
        try {
            $sql = 'SELECT 
                        COUNT(DISTINCT instructor_id) as total_instructores_asignados,
                        COUNT(DISTINCT ficha_id) as total_fichas_asignadas,
                        COUNT(*) as total_asignaciones,
                        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as asignaciones_activas,
                        SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as asignaciones_inactivas
                    FROM instructor_fichas';
            
            $stmt = Connection::query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [
                'total_instructores_asignados' => 0,
                'total_fichas_asignadas' => 0,
                'total_asignaciones' => 0,
                'asignaciones_activas' => 0,
                'asignaciones_inactivas' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error en getStats: " . $e->getMessage());
            return [
                'total_instructores_asignados' => 0,
                'total_fichas_asignadas' => 0,
                'total_asignaciones' => 0,
                'asignaciones_activas' => 0,
                'asignaciones_inactivas' => 0
            ];
        }
    }

    /**
     * Sincroniza las fichas de un instructor (elimina existentes y crea nuevas)
     * 
     * @param int $instructorId ID del instructor
     * @param array $fichaIds Array de IDs de fichas a asignar
     * @param int|null $asignadoPor ID del usuario que realiza la asignación
     * @return bool True si se sincronizó exitosamente
     */
    public function syncFichasForInstructor(int $instructorId, array $fichaIds, ?int $asignadoPor = null): bool
    {
        try {
            Connection::beginTransaction();
            
            // Eliminar todas las asignaciones actuales
            $this->deleteAllByInstructor($instructorId);
            
            // Crear las nuevas asignaciones
            foreach ($fichaIds as $fichaId) {
                $this->create($instructorId, $fichaId, $asignadoPor);
            }
            
            Connection::commit();
            return true;
        } catch (Exception $e) {
            Connection::rollBack();
            error_log("Error en syncFichasForInstructor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el historial de asignaciones de un instructor
     * 
     * @param int $instructorId ID del instructor
     * @param int $limit Límite de registros
     * @param int $offset Offset para paginación
     * @return array Historial de asignaciones
     */
    public function getHistorialByInstructor(int $instructorId, int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = 'SELECT inf.*, f.numero_ficha, f.nombre as ficha_nombre,
                           u.nombre as asignado_por_nombre
                    FROM instructor_fichas inf
                    INNER JOIN fichas f ON inf.ficha_id = f.id
                    LEFT JOIN usuarios u ON inf.asignado_por = u.id
                    WHERE inf.instructor_id = :instructor_id
                    ORDER BY inf.created_at DESC
                    LIMIT :limit OFFSET :offset';
            
            $stmt = Connection::prepare($sql);
            $stmt->bindValue(':instructor_id', $instructorId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getHistorialByInstructor: " . $e->getMessage());
            return [];
        }
    }
}
