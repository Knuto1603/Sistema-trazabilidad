<?php

namespace App\apps\core\Service\Operacion;

use App\apps\core\Repository\OperacionRepository;
use App\apps\core\Service\Operacion\Dto\OperacionDtoTransformer;
use App\shared\Service\Dto\FilterDto;

final readonly class GetOperacionesService
{
    public function __construct(
        private OperacionRepository $operacionRepository,
        private OperacionDtoTransformer $transformer,
    ) {
    }

    public function execute(?string $sede = null): array
    {
        $operaciones = $sede !== null
            ? $this->operacionRepository->findBySede($sede)
            : $this->operacionRepository->findAllActive();

        return [
            'items' => array_map(
                fn ($o) => $this->transformer->fromObject($o),
                $operaciones
            ),
        ];
    }
}
