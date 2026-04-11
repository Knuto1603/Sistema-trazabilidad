<?php

namespace App\apps\core\Service\CuentasCobrar\Filter;

class CuentasCobrarFilterDto
{
    public function __construct(
        public int $page = 0,
        public int $itemsPerPage = 20,
        public ?string $search = null,
        public ?string $sede = null,
        public ?string $operacionId = null,
        public ?string $clienteId = null,
        public ?string $estado = null,    // PENDIENTE | PAGADO | VENCIDA
    ) {}
}
