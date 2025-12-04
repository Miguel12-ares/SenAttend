<?php

namespace App\Repositories;

use App\Database\Connection;
use PDOException;
use RuntimeException;

/**
 * Repositorio para gestión de anomalías de asistencia
 * Permite registrar anomalías por aprendiz o para la ficha en general
 */
class AnomaliaRepository
{
    /**
     * Crea una nueva anomalía
     * 
     * @param array $data Datos de la anomalía
     * @return int ID de la anomalía creada
     * @throws RuntimeException Si hay error al crear
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO anomalias 
                 (id_asistencia, id_aprendiz, id_ficha, tipo_anomalia, descripcion, 
                  registrado_por, fecha_asistencia, ip_address, user_agent) 
                 VALUES 
                 (:id_asistencia, :id_aprendiz, :id_ficha, :tipo_anomalia, :descripcion,
                  :registrado_por, :fecha_asistencia, :ip_address, :user_agent)'
            );

            $stmt->execute([
                'id_asistencia' => $data['id_asistencia'] ?? null,
                'id_aprendiz' => $data['id_aprendiz'] ?? null,
                'id_ficha' => $data['id_ficha'],
                'tipo_anomalia' => $data['tipo_anomalia'],
                'descripcion' => $data['descripcion'] ?? null,
                'registrado_por' => $data['registrado_por'],
                'fecha_asistencia' => $data['fecha_asistencia'],
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating anomalia: " . $e->getMessage());
            throw new RuntimeException('Error al registrar anomalía: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene anomalías por asistencia
     * 
     * @param int $asistenciaId ID de la asistencia
     * @return array Lista de anomalías
     */
    public function findByAsistencia(int $asistenciaId): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.*,
                    u.nombre as registrado_por_nombre,
                    u.rol as registrado_por_rol,
                    ap.nombre as aprendiz_nombre,
                    ap.apellido as aprendiz_apellido
                 FROM anomalias a
                 LEFT JOIN usuarios u ON a.registrado_por = u.id
                 LEFT JOIN aprendices ap ON a.id_aprendiz = ap.id
                 WHERE a.id_asistencia = :id_asistencia
                 ORDER BY a.fecha_registro DESC'
            );

            $stmt->execute(['id_asistencia' => $asistenciaId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding anomalias by asistencia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene anomalías por aprendiz y fecha
     * 
     * @param int $aprendizId ID del aprendiz
     * @param int $fichaId ID de la ficha
     * @param string $fecha Fecha de asistencia (YYYY-MM-DD)
     * @return array Lista de anomalías
     */
    public function findByAprendizAndFecha(int $aprendizId, int $fichaId, string $fecha): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.*,
                    u.nombre as registrado_por_nombre,
                    u.rol as registrado_por_rol
                 FROM anomalias a
                 LEFT JOIN usuarios u ON a.registrado_por = u.id
                 WHERE a.id_aprendiz = :id_aprendiz
                   AND a.id_ficha = :id_ficha
                   AND a.fecha_asistencia = :fecha
                 ORDER BY a.fecha_registro DESC'
            );

            $stmt->execute([
                'id_aprendiz' => $aprendizId,
                'id_ficha' => $fichaId,
                'fecha' => $fecha
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding anomalias by aprendiz and fecha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene anomalías generales de ficha por fecha
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fecha Fecha de asistencia (YYYY-MM-DD)
     * @return array Lista de anomalías
     */
    public function findByFichaAndFecha(int $fichaId, string $fecha): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.*,
                    u.nombre as registrado_por_nombre,
                    u.rol as registrado_por_rol
                 FROM anomalias a
                 LEFT JOIN usuarios u ON a.registrado_por = u.id
                 WHERE a.id_ficha = :id_ficha
                   AND a.fecha_asistencia = :fecha
                   AND a.id_asistencia IS NULL
                   AND a.id_aprendiz IS NULL
                 ORDER BY a.fecha_registro DESC'
            );

            $stmt->execute([
                'id_ficha' => $fichaId,
                'fecha' => $fecha
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding anomalias by ficha and fecha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si ya existe una anomalía del mismo tipo para una asistencia
     * 
     * @param int|null $asistenciaId ID de la asistencia (puede ser NULL)
     * @param int|null $aprendizId ID del aprendiz (puede ser NULL)
     * @param int $fichaId ID de la ficha
     * @param string $fecha Fecha de asistencia
     * @param string $tipoAnomalia Tipo de anomalía
     * @return bool True si existe, False si no
     */
    public function existeAnomalia(
        ?int $asistenciaId,
        ?int $aprendizId,
        int $fichaId,
        string $fecha,
        string $tipoAnomalia
    ): bool {
        try {
            $sql = 'SELECT COUNT(*) as total 
                    FROM anomalias 
                    WHERE id_ficha = :id_ficha
                      AND fecha_asistencia = :fecha
                      AND tipo_anomalia = :tipo_anomalia';

            $params = [
                'id_ficha' => $fichaId,
                'fecha' => $fecha,
                'tipo_anomalia' => $tipoAnomalia
            ];

            if ($asistenciaId !== null) {
                $sql .= ' AND id_asistencia = :id_asistencia';
                $params['id_asistencia'] = $asistenciaId;
            } else {
                $sql .= ' AND id_asistencia IS NULL';
            }

            if ($aprendizId !== null) {
                $sql .= ' AND id_aprendiz = :id_aprendiz';
                $params['id_aprendiz'] = $aprendizId;
            } else {
                $sql .= ' AND id_aprendiz IS NULL';
            }

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking if anomalia exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todas las anomalías de una ficha en un rango de fechas
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return array Lista de anomalías
     */
    public function findByFichaAndRango(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.*,
                    u.nombre as registrado_por_nombre,
                    u.rol as registrado_por_rol,
                    ap.nombre as aprendiz_nombre,
                    ap.apellido as aprendiz_apellido,
                    f.numero_ficha,
                    f.nombre as ficha_nombre
                 FROM anomalias a
                 LEFT JOIN usuarios u ON a.registrado_por = u.id
                 LEFT JOIN aprendices ap ON a.id_aprendiz = ap.id
                 LEFT JOIN fichas f ON a.id_ficha = f.id
                 WHERE a.id_ficha = :id_ficha
                   AND a.fecha_asistencia BETWEEN :fecha_inicio AND :fecha_fin
                 ORDER BY a.fecha_asistencia DESC, a.fecha_registro DESC'
            );

            $stmt->execute([
                'id_ficha' => $fichaId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding anomalias by ficha and rango: " . $e->getMessage());
            return [];
        }
    }
}

