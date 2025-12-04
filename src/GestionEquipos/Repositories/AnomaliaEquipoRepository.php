<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDOException;

/**
 * Repositorio para gestión de anomalías de equipos
 */
class AnomaliaEquipoRepository
{
    /**
     * Crea una nueva anomalía
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO anomalias_equipos 
                 (id_ingreso, descripcion, id_administrativo_gestor)
                 VALUES 
                 (:id_ingreso, :descripcion, :id_administrativo_gestor)'
            );

            $stmt->execute([
                'id_ingreso' => $data['id_ingreso'],
                'descripcion' => $data['descripcion'],
                'id_administrativo_gestor' => $data['id_administrativo_gestor'],
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating anomalia_equipo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Marca una anomalía como resuelta
     */
    public function marcarResuelta(int $anomaliaId): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE anomalias_equipos 
                 SET resuelta = TRUE, resuelta_en = NOW()
                 WHERE id = :id'
            );

            return $stmt->execute(['id' => $anomaliaId]);
        } catch (PDOException $e) {
            error_log("Error marking anomalia as resolved: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene anomalías pendientes (no resueltas)
     */
    public function findPendientes(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.*,
                    ie.id_equipo,
                    ie.id_aprendiz,
                    e.numero_serial,
                    e.marca,
                    ap.nombre as aprendiz_nombre,
                    ap.apellido as aprendiz_apellido,
                    u.nombre as administrativo_nombre
                 FROM anomalias_equipos a
                 INNER JOIN ingresos_equipos ie ON a.id_ingreso = ie.id
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 INNER JOIN aprendices ap ON ie.id_aprendiz = ap.id
                 INNER JOIN usuarios u ON a.id_administrativo_gestor = u.id
                 WHERE a.resuelta = FALSE
                 ORDER BY a.creada_en DESC
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding pending anomalias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una anomalía por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.*,
                    ie.id_equipo,
                    ie.id_aprendiz,
                    e.numero_serial,
                    e.marca,
                    ap.nombre as aprendiz_nombre,
                    ap.apellido as aprendiz_apellido,
                    u.nombre as administrativo_nombre
                 FROM anomalias_equipos a
                 INNER JOIN ingresos_equipos ie ON a.id_ingreso = ie.id
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 INNER JOIN aprendices ap ON ie.id_aprendiz = ap.id
                 INNER JOIN usuarios u ON a.id_administrativo_gestor = u.id
                 WHERE a.id = :id
                 LIMIT 1'
            );

            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding anomalia by id: " . $e->getMessage());
            return null;
        }
    }
}

