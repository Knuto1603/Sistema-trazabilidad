<?php

namespace App\apps\security\Service\UserRole\Dto;

use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRepository;

final class UserRoleFactory
{
    public function __construct(
        private UserRepository $userRepository,
    )
    {
    }

    public function ofDto(?UserRoleDto $dto): ?UserRole
    {
        if (null === $dto) {
            return null;
        }

        $role = new UserRole();
        $this->updateOfDto($dto, $role);

        return $role;
    }

    public function updateOfDto(UserRoleDto $dto, UserRole $role): void
    {
        $role->setName($dto->name);
        $role->setAlias($dto->alias);

        match ($dto->isActive) {
            false => $role->disable(),
            default => $role->enable(),
        };

        // Actualizar usuarios asignados si se proporcionaron IDs
        if (null !== $dto->userIds) {
            $this->updateUsers($role, $dto->userIds);
        }
    }

    /**
     * Actualiza los usuarios asignados a este rol
     */
    private function updateUsers(UserRole $role, array $userIds): void
    {
        // Remover usuarios actuales
        foreach ($role->getUsers()->toArray() as $currentUser) {
            $role->removeUser($currentUser);
        }

        // Añadir nuevos usuarios
        foreach ($userIds as $userId) {
            $user = $this->userRepository->ofId($userId);
            if (null !== $user) {
                $role->addUser($user);
            }
        }
    }
}
