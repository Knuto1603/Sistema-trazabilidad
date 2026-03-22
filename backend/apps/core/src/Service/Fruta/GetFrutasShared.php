<?php

namespace App\apps\core\Service\Fruta;

use App\apps\core\Repository\FrutaRepository;
use App\shared\Doctrine\UidType;

final readonly class GetFrutasShared
{
    public function __construct(
        private FrutaRepository $repository,
    )
    {
    }

    public function execute(): array
    {
        $items = $this->repository->allShared();
        return array_map(function ($item) {
            return [
                'id' => UidType::toString($item['id']),
                'nombre' => $item['nombre'],
                'codigo' => $item['codigo'],
            ];
        }, $items);
    }

}
