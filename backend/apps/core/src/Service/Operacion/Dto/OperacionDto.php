<?php

namespace App\apps\core\Service\Operacion\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class OperacionDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $nombre = null,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['SULLANA', 'TAMBOGRANDE', 'GENERAL'])]
        public ?string $sede = null,
    ) {
    }
}
