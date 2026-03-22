<?php

namespace App\apps\core\Service\Fruta\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use Symfony\Component\Validator\Constraints as Assert;


class FrutaDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(

        #[Assert\Length(min: 2, max: 5)]
        public ?string $codigo = null,

        #[Assert\Length(min: 2, max: 100)]
        public ?string $nombre = null,

    ) {
    }

}
