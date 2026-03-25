<?php

namespace App\apps\core\Service\Cliente\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;

final class ClienteDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(exactly: 11)]
        public ?string $ruc = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $razonSocial = null,

        public ?string $nombreComercial = null,
        public ?string $direccion = null,
        public ?string $departamento = null,
        public ?string $provincia = null,
        public ?string $distrito = null,
        public ?string $estado = null,
        public ?string $condicion = null,
        public ?string $tipoContribuyente = null,
        public ?string $telefono = null,
        public ?string $email = null,
    ) {
    }
}
