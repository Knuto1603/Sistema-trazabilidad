<?php

namespace App\apps\security\EventListener\JWT;

use App\apps\security\Entity\User;
use App\shared\Doctrine\UidType;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;


final readonly class CreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $payload = $event->getData();
        $payload['id'] = UidType::toString($user->uuid());
        $payload['roles'] = $user->getRoles();
        $event->setData($payload);
    }
}