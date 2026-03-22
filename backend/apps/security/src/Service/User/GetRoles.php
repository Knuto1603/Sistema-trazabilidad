<?php

namespace App\apps\security\Service\User;

use App\apps\security\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class GetRoles
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function execute(TokenInterface $token)
    {
        return $this->userRepository->rolesOfUser(
            $token->getUser()->getUserIdentifier()
        );
    }
}
