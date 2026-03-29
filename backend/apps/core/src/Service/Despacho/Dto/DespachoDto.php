<?php

namespace App\apps\core\Service\Despacho\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;

final class DespachoDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        public ?int $numeroCliente = null,
        public ?int $numeroPlanta = null,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['SULLANA', 'TAMBOGRANDE', 'GENERAL'])]
        public ?string $sede = null,

        public ?string $contenedor = null,
        public ?string $observaciones = null,

        #[Assert\NotBlank]
        #[Uid]
        public ?string $clienteId = null,

        #[Assert\NotBlank]
        #[Uid]
        public ?string $frutaId = null,

        #[Uid]
        public ?string $operacionId = null,

        public ?string $clienteRuc = null,
        public ?string $clienteRazonSocial = null,
        public ?string $frutaNombre = null,
        public ?string $operacionNombre = null,
    ) {
    }
}
