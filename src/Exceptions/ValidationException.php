<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción para errores de validación de datos
 * Dev 2: AsistenciaService - Excepciones personalizadas
 */
class ValidationException extends RuntimeException
{
    private array $errors;
    private string $field;

    public function __construct(string $field, string $message, array $errors = [])
    {
        $this->field = $field;
        $this->errors = $errors;
        
        parent::__construct($message);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $error): void
    {
        $this->errors[$field] = $error;
    }

    public function toArray(): array
    {
        return [
            'error' => 'VALIDATION_ERROR',
            'message' => $this->getMessage(),
            'field' => $this->field,
            'errors' => $this->errors,
        ];
    }

    /**
     * Crea una excepción de validación con múltiples errores
     */
    public static function withMultipleErrors(array $errors, string $message = 'Errores de validación'): self
    {
        $exception = new self('multiple', $message, $errors);
        return $exception;
    }
}
