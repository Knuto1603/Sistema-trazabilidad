<?php

namespace App\apps\security\Service\User\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;

final class UserDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\Length(min: 4, max: 30)]
        public ?string $username = null,

        #[Assert\Length(min: 4, max: 100)]
        public ?string $fullname = null,

        #[Assert\Length(min: 0, max: 100)]
        public ?string $password = null,

        /**
         * @var array<string>|null Lista de UUIDs de UserRole
         */
        #[Assert\All([
            new Uid()
        ])]
        public ?array $roles = null,

        #[Uid]
        public ?string $gender = null,

        public ?string $photo = null,
        public ?string $photoUrl = null,
    ) {
    }
}
