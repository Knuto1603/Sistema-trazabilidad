<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Entity\ProductorCampahna;
use App\apps\core\Repository\CampahnaRepository;
use App\apps\core\Repository\ProductorCampahnaRepository;
use App\apps\core\Repository\ProductorRepository;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

/**
 * Servicio para asignar un productor existente a una campaña.
 * Permite que un productor maestro participe en múltiples campañas.
 */
final readonly class AssignProductorToCampahnaService
{
    public function __construct(
        private ProductorRepository $productorRepository,
        private CampahnaRepository $campahnaRepository,
        private ProductorCampahnaRepository $productorCampahnaRepository,
    ) {
    }

    public function execute(string $productorId, string $campahnaId): ProductorCampahna
    {
        $this->validate($productorId, $campahnaId);

        $productor = $this->productorRepository->ofId($productorId, true);
        $campahna = $this->campahnaRepository->ofId($campahnaId, true);

        // Verificar si ya existe la relación
        $existingRelation = $this->productorCampahnaRepository->findByProductorAndCampahna($productor, $campahna);

        if ($existingRelation) {
            // Si existe pero está inactiva, reactivarla
            if (!$existingRelation->isActivo()) {
                $existingRelation->enable();
                $existingRelation->setFechaIngreso(new \DateTime());
                $this->productorCampahnaRepository->save($existingRelation);
            }
            return $existingRelation;
        }

        // Crear nueva relación
        $productorCampahna = new ProductorCampahna();
        $productorCampahna->setProductor($productor);
        $productorCampahna->setCampahna($campahna);
        $productorCampahna->setFechaIngreso(new \DateTime());

        $this->productorCampahnaRepository->save($productorCampahna);

        return $productorCampahna;
    }

    /**
     * Asigna múltiples productores a una campaña
     * @param string[] $productorIds
     * @return ProductorCampahna[]
     */
    public function executeMultiple(array $productorIds, string $campahnaId): array
    {
        $results = [];
        foreach ($productorIds as $productorId) {
            $results[] = $this->execute($productorId, $campahnaId);
        }
        return $results;
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
