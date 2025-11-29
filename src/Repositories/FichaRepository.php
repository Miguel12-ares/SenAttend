<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repositorio para la entidad Ficha
 */
class FichaRepository
{
    /**
     * Obtiene todas las fichas con paginación
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, numero_ficha, nombre, jornada, estado 
                 FROM fichas 
                 ORDER BY numero_ficha ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding all fichas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca una ficha por número de ficha
     */
    public function findByNumero(string $numeroFicha): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, numero_ficha, nombre, jornada, estado 
                 FROM fichas 
                 WHERE numero_ficha = :numero_ficha 
                 LIMIT 1'
            );

            $stmt->execute(['numero_ficha' => $numeroFicha]);
            $ficha = $stmt->fetch();

            return $ficha ?: null;
        } catch (PDOException $e) {
            error_log("Error finding ficha by numero: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca una ficha por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, numero_ficha, nombre, jornada, estado 
                 FROM fichas 
                 WHERE id = :id 
                 LIMIT 1'
            );

            $stmt->execute(['id' => $id]);
            $ficha = $stmt->fetch();

            return $ficha ?: null;
        } catch (PDOException $e) {
            error_log("Error finding ficha by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea una nueva ficha
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO fichas (numero_ficha, nombre, jornada, estado) 
                 VALUES (:numero_ficha, :nombre, :jornada, :estado)'
            );

            $stmt->execute([
                'numero_ficha' => $data['numero_ficha'],
                'nombre' => $data['nombre'],
                'jornada' => $data['jornada'] ?? 'Mañana',
                'estado' => $data['estado'] ?? 'activa',
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating ficha: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza una ficha existente
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = ['id' => $id];

            if (isset($data['numero_ficha'])) {
                $fields[] = 'numero_ficha = :numero_ficha';
                $params['numero_ficha'] = $data['numero_ficha'];
            }
            if (isset($data['nombre'])) {
                $fields[] = 'nombre = :nombre';
                $params['nombre'] = $data['nombre'];
            }
            if (isset($data['jornada'])) {
                $fields[] = 'jornada = :jornada';
                $params['jornada'] = $data['jornada'];
            }
            if (isset($data['estado'])) {
                $fields[] = 'estado = :estado';
                $params['estado'] = $data['estado'];
            }

            if (empty($fields)) {
                return false;
            }

            $sql = 'UPDATE fichas SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = Connection::prepare($sql);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating ficha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene fichas activas
     */
    public function findActive(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, numero_ficha, nombre, jornada, estado 
                 FROM fichas 
                 WHERE estado = "activa" 
                 ORDER BY numero_ficha ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding active fichas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de fichas
     */
    public function count(): int
    {
        try {
            $stmt = Connection::query('SELECT COUNT(*) as total FROM fichas');
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting fichas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cuenta aprendices en una ficha
     */
    public function countAprendices(int $fichaId): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total 
                 FROM ficha_aprendiz 
                 WHERE id_ficha = :id_ficha'
            );

            $stmt->execute(['id_ficha' => $fichaId]);
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting aprendices in ficha: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Elimina una ficha
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = Connection::prepare('DELETE FROM fichas WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting ficha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca fichas por número de ficha únicamente
     */
    public function search(string $search, int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, numero_ficha, nombre, jornada, estado 
                 FROM fichas 
                 WHERE numero_ficha LIKE :search
                 ORDER BY numero_ficha ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $searchTerm = "%{$search}%";
            $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching fichas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta fichas que coinciden con búsqueda por número de ficha
     */
    public function countSearch(string $search): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total FROM fichas 
                 WHERE numero_ficha LIKE :search'
            );
            $searchTerm = "%{$search}%";
            $stmt->execute(['search' => $searchTerm]);
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting search fichas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene fichas por estado
     */
    public function findByEstado(string $estado, int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, numero_ficha, nombre, jornada, estado 
                 FROM fichas 
                 WHERE estado = :estado 
                 ORDER BY numero_ficha ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding fichas by estado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta fichas por estado
     */
    public function countByEstado(string $estado): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total FROM fichas WHERE estado = :estado'
            );
            $stmt->execute(['estado' => $estado]);
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting fichas by estado: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Búsqueda avanzada de fichas con múltiples filtros
     * @param array $filters Filtros: search, estado, fecha_desde, fecha_hasta
     * @param int $limit Límite de registros
     * @param int $offset Offset para paginación
     * @return array
     */
    public function advancedSearch(array $filters, int $limit = 50, int $offset = 0): array
    {
        try {
            $conditions = [];
            $params = [];

            // Filtro de búsqueda por número o nombre
            if (!empty($filters['search'])) {
                $conditions[] = '(numero_ficha LIKE :search OR nombre LIKE :search)';
                $params['search'] = "%{$filters['search']}%";
            }

            // Filtro por estado
            if (!empty($filters['estado']) && in_array($filters['estado'], ['activa', 'finalizada'])) {
                $conditions[] = 'estado = :estado';
                $params['estado'] = $filters['estado'];
            }

            // Filtro por rango de fechas de creación
            if (!empty($filters['fecha_desde'])) {
                $conditions[] = 'DATE(created_at) >= :fecha_desde';
                $params['fecha_desde'] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $conditions[] = 'DATE(created_at) <= :fecha_hasta';
                $params['fecha_hasta'] = $filters['fecha_hasta'];
            }

            // Construir query
            $sql = 'SELECT DISTINCT id, numero_ficha, nombre, jornada, estado, created_at 
                    FROM fichas';
            
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }
            
            $sql .= ' ORDER BY numero_ficha ASC LIMIT :limit OFFSET :offset';

            $stmt = Connection::prepare($sql);
            
            // Bind todos los parámetros
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
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
     * Cuenta fichas con filtros avanzados
     */
    public function countAdvancedSearch(array $filters): int
    {
        try {
            $conditions = [];
            $params = [];

            if (!empty($filters['search'])) {
                $conditions[] = '(numero_ficha LIKE :search OR nombre LIKE :search)';
                $params['search'] = "%{$filters['search']}%";
            }

            if (!empty($filters['estado']) && in_array($filters['estado'], ['activa', 'finalizada'])) {
                $conditions[] = 'estado = :estado';
                $params['estado'] = $filters['estado'];
            }

            if (!empty($filters['fecha_desde'])) {
                $conditions[] = 'DATE(created_at) >= :fecha_desde';
                $params['fecha_desde'] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $conditions[] = 'DATE(created_at) <= :fecha_hasta';
                $params['fecha_hasta'] = $filters['fecha_hasta'];
            }

            $sql = 'SELECT COUNT(DISTINCT id) as total FROM fichas';
            
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $stmt = Connection::prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
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
     * Obtiene fichas con información completa incluyendo estadísticas
     */
    public function findWithStats(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT f.id, f.numero_ficha, f.nombre, f.jornada, f.estado, f.created_at,
                        COUNT(DISTINCT fa.id_aprendiz) as total_aprendices,
                        COUNT(DISTINCT CASE WHEN a.estado = "activo" THEN fa.id_aprendiz END) as aprendices_activos
                 FROM fichas f
                 LEFT JOIN ficha_aprendiz fa ON f.id = fa.id_ficha
                 LEFT JOIN aprendices a ON fa.id_aprendiz = a.id
                 GROUP BY f.id, f.numero_ficha, f.nombre, f.estado, f.created_at
                 ORDER BY f.numero_ficha ASC
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in findWithStats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las fichas con más aprendices
     */
    public function getTopFichasByAprendices(int $limit = 10): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT f.id, f.numero_ficha, f.nombre, f.estado,
                        COUNT(fa.id_aprendiz) as total_aprendices
                 FROM fichas f
                 LEFT JOIN ficha_aprendiz fa ON f.id = fa.id_ficha
                 GROUP BY f.id
                 ORDER BY total_aprendices DESC
                 LIMIT :limit'
            );

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getTopFichasByAprendices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si existe una ficha con aprendices asignados
     */
    public function hasAprendices(int $id): bool
    {
        return $this->countAprendices($id) > 0;
    }

    /**
     * Obtiene estadísticas generales de fichas
     */
    public function getStats(): array
    {
        try {
            $stmt = Connection::query(
                'SELECT 
                    COUNT(*) as total_fichas,
                    SUM(CASE WHEN estado = "activa" THEN 1 ELSE 0 END) as fichas_activas,
                    SUM(CASE WHEN estado = "finalizada" THEN 1 ELSE 0 END) as fichas_finalizadas
                 FROM fichas'
            );
            
            return $stmt->fetch() ?: [
                'total_fichas' => 0,
                'fichas_activas' => 0,
                'fichas_finalizadas' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error in getStats: " . $e->getMessage());
            return [
                'total_fichas' => 0,
                'fichas_activas' => 0,
                'fichas_finalizadas' => 0
            ];
        }
    }
}

