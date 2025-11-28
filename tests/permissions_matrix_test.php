<?php

/**
 * Script sencillo de inspección de la matriz de permisos.
 *
 * Uso (desde la raíz del proyecto):
 *   php tests/permissions_matrix_test.php
 *
 * No ejecuta peticiones reales, pero permite visualizar rápidamente
 * qué roles están autorizados para cada ruta configurada.
 */

$configPath = __DIR__ . '/../config/permissions_config.php';

if (!file_exists($configPath)) {
    echo "No se encontró config/permissions_config.php\n";
    exit(1);
}

$config = require $configPath;
$permissions = $config['permissions'] ?? [];

echo "=== MATRIZ DE PERMISOS (RUTAS EXACTAS) ===\n\n";

foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
    if (empty($permissions['exact'][$method])) {
        continue;
    }

    echo "Método: {$method}\n";
    foreach ($permissions['exact'][$method] as $route => $roles) {
        $rolesLabel = empty($roles) ? 'PÚBLICA (sin rol requerido)' : implode(', ', $roles);
        echo "  {$route} -> {$rolesLabel}\n";
    }
    echo "\n";
}

echo "=== MATRIZ DE PERMISOS (PATRONES DINÁMICOS) ===\n\n";

foreach (['GET', 'POST'] as $method) {
    if (empty($permissions['patterns'][$method])) {
        continue;
    }

    echo "Método: {$method}\n";
    foreach ($permissions['patterns'][$method] as $patternConfig) {
        $pattern = $patternConfig['pattern'] ?? '(sin pattern)';
        $roles = $patternConfig['roles'] ?? [];
        $rolesLabel = empty($roles) ? 'PÚBLICA (sin rol requerido)' : implode(', ', $roles);
        echo "  {$pattern} -> {$rolesLabel}\n";
    }
    echo "\n";
}

echo "Fin de la inspección de permisos.\n";


