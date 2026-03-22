<?php

namespace App\shared\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class FilterDto
{
    public function __construct(
        #[Assert\GreaterThanOrEqual(0)]
        public int $page = 0,

        #[Assert\GreaterThan(0)]
        public int $itemsPerPage = 5,

        public ?string $search = null,
        public ?string $sort = null,
        public ?string $direction = null,
    ) {
    }
}