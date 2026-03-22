<?php

namespace App\apps\core\Service\Parametro\Filter;

use Symfony\Component\Validator\Constraints as Assert;

class ParametroFilterDto
{
    public function __construct(
        #[Assert\GreaterThanOrEqual(0)]
        public int $page = 0,

        #[Assert\GreaterThan(0)]
        public int $itemsPerPage = 5,

        public ?string $search = null,
        public ?string $sort = null,
        public ?string $direction = null,

        public ?string $parentId = null, // Id del padre
    ) {
    }
}
