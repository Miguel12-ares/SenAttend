<?php

namespace App\Services;

use App\Repositories\EstadisticasRepository;
use DateTime;
use DateInterval;
use Exception;

/**
 * Servicio de lógica de negocio para estadísticas de asistencia
 * Dev: Módulo de Estadísticas - Capa de Lógica
 *
 * @author Dev - EstadisticasService
 * @version 1.0
 */
class EstadisticasService
{
    private EstadisticasRepository $estadisticasRepository;

    // Configuración de reglas de negocio
    private const CONFIG_TARDANZA = [
        'mañana' => ['inicio' => '06:00', 'limite' => '06:20'],
        'tarde' => ['inicio' => '12:00', 'limite' => '12:20'],
        'noche' => ['inicio' => '18:00', 'limite' => '18:20']
    ];

    // Configuración para detectar "reporte por analizar"
    private const CONFIG_REPORTE = [
        'concentracion_dia_minima' => 40, // porcentaje mínimo en un día
        'frecuencia_maxima_dias' => 3,    // días máximo entre inasistencias
        'caida_porcentaje_maxima' => 15   // puntos de caída máxima
    ];

    public function __construct(EstadisticasRepository $estadisticasRepository)
    {
        $this->estadisticasRepository = $estadisticasRepository;
    }

    /**
     * Obtiene estadísticas completas para uno o varios aprendices
     */
    public function getEstadisticasAprendiz(array $filtros): array
    {
        try {
            $aprendicesIds = $filtros['id_aprendiz'] ?? null;
            if (!$aprendicesIds) {
                throw new Exception('Se requiere especificar al menos un ID de aprendiz');
            }

            // Si es un solo aprendiz, convertir a array
            if (!is_array($aprendicesIds)) {
                $aprendicesIds = [$aprendicesIds];
            }

            $resultados = [];

            foreach ($aprendicesIds as $idAprendiz) {
                $filtrosAprendiz = array_merge($filtros, ['id_aprendiz' => $idAprendiz]);

                // Obtener datos crudos
                $totales = $this->estadisticasRepository->getEstadisticasPorAprendiz($idAprendiz, $filtrosAprendiz);
                $excusas = $this->estadisticasRepository->getInasistenciasConExcusa($filtrosAprendiz);
                $fechasInasistencia = $this->estadisticasRepository->getFechasInasistencia($idAprendiz, $filtrosAprendiz);
                $inasistenciasPorPeriodo = $this->estadisticasRepository->getInasistenciasPorPeriodo($idAprendiz, $filtrosAprendiz);

                // Calcular estadísticas
                $estadisticas = $this->calcularEstadisticasAprendiz(
                    $totales,
                    $excusas,
                    $fechasInasistencia,
                    $inasistenciasPorPeriodo,
                    $filtrosAprendiz
                );

                $resultados[] = $estadisticas;
            }

            // Si solo se pidió uno, devolver el objeto directo
            return count($aprendicesIds) === 1 ? $resultados[0] : $resultados;

        } catch (Exception $e) {
            error_log("Error en getEstadisticasAprendiz: " . $e->getMessage());
            throw new Exception('Error al obtener estadísticas de aprendiz: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene estadísticas agregadas por ficha
     */
    public function getEstadisticasFicha(int $idFicha, string $fechaDesde, string $fechaHasta): array
    {
        try {
            // Datos básicos de la ficha
            $datosFicha = $this->estadisticasRepository->getEstadisticasPorFicha($idFicha, $fechaDesde, $fechaHasta);

            // Distribución por día de semana
            $filtros = [
                'id_ficha' => $idFicha,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ];
            $inasistenciasPorDia = $this->estadisticasRepository->getInasistenciasPorDiaSemana($filtros);
            $inasistenciasPorJornada = $this->estadisticasRepository->getInasistenciasPorJornada($filtros);

            // Top aprendices con más inasistencias
            $topInasistentes = $this->estadisticasRepository->getTopInasistentesPorFicha($idFicha, $fechaDesde, $fechaHasta);

            // Calcular porcentajes
            $porcentajes = $this->calcularPorcentajes($datosFicha);

            // Flags de reporte
            $flags = $this->detectarFlagsReporte($datosFicha, $inasistenciasPorDia);

            return [
                'id_ficha' => $idFicha,
                'totales' => [
                    'total_registros' => $datosFicha['total_registros'],
                    'presentes' => $datosFicha['presentes'],
                    'ausentes' => $datosFicha['ausentes'],
                    'tardanzas' => $datosFicha['tardanzas']
                ],
                'porcentajes' => $porcentajes,
                'distribucion_dia_semana' => $this->formatearDistribucionDiaSemana($inasistenciasPorDia),
                'distribucion_jornada' => $inasistenciasPorJornada,
                'top_inasistentes' => $topInasistentes,
                'flags' => $flags
            ];

        } catch (Exception $e) {
            error_log("Error en getEstadisticasFicha: " . $e->getMessage());
            throw new Exception('Error al obtener estadísticas de ficha: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene casos marcados como "reporte por analizar"
     */
    public function getReportesPorAnalizar(array $filtros): array
    {
        try {
            return $this->estadisticasRepository->getReportesPorAnalizar($filtros);
        } catch (Exception $e) {
            error_log("Error en getReportesPorAnalizar: " . $e->getMessage());
            throw new Exception('Error al obtener reportes por analizar: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Exporta datos tabulares para CSV
     */
    public function exportarDatos(array $filtros, string $tipo): array
    {
        try {
            if ($tipo === 'aprendiz') {
                return $this->exportarDatosAprendiz($filtros);
            } elseif ($tipo === 'ficha') {
                return $this->exportarDatosFicha($filtros);
            } else {
                throw new Exception('Tipo de exportación no válido');
            }
        } catch (Exception $e) {
            error_log("Error en exportarDatos: " . $e->getMessage());
            throw new Exception('Error al exportar datos: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Calcula estadísticas completas para un aprendiz
     */
    private function calcularEstadisticasAprendiz(
        array $totales,
        array $excusas,
        array $fechasInasistencia,
        array $inasistenciasPorPeriodo,
        array $filtros
    ): array {
        $totalAsistencias = $totales['total_asistencias'];
        $totalAusentes = $totales['total_ausentes'];
        $totalTardanzas = $totales['total_tardanzas'];

        // Calcular porcentajes
        $porcentajes = $this->calcularPorcentajes([
            'presentes' => $totales['total_presentes'],
            'ausentes' => $totalAusentes,
            'tardanzas' => $totalTardanzas,
            'total_registros' => $totalAsistencias
        ]);

        // Calcular frecuencia de inasistencias
        $frecuencia = $this->calcularFrecuenciaInasistencias($fechasInasistencia);

        // Distribución por período
        $distribucionPeriodo = $this->calcularDistribucionPorPeriodo($inasistenciasPorPeriodo);

        // Distribución por día de semana
        $filtrosDiaSemana = array_merge($filtros, ['estado' => 'ausente']);
        $inasistenciasDiaSemana = $this->estadisticasRepository->getInasistenciasPorDiaSemana($filtrosDiaSemana);

        // Distribución por jornada
        $inasistenciasJornada = $this->estadisticasRepository->getInasistenciasPorJornada($filtrosDiaSemana);

        // Detectar flags de reporte
        $flags = $this->detectarFlagsReporteAprendiz($fechasInasistencia, $inasistenciasDiaSemana, $porcentajes);

        return [
            'id_aprendiz' => $filtros['id_aprendiz'],
            'total_asistencias' => $totalAsistencias,
            'total_inasistencias' => $totalAusentes,
            'total_tardanzas' => $totalTardanzas,
            'total_inasistencias_con_excusa' => $excusas['inasistencias_con_excusa'],
            'porcentaje_asistencia' => $porcentajes['asistencia'],
            'porcentaje_inasistencia' => $porcentajes['inasistencia'],
            'frecuencia_inasistencia_dias_promedio' => $frecuencia['dias_promedio'],
            'inasistencias_por_semana' => $distribucionPeriodo['por_semana'],
            'inasistencias_por_mes' => $distribucionPeriodo['por_mes'],
            'inasistencias_por_dia_semana' => $this->formatearDistribucionDiaSemana($inasistenciasDiaSemana),
            'inasistencias_por_jornada' => $inasistenciasJornada,
            'flags' => $flags
        ];
    }

    /**
     * Calcula porcentajes de asistencia e inasistencia
     */
    private function calcularPorcentajes(array $datos): array
    {
        $total = $datos['total_registros'] ?? ($datos['presentes'] + $datos['ausentes'] + $datos['tardanzas']);

        if ($total === 0) {
            return [
                'asistencia' => 0,
                'inasistencia' => 0,
                'tardanza' => 0
            ];
        }

        return [
            'asistencia' => round(($datos['presentes'] / $total) * 100, 2),
            'inasistencia' => round(($datos['ausentes'] / $total) * 100, 2),
            'tardanza' => round(($datos['tardanzas'] / $total) * 100, 2)
        ];
    }

    /**
     * Calcula frecuencia promedio de inasistencias en días
     */
    private function calcularFrecuenciaInasistencias(array $fechas): array
    {
        if (count($fechas) < 2) {
            return [
                'dias_promedio' => null,
                'total_inasistencias' => count($fechas)
            ];
        }

        $diferencias = [];
        for ($i = 1; $i < count($fechas); $i++) {
            $fecha1 = new DateTime($fechas[$i - 1]);
            $fecha2 = new DateTime($fechas[$i]);
            $diferencia = $fecha1->diff($fecha2)->days;
            $diferencias[] = $diferencia;
        }

        $promedio = array_sum($diferencias) / count($diferencias);

        return [
            'dias_promedio' => round($promedio, 1),
            'total_inasistencias' => count($fechas)
        ];
    }

    /**
     * Calcula distribución por semana y mes
     */
    private function calcularDistribucionPorPeriodo(array $datos): array
    {
        $porSemana = [];
        $porMes = [];

        foreach ($datos as $registro) {
            $semanaKey = $registro['anio'] . '-W' . str_pad($registro['semana'], 2, '0', STR_PAD_LEFT);
            $mesKey = $registro['anio'] . '-' . str_pad($registro['mes'], 2, '0', STR_PAD_LEFT);

            if (!isset($porSemana[$semanaKey])) {
                $porSemana[$semanaKey] = 0;
            }
            if (!isset($porMes[$mesKey])) {
                $porMes[$mesKey] = 0;
            }

            $porSemana[$semanaKey] += $registro['total_inasistencias'];
            $porMes[$mesKey] += $registro['total_inasistencias'];
        }

        return [
            'por_semana' => $porSemana,
            'por_mes' => $porMes
        ];
    }

    /**
     * Detecta flags de "reporte por analizar" para ficha
     */
    private function detectarFlagsReporte(array $datosFicha, array $inasistenciasDia): array
    {
        $flags = ['reporte_por_analizar' => false, 'motivos' => []];

        // Verificar concentración en un día de la semana
        $totalInasistencias = array_sum(array_column($inasistenciasDia, 'total'));
        if ($totalInasistencias > 0) {
            foreach ($inasistenciasDia as $dia) {
                $porcentaje = ($dia['total'] / $totalInasistencias) * 100;
                if ($porcentaje >= self::CONFIG_REPORTE['concentracion_dia_minima']) {
                    $flags['reporte_por_analizar'] = true;
                    $flags['motivos'][] = 'alta_concentracion_en_' . $this->nombreDiaSemana($dia['dia_semana']);
                    break;
                }
            }
        }

        return $flags;
    }

    /**
     * Detecta flags de "reporte por analizar" para aprendiz
     */
    private function detectarFlagsReporteAprendiz(array $fechasInasistencia, array $inasistenciasDia, array $porcentajes): array
    {
        $flags = ['reporte_por_analizar' => false, 'motivos' => []];

        // Verificar frecuencia de inasistencias
        $frecuencia = $this->calcularFrecuenciaInasistencias($fechasInasistencia);
        if ($frecuencia['dias_promedio'] !== null &&
            $frecuencia['dias_promedio'] <= self::CONFIG_REPORTE['frecuencia_maxima_dias']) {
            $flags['reporte_por_analizar'] = true;
            $flags['motivos'][] = 'frecuencia_menor_igual_' . self::CONFIG_REPORTE['frecuencia_maxima_dias'] . '_dias';
        }

        // Verificar concentración en un día de la semana
        $totalInasistencias = array_sum(array_column($inasistenciasDia, 'total'));
        if ($totalInasistencias > 0) {
            foreach ($inasistenciasDia as $dia) {
                $porcentaje = ($dia['total'] / $totalInasistencias) * 100;
                if ($porcentaje >= self::CONFIG_REPORTE['concentracion_dia_minima']) {
                    $flags['reporte_por_analizar'] = true;
                    $flags['motivos'][] = 'alta_concentracion_en_' . $this->nombreDiaSemana($dia['dia_semana']);
                    break;
                }
            }
        }

        return $flags;
    }

    /**
     * Convierte número de día de la semana a nombre
     */
    private function nombreDiaSemana(int $diaNumero): string
    {
        $dias = [
            1 => 'domingo',
            2 => 'lunes',
            3 => 'martes',
            4 => 'miercoles',
            5 => 'jueves',
            6 => 'viernes',
            7 => 'sabado'
        ];

        return $dias[$diaNumero] ?? 'desconocido';
    }

    /**
     * Formatea distribución por día de la semana
     */
    private function formatearDistribucionDiaSemana(array $datos): array
    {
        $formateado = [];
        foreach ($datos as $dia) {
            $formateado[$this->nombreDiaSemana($dia['dia_semana'])] = $dia['total'];
        }
        return $formateado;
    }

    /**
     * Exporta datos de aprendices para CSV
     */
    private function exportarDatosAprendiz(array $filtros): array
    {
        // Implementación simplificada - en producción se haría una consulta optimizada
        $aprendicesIds = $filtros['id_aprendiz'] ?? [];
        if (!is_array($aprendicesIds)) {
            $aprendicesIds = [$aprendicesIds];
        }

        $datos = [];
        foreach ($aprendicesIds as $idAprendiz) {
            $estadisticas = $this->getEstadisticasAprendiz(array_merge($filtros, ['id_aprendiz' => $idAprendiz]));
            $datos[] = [
                'id_aprendiz' => $estadisticas['id_aprendiz'],
                'total_asistencias' => $estadisticas['total_asistencias'],
                'total_inasistencias' => $estadisticas['total_inasistencias'],
                'total_tardanzas' => $estadisticas['total_tardanzas'],
                'porcentaje_asistencia' => $estadisticas['porcentaje_asistencia'],
                'porcentaje_inasistencia' => $estadisticas['porcentaje_inasistencia'],
                'frecuencia_promedio_dias' => $estadisticas['frecuencia_inasistencia_dias_promedio'],
                'reporte_por_analizar' => $estadisticas['flags']['reporte_por_analizar'] ? 'SI' : 'NO'
            ];
        }

        return $datos;
    }

    /**
     * Exporta datos de fichas para CSV
     */
    private function exportarDatosFicha(array $filtros): array
    {
        $idFicha = $filtros['id_ficha'];
        $estadisticas = $this->getEstadisticasFicha($idFicha, $filtros['fecha_desde'], $filtros['fecha_hasta']);

        return [
            [
                'id_ficha' => $idFicha,
                'total_registros' => $estadisticas['totales']['total_registros'],
                'presentes' => $estadisticas['totales']['presentes'],
                'ausentes' => $estadisticas['totales']['ausentes'],
                'tardanzas' => $estadisticas['totales']['tardanzas'],
                'porcentaje_asistencia' => $estadisticas['porcentajes']['asistencia'],
                'porcentaje_inasistencia' => $estadisticas['porcentajes']['inasistencia'],
                'reporte_por_analizar' => $estadisticas['flags']['reporte_por_analizar'] ? 'SI' : 'NO'
            ]
        ];
    }
}
