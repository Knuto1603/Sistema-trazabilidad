<?php

namespace App\apps\security\Service\UserRole\Dto;

use App\apps\security\Entity\UserRole;
use App\shared\Service\Transformer\DtoTransformer;

final class UserRoleDtoTransformer extends DtoTransformer
{
    /** @param UserRole $object */
    public function fromObject(mixed $object): ?UserRoleDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new UserRoleDto();
        $dto->name = $object->getName();
        $dto->alias = $object->getAlias();

        // Contar usuarios o incluir sus IDs si es necesario
        $dto->userCount = $object->getUsers()->count();

        // Opcionalmente, incluir IDs de usuarios si se necesita
        if ($object->getUsers()->count() > 0) {
            $dto->userIds = array_map(
                fn($user) => $user->uuidToString(),
                $object->getUsers()->toArray()
            );
        }

        $dto->ofEntity($object);

        return $dto;
    }
}
