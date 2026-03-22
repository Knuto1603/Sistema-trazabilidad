<?php

namespace App\apps\security\Service\User;

use App\apps\security\Entity\User;
use App\apps\security\Repository\UserRepository;
use App\shared\Exception\NotFoundException;

final readonly class ChangeStateUserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function execute(string $id, bool $state): User
    {
        $user = $this->userRepository->ofId($id);
        if (null === $user) {
            throw new NotFoundException();
        }

        match ($state) {
            false => $user->disable(),
            true => $user->enable(),
        };

        $this->userRepository->save($user);

        return $user;
    }
}