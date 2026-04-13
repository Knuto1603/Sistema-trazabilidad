<?php

namespace App\apps\core\Controller;

use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FrutaRepository;
use App\apps\core\Repository\OperacionRepository;
use App\apps\core\Service\Despacho\CreateDespachoService;
use App\apps\core\Service\Despacho\Dto\EnviarCorreoDto;
use App\apps\core\Service\Despacho\EnviarCorreoDespachoService;
use App\apps\core\Service\Despacho\DeleteDespachoService;
use App\apps\core\Service\Despacho\Dto\DespachoDto;
use App\apps\core\Service\Despacho\Dto\DespachoDtoTransformer;
use App\apps\core\Service\Despacho\Filter\DespachoFilterDto;
use App\apps\core\Service\Despacho\GetDespachoService;
use App\apps\core\Service\Despacho\GetDespachosService;
use App\apps\core\Service\Despacho\UpdateDespachoService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/despachos')]
class DespachoApi extends AbstractSerializerApi
{
    #[Route('/proximo-numero', name: 'despacho_proximo_numero', methods: ['GET'])]
    public function proximoNumero(
        Request $request,
        DespachoRepository $despachoRepository,
        OperacionRepository $operacionRepository,
        FrutaRepository $frutaRepository,
    ): Response {
        $operacionUid = $request->query->get('operacionId');
        $frutaUid = $request->query->get('frutaId');

        if ($operacionUid) {
            try {
                $operacion = $operacionRepository->ofId($operacionUid, true);
                $frutaDbId = null;
                if ($frutaUid) {
                    $fruta = $frutaRepository->ofId($frutaUid, true);
                    $frutaDbId = $fruta->getId();
                }
                $numeroPlanta = $despachoRepository->findMaxNumeroPlantaByOperacion($operacion->getId(), $frutaDbId) + 1;
            } catch (\Throwable) {
                $numeroPlanta = 1;
            }
        } else {
            $numeroPlanta = $despachoRepository->findMaxNumeroPlanta() + 1;
        }

        return $this->ok(['item' => ['numeroPlanta' => $numeroPlanta]]);
    }

    #[Route('/proximo-numero-cliente', name: 'despacho_proximo_numero_cliente', methods: ['GET'])]
    public function proximoNumeroCliente(
        Request $request,
        DespachoRepository $despachoRepository,
        ClienteRepository $clienteRepository,
        OperacionRepository $operacionRepository,
        FrutaRepository $frutaRepository,
    ): Response {
        $clienteUid = $request->query->get('clienteId');
        $operacionUid = $request->query->get('operacionId');
        $frutaUid = $request->query->get('frutaId');

        if (!$clienteUid) {
            return $this->ok(['item' => ['numeroCliente' => 1]]);
        }

        try {
            $cliente = $clienteRepository->ofId($clienteUid, true);
            $frutaDbId = null;
            if ($frutaUid) {
                $fruta = $frutaRepository->ofId($frutaUid, true);
                $frutaDbId = $fruta->getId();
            }

            if ($operacionUid) {
                $operacion = $operacionRepository->ofId($operacionUid, true);
                $numeroCliente = $despachoRepository->findMaxNumeroClienteByOperacion(
                    $cliente->getId(),
                    $operacion->getId(),
                    $frutaDbId
                ) + 1;
            } else {
                $numeroCliente = $despachoRepository->findMaxNumeroCliente($cliente->getId()) + 1;
            }
        } catch (\Throwable) {
            $numeroCliente = 1;
        }

        return $this->ok(['item' => ['numeroCliente' => $numeroCliente]]);
    }

    #[Route('/', name: 'despacho_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        DespachoFilterDto $filterDto,
        GetDespachosService $service,
    ): Response {
        return $this->ok($service->execute($filterDto));
    }

    #[Route('/', name: 'despacho_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        DespachoDto $dto,
        CreateDespachoService $service,
        DespachoDtoTransformer $transformer,
    ): Response {
        $despacho = $service->execute($dto);

        return $this->ok([
            'message' => 'Despacho creado exitosamente',
            'item' => $transformer->fromObject($despacho),
        ]);
    }

    #[Route('/{id}', name: 'despacho_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        GetDespachoService $service,
        DespachoDtoTransformer $transformer,
    ): Response {
        $despacho = $service->execute($id);

        return $this->ok([
            'message' => 'Despacho obtenido exitosamente',
            'item' => $transformer->fromObject($despacho),
        ]);
    }

    #[Route('/{id}', name: 'despacho_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        string $id,
        #[MapRequestPayload]
        DespachoDto $dto,
        UpdateDespachoService $service,
        DespachoDtoTransformer $transformer,
    ): Response {
        $despacho = $service->execute($id, $dto);

        return $this->ok([
            'message' => 'Despacho actualizado exitosamente',
            'item' => $transformer->fromObject($despacho),
        ]);
    }

    #[Route('/{id}/preview-correo', name: 'despacho_preview_correo', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function previewCorreo(
        string $id,
        EnviarCorreoDespachoService $service,
    ): Response {
        try {
            $preview = $service->preview($id);
            return $this->ok(['item' => $preview]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/{id}/enviar-correo', name: 'despacho_enviar_correo', requirements: ['id' => UidType::REGEX], methods: ['POST'])]
    public function enviarCorreo(
        string $id,
        #[MapRequestPayload]
        EnviarCorreoDto $dto,
        EnviarCorreoDespachoService $service,
    ): Response {
        try {
            $service->execute($id, $dto->asunto, $dto->cuerpo, $dto->destinatarios, $dto->archivosIds);
            return $this->ok(['message' => 'Correo enviado exitosamente', 'item' => null]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/{id}', name: 'despacho_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        string $id,
        DeleteDespachoService $service,
    ): Response {
        $service->execute($id);

        return $this->ok([
            'message' => 'Despacho eliminado exitosamente',
            'item' => null,
        ]);
    }
}
