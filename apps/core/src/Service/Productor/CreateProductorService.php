<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Entity\Productor;
use App\apps\core\Entity\ProductorCampahna;
use App\apps\core\Repository\CampahnaRepository;
use App\apps\core\Repository\ProductorCampahnaRepository;
use App\apps\core\Repository\ProductorRepository;
use App\apps\core\Service\Productor\Dto\ProductorDto;
use App\apps\core\Service\Productor\Dto\ProductorFactory;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

final readonly class CreateProductorService
{
    public function __construct(
        private ProductorRepository $productorRepository,
        private ProductorFactory $productorFactory,
        private CampahnaRepository $campahnaRepository,
        private ProductorCampahnaRepository $productorCampahnaRepository,
    )
    {
    }

    public function execute(ProductorDto $productorDto): Productor
    {
        $this->isValid($productorDto);

        // 1. Crear el productor maestro
        $productor = $this->productorFactory->ofDto($productorDto);
        $this->productorRepository->save($productor);

        // 2. Crear la relación ProductorCampahna
        $campahna = $this->campahnaRepository->ofId($productorDto->campahnaId);

        $productorCampahna = new ProductorCampahna();
        $productorCampahna->setProductor($productor);
        $productorCampahna->setCampahna($campahna);
        $productorCampahna->setFechaIngreso(new \DateTime());

        $this->productorCampahnaRepository->save($productorCampahna);

        return $productor;
    }

    public function isValid(ProductorDto $productorDto): void
    {
        if (null === $productorDto->nombre) {
            throw new MissingParameterException('Missing parameter nombre');
        }

        if (null === $productorDto->campahnaId) {
            throw new MissingParameterException('Missing parameter campahnaId');
        }

        if (null === $productorDto->codigo) {
            throw new MissingParameterException('Missing parameter codigo');
        }

        if (null === $productorDto->clp) {
            throw new MissingParameterException('Missing parameter clp');
        }

        // Verificar que el CLP no exista (es único global)
        if (null !== $this->productorRepository->findOneBy(['clp' => $productorDto->clp])) {
            throw new RepositoryException(\sprintf('CLP %s ya existe', $productorDto->clp));
        }

        // Verificar que el código no exista en la misma campaña
        $campahna = $this->campahnaRepository->ofId($productorDto->campahnaId);

        $existingProductor = $this->productorCampahnaRepository->createQueryBuilder('pc')
            ->join('pc.productor', 'p')
            ->join('pc.campahna', 'c')
            ->where('p.codigo = :codigo')
            ->andWhere('c.id = :campahnaId')
            ->setParameter('codigo', $productorDto->codigo)
            ->setParameter('campahnaId', $campahna->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $existingProductor) {
            throw new RepositoryException(\sprintf('Código %s ya existe en esta campaña', $productorDto->codigo));
        }
    }
}
