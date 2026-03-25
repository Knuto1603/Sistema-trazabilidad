<?php

namespace App\apps\core\Service\TipoCambio\Dto;

use App\apps\core\Entity\TipoCambio;

final readonly class TipoCambioFactory
{
    public function ofDto(?TipoCambioDto $dto): ?TipoCambio
    {
        if (null === $dto) {
            return null;
        }

        $tc = new TipoCambio();
        $this->updateOfDto($dto, $tc);

        return $tc;
    }

    public function updateOfDto(TipoCambioDto $dto, TipoCambio $tc): void
    {
        $tc->setFecha(new \DateTime($dto->fecha));
        $tc->setCompra($dto->compra);
        $tc->setVenta($dto->venta);
    }
}
