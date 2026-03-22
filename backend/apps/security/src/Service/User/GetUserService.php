<?php

namespace App\apps\security\Service\User;

use App\apps\security\Entity\User;
use App\apps\security\Repository\UserRepository;
use App\shared\Exception\NotFoundException;

final readonly class GetUserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function execute(string $id, bool $strict = false): ?User
    {
        $user = $this->userRepository->ofId($id);
        if (true === $strict && null === $user) {
            throw new NotFoundException('Dto not found');
        }

        return $user;
    }
}