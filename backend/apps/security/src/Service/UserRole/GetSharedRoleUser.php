<?php

namespace App\apps\security\Service\UserRole;

use App\apps\security\Repository\UserRoleRepository;
use App\shared\Doctrine\UidType;

final readonly  class GetSharedRoleUser
{
    public function __construct(
        private UserRoleRepository $userRoleRepository,
    ) {
    }

    public function execute(): iterable
    {
        $list = $this->userRoleRepository->allShared();

        return array_map(function ($item) {
            return [
                'id' => UidType::toString($item['id']),
                'name' => $item['name'],
            ];
        }, $list);
    }
}
