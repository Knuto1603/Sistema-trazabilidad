<?php

namespace App\apps\security\Service\User;

use App\apps\security\Repository\UserRepository;

final readonly class DeleteUserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->userRepository->removeById($id);
    }
}
