<?php

namespace App\apps\core\Service\Productor\Filter;

use Symfony\Component\Validator\Constraints as Assert;

class ProductorFilterDto
{
    public function __construct(
        #[Assert\GreaterThanOrEqual(0)]
        public int $page = 0,

        #[Assert\GreaterThan(0)]
        public int $itemsPerPage = 5,

        public ?string $search = null,
        public ?string $sort = null,
        public ?string $direction = null,

        public ?string $campahnaId = null, // Filtrar por campaña
        public ?string $frutaId = null,    // Filtrar por fruta (a través de campaña)
        public ?string $periodoId = null,  // Filtrar por período (a través de campaña)
        public ?bool $isActive = null,     // Filtrar por estado activo
    ) {
    }
}
