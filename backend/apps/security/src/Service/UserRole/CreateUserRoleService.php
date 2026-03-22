<?php

namespace App\apps\security\Service\UserRole;

use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRoleRepository;
use App\apps\security\Service\UserRole\Dto\UserRoleDto;
use App\apps\security\Service\UserRole\Dto\UserRoleFactory;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

final readonly class CreateUserRoleService
{
    public function __construct(
        private UserRoleRepository $roleRepository,
        private UserRoleFactory $roleFactory,
    ) {
    }

    public function execute(UserRoleDto $roleDto): UserRole
    {
        if (null === $roleDto->name) {
            throw new MissingParameterException('Missing parameter name');
        }
        if (null === $roleDto->alias) {
            throw new MissingParameterException('Missing parameter alias');
        }

        if (null !== $this->roleRepository->findOneBy(['alias' => $roleDto->alias])) {
            throw new RepositoryException(\sprintf('Alias %s already exists', $roleDto->alias));
        }

        $role = $this->roleFactory->ofDto($roleDto);
        $this->roleRepository->save($role);

        return $role;
    }
}
