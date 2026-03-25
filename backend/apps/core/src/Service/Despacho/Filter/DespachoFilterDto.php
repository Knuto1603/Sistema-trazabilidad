<?php

namespace App\apps\core\Service\Despacho\Filter;

use App\shared\Service\Dto\FilterDto;

class DespachoFilterDto extends FilterDto
{
    public function __construct(
        int $page = 0,
        int $itemsPerPage = 10,
        ?string $search = null,
        ?string $sort = null,
        ?string $direction = null,
        public ?string $clienteId = null,
        public ?string $frutaId = null,
        public ?string $sede = null,
    ) {
        parent::__construct($page, $itemsPerPage, $search, $sort, $direction);
    }
}
