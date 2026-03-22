<?php

namespace App\apps\security\Service\Auth;

use App\apps\security\Entity\User;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class UserLoginDtoTransformer extends DtoTransformer
{
    /** @param User $object */
    public function fromObject(mixed $object): ?UserLoginDto
    {
        if (null === $object) {
            return null;
        }

        return new UserLoginDto(
            id: UidType::toString($object->uuid()),
            username: $object->getUsername(),
            fullname: $object->getFullName(),
            roles: $object->getRoles(),
        );
    }
}
