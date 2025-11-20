<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Repositorio optimizado para la entidad Asistencia
 * Sprint 4 - Registro Manual de Asistencia
 * Dev 1: AsistenciaRepository Optimizado
 * 
 * @author Dev 1 - AsistenciaRepository Optimizado
 * @version 2.0
 */
class AsistenciaRepository
{
    /**
     * Obtiene aprendices de una ficha específica con su estado de asistencia del día actual
     * Query optimizado con JOIN eficiente y validaciones de estado activo
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fecha Fecha en formato Y-m-d (por defecto hoy)
     * @return array Array estructurado con datos completos del aprendiz y estado de asistencia
     * @throws PDOException Si hay error en la consulta
     */
    public function getAprendicesPorFichaConAsistenciaDelDia(int $fichaId, string $fecha = null): array
    {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }

        try {
            $stmt = Connection::prepare(
                'SELECT 
                    ap.id as id_aprendiz,
                    ap.documento,
                    ap.nombre,
                    ap.apellido,
                    CONCAT(ap.apellido, ", ", ap.nombre) as nombre_completo,
                    ap.codigo_carnet,
                    ap.estado as estado_aprendiz,
                    ap.created_at as fecha_matricula,
                    fa.fecha_vinculacion,
                    u.nombre as usuario_registro,
                    u.rol as rol_usuario,
                    -- Datos de asistencia (NULL si no existe registro)
                    a.id as asistencia_id,
                    a.estado as asistencia_estado,
                    a.hora as asistencia_hora,
                    a.observaciones,
                    a.created_at as fecha_registro_asistencia,
                    ur.nombre as registrado_por_nombre,
                    ur.rol as registrado_por_rol
                 FROM aprendices ap
                 INNER JOIN ficha_aprendiz fa ON ap.id = fa.id_aprendiz
                 INNER JOIN usuarios u ON u.id = 1 -- Usuario sistema para validación
                 LEFT JOIN asistencias a ON ap.id = a.id_aprendiz 
                     AND a.id_ficha = :id_ficha 
                     AND a.fecha = :fecha
                 LEFT JOIN usuarios ur ON a.registrado_por = ur.id
                 WHERE fa.id_ficha = :id_ficha_2
                     AND ap.estado = "activo"
                     AND fa.fecha_vinculacion <= :fecha_limite
                 ORDER BY ap.apellido ASC, ap.nombre ASC'
            );

            $stmt->execute([
                'id_ficha' => $fichaId,
                'id_ficha_2' => $fichaId,
                'fecha' => $fecha,
                'fecha_limite' => $fecha
            ]);

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log de performance en desarrollo
            if (defined('APP_ENV') && APP_ENV === 'local') {
                error_log("AsistenciaRepository::getAprendicesPorFichaConAsistenciaDelDia - Ficha: {$fichaId}, Fecha: {$fecha}, Resultados: " . count($resultados));
            }

            return $resultados;
        } catch (PDOException $e) {
            error_log("Error en getAprendicesPorFichaConAsistenciaDelDia: " . $e->getMessage());
            throw new RuntimeException('Error al obtener aprendices con asistencia: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Registra asistencia de un aprendiz con validaciones completas
     * Prepared statements optimizados y manejo de transacciones
     * 
     * @param array $data Datos de la asistencia
     * @return int ID del registro creado
     * @throws RuntimeException Si hay error de duplicado o validación
     */
    public function registrarAsistencia(array $data): int
    {
        // Validaciones de entrada
        $this->validarDatosAsistencia($data);

        Connection::beginTransaction();
        
        try {
            // Validar que no exista registro duplicado (constraint UNIQUE)
            if ($this->existeRegistroAsistencia($data['id_aprendiz'], $data['id_ficha'], $data['fecha'])) {
                throw new RuntimeException('Ya existe un registro de asistencia para este aprendiz en esta fecha');
            }

            // Validar que el aprendiz esté matriculado activamente en la ficha
            if (!$this->validarAprendizMatriculado($data['id_aprendiz'], $data['id_ficha'])) {
                throw new RuntimeException('El aprendiz no está matriculado activamente en esta ficha');
            }

            // Insertar registro de asistencia
            $stmt = Connection::prepare(
                'INSERT INTO asistencias 
                 (id_aprendiz, id_ficha, fecha, hora, estado, registrado_por, observaciones) 
                 VALUES (:id_aprendiz, :id_ficha, :fecha, :hora, :estado, :registrado_por, :observaciones)'
            );

            $stmt->execute([
                'id_aprendiz' => $data['id_aprendiz'],
                'id_ficha' => $data['id_ficha'],
                'fecha' => $data['fecha'],
                'hora' => $data['hora'] ?? date('H:i:s'),
                'estado' => $data['estado'],
                'registrado_por' => $data['registrado_por'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            $asistenciaId = (int) Connection::lastInsertId();
            
            Connection::commit();
            
            // Log de éxito en desarrollo
            if (defined('APP_ENV') && APP_ENV === 'local') {
                error_log("Asistencia registrada exitosamente - ID: {$asistenciaId}, Aprendiz: {$data['id_aprendiz']}, Estado: {$data['estado']}");
            }

            return $asistenciaId;
        } catch (PDOException $e) {
            Connection::rollBack();
            
            // Si es error de duplicado (constraint violation)
            if ($e->getCode() == 23000) {
                throw new RuntimeException('Ya existe un registro de asistencia para este aprendiz en esta fecha');
            }
            
            error_log("Error registrando asistencia: " . $e->getMessage());
            throw new RuntimeException('Error al registrar asistencia: ' . $e->getMessage(), 0, $e);
        } catch (RuntimeException $e) {
            Connection::rollBack();
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

    /**
     * Valida los datos de entrada para registro de asistencia
     * 
     * @param array $data Datos a validar
     * @throws RuntimeException Si los datos son inválidos
     */
    private function validarDatosAsistencia(array $data): void
    {
        $camposRequeridos = ['id_aprendiz', 'id_ficha', 'fecha', 'estado', 'registrado_por'];
        
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || empty($data[$campo])) {
                throw new RuntimeException("El campo '{$campo}' es requerido");
            }
        }

        // Validar tipos de datos
        if (!is_int($data['id_aprendiz']) || $data['id_aprendiz'] <= 0) {
            throw new RuntimeException('ID de aprendiz debe ser un entero positivo');
        }

        if (!is_int($data['id_ficha']) || $data['id_ficha'] <= 0) {
            throw new RuntimeException('ID de ficha debe ser un entero positivo');
        }

        if (!is_int($data['registrado_por']) || $data['registrado_por'] <= 0) {
            throw new RuntimeException('ID de usuario que registra debe ser un entero positivo');
        }

        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
            throw new RuntimeException('Fecha debe estar en formato Y-m-d');
        }

        // Validar estado
        $estadosValidos = ['presente', 'ausente', 'tardanza'];
        if (!in_array($data['estado'], $estadosValidos)) {
            throw new RuntimeException('Estado debe ser uno de: ' . implode(', ', $estadosValidos));
        }

        // Validar hora si se proporciona
        if (isset($data['hora']) && !empty($data['hora'])) {
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $data['hora'])) {
                throw new RuntimeException('Hora debe estar en formato H:i:s');
            }
        }
    }

    /**
     * Verifica si ya existe un registro de asistencia para evitar duplicados
     * 
     * @param int $aprendizId ID del aprendiz
     * @param int $fichaId ID de la ficha
     * @param string $fecha Fecha en formato Y-m-d
     * @return bool True si existe, false si no
     */
    private function existeRegistroAsistencia(int $aprendizId, int $fichaId, string $fecha): bool
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
            error_log("Error verificando registro existente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida que el aprendiz esté matriculado activamente en la ficha
     * 
     * @param int $aprendizId ID del aprendiz
     * @param int $fichaId ID de la ficha
     * @return bool True si está matriculado y activo, false si no
     */
    private function validarAprendizMatriculado(int $aprendizId, int $fichaId): bool
    {
        try {
            $stmt = Connection::prepare(
                'SELECT COUNT(*) as total
                 FROM ficha_aprendiz fa
                 INNER JOIN aprendices ap ON fa.id_aprendiz = ap.id
                 INNER JOIN fichas f ON fa.id_ficha = f.id
                 WHERE fa.id_aprendiz = :id_aprendiz
                   AND fa.id_ficha = :id_ficha
                   AND ap.estado = "activo"
                   AND f.estado = "activa"'
            );

            $stmt->execute([
                'id_aprendiz' => $aprendizId,
                'id_ficha' => $fichaId,
            ]);

            $result = $stmt->fetch();
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error validando matrícula de aprendiz: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Método legacy para compatibilidad - usar registrarAsistencia() en su lugar
     * @deprecated Usar registrarAsistencia() para mejor validación y transacciones
     */
    public function create(array $data): int
    {
        return $this->registrarAsistencia($data);
    }
}

