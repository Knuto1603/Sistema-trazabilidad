<?php

namespace App\apps\core\Service\TipoCambio\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class TipoCambioDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        public ?string $fecha = null,

        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public ?float $compra = null,

        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public ?float $venta = null,
    ) {
    }
}
