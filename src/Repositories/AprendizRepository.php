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
                'SELECT a.id, a.documento, a.nombre, a.apellido, a.codigo_carnet, a.estado 
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
                'SELECT id, documento, nombre, apellido, codigo_carnet, estado 
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
                'SELECT id, documento, nombre, apellido, codigo_carnet, estado 
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
     * Busca aprendices por código de carnet
     */
    public function findByCodigoCarnet(string $codigoCarnet): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, apellido, codigo_carnet, estado 
                 FROM aprendices 
                 WHERE codigo_carnet = :codigo_carnet 
                 LIMIT 1'
            );

            $stmt->execute(['codigo_carnet' => $codigoCarnet]);
            $aprendiz = $stmt->fetch();

            return $aprendiz ?: null;
        } catch (PDOException $e) {
            error_log("Error finding aprendiz by codigo_carnet: " . $e->getMessage());
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
                'INSERT INTO aprendices (documento, nombre, apellido, codigo_carnet, estado) 
                 VALUES (:documento, :nombre, :apellido, :codigo_carnet, :estado)'
            );

            $stmt->execute([
                'documento' => $data['documento'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'codigo_carnet' => $data['codigo_carnet'],
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
            if (isset($data['codigo_carnet'])) {
                $fields[] = 'codigo_carnet = :codigo_carnet';
                $params['codigo_carnet'] = $data['codigo_carnet'];
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
                'SELECT id, documento, nombre, apellido, codigo_carnet, estado 
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
}

