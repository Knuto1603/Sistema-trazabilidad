<?php

namespace App\apps\security\Controller;

use App\apps\security\Service\UserRole\ChangeStateUserRoleService;
use App\apps\security\Service\UserRole\CreateUserRoleService;
use App\apps\security\Service\UserRole\DeleteUserRoleService;
use App\apps\security\Service\UserRole\Dto\UserRoleDto;
use App\apps\security\Service\UserRole\Dto\UserRoleDtoTransformer;
use App\apps\security\Service\UserRole\GetSharedRoleUser;
use App\apps\security\Service\UserRole\GetUserRoleService;
use App\apps\security\Service\UserRole\GetUserRolesService;
use App\apps\security\Service\UserRole\UpdateUserRoleService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Api\DtoSerializer;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user_roles')]
class UserRoleApi extends AbstractSerializerApi
{
    #[Route('/', name: 'user_role_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        FilterDto $filterDto,
        GetUserRolesService $rolesService
    ): Response
    {
        return $this->ok($rolesService->execute($filterDto));
    }

    #[Route('/', name: 'user_role_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateUserRoleService $roleService,
        DtoSerializer $serializer,
        UserRoleDtoTransformer $transformer,
    ): Response {
        /** @var UserRoleDto $roleDto */
        $roleDto = $serializer->deserialize($request->getContent(), UserRoleDto::class);
        $role = $roleService->execute($roleDto);

        return $this->ok([
            'message' => 'Dto Role created',
            'item' => $transformer->fromObject($role),
        ]);
    }

    #[Route('/{id}', name: 'user_role_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        Request $request,
        string $id,
        UpdateUserRoleService $roleService,
        DtoSerializer $serializer,
        UserRoleDtoTransformer $transformer,
    ): Response {
        /** @var UserRoleDto $roleDto */
        $roleDto = $serializer->deserialize($request->getContent(), UserRoleDto::class);
        $role = $roleService->execute($id, $roleDto);

        return $this->ok([
            'message' => 'Dto Role updated',
            'item' => $transformer->fromObject($role),
        ]);
    }

    #[Route('/{id}', name: 'user_role_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        GetUserRoleService $roleService,
        UserRoleDtoTransformer $transformer,
    ): Response {
        $role = $roleService->execute($id, true);

        return $this->ok([
            'message' => 'Dto Role found',
            'item' => $transformer->fromObject($role),
        ]);
    }

    #[Route('/{id}/disable', name: 'user_role_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function disable(
        string $id,
        ChangeStateUserRoleService $roleService,
        UserRoleDtoTransformer $transformer,
    ): Response {
        $role = $roleService->execute($id, false);

        return $this->ok([
            'message' => 'Dto Role disable',
            'item' => $transformer->fromObject($role),
        ]);
    }

    #[Route('/{id}/enable', name: 'user_role_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function enable(
        string $id,
        ChangeStateUserRoleService $roleService,
        UserRoleDtoTransformer $transformer,
    ): Response {
        $role = $roleService->execute($id, true);

        return $this->ok([
            'message' => 'Dto Role enable',
            'item' => $transformer->fromObject($role),
        ]);
    }

    #[Route('/{id}', name: 'user_role_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(
        string $id,
        DeleteUserRoleService $roleService,
    ): Response {
        $roleService->execute($id);

        return $this->ok([
            'message' => 'Dto Role delete',
            'item' => null,
        ]);
    }

    #[Route('/shared', name: 'role_user_list_shared', methods: ['GET'])]
    public function almacenShared(
        GetSharedRoleUser $roleUserService,
    ): Response {
        return $this->ok(['items' => $roleUserService->execute()]);
    }
}
