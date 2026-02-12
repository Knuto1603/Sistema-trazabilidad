<?php

namespace App\apps\security\Service\UserRole\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class UserRoleDto implements DtoRequestInterface
{
    use DtoTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public ?string $name = null,

        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public ?string $alias = null,

        /**
         * @var array|null Lista de UUIDs de usuarios asignados a este rol
         */
        public ?array $userIds = null,

        /**
         * Contador de usuarios que tienen este rol
         */
        public ?int $userCount = null,

    ) {
    }
}
