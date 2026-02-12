<?php

namespace App\apps\security\Controller;

use App\apps\security\Service\User\ChangeStateUserService;
use App\apps\security\Service\User\CreateUserService;
use App\apps\security\Service\User\DeleteUserService;
use App\apps\security\Service\User\DownloadUsersService;
use App\apps\security\Service\User\Dto\UserDto;
use App\apps\security\Service\User\Dto\UserDtoTransformer;
use App\apps\security\Service\User\Dto\UserFilterDto;
use App\apps\security\Service\User\GetRoles;
use App\apps\security\Service\User\GetUserService;
use App\apps\security\Service\User\GetUsersService;
use App\apps\security\Service\User\UpdateUserService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Api\DtoSerializer;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/users')]
class UserApi extends AbstractSerializerApi
{
    #[Route('/', name: 'user_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        UserFilterDto $filterDto,
        GetUsersService $usersService,
    ): Response {
        return $this->ok($usersService->execute($filterDto));
    }

    #[Route('/myroles', name: 'user_my_roles', methods: ['GET'])]
    public function myRoles(
        TokenStorageInterface $tokenStorage,
        GetRoles $getRoles,
    ): Response
    {
        $token = $tokenStorage->getToken();

        return $this->ok([
            $getRoles->execute($token),
        ]);
    }

    #[Route('/', name: 'user_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateUserService $userService,
        DtoSerializer $serializer,
        UserDtoTransformer $transformer,
    ): Response {
        /** @var UserDto $userDto */
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class);
        $user = $userService->execute($userDto);

        return $this->ok([
            'message' => 'Registro creado',
            'item' => $transformer->fromObject($user),
        ]);
    }

    #[Route('/{id}', name: 'user_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        Request $request,
        string $id,
        UpdateUserService $userService,
        DtoSerializer $serializer,
        UserDtoTransformer $transformer,
    ): Response {
        /** @var UserDto $userDto */
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class);
        $user = $userService->execute($id, $userDto);

        return $this->ok([
            'message' => 'Registro actualizado',
            'item' => $transformer->fromObject($user),
        ]);
    }

    #[Route('/{id}', name: 'user_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        GetUserService $userService,
        UserDtoTransformer $transformer,
    ): Response {
        $user = $userService->execute($id, true);

        return $this->ok([
            'message' => 'Registro obtenido',
            'item' => $transformer->fromObject($user),
        ]);
    }

    #[Route('/{id}/disable', name: 'user_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function disable(
        string $id,
        ChangeStateUserService $userService,
        UserDtoTransformer $transformer,
    ): Response {
        $user = $userService->execute($id, false);

        return $this->ok([
            'message' => 'Registro deshabilitado',
            'item' => $transformer->fromObject($user),
        ]);
    }

    #[Route('/{id}/enable', name: 'user_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function enable(
        string $id,
        ChangeStateUserService $userService,
        UserDtoTransformer $transformer,
    ): Response {
        $user = $userService->execute($id, true);

        return $this->ok([
            'message' => 'Registro habilitado',
            'item' => $transformer->fromObject($user),
        ]);
    }

    #[Route('/{id}', name: 'user_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(
        string $id,
        DeleteUserService $userService,
    ): Response {
        $userService->execute($id);

        return $this->ok([
            'message' => 'Registro borrado',
            'item' => null,
        ]);
    }

    #[Route('/download', name: 'user_download', methods: ['GET'])]
    public function download(
        #[MapQueryString]
        UserFilterDto $filterDto,
        DownloadUsersService $usersService,
    ): Response {
        return $usersService->execute($filterDto);
    }
}
