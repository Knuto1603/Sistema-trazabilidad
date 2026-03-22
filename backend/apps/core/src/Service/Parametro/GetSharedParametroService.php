<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Repository\ParametroRepository;
use App\shared\Doctrine\UidType;

final readonly class GetSharedParametroService
{
    public function __construct(
        private ParametroRepository $parametroRepository,
    ) {
    }

    public function execute(): iterable
    {
        $items = $this->parametroRepository->allShared();

        return array_map(function ($item) {
            return [
                'id' => UidType::toString($item['id']),
                'name' => $item['name'],
                'alias' => $item['alias'],
                'parentAlias' => $item['parentAlias'],
            ];
        }, $items);
    }
}
