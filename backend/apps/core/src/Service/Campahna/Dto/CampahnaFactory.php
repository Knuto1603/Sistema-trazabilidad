<?php

namespace App\apps\core\Service\Campahna\Dto;

use App\apps\core\Entity\Campahna;
use App\apps\core\Repository\FrutaRepository;


final readonly class CampahnaFactory
{
    public function __construct(
        private FrutaRepository $frutaRepository,
    ) {
    }

    public function ofDto(CampahnaDto $campahnaDto): Campahna
    {
        $campahna = new Campahna();
        $this->updateOfDto($campahnaDto, $campahna);

        return $campahna;
    }

    public function updateOfDto(CampahnaDto $campahnaDto, Campahna $campahna): void
    {
        $campahna->setNombre($campahnaDto->nombre);
        $campahna->setDescripcion($campahnaDto->descripcion);

        // Conversión de fechas de string (ISO) a objeto DateTime
        if ($campahnaDto->fechaInicio) {
            $campahna->setFechaInicio(new \DateTime($campahnaDto->fechaInicio));
        }

        if ($campahnaDto->fechaFin) {
            $campahna->setFechaFin(new \DateTime($campahnaDto->fechaFin));
        } else {
            $campahna->setFechaFin(null);
        }

        // Relación con Fruta (Producto)
        if ($campahnaDto->frutaId) {
            $fruta = $this->frutaRepository->ofId($campahnaDto->frutaId);
            $campahna->setFruta($fruta);
        }
    }
}
