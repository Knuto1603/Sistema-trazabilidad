<?php

namespace App\apps\core\Service\Operacion\Dto;

use App\apps\core\Entity\Operacion;

final readonly class OperacionFactory
{
    public function ofDto(OperacionDto $dto): Operacion
    {
        $operacion = new Operacion();
        $this->updateOfDto($dto, $operacion);

        return $operacion;
    }

    public function updateOfDto(OperacionDto $dto, Operacion $operacion): void
    {
        $operacion->setNombre($dto->nombre);
        $operacion->setSede($dto->sede);

        match ($dto->isActive) {
            false => $operacion->disable(),
            default => $operacion->enable(),
        };
    }
}
