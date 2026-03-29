<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Operacion\CreateOperacionService;
use App\apps\core\Service\Operacion\DeleteOperacionService;
use App\apps\core\Service\Operacion\Dto\OperacionDto;
use App\apps\core\Service\Operacion\Dto\OperacionDtoTransformer;
use App\apps\core\Service\Operacion\GetOperacionesService;
use App\apps\core\Service\Operacion\UpdateOperacionService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/operaciones')]
class OperacionApi extends AbstractSerializerApi
{
    #[Route('/', name: 'operacion_list', methods: ['GET'])]
    public function list(
        Request $request,
        GetOperacionesService $service,
    ): Response {
        $sede = $request->query->get('sede');
        return $this->ok($service->execute($sede ?: null));
    }

    #[Route('/', name: 'operacion_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload]
        OperacionDto $dto,
        CreateOperacionService $service,
        OperacionDtoTransformer $transformer,
    ): Response {
        $operacion = $service->execute($dto);

        return $this->ok([
            'message' => 'Operación creada exitosamente',
            'item' => $transformer->fromObject($operacion),
        ]);
    }

    #[Route('/{id}', name: 'operacion_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        string $id,
        #[MapRequestPayload]
        OperacionDto $dto,
        UpdateOperacionService $service,
        OperacionDtoTransformer $transformer,
    ): Response {
        $operacion = $service->execute($id, $dto);

        return $this->ok([
            'message' => 'Operación actualizada exitosamente',
            'item' => $transformer->fromObject($operacion),
        ]);
    }

    #[Route('/{id}/enable', name: 'operacion_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function enable(
        string $id,
        \App\apps\core\Repository\OperacionRepository $repository,
        OperacionDtoTransformer $transformer,
    ): Response {
        $operacion = $repository->ofId($id, true);
        $operacion->enable();
        $repository->save($operacion);

        return $this->ok([
            'message' => 'Operación habilitada',
            'item' => $transformer->fromObject($operacion),
        ]);
    }

    #[Route('/{id}/disable', name: 'operacion_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function disable(
        string $id,
        \App\apps\core\Repository\OperacionRepository $repository,
        OperacionDtoTransformer $transformer,
    ): Response {
        $operacion = $repository->ofId($id, true);
        $operacion->disable();
        $repository->save($operacion);

        return $this->ok([
            'message' => 'Operación deshabilitada',
            'item' => $transformer->fromObject($operacion),
        ]);
    }

    #[Route('/{id}', name: 'operacion_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        string $id,
        DeleteOperacionService $service,
    ): Response {
        $service->execute($id);

        return $this->ok([
            'message' => 'Operación eliminada exitosamente',
            'item' => null,
        ]);
    }
}
