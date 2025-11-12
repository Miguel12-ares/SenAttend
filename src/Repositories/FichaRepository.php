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
                'SELECT id, numero_ficha, nombre, estado 
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
                'SELECT id, numero_ficha, nombre, estado 
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
                'SELECT id, numero_ficha, nombre, estado 
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
                'INSERT INTO fichas (numero_ficha, nombre, estado) 
                 VALUES (:numero_ficha, :nombre, :estado)'
            );

            $stmt->execute([
                'numero_ficha' => $data['numero_ficha'],
                'nombre' => $data['nombre'],
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
                'SELECT id, numero_ficha, nombre, estado 
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
     * Busca fichas por número o nombre
     */
    public function search(string $search, int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, numero_ficha, nombre, estado 
                 FROM fichas 
                 WHERE numero_ficha LIKE :search OR nombre LIKE :search
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
     * Cuenta fichas que coinciden con búsqueda
     */
    public function countSearch(string $search): int
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total FROM fichas 
                 WHERE numero_ficha LIKE :search OR nombre LIKE :search'
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
                'SELECT id, numero_ficha, nombre, estado 
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
}

