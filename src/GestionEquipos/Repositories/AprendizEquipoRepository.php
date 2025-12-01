<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

class AprendizEquipoRepository
{
    /**
     * Obtiene los equipos asociados a un aprendiz (con estado en la relación).
     */
    public function findEquiposByAprendiz(int $aprendizId): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT ae.id as relacion_id,
                        ae.estado,
                        ae.fecha_asignacion,
                        e.id as equipo_id,
                        e.numero_serial,
                        e.marca,
                        e.imagen,
                        e.activo
                 FROM aprendiz_equipo ae
                 INNER JOIN equipos e ON ae.id_equipo = e.id
                 WHERE ae.id_aprendiz = :aprendiz_id
                 ORDER BY ae.fecha_asignacion DESC'
            );

            $stmt->execute(['aprendiz_id' => $aprendizId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching equipos by aprendiz: " . $e->getMessage());
            return [];
        }
    }

    public function createRelacion(int $aprendizId, int $equipoId, string $estado = 'activo'): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO aprendiz_equipo (id_aprendiz, id_equipo, estado) 
                 VALUES (:aprendiz_id, :equipo_id, :estado)'
            );

            $stmt->execute([
                'aprendiz_id' => $aprendizId,
                'equipo_id' => $equipoId,
                'estado' => $estado,
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating relacion aprendiz_equipo: " . $e->getMessage());
            throw $e;
        }
    }
}


