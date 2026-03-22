<?php

namespace App\apps\core\Service\Campahna;

use App\apps\core\Repository\CampahnaRepository;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadCampahmasService
{
    public function __construct(
        private CampahnaRepository $repository,
        private FilterService $filterService,
    ) {
    }

    public function execute(FilterDto $filterDto): Response
    {
        // Aplicar filtros sin paginación para descargar todo
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'campahna.nombre',
            'campahna.descripcion',
            'fruta.nombre',
        ]));

        $data = $this->repository->downloadAndFilter($this->filterService);

        $headers = [
            'nombre' => 'Nombre',
            'descripcion' => 'Descripción',
            'frutaNombre' => 'Fruta',
            'isActive' => 'Activo',
            'createdAt' => 'Fecha de Creación',
        ];

        return $this->excelDownloadService->download(
            'campahnas.xlsx',
            $data,
            $headers
        );
    }
}
