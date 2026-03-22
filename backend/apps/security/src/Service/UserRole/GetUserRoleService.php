<?php

namespace App\apps\security\Service\UserRole;

use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRoleRepository;
use App\shared\Exception\NotFoundException;

final readonly class GetUserRoleService
{
    public function __construct(
        private UserRoleRepository $repository,
    ) {
    }

    public function execute(string $id, bool $strict = false): ?UserRole
    {
        $user = $this->repository->ofId($id);
        if (true === $strict && null === $user) {
            throw new NotFoundException();
        }

        return $user;
    }
}
