<?php

namespace App\apps\core\Service\Campahna\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;

final class CampahnaDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public ?string $nombre = null,

        #[Assert\Length(max: 255)]
        public ?string $descripcion = null,

        #[Assert\NotBlank]
        #[Assert\Date]
        public ?string $fechaInicio = null,

        #[Assert\Date]
        public ?string $fechaFin = null,

        #[Assert\NotBlank]
        #[Uid]
        public ?string $frutaId = null,

        // Campos complementarios para respuestas (Output)
        public ?string $frutaNombre = null,
        public ?string $nombreCompleto = null,
    ) {
    }
}
