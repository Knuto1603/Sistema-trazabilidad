<?php

namespace App\apps\security\Service\User;

use App\apps\security\Entity\User;
use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\User\Dto\UserDto;
use App\apps\security\Service\User\Dto\UserFactory;
use App\shared\Exception\NotFoundException;
use App\shared\Exception\RepositoryException;

final readonly class UpdateUserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserFactory $userFactory,
    ) {
    }

    public function execute(string $id, UserDto $userDto): User
    {
        $user = $this->getUser($id);
        $this->isValid($user, $userDto);
        $this->userFactory->updateOfDto($userDto, $user);
        $this->userRepository->save($user);

        return $user;
    }

    private function getUser(string $id): User
    {
        $user = $this->userRepository->ofId($id);
        if (null === $user) {
            throw new NotFoundException();
        }

        return $user;
    }

    public function isValid(User $user, UserDto $userDto): void
    {
        if (null === $userDto->username || $user->getUsername() === $userDto->username) {
            return;
        }

        if ($this->userRepository->usernameExists($userDto->username)) {
            throw new epositoryException(\sprintf('Username %s already exists', $userDto->username));
        }
    }
}
