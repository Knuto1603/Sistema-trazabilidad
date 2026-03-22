<?php

namespace App\shared\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class PaginationDto
{
    public const PAGE_DEFAULT = 0;
    public const LIMIT_DEFAULT = 5;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThanOrEqual(0)]
        public int $page,

        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $itemsPerPage,
    ) {
    }

    public static function create(?int $page, ?int $itemsPerPage): static
    {
        return new static(
            $page ?? self::PAGE_DEFAULT,
            $itemsPerPage ?? self::LIMIT_DEFAULT,
        );
    }
}