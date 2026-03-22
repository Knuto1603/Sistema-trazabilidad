<?php

namespace App\apps\core\Service\Productor\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductorDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\Length(exactly: 4)]
        public ?string $codigo = null,

        #[Assert\Length(min: 2, max: 100)]
        public ?string $nombre = null,

        #[Assert\Regex(
            pattern: '/^[0-9]{3}-[0-9]{5}-[0-9]{2}$/',
            message: 'El campo debe tener el formato XXX-XXXXX-XX (ej: 002-08004-02)'
        )]
        public ?string $clp = null,

        #[Assert\Regex(
            pattern: '/^[0-9]\.[0-9]{4}$/',
            message: 'El campo debe tener el formato x.xxxx (ej: 1.2345)'
        )]
        public ?string $mtdCeratitis = null,

        #[Assert\Regex(
            pattern: '/^[0-9]\.[0-9]{4}$/',
            message: 'El campo debe tener el formato x.xxxx (ej: 1.2345)'
        )]
        public ?string $mtdAnastrepha = null,

        #[Assert\NotBlank]
        #[Uid]
        public ?string $campahnaId = null,

        // Campos adicionales para mostrar información de la campaña
        public ?string $campahnaName = null,
        public ?string $frutaName = null,
        public ?string $periodoName = null,
    )
    {
    }
}
