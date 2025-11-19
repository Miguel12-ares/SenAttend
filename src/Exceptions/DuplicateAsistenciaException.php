<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción para registros de asistencia duplicados
 * Dev 2: AsistenciaService - Excepciones personalizadas
 */
class DuplicateAsistenciaException extends RuntimeException
{
    private int $aprendizId;
    private int $fichaId;
    private string $fecha;

    public function __construct(int $aprendizId, int $fichaId, string $fecha, string $message = null)
    {
        $this->aprendizId = $aprendizId;
        $this->fichaId = $fichaId;
        $this->fecha = $fecha;

        $defaultMessage = "Ya existe un registro de asistencia para el aprendiz {$aprendizId} en la ficha {$fichaId} para la fecha {$fecha}";
        parent::__construct($message ?? $defaultMessage);
    }

    public function getAprendizId(): int
    {
        return $this->aprendizId;
    }

    public function getFichaId(): int
    {
        return $this->fichaId;
    }

    public function getFecha(): string
    {
        return $this->fecha;
    }

    public function toArray(): array
    {
        return [
            'error' => 'DUPLICATE_ASISTENCIA',
            'message' => $this->getMessage(),
            'aprendiz_id' => $this->aprendizId,
            'ficha_id' => $this->fichaId,
            'fecha' => $this->fecha,
        ];
    }
}
