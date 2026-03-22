<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\ProductorRepository;
use App\apps\core\Service\Productor\Dto\ProductorDtoTransformer;
use App\apps\core\Service\Productor\Filter\ProductorFilterDto;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetProductoresService
{
    public function __construct(
        protected FilterService $filterService,
        protected ProductorRepository $repository,
        protected ProductorDtoTransformer $dtoTransformer,
    )
    {
    }

    public function execute(FilterDto|ProductorFilterDto $filterDto): array
    {
        // Apply filters
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'productor.nombre',
            'productor.codigo',
            'productor.clp',
            'campahna.nombre',
            'fruta.nombre',
            'periodo.nombre',
        ]));

        $this->newFilters($filterDto);

        // Sortings
        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'nombre' => 'productor.nombre',
            'codigo' => 'productor.codigo',
            'clp' => 'productor.clp',
            'campahna' => 'campahna.nombre',
            'fruta' => 'fruta.nombre',
            'periodo' => 'periodo.nombre',
            'createdAt' => 'productor.createdAt',
        ]));

        // Pagination
        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }

    protected function newFilters(FilterDto|ProductorFilterDto $filterDto): void
    {
        if ($filterDto instanceof ProductorFilterDto) {
            // Filtro por campaña
            if ($filterDto->campahnaId) {
                $this->filterService->addCondition(
                    'campahna.uuid = :campahnaId',
                    ['campahnaId' => UidType::fromString($filterDto->campahnaId)]
                );
            }

            // Filtro por fruta (a través de campaña)
            if ($filterDto->frutaId) {
                $this->filterService->addCondition(
                    'fruta.uuid = :frutaId',
                    ['frutaId' => UidType::fromString($filterDto->frutaId)]
                );
            }

            // Filtro por período (a través de campaña)
            if ($filterDto->periodoId) {
                $this->filterService->addCondition(
                    'periodo.uuid = :periodoId',
                    ['periodoId' => UidType::fromString($filterDto->periodoId)]
                );
            }

            // Filtro por estado activo
            if (null !== $filterDto->isActive) {
                $this->filterService->addCondition(
                    'productor.isActive = :isActive',
                    ['isActive' => $filterDto->isActive]
                );
            }
        }
    }
}
