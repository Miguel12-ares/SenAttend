<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repositorio para la entidad Usuario
 */
class UserRepository
{
    /**
     * Busca un usuario por email
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, email, password_hash, rol 
                 FROM usuarios 
                 WHERE email = :email 
                 LIMIT 1'
            );

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca un usuario por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, email, password_hash, rol 
                 FROM usuarios 
                 WHERE id = :id 
                 LIMIT 1'
            );

            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca un usuario por documento
     */
    public function findByDocumento(string $documento): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, email, password_hash, rol 
                 FROM usuarios 
                 WHERE documento = :documento 
                 LIMIT 1'
            );

            $stmt->execute(['documento' => $documento]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by documento: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo usuario
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO usuarios (documento, nombre, email, password_hash, rol) 
                 VALUES (:documento, :nombre, :email, :password_hash, :rol)'
            );

            $stmt->execute([
                'documento' => $data['documento'],
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'password_hash' => $data['password_hash'],
                'rol' => $data['rol'],
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un usuario existente
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = ['id' => $id];

            // Construir campos dinámicamente
            if (isset($data['documento'])) {
                $fields[] = 'documento = :documento';
                $params['documento'] = $data['documento'];
            }
            if (isset($data['nombre'])) {
                $fields[] = 'nombre = :nombre';
                $params['nombre'] = $data['nombre'];
            }
            if (isset($data['email'])) {
                $fields[] = 'email = :email';
                $params['email'] = $data['email'];
            }
            if (isset($data['password_hash'])) {
                $fields[] = 'password_hash = :password_hash';
                $params['password_hash'] = $data['password_hash'];
            }
            if (isset($data['rol'])) {
                $fields[] = 'rol = :rol';
                $params['rol'] = $data['rol'];
            }

            if (empty($fields)) {
                return false;
            }

            $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = Connection::prepare($sql);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los usuarios con paginación
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, documento, nombre, email, rol 
                 FROM usuarios 
                 ORDER BY nombre ASC 
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding all users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de usuarios
     */
    public function count(): int
    {
        try {
            $stmt = Connection::query('SELECT COUNT(*) as total FROM usuarios');
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting users: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Elimina un usuario (soft delete podría implementarse)
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = Connection::prepare('DELETE FROM usuarios WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
}

