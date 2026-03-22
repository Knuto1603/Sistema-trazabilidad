<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Repository\ParametroRepository;
use App\apps\core\Service\Parametro\Dto\ParametroDtoTransformer;
use App\apps\core\Service\Parametro\Filter\ParametroFilterDto;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

readonly class GetParametrosService
{
    public function __construct(
        protected ParametroRepository $repository,
        protected FilterService $filterService,
        protected ParametroDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(FilterDto|ParametroFilterDto $filterDto): array
    {
        // filters
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'parametro.name',
            'parametro.alias',
            'parent.name',
            'parent.alias',
        ]));

        $this->newFilters($filterDto);

        // sortings
        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'name' => 'parametro.name',
            'alias' => 'parametro.alias',
            'parent' => 'parent.name',
        ]));

        // pagination
        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }

    protected function newFilters(FilterDto|ParametroFilterDto $filterDto): void
    {
    }
}
