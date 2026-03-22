<?php

namespace App\apps\security\Service\UserRole;

use App\apps\security\Repository\UserRoleRepository;

final readonly class DeleteUserRoleService
{
    public function __construct(
        private UserRoleRepository $repository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->repository->removeById($id);
    }
}
