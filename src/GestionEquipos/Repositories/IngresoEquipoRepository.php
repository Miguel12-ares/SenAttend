<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDOException;

/**
 * Repositorio para gestión de ingresos y salidas de equipos
 */
class IngresoEquipoRepository
{
    /**
     * Crea un nuevo registro de ingreso
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO ingresos_equipos 
                 (id_equipo, id_aprendiz, fecha_ingreso, hora_ingreso, id_portero, observaciones)
                 VALUES 
                 (:id_equipo, :id_aprendiz, :fecha_ingreso, :hora_ingreso, :id_portero, :observaciones)'
            );

            $stmt->execute([
                'id_equipo' => $data['id_equipo'],
                'id_aprendiz' => $data['id_aprendiz'],
                'fecha_ingreso' => $data['fecha_ingreso'],
                'hora_ingreso' => $data['hora_ingreso'],
                'id_portero' => $data['id_portero'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating ingreso_equipo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra la salida de un equipo
     */
    public function registrarSalida(int $ingresoId, int $porteroId, ?string $observaciones = null): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE ingresos_equipos 
                 SET fecha_salida = CURDATE(),
                     hora_salida = CURTIME(),
                     observaciones = COALESCE(:observaciones, observaciones)
                 WHERE id = :ingreso_id 
                   AND fecha_salida IS NULL'
            );

            return $stmt->execute([
                'ingreso_id' => $ingresoId,
                'observaciones' => $observaciones,
            ]);
        } catch (PDOException $e) {
            error_log("Error registering salida: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca un ingreso activo (sin salida) para un equipo
     */
    public function findIngresoActivo(int $equipoId): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT * FROM ingresos_equipos 
                 WHERE id_equipo = :equipo_id 
                   AND fecha_salida IS NULL 
                   AND hora_salida IS NULL
                 ORDER BY fecha_ingreso DESC, hora_ingreso DESC
                 LIMIT 1'
            );

            $stmt->execute(['equipo_id' => $equipoId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding active ingreso: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene todos los ingresos activos (sin salida)
     */
    public function findIngresosActivos(int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    ie.*,
                    e.numero_serial,
                    e.marca,
                    a.nombre as aprendiz_nombre,
                    a.apellido as aprendiz_apellido,
                    a.documento as aprendiz_documento,
                    u.nombre as portero_nombre
                 FROM ingresos_equipos ie
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 INNER JOIN usuarios u ON ie.id_portero = u.id
                 WHERE ie.fecha_salida IS NULL 
                   AND ie.hora_salida IS NULL
                 ORDER BY ie.fecha_ingreso DESC, ie.hora_ingreso DESC
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding active ingresos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta ingresos activos
     */
    public function countIngresosActivos(): int
    {
        try {
            $stmt = Connection::query(
                'SELECT COUNT(*) as total 
                 FROM ingresos_equipos 
                 WHERE fecha_salida IS NULL AND hora_salida IS NULL'
            );
            $result = $stmt->fetch();
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting active ingresos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene un ingreso por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    ie.*,
                    e.numero_serial,
                    e.marca,
                    a.nombre as aprendiz_nombre,
                    a.apellido as aprendiz_apellido,
                    a.documento as aprendiz_documento,
                    u.nombre as portero_nombre
                 FROM ingresos_equipos ie
                 INNER JOIN equipos e ON ie.id_equipo = e.id
                 INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                 INNER JOIN usuarios u ON ie.id_portero = u.id
                 WHERE ie.id = :id
                 LIMIT 1'
            );

            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error finding ingreso by id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene historial de ingresos/salidas con filtros
     */
    public function findHistorial(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $conditions = [];
            $params = [];

            if (!empty($filters['fecha_desde'])) {
                $conditions[] = 'ie.fecha_ingreso >= :fecha_desde';
                $params['fecha_desde'] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $conditions[] = 'ie.fecha_ingreso <= :fecha_hasta';
                $params['fecha_hasta'] = $filters['fecha_hasta'];
            }

            if (!empty($filters['equipo_id'])) {
                $conditions[] = 'ie.id_equipo = :equipo_id';
                $params['equipo_id'] = $filters['equipo_id'];
            }

            if (!empty($filters['aprendiz_id'])) {
                $conditions[] = 'ie.id_aprendiz = :aprendiz_id';
                $params['aprendiz_id'] = $filters['aprendiz_id'];
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql = "SELECT 
                        ie.*,
                        e.numero_serial,
                        e.marca,
                        a.nombre as aprendiz_nombre,
                        a.apellido as aprendiz_apellido,
                        a.documento as aprendiz_documento,
                        u.nombre as portero_nombre
                     FROM ingresos_equipos ie
                     INNER JOIN equipos e ON ie.id_equipo = e.id
                     INNER JOIN aprendices a ON ie.id_aprendiz = a.id
                     INNER JOIN usuarios u ON ie.id_portero = u.id
                     {$whereClause}
                     ORDER BY ie.fecha_ingreso DESC, ie.hora_ingreso DESC
                     LIMIT :limit OFFSET :offset";

            $stmt = Connection::prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, \PDO::PARAM_STR);
            }

            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding historial: " . $e->getMessage());
            return [];
        }
    }
}

