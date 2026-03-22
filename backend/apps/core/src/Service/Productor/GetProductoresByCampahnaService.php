<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\ProductorCampahnaRepository;
use App\apps\core\Service\Productor\Dto\ProductorDtoTransformer;
use App\apps\core\Service\Productor\Filter\ProductorFilterDto;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetProductoresByCampahnaService
{
    public function __construct(
        protected FilterService $filterService,
        protected ProductorCampahnaRepository $repository,
        protected ProductorDtoTransformer $dtoTransformer,
    )
    {
    }

    public function execute(string $campahnaId, FilterDto $filterDto): array
    {
        // Crear filtro específico para la campaña
        $campahnaFilterDto = new ProductorFilterDto(
            page: $filterDto->page,
            itemsPerPage: $filterDto->itemsPerPage,
            search: $filterDto->search,
            sort: $filterDto->sort,
            direction: $filterDto->direction,
            campahnaId: $campahnaId
        );

        // Apply filters
        $this->filterService->addFilter(new PaginationFilter($campahnaFilterDto->page, $campahnaFilterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($campahnaFilterDto->search, [
            'productor.nombre',
            'productor.codigo',
            'productor.clp',
        ]));

        // Filtro por campaña a través de ProductorCampahna
        $this->filterService->addCondition(
            'campahna.uuid = :campahnaId',
            ['campahnaId' => $campahnaId]
        );

        // Solo productores activos en la campaña
        $this->filterService->addCondition(
            'productorCampahna.isActive = :isActive',
            ['isActive' => true]
        );

        // Sortings
        $sorting = SortingDto::create($campahnaFilterDto->sort, $campahnaFilterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'nombre' => 'productor.nombre',
            'codigo' => 'productor.codigo',
            'clp' => 'productor.clp',
            'createdAt' => 'productorCampahna.createdAt',
            'fechaIngreso' => 'productorCampahna.fechaIngreso',
        ]));

        // Establecer contexto de campaña para el transformer
        $this->dtoTransformer->setCampahnaContext($campahnaId);

        // Pagination - Ahora trabaja con ProductorCampahna
        $paginator = $this->repository->paginateAndFilter($this->filterService);

        // Transformar: extraer el Productor de cada ProductorCampahna
        $productores = [];
        foreach ($paginator->getIterator() as $productorCampahna) {
            $productores[] = $productorCampahna->getProductor();
        }

        $items = $this->dtoTransformer->fromObjects($productores);

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
