<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Entity\Productor;
use App\apps\core\Entity\ProductorCampahna;
use App\apps\core\Repository\CampahnaRepository;
use App\apps\core\Repository\ProductorCampahnaRepository;
use App\apps\core\Repository\ProductorRepository;
use App\apps\core\Service\Productor\Dto\ProductorDto;
use App\apps\core\Service\Productor\Dto\ProductorFactory;
use App\shared\Exception\RepositoryException;

final readonly class UpdateProductorService
{
    public function __construct(
        private ProductorRepository $productorRepository,
        private ProductorFactory $productorFactory,
        private CampahnaRepository $campahnaRepository,
        private ProductorCampahnaRepository $productorCampahnaRepository,
    ) {
    }

    public function execute(string $id, ProductorDto $productorDto): Productor
    {
        $productor = $this->productorRepository->ofId($id, true);
        $this->isValid($productorDto, $productor);
        $this->productorFactory->updateOfDto($productorDto, $productor);
        $this->productorRepository->save($productor);

        // Si cambió la campaña, actualizar la relación
        if ($productorDto->campahnaId) {
            $this->updateCampahnaRelation($productor, $productorDto->campahnaId);
        }

        return $productor;
    }

    private function updateCampahnaRelation(Productor $productor, string $campahnaId): void
    {
        $campahna = $this->campahnaRepository->ofId($campahnaId);

        // Verificar si ya existe la relación
        if (!$this->productorCampahnaRepository->existsProductorInCampahna($productor, $campahna)) {
            // Crear nueva relación
            $productorCampahna = new ProductorCampahna();
            $productorCampahna->setProductor($productor);
            $productorCampahna->setCampahna($campahna);
            $productorCampahna->setFechaIngreso(new \DateTime());

            $this->productorCampahnaRepository->save($productorCampahna);
        }
    }

    public function isValid(ProductorDto $productorDto, ?Productor $productor): void
    {
        // Verificar que el CLP no exista en otro productor
        if ($productor->getClp() !== $productorDto->clp) {
            $existingByCLP = $this->productorRepository->findOneBy(['clp' => $productorDto->clp]);
            if (null !== $existingByCLP && $existingByCLP->getId() !== $productor->getId()) {
                throw new RepositoryException(\sprintf('CLP %s ya existe', $productorDto->clp));
            }
        }

        // Verificar que el código no exista en la misma campaña (en otro productor)
        if ($productorDto->campahnaId) {
            $campahna = $this->campahnaRepository->ofId($productorDto->campahnaId);

            $existingProductor = $this->productorCampahnaRepository->createQueryBuilder('pc')
                ->join('pc.productor', 'p')
                ->join('pc.campahna', 'c')
                ->where('p.codigo = :codigo')
                ->andWhere('c.id = :campahnaId')
                ->andWhere('p.id != :currentId')
                ->setParameter('codigo', $productorDto->codigo)
                ->setParameter('campahnaId', $campahna->getId())
                ->setParameter('currentId', $productor->getId())
                ->getQuery()
                ->getOneOrNullResult();

            if (null !== $existingProductor) {
                throw new RepositoryException(\sprintf('Código %s ya existe en esta campaña', $productorDto->codigo));
            }
        }
    }
}
