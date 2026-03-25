<?php

namespace App\apps\core\Service\TipoCambio;

use App\apps\core\Entity\TipoCambio;
use App\apps\core\Repository\TipoCambioRepository;
use App\shared\Exception\NotFoundException;

final readonly class GetTipoCambioByFechaService
{
    public function __construct(
        private TipoCambioRepository $tipoCambioRepository,
    ) {
    }

    public function execute(string $fecha): TipoCambio
    {
        $fechaObj = new \DateTime($fecha);
        $tc = $this->tipoCambioRepository->findByFecha($fechaObj);

        if (null === $tc) {
            throw new NotFoundException(\sprintf('No se encontró tipo de cambio para la fecha %s', $fecha));
        }

        return $tc;
    }
}
