<?php

namespace App\apps\security\Service\User\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateMeDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(min: 2, max: 100)]
        public ?string $fullname = null,

        #[Assert\Length(min: 6, max: 100)]
        public ?string $password = null,

        #[Assert\EqualTo(propertyPath: 'password', message: 'Las contraseñas no coinciden.')]
        public ?string $passwordConfirm = null,
    ) {
    }
}
