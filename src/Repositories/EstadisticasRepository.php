<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Repositorio para consultas de estadísticas de asistencia
 * Dev: Módulo de Estadísticas - Capa de Datos
 *
 * @author Dev - EstadisticasRepository
 * @version 1.0
 */
class EstadisticasRepository
{
    /**
     * Obtiene estadísticas básicas por estado para un filtro dado
     * Query base para totales por estado
     */
    public function getTotalesPorEstado(array $filtros): array
    {
        try {
            $sql = "
                SELECT estado, COUNT(*) AS total
                FROM asistencias
                WHERE 1=1
            ";

            $params = [];
            $sql .= $this->buildWhereClause($filtros, $params);

            $sql .= " GROUP BY estado";

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getTotalesPorEstado: " . $e->getMessage());
            throw new RuntimeException('Error al obtener totales por estado: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene inasistencias por día de la semana
     */
    public function getInasistenciasPorDiaSemana(array $filtros): array
    {
        try {
            $sql = "
                SELECT
                    DAYOFWEEK(fecha) AS dia_semana,
                    COUNT(*) AS total
                FROM asistencias
                WHERE estado = 'ausente'
            ";

            $params = [];
            $sql .= $this->buildWhereClause($filtros, $params);

            $sql .= " GROUP BY dia_semana ORDER BY dia_semana";

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getInasistenciasPorDiaSemana: " . $e->getMessage());
            throw new RuntimeException('Error al obtener inasistencias por día: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene inasistencias por jornada
     */
    public function getInasistenciasPorJornada(array $filtros): array
    {
        try {
            $sql = "
                SELECT
                    CASE
                        WHEN TIME(hora) BETWEEN '06:00:00' AND '11:59:59' THEN 'mañana'
                        WHEN TIME(hora) BETWEEN '12:00:00' AND '17:59:59' THEN 'tarde'
                        WHEN TIME(hora) BETWEEN '18:00:00' AND '23:59:59' THEN 'noche'
                        ELSE 'desconocida'
                    END AS jornada,
                    COUNT(*) AS total
                FROM asistencias
                WHERE estado = 'ausente'
            ";

            $params = [];
            $sql .= $this->buildWhereClause($filtros, $params);

            $sql .= " GROUP BY jornada ORDER BY jornada";

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getInasistenciasPorJornada: " . $e->getMessage());
            throw new RuntimeException('Error al obtener inasistencias por jornada: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene inasistencias con/sin excusa
     */
    public function getInasistenciasConExcusa(array $filtros): array
    {
        try {
            $sql = "
                SELECT
                    SUM(CASE WHEN a.estado = 'ausente' AND an.id IS NULL THEN 1 ELSE 0 END) AS inasistencias_sin_excusa,
                    SUM(CASE WHEN a.estado = 'ausente' AND an.id IS NOT NULL THEN 1 ELSE 0 END) AS inasistencias_con_excusa
                FROM asistencias a
                LEFT JOIN anomalias an ON an.id_asistencia = a.id AND an.tipo = 'excusa'
                WHERE a.estado = 'ausente'
            ";

            $params = [];
            $sql .= $this->buildWhereClause($filtros, $params, 'a.');

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [
                'inasistencias_sin_excusa' => 0,
                'inasistencias_con_excusa' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error en getInasistenciasConExcusa: " . $e->getMessage());
            throw new RuntimeException('Error al obtener inasistencias con excusa: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene fechas de inasistencia para calcular frecuencia
     */
    public function getFechasInasistencia(int $idAprendiz, array $filtros): array
    {
        try {
            $sql = "
                SELECT fecha
                FROM asistencias
                WHERE estado = 'ausente'
                  AND id_aprendiz = :id_aprendiz
            ";

            $params = [':id_aprendiz' => $idAprendiz];
            $sql .= $this->buildWhereClause($filtros, $params);

            $sql .= " ORDER BY fecha";

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error en getFechasInasistencia: " . $e->getMessage());
            throw new RuntimeException('Error al obtener fechas de inasistencia: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene estadísticas por ficha
     */
    public function getEstadisticasPorFicha(int $idFicha, string $fechaDesde, string $fechaHasta): array
    {
        try {
            $sql = "
                SELECT
                    COUNT(*) as total_registros,
                    SUM(CASE WHEN estado = 'presente' THEN 1 ELSE 0 END) as presentes,
                    SUM(CASE WHEN estado = 'ausente' THEN 1 ELSE 0 END) as ausentes,
                    SUM(CASE WHEN estado = 'tardanza' THEN 1 ELSE 0 END) as tardanzas,
                    COUNT(DISTINCT id_aprendiz) as total_aprendices
                FROM asistencias
                WHERE id_ficha = :id_ficha
                  AND fecha BETWEEN :fecha_desde AND :fecha_hasta
            ";

            $stmt = Connection::prepare($sql);
            $stmt->execute([
                ':id_ficha' => $idFicha,
                ':fecha_desde' => $fechaDesde,
                ':fecha_hasta' => $fechaHasta
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [
                'total_registros' => 0,
                'presentes' => 0,
                'ausentes' => 0,
                'tardanzas' => 0,
                'total_aprendices' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error en getEstadisticasPorFicha: " . $e->getMessage());
            throw new RuntimeException('Error al obtener estadísticas por ficha: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene casos marcados como "reporte por analizar"
     */
    public function getReportesPorAnalizar(array $filtros): array
    {
        try {
            // Por ahora, implementamos una lógica básica
            // En el futuro esto podría ser un campo específico en la BD
            $sql = "
                SELECT
                    a.id,
                    a.id_aprendiz,
                    a.id_ficha,
                    a.fecha,
                    a.estado,
                    ap.nombre,
                    ap.apellido,
                    f.numero_ficha,
                    f.nombre as nombre_ficha,
                    COUNT(*) OVER (PARTITION BY a.id_aprendiz) as total_inasistencias_aprendiz
                FROM asistencias a
                INNER JOIN aprendices ap ON a.id_aprendiz = ap.id
                INNER JOIN fichas f ON a.id_ficha = f.id
                WHERE a.estado = 'ausente'
            ";

            $params = [];
            $sql .= $this->buildWhereClause($filtros, $params, 'a.');

            // Filtro adicional para casos críticos (más de 5 inasistencias)
            $sql .= " HAVING total_inasistencias_aprendiz > 5 ORDER BY total_inasistencias_aprendiz DESC, a.fecha DESC";

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getReportesPorAnalizar: " . $e->getMessage());
            throw new RuntimeException('Error al obtener reportes por analizar: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene estadísticas detalladas por aprendiz
     */
    public function getEstadisticasPorAprendiz(int $idAprendiz, array $filtros): array
    {
        try {
            $sql = "
                SELECT
                    COUNT(*) as total_asistencias,
                    SUM(CASE WHEN estado = 'presente' THEN 1 ELSE 0 END) as total_presentes,
                    SUM(CASE WHEN estado = 'ausente' THEN 1 ELSE 0 END) as total_ausentes,
                    SUM(CASE WHEN estado = 'tardanza' THEN 1 ELSE 0 END) as total_tardanzas
                FROM asistencias
                WHERE id_aprendiz = :id_aprendiz
            ";

            $params = [':id_aprendiz' => $idAprendiz];
            $sql .= $this->buildWhereClause($filtros, $params);

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [
                'total_asistencias' => 0,
                'total_presentes' => 0,
                'total_ausentes' => 0,
                'total_tardanzas' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error en getEstadisticasPorAprendiz: " . $e->getMessage());
            throw new RuntimeException('Error al obtener estadísticas por aprendiz: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene distribución de inasistencias por semana/mes
     */
    public function getInasistenciasPorPeriodo(int $idAprendiz, array $filtros): array
    {
        try {
            $sql = "
                SELECT
                    YEAR(fecha) as anio,
                    WEEK(fecha) as semana,
                    MONTH(fecha) as mes,
                    COUNT(*) as total_inasistencias
                FROM asistencias
                WHERE estado = 'ausente'
                  AND id_aprendiz = :id_aprendiz
            ";

            $params = [':id_aprendiz' => $idAprendiz];
            $sql .= $this->buildWhereClause($filtros, $params);

            $sql .= " GROUP BY anio, semana, mes ORDER BY anio, semana";

            $stmt = Connection::prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getInasistenciasPorPeriodo: " . $e->getMessage());
            throw new RuntimeException('Error al obtener inasistencias por período: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Construye la cláusula WHERE dinámicamente basada en filtros
     */
    private function buildWhereClause(array $filtros, array &$params, string $tablePrefix = ''): string
    {
        $where = '';

        if (isset($filtros['id_aprendiz']) && $filtros['id_aprendiz']) {
            $where .= " AND {$tablePrefix}id_aprendiz = :id_aprendiz";
            $params[':id_aprendiz'] = $filtros['id_aprendiz'];
        }

        if (isset($filtros['id_ficha']) && $filtros['id_ficha']) {
            $where .= " AND {$tablePrefix}id_ficha = :id_ficha";
            $params[':id_ficha'] = $filtros['id_ficha'];
        }

        if (isset($filtros['fecha_desde']) && $filtros['fecha_desde']) {
            $where .= " AND {$tablePrefix}fecha >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (isset($filtros['fecha_hasta']) && $filtros['fecha_hasta']) {
            $where .= " AND {$tablePrefix}fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        // Excluir domingos por defecto
        $where .= " AND DAYOFWEEK({$tablePrefix}fecha) != 1";

        return $where;
    }

    /**
     * Obtiene top N aprendices con más inasistencias en una ficha
     */
    public function getTopInasistentesPorFicha(int $idFicha, string $fechaDesde, string $fechaHasta, int $limit = 5): array
    {
        try {
            $sql = "
                SELECT
                    a.id_aprendiz,
                    ap.nombre,
                    ap.apellido,
                    COUNT(*) as total_inasistencias,
                    COUNT(CASE WHEN an.id IS NOT NULL THEN 1 END) as inasistencias_con_excusa
                FROM asistencias a
                INNER JOIN aprendices ap ON a.id_aprendiz = ap.id
                LEFT JOIN anomalias an ON an.id_asistencia = a.id AND an.tipo = 'excusa'
                WHERE a.id_ficha = :id_ficha
                  AND a.estado = 'ausente'
                  AND a.fecha BETWEEN :fecha_desde AND :fecha_hasta
                  AND DAYOFWEEK(a.fecha) != 1
                GROUP BY a.id_aprendiz, ap.nombre, ap.apellido
                ORDER BY total_inasistencias DESC
                LIMIT :limit
            ";

            $stmt = Connection::prepare($sql);
            $stmt->bindParam(':id_ficha', $idFicha, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_desde', $fechaDesde, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_hasta', $fechaHasta, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getTopInasistentesPorFicha: " . $e->getMessage());
            throw new RuntimeException('Error al obtener top inasistentes: ' . $e->getMessage(), 0, $e);
        }
    }
}
