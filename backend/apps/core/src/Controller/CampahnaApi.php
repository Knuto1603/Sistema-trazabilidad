<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Campahna\ChangeStateCampahnaService;
use App\apps\core\Service\Campahna\CreateCampahnaService;
use App\apps\core\Service\Campahna\DeleteCampahnaService;
use App\apps\core\Service\Campahna\DownloadCampahmasService;
use App\apps\core\Service\Campahna\Dto\CampahnaDto;
use App\apps\core\Service\Campahna\Dto\CampahnaDtoTransformer;
use App\apps\core\Service\Campahna\Filter\CampahnaFilterDto;
use App\apps\core\Service\Campahna\GetCampahnaService;
use App\apps\core\Service\Campahna\GetCampahmasService;
use App\apps\core\Service\Campahna\GetSharedCampahnaService;
use App\apps\core\Service\Campahna\UpdateCampahnaService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/campahnas')]
final class CampahnaApi extends AbstractSerializerApi
{
    #[Route('/', name: 'campahna_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        FilterDto $filterDto,
        GetCampahmasService $campahmasService,
    ): Response {
        return $this->ok($campahmasService->execute($filterDto));
    }

    #[Route('/filter_advanced', name: 'campahna_filter_advanced', methods: ['GET'])]
    public function filterAdvanced(
        #[MapQueryString]
        CampahnaFilterDto $filterDto,
        GetCampahmasService $campahmasService,
    ): Response {
        return $this->ok($campahmasService->execute($filterDto));
    }

    #[Route('/', name: 'campahna_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        CampahnaDto $campahnaDto,
        CreateCampahnaService $campahnaService,
        CampahnaDtoTransformer $transformer,
    ): Response {
        $campahna = $campahnaService->execute($campahnaDto);

        return $this->ok([
            'message' => 'Campaña creada exitosamente',
            'item' => $transformer->fromObject($campahna),
        ]);
    }

    #[Route('/{id}', name: 'campahna_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        #[MapRequestPayload]
        CampahnaDto $campahnaDto,
        string $id,
        UpdateCampahnaService $campahnaService,
        CampahnaDtoTransformer $transformer,
    ): Response {
        $campahna = $campahnaService->execute($id, $campahnaDto);

        return $this->ok([
            'message' => 'Campaña actualizada exitosamente',
            'item' => $transformer->fromObject($campahna),
        ]);
    }

    #[Route('/{id}', name: 'campahna_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        GetCampahnaService $campahnaService,
        CampahnaDtoTransformer $transformer,
    ): Response {
        $campahna = $campahnaService->execute($id, true);

        return $this->ok([
            'message' => 'Campaña obtenida exitosamente',
            'item' => $transformer->fromObject($campahna),
        ]);
    }

    #[Route('/{id}/disable', name: 'campahna_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function disable(
        string $id,
        ChangeStateCampahnaService $campahnaService,
        CampahnaDtoTransformer $transformer,
    ): Response {
        $campahna = $campahnaService->execute($id, false);

        return $this->ok([
            'message' => 'Campaña deshabilitada exitosamente',
            'item' => $transformer->fromObject($campahna),
        ]);
    }

    #[Route('/{id}/enable', name: 'campahna_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function enable(
        string $id,
        ChangeStateCampahnaService $campahnaService,
        CampahnaDtoTransformer $transformer,
    ): Response {
        $campahna = $campahnaService->execute($id, true);

        return $this->ok([
            'message' => 'Campaña habilitada exitosamente',
            'item' => $transformer->fromObject($campahna),
        ]);
    }

    #[Route('/{id}', name: 'campahna_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(
        string $id,
        DeleteCampahnaService $campahnaService,
    ): Response {
        $campahnaService->execute($id);

        return $this->ok([
            'message' => 'Campaña eliminada exitosamente',
            'item' => null,
        ]);
    }

    #[Route('/download', name: 'campahna_download', methods: ['GET'])]
    public function __invoke(
        #[MapQueryString]
        FilterDto $filterDto,
        DownloadCampahmasService $campahmasService,
    ): Response {
        return $campahmasService->execute($filterDto);
    }

    #[Route('/shared', name: 'campahna_list_shared', methods: ['GET'])]
    public function listShared(
        GetSharedCampahnaService $campahnaService,
    ): Response {
        return $this->ok(['items' => $campahnaService->execute()]);
    }
}
