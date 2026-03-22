<?php

namespace App\apps\core\Service\Campahna;

use App\apps\core\Repository\CampahnaRepository;
use App\shared\Doctrine\UidType;

final readonly class GetSharedCampahnaService
{
    public function __construct(
        private CampahnaRepository $campahnaRepository,
    ) {
    }

    public function execute(): array
    {

        $items = $this->campahnaRepository->allActive();

        return array_map(function ($item) {
            return [
                'id' => UidType::toString($item['id']),
                'nombre' => $item['nombre'],
                'fechaInicio' => $item['fechaInicio'],
            ];
        }, $items);
    }
}
