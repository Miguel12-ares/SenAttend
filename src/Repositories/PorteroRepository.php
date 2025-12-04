<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repositorio para la gestión de Porteros
 * Maneja todas las operaciones de base de datos relacionadas con usuarios con rol 'portero'
 */
class PorteroRepository
{
    /**
     * Obtiene todos los porteros con filtros y paginación
     * 
     * @param array $filters Filtros: search, nombre
     * @param int $limit Límite de registros
     * @param int $offset Offset para paginación
     * @return array
     */
    public function findAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT id, documento, nombre, email, rol, created_at, updated_at 
                    FROM usuarios 
                    WHERE rol = 'portero'";
            
            $params = [];
            
            // Filtro de búsqueda (solo documento)
            if (!empty($filters['search']) && trim($filters['search']) !== '') {
                $sql .= " AND documento LIKE :search";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }
            
            // Filtro por nombre
            if (!empty($filters['nombre']) && trim($filters['nombre']) !== '') {
                $sql .= " AND nombre LIKE :nombre";
                $params[':nombre'] = '%' . trim($filters['nombre']) . '%';
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($sql);
            
            // Bind de parámetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::findAll - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cuenta el total de porteros con filtros
     * 
     * @param array $filters Filtros aplicados
     * @return int
     */
    public function count(array $filters = []): int
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'portero'";
            $params = [];
            
            if (!empty($filters['search']) && trim($filters['search']) !== '') {
                $sql .= " AND documento LIKE :search";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }
            
            if (!empty($filters['nombre']) && trim($filters['nombre']) !== '') {
                $sql .= " AND nombre LIKE :nombre";
                $params[':nombre'] = '%' . trim($filters['nombre']) . '%';
            }
            
            $stmt = $db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total'];
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::count - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca un portero por ID
     * 
     * @param int $id ID del portero
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT id, documento, nombre, email, rol, created_at, updated_at 
                    FROM usuarios 
                    WHERE id = :id AND rol = 'portero'";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::findById - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca un portero por documento
     * 
     * @param string $documento Documento del portero
     * @return array|null
     */
    public function findByDocumento(string $documento): ?array
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT id, documento, nombre, email, rol, created_at, updated_at 
                    FROM usuarios 
                    WHERE documento = :documento";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':documento', $documento);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::findByDocumento - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca un portero por email
     * 
     * @param string $email Email del portero
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT id, documento, nombre, email, rol, created_at, updated_at 
                    FROM usuarios 
                    WHERE email = :email";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::findByEmail - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crea un nuevo portero
     * 
     * @param array $data Datos del portero
     * @return int ID del portero creado
     */
    public function create(array $data): int
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "INSERT INTO usuarios (documento, nombre, email, password_hash, rol, created_at, updated_at) 
                    VALUES (:documento, :nombre, :email, :password_hash, 'portero', NOW(), NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':documento', $data['documento']);
            $stmt->bindValue(':nombre', $data['nombre']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':password_hash', $data['password_hash']);
            
            $stmt->execute();
            
            return (int) $db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::create - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un portero existente
     * 
     * @param int $id ID del portero
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Connection::getInstance();
            
            $fields = [];
            $params = [':id' => $id];
            
            if (isset($data['documento'])) {
                $fields[] = "documento = :documento";
                $params[':documento'] = $data['documento'];
            }
            
            if (isset($data['nombre'])) {
                $fields[] = "nombre = :nombre";
                $params[':nombre'] = $data['nombre'];
            }
            
            if (isset($data['email'])) {
                $fields[] = "email = :email";
                $params[':email'] = $data['email'];
            }
            
            if (isset($data['password_hash'])) {
                $fields[] = "password_hash = :password_hash";
                $params[':password_hash'] = $data['password_hash'];
            }
            
            $fields[] = "updated_at = NOW()";
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = :id AND rol = 'portero'";
            
            $stmt = $db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::update - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un portero (hard delete)
     * 
     * @param int $id ID del portero
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "DELETE FROM usuarios WHERE id = :id AND rol = 'portero'";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::delete - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica si un email ya existe en la base de datos
     * 
     * @param string $email Email a verificar
     * @param int|null $excludeId ID a excluir de la búsqueda (para edición)
     * @return bool
     */
    public function checkEmailExists(string $email, ?int $excludeId = null): bool
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email";
            
            if ($excludeId !== null) {
                $sql .= " AND id != :excludeId";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email);
            
            if ($excludeId !== null) {
                $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::checkEmailExists - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica si un documento ya existe en la base de datos
     * 
     * @param string $documento Documento a verificar
     * @param int|null $excludeId ID a excluir de la búsqueda (para edición)
     * @return bool
     */
    public function checkDocumentExists(string $documento, ?int $excludeId = null): bool
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE documento = :documento";
            
            if ($excludeId !== null) {
                $sql .= " AND id != :excludeId";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':documento', $documento);
            
            if ($excludeId !== null) {
                $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::checkDocumentExists - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Búsqueda por múltiples documentos (útil para validación CSV)
     * 
     * @param array $documentos Array de documentos a buscar
     * @return array
     */
    public function findByDocumentos(array $documentos): array
    {
        try {
            if (empty($documentos)) {
                return [];
            }
            
            $db = Connection::getInstance();
            
            $placeholders = implode(',', array_fill(0, count($documentos), '?'));
            $sql = "SELECT documento, email FROM usuarios WHERE documento IN ($placeholders)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($documentos);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::findByDocumentos - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene estadísticas de porteros
     * 
     * @return array
     */
    public function getStats(): array
    {
        try {
            $db = Connection::getInstance();
            
            $sql = "SELECT 
                        COUNT(*) as total_porteros,
                        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as nuevos_hoy,
                        COUNT(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as nuevos_este_ano
                    FROM usuarios 
                    WHERE rol = 'portero'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en PorteroRepository::getStats - " . $e->getMessage());
            throw $e;
        }
    }
}
