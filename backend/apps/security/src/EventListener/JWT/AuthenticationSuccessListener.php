<?php

namespace App\apps\security\EventListener\JWT;

use App\apps\security\Entity\User;
use App\apps\security\Service\Auth\UserLoginDtoTransformer;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;


final readonly class AuthenticationSuccessListener
{
    public function __construct(
        private UserLoginDtoTransformer $dtoTransformer,
    ) {
    }

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $data['status'] = true;
        /** @var User $user */
        $user = $event->getUser();
        $data['user'] = $this->dtoTransformer->fromObject($user);

        $event->setData($data);
    }
}
