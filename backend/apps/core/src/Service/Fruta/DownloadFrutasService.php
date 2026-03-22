<?php

namespace App\apps\core\Service\Fruta;

use App\apps\core\Repository\FrutaRepository;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use CarlosChininin\Spreadsheet\Writer\OpenSpout\SpreadsheetWriter;
use Symfony\Component\HttpFoundation\Response;

class DownloadFrutasService
{

    public function __construct(
        private FrutaRepository $repository,
        private FilterService $filterService,
    )
    {
    }

    public function execute(FilterDto $filterDto): Response
    {
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'fruta.codigo',
            'fruta.nombre',
        ]));

        $items = $this->repository->downloadAndFilter($this->filterService);
        $export = new SpreadsheetWriter($items, $this->headers());

        return $export->execute()->download('export_frutas');
    }
    private function headers(): array
    {
        return [
            'Codigo',
            'Fruta',
            'Alias',
            'Activo',
        ];
    }
}
