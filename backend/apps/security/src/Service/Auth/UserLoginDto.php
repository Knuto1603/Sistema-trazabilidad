<?php

namespace App\apps\security\Service\Auth;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Validator\Uid;

final class UserLoginDto implements DtoRequestInterface
{
    public function __construct(
        #[Uid]
        public ?string $id = null,
        public ?string $username = null,
        public ?string $fullname = null,
        /** @var array|null */
        public ?array $roles = null,
        public ?string $avatar = null,
    ) {
    }
}
