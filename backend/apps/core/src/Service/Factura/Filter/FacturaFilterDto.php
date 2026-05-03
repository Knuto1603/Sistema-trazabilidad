<?php

namespace App\apps\core\Service\Factura\Filter;

use App\shared\Service\Dto\FilterDto;

class FacturaFilterDto extends FilterDto
{
    public function __construct(
        int $page = 0,
        int $itemsPerPage = 25,
        ?string $search = null,
        ?string $sort = null,
        ?string $direction = null,
        public ?string $despachoId = null,
        public ?string $tipoDocumento = null,
        public ?bool $isAnulada = null,
        public ?string $tipoServicio = null,
        public ?string $fechaDesde = null,
        public ?string $fechaHasta = null,
    ) {
        parent::__construct($page, $itemsPerPage, $search, $sort, $direction);
    }
}
