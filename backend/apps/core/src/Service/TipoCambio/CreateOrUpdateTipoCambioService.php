<?php

namespace App\apps\core\Service\TipoCambio;

use App\apps\core\Entity\TipoCambio;
use App\apps\core\Repository\TipoCambioRepository;
use App\apps\core\Service\TipoCambio\Dto\TipoCambioDto;
use App\apps\core\Service\TipoCambio\Dto\TipoCambioFactory;

final readonly class CreateOrUpdateTipoCambioService
{
    public function __construct(
        private TipoCambioRepository $tipoCambioRepository,
        private TipoCambioFactory $tipoCambioFactory,
    ) {
    }

    public function execute(TipoCambioDto $dto): TipoCambio
    {
        $fecha = new \DateTime($dto->fecha);
        $existing = $this->tipoCambioRepository->findByFecha($fecha);

        if ($existing) {
            $this->tipoCambioFactory->updateOfDto($dto, $existing);
            $this->tipoCambioRepository->save($existing);

            return $existing;
        }

        $tipoCambio = $this->tipoCambioFactory->ofDto($dto);
        $this->tipoCambioRepository->save($tipoCambio);

        return $tipoCambio;
    }
}
