<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repositorio para la entidad Asistencia
 * Sprint 4 - Registro Manual de Asistencia
 */
class AsistenciaRepository
{
    /**
     * Registra asistencia de un aprendiz
     * Valida duplicados con UNIQUE KEY (id_aprendiz, id_ficha, fecha)
     */
    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO asistencias 
                 (id_aprendiz, id_ficha, fecha, hora, estado, registrado_por) 
                 VALUES (:id_aprendiz, :id_ficha, :fecha, :hora, :estado, :registrado_por)'
            );

            $stmt->execute([
                'id_aprendiz' => $data['id_aprendiz'],
                'id_ficha' => $data['id_ficha'],
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'estado' => $data['estado'],
                'registrado_por' => $data['registrado_por'],
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            // Si es error de duplicado, lanzar excepción específica
            if ($e->getCode() == 23000) {
                throw new \RuntimeException('Ya existe un registro de asistencia para este aprendiz en esta fecha');
            }
            error_log("Error creating asistencia: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza el estado de una asistencia existente
     */
    public function updateEstado(int $id, string $nuevoEstado, int $modificadoPor): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE asistencias 
                 SET estado = :estado,
                     registrado_por = :modificado_por
                 WHERE id = :id'
            );

            return $stmt->execute([
                'estado' => $nuevoEstado,
                'modificado_por' => $modificadoPor,
                'id' => $id,
            ]);
        } catch (PDOException $e) {
            error_log("Error updating asistencia estado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene asistencias de una ficha en una fecha específica
     * Retorna lista de aprendices con su estado de asistencia
     */
    public function findByFichaAndFecha(int $fichaId, string $fecha): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.id,
                    a.id_aprendiz,
                    a.estado,
                    a.hora,
                    ap.documento,
                    ap.nombre,
                    ap.apellido,
                    ap.codigo_carnet,
                    u.nombre as registrado_por_nombre
                 FROM asistencias a
                 INNER JOIN aprendices ap ON a.id_aprendiz = ap.id
                 LEFT JOIN usuarios u ON a.registrado_por = u.id
                 WHERE a.id_ficha = :id_ficha AND a.fecha = :fecha
                 ORDER BY ap.apellido ASC, ap.nombre ASC'
            );

            $stmt->execute([
                'id_ficha' => $fichaId,
                'fecha' => $fecha,
            ]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding asistencias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los aprendices de una ficha con su estado de asistencia del día
     * Si no tiene registro, retorna null en los campos de asistencia
     */
    public function getAprendicesConAsistencia(int $fichaId, string $fecha): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    ap.id as id_aprendiz,
                    ap.documento,
                    ap.nombre,
                    ap.apellido,
                    ap.codigo_carnet,
                    ap.estado as estado_aprendiz,
                    a.id as asistencia_id,
                    a.estado as asistencia_estado,
                    a.hora as asistencia_hora
                 FROM aprendices ap
                 INNER JOIN ficha_aprendiz fa ON ap.id = fa.id_aprendiz
                 LEFT JOIN asistencias a ON ap.id = a.id_aprendiz 
                     AND a.id_ficha = :id_ficha 
                     AND a.fecha = :fecha
                 WHERE fa.id_ficha = :id_ficha_2
                     AND ap.estado = "activo"
                 ORDER BY ap.apellido ASC, ap.nombre ASC'
            );

            $stmt->execute([
                'id_ficha' => $fichaId,
                'id_ficha_2' => $fichaId,
                'fecha' => $fecha,
            ]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting aprendices con asistencia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si ya existe un registro de asistencia
     */
    public function existe(int $aprendizId, int $fichaId, string $fecha): bool
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total 
                 FROM asistencias 
                 WHERE id_aprendiz = :id_aprendiz 
                   AND id_ficha = :id_ficha 
                   AND fecha = :fecha'
            );

            $stmt->execute([
                'id_aprendiz' => $aprendizId,
                'id_ficha' => $fichaId,
                'fecha' => $fecha,
            ]);

            $result = $stmt->fetch();
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking asistencia existe: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene asistencias por rango de fechas para reportes
     */
    public function findByFichaAndRango(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    a.id,
                    a.fecha,
                    a.hora,
                    a.estado,
                    ap.documento,
                    ap.nombre,
                    ap.apellido,
                    u.nombre as registrado_por
                 FROM asistencias a
                 INNER JOIN aprendices ap ON a.id_aprendiz = ap.id
                 LEFT JOIN usuarios u ON a.registrado_por = u.id
                 WHERE a.id_ficha = :id_ficha 
                   AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                 ORDER BY a.fecha DESC, ap.apellido ASC'
            );

            $stmt->execute([
                'id_ficha' => $fichaId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error finding asistencias by rango: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas de asistencia por ficha y fecha
     */
    public function getEstadisticas(int $fichaId, string $fecha): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = "presente" THEN 1 ELSE 0 END) as presentes,
                    SUM(CASE WHEN estado = "ausente" THEN 1 ELSE 0 END) as ausentes,
                    SUM(CASE WHEN estado = "tardanza" THEN 1 ELSE 0 END) as tardanzas
                 FROM asistencias
                 WHERE id_ficha = :id_ficha AND fecha = :fecha'
            );

            $stmt->execute([
                'id_ficha' => $fichaId,
                'fecha' => $fecha,
            ]);

            return $stmt->fetch() ?: [
                'total' => 0,
                'presentes' => 0,
                'ausentes' => 0,
                'tardanzas' => 0,
            ];
        } catch (PDOException $e) {
            error_log("Error getting estadisticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Elimina un registro de asistencia
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = Connection::prepare('DELETE FROM asistencias WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting asistencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca una asistencia por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT * FROM asistencias WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $asistencia = $stmt->fetch();
            return $asistencia ?: null;
        } catch (PDOException $e) {
            error_log("Error finding asistencia by ID: " . $e->getMessage());
            return null;
        }
    }
}

