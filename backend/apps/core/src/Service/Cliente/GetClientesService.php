<?php

namespace App\apps\core\Service\Cliente;

use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Service\Cliente\Dto\ClienteDtoTransformer;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetClientesService
{
    public function __construct(
        protected FilterService $filterService,
        protected ClienteRepository $repository,
        protected ClienteDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(FilterDto $filterDto): array
    {
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'cliente.ruc',
            'cliente.razonSocial',
            'cliente.nombreComercial',
        ]));

        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'ruc' => 'cliente.ruc',
            'razonSocial' => 'cliente.razonSocial',
            'createdAt' => 'cliente.createdAt',
        ]));

        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
