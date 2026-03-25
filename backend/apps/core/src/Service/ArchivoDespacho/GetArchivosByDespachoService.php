<?php

namespace App\apps\core\Service\ArchivoDespacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Service\ArchivoDespacho\Dto\ArchivoDespachoDtoTransformer;

final readonly class GetArchivosByDespachoService
{
    public function __construct(
        private ArchivoDespachoRepository $archivoDespachoRepository,
        private ArchivoDespachoDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(string $despachoUuid): array
    {
        $archivos = $this->archivoDespachoRepository->findByDespachoUuid($despachoUuid);

        return array_map(fn ($a) => $this->dtoTransformer->fromObject($a), $archivos);
    }
}
