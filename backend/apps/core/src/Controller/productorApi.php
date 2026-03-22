<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Productor\AssignProductorToCampahnaService;
use App\apps\core\Service\Productor\CreateProductorService;
use App\apps\core\Service\Productor\DeleteProductorService;
use App\apps\core\Service\Productor\Dto\ProductorDto;
use App\apps\core\Service\Productor\Dto\ProductorDtoTransformer;
use App\apps\core\Service\Productor\GetLastProductorCode;
use App\apps\core\Service\Productor\GetProductoresByCampahnaService;
use App\apps\core\Service\Productor\GetProductoresService;
use App\apps\core\Service\Productor\RemoveProductorFromCampahnaService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/productores')]
class ProductorApi extends AbstractSerializerApi
{
    #[Route('/', name: 'productor_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        FilterDto $filterDto,
        GetProductoresService $service,
    ): Response {
        return $this->ok($service->execute($filterDto));
    }

    #[Route('/', name: 'productor_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        ProductorDto $productorDto,
        CreateProductorService $productorService,
        ProductorDtoTransformer $transformer,
    ): Response
    {
        $productor = $productorService->execute($productorDto);
        return $this->ok([
            'message' => 'Productor creado exitosamente',
            'item' => $transformer->fromObject($productor),
        ]);
    }

    #[Route('/last-code', name: 'productor_last_code', methods: ['GET'])]
    public function lastCode(
        GetLastProductorCode $productorService,
    ): Response
    {
        $lastCode = $productorService->execute();
        return $this->ok(['lastCode' => $lastCode]);
    }

    #[Route('/campahna/{campahnaId}', name: 'productor_by_campahna', requirements: ['campahnaId' => UidType::REGEX], methods: ['GET'])]
    public function listByCampahna(
        string $campahnaId,
        #[MapQueryString]
        FilterDto $filterDto,
        GetProductoresByCampahnaService $service,
    ): Response {
        return $this->ok($service->execute($campahnaId, $filterDto));
    }

    #[Route('/campahna/{campahnaId}/assign', name: 'productor_assign_to_campahna', requirements: ['campahnaId' => UidType::REGEX], methods: ['POST'])]
    public function assignToCampahna(
        string $campahnaId,
        #[MapRequestPayload]
        array $payload,
        AssignProductorToCampahnaService $service,
    ): Response {
        $productorId = $payload['productorId'] ?? null;

        if (!$productorId) {
            return $this->fail('El campo productorId es requerido');
        }

        $productorCampahna = $service->execute($productorId, $campahnaId);

        return $this->ok([
            'message' => 'Productor asignado a la campaña exitosamente',
            'item' => [
                'productorId' => $productorCampahna->getProductor()->uuidToString(),
                'campahnaId' => $productorCampahna->getCampahna()->uuidToString(),
                'fechaIngreso' => $productorCampahna->getFechaIngreso()->format('Y-m-d'),
            ],
        ]);
    }

    #[Route('/campahna/{campahnaId}/assign-multiple', name: 'productor_assign_multiple_to_campahna', requirements: ['campahnaId' => UidType::REGEX], methods: ['POST'])]
    public function assignMultipleToCampahna(
        string $campahnaId,
        #[MapRequestPayload]
        array $payload,
        AssignProductorToCampahnaService $service,
    ): Response {
        $productorIds = $payload['productorIds'] ?? [];

        if (empty($productorIds)) {
            return $this->fail('El campo productorIds es requerido y debe contener al menos un ID');
        }

        $results = $service->executeMultiple($productorIds, $campahnaId);

        return $this->ok([
            'message' => sprintf('%d productor(es) asignado(s) a la campaña exitosamente', count($results)),
            'count' => count($results),
        ]);
    }

    #[Route('/campahna/{campahnaId}/remove/{productorId}', name: 'productor_remove_from_campahna', requirements: ['campahnaId' => UidType::REGEX, 'productorId' => UidType::REGEX], methods: ['DELETE'])]
    public function removeFromCampahna(
        string $campahnaId,
        string $productorId,
        RemoveProductorFromCampahnaService $service,
    ): Response {
        $service->execute($productorId, $campahnaId);

        return $this->ok([
            'message' => 'Productor removido de la campaña exitosamente',
        ]);
    }

    #[Route('/{id}', name: 'productor_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        GetProductoresService $service,
        ProductorDtoTransformer $transformer,
    ): Response {
        $filterDto = new FilterDto();
        $result = $service->execute($filterDto);

        $productor = null;
        foreach ($result['items'] as $item) {
            if ($item->uuid === $id) {
                $productor = $item;
                break;
            }
        }

        return $this->ok([
            'message' => 'Productor obtenido exitosamente',
            'item' => $productor,
        ]);
    }

    #[Route('/{id}', name: 'productor_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(
        string $id,
        DeleteProductorService $productorService,
    ): Response {
        $productorService->execute($id);

        return $this->ok([
            'message' => 'Productor eliminado exitosamente',
            'item' => null,
        ]);
    }
}
