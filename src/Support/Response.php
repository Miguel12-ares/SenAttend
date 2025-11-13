<?php

namespace App\Support;

/**
 * Helper para respuestas HTTP y JSON
 */
class Response
{
    /**
     * Envía una respuesta JSON
     */
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envía una respuesta JSON de éxito
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Envía una respuesta JSON de error
     */
    public static function error(string $message = 'Error', int $statusCode = 400, mixed $errors = null): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Redirige a una URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Envía una respuesta con código de estado
     */
    public static function status(int $statusCode): void
    {
        http_response_code($statusCode);
    }

    /**
     * Renderiza una vista
     */
    public static function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            self::notFound();
        }

        require $viewPath;
        exit;
    }

    /**
     * Respuesta 404 Not Found
     */
    public static function notFound(string $message = 'Page not found'): void
    {
        http_response_code(404);
        require __DIR__ . '/../../views/errors/404.php';
        exit;
    }

    /**
     * Respuesta 500 Internal Server Error
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        http_response_code(500);
        require __DIR__ . '/../../views/errors/500.php';
        exit;
    }

    /**
     * Respuesta 403 Forbidden
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        http_response_code(403);
        echo "<h1>403 Forbidden</h1><p>{$message}</p>";
        exit;
    }
}

