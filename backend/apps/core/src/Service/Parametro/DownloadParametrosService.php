<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Repository\ParametroRepository;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use CarlosChininin\Spreadsheet\Writer\OpenSpout\SpreadsheetWriter;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadParametrosService
{
    public function __construct(
        private ParametroRepository $repository,
        private FilterService $filterService,
    ) {
    }

    public function execute(FilterDto $filterDto): Response
    {
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'parametro.name',
            'parametro.alias',
            'parent.name',
            'parent.alias',
        ]));

        $items = $this->repository->downloadAndFilter($this->filterService);
        $export = new SpreadsheetWriter($items, $this->headers());

        return $export->execute()->download('export_parametros');
    }

    private function headers(): array
    {
        return [
            'Padre',
            'Nombre',
            'Alias',
            'Activo',
        ];
    }
}
