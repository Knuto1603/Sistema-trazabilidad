<?php

namespace App\apps\security\Service\UserRole;

use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRoleRepository;
use App\shared\Exception\NotFoundException;

final readonly class ChangeStateUserRoleService
{
    public function __construct(
        private UserRoleRepository $repository,
    ) {
    }

    public function execute(string $id, bool $state): UserRole
    {
        $role = $this->repository->ofId($id);
        if (null === $role) {
            throw new NotFoundException();
        }

        match ($state) {
            false => $role->disable(),
            true => $role->enable(),
        };

        $this->repository->save($role);

        return $role;
    }
}
