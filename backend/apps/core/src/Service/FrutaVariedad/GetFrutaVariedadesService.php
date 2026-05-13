<?php

namespace App\apps\core\Service\FrutaVariedad;

use App\apps\core\Repository\FrutaRepository;
use App\apps\core\Repository\FrutaVariedadRepository;
use App\apps\core\Service\FrutaVariedad\Dto\FrutaVariedadDtoTransformer;

final readonly class GetFrutaVariedadesService
{
    public function __construct(
        private FrutaVariedadRepository $repository,
        private FrutaRepository $frutaRepository,
        private FrutaVariedadDtoTransformer $transformer,
    ) {}

    public function execute(string $frutaId): array
    {
        $fruta = $this->frutaRepository->ofId($frutaId, true);
        $items = $this->repository->byFruta($fruta->getId());
        return ['items' => $this->transformer->fromObjects($items)];
    }
}
