<?php

namespace App\apps\security\Service\UserRole;

use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRepository;
use App\apps\security\Repository\UserRoleRepository;
use App\apps\security\Service\User\Dto\UserDtoTransformer;
use App\apps\security\Service\UserRole\Dto\UserRoleDtoTransformer;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetUserRolesService
{
    public function __construct(
        private UserRoleRepository $repository,
        private FilterService $filterService,
        private UserRoleDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(FilterDto $filterDto): array
    {
        // filters
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, ['userRole.name', 'userRole.alias']));

        // sortings
        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'name' => 'userRole.name',
            'alias' => 'userRole.alias',
        ]));

        // pagination
        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
