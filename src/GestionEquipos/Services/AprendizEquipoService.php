<?php

namespace App\GestionEquipos\Services;

use App\GestionEquipos\Repositories\AprendizEquipoRepository;

class AprendizEquipoService
{
    private AprendizEquipoRepository $aprendizEquipoRepository;

    public function __construct(AprendizEquipoRepository $aprendizEquipoRepository)
    {
        $this->aprendizEquipoRepository = $aprendizEquipoRepository;
    }

    /**
     * Devuelve los equipos del aprendiz en una estructura directa para la vista.
     */
    public function getEquiposDeAprendiz(int $aprendizId): array
    {
        return $this->aprendizEquipoRepository->findEquiposByAprendiz($aprendizId);
    }
}


