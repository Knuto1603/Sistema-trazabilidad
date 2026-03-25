<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\ApispEru\ApispEruService;
use App\apps\core\Service\Cliente\ChangeStateClienteService;
use App\apps\core\Service\Cliente\CreateClienteService;
use App\apps\core\Service\Cliente\DeleteClienteService;
use App\apps\core\Service\Cliente\Dto\ClienteDto;
use App\apps\core\Service\Cliente\Dto\ClienteDtoTransformer;
use App\apps\core\Service\Cliente\GetClienteService;
use App\apps\core\Service\Cliente\GetClientesService;
use App\apps\core\Service\Cliente\UpdateClienteService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clientes')]
class ClienteApi extends AbstractSerializerApi
{
    #[Route('/', name: 'cliente_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        FilterDto $filterDto,
        GetClientesService $service,
    ): Response {
        return $this->ok($service->execute($filterDto));
    }

    #[Route('/', name: 'cliente_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        ClienteDto $dto,
        CreateClienteService $service,
        ClienteDtoTransformer $transformer,
    ): Response {
        $cliente = $service->execute($dto);

        return $this->ok([
            'message' => 'Cliente creado exitosamente',
            'item' => $transformer->fromObject($cliente),
        ]);
    }

    #[Route('/search-ruc/{ruc}', name: 'cliente_search_ruc', requirements: ['ruc' => '\d{11}'], methods: ['GET'])]
    public function searchRuc(
        string $ruc,
        ApispEruService $apispEruService,
    ): Response {
        try {
            $data = $apispEruService->consultarRuc($ruc);

            return $this->ok([
                'message' => 'RUC encontrado',
                'item' => $data,
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/{id}', name: 'cliente_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        GetClienteService $service,
        ClienteDtoTransformer $transformer,
    ): Response {
        $cliente = $service->execute($id);

        return $this->ok([
            'message' => 'Cliente obtenido exitosamente',
            'item' => $transformer->fromObject($cliente),
        ]);
    }

    #[Route('/{id}', name: 'cliente_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        string $id,
        #[MapRequestPayload]
        ClienteDto $dto,
        UpdateClienteService $service,
        ClienteDtoTransformer $transformer,
    ): Response {
        $cliente = $service->execute($id, $dto);

        return $this->ok([
            'message' => 'Cliente actualizado exitosamente',
            'item' => $transformer->fromObject($cliente),
        ]);
    }

    #[Route('/{id}/enable', name: 'cliente_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function enable(
        string $id,
        ChangeStateClienteService $service,
        ClienteDtoTransformer $transformer,
    ): Response {
        $cliente = $service->execute($id, true);

        return $this->ok([
            'message' => 'Cliente activado exitosamente',
            'item' => $transformer->fromObject($cliente),
        ]);
    }

    #[Route('/{id}/disable', name: 'cliente_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function disable(
        string $id,
        ChangeStateClienteService $service,
        ClienteDtoTransformer $transformer,
    ): Response {
        $cliente = $service->execute($id, false);

        return $this->ok([
            'message' => 'Cliente desactivado exitosamente',
            'item' => $transformer->fromObject($cliente),
        ]);
    }

    #[Route('/{id}', name: 'cliente_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        string $id,
        DeleteClienteService $service,
    ): Response {
        $service->execute($id);

        return $this->ok([
            'message' => 'Cliente eliminado exitosamente',
            'item' => null,
        ]);
    }
}
