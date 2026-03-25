<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Service\Factura\Dto\FacturaDtoTransformer;
use App\apps\core\Service\Factura\Filter\FacturaFilterDto;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use App\shared\Service\Dto\SortingDto;
use App\shared\Service\Filter\ConditionFilter;
use App\shared\Service\Filter\PaginationFilter;
use App\shared\Service\Filter\SearchTextFilter;
use App\shared\Service\FilterService;
use App\shared\Service\Sorting\SortByRequestField;

final readonly class GetFacturasService
{
    public function __construct(
        protected FilterService $filterService,
        protected FacturaRepository $repository,
        protected FacturaDtoTransformer $dtoTransformer,
    ) {
    }

    public function execute(FilterDto|FacturaFilterDto $filterDto): array
    {
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, [
            'factura.numeroDocumento',
            'factura.serie',
            'factura.contenedor',
            'cliente.razonSocial',
        ]));

        if ($filterDto instanceof FacturaFilterDto) {
            if ($filterDto->despachoId) {
                $this->filterService->addFilter(new ConditionFilter(
                    'despacho.uuid = :despachoId',
                    ['despachoId' => UidType::fromString($filterDto->despachoId)]
                ));
            }

            if ($filterDto->tipoDocumento) {
                $this->filterService->addFilter(new ConditionFilter(
                    'factura.tipoDocumento = :tipoDocumento',
                    ['tipoDocumento' => $filterDto->tipoDocumento]
                ));
            }
        }

        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'fechaEmision' => 'factura.fechaEmision',
            'numeroDocumento' => 'factura.numeroDocumento',
            'createdAt' => 'factura.createdAt',
        ]));

        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
