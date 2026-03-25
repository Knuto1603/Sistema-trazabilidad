<?php

namespace App\apps\core\Service\TipoCambio;

use App\apps\core\Repository\TipoCambioRepository;
use App\apps\core\Service\TipoCambio\Dto\TipoCambioDtoTransformer;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetTipoCambiosService
{
    public function __construct(
        protected FilterService $filterService,
        protected TipoCambioRepository $repository,
        protected TipoCambioDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(FilterDto $filterDto): array
    {
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));

        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'fecha' => 'tipoCambio.fecha',
        ]));

        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
