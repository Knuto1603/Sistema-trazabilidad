<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Repository\ParametroRepository;
use App\shared\Doctrine\UidType;

final readonly class GetParentsParametroService
{
    public function __construct(
        private ParametroRepository $parametroRepository,
    ) {
    }

    public function execute(): iterable
    {
        $parents = $this->parametroRepository->allParents();

        return array_map(function ($item) {
            return [
                'id' => UidType::toString($item['id']),
                'name' => $item['name'],
            ];
        }, $parents);
    }
}
