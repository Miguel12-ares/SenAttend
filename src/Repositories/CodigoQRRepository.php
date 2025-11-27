<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repositorio para la entidad CodigoQR
 * Gestiona códigos QR con expiración de 3 minutos
 */
class CodigoQRRepository
{
    /**
     * Crea un nuevo código QR con expiración
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO codigos_qr (token, id_aprendiz, qr_data, fecha_expiracion) 
                 VALUES (:token, :id_aprendiz, :qr_data, :fecha_expiracion)'
            );

            $stmt->execute([
                'token' => $data['token'],
                'id_aprendiz' => $data['id_aprendiz'],
                'qr_data' => $data['qr_data'],
                'fecha_expiracion' => $data['fecha_expiracion'],
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating codigo QR: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca un código QR por token
     */
    public function findByToken(string $token): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, token, id_aprendiz, qr_data, fecha_generacion, fecha_expiracion, usado, fecha_uso
                 FROM codigos_qr 
                 WHERE token = :token 
                 LIMIT 1'
            );

            $stmt->execute(['token' => $token]);
            $codigo = $stmt->fetch();

            return $codigo ?: null;
        } catch (PDOException $e) {
            error_log("Error finding codigo QR by token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si un código QR es válido (no expirado y no usado)
     */
    public function isValid(string $token): bool
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id FROM codigos_qr 
                 WHERE token = :token 
                   AND usado = FALSE 
                   AND fecha_expiracion > NOW()
                 LIMIT 1'
            );

            $stmt->execute(['token' => $token]);
            $result = $stmt->fetch();

            return $result !== false;
        } catch (PDOException $e) {
            error_log("Error validating codigo QR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca un código QR como usado
     */
    public function markAsUsed(string $token): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE codigos_qr 
                 SET usado = TRUE, fecha_uso = NOW() 
                 WHERE token = :token'
            );

            return $stmt->execute(['token' => $token]);
        } catch (PDOException $e) {
            error_log("Error marking codigo QR as used: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina códigos QR expirados (limpieza automática)
     */
    public function deleteExpired(): int
    {
        try {
            $stmt = Connection::prepare(
                'DELETE FROM codigos_qr 
                 WHERE fecha_expiracion < NOW() 
                   AND usado = FALSE'
            );

            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error deleting expired codigos QR: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene códigos QR de un aprendiz
     */
    public function findByAprendiz(int $aprendizId, int $limit = 10): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT id, token, qr_data, fecha_generacion, fecha_expiracion, usado, fecha_uso
                 FROM codigos_qr 
                 WHERE id_aprendiz = :id_aprendiz
                 ORDER BY fecha_generacion DESC
                 LIMIT :limit'
            );

            $stmt->bindValue(':id_aprendiz', $aprendizId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding codigos QR by aprendiz: " . $e->getMessage());
            return [];
        }
    }
}

