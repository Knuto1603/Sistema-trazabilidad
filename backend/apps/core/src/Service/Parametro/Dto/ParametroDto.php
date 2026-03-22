<?php

namespace App\apps\core\Service\Parametro\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;


final class ParametroDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\Length(min: 2, max: 100)]
        public ?string $name = null,

        #[Assert\Length(min: 0, max: 6)]
        public ?string $alias = null,

        #[Assert\Length(min: 0, max: 12)]
        public string|float|null $value = null,

        #[Uid]
        public ?string $parentId = null,
        public ?string $parentName = null,
    ) {
    }
}
