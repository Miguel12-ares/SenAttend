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
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;
use App\Repositories\FichaRepository;
use App\Repositories\AprendizRepository;
use App\Services\AuthService;
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
$asistenciaRepository = new \App\Repositories\AsistenciaRepository();
$authService = new AuthService($userRepository, $session);
$asistenciaService = new \App\Services\AsistenciaService($asistenciaRepository, $aprendizRepository, $fichaRepository);
$authMiddleware = new AuthMiddleware($session);

// Definición de rutas estáticas
$routes = [
    'GET' => [
        '/' => [
            'controller' => DashboardController::class,
            'action' => 'index',
            'middleware' => ['auth']
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
        // Asistencia (CRÍTICO)
        '/asistencia/registrar' => [
            'controller' => \App\Controllers\AsistenciaController::class,
            'action' => 'registrar',
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
    ],
    'POST' => [
        '/auth/login' => [
            'controller' => AuthController::class,
            'action' => 'login',
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
    ],
];

// Definición de rutas dinámicas con parámetros
$dynamicRoutes = [
    'GET' => [
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
            $fichaRepository
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

