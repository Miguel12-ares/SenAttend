<?php

namespace App\Controllers;

use App\Services\EstadisticasService;
use App\Services\AuthService;
use App\Repositories\FichaRepository;
use App\Support\Response;
use Exception;

/**
 * Controlador para el módulo de estadísticas de asistencia
 * Dev: Módulo de Estadísticas - Capa de API
 *
 * @author Dev - EstadisticasController
 * @version 1.0
 */
class EstadisticasController
{
    private EstadisticasService $estadisticasService;
    private AuthService $authService;
    private FichaRepository $fichaRepository;

    public function __construct(
        EstadisticasService $estadisticasService,
        AuthService $authService,
        FichaRepository $fichaRepository
    ) {
        $this->estadisticasService = $estadisticasService;
        $this->authService = $authService;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * GET /api/estadisticas/aprendiz
     * Devuelve estadísticas para uno o varios aprendices
     */
    public function aprendiz(): void
    {
        try {
            // Validar que es una petición API
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Validar permisos
            if (!$this->validarPermisosEstadisticas($user)) {
                Response::error('No tiene permisos para acceder a estadísticas', 403);
                return;
            }

            // Obtener y validar parámetros
            $filtros = $this->obtenerFiltrosAprendiz();

            // Validar acceso a fichas (si se especifica)
            if (isset($filtros['id_ficha']) && !$this->validarAccesoFicha($user, $filtros['id_ficha'])) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            // Rate limiting para API
            if (!$this->verificarRateLimit($user['id'], 'api')) {
                Response::error('Demasiadas solicitudes. Espere un momento.', 429);
                return;
            }

            // Obtener estadísticas
            $estadisticas = $this->estadisticasService->getEstadisticasAprendiz($filtros);

            // Headers de seguridad para API
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en EstadisticasController::aprendiz: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * GET /api/estadisticas/ficha
     * Devuelve estadísticas agregadas por ficha
     */
    public function ficha(): void
    {
        try {
            // Validar que es una petición API
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Validar permisos
            if (!$this->validarPermisosEstadisticas($user)) {
                Response::error('No tiene permisos para acceder a estadísticas', 403);
                return;
            }

            // Obtener parámetros
            $idFicha = filter_input(INPUT_GET, 'id_ficha', FILTER_VALIDATE_INT);
            $fechaDesde = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_desde'));
            $fechaHasta = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_hasta'));

            // Validaciones
            if (!$idFicha) {
                Response::error('ID de ficha es requerido', 400);
                return;
            }

            if (!$fechaDesde || !$fechaHasta) {
                Response::error('Fechas desde y hasta son requeridas', 400);
                return;
            }

            // Validar acceso a la ficha
            if (!$this->validarAccesoFicha($user, $idFicha)) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            // Rate limiting
            if (!$this->verificarRateLimit($user['id'], 'api')) {
                Response::error('Demasiadas solicitudes', 429);
                return;
            }

            // Obtener estadísticas
            $estadisticas = $this->estadisticasService->getEstadisticasFicha($idFicha, $fechaDesde, $fechaHasta);

            // Headers de seguridad
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de ficha obtenidas exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en EstadisticasController::ficha: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * GET /api/estadisticas/reportes
     * Lista casos marcados como "reporte por analizar"
     */
    public function reportes(): void
    {
        try {
            // Validar que es una petición API
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Solo administradores y coordinadores pueden ver reportes
            if (!in_array($user['rol'], ['admin', 'coordinador'])) {
                Response::error('Solo administradores y coordinadores pueden acceder a reportes', 403);
                return;
            }

            // Obtener filtros
            $tipoEntidad = filter_input(INPUT_GET, 'tipo_entidad', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $idAprendiz = filter_input(INPUT_GET, 'id_aprendiz', FILTER_VALIDATE_INT);
            $idFicha = filter_input(INPUT_GET, 'id_ficha', FILTER_VALIDATE_INT);
            $fechaDesde = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_desde'));
            $fechaHasta = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_hasta'));

            $filtros = [];
            if ($tipoEntidad) $filtros['tipo_entidad'] = $tipoEntidad;
            if ($idAprendiz) $filtros['id_aprendiz'] = $idAprendiz;
            if ($idFicha) $filtros['id_ficha'] = $idFicha;
            if ($fechaDesde) $filtros['fecha_desde'] = $fechaDesde;
            if ($fechaHasta) $filtros['fecha_hasta'] = $fechaHasta;

            // Rate limiting
            if (!$this->verificarRateLimit($user['id'], 'api')) {
                Response::error('Demasiadas solicitudes', 429);
                return;
            }

            // Obtener reportes
            $reportes = $this->estadisticasService->getReportesPorAnalizar($filtros);

            // Headers de seguridad
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => $reportes,
                'total' => count($reportes),
                'message' => 'Reportes obtenidos exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en EstadisticasController::reportes: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * GET /api/estadisticas/exportar
     * Devuelve datos tabulares para exportar a CSV
     */
    public function exportar(): void
    {
        try {
            // Validar que es una petición API
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();

            // Validar permisos
            if (!$this->validarPermisosEstadisticas($user)) {
                Response::error('No tiene permisos para exportar estadísticas', 403);
                return;
            }

            // Obtener parámetros
            $tipo = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $idAprendiz = filter_input(INPUT_GET, 'id_aprendiz', FILTER_VALIDATE_INT);
            $idFicha = filter_input(INPUT_GET, 'id_ficha', FILTER_VALIDATE_INT);
            $fechaDesde = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_desde'));
            $fechaHasta = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_hasta'));

            // Validaciones
            if (!in_array($tipo, ['aprendiz', 'ficha'])) {
                Response::error('Tipo debe ser "aprendiz" o "ficha"', 400);
                return;
            }

            $filtros = [];
            if ($tipo === 'aprendiz' && $idAprendiz) {
                $filtros['id_aprendiz'] = $idAprendiz;
            } elseif ($tipo === 'ficha' && $idFicha) {
                $filtros['id_ficha'] = $idFicha;
            }

            if ($fechaDesde) $filtros['fecha_desde'] = $fechaDesde;
            if ($fechaHasta) $filtros['fecha_hasta'] = $fechaHasta;

            // Validar acceso
            if (isset($filtros['id_ficha']) && !$this->validarAccesoFicha($user, $filtros['id_ficha'])) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            // Rate limiting
            if (!$this->verificarRateLimit($user['id'], 'api')) {
                Response::error('Demasiadas solicitudes', 429);
                return;
            }

            // Obtener datos para exportar
            $datos = $this->estadisticasService->exportarDatos($filtros, $tipo);

            // Headers de seguridad
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => $datos,
                'tipo' => $tipo,
                'total_registros' => count($datos),
                'message' => 'Datos para exportación obtenidos exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en EstadisticasController::exportar: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Vista web para estadísticas (GET /estadisticas)
     */
    public function index(): void
    {
        try {
            $user = $this->authService->getCurrentUser();

            // Validar permisos
            if (!$this->validarPermisosEstadisticas($user)) {
                $_SESSION['errors'] = ['No tiene permisos para acceder al módulo de estadísticas'];
                Response::redirect('/dashboard');
                return;
            }

            // Obtener fichas disponibles según permisos
            $fichas = $this->obtenerFichasPermitidas($user);

            // Headers de seguridad
            $this->establecerHeadersSeguridad();

            require __DIR__ . '/../../views/estadisticas/index.php';

        } catch (Exception $e) {
            error_log("Error en EstadisticasController::index: " . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del sistema'];
            Response::redirect('/dashboard');
        }
    }

    // ============================================================================
    // MÉTODOS PRIVADOS DE VALIDACIÓN Y UTILIDADES
    // ============================================================================

    /**
     * Obtiene filtros para estadísticas de aprendiz
     */
    private function obtenerFiltrosAprendiz(): array
    {
        $filtros = [];

        // ID de aprendiz (puede ser uno o varios)
        $idAprendiz = filter_input(INPUT_GET, 'id_aprendiz', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($idAprendiz) {
            // Si contiene comas, es una lista
            if (strpos($idAprendiz, ',') !== false) {
                $filtros['id_aprendiz'] = array_map('intval', explode(',', $idAprendiz));
            } else {
                $filtros['id_aprendiz'] = (int) $idAprendiz;
            }
        }

        // Filtros opcionales
        $idFicha = filter_input(INPUT_GET, 'id_ficha', FILTER_VALIDATE_INT);
        $fechaDesde = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_desde'));
        $fechaHasta = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha_hasta'));
        $jornada = filter_input(INPUT_GET, 'jornada', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($idFicha) $filtros['id_ficha'] = $idFicha;
        if ($fechaDesde) $filtros['fecha_desde'] = $fechaDesde;
        if ($fechaHasta) $filtros['fecha_hasta'] = $fechaHasta;
        if ($jornada && in_array($jornada, ['mañana', 'tarde', 'noche'])) {
            $filtros['jornada'] = $jornada;
        }

        return $filtros;
    }

    /**
     * Valida permisos para acceder a estadísticas
     */
    private function validarPermisosEstadisticas(array $user): bool
    {
        $rolesPermitidos = ['admin', 'coordinador', 'instructor'];
        return in_array($user['rol'], $rolesPermitidos);
    }

    /**
     * Valida acceso del usuario a una ficha específica
     */
    private function validarAccesoFicha(array $user, int $fichaId): bool
    {
        // Admin y coordinador tienen acceso a todas las fichas
        if (in_array($user['rol'], ['admin', 'coordinador'])) {
            return true;
        }

        // Instructor - validar con la tabla de asignaciones
        if ($user['rol'] === 'instructor') {
            $instructorFichaRepo = new \App\Repositories\InstructorFichaRepository();
            return $instructorFichaRepo->isActive($user['id'], $fichaId);
        }

        return false;
    }

    /**
     * Obtiene fichas permitidas para el usuario
     */
    private function obtenerFichasPermitidas(array $user): array
    {
        // Admin y coordinador ven todas las fichas
        if (in_array($user['rol'], ['admin', 'coordinador'])) {
            return $this->fichaRepository->findActive(200, 0);
        }

        // Instructor solo ve sus fichas asignadas
        if ($user['rol'] === 'instructor') {
            $instructorFichaRepo = new \App\Repositories\InstructorFichaRepository();
            return $instructorFichaRepo->findFichasByInstructor($user['id'], true);
        }

        return [];
    }

    /**
     * Sanitiza fecha de entrada
     */
    private function sanitizarFecha(?string $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return null;
        }

        // Validar que es una fecha válida
        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return null;
        }

        return $fecha;
    }

    /**
     * Verifica rate limiting básico
     */
    private function verificarRateLimit(int $userId, string $tipo = 'web'): bool
    {
        // Implementación básica de rate limiting
        // En producción usar Redis o base de datos
        return true; // Por ahora siempre permitir
    }

    /**
     * Verifica si es una petición AJAX
     */
    private function esRequestAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Establece headers de seguridad para páginas web
     */
    private function establecerHeadersSeguridad(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Establece headers de seguridad para API
     */
    private function establecerHeadersAPI(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
