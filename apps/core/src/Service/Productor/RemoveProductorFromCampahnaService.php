<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\CampahnaRepository;
use App\apps\core\Repository\ProductorCampahnaRepository;
use App\apps\core\Repository\ProductorRepository;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\NotFoundException;

/**
 * Servicio para remover un productor de una campaña.
 * No elimina la relación, solo la desactiva para mantener historial.
 */
final readonly class RemoveProductorFromCampahnaService
{
    public function __construct(
        private ProductorRepository $productorRepository,
        private CampahnaRepository $campahnaRepository,
        private ProductorCampahnaRepository $productorCampahnaRepository,
    ) {
    }

    public function execute(string $productorId, string $campahnaId): void
    {
        $this->validate($productorId, $campahnaId);

        $productor = $this->productorRepository->ofId($productorId, true);
        $campahna = $this->campahnaRepository->ofId($campahnaId, true);

        $productorCampahna = $this->productorCampahnaRepository->findByProductorAndCampahna($productor, $campahna);

        if (!$productorCampahna) {
            throw new NotFoundException('El productor no está asignado a esta campaña');
        }

        // Desactivar la relación (soft delete)
        $productorCampahna->disable();
        $this->productorCampahnaRepository->save($productorCampahna);
    }

    private function validate(string $productorId, string $campahnaId): void
    {
        if (empty($productorId)) {
            throw new MissingParameterException('Missing parameter productorId');
        }

        if (empty($campahnaId)) {
            throw new MissingParameterException('Missing parameter campahnaId');
        }
    }
}
