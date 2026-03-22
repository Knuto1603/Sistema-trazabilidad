<?php

namespace App\apps\core\Service\Fruta\Dto;

use App\apps\core\Entity\Fruta;

class FrutaFactory
{
    public function __construct()
    {
    }
    public function ofDto(FrutaDto $dto): ?Fruta
    {
        if (null === $dto) {
            return null;
        }

        $fruta = new Fruta();
        $this->updateOfDto($dto, $fruta);

        return $fruta;
    }

    public function updateOfDto(FrutaDto $dto, Fruta $fruta): void
    {
        $fruta->setCodigo($dto->codigo);
        $fruta->setNombre($dto->nombre);

        match ($dto->isActive) {
            false => $fruta->disable(),
            default => $fruta->enable(),
        };
    }

}
