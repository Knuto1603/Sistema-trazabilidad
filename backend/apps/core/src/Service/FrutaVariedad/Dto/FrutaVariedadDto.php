<?php

namespace App\apps\core\Service\FrutaVariedad\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class FrutaVariedadDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public ?string $nombre = null,

        // frutaId viene por URL, no por body; se asigna en el service
        public ?string $frutaId = null,

        public ?string $frutaNombre = null,
    ) {}
}
