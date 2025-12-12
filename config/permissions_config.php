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
if (!defined('ROLE_ADMINISTRATIVO')) {
    define('ROLE_ADMINISTRATIVO', 'administrativo');
}
if (!defined('ROLE_ESTUDIANTE')) {
    define('ROLE_ESTUDIANTE', 'estudiante');
}
if (!defined('ROLE_PORTERO')) {
    define('ROLE_PORTERO', 'portero');
}
if (!defined('ROLE_APRENDIZ')) {
    define('ROLE_APRENDIZ', 'aprendiz');
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
                '/dashboard' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],

                // Fichas
                '/fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/fichas/crear' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],

                // Aprendices
                '/aprendices' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/aprendices/crear' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],


                // Módulo QR:
                // - Generar QR: típicamente para aprendices, pero se permite a cualquier autenticado
                '/qr/generar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR, ROLE_ESTUDIANTE],
                // - Escanear QR: EXCLUSIVO de instructores (coordinador/admin no pueden acceder)
                '/qr/escanear' => [ROLE_INSTRUCTOR],

                // Perfil
                '/perfil' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR, ROLE_ESTUDIANTE, ROLE_PORTERO, ROLE_APRENDIZ],
                // Gestión de Reportes - solo instructores
                '/gestion-reportes' => [ROLE_INSTRUCTOR],
                // Analítica y Reportes - admin y administrativo
                '/analytics' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // Gestión de Asignaciones Instructor-Ficha
                // Comentario en el controlador indica: solo Admin y Administrativo (y coordinador)
                '/instructor-fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // APIs varias (se asume acceso de staff/docente, no aprendices)
                '/api/instructor-fichas/estadisticas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/api/instructores' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                '/api/fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/api/fichas/search' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/api/fichas/estadisticas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],

                '/api/aprendices' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/api/aprendices/estadisticas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],

                // Gestión de Instructores
                '/gestion-instructores' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-instructores/crear' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-instructores/importar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // Gestión de Porteros
                '/gestion-porteros' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-porteros/crear' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-porteros/importar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-porteros/plantilla-csv' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-porteros/exportar-csv' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // Historial diario de asistencia via QR (ver QRController)
                '/api/qr/historial-diario' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/api/qr/buscar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],

                // Configuración de turnos (solo admin en UI)
                '/configuracion/horarios' => [ROLE_ADMIN],

                // APIs de configuración de turnos: lectura para staff (no estudiantes)
                '/api/configuracion/turnos' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],
                '/api/configuracion/turno-actual' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR, ROLE_ADMINISTRATIVO],

                // Módulo Portero - Gestión de equipos
                '/portero/panel' => [ROLE_PORTERO],
                '/portero/escanear' => [ROLE_PORTERO],

                // Panel de Aprendiz
                '/aprendiz/panel' => [ROLE_APRENDIZ],
                '/aprendiz/equipos' => [ROLE_APRENDIZ],
                '/aprendiz/equipos/crear' => [ROLE_APRENDIZ],
                '/aprendiz/asistencias' => [ROLE_APRENDIZ],
                '/aprendiz/generar-qr' => [ROLE_APRENDIZ],
            ],
            'POST' => [
                // Auth
                '/auth/login' => [],

                // Perfil
                '/perfil/cambiar-password' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR, ROLE_ESTUDIANTE, ROLE_PORTERO, ROLE_APRENDIZ],

                // API pública de validación de aprendiz (sin login)
                '/api/public/aprendiz/validar' => [],

                // Fichas
                '/fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/api/fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/api/fichas/importar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/api/fichas/validar-csv' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // Aprendices
                '/aprendices' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/aprendices/importar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/api/aprendices' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                '/api/aprendices/importar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/api/aprendices/validar-csv' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/api/aprendices/vincular-multiples' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // Gestión de Instructores POST
                '/gestion-instructores' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-instructores/importar-csv' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // Gestión de Porteros POST
                '/gestion-porteros' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/gestion-porteros/importar-csv' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // Asistencia
                '/asistencia/guardar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],

                // Asignación Instructor-Ficha
                '/api/instructor-fichas/asignar-fichas' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                '/api/instructor-fichas/asignar-instructores' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                '/api/instructor-fichas/sincronizar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                '/api/instructor-fichas/eliminar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],

                // QR
                '/api/qr/procesar' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],

                // Configuración de turnos (solo admin)
                '/configuracion/horarios/actualizar' => [ROLE_ADMIN],
                // Gestión de Reportes - generación de exportes vía AJAX (solo instructores)
                '/gestion-reportes/generar' => [ROLE_INSTRUCTOR],
                // Analítica - Generación de reportes (admin y administrativo)
                '/analytics/generar-semanal' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                '/analytics/generar-mensual' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],

                // API Portero - Gestión de equipos
                '/api/portero/procesar-qr' => [ROLE_PORTERO],
                '/api/portero/ingresos-activos' => [ROLE_PORTERO],

                // Aprendiz - Gestión de equipos
                '/aprendiz/equipos' => [ROLE_APRENDIZ],
            ],
            // PUT y DELETE se manejan en 'patterns' porque tienen parámetros dinámicos
        ],

        // Rutas dinámicas basadas en patrones (regex)
        'patterns' => [
            'GET' => [
                [
                    'pattern' => '#^/instructor-fichas/instructor/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/instructor-fichas/ficha/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/fichas/(\d+)/editar$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)/editar$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/gestion-instructores/(\d+)/editar$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/gestion-porteros/(\d+)/editar$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/fichas/(\d+)/aprendices$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/fichas-disponibles/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/instructores-disponibles/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/instructor/(\d+)/fichas$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/api/instructor-fichas/ficha/(\d+)/instructores$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_ADMINISTRATIVO],
                ],
            ],
            'POST' => [
                [
                    'pattern' => '#^/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/fichas/(\d+)/eliminar$#',
                    'roles' => [ROLE_ADMIN],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO, ROLE_INSTRUCTOR],
                ],
                [
                    'pattern' => '#^/aprendices/(\d+)/eliminar$#',
                    'roles' => [ROLE_ADMIN],
                ],
                [
                    'pattern' => '#^/gestion-instructores/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/gestion-instructores/(\d+)/eliminar$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/gestion-porteros/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/gestion-porteros/(\d+)/eliminar$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/fichas/(\d+)/estado$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)/estado$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)/vincular$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)/desvincular$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
            ],
            'PUT' => [
                [
                    'pattern' => '#^/api/fichas/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
                ],
                [
                    'pattern' => '#^/api/aprendices/(\d+)$#',
                    'roles' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
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


