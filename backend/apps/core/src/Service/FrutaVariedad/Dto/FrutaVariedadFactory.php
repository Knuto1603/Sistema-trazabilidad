<?php

namespace App\apps\core\Service\FrutaVariedad\Dto;

use App\apps\core\Entity\FrutaVariedad;
use App\apps\core\Repository\FrutaRepository;

final readonly class FrutaVariedadFactory
{
    public function __construct(
        private FrutaRepository $frutaRepository,
    ) {}

    public function ofDto(FrutaVariedadDto $dto): FrutaVariedad
    {
        $variedad = new FrutaVariedad();
        $this->updateOfDto($dto, $variedad);
        return $variedad;
    }

    public function updateOfDto(FrutaVariedadDto $dto, FrutaVariedad $variedad): void
    {
        $variedad->setNombre($dto->nombre);

        if ($dto->frutaId) {
            $fruta = $this->frutaRepository->ofId($dto->frutaId, true);
            $variedad->setFruta($fruta);
        }
    }
}
