<?php

namespace App\apps\security\Service\User;

use App\apps\security\Entity\User;
use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\User\Dto\UpdateMeDto;
use App\shared\Exception\NotFoundException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UpdateMeService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function execute(string $uuid, UpdateMeDto $dto): User
    {
        $user = $this->userRepository->ofId($uuid);
        if (null === $user) {
            throw new NotFoundException();
        }

        if ($dto->fullname !== null) {
            $user->setFullName($dto->fullname);
        }

        if ($dto->password !== null && $dto->password !== '') {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        }

        $this->userRepository->save($user);

        return $user;
    }
}
