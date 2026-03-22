<?php

namespace App\apps\core\Service\Campahna;

use App\apps\core\Repository\CampahnaRepository;
use App\apps\core\Service\Campahna\Dto\CampahnaDtoTransformer;
use App\apps\core\Service\Campahna\Filter\CampahnaFilterDto;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

readonly class GetCampahmasService
{
    public function __construct(
        protected CampahnaRepository     $repository,
        protected FilterService          $filterService,
        protected CampahnaDtoTransformer $dtoTransformer,
    )
    {
    }

    public function execute(FilterDto|CampahnaFilterDto $filterDto): array
    {
        // filters
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'campahna.nombre',
            'campahna.descripcion',
            'fruta.nombre',
        ]));

        //$this->newFilters($filterDto);

        // sortings
        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'nombre' => 'campahna.nombre',
            'descripcion' => 'campahna.descripcion',
            'fruta' => 'fruta.nombre',
            'createdAt' => 'campahna.createdAt',
        ]));

        // pagination
        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}

/*
    protected function newFilters(FilterDto|CampahnaFilterDto $filterDto): void
    {
        if ($filterDto instanceof CampahnaFilterDto) {
            // Filtro por período
            if ($filterDto->periodoId) {
                $this->filterService->addFilter(
                    'periodo.uuid = :periodoId',
                    ['periodoId' => UidType::fromString($filterDto->periodoId)]
                );
            }

            // Filtro por fruta
            if ($filterDto->frutaId) {
                $this->filterService->addFilter(
                    'fruta.uuid = :frutaId',
                    ['frutaId' => UidType::fromString($filterDto->frutaId)]
                );
            }

            // Filtro por estado activo
            if (null !== $filterDto->isActive) {
                $this->filterService->addCondition(
                    'campahna.isActive = :isActive',
                    ['isActive' => $filterDto->isActive]
                );
            }
        }
    }
}
*/
