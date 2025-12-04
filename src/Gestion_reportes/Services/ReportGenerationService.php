<?php

namespace App\Gestion_reportes\Services;

use App\Repositories\AsistenciaRepository;
use App\Repositories\FichaRepository;
use App\Repositories\UserRepository;
use App\Repositories\InstructorFichaRepository;
use App\Database\Connection;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Servicio de generación de reportes de asistencia.
 *
 * - Aplica reglas de negocio:
 *   - Incluye TODOS los aprendices de la ficha
 *   - Marca como "Ausente" a quienes no tengan registro
 * - Calcula contadores (presentes, ausentes, tardanzas)
 * - Registra historial de exportaciones
 */
class ReportGenerationService
{
    private AsistenciaRepository $asistenciaRepository;
    private FichaRepository $fichaRepository;
    private UserRepository $userRepository;
    private InstructorFichaRepository $instructorFichaRepository;

    public function __construct(
        AsistenciaRepository $asistenciaRepository,
        FichaRepository $fichaRepository,
        UserRepository $userRepository,
        InstructorFichaRepository $instructorFichaRepository
    ) {
        $this->asistenciaRepository = $asistenciaRepository;
        $this->fichaRepository = $fichaRepository;
        $this->userRepository = $userRepository;
        $this->instructorFichaRepository = $instructorFichaRepository;
    }

    /**
     * Obtiene fichas disponibles para un instructor:
     * - Fichas asignadas directamente
     * - Fichas donde haya registrado asistencia previamente
     */
    public function getFichasDisponiblesParaInstructor(int $instructorId): array
    {
        $fichasAsignadas = $this->instructorFichaRepository->findFichasByInstructor($instructorId, true);
        $fichasPorAsistencia = $this->getFichasPorAsistencias($instructorId);

        // Unificar por ID
        $map = [];
        foreach ([$fichasAsignadas, $fichasPorAsistencia] as $lista) {
            foreach ($lista as $ficha) {
                $map[$ficha['id']] = $ficha;
            }
        }

        return array_values($map);
    }

    /**
     * Genera datos del reporte de asistencia para una ficha y fecha.
     *
     * @return array [ 'rows' => array, 'meta' => array, 'stats' => array ]
     */
    public function generarDatosReporte(int $fichaId, string $fecha, int $instructorId): array
    {
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            throw new RuntimeException('La ficha seleccionada no existe.');
        }

        $instructor = $this->userRepository->findById($instructorId);
        if (!$instructor || $instructor['rol'] !== 'instructor') {
            throw new RuntimeException('Solo instructores pueden generar este reporte.');
        }

        // Respetar vinculación: verificar que el instructor tenga acceso a la ficha
        $fichasDisponibles = $this->getFichasDisponiblesParaInstructor($instructorId);
        $idsDisponibles = array_column($fichasDisponibles, 'id');
        if (!in_array($fichaId, $idsDisponibles, true)) {
            throw new RuntimeException('No tiene permisos para exportar reportes de esta ficha.');
        }

        $aprendices = $this->asistenciaRepository
            ->getAprendicesPorFichaConAsistenciaDelDia($fichaId, $fecha);

        if (empty($aprendices)) {
            throw new RuntimeException('No hay aprendices activos en la ficha seleccionada para esta fecha.');
        }

        $fechaReporte = date('Y-m-d H:i:s');
        $nombreInstructor = $instructor['nombre'];
        $nombreFicha = $ficha['numero_ficha'] . ' - ' . $ficha['nombre'];

        $rows = [];
        $stats = [
            'total_aprendices' => 0,
            'presentes' => 0,
            'ausentes' => 0,
            'tardanzas' => 0,
        ];

        foreach ($aprendices as $ap) {
            $stats['total_aprendices']++;

            $estado = strtolower((string) ($ap['asistencia_estado'] ?? ''));
            $horaIngreso = $ap['asistencia_hora'] ?? null;

            if ($estado === '') {
                // Regla crítica: sin registro => Ausente
                $estado = 'ausente';
                $horaIngreso = null;
            }

            // Normalizar estado a etiquetas requeridas
            $estadoNormalizado = match ($estado) {
                'presente' => 'Presente',
                'tardanza' => 'Tardanza',
                'ausente' => 'Ausente',
                default => 'Ausente',
            };

            switch ($estadoNormalizado) {
                case 'Presente':
                    $stats['presentes']++;
                    break;
                case 'Tardanza':
                    $stats['tardanzas']++;
                    break;
                case 'Ausente':
                    $stats['ausentes']++;
                    break;
            }

            $rows[] = [
                'documento' => $ap['documento'],
                'nombre_completo' => trim(($ap['nombre'] ?? '') . ' ' . ($ap['apellido'] ?? '')),
                'hora_ingreso' => $horaIngreso,
                'estado_asistencia' => $estadoNormalizado,
                'ficha' => $nombreFicha,
                'fecha_reporte' => $fecha,
                'instructor' => $nombreInstructor,
            ];
        }

        $meta = [
            'Ficha' => $nombreFicha,
            'Fecha del reporte' => $fecha,
            'Instructor' => $nombreInstructor,
            'Generado en' => $fechaReporte,
        ];

        return [
            'rows' => $rows,
            'meta' => $meta,
            'stats' => $stats,
        ];
    }

    /**
     * Registra en la tabla de historial de exportaciones.
     */
    public function registrarHistorialExportacion(
        int $instructorId,
        int $fichaId,
        string $fecha,
        string $fileName,
        array $stats
    ): void {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO historial_exportaciones 
                 (instructor_id, ficha_id, fecha_reporte, nombre_archivo, total_aprendices, presentes, ausentes, tardanzas, created_at)
                 VALUES (:instructor_id, :ficha_id, :fecha_reporte, :nombre_archivo, :total_aprendices, :presentes, :ausentes, :tardanzas, NOW())'
            );

            $stmt->execute([
                'instructor_id' => $instructorId,
                'ficha_id' => $fichaId,
                'fecha_reporte' => $fecha,
                'nombre_archivo' => $fileName,
                'total_aprendices' => $stats['total_aprendices'] ?? 0,
                'presentes' => $stats['presentes'] ?? 0,
                'ausentes' => $stats['ausentes'] ?? 0,
                'tardanzas' => $stats['tardanzas'] ?? 0,
            ]);
        } catch (PDOException $e) {
            error_log('Error registrando historial_exportaciones: ' . $e->getMessage());
            // No lanzamos excepción para no romper la descarga del archivo:
            // se prioriza que el usuario obtenga el reporte.
        }
    }

    /**
     * Obtiene historial de exportaciones para un instructor.
     */
    public function getHistorialExportaciones(int $instructorId, int $limit = 20): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    h.id,
                    h.fecha_reporte,
                    h.nombre_archivo,
                    h.total_aprendices,
                    h.presentes,
                    h.ausentes,
                    h.tardanzas,
                    h.created_at,
                    f.numero_ficha,
                    f.nombre as nombre_ficha
                 FROM historial_exportaciones h
                 INNER JOIN fichas f ON h.ficha_id = f.id
                 WHERE h.instructor_id = :instructor_id
                 ORDER BY h.created_at DESC
                 LIMIT :limit'
            );

            $stmt->bindValue(':instructor_id', $instructorId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error obteniendo historial_exportaciones: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fichas donde el instructor haya registrado asistencia previamente.
     */
    private function getFichasPorAsistencias(int $instructorId): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT DISTINCT 
                    f.id,
                    f.numero_ficha,
                    f.nombre,
                    f.jornada,
                    f.estado
                 FROM asistencias a
                 INNER JOIN fichas f ON a.id_ficha = f.id
                 WHERE a.registrado_por = :instructor_id
                 ORDER BY f.numero_ficha ASC'
            );

            $stmt->execute(['instructor_id' => $instructorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error obteniendo fichas por asistencias: ' . $e->getMessage());
            return [];
        }
    }
}


