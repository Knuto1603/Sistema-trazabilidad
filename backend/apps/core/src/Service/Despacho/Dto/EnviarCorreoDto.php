<?php

namespace App\apps\core\Service\Despacho\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class EnviarCorreoDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        public ?string $asunto = null,

        #[Assert\NotBlank]
        public ?string $cuerpo = null,

        #[Assert\NotBlank]
        public ?string $destinatarios = null,

        /** @var string[] */
        public array $archivosIds = [],
    ) {
    }
}
