<?php

namespace App\Services;

use App\Repositories\AnalyticsRepository;
use App\Repositories\AsistenciaRepository;
use App\Repositories\AnomaliaRepository;
use App\Repositories\FichaRepository;
use RuntimeException;

/**
 * Servicio de lógica de negocio para analítica y reportes
 * 
 * Coordina la generación de reportes estadísticos combinando datos
 * de múltiples repositorios y aplicando lógica de negocio.
 * 
 * @author Analytics Module
 * @version 1.0
 */
class AnalyticsService
{
    private AnalyticsRepository $analyticsRepository;
    private AsistenciaRepository $asistenciaRepository;
    private AnomaliaRepository $anomaliaRepository;
    private FichaRepository $fichaRepository;

    public function __construct(
        AnalyticsRepository $analyticsRepository,
        AsistenciaRepository $asistenciaRepository,
        AnomaliaRepository $anomaliaRepository,
        FichaRepository $fichaRepository
    ) {
        $this->analyticsRepository = $analyticsRepository;
        $this->asistenciaRepository = $asistenciaRepository;
        $this->anomaliaRepository = $anomaliaRepository;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Genera datos para reporte semanal de una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @param string|null $fechaInicio Fecha de inicio (si es null, usa última semana)
     * @return array Datos del reporte
     */
    public function generateWeeklyReport(int $fichaId, ?string $fechaInicio = null): array
    {
        // Calcular rango de fechas (última semana si no se especifica)
        if ($fechaInicio === null) {
            $fechaFin = date('Y-m-d');
            $fechaInicio = date('Y-m-d', strtotime('-7 days'));
        } else {
            $fechaFin = date('Y-m-d', strtotime($fechaInicio . ' +6 days'));
        }

        return $this->generateReport($fichaId, $fechaInicio, $fechaFin, 'semanal');
    }

    /**
     * Genera datos para reporte mensual de una ficha
     * 
     * @param int $fichaId ID de la ficha
     * @param int|null $mes Mes (1-12, si es null usa mes actual)
     * @param int|null $año Año (si es null usa año actual)
     * @return array Datos del reporte
     */
    public function generateMonthlyReport(int $fichaId, ?int $mes = null, ?int $año = null): array
    {
        // Usar mes y año actual si no se especifican
        if ($mes === null) {
            $mes = (int)date('n');
        }
        if ($año === null) {
            $año = (int)date('Y');
        }

        // Validar mes
        if ($mes < 1 || $mes > 12) {
            throw new RuntimeException('Mes inválido. Debe estar entre 1 y 12.');
        }

        // Calcular primer y último día del mes
        $fechaInicio = sprintf('%04d-%02d-01', $año, $mes);
        $ultimoDia = date('t', strtotime($fechaInicio)); // Número de días en el mes
        $fechaFin = sprintf('%04d-%02d-%02d', $año, $mes, $ultimoDia);

        return $this->generateReport($fichaId, $fechaInicio, $fechaFin, 'mensual');
    }

    /**
     * Genera reporte completo con todas las estadísticas
     * 
     * @param int $fichaId ID de la ficha
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @param string $tipo Tipo de reporte ('semanal' o 'mensual')
     * @return array Datos completos del reporte
     */
    private function generateReport(int $fichaId, string $fechaInicio, string $fechaFin, string $tipo): array
    {
        // Validar que la ficha existe
        $ficha = $this->fichaRepository->findById($fichaId);
        if (!$ficha) {
            throw new RuntimeException('Ficha no encontrada');
        }

        // Obtener estadísticas generales
        try {
            $statsGenerales = $this->analyticsRepository->getAttendanceStatsByFicha(
                $fichaId, 
                $fechaInicio, 
                $fechaFin
            );
        } catch (\Exception $e) {
            error_log('Error en getAttendanceStatsByFicha: ' . $e->getMessage());
            $statsGenerales = [
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

        // Obtener estadísticas por aprendiz
        try {
            $statsPorAprendiz = $this->analyticsRepository->getAttendanceStatsByAprendiz(
                $fichaId, 
                $fechaInicio, 
                $fechaFin
            );
        } catch (\Exception $e) {
            error_log('Error en getAttendanceStatsByAprendiz: ' . $e->getMessage());
            $statsPorAprendiz = [];
        }

        // Obtener patrones de tardanzas
        try {
            $patronesTardanzas = $this->analyticsRepository->getTardinessPatterns(
                $fichaId, 
                $fechaInicio, 
                $fechaFin
            );
        } catch (\Exception $e) {
            error_log('Error en getTardinessPatterns: ' . $e->getMessage());
            $patronesTardanzas = [];
        }

        // Obtener tardanzas justificadas
        try {
            $tardanzasJustificadas = $this->analyticsRepository->getJustifiedTardiness(
                $fichaId, 
                $fechaInicio, 
                $fechaFin
            );
        } catch (\Exception $e) {
            error_log('Error en getJustifiedTardiness: ' . $e->getMessage());
            $tardanzasJustificadas = [];
        }

        // Obtener media de hora de ingreso
        try {
            $mediaHoraIngreso = $this->analyticsRepository->getAverageEntryTime(
                $fichaId, 
                $fechaInicio, 
                $fechaFin
            );
        } catch (\Exception $e) {
            error_log('Error en getAverageEntryTime: ' . $e->getMessage());
            $mediaHoraIngreso = [
                'promedio_hora' => 'N/A',
                'hora_minima' => 'N/A',
                'hora_maxima' => 'N/A',
                'total_registros' => 0
            ];
        }

        // Obtener ausencias por día
        try {
            $ausenciasPorDia = $this->analyticsRepository->getAbsencesByDay(
                $fichaId, 
                $fechaInicio, 
                $fechaFin
            );
        } catch (\Exception $e) {
            error_log('Error en getAbsencesByDay: ' . $e->getMessage());
            $ausenciasPorDia = [];
        }

        // Obtener aprendices problemáticos
        try {
            $aprendicesProblematicos = $this->analyticsRepository->getProblematicStudents(
                $fichaId, 
                $fechaInicio, 
                $fechaFin,
                3, // Umbral de ausencias
                5  // Umbral de tardanzas
            );
        } catch (\Exception $e) {
            error_log('Error en getProblematicStudents: ' . $e->getMessage());
            $aprendicesProblematicos = [];
        }

        // Construir respuesta
        return [
            'metadata' => [
                'ficha_id' => $fichaId,
                'numero_ficha' => $ficha['numero_ficha'],
                'nombre_ficha' => $ficha['nombre'],
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo_reporte' => $tipo,
                'fecha_generacion' => date('Y-m-d H:i:s')
            ],
            'resumen_general' => $statsGenerales,
            'estadisticas_aprendices' => $statsPorAprendiz,
            'patrones_tardanzas' => $patronesTardanzas,
            'tardanzas_justificadas' => $tardanzasJustificadas,
            'media_hora_ingreso' => $mediaHoraIngreso,
            'ausencias_por_dia' => $ausenciasPorDia,
            'aprendices_problematicos' => $aprendicesProblematicos
        ];
    }

    /**
     * Obtiene todas las fichas disponibles en el sistema
     * 
     * @return array Lista de fichas activas
     */
    public function getFichasDisponibles(): array
    {
        try {
            return $this->fichaRepository->findAll();
        } catch (\Exception $e) {
            error_log('Error al obtener fichas disponibles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula el porcentaje de asistencia basado en estadísticas
     * 
     * @param array $stats Estadísticas de asistencia
     * @return float Porcentaje de asistencia
     */
    public function calculateAttendancePercentage(array $stats): float
    {
        if (!isset($stats['total_presentes'], $stats['total_aprendices'], $stats['dias_registrados'])) {
            return 0.0;
        }

        $totalEsperado = (int)$stats['total_aprendices'] * (int)$stats['dias_registrados'];
        
        if ($totalEsperado === 0) {
            return 0.0;
        }

        return round(((int)$stats['total_presentes'] / $totalEsperado) * 100, 2);
    }

    /**
     * Identifica aprendices con problemas de asistencia
     * 
     * @param array $statsAprendices Estadísticas por aprendiz
     * @param float $umbralAsistencia Porcentaje mínimo de asistencia aceptable
     * @return array Aprendices con problemas
     */
    public function identifyProblematicStudents(array $statsAprendices, float $umbralAsistencia = 80.0): array
    {
        $problematicos = [];

        foreach ($statsAprendices as $aprendiz) {
            $porcentajeAsistencia = (float)($aprendiz['porcentaje_asistencia'] ?? 0);
            $tardanzas = (int)($aprendiz['tardanzas'] ?? 0);

            if ($porcentajeAsistencia < $umbralAsistencia || $tardanzas >= 5) {
                $problematicos[] = [
                    'aprendiz_id' => $aprendiz['aprendiz_id'],
                    'documento' => $aprendiz['documento'],
                    'nombre_completo' => $aprendiz['nombre'] . ' ' . $aprendiz['apellido'],
                    'porcentaje_asistencia' => $porcentajeAsistencia,
                    'ausencias' => $aprendiz['ausentes'] ?? 0,
                    'tardanzas' => $tardanzas,
                    'motivo' => $this->determinarMotivoProblema($porcentajeAsistencia, $tardanzas)
                ];
            }
        }

        return $problematicos;
    }

    /**
     * Determina el motivo principal del problema de asistencia
     * 
     * @param float $porcentajeAsistencia Porcentaje de asistencia
     * @param int $tardanzas Número de tardanzas
     * @return string Motivo del problema
     */
    private function determinarMotivoProblema(float $porcentajeAsistencia, int $tardanzas): string
    {
        if ($porcentajeAsistencia < 70.0) {
            return 'Asistencia crítica (< 70%)';
        } elseif ($porcentajeAsistencia < 80.0) {
            return 'Asistencia baja (< 80%)';
        } elseif ($tardanzas >= 10) {
            return 'Tardanzas excesivas (≥ 10)';
        } elseif ($tardanzas >= 5) {
            return 'Tardanzas frecuentes (≥ 5)';
        }

        return 'Requiere atención';
    }

    /**
     * Formatea los datos del reporte para exportación a Excel
     * 
     * @param array $reportData Datos del reporte
     * @return array Datos formateados para Excel
     */
    public function formatForExcel(array $reportData): array
    {
        $rows = [];

        // Hoja 1: Resumen General
        $rows['resumen'] = [
            'headers' => ['Métrica', 'Valor'],
            'data' => [
                ['Ficha', $reportData['metadata']['numero_ficha'] . ' - ' . $reportData['metadata']['nombre_ficha']],
                ['Período', $reportData['metadata']['fecha_inicio'] . ' a ' . $reportData['metadata']['fecha_fin']],
                ['Tipo de Reporte', ucfirst($reportData['metadata']['tipo_reporte'])],
                ['Total Aprendices', $reportData['resumen_general']['total_aprendices'] ?? 0],
                ['Días Registrados', $reportData['resumen_general']['dias_registrados'] ?? 0],
                ['% Asistencia', ($reportData['resumen_general']['porcentaje_asistencia'] ?? 0) . '%'],
                ['% Ausencias', ($reportData['resumen_general']['porcentaje_ausencias'] ?? 0) . '%'],
                ['% Tardanzas', ($reportData['resumen_general']['porcentaje_tardanzas'] ?? 0) . '%'],
                ['Media Hora Ingreso', $reportData['media_hora_ingreso']['promedio_hora'] ?? 'N/A']
            ]
        ];

        // Hoja 2: Estadísticas por Aprendiz
        $rows['aprendices'] = [
            'headers' => ['Documento', 'Nombre', 'Apellido', 'Presentes', 'Ausentes', 'Tardanzas', '% Asistencia', 'Promedio Hora'],
            'data' => array_map(function($aprendiz) {
                return [
                    $aprendiz['documento'],
                    $aprendiz['nombre'],
                    $aprendiz['apellido'],
                    $aprendiz['presentes'],
                    $aprendiz['ausentes'],
                    $aprendiz['tardanzas'],
                    $aprendiz['porcentaje_asistencia'] . '%',
                    $aprendiz['promedio_hora_ingreso']
                ];
            }, $reportData['estadisticas_aprendices'])
        ];

        // Hoja 3: Aprendices Problemáticos
        if (!empty($reportData['aprendices_problematicos'])) {
            $rows['problematicos'] = [
                'headers' => ['Documento', 'Nombre', 'Apellido', 'Ausencias', 'Tardanzas'],
                'data' => array_map(function($aprendiz) {
                    return [
                        $aprendiz['documento'],
                        $aprendiz['nombre'],
                        $aprendiz['apellido'],
                        $aprendiz['total_ausencias'],
                        $aprendiz['total_tardanzas']
                    ];
                }, $reportData['aprendices_problematicos'])
            ];
        }

        // Hoja 4: Patrones de Tardanzas
        if (!empty($reportData['patrones_tardanzas'])) {
            $rows['patrones_tardanzas'] = [
                'headers' => ['Día de la Semana', 'Total Tardanzas', 'Aprendices Distintos'],
                'data' => array_map(function($patron) {
                    return [
                        $patron['dia_semana'],
                        $patron['total_tardanzas'],
                        $patron['aprendices_distintos']
                    ];
                }, $reportData['patrones_tardanzas'])
            ];
        }

        return $rows;
    }
}
