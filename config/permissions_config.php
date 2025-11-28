<?php

/**
 * Configuración centralizada de permisos por ruta (RBAC)
 *
 * - Define constantes de roles
 * - Matriz de permisos: método + ruta (o patrón) => roles permitidos
 * - Helpers para verificar permisos desde cualquier parte del sistema
 *
 * IMPORTANTE:
 * - Todas las rutas críticas deben estar aquí.
 * - Para rutas dinámicas, usar patrones REGEX (clave 'pattern' => '#^/ruta/(\d+)$#').
 */

// Constantes de roles
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}
if (!defined('ROLE_INSTRUCTOR')) {
    define('ROLE_INSTRUCTOR', 'instructor');
}
if (!defined('ROLE_COORDINADOR')) {
    define('ROLE_COORDINADOR', 'coordinador');
}
if (!defined('ROLE_ESTUDIANTE')) {
    define('ROLE_ESTUDIANTE', 'estudiante');
}
if (!defined('ROLE_ADMINISTRATIVO')) {
    define('ROLE_ADMINISTRATIVO', 'administrativo');
}

return [
    /**
     * Matriz de permisos:
     *
     * 'exact' => rutas estáticas (coincidencia exacta de URI)
     * 'patterns' => rutas dinámicas con parámetros, usando regex
     *
     * Clave de primer nivel: método HTTP (GET, POST, PUT, DELETE, '*')
     */
    'permissions' => [
        'exact' => [
            'GET' => [
                // Públicas
                '/' => [],
                '/home' => [],
                '/login' => [],
                '/auth/logout' => [], // Logout es público (cualquiera puede cerrar sesión)

                // Dashboard general (usuarios autenticados típicos)
                '/dashboard' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],

                // Fichas
                '/fichas' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
                '/fichas/crear' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                // Aprendices
                '/aprendices' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
                '/aprendices/crear' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                // Asistencia manual
                '/asistencia/registrar' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                // Módulo QR:
                // - Generar QR: típicamente para aprendices, pero se permite a cualquier autenticado
                '/qr/generar' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ESTUDIANTE],
                // - Escanear QR: EXCLUSIVO de instructores (coordinador/admin no pueden acceder)
                '/qr/escanear' => [ROLE_INSTRUCTOR],

                // Perfil
                '/perfil' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ESTUDIANTE, ROLE_ADMINISTRATIVO],

                // Gestión de Asignaciones Instructor-Ficha
                // Comentario en el controlador indica: solo Admin y Administrativo (y coordinador)
                '/instructor-fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],

                // APIs varias (se asume acceso de staff/docente, no aprendices)
                '/api/instructor-fichas/estadisticas' => [ROLE_ADMIN, ROLE_COORDINADOR],
                '/api/instructores' => [ROLE_ADMIN, ROLE_COORDINADOR],

                '/api/fichas' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
                '/api/fichas/search' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
                '/api/fichas/estadisticas' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                '/api/aprendices' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
                '/api/aprendices/estadisticas' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                // Historial diario de asistencia via QR (ver QRController)
                '/api/qr/historial-diario' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                '/api/qr/buscar' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                // Configuración de turnos (solo admin en UI)
                '/configuracion/horarios' => [ROLE_ADMIN],

                // APIs de configuración de turnos: lectura para staff (no estudiantes)
                '/api/configuracion/turnos' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
                '/api/configuracion/turno-actual' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
            ],
            'POST' => [
                // Auth
                '/auth/login' => [],

                // Perfil
                '/perfil/cambiar-password' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR, ROLE_ESTUDIANTE, ROLE_ADMINISTRATIVO],

                // API pública de validación de aprendiz (sin login)
                '/api/public/aprendiz/validar' => [],

                // Fichas
                '/fichas' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                '/api/fichas' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                '/api/fichas/importar' => [ROLE_ADMIN, ROLE_COORDINADOR],
                '/api/fichas/validar-csv' => [ROLE_ADMIN, ROLE_COORDINADOR],

                // Aprendices
                '/aprendices' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                '/aprendices/importar' => [ROLE_ADMIN, ROLE_COORDINADOR],
                '/api/aprendices' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                '/api/aprendices/importar' => [ROLE_ADMIN, ROLE_COORDINADOR],
                '/api/aprendices/validar-csv' => [ROLE_ADMIN, ROLE_COORDINADOR],
                '/api/aprendices/vincular-multiples' => [ROLE_ADMIN, ROLE_COORDINADOR],

                // Asistencia
                '/asistencia/guardar' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                // Asignación Instructor-Ficha
                '/api/instructor-fichas/asignar-fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                '/api/instructor-fichas/asignar-instructores' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                '/api/instructor-fichas/sincronizar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                '/api/instructor-fichas/eliminar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],

                // QR
                '/api/qr/procesar' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],

                // Configuración de turnos (solo admin)
                '/configuracion/horarios/actualizar' => [ROLE_ADMIN],
            ],
            // PUT y DELETE se manejan en 'patterns' porque tienen parámetros dinámicos
        ],

        // Rutas dinámicas basadas en patrones (regex)
        'patterns' => [
            'GET' => [
                [
                    'pattern' => '#^/instructor-fichas/instructor/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/instructor-fichas/ficha/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/fichas/(\d+)/editar$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)/editar$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/fichas/(\d+)/aprendices$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/fichas-disponibles/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/instructores-disponibles/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/instructor/(\d+)/fichas$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/ficha/(\d+)/instructores$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_COORDINADOR],
                ],
            ],
            'POST' => [
                [
                    'pattern' => '#^/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/fichas/(\d+)/eliminar$#',
                    'roles' => [ROLE_ADMIN],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)/eliminar$#',
                    'roles' => [ROLE_ADMIN],
                ],
                [
                    'pattern' => '#^/api/fichas/(\d+)/estado$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)/estado$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)/vincular$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)/desvincular$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR],
                ],
            ],
            'PUT' => [
                [
                    'pattern' => '#^/api/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_COORDINADOR],
                ],
            ],
            'DELETE' => [
                [
                    'pattern' => '#^/api/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN],
                ],
            ],
        ],
    ],
];

/**
 * FUNCIONES HELPER PARA VERIFICACIÓN DE PERMISOS
 * 
 * Estas funciones permiten verificar permisos desde cualquier parte del código
 * sin necesidad de instanciar el middleware directamente.
 */

/**
 * Verifica si un rol tiene permiso para acceder a una ruta
 * 
 * @param string $method Método HTTP (GET, POST, PUT, DELETE)
 * @param string $uri URI de la ruta
 * @param string $role Rol del usuario
 * @return bool true si el rol tiene permiso, false en caso contrario
 */
function route_allowed(string $method, string $uri, string $role): bool
{
    static $config = null;
    
    if ($config === null) {
        $config = require __DIR__ . '/permissions_config.php';
    }
    
    $method = strtoupper($method);
    $permissions = $config['permissions'] ?? [];
    
    // Verificar rutas exactas
    $exact = $permissions['exact'][$method] ?? [];
    if (array_key_exists($uri, $exact)) {
        $allowedRoles = $exact[$uri];
        // Si está vacío, es ruta pública
        if (empty($allowedRoles)) {
            return true;
        }
        return in_array($role, $allowedRoles, true);
    }
    
    // Verificar patrones
    $patterns = $permissions['patterns'][$method] ?? [];
    foreach ($patterns as $patternConfig) {
        if (!isset($patternConfig['pattern'], $patternConfig['roles'])) {
            continue;
        }
        
        if (preg_match($patternConfig['pattern'], $uri)) {
            return in_array($role, $patternConfig['roles'], true);
        }
    }
    
    // Ruta no mapeada: por compatibilidad, permitir acceso
    return true;
}

/**
 * Obtiene todos los roles permitidos para una ruta
 * 
 * @param string $method Método HTTP
 * @param string $uri URI de la ruta
 * @return array|null Array de roles permitidos, [] para pública, null si no está mapeada
 */
function get_allowed_roles_for_route(string $method, string $uri): ?array
{
    static $config = null;
    
    if ($config === null) {
        $config = require __DIR__ . '/permissions_config.php';
    }
    
    $method = strtoupper($method);
    $permissions = $config['permissions'] ?? [];
    
    // Verificar rutas exactas
    $exact = $permissions['exact'][$method] ?? [];
    if (array_key_exists($uri, $exact)) {
        return $exact[$uri];
    }
    
    // Verificar patrones
    $patterns = $permissions['patterns'][$method] ?? [];
    foreach ($patterns as $patternConfig) {
        if (!isset($patternConfig['pattern'], $patternConfig['roles'])) {
            continue;
        }
        
        if (preg_match($patternConfig['pattern'], $uri)) {
            return $patternConfig['roles'];
        }
    }
    
    return null;
}


