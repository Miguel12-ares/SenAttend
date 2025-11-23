<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repositorio para la entidad Aprendiz
 */
class AprendizRepository
{
    /**
     * Busca aprendices por ficha con paginación
     */
    public function findByFicha(int $fichaId, int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT a.id, a.documento, a.nombre, a.apellido, a.email, a.estado 
                 FROM aprendices a
                 INNER JOIN ficha_aprendiz fa ON a.id = fa.id_aprendiz
                 WHERE fa.id_ficha = :id_ficha
                 ORDER BY a.apellido ASC, a.nombre ASC
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':id_ficha', $fichaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding aprendices by ficha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca un aprendiz por documento
     */
    public function findByDocumento(string $documento): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, apellido, email, estado 
                 FROM aprendices 
                 WHERE documento = :documento 
                 LIMIT 1'
            );

            $stmt->execute(['documento' => $documento]);
            $aprendiz = $stmt->fetch();

            return $aprendiz ?: null;
        } catch (PDOException $e) {
            error_log("Error finding aprendiz by documento: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca un aprendiz por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, apellido, email, estado 
                 FROM aprendices 
                 WHERE id = :id 
                 LIMIT 1'
            );

            $stmt->execute(['id' => $id]);
            $aprendiz = $stmt->fetch();

            return $aprendiz ?: null;
        } catch (PDOException $e) {
            error_log("Error finding aprendiz by ID: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Crea un nuevo aprendiz
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO aprendices (documento, nombre, apellido, email, estado) 
                 VALUES (:documento, :nombre, :apellido, :email, :estado)'
            );

            $stmt->execute([
                'documento' => $data['documento'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $data['email'] ?? null,
                'estado' => $data['estado'] ?? 'activo',
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating aprendiz: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un aprendiz existente
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = ['id' => $id];

            if (isset($data['documento'])) {
                $fields[] = 'documento = :documento';
                $params['documento'] = $data['documento'];
            }
            if (isset($data['nombre'])) {
                $fields[] = 'nombre = :nombre';
                $params['nombre'] = $data['nombre'];
            }
            if (isset($data['apellido'])) {
                $fields[] = 'apellido = :apellido';
                $params['apellido'] = $data['apellido'];
            }
            if (isset($data['email'])) {
                $fields[] = 'email = :email';
                $params['email'] = $data['email'];
            }
            if (isset($data['estado'])) {
                $fields[] = 'estado = :estado';
                $params['estado'] = $data['estado'];
            }

            if (empty($fields)) {
                return false;
            }

            $sql = 'UPDATE aprendices SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = Connection::prepare($sql);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating aprendiz: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los aprendices con paginación
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, apellido, email, estado 
                 FROM aprendices 
                 ORDER BY apellido ASC, nombre ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding all aprendices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de aprendices
     */
    public function count(): int
    {
        try {
            $stmt = Connection::query('SELECT COUNT(*) as total FROM aprendices');
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting aprendices: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Vincula un aprendiz a una ficha
     */
    public function attachToFicha(int $aprendizId, int $fichaId): bool
    {
        try {
            $stmt = Connection::prepare(
                'INSERT IGNORE INTO ficha_aprendiz (id_ficha, id_aprendiz) 
                 VALUES (:id_ficha, :id_aprendiz)'
            );

            return $stmt->execute([
                'id_ficha' => $fichaId,
                'id_aprendiz' => $aprendizId,
            ]);
        } catch (PDOException $e) {
            error_log("Error attaching aprendiz to ficha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desvincula un aprendiz de una ficha
     */
    public function detachFromFicha(int $aprendizId, int $fichaId): bool
    {
        try {
            $stmt = Connection::prepare(
                'DELETE FROM ficha_aprendiz 
                 WHERE id_ficha = :id_ficha AND id_aprendiz = :id_aprendiz'
            );

            return $stmt->execute([
                'id_ficha' => $fichaId,
                'id_aprendiz' => $aprendizId,
            ]);
        } catch (PDOException $e) {
            error_log("Error detaching aprendiz from ficha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un aprendiz
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = Connection::prepare('DELETE FROM aprendices WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting aprendiz: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca aprendices por documento, nombre o apellido
     */
    public function search(string $search, int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, apellido, email, estado 
                 FROM aprendices 
                 WHERE documento LIKE :search 
                    OR nombre LIKE :search 
                    OR apellido LIKE :search
                    OR email LIKE :search
                 ORDER BY apellido ASC, nombre ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $searchTerm = "%{$search}%";
            $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching aprendices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta aprendices que coinciden con búsqueda
     */
    public function countSearch(string $search): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total FROM aprendices 
                 WHERE documento LIKE :search 
                    OR nombre LIKE :search 
                    OR apellido LIKE :search
                    OR email LIKE :search'
            );
            $searchTerm = "%{$search}%";
            $stmt->execute(['search' => $searchTerm]);
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting search aprendices: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene aprendices por estado
     */
    public function findByEstado(string $estado, int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, apellido, email, estado 
                 FROM aprendices 
                 WHERE estado = :estado 
                 ORDER BY apellido ASC, nombre ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding aprendices by estado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta aprendices por estado
     */
    public function countByEstado(string $estado): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total FROM aprendices WHERE estado = :estado'
            );
            $stmt->execute(['estado' => $estado]);
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting aprendices by estado: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene las fichas a las que pertenece un aprendiz
     */
    public function getFichas(int $aprendizId): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT f.id, f.numero_ficha, f.nombre, f.estado, fa.fecha_vinculacion
                 FROM fichas f
                 INNER JOIN ficha_aprendiz fa ON f.id = fa.id_ficha
                 WHERE fa.id_aprendiz = :id_aprendiz
                 ORDER BY f.numero_ficha ASC'
            );

            $stmt->execute(['id_aprendiz' => $aprendizId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting fichas for aprendiz: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Búsqueda avanzada de aprendices con múltiples filtros
     * @param array $filters Filtros: search, estado, ficha_id, fecha_desde, fecha_hasta
     * @param int $limit Límite de registros
     * @param int $offset Offset para paginación
     * @return array
     */
    public function advancedSearch(array $filters, int $limit = 50, int $offset = 0): array
    {
        try {
            $conditions = [];
            $params = [];
            $joins = '';

            // Filtro de búsqueda por documento, nombre, apellido o email
            if (!empty($filters['search'])) {
                $conditions[] = '(a.documento LIKE :search OR a.nombre LIKE :search OR a.apellido LIKE :search OR a.email LIKE :search)';
                $params['search'] = "%{$filters['search']}%";
            }

            // Filtro por estado
            if (!empty($filters['estado']) && in_array($filters['estado'], ['activo', 'retirado'])) {
                $conditions[] = 'a.estado = :estado';
                $params['estado'] = $filters['estado'];
            }

            // Filtro por ficha
            if (!empty($filters['ficha_id'])) {
                $joins = 'INNER JOIN ficha_aprendiz fa ON a.id = fa.id_aprendiz';
                $conditions[] = 'fa.id_ficha = :ficha_id';
                $params['ficha_id'] = $filters['ficha_id'];
            }

            // Filtro por rango de fechas de creación
            if (!empty($filters['fecha_desde'])) {
                $conditions[] = 'DATE(a.created_at) >= :fecha_desde';
                $params['fecha_desde'] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $conditions[] = 'DATE(a.created_at) <= :fecha_hasta';
                $params['fecha_hasta'] = $filters['fecha_hasta'];
            }

            // Construir query
            $sql = 'SELECT DISTINCT a.id, a.documento, a.nombre, a.apellido, a.email, a.estado, a.created_at 
                    FROM aprendices a ' . $joins;
            
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }
            
            $sql .= ' ORDER BY a.apellido ASC, a.nombre ASC LIMIT :limit OFFSET :offset';

            $stmt = Connection::prepare($sql);
            
            // Bind todos los parámetros
            foreach ($params as $key => $value) {
                if ($key === 'ficha_id') {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
                }
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in advancedSearch: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta aprendices con filtros avanzados
     */
    public function countAdvancedSearch(array $filters): int
    {
        try {
            $conditions = [];
            $params = [];
            $joins = '';

            if (!empty($filters['search'])) {
                $conditions[] = '(a.documento LIKE :search OR a.nombre LIKE :search OR a.apellido LIKE :search OR a.email LIKE :search)';
                $params['search'] = "%{$filters['search']}%";
            }

            if (!empty($filters['estado']) && in_array($filters['estado'], ['activo', 'retirado'])) {
                $conditions[] = 'a.estado = :estado';
                $params['estado'] = $filters['estado'];
            }

            if (!empty($filters['ficha_id'])) {
                $joins = 'INNER JOIN ficha_aprendiz fa ON a.id = fa.id_aprendiz';
                $conditions[] = 'fa.id_ficha = :ficha_id';
                $params['ficha_id'] = $filters['ficha_id'];
            }

            if (!empty($filters['fecha_desde'])) {
                $conditions[] = 'DATE(a.created_at) >= :fecha_desde';
                $params['fecha_desde'] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $conditions[] = 'DATE(a.created_at) <= :fecha_hasta';
                $params['fecha_hasta'] = $filters['fecha_hasta'];
            }

            $sql = 'SELECT COUNT(DISTINCT a.id) as total FROM aprendices a ' . $joins;
            
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $stmt = Connection::prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === 'ficha_id') {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error in countAdvancedSearch: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cuenta aprendices en una ficha específica (optimizado)
     */
    public function countByFicha(int $fichaId): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(DISTINCT id_aprendiz) as total 
                 FROM ficha_aprendiz 
                 WHERE id_ficha = :id_ficha'
            );
            
            $stmt->execute(['id_ficha' => $fichaId]);
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error in countByFicha: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene aprendices con información de sus fichas (optimizado)
     */
    public function findWithFichas(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT a.id, a.documento, a.nombre, a.apellido, a.email, a.estado,
                        COUNT(DISTINCT fa.id_ficha) as total_fichas
                 FROM aprendices a
                 LEFT JOIN ficha_aprendiz fa ON a.id = fa.id_aprendiz
                 GROUP BY a.id, a.documento, a.nombre, a.apellido, a.email, a.estado
                 ORDER BY a.apellido ASC, a.nombre ASC
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in findWithFichas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un aprendiz está vinculado a una ficha
     */
    public function isAttachedToFicha(int $aprendizId, int $fichaId): bool
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total 
                 FROM ficha_aprendiz 
                 WHERE id_aprendiz = :id_aprendiz AND id_ficha = :id_ficha'
            );
            
            $stmt->execute([
                'id_aprendiz' => $aprendizId,
                'id_ficha' => $fichaId
            ]);
            
            $result = $stmt->fetch();
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error in isAttachedToFicha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas generales de aprendices
     */
    public function getStats(): array
    {
        try {
            $stmt = Connection::query(
                'SELECT 
                    COUNT(*) as total_aprendices,
                    SUM(CASE WHEN estado = "activo" THEN 1 ELSE 0 END) as aprendices_activos,
                    SUM(CASE WHEN estado = "retirado" THEN 1 ELSE 0 END) as aprendices_retirados
                 FROM aprendices'
            );
            
            return $stmt->fetch() ?: [
                'total_aprendices' => 0,
                'aprendices_activos' => 0,
                'aprendices_retirados' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error in getStats: " . $e->getMessage());
            return [
                'total_aprendices' => 0,
                'aprendices_activos' => 0,
                'aprendices_retirados' => 0
            ];
        }
    }

    /**
     * Búsqueda por múltiples documentos (útil para validación CSV)
     */
    public function findByDocumentos(array $documentos): array
    {
        if (empty($documentos)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($documentos), '?'));
            $stmt = Connection::prepare(
                "SELECT id, documento, nombre, apellido, email, estado 
                 FROM aprendices 
                 WHERE documento IN ($placeholders)"
            );
            
            $stmt->execute($documentos);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in findByDocumentos: " . $e->getMessage());
            return [];
        }
    }
}

