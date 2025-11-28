<?php

/**
 * Router frontal de la aplicación
 * Punto de entrada único para todas las peticiones
 */

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuración
require_once __DIR__ . '/../config/config.php';

// Importar clases necesarias
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use App\Controllers\QRController;
use App\Controllers\WelcomeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Repositories\UserRepository;
use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;
use App\Repositories\CodigoQRRepository;
use App\Repositories\InstructorFichaRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\QRService;
use App\Session\SessionManager;
use App\Support\Response;

// Manejo de errores global
set_exception_handler(function ($exception) {
    error_log('Uncaught Exception: ' . $exception->getMessage());
    
    if (defined('APP_ENV') && APP_ENV === 'local') {
        echo '<pre>';
        echo 'Error: ' . $exception->getMessage() . "\n";
        echo 'File: ' . $exception->getFile() . ':' . $exception->getLine() . "\n";
        echo "\nStack trace:\n" . $exception->getTraceAsString();
        echo '</pre>';
    } else {
        Response::serverError();
    }
});

// Obtener la URI y el método
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Limpiar query string de la URI
$uri = strtok($requestUri, '?');
$uri = rtrim($uri, '/') ?: '/';

// Inicializar dependencias
$session = new SessionManager();
$userRepository = new UserRepository();
$fichaRepository = new FichaRepository();
$aprendizRepository = new AprendizRepository();
$codigoQRRepository = new CodigoQRRepository();
$instructorFichaRepository = new InstructorFichaRepository();
$asistenciaRepository = new \App\Repositories\AsistenciaRepository();
$turnoConfigRepository = new \App\Repositories\TurnoConfigRepository();
$authService = new AuthService($userRepository, $session);
$emailService = new EmailService();
$qrService = new QRService($codigoQRRepository, $aprendizRepository, $emailService);
$turnoConfigService = new \App\Services\TurnoConfigService($turnoConfigRepository);
$asistenciaService = new \App\Services\AsistenciaService($asistenciaRepository, $aprendizRepository, $fichaRepository, $turnoConfigService);
$authMiddleware = new AuthMiddleware($session);
// Cargar configuración de permisos (RBAC)
$permissionsConfig = require __DIR__ . '/../config/permissions_config.php';
$permissionMiddleware = new PermissionMiddleware($session, $permissionsConfig);

// Definición de rutas estáticas
$routes = [
    'GET' => [
        '/' => [
            'controller' => WelcomeController::class,
            'action' => 'index',
            'middleware' => []
        ],
        '/dashboard' => [
            'controller' => DashboardController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        '/home' => [
            'controller' => HomeController::class,
            'action' => 'index',
            'middleware' => []
        ],
        '/login' => [
            'controller' => AuthController::class,
            'action' => 'viewLogin',
            'middleware' => []
        ],
        '/auth/logout' => [
            'controller' => AuthController::class,
            'action' => 'logout',
            'middleware' => []
        ],
        // Fichas
        '/fichas' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        '/fichas/crear' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'create',
            'middleware' => ['auth']
        ],
        // Aprendices
        '/aprendices' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        '/aprendices/crear' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'create',
            'middleware' => ['auth']
        ],
        // Gestión de Instructores
        '/gestion-instructores' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        '/gestion-instructores/crear' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'create',
            'middleware' => ['auth']
        ],
        '/gestion-instructores/importar' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'importView',
            'middleware' => ['auth']
        ],
        // Asistencia (CRÍTICO)
        '/asistencia/registrar' => [
            'controller' => \App\Controllers\AsistenciaController::class,
            'action' => 'registrar',
            'middleware' => ['auth']
        ],
        // QR
        '/qr/generar' => [
            'controller' => QRController::class,
            'action' => 'generar',
            'middleware' => ['auth']
        ],
        '/qr/escanear' => [
            'controller' => QRController::class,
            'action' => 'escanear',
            'middleware' => ['auth']
        ],
        // Perfil
        '/perfil' => [
            'controller' => ProfileController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        // Test de rutas (solo en desarrollo)
        '/test-routes' => [
            'controller' => function() {
                include __DIR__ . '/../test_routes.php';
            },
            'action' => null,
            'middleware' => []
        ],
        // Gestión de Asignaciones Instructor-Ficha
        '/instructor-fichas' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        // API Instructor-Fichas
        '/api/instructor-fichas/estadisticas' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'getEstadisticas',
            'middleware' => ['auth']
        ],
        '/api/instructores' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'getAllInstructores',
            'middleware' => ['auth']
        ],
        // API Fichas
        '/api/fichas' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiList',
            'middleware' => ['auth']
        ],
        '/api/fichas/search' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiSearch',
            'middleware' => ['auth']
        ],
        '/api/fichas/estadisticas' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiEstadisticas',
            'middleware' => ['auth']
        ],
        // API Aprendices
        '/api/aprendices' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiList',
            'middleware' => ['auth']
        ],
        '/api/aprendices/estadisticas' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiEstadisticas',
            'middleware' => ['auth']
        ],
        // API QR
        '/api/qr/buscar' => [
            'controller' => QRController::class,
            'action' => 'apiBuscarAprendiz',
            'middleware' => ['auth']
        ],
        '/api/qr/historial-diario' => [
            'controller' => QRController::class,
            'action' => 'apiHistorialDiario',
            'middleware' => ['auth']
        ],
        // Configuración de Turnos (Solo Admin)
        '/configuracion/horarios' => [
            'controller' => \App\Controllers\TurnoConfigController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        // API Configuración de Turnos
        '/api/configuracion/turnos' => [
            'controller' => \App\Controllers\TurnoConfigController::class,
            'action' => 'apiObtenerTurnos',
            'middleware' => ['auth']
        ],
        '/api/configuracion/turno-actual' => [
            'controller' => \App\Controllers\TurnoConfigController::class,
            'action' => 'apiTurnoActual',
            'middleware' => ['auth']
        ],
    ],
    'POST' => [
        '/auth/login' => [
            'controller' => AuthController::class,
            'action' => 'login',
            'middleware' => []
        ],
        // Perfil
        '/perfil/cambiar-password' => [
            'controller' => ProfileController::class,
            'action' => 'cambiarPassword',
            'middleware' => ['auth']
        ],
        // API Pública - Validar aprendiz y generar QR
        '/api/public/aprendiz/validar' => [
            'controller' => HomeController::class,
            'action' => 'apiValidarAprendiz',
            'middleware' => []
        ],
        // Fichas POST
        '/fichas' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'store',
            'middleware' => ['auth']
        ],
        // Aprendices POST
        '/aprendices' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'store',
            'middleware' => ['auth']
        ],
        '/aprendices/importar' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'import',
            'middleware' => ['auth']
        ],
        // Gestión de Instructores POST
        '/gestion-instructores' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'store',
            'middleware' => ['auth']
        ],
        '/gestion-instructores/importar-csv' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'processImport',
            'middleware' => ['auth']
        ],
        // Asistencia (CRÍTICO)
        '/asistencia/guardar' => [
            'controller' => \App\Controllers\AsistenciaController::class,
            'action' => 'guardar',
            'middleware' => ['auth']
        ],
        // API Fichas POST
        '/api/fichas' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiCreate',
            'middleware' => ['auth']
        ],
        // API Instructor-Fichas POST
        '/api/instructor-fichas/asignar-fichas' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'asignarFichas',
            'middleware' => ['auth']
        ],
        '/api/instructor-fichas/asignar-instructores' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'asignarInstructores',
            'middleware' => ['auth']
        ],
        '/api/instructor-fichas/sincronizar' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'sincronizarFichas',
            'middleware' => ['auth']
        ],
        '/api/instructor-fichas/eliminar' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'eliminarAsignacion',
            'middleware' => ['auth']
        ],
        '/api/fichas/importar' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiImportarCSV',
            'middleware' => ['auth']
        ],
        '/api/fichas/validar-csv' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiValidarCSV',
            'middleware' => ['auth']
        ],
        // API Aprendices POST
        '/api/aprendices' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiCreate',
            'middleware' => ['auth']
        ],
        '/api/aprendices/importar' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiImportarCSV',
            'middleware' => ['auth']
        ],
        '/api/aprendices/validar-csv' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiValidarCSV',
            'middleware' => ['auth']
        ],
        '/api/aprendices/vincular-multiples' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiVincularMultiples',
            'middleware' => ['auth']
        ],
        // API QR POST
        '/api/qr/procesar' => [
            'controller' => QRController::class,
            'action' => 'apiProcesarQR',
            'middleware' => ['auth']
        ],
        // Configuración de Turnos POST (Solo Admin)
        '/configuracion/horarios/actualizar' => [
            'controller' => \App\Controllers\TurnoConfigController::class,
            'action' => 'actualizar',
            'middleware' => ['auth']
        ],
    ],
];

// Definición de rutas dinámicas con parámetros
$dynamicRoutes = [
    'GET' => [
        '/instructor-fichas/instructor/(\d+)' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'verInstructor',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/instructor-fichas/ficha/(\d+)' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'verFicha',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/fichas/(\d+)' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'show',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/fichas/(\d+)/editar' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'edit',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/aprendices/(\d+)' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'show',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/aprendices/(\d+)/editar' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'edit',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/gestion-instructores/(\d+)/editar' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'edit',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/fichas/(\d+)' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiShow',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/fichas/(\d+)/aprendices' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiAprendices',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/aprendices/(\d+)' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiShow',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/instructor-fichas/fichas-disponibles/(\d+)' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'getFichasDisponibles',
            'middleware' => ['auth'],
            'params' => ['instructorId']
        ],
        '/api/instructor-fichas/instructores-disponibles/(\d+)' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'getInstructoresDisponibles',
            'middleware' => ['auth'],
            'params' => ['fichaId']
        ],
        '/api/instructor-fichas/instructor/(\d+)/fichas' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'getFichasInstructor',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/instructor-fichas/ficha/(\d+)/instructores' => [
            'controller' => \App\Controllers\InstructorFichaController::class,
            'action' => 'getInstructoresFicha',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
    ],
    'POST' => [
        '/fichas/(\d+)' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'update',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/fichas/(\d+)/eliminar' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'delete',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/aprendices/(\d+)' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'update',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/aprendices/(\d+)/eliminar' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'delete',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/gestion-instructores/(\d+)' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'update',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/gestion-instructores/(\d+)/eliminar' => [
            'controller' => \App\Controllers\GestionInstructoresController::class,
            'action' => 'delete',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/fichas/(\d+)/estado' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiCambiarEstado',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/aprendices/(\d+)/estado' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiCambiarEstado',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/aprendices/(\d+)/vincular' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiVincularFicha',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/aprendices/(\d+)/desvincular' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiDesvincularFicha',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
    ],
    'PUT' => [
        '/api/fichas/(\d+)' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiUpdate',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/aprendices/(\d+)' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiUpdate',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
    ],
    'DELETE' => [
        '/api/fichas/(\d+)' => [
            'controller' => \App\Controllers\FichaController::class,
            'action' => 'apiDelete',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
        '/api/aprendices/(\d+)' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'apiDelete',
            'middleware' => ['auth'],
            'params' => ['id']
        ],
    ],
];

// Buscar primero en rutas estáticas
$route = $routes[$requestMethod][$uri] ?? null;
$params = [];

// Si no se encuentra, buscar en rutas dinámicas
if (!$route && isset($dynamicRoutes[$requestMethod])) {
    foreach ($dynamicRoutes[$requestMethod] as $pattern => $routeData) {
        $regex = '#^' . $pattern . '$#';
        if (preg_match($regex, $uri, $matches)) {
            $route = $routeData;
            // Extraer parámetros (omitir el primer elemento que es la coincidencia completa)
            array_shift($matches);
            $params = $matches;
            break;
        }
    }
}

if (!$route) {
    Response::notFound();
}

// Aplicar middleware de autenticación si es necesario
if (in_array('auth', $route['middleware'])) {
    $authMiddleware->handle();
}

// Aplicar validación de permisos basada en rol (RBAC) para todas las rutas resueltas
// Incluye rutas estáticas y dinámicas, con matriz centralizada en config/permissions_config.php
$permissionMiddleware->authorize($requestMethod, $uri);

// Instanciar controlador y ejecutar acción
try {
    $controllerClass = $route['controller'];
    
    // Si el controlador es un Closure, ejecutarlo directamente
    if ($controllerClass instanceof Closure) {
        $controllerClass();
        exit;
    }
    
    // Inyectar dependencias según el controlador
    if ($controllerClass === AuthController::class) {
        $controller = new $controllerClass($authService, $session);
    } elseif ($controllerClass === DashboardController::class) {
        $controller = new $controllerClass(
            $authService,
            $fichaRepository,
            $aprendizRepository,
            $userRepository
        );
    } elseif ($controllerClass === \App\Controllers\FichaController::class) {
        $controller = new $controllerClass(
            $fichaRepository,
            $aprendizRepository,
            $authService
        );
    } elseif ($controllerClass === \App\Controllers\AprendizController::class) {
        $controller = new $controllerClass(
            $aprendizRepository,
            $fichaRepository,
            $authService
        );
    } elseif ($controllerClass === \App\Controllers\AsistenciaController::class) {
        $controller = new $controllerClass(
            $asistenciaService,
            $authService,
            $fichaRepository,
            $aprendizRepository
        );
    } elseif ($controllerClass === QRController::class) {
        $controller = new $controllerClass(
            $asistenciaService,
            $authService,
            $qrService,
            $aprendizRepository,
            $fichaRepository,
            $instructorFichaRepository,
            $turnoConfigService
        );
    } elseif ($controllerClass === HomeController::class) {
        $controller = new $controllerClass(
            $aprendizRepository,
            $qrService
        );
    } elseif ($controllerClass === ProfileController::class) {
        $controller = new $controllerClass(
            $authService,
            $session
        );
    } elseif ($controllerClass === WelcomeController::class) {
        $controller = new $controllerClass();
    } elseif ($controllerClass === \App\Controllers\InstructorFichaController::class) {
        // Inicializar repositorios y servicios necesarios
        $instructorFichaService = new \App\Services\InstructorFichaService(
            $instructorFichaRepository,
            $userRepository,
            $fichaRepository
        );
        $controller = new $controllerClass(
            $instructorFichaService,
            $authService,
            $userRepository,
            $fichaRepository
        );
    } elseif ($controllerClass === \App\Controllers\TurnoConfigController::class) {
        $controller = new $controllerClass(
            $turnoConfigService,
            $authService
        );
    } elseif ($controllerClass === \App\Controllers\GestionInstructoresController::class) {
        $instructorRepository = new \App\Repositories\InstructorRepository();
        $instructorService = new \App\Services\InstructorService($instructorRepository);
        $controller = new $controllerClass(
            $instructorService,
            $instructorRepository,
            $authService
        );
    } else {
        throw new RuntimeException("Unknown controller: {$controllerClass}");
    }
    
    $action = $route['action'];
    
    if (!method_exists($controller, $action)) {
        throw new RuntimeException("Action {$action} not found in controller {$controllerClass}");
    }
    
    // Ejecutar la acción con parámetros si existen
    if (!empty($params)) {
        call_user_func_array([$controller, $action], $params);
    } else {
        $controller->$action();
    }
    
} catch (Exception $e) {
    error_log('Router error: ' . $e->getMessage());
    
    if (defined('APP_ENV') && APP_ENV === 'local') {
        echo '<pre>';
        echo 'Router Error: ' . $e->getMessage() . "\n";
        echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo '</pre>';
    } else {
        Response::serverError();
    }
}

