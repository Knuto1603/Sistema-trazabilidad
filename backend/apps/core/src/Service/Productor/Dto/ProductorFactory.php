<?php

namespace App\apps\core\Service\Productor\Dto;

use App\apps\core\Entity\Productor;

final readonly class ProductorFactory
{
    public function ofDto(?ProductorDto $dto): ?Productor
    {
        if (null === $dto) {
            return null;
        }

        $productor = new Productor();
        $this->updateOfDto($dto, $productor);

        return $productor;
    }

    public function updateOfDto(ProductorDto $dto, Productor $productor): void
    {
        $productor->setCodigo($dto->codigo);
        $productor->setNombre($dto->nombre);
        $productor->setClp($dto->clp);
        $productor->setMtdCeratitis($dto->mtdCeratitis);
        $productor->setMtdAnastrepha($dto->mtdAnastrepha);
        $productor->setProductor($dto->nombre); // Campo productor = nombre por defecto

        match ($dto->isActive) {
            false => $productor->disable(),
            default => $productor->enable(),
        };
    }
}
