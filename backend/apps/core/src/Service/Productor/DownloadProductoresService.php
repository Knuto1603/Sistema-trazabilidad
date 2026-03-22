<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\ProductorRepository;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Download\ExcelDownloadService;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadProductoresService
{
    public function __construct(
        private ProductorRepository $repository,
        private FilterService $filterService,
        private ExcelDownloadService $excelDownloadService,
    ) {
    }

    public function execute(FilterDto $filterDto): Response
    {
        // Aplicar filtros sin paginación para descargar todo
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'productor.nombre',
            'productor.codigo',
            'productor.clp',
            'campahna.nombre',
            'fruta.nombre',
            'periodo.nombre',
        ]));

        $data = $this->repository->downloadAndFilter($this->filterService);

        $headers = [
            'codigo' => 'Código',
            'nombre' => 'Nombre',
            'clp' => 'CLP',
            'mtdCeratitis' => 'Método Ceratitis',
            'mtdAnastrepha' => 'Método Anastrepha',
            'campahna' => 'Campaña',
            'fruta' => 'Fruta',
            'periodo' => 'Período',
            'isActive' => 'Activo',
            'createdAt' => 'Fecha de Creación',
        ];

        return $this->excelDownloadService->download(
            'productores.xlsx',
            $data,
            $headers
        );
    }
}
