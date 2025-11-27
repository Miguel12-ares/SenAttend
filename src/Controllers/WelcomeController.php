<?php

namespace App\Controllers;

use App\Support\Response;

/**
 * Controlador para la página de bienvenida pública
 * Muestra información sobre la aplicación y permite acceso a login y generación de QR
 */
class WelcomeController
{
    /**
     * Vista principal de bienvenida (pública)
     * GET /
     */
    public function index(): void
    {
        try {
            // Headers de seguridad
            $this->establecerHeadersSeguridad();

            // Incluir la vista
            require __DIR__ . '/../../views/welcome/index.php';
            
        } catch (\Exception $e) {
            error_log("Error en WelcomeController::index: " . $e->getMessage());
            Response::serverError();
        }
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
}

