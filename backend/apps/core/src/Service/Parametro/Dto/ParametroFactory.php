<?php

namespace App\apps\core\Service\Parametro\Dto;

use App\apps\core\Entity\Parametro;
use App\apps\core\Repository\ParametroRepository;

final readonly class ParametroFactory
{
    public function __construct(
        private ParametroRepository $parametroRepository,
    ) {
    }

    public function ofDto(?ParametroDto $dto): ?Parametro
    {
        if (null === $dto) {
            return null;
        }

        $parametro = new Parametro();
        $this->updateOfDto($dto, $parametro);

        return $parametro;
    }

    public function updateOfDto(ParametroDto $dto, Parametro $parametro): void
    {
        $parametro->setName($dto->name);
        $parametro->setAlias($dto->alias);
        $parametro->setValue($dto->value ? (string) $dto->value : null);
        $parametro->setParent($dto->parentId
            ? $this->parametroRepository->ofId($dto->parentId)
            : null
        );

        match ($dto->isActive) {
            false => $parametro->disable(),
            default => $parametro->enable(),
        };
    }
}
