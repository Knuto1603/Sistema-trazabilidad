<?php

namespace App\apps\core\Service\Fruta;

use App\apps\core\Repository\FrutaRepository;
use App\apps\core\Service\Fruta\Dto\FrutaDtoTransformer;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

class GetFrutasService
{
    public function __construct(
        protected FilterService $filterService,
        protected FrutaRepository $repository,
        protected FrutaDtoTransformer $dtoTransformer,
    )
    {
    }

    public function execute(FilterDto $filterDto): array
    {
        // Apply filters
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'fruta.nombre',
            'fruta.codigo',
        ]));

        // Sortings
        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'nombre' => 'fruta.nombre',
            'codigo' => 'fruta.codigo',
        ]));

        // Pagination
        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }

}
