<?php

namespace App\apps\security\Service\User;

use App\apps\security\Entity\User;
use App\apps\security\Repository\UserRepository;
use App\apps\security\Service\User\Dto\UserDto;
use App\apps\security\Service\User\Dto\UserFactory;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

final readonly class CreateUserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserFactory $userFactory,
    ) {
    }

    public function execute(UserDto $userDto): User
    {
        $this->validOrException($userDto);
        $user = $this->userFactory->ofDto($userDto);
        $this->userRepository->save($user);

        return $user;
    }

    public function validOrException(UserDto $userDto): void
    {
        if (null === $userDto->username) {
            throw new MissingParameterException('Missing parameter username');
        }
        if (null === $userDto->password) {
            throw new MissingParameterException('Missing parameter password');
        }

        if (null !== $this->userRepository->findOneBy(['username' => $userDto->username])) {
            throw new RepositoryException(\sprintf('Username %s already exists', $userDto->username));
        }
    }
}