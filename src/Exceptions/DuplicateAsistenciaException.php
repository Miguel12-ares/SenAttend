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
    private ?string $nombreAprendiz;

    public function __construct(int $aprendizId, int $fichaId, string $fecha, ?string $nombreAprendiz = null, string $message = null)
    {
        $this->aprendizId = $aprendizId;
        $this->fichaId = $fichaId;
        $this->fecha = $fecha;
        $this->nombreAprendiz = $nombreAprendiz;

        // Usar nombre completo si está disponible, sino usar ID
        $aprendizLabel = $nombreAprendiz ? $nombreAprendiz : "ID {$aprendizId}";
        $defaultMessage = "El aprendiz {$aprendizLabel} ya se encuentra registrado para esta fecha";
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

    public function getNombreAprendiz(): ?string
    {
        return $this->nombreAprendiz;
    }

    public function toArray(): array
    {
        return [
            'error' => 'DUPLICATE_ASISTENCIA',
            'message' => $this->getMessage(),
            'aprendiz_id' => $this->aprendizId,
            'aprendiz_nombre' => $this->nombreAprendiz,
            'ficha_id' => $this->fichaId,
            'fecha' => $this->fecha,
        ];
    }
}
