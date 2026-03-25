<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Service\Despacho\Dto\DespachoDtoTransformer;
use App\apps\core\Service\Despacho\Filter\DespachoFilterDto;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\ConditionFilter;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetDespachosService
{
    public function __construct(
        protected FilterService $filterService,
        protected DespachoRepository $repository,
        protected DespachoDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(FilterDto|DespachoFilterDto $filterDto): array
    {
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'cliente.ruc',
            'cliente.razonSocial',
            'fruta.nombre',
            'despacho.contenedor',
        ]));

        if ($filterDto instanceof DespachoFilterDto) {
            if ($filterDto->clienteId) {
                $this->filterService->addFilter(new ConditionFilter(
                    'cliente.uuid = :clienteId',
                    ['clienteId' => UidType::fromString($filterDto->clienteId)]
                ));
            }

            if ($filterDto->frutaId) {
                $this->filterService->addFilter(new ConditionFilter(
                    'fruta.uuid = :frutaId',
                    ['frutaId' => UidType::fromString($filterDto->frutaId)]
                ));
            }

            if ($filterDto->sede) {
                $this->filterService->addFilter(new ConditionFilter(
                    'despacho.sede = :sede',
                    ['sede' => $filterDto->sede]
                ));
            }
        }

        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'numeroPlanta' => 'despacho.numeroPlanta',
            'numeroCliente' => 'despacho.numeroCliente',
            'sede' => 'despacho.sede',
            'createdAt' => 'despacho.createdAt',
        ]));

        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
