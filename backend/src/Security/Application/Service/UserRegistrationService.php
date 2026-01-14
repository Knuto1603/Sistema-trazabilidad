<?php

namespace App\Security\Application\Service;

use App\Security\Application\Dto\UserRegistrationDto;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Caso de uso: Registrar un nuevo usuario.
 */
class UserRegistrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function execute(UserRegistrationDto $dto): User
    {
        $user = new User();
        $user->setUsername($dto->username);
        $user->setNombreCompleto($dto->nombreCompleto);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        // Aquí podrías añadir lógica para asignar roles desde las entidades de Role

        $this->userRepository->save($user);

        return $user;
    }
}
