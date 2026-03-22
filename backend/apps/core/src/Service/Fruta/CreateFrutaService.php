<?php

namespace App\apps\core\Service\Fruta;

use App\apps\core\Entity\Fruta;
use App\apps\core\Repository\FrutaRepository;
use App\apps\core\Service\Fruta\Dto\FrutaDto;
use App\apps\core\Service\Fruta\Dto\FrutaFactory;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

final readonly class CreateFrutaService
{
    public function __construct(
        private FrutaFactory $frutaFactory,
        private FrutaRepository $repository,
    ) {
    }

    public function execute(FrutaDto $dto): ?Fruta
    {
        $this->isValid($dto);
        $fruta = $this->frutaFactory->ofDto($dto);
        $this->repository->save($fruta);
        return $fruta;
    }
    private function isValid(FrutaDto $dto): void
    {
        if(null === $dto->nombre){
            throw new MissingParameterException('Missing parameter nombre  ');
        }
        if(null === $dto->codigo){
            throw new MissingParameterException('Missing parameter código  ');
        }

        if(null !== $this->repository->findOneBy(['nombre' => $dto->nombre])){
            throw new RepositoryException(\sprintf('Nombre %s ya existe', $dto->nombre));
        }

        if(null !== $this->repository->findOneBy(['codigo' => $dto->codigo])){
            throw new RepositoryException(\sprintf('Código %s ya existe', $dto->codigo));
        }
    }

}
