<?php

namespace App\apps\core\Service\Campahna\Filter;

use Symfony\Component\Validator\Constraints as Assert;

class CampahnaFilterDto
{
    public function __construct(
        #[Assert\GreaterThanOrEqual(0)]
        public int $page = 0,

        #[Assert\GreaterThan(0)]
        public int $itemsPerPage = 5,

        public ?string $search = null,
        public ?string $sort = null,
        public ?string $direction = null,

        public ?string $periodoId = null, // Filtrar por período
        public ?string $frutaId = null,   // Filtrar por fruta
        public ?bool $isActive = null,    // Filtrar por estado activo
    ) {
    }
}
