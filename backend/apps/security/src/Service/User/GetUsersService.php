<?php

namespace App\apps\security\Service\User;

use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\User\Dto\UserDtoTransformer;
use App\apps\security\Service\User\Dto\UserFilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetUsersService
{
    public function __construct(
        private UserRepository $userRepository,
        private FilterService $filterService,
        private UserDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(UserFilterDto $filterDto): array
    {
        // filters
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, ['user.username', 'user.fullName']));

        // sortings
        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'username' => 'user.username',
            'fullname' => 'user.fullName',
        ]));

        // pagination
        $paginator = $this->userRepository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
