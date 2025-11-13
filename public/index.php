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

// Definición de rutas
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
        // Aprendices
        '/aprendices' => [
            'controller' => \App\Controllers\AprendizController::class,
            'action' => 'index',
            'middleware' => ['auth']
        ],
        // Asistencia (CRÍTICO)
        '/asistencia/registrar' => [
            'controller' => \App\Controllers\AsistenciaController::class,
            'action' => 'registrar',
            'middleware' => ['auth']
        ],
    ],
    'POST' => [
        '/auth/login' => [
            'controller' => AuthController::class,
            'action' => 'login',
            'middleware' => []
        ],
        // Asistencia (CRÍTICO)
        '/asistencia/guardar' => [
            'controller' => \App\Controllers\AsistenciaController::class,
            'action' => 'guardar',
            'middleware' => ['auth']
        ],
    ],
];

// Buscar la ruta
$route = $routes[$requestMethod][$uri] ?? null;

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
    
    // Ejecutar la acción
    $controller->$action();
    
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

