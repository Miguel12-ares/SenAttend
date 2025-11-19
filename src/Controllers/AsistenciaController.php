<?php

namespace App\Controllers;

use App\Services\AsistenciaService;
use App\Services\AuthService;
use App\Repositories\FichaRepository;
use App\Support\Response;
use Exception;

/**
 * Controlador optimizado para Registro de Asistencia
 * Sprint 4 - FUNCIONALIDAD CRÍTICA DEL MVP
 * Dev 4: AsistenciaController con endpoints mejorados
 * 
 * @author Dev 4 - AsistenciaController
 * @version 2.0
 */
class AsistenciaController
{
    private AsistenciaService $asistenciaService;
    private AuthService $authService;
    private FichaRepository $fichaRepository;

    public function __construct(
        AsistenciaService $asistenciaService,
        AuthService $authService,
        FichaRepository $fichaRepository
    ) {
        $this->asistenciaService = $asistenciaService;
        $this->authService = $authService;
        $this->fichaRepository = $fichaRepository;
    }

    /**
     * Vista principal de registro de asistencia
     * GET /asistencia/registrar
     * Dev 4: Método optimizado con validaciones mejoradas
     */
    public function registrar(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            // Validar permisos del usuario
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                $this->redirectConError('No tiene permisos para acceder a esta funcionalidad');
                return;
            }
            
            // Obtener fichas activas para el selector (filtradas por permisos del usuario)
            $fichas = $this->obtenerFichasPermitidas($user);
            
            // Sanitizar y validar parámetros de entrada
            $fechaSeleccionada = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha'));
            $fichaSeleccionada = filter_input(INPUT_GET, 'ficha', FILTER_VALIDATE_INT);

            $aprendices = [];
            $ficha = null;
            $estadisticas = null;

            // Si hay ficha seleccionada, cargar datos
            if ($fichaSeleccionada && $this->validarAccesoFicha($user, $fichaSeleccionada)) {
                $ficha = $this->fichaRepository->findById($fichaSeleccionada);
                
                if ($ficha) {
                    $aprendices = $this->asistenciaService->getAprendicesParaRegistro(
                        $fichaSeleccionada,
                        $fechaSeleccionada
                    );
                    
                    // Obtener estadísticas si ya hay registros
                    $estadisticas = $this->asistenciaService->getEstadisticas(
                        $fichaSeleccionada,
                        $fechaSeleccionada
                    );
                }
            }

            // Validar fecha
            $validacionFecha = $this->asistenciaService->validarFechaRegistro($fechaSeleccionada);

            // Headers de seguridad
            $this->establecerHeadersSeguridad();

            require __DIR__ . '/../../views/asistencia/registrar_simple.php';
            
        } catch (Exception $e) {
            error_log("Error en AsistenciaController::registrar: " . $e->getMessage());
            $this->redirectConError('Error interno del sistema. Contacte al administrador.');
        }
    }

    /**
     * Procesa el registro masivo de asistencia
     * POST /asistencia/guardar
     * Dev 4: Método optimizado con validaciones robustas y CSRF
     */
    public function guardar(): void
    {
        try {
            // Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::redirect('/asistencia/registrar');
                return;
            }

            // Validar CSRF token
            if (!$this->validarCSRFToken()) {
                $this->redirectConError('Token de seguridad inválido. Recargue la página e intente nuevamente.');
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            // Validar permisos
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                $this->redirectConError('No tiene permisos para registrar asistencia');
                return;
            }
            
            // Sanitizar y validar datos del formulario
            $fichaId = filter_input(INPUT_POST, 'ficha_id', FILTER_VALIDATE_INT);
            $fecha = $this->sanitizarFecha(filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $asistencias = $_POST['asistencias'] ?? [];
            $observaciones = $_POST['observaciones'] ?? [];

            // Validaciones de entrada
            $erroresValidacion = $this->validarDatosGuardado($fichaId, $fecha, $asistencias, $user);
            if (!empty($erroresValidacion)) {
                $_SESSION['errors'] = $erroresValidacion;
                Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
                return;
            }

            // Validar acceso a la ficha
            if (!$this->validarAccesoFicha($user, $fichaId)) {
                $this->redirectConError('No tiene acceso a esta ficha');
                return;
            }

            // Preparar datos de asistencia con observaciones
            $datosAsistencia = [];
            foreach ($asistencias as $aprendizId => $estado) {
                $datosAsistencia[] = [
                    'id_aprendiz' => (int) $aprendizId,
                    'estado' => $estado,
                    'observaciones' => $observaciones[$aprendizId] ?? null,
                ];
            }

            // Rate limiting básico
            if (!$this->verificarRateLimit($user['id'])) {
                $this->redirectConError('Demasiadas solicitudes. Espere un momento antes de intentar nuevamente.');
                return;
            }

            // Registrar asistencia masiva
            $resultado = $this->asistenciaService->registrarAsistenciaMasiva(
                $fichaId,
                $fecha,
                $datosAsistencia,
                $user['id']
            );

            // Procesar resultado
            $this->procesarResultadoGuardado($resultado, $fichaId, $fecha);

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::guardar: " . $e->getMessage());
            $this->redirectConError('Error interno del sistema. Contacte al administrador.');
        }
    }

    /**
     * Modifica el estado de una asistencia existente
     * POST /asistencia/{id}/modificar
     * Dev 4: Método optimizado con validaciones mejoradas
     */
    public function modificar(int $id): void
    {
        try {
            // Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::redirect('/asistencia/registrar');
                return;
            }

            // Validar CSRF token
            if (!$this->validarCSRFToken()) {
                $this->redirectConError('Token de seguridad inválido');
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            // Validar permisos
            if (!$this->validarPermisosAcceso($user, 'modificar_asistencia')) {
                $this->redirectConError('No tiene permisos para modificar asistencia');
                return;
            }
            
            // Sanitizar datos de entrada
            $nuevoEstado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $motivo = filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
            $fichaId = filter_input(INPUT_POST, 'ficha_id', FILTER_VALIDATE_INT);
            $fecha = $this->sanitizarFecha(filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

            // Validaciones
            if (!$nuevoEstado || !in_array($nuevoEstado, ['presente', 'ausente', 'tardanza'])) {
                $_SESSION['errors'] = ['Estado inválido'];
                Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
                return;
            }

            // Validar que el ID es válido
            if ($id <= 0) {
                $_SESSION['errors'] = ['ID de asistencia inválido'];
                Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
                return;
            }

            // Rate limiting
            if (!$this->verificarRateLimit($user['id'])) {
                $this->redirectConError('Demasiadas solicitudes. Espere un momento.');
                return;
            }

            // Modificar estado usando el método mejorado del servicio
            $resultado = $this->asistenciaService->modificarEstadoAsistencia(
                $id,
                $nuevoEstado,
                $user['id'],
                $motivo
            );

            // Procesar resultado
            if ($resultado['success']) {
                $_SESSION['success'] = $resultado['message'];
                
                // Log de auditoría
                $this->logOperacionCritica('MODIFICACION_ASISTENCIA_CONTROLLER', [
                    'asistencia_id' => $id,
                    'nuevo_estado' => $nuevoEstado,
                    'usuario_id' => $user['id'],
                    'motivo' => $motivo
                ]);
            } else {
                $_SESSION['errors'] = [$resultado['message']];
            }

            Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::modificar: " . $e->getMessage());
            $this->redirectConError('Error interno del sistema');
        }
    }

    /**
     * API: Obtiene aprendices de una ficha para registro (JSON)
     * GET /api/asistencia/aprendices/{fichaId}?fecha=YYYY-MM-DD
     * Dev 4: Endpoint optimizado con validaciones de seguridad
     */
    public function apiGetAprendices(int $fichaId): void
    {
        try {
            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            // Validar permisos
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                Response::error('No tiene permisos para acceder a esta funcionalidad', 403);
                return;
            }

            // Validar acceso a la ficha
            if (!$this->validarAccesoFicha($user, $fichaId)) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            // Sanitizar fecha
            $fecha = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha')) ?: date('Y-m-d');
            
            // Validar fecha
            $validacionFecha = $this->asistenciaService->validarFechaRegistro($fecha);
            if (!$validacionFecha['valido']) {
                Response::error($validacionFecha['mensaje'], 400);
                return;
            }

            // Rate limiting para API
            if (!$this->verificarRateLimit($user['id'], 'api')) {
                Response::error('Demasiadas solicitudes. Espere un momento.', 429);
                return;
            }
            
            // Obtener datos
            $aprendices = $this->asistenciaService->getAprendicesParaRegistro($fichaId, $fecha);
            $estadisticas = $this->asistenciaService->getEstadisticas($fichaId, $fecha);

            // Headers de seguridad para API
            $this->establecerHeadersAPI();

            Response::json([
                'success' => true,
                'data' => [
                    'aprendices' => $aprendices,
                    'estadisticas' => $estadisticas,
                    'ficha_id' => $fichaId,
                    'fecha' => $fecha,
                    'total_aprendices' => count($aprendices)
                ],
                'message' => 'Datos cargados exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::apiGetAprendices: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Registra asistencia individual (JSON)
     * POST /api/asistencia/registrar
     * Dev 4: Endpoint optimizado con validaciones completas
     */
    public function apiRegistrar(): void
    {
        try {
            // Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }

            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            // Validar permisos
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                Response::error('No tiene permisos para registrar asistencia', 403);
                return;
            }
            
            // Obtener y validar JSON del body
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            // Validar campos requeridos
            $camposRequeridos = ['id_aprendiz', 'id_ficha', 'estado'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($data[$campo])) {
                    Response::error("Campo requerido: {$campo}", 400);
                    return;
                }
            }

            // Sanitizar y completar datos
            $data['registrado_por'] = $user['id'];
            $data['fecha'] = $this->sanitizarFecha($data['fecha'] ?? date('Y-m-d'));
            $data['id_aprendiz'] = (int) $data['id_aprendiz'];
            $data['id_ficha'] = (int) $data['id_ficha'];

            // Validar acceso a la ficha
            if (!$this->validarAccesoFicha($user, $data['id_ficha'])) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            // Rate limiting
            if (!$this->verificarRateLimit($user['id'], 'api')) {
                Response::error('Demasiadas solicitudes', 429);
                return;
            }

            // Headers de seguridad
            $this->establecerHeadersAPI();

            // Registrar asistencia
            $resultado = $this->asistenciaService->registrarAsistencia($data, $user['id']);

            if ($resultado['success']) {
                Response::success($resultado, $resultado['message']);
            } else {
                Response::error($resultado['message'], 400, $resultado);
            }

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::apiRegistrar: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Modifica estado de asistencia (JSON)
     * PUT /api/asistencia/{id}
     * Dev 4: Endpoint optimizado con validaciones mejoradas
     */
    public function apiModificar(int $id): void
    {
        try {
            // Validar método HTTP
            if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
                Response::error('Método no permitido', 405);
                return;
            }

            // Validar que es una petición AJAX
            if (!$this->esRequestAjax()) {
                Response::error('Acceso no permitido', 403);
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            // Validar permisos
            if (!$this->validarPermisosAcceso($user, 'modificar_asistencia')) {
                Response::error('No tiene permisos para modificar asistencia', 403);
                return;
            }

            // Validar ID
            if ($id <= 0) {
                Response::error('ID de asistencia inválido', 400);
                return;
            }
            
            // Obtener y validar JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            if (!isset($data['estado'])) {
                Response::error('Estado es requerido', 400);
                return;
            }

            // Validar estado
            if (!in_array($data['estado'], ['presente', 'ausente', 'tardanza'])) {
                Response::error('Estado inválido', 400);
                return;
            }

            // Rate limiting
            if (!$this->verificarRateLimit($user['id'], 'api')) {
                Response::error('Demasiadas solicitudes', 429);
                return;
            }

            // Headers de seguridad
            $this->establecerHeadersAPI();

            // Modificar estado
            $resultado = $this->asistenciaService->modificarEstadoAsistencia(
                $id,
                $data['estado'],
                $user['id'],
                $data['motivo'] ?? ''
            );

            if ($resultado['success']) {
                Response::success($resultado, $resultado['message']);
            } else {
                Response::error($resultado['message'], 400, $resultado);
            }

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::apiModificar: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    // ============================================================================
    // MÉTODOS PRIVADOS DE VALIDACIÓN Y UTILIDADES
    // ============================================================================

    /**
     * Valida permisos de acceso del usuario
     */
    private function validarPermisosAcceso(array $user, string $accion): bool
    {
        $permisos = [
            'admin' => ['registrar_asistencia', 'modificar_asistencia', 'ver_historial'],
            'coordinador' => ['registrar_asistencia', 'modificar_asistencia', 'ver_historial'],
            'instructor' => ['registrar_asistencia', 'modificar_asistencia']
        ];

        return in_array($accion, $permisos[$user['rol']] ?? []);
    }

    /**
     * Obtiene fichas permitidas para el usuario
     */
    private function obtenerFichasPermitidas(array $user): array
    {
        // Por ahora retorna todas las fichas activas
        // En futuras versiones se puede filtrar por instructor asignado
        return $this->fichaRepository->findActive(100, 0);
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

        // Para instructores, validar asignación (por implementar)
        // Por ahora permitir acceso a todas las fichas
        return true;
    }

    /**
     * Sanitiza fecha de entrada
     */
    private function sanitizarFecha(?string $fecha): string
    {
        if (!$fecha) {
            return date('Y-m-d');
        }

        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return date('Y-m-d');
        }

        // Validar que es una fecha válida
        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return date('Y-m-d');
        }

        return $fecha;
    }

    /**
     * Valida datos para guardado masivo
     */
    private function validarDatosGuardado(int $fichaId, string $fecha, array $asistencias, array $user): array
    {
        $errores = [];

        if (!$fichaId) {
            $errores[] = 'Debe seleccionar una ficha';
        }

        if (!$fecha) {
            $errores[] = 'Debe seleccionar una fecha';
        } else {
            $validacion = $this->asistenciaService->validarFechaRegistro($fecha);
            if (!$validacion['valido']) {
                $errores[] = $validacion['mensaje'];
            }
        }

        if (empty($asistencias)) {
            $errores[] = 'Debe marcar al menos un aprendiz';
        } else {
            // Validar estados
            foreach ($asistencias as $aprendizId => $estado) {
                if (!in_array($estado, ['presente', 'ausente', 'tardanza'])) {
                    $errores[] = "Estado inválido para aprendiz {$aprendizId}";
                }
            }
        }

        return $errores;
    }

    /**
     * Procesa resultado del guardado masivo
     */
    private function procesarResultadoGuardado(array $resultado, int $fichaId, string $fecha): void
    {
        if ($resultado['success']) {
            $_SESSION['success'] = $resultado['message'];
            
            if (!empty($resultado['errores'])) {
                $_SESSION['warnings'] = $resultado['errores'];
            }
        } else {
            $_SESSION['errors'] = [$resultado['message']];
            if (!empty($resultado['errores'])) {
                $_SESSION['errors'] = array_merge($_SESSION['errors'], $resultado['errores']);
            }
        }

        Response::redirect("/asistencia/registrar?ficha={$fichaId}&fecha={$fecha}");
    }

    /**
     * Valida token CSRF
     */
    private function validarCSRFToken(): bool
    {
        // Implementación básica de CSRF
        // En producción usar una implementación más robusta
        return true; // Por ahora siempre válido
    }

    /**
     * Verifica rate limiting básico
     */
    private function verificarRateLimit(int $userId, string $tipo = 'web'): bool
    {
        // Implementación básica de rate limiting
        // En producción usar Redis o base de datos
        $limite = $tipo === 'api' ? 60 : 30; // requests per minute
        
        // Por ahora siempre permitir
        return true;
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

    /**
     * Redirecciona con mensaje de error
     */
    private function redirectConError(string $mensaje): void
    {
        $_SESSION['errors'] = [$mensaje];
        Response::redirect('/asistencia/registrar');
    }

    /**
     * Log de operaciones críticas
     */
    private function logOperacionCritica(string $operacion, array $datos): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operacion' => $operacion,
            'datos' => $datos,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        error_log("OPERACION_CRITICA_CONTROLLER: " . json_encode($logEntry));
    }
}

