<?php

namespace App\apps\core\Service\Campahna;

use App\apps\core\Entity\Campahna;
use App\apps\core\Repository\CampahnaRepository;
use App\apps\core\Service\Campahna\Dto\CampahnaDto;
use App\apps\core\Service\Campahna\Dto\CampahnaFactory;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

final readonly class CreateCampahnaService
{
    public function __construct(
        private CampahnaRepository $campahnaRepository,
        private CampahnaFactory $campahnaFactory,
    ) {
    }

    public function execute(CampahnaDto $campahnaDto): Campahna
    {
        $this->isValid($campahnaDto);

        $campahna = $this->campahnaFactory->ofDto($campahnaDto);
        $this->campahnaRepository->save($campahna);

        return $campahna;
    }

    public function isValid(CampahnaDto $campahnaDto): void
    {
        if (null === $campahnaDto->nombre) {
            throw new MissingParameterException('Missing parameter nombre');
        }

        if (null === $campahnaDto->frutaId) {
            throw new MissingParameterException('Missing parameter frutaId');
        }

        if (null === $campahnaDto->fechaInicio) {
            throw new MissingParameterException('Missing parameter fechaInicio');
        }

        if ($this->campahnaRepository->existsByNombreFruta(
            $campahnaDto->nombre,
            $campahnaDto->frutaId
        )) {
            throw new RepositoryException(
                \sprintf(
                    'Ya existe una campaña con el nombre "%s" para esta fruta',
                    $campahnaDto->nombre
                )
            );
        }
    }
}
