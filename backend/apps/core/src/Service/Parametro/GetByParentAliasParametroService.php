<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Repository\ParametroRepository;
use App\shared\Doctrine\UidType;

final readonly class GetByParentAliasParametroService
{
    public function __construct(
        private ParametroRepository $parametroRepository,
    ) {
    }

    public function execute(string $parentAlias): array
    {
        $items = $this->parametroRepository->findByParentAlias(strtoupper($parentAlias));

        return array_values(array_map(
            fn($item) => [
                'id'    => UidType::toString($item->uuid()),
                'name'  => $item->getName(),
                'alias' => $item->getAlias(),
            ],
            array_filter($items, fn($item) => $item->isActive())
        ));
    }
}
