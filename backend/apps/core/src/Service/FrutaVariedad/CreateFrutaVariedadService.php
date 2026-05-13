<?php

namespace App\apps\core\Service\FrutaVariedad;

use App\apps\core\Entity\FrutaVariedad;
use App\apps\core\Repository\FrutaVariedadRepository;
use App\apps\core\Repository\FrutaRepository;
use App\apps\core\Service\FrutaVariedad\Dto\FrutaVariedadDto;
use App\apps\core\Service\FrutaVariedad\Dto\FrutaVariedadFactory;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

final readonly class CreateFrutaVariedadService
{
    public function __construct(
        private FrutaVariedadFactory $factory,
        private FrutaVariedadRepository $repository,
        private FrutaRepository $frutaRepository,
    ) {}

    public function execute(string $frutaId, FrutaVariedadDto $dto): FrutaVariedad
    {
        $dto->frutaId = $frutaId;
        $this->isValid($dto);

        $variedad = $this->factory->ofDto($dto);
        $this->repository->save($variedad);
        return $variedad;
    }

    private function isValid(FrutaVariedadDto $dto): void
    {
        if (null === $dto->nombre) {
            throw new MissingParameterException('Missing parameter nombre');
        }

        $fruta = $this->frutaRepository->ofId($dto->frutaId, true);

        $existing = $this->repository->findOneBy(['nombre' => $dto->nombre, 'fruta' => $fruta]);
        if (null !== $existing) {
            throw new RepositoryException(sprintf('La variedad "%s" ya existe para esta fruta', $dto->nombre));
        }
    }
}
