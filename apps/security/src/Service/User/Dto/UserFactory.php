<?php

namespace App\apps\security\Service\User\Dto;

use App\apps\security\Entity\User;
use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRoleRepository;
use App\shared\Doctrine\UidType;
use App\shared\Service\TextCleaner;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserFactory
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private TextCleaner $cleaner,
        private UserRoleRepository $userRoleRepository,
    ) {
    }

    public function ofDto(?UserDto $dto): ?User
    {
        if (null === $dto) {
            return null;
        }

        $user = new User();
        $this->updateOfDto($dto, $user);

        if ($user->getRol()->isEmpty()) {
            $this->assignDefaultRole($user);
        }

        return $user;
    }

    private function assignDefaultRole(User $user): void
    {
        // Buscar el rol ROLE_USER
        $userRole = $this->userRoleRepository->findOneBy(['name' => 'ROLE_USER']);

        // Si no existe, crearlo
        if (null === $userRole) {
            $userRole = new UserRole();
            $userRole->setName('ROLE_USER');
            $userRole->setAlias('User');
            $userRole->enable();

            // Guardar el rol en la base de datos
            $entityManager = $this->userRoleRepository->getEntityManager();
            $entityManager->persist($userRole);
            $entityManager->flush();
        }

        // Asignar el rol al usuario
        $user->addRol($userRole);
    }

    public function updateOfDto(UserDto $dto, User $user): void
    {
        $username = $dto->username ?? $user->getUsername() ?? 'no-username';
        $user->setUsername($this->cleaner->username($username));
        $user->setFullName($dto->fullname ?? $user->getFullName());
        if (null !== $dto->password) {
            $user->setPassword($this->passwordEncrypt($dto->password, $user));
        }
        $user->setGender($dto->gender ? UidType::fromString($dto->gender) : null);
        match ($dto->isActive) {
            false => $user->disable(),
            default => $user->enable(),
        };
        if (null !== $dto->roles) {
            $this->updateRoles($user, $dto->roles);
        }

    }

    /**
     * Actualiza los roles del usuario
     */
    private function updateRoles(User $user, array $roleIds): void
    {
        // Primero, eliminar todos los roles actuales
        foreach ($user->getRol()->toArray() as $currentRole) {
            $user->removeRol($currentRole);
        }

        // Añadir los nuevos roles
        foreach ($roleIds as $roleId) {
            $role = $this->userRoleRepository->ofId($roleId);
            if (null !== $role) {
                $user->addRol($role);
            }
        }
        if ($user->getRol()->isEmpty()) {
            $this->assignDefaultRole($user);
        }
    }

    public function passwordEncrypt(string $password, User $user): string
    {
        return $this->passwordHasher->hashPassword($user, $password);
    }
}
