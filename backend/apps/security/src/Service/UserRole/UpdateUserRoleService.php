<?php

namespace App\apps\security\Service\UserRole;

use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRoleRepository;
use App\apps\security\Service\UserRole\Dto\UserRoleDto;
use App\apps\security\Service\UserRole\Dto\UserRoleFactory;
use App\shared\Exception\RepositoryException;

final readonly class UpdateUserRoleService
{
    public function __construct(
        private UserRoleRepository $roleRepository,
        private UserRoleFactory $roleFactory,
        private GetUserRoleService $getUserRole,
    ) {
    }

    public function execute(string $id, UserRoleDto $roleDto): UserRole
    {
        $role = $this->getUserRole->execute($id, true);
        if ($role->getAlias() !== $roleDto->alias
            && null !== $this->roleRepository->findOneBy(['alias' => $roleDto->alias])
        ) {
            throw new RepositoryException(\sprintf('Alias %s already exists', $roleDto->alias));
        }
        $this->roleFactory->updateOfDto($roleDto, $role);
        $this->roleRepository->save($role);

        return $role;
    }
}
