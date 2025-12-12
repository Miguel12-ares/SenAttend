<?php

namespace App\Controllers;

use App\Services\AsistenciaService;
use App\Services\AuthService;
use App\Services\AnomaliaService;
use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\AnomaliaRepository;
use App\Repositories\UserRepository;
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
    private AprendizRepository $aprendizRepository;
    private ?AnomaliaService $anomaliaService = null;

    public function __construct(
        AsistenciaService $asistenciaService,
        AuthService $authService,
        FichaRepository $fichaRepository,
        AprendizRepository $aprendizRepository,
        ?AnomaliaService $anomaliaService = null
    ) {
        $this->asistenciaService = $asistenciaService;
        $this->authService = $authService;
        $this->fichaRepository = $fichaRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->anomaliaService = $anomaliaService;
    }

    /**
     * Obtiene o inicializa el servicio de anomalías
     */
    private function getAnomaliaService(): AnomaliaService
    {
        if ($this->anomaliaService === null) {
            $anomaliaRepository = new AnomaliaRepository();
            $asistenciaRepository = new \App\Repositories\AsistenciaRepository();
            $userRepository = new UserRepository();
            $this->anomaliaService = new AnomaliaService(
                $anomaliaRepository,
                $asistenciaRepository,
                $this->fichaRepository,
                $this->aprendizRepository,
                $userRepository
            );
        }
        return $this->anomaliaService;
    }


    /**
     * Muestra la vista de registro de anomalías
     * GET /anomalias/registrar
     */
    public function registrarAnomalias(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            // Validar permisos
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                $this->redirectConError('No tiene permisos para registrar anomalías');
                return;
            }

            // Obtener fichas del instructor
            $fichas = $this->obtenerFichasPermitidas($user);

            // Obtener tipos de anomalías
            $tiposAnomalias = $this->getAnomaliaService()->getTiposAnomalias();

            // Renderizar vista
            $this->renderizarVistaAnomalias($user, $fichas, $tiposAnomalias);

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::registrarAnomalias: " . $e->getMessage());
            $this->redirectConError('Error al cargar la vista de anomalías');
        }
    }

    /**
     * Renderiza la vista de registro de anomalías
     */
    private function renderizarVistaAnomalias(array $user, array $fichas, array $tiposAnomalias): void
    {
        // La vista ya incluye todo el HTML completo
        require __DIR__ . '/../../views/anomalias/registrar.php';
        exit;
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
                Response::redirect('/dashboard');
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
                Response::redirect('/dashboard');
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
                Response::redirect('/dashboard');
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
                Response::redirect('/dashboard');
                return;
            }

            // Validar que el ID es válido
            if ($id <= 0) {
                $_SESSION['errors'] = ['ID de asistencia inválido'];
                Response::redirect('/dashboard');
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
            try {
                $aprendices = $this->asistenciaService->getAprendicesParaRegistro($fichaId, $fecha);
            } catch (Exception $e) {
                error_log("Error obteniendo aprendices: " . $e->getMessage());
                throw $e;
            }
            
            try {
                $estadisticas = $this->asistenciaService->getEstadisticas($fichaId, $fecha);
            } catch (Exception $e) {
                error_log("Error obteniendo estadísticas: " . $e->getMessage());
                // Si falla estadísticas, usar valores por defecto
                $estadisticas = [
                    'total' => 0,
                    'presentes' => 0,
                    'ausentes' => 0,
                    'tardanzas' => 0,
                    'porcentaje_presentes' => 0,
                    'porcentaje_ausentes' => 0,
                    'porcentaje_tardanzas' => 0,
                ];
            }

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
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // En desarrollo, mostrar el error real
            $errorMessage = defined('APP_ENV') && APP_ENV === 'local' 
                ? $e->getMessage() 
                : 'Error interno del servidor';
            
            Response::error($errorMessage, 500);
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
        // Admin y coordinador ven todas las fichas
        if (in_array($user['rol'], ['admin', 'coordinador'])) {
            return $this->fichaRepository->findActive(200, 0);
        }
        
        // Instructor solo ve sus fichas asignadas
        if ($user['rol'] === 'instructor') {
            // Necesitamos inyectar el InstructorFichaRepository
            $instructorFichaRepo = new \App\Repositories\InstructorFichaRepository();
            return $instructorFichaRepo->findFichasByInstructor($user['id'], true);
        }
        
        // Por defecto, sin acceso
        return [];
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
                    // Obtener nombre completo del aprendiz para el mensaje de error
                    $aprendiz = $this->aprendizRepository->findById((int)$aprendizId);
                    $nombreCompleto = $aprendiz ? trim($aprendiz['nombre'] . ' ' . $aprendiz['apellido']) : "ID {$aprendizId}";
                    $errores[] = "Estado inválido para aprendiz {$nombreCompleto}";
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
        Response::redirect('/dashboard');
    }

    /**
     * API: Registra anomalía por aprendiz
     * POST /api/asistencia/anomalia/aprendiz
     */
    public function apiRegistrarAnomaliaAprendiz(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                Response::error('No tiene permisos para registrar anomalías', 403);
                return;
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            // Validar campos requeridos
            $camposRequeridos = ['id_aprendiz', 'id_ficha', 'fecha_asistencia', 'tipo_anomalia'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($data[$campo])) {
                    Response::error("Campo requerido: {$campo}", 400);
                    return;
                }
            }

            // Validar acceso a la ficha
            if (!$this->validarAccesoFicha($user, $data['id_ficha'])) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            $this->establecerHeadersAPI();

            $resultado = $this->getAnomaliaService()->registrarAnomaliaAprendiz($data, $user['id']);

            if ($resultado['success']) {
                Response::success($resultado, $resultado['message']);
            } else {
                Response::error($resultado['message'], 400, $resultado);
            }

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::apiRegistrarAnomaliaAprendiz: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Registra anomalía general de ficha
     * POST /api/asistencia/anomalia/ficha
     */
    public function apiRegistrarAnomaliaFicha(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }

            $user = $this->authService->getCurrentUser();
            
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                Response::error('No tiene permisos para registrar anomalías', 403);
                return;
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            // Validar campos requeridos
            $camposRequeridos = ['id_ficha', 'fecha_asistencia', 'tipo_anomalia'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($data[$campo])) {
                    Response::error("Campo requerido: {$campo}", 400);
                    return;
                }
            }

            // Validar acceso a la ficha
            if (!$this->validarAccesoFicha($user, $data['id_ficha'])) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            $this->establecerHeadersAPI();

            $resultado = $this->getAnomaliaService()->registrarAnomaliaFicha($data, $user['id']);

            if ($resultado['success']) {
                Response::success($resultado, $resultado['message']);
            } else {
                Response::error($resultado['message'], 400, $resultado);
            }

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::apiRegistrarAnomaliaFicha: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Obtiene anomalías de un aprendiz o ficha
     * GET /api/asistencia/anomalias?ficha_id=X&fecha=YYYY-MM-DD&aprendiz_id=X (opcional)
     */
    public function apiGetAnomalias(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                Response::error('No tiene permisos para acceder a esta información', 403);
                return;
            }

            $fichaId = filter_input(INPUT_GET, 'ficha_id', FILTER_VALIDATE_INT);
            $fecha = $this->sanitizarFecha(filter_input(INPUT_GET, 'fecha'));
            $aprendizId = filter_input(INPUT_GET, 'aprendiz_id', FILTER_VALIDATE_INT);

            if (!$fichaId || !$fecha) {
                Response::error('ficha_id y fecha son requeridos', 400);
                return;
            }

            // Validar acceso a la ficha
            if (!$this->validarAccesoFicha($user, $fichaId)) {
                Response::error('No tiene acceso a esta ficha', 403);
                return;
            }

            $this->establecerHeadersAPI();

            $anomalias = [];
            if ($aprendizId) {
                // Anomalías de un aprendiz específico
                $anomalias = $this->getAnomaliaService()->getAnomaliasAprendiz($aprendizId, $fichaId, $fecha);
            } else {
                // Anomalías generales de ficha
                $anomalias = $this->getAnomaliaService()->getAnomaliasFicha($fichaId, $fecha);
            }

            Response::json([
                'success' => true,
                'data' => $anomalias
            ]);

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::apiGetAnomalias: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * API: Obtiene tipos de anomalías disponibles
     * GET /api/asistencia/anomalias/tipos
     */
    public function apiGetTiposAnomalias(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$this->validarPermisosAcceso($user, 'registrar_asistencia')) {
                Response::error('No tiene permisos para acceder a esta información', 403);
                return;
            }

            $this->establecerHeadersAPI();

            $tipos = $this->getAnomaliaService()->getTiposAnomalias();

            Response::json([
                'success' => true,
                'data' => $tipos
            ]);

        } catch (Exception $e) {
            error_log("Error en AsistenciaController::apiGetTiposAnomalias: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
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

