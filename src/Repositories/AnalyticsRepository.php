<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Repositorio para consultas analíticas y estadísticas de asistencia
 * 
 * Proporciona métodos optimizados para generar reportes estadísticos
 * basados en datos de asistencias y anomalías.
 * 
 * @author Analytics Module
 * @version 1.0
 */
class AnalyticsRepository
{
    /**
     * Obtiene estadísticas de asistencia por ficha en un rango de fechas
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return array Estadísticas de asistencia
     */
    public function getAttendanceStatsByFicha(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $pdo = Connection::getInstance();
            
            $sql = "
                SELECT 
                    f.id as ficha_id,
                    f.numero_ficha,
                    f.nombre as ficha_nombre,
                    COUNT(DISTINCT fa.id_aprendiz) as total_aprendices,
                    COUNT(DISTINCT a.id) as total_registros,
                    COALESCE(SUM(CASE WHEN a.estado = 'presente' THEN 1 ELSE 0 END), 0) as total_presentes,
                    COALESCE(SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END), 0) as total_ausentes,
                    COALESCE(SUM(CASE WHEN a.estado = 'tardanza' THEN 1 ELSE 0 END), 0) as total_tardanzas,
                    COUNT(DISTINCT a.fecha) as dias_registrados
                FROM fichas f
                INNER JOIN ficha_aprendiz fa ON f.id = fa.id_ficha
                LEFT JOIN asistencias a ON fa.id_aprendiz = a.id_aprendiz 
                    AND a.id_ficha = f.id
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND a.fecha <= CURDATE()
                WHERE f.id = :ficha_id
                    AND fa.id_aprendiz IN (
                        SELECT id FROM aprendices WHERE estado = 'activo'
                    )
                GROUP BY f.id, f.numero_ficha, f.nombre
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ficha_id' => $fichaId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Return default structure when no data exists
                return [
                    'ficha_id' => $fichaId,
                    'numero_ficha' => 'N/A',
                    'ficha_nombre' => 'N/A',
                    'total_aprendices' => 0,
                    'total_registros' => 0,
                    'total_presentes' => 0,
                    'total_ausentes' => 0,
                    'total_tardanzas' => 0,
                    'dias_registrados' => 0,
                    'porcentaje_asistencia' => 0,
                    'porcentaje_ausencias' => 0,
                    'porcentaje_tardanzas' => 0
                ];
            }
            
            // Calcular porcentajes
            $totalEsperado = (int)$result['total_aprendices'] * (int)$result['dias_registrados'];
            $result['porcentaje_asistencia'] = $totalEsperado > 0 
                ? round(((int)$result['total_presentes'] / $totalEsperado) * 100, 2) 
                : 0;
            $result['porcentaje_ausencias'] = $totalEsperado > 0 
                ? round(((int)$result['total_ausentes'] / $totalEsperado) * 100, 2) 
                : 0;
            $result['porcentaje_tardanzas'] = $totalEsperado > 0 
                ? round(((int)$result['total_tardanzas'] / $totalEsperado) * 100, 2) 
                : 0;
            
            return $result;
            
        } catch (PDOException $e) {
            error_log('Error en getAttendanceStatsByFicha: ' . $e->getMessage());
            throw new RuntimeException('Error al obtener estadísticas de asistencia por ficha');
        }
    }

    /**
     * Obtiene estadísticas detalladas por aprendiz en una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return array Lista de aprendices con sus estadísticas
     */
    public function getAttendanceStatsByAprendiz(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $pdo = Connection::getInstance();
            
            $sql = "
                SELECT 
                    ap.id as aprendiz_id,
                    ap.documento,
                    ap.nombre,
                    ap.apellido,
                    COUNT(DISTINCT a.fecha) as dias_registrados,
                    COALESCE(SUM(CASE WHEN a.estado = 'presente' THEN 1 ELSE 0 END), 0) as presentes,
                    COALESCE(SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END), 0) as ausentes,
                    COALESCE(SUM(CASE WHEN a.estado = 'tardanza' THEN 1 ELSE 0 END), 0) as tardanzas,
                    AVG(CASE 
                        WHEN a.estado IN ('presente', 'tardanza') 
                        THEN TIME_TO_SEC(a.hora) 
                        ELSE NULL 
                    END) as promedio_hora_ingreso_segundos
                FROM aprendices ap
                INNER JOIN ficha_aprendiz fa ON ap.id = fa.id_aprendiz
                LEFT JOIN asistencias a ON ap.id = a.id_aprendiz 
                    AND a.id_ficha = :ficha_id1
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND a.fecha <= CURDATE()
                WHERE fa.id_ficha = :ficha_id2
                    AND ap.estado = 'activo'
                GROUP BY ap.id, ap.documento, ap.nombre, ap.apellido
                ORDER BY ap.apellido, ap.nombre
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ficha_id1' => $fichaId,
                ':ficha_id2' => $fichaId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular días hábiles en el rango
            $diasHabiles = $this->calcularDiasHabiles($fechaInicio, $fechaFin);
            
            // Procesar resultados
            foreach ($results as &$row) {
                $diasRegistrados = (int)$row['dias_registrados'];
                $presentes = (int)$row['presentes'];
                $ausentes = (int)$row['ausentes'];
                $tardanzas = (int)$row['tardanzas'];
                
                // Calcular ausencias implícitas (días sin registro = ausente)
                $diasConRegistro = $presentes + $ausentes + $tardanzas;
                $ausenciasImplicitas = max(0, $diasHabiles - $diasConRegistro);
                $ausentesFinal = $ausentes + $ausenciasImplicitas;
                
                // Actualizar el valor de ausentes para incluir ausencias implícitas
                $row['ausentes'] = $ausentesFinal;
                
                // Calcular porcentajes
                $row['porcentaje_asistencia'] = $diasHabiles > 0 
                    ? round(($presentes / $diasHabiles) * 100, 2) 
                    : 0;
                $row['porcentaje_ausencias'] = $diasHabiles > 0 
                    ? round(($ausentesFinal / $diasHabiles) * 100, 2) 
                    : 0;
                $row['porcentaje_tardanzas'] = $diasHabiles > 0 
                    ? round(($tardanzas / $diasHabiles) * 100, 2) 
                    : 0;
                
                // Convertir promedio de segundos a formato HH:MM:SS
                if ($row['promedio_hora_ingreso_segundos'] !== null) {
                    $segundos = (int)$row['promedio_hora_ingreso_segundos'];
                    $horas = floor($segundos / 3600);
                    $minutos = floor(($segundos % 3600) / 60);
                    $row['promedio_hora_ingreso'] = sprintf('%02d:%02d:00', $horas, $minutos);
                } else {
                    $row['promedio_hora_ingreso'] = 'N/A';
                }
                
                unset($row['promedio_hora_ingreso_segundos']);
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log('Error en getAttendanceStatsByAprendiz: ' . $e->getMessage());
            throw new RuntimeException('Error al obtener estadísticas por aprendiz');
        }
    }

    /**
     * Obtiene patrones de tardanzas por día de la semana
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return array Tardanzas agrupadas por día de la semana
     */
    public function getTardinessPatterns(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $pdo = Connection::getInstance();
            
            $sql = "
                SELECT 
                    DAYNAME(a.fecha) as dia_semana,
                    DAYOFWEEK(a.fecha) as dia_numero,
                    COUNT(*) as total_tardanzas,
                    COUNT(DISTINCT a.id_aprendiz) as aprendices_distintos
                FROM asistencias a
                WHERE a.id_ficha = :ficha_id
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND a.estado = 'tardanza'
                GROUP BY DAYOFWEEK(a.fecha), DAYNAME(a.fecha)
                ORDER BY dia_numero
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ficha_id' => $fichaId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Error en getTardinessPatterns: ' . $e->getMessage());
            throw new RuntimeException('Error al obtener patrones de tardanzas');
        }
    }

    /**
     * Obtiene tardanzas justificadas mediante anomalías
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return array Tardanzas justificadas
     */
    public function getJustifiedTardiness(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $pdo = Connection::getInstance();
            
            $sql = "
                SELECT 
                    ap.id as aprendiz_id,
                    ap.documento,
                    ap.nombre,
                    ap.apellido,
                    a.fecha,
                    a.hora,
                    an.tipo_anomalia,
                    an.descripcion as justificacion
                FROM asistencias a
                INNER JOIN aprendices ap ON a.id_aprendiz = ap.id
                INNER JOIN anomalias an ON a.id = an.id_asistencia
                WHERE a.id_ficha = :ficha_id
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND a.estado = 'tardanza'
                    AND an.tipo_anomalia = 'inasistencia_justificada'
                ORDER BY a.fecha DESC, ap.apellido, ap.nombre
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ficha_id' => $fichaId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Error en getJustifiedTardiness: ' . $e->getMessage());
            throw new RuntimeException('Error al obtener tardanzas justificadas');
        }
    }

    /**
     * Obtiene la media de hora de ingreso por ficha
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return array Media de hora de ingreso
     */
    public function getAverageEntryTime(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $pdo = Connection::getInstance();
            
            $sql = "
                SELECT 
                    AVG(TIME_TO_SEC(a.hora)) as promedio_segundos,
                    MIN(a.hora) as hora_minima,
                    MAX(a.hora) as hora_maxima,
                    COUNT(*) as total_registros
                FROM asistencias a
                WHERE a.id_ficha = :ficha_id
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND a.estado IN ('presente', 'tardanza')
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ficha_id' => $fichaId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['promedio_segundos'] !== null) {
                $segundos = (int)$result['promedio_segundos'];
                $horas = floor($segundos / 3600);
                $minutos = floor(($segundos % 3600) / 60);
                $result['promedio_hora'] = sprintf('%02d:%02d:00', $horas, $minutos);
            } else {
                $result['promedio_hora'] = 'N/A';
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log('Error en getAverageEntryTime: ' . $e->getMessage());
            throw new RuntimeException('Error al obtener media de hora de ingreso');
        }
    }

    /**
     * Obtiene ausencias agrupadas por día de la semana
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return array Ausencias por día de la semana
     */
    public function getAbsencesByDay(int $fichaId, string $fechaInicio, string $fechaFin): array
    {
        try {
            $pdo = Connection::getInstance();
            
            $sql = "
                SELECT 
                    DAYNAME(a.fecha) as dia_semana,
                    DAYOFWEEK(a.fecha) as dia_numero,
                    COUNT(*) as total_ausencias,
                    COUNT(DISTINCT a.id_aprendiz) as aprendices_distintos,
                    GROUP_CONCAT(DISTINCT CONCAT(ap.nombre, ' ', ap.apellido) SEPARATOR ', ') as aprendices
                FROM asistencias a
                INNER JOIN aprendices ap ON a.id_aprendiz = ap.id
                WHERE a.id_ficha = :ficha_id
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND a.estado = 'ausente'
                GROUP BY DAYOFWEEK(a.fecha), DAYNAME(a.fecha)
                ORDER BY dia_numero
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ficha_id' => $fichaId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Error en getAbsencesByDay: ' . $e->getMessage());
            throw new RuntimeException('Error al obtener ausencias por día');
        }
    }

    /**
     * Obtiene aprendices con problemas recurrentes de asistencia
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @param int $umbralAusencias Número mínimo de ausencias para considerar problemático
     * @param int $umbralTardanzas Número mínimo de tardanzas para considerar problemático
     * @return array Aprendices problemáticos
     */
    public function getProblematicStudents(
        int $fichaId, 
        string $fechaInicio, 
        string $fechaFin,
        int $umbralAusencias = 3,
        int $umbralTardanzas = 5
    ): array {
        try {
            $pdo = Connection::getInstance();
            
            // Primero, calcular días hábiles en el rango (excluyendo futuros)
            $diasHabiles = $this->calcularDiasHabilesHastaHoy($fechaInicio, $fechaFin);
            
            $sql = "
                SELECT 
                    ap.id as aprendiz_id,
                    ap.documento,
                    ap.nombre,
                    ap.apellido,
                    COALESCE(SUM(CASE WHEN a.estado = 'presente' THEN 1 ELSE 0 END), 0) as total_presentes,
                    COALESCE(SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END), 0) as total_ausencias_registradas,
                    COALESCE(SUM(CASE WHEN a.estado = 'tardanza' THEN 1 ELSE 0 END), 0) as total_tardanzas,
                    COUNT(DISTINCT a.fecha) as dias_registrados
                FROM aprendices ap
                INNER JOIN ficha_aprendiz fa ON ap.id = fa.id_aprendiz
                LEFT JOIN asistencias a ON ap.id = a.id_aprendiz 
                    AND a.id_ficha = :ficha_id
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND a.fecha <= CURDATE()
                WHERE fa.id_ficha = :ficha_id
                    AND ap.estado = 'activo'
                GROUP BY ap.id, ap.documento, ap.nombre, ap.apellido
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ficha_id' => $fichaId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar resultados para incluir ausencias implícitas
            $problematicos = [];
            foreach ($results as $row) {
                $presentes = (int)$row['total_presentes'];
                $ausentesRegistradas = (int)$row['total_ausencias_registradas'];
                $tardanzas = (int)$row['total_tardanzas'];
                
                // Calcular ausencias implícitas (días sin registro)
                $diasConRegistro = $presentes + $ausentesRegistradas + $tardanzas;
                $ausenciasImplicitas = max(0, $diasHabiles - $diasConRegistro);
                $totalAusencias = $ausentesRegistradas + $ausenciasImplicitas;
                
                // Filtrar por umbrales
                if ($totalAusencias >= $umbralAusencias || $tardanzas >= $umbralTardanzas) {
                    $problematicos[] = [
                        'aprendiz_id' => $row['aprendiz_id'],
                        'documento' => $row['documento'],
                        'nombre' => $row['nombre'],
                        'apellido' => $row['apellido'],
                        'total_ausencias' => $totalAusencias,
                        'total_tardanzas' => $tardanzas,
                        'dias_registrados' => $row['dias_registrados']
                    ];
                }
            }
            
            // Ordenar por ausencias y tardanzas
            usort($problematicos, function($a, $b) {
                if ($a['total_ausencias'] !== $b['total_ausencias']) {
                    return $b['total_ausencias'] - $a['total_ausencias'];
                }
                return $b['total_tardanzas'] - $a['total_tardanzas'];
            });
            
            return $problematicos;
            
        } catch (PDOException $e) {
            error_log('Error en getProblematicStudents: ' . $e->getMessage());
            throw new RuntimeException('Error al obtener aprendices problemáticos');
        }
    }

    /**
     * Calcula el número de días hábiles (lunes a viernes) en un rango de fechas
     * 
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return int Número de días hábiles
     */
    private function calcularDiasHabiles(string $fechaInicio, string $fechaFin): int
    {
        $inicio = new \DateTime($fechaInicio);
        $fin = new \DateTime($fechaFin);
        $fin->modify('+1 day'); // Incluir el último día
        
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($inicio, $interval, $fin);
        
        $diasHabiles = 0;
        foreach ($period as $date) {
            $diaSemana = (int)$date->format('N'); // 1 (lunes) a 7 (domingo)
            if ($diaSemana >= 1 && $diaSemana <= 5) {
                $diasHabiles++;
            }
        }
        
        return $diasHabiles;
    }
    
    /**
     * Calcula el número de días hábiles hasta hoy (excluyendo fechas futuras)
     * 
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @return int Número de días hábiles hasta hoy
     */
    private function calcularDiasHabilesHastaHoy(string $fechaInicio, string $fechaFin): int
    {
        $hoy = new \DateTime();
        $fin = new \DateTime($fechaFin);
        
        // Si la fecha fin es futura, usar hoy como límite
        if ($fin > $hoy) {
            $fechaFin = $hoy->format('Y-m-d');
        }
        
        return $this->calcularDiasHabiles($fechaInicio, $fechaFin);
    }
}
