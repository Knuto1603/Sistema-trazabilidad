<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\ProductorRepository;
use App\apps\core\Service\Productor\Dto\ProductorDtoTransformer;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetProductorContextService
{

    public function __construct(
        protected FilterService $filterService,
        protected ProductorRepository $repository,
        protected ProductorDtoTransformer $dtoTransformer,
    )
    {
    }

    public function execute(FilterDto $filterDto, array $context): array
    {
        // Apply filters
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'productor.name',
            'productor.clp',
        ]));


        // Sortings
        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'name' => 'productor.name',
            'clp' => 'productor.clp',
        ]));

        // Pagination
        $paginator = $this->repository->contextPaginateAndFilter($this->filterService, $context['periodo'], $context['fruta']);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
