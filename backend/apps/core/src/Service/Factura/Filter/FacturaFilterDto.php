<?php

namespace App\apps\core\Service\Factura\Filter;

use App\shared\Service\Dto\FilterDto;

class FacturaFilterDto extends FilterDto
{
    public function __construct(
        int $page = 0,
        int $itemsPerPage = 10,
        ?string $search = null,
        ?string $sort = null,
        ?string $direction = null,
        public ?string $despachoId = null,
        public ?string $tipoDocumento = null,
    ) {
        parent::__construct($page, $itemsPerPage, $search, $sort, $direction);
    }
}
