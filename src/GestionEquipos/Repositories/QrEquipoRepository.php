<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDOException;

class QrEquipoRepository
{
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO qr_equipos (id_equipo, id_aprendiz, token, qr_data, fecha_expiracion, activo)
                 VALUES (:id_equipo, :id_aprendiz, :token, :qr_data, :fecha_expiracion, :activo)'
            );

            $stmt->execute([
                'id_equipo' => $data['id_equipo'],
                'id_aprendiz' => $data['id_aprendiz'],
                'token' => $data['token'],
                'qr_data' => $data['qr_data'],
                'fecha_expiracion' => $data['fecha_expiracion'],
                'activo' => $data['activo'] ?? true,
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating qr_equipo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene el QR activo más reciente para un equipo y aprendiz.
     */
    public function findActiveByEquipoAndAprendiz(int $equipoId, int $aprendizId): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT * FROM qr_equipos 
                 WHERE id_equipo = :equipo_id 
                   AND id_aprendiz = :aprendiz_id 
                   AND activo = 1
                 ORDER BY fecha_generacion DESC
                 LIMIT 1'
            );

            $stmt->execute([
                'equipo_id' => $equipoId,
                'aprendiz_id' => $aprendizId,
            ]);

            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding active qr_equipo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca un QR por token (para validación en escaneo)
     */
    public function findByToken(string $token): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT * FROM qr_equipos 
                 WHERE token = :token 
                   AND activo = 1
                 LIMIT 1'
            );

            $stmt->execute(['token' => $token]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding qr_equipo by token: " . $e->getMessage());
            return null;
        }
    }
}


